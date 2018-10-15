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

function inviteToSlack( $email )
{
    try {
        // If there's no Slack token, don't bother
        if ( empty( SLACK_TOKEN ) )
            return false;

        /* Only do this for:
            - *.gov.uk
            - *.police.uk
            - *.nhs.uk
            - (*.)nhs.net
            - (*.)gov.scot
            - (*.)gov.wales
         */
        if ( !preg_match( '^[\w\.+\-]+@(?>[\w\.\-]+\.(?>gov|police|nhs)\.uk|(?:[\w\.\-]+\.)?(?>nhs.net|gov.scot|gov.wales))$/i', $email ) )
            return false;

        $client = new GuzzleHttp\Client([ 'base_uri' => 'https://localgovdigital.slack.com' ]);

        $response = $client->request('POST', '/api/users.admin.invite', [
            'form_params' => [
                'email' => $email,
                'token' => SLACK_TOKEN,
                'set_active' => true
            ]
        ]);
        
        $body = json_decode( $response->getBody() );

        return $body->{'ok'};

    } catch (Exception $e) {
        return false;
    }
}
