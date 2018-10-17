<?php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/settings.php';

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/google_credentials.json');

    $client = new Google_Client();
    $client->useApplicationDefaultCredentials();

    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);

    return $client;
}

function publicSectorEmail ( $email ) {
    /* Only do this for:
    - *.gov.uk
    - *.police.uk
    - *.nhs.uk
    - (*.)nhs.net
    - (*.)gov.je
    - (*.)gov.scot
    - (*.)gov.wales
    */
    return preg_match( '/^[\w\'\.+\-]+@(?>[\w\.\-]+\.(?>gov|police|nhs)\.uk|(?:[\w\.\-]+\.)?(?>nhs.net|gov.je|gov.scot|gov.wales))$/i', $email );
}

function inviteToSlack( $email )
{
    try {
        // If there's no Slack token, don't bother
        if ( empty( SLACK_TOKEN ) )
            return false;

        if ( !publicSectorEmail( $email ) )
            return false;

        $client = new GuzzleHttp\Client([ 'base_uri' => 'https://localgovdigital.slack.com' ]);

        // Based on guidance in https://github.com/ErikKalkoken/slackApiDoc/blob/master/users.admin.invite.md
        // Invite email address and resend if the invite email was sent a while ago
        $response = $client->request('POST', '/api/users.admin.invite', [
            'form_params' => [
                'email' => $email,
                'token' => SLACK_TOKEN,
                'set_active' => true,
                'resend' => true
            ]
        ]);
        
        $body = json_decode( $response->getBody() );

        // If OK or if already invited etc, return true
        return ($body->{'ok'} || ( isset( $body->{'error'} ) && in_array( $body->{'error'}, array('already_invited', 'already_in_team', 'sent_recently', 'user_disabled') ) ) );

    } catch (Exception $e) {
        // Any sort of error, return false for further investigation
        return false;
    }
}
