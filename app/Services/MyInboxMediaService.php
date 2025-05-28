<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class MyInboxMediaService
{
    protected Client $client;
    protected string $apiUrl;
    protected string $userId;
    protected string $password;
    protected string $sender;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = config('services.myinboxmedia.api_url');
        $this->userId = config('services.myinboxmedia.user_id');
        $this->password = config('services.myinboxmedia.password');
        $this->sender = config('services.myinboxmedia.sender_id');
    }

    public function sendSms(string $mobile, string $message): array
    {
        $formattedMobile = ltrim($mobile, '+');

        $payload = [
            'form_params' => [
                'userid'  => $this->userId,
                'pwd'     => $this->password,
                'mobile'  => $formattedMobile,
                'sender'  => $this->sender,
                'msg'     => $message,
                'msgtype' => '16',
            ]
        ];

        try {
            $response = $this->client->post($this->apiUrl, $payload);

            Log::info('MyInboxMediaService sendSms response', [
                'status_code' => $response->getStatusCode(),
                'body'        => $response->getBody()->getContents(),
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (is_array($body) && isset($body[0]['Response'])) {
                $responseText = $body[0]['Response'];

                if (str_contains($responseText, 'SMS Submitted Successfully')) {
                    if (preg_match('/Message ID: (\S+)/', $responseText, $matches)) {
                        $messageId = $matches[1];
                    } else {
                        $messageId = null;
                    }

                    return [
                        'success' => true,
                        'message_id' => $messageId,
                        'response' => $responseText,
                    ];
                }
                return [
                    'success' => false,
                    'error' => $responseText,
                ];
            }

            return [
                'success' => false,
                'error' => 'Unexpected response format',
                'response' => $body,
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
