<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Sentry\Severity;
use Sentry\State\Scope;
use function Sentry\captureMessage;
use function Sentry\configureScope;

class MyInboxMediaService
{
    protected Client $client;
    protected string $apiUrl;
    protected string $userId;
    protected string $password;
    protected string $sender;
    protected array $errorMessages = [
        1 => 'SMS Submitted Successfully',
        2 => 'Invalid User ID',
        3 => 'Incorrect Password',
        4 => 'Invalid Sender ID',
        5 => 'Invalid Mobile Number',
        6 => 'Invalid Message Text',
        7 => 'Insufficient Balance',
        8 => 'Invalid Message Type',
    ];

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
        $startTime = microtime(true);

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

        $context = [
            'service' => 'MyInboxMedia',
            'mobile' => $formattedMobile,
            'sender' => $this->sender,
            'message_length' => strlen($message),
            'user_id' => $this->userId,
        ];

        try {
            $response = $this->client->post($this->apiUrl, $payload);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            $context['response_time_ms'] = $responseTime;
            $context['status_code'] = $statusCode;
            $context['raw_response'] = $responseBody;

            Log::info('MyInboxMediaService sendSms response', $context);

            $decodedBody = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Invalid JSON response from MyInboxMedia API';
                $this->reportErrorToSentry($error, $context + ['json_error' => json_last_error_msg()]);

                return [
                    'success' => false,
                    'error' => $error,
                    'error_code' => 'JSON_PARSE_ERROR',
                    'raw_response' => $responseBody,
                ];
            }

            if (!is_array($decodedBody) || !isset($decodedBody[0]['Response'])) {
                $error = 'Unexpected response format from MyInboxMedia API';
                $this->reportErrorToSentry($error, $context + ['parsed_response' => $decodedBody]);

                return [
                    'success' => false,
                    'error' => $error,
                    'error_code' => 'INVALID_RESPONSE_FORMAT',
                    'response' => $decodedBody,
                ];
            }

            $responseText = $decodedBody[0]['Response'];
            $context['response_text'] = $responseText;

            if (str_contains($responseText, 'SMS Submitted Successfully')) {
                $messageId = null;
                if (preg_match('/Message ID: (\S+)/', $responseText, $matches)) {
                    $messageId = $matches[1];
                }

                Log::info('SMS sent successfully via MyInboxMedia', $context + ['message_id' => $messageId]);

                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'response' => $responseText,
                    'response_time_ms' => $responseTime,
                ];
            }

            $errorCode = $this->getErrorCodeFromResponse($responseText);
            $errorMessage = $this->getErrorMessage($errorCode, $responseText);

            $this->reportErrorToSentry("MyInboxMedia SMS failed: {$errorMessage}", $context + [
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'error_code' => $errorCode,
                'response' => $responseText,
            ];

        } catch (GuzzleException $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $error = "HTTP request failed: {$e->getMessage()}";

            $this->reportErrorToSentry($error, $context + [
                'exception_class' => get_class($e),
                'exception_code' => $e->getCode(),
                'response_time_ms' => $responseTime,
                'request_payload' => $this->sanitizePayloadForLogging($payload),
            ]);

            return [
                'success' => false,
                'error' => $error,
                'error_code' => 'HTTP_REQUEST_FAILED',
                'exception' => get_class($e),
            ];

        } catch (Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $error = "Unexpected error: {$e->getMessage()}";

            $this->reportErrorToSentry($error, $context + [
                'exception_class' => get_class($e),
                'exception_code' => $e->getCode(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'response_time_ms' => $responseTime,
                'request_payload' => $this->sanitizePayloadForLogging($payload),
            ]);

            return [
                'success' => false,
                'error' => $error,
                'error_code' => 'UNEXPECTED_ERROR',
                'exception' => get_class($e),
            ];
        }
    }

    /**
     * Extract error code from response text
     */
    protected function getErrorCodeFromResponse(string $responseText): ?string
    {
        foreach ($this->errorMessages as $code => $message) {
            if (str_contains($responseText, $message)) {
                return (string) $code;
            }
        }

        return 'UNKNOWN_ERROR';
    }

    /**
     * Get a human-readable error message
     */
    protected function getErrorMessage(?string $errorCode, string $fallbackMessage): string
    {
        if ($errorCode && isset($this->errorMessages[(int) $errorCode])) {
            return $this->errorMessages[(int) $errorCode];
        }

        return $fallbackMessage;
    }

    /**
     * Report errors to Sentry with comprehensive context
     */
    protected function reportErrorToSentry(string $message, array $context = []): void
    {
        if (function_exists('app') && app()->bound('sentry')) {
            configureScope(function (Scope $scope) use ($context) {
                $scope->setContext('myinboxmedia_sms', $context);
                $scope->setTag('service', 'MyInboxMedia');
                $scope->setTag('operation', 'send_sms');

                if (isset($context['error_code'])) {
                    $scope->setTag('error_code', $context['error_code']);
                }

                if (isset($context['mobile'])) {
                    $scope->setUser(['phone' => $this->maskPhoneNumber($context['mobile'])]);
                }
            });

            captureMessage($message, Severity::error());
        }

        Log::error($message, $context);
    }

    /**
     * Sanitize payload for logging (remove sensitive data)
     */
    protected function sanitizePayloadForLogging(array $payload): array
    {
        $sanitized = $payload;

        if (isset($sanitized['form_params']['pwd'])) {
            $sanitized['form_params']['pwd'] = '***REDACTED***';
        }

        if (isset($sanitized['form_params']['userid'])) {
            $sanitized['form_params']['userid'] = substr($sanitized['form_params']['userid'], 0, 3) . '***';
        }

        return $sanitized;
    }

    /**
     * Mask phone number for privacy
     */
    protected function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return '***';
        }

        return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
    }

    /**
     * Get service health status
     */
    public function getHealthStatus(): array
    {
        try {
            $testPayload = [
                'form_params' => [
                    'userid'  => $this->userId,
                    'pwd'     => $this->password,
                    'mobile'  => '1234567890',
                    'sender'  => $this->sender,
                    'msg'     => 'Health check',
                    'msgtype' => '16',
                ]
            ];

            $response = $this->client->post($this->apiUrl, $testPayload);

            return [
                'status' => 'healthy',
                'response_code' => $response->getStatusCode(),
                'timestamp' => now()->toISOString(),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];
        }
    }
}
