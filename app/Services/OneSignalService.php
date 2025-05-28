<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OneSignalService
{
    private string $appId;
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->appId = config('services.onesignal.app_id');
        $this->apiKey = config('services.onesignal.rest_api_key');
        $this->apiUrl = config('services.onesignal.rest_api_url');
    }

    public function sendNotification(array $userIds, string $title, string $message, array $data = []): bool
    {
        $payload = [
            'app_id' => $this->appId,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'include_external_user_ids' => $userIds,
        ];

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->apiUrl, $payload);


            if ($response->successful()) {
                Log::info('OneSignal notification sent successfully', [
                    'user_ids' => $userIds,
                    'response' => $response->json()
                ]);
                return true;
            }

            if ($response->failed()) {
                Log::info('OneSignal notification sent successfully', [
                    'user_ids' => $userIds,
                    'response' => $response->json()
                ]);
                return false;
            }
        } catch (Throwable $e) {
            Log::error('Exception when sending OneSignal notification', [
                'user_ids'  => $userIds,
                'exception' => $e->getMessage()
            ]);

            return false;
        }

        return false;
    }

}
