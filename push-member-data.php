<?php
require __DIR__ . '/vendor/autoload.php';

putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/google_credentials.json');

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->useApplicationDefaultCredentials();

    // $client->setApplicationName('LocalGov Digital Membership Register');
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);

    return $client;
}

function inviteToSlack( $email )
{
    try {
        if ( empty( $_ENV['slack_token'] ) )
            return false;
        
        if ( !preg_match( "/^[a-z\.+\-'@]+(?>\.gov\.uk|(?>@|\.)nhs.net)$/igm", $email ) )
            return false;

        $client = new Client([ 'base_uri' => 'https://localgovdigital.slack.com' ]);

        $response = $client->request('POST', '/api/users.admin.invite', [
            'form_params' => [
                'email' => $email,
                'token' => $_ENV['slack_token'],
                'set_active' => true
            ]
        ]);

        $body = json_decode( $response->getBody() );

        return $body.ok;

    } catch (Exception $e) {
        return false;
    }
}
