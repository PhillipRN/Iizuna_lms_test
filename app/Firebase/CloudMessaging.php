<?php

namespace IizunaLMS\Firebase;

use Google\Exception;
use Google_Client;
use GuzzleHttp\Exception\GuzzleException;

class CloudMessaging
{
    /**
     * @param $title
     * @param $body
     * @param $token
     * @return mixed
     * @throws Exception
     * @throws GuzzleException
     */
    public function SendByToken($title, $body, $token)
    {
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'body' => $body,
                    'title' => $title
                ],
                // Android
                'android' => [
                    'priority' => 'high'
                ],
                // iOS
                'apns' => [
                    'headers' => [
                        'apns-priority' => '5',
                    ],
                    'payload' => [
                        'aps' => [
                            'content-available' => 1,
                        ],
                    ]
                ]
            ]
        ];

        return $this->Send($message);
    }

//    /**
//     * @param $title
//     * @param $body
//     * @param $topic
//     * @return mixed
//     * @throws Exception
//     * @throws GuzzleException
//     */
//    public function SendByTopic($title, $body, $topic)
//    {
//        $message = [
//            'message' => [
//                'topic' => $topic,
//                'notification' => [
//                    'body' => $body,
//                    'title' => $title,
//                ],
//                // Android
//                'android' => [
//                    'priority' => 'HIGH',
//                    'notification' => [
//                        'notification_count' => 1
//                    ]
//                ],
//                // iOS
//                'apns' => [
//                    'payload' => [
//                        'aps' => [
//                            'badge' => 1,
//                            'mutable-content' => 1,
//                            'content-available' => 1
//                        ]
//                    ]
//                ],
//            ]
//        ];
//
//        return $this->Send($message);
//    }

    /**
     * @param $message
     * @return mixed
     * @throws Exception
     * @throws GuzzleException
     */
    private function Send($message)
    {
        $client = new Google_Client();

        // Authentication with the GOOGLE_APPLICATION_CREDENTIALS environment variable
        $client->useApplicationDefaultCredentials();

        // Alternatively, provide the JSON authentication file directly.
        $client->setAuthConfig(ROOT_DIR . '/app/firebase_auth.json');

        // Add the scope as a string (multiple scopes can be provided as an array)
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        // Returns an instance of GuzzleHttp\Client that authenticates with the Google API.
        $httpClient = $client->authorize();

        // Your Firebase project ID
        $project = FIREBASE_PROJECT_ID;

        // Send the Push Notification - use $response to inspect success or errors
        $response = $httpClient->post("https://fcm.googleapis.com/v1/projects/{$project}/messages:send", ['json' => $message]);

        return json_decode($response->getBody()->getContents(), true);
    }
}