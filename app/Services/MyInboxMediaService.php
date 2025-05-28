<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

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

    public function sendSms(string $mobile, string $message)
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
            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['MessageID'])) {
                return true;
            }

            return $body;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
