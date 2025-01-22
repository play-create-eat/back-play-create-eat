<?php

namespace App\Services;

use App\Enums\Otps\TypeEnum;
use App\Models\OtpCode;
use Throwable;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Rest\Client;

class TwilloService
{
    protected Client $client;

    protected mixed $from;

    /**
     * @throws ConfigurationException
     */
    public function __construct()
    {
        $this->client = new Client(config('services.twilio.account_sid'), config('services.twilio.auth_token'));
        $this->from = config('services.twilio.phone_number');
    }

    public function sendSms(string $to, string $message)
    {
        try {
            $this->client->messages->create($to, [
               'from' => $this->from,
               'body' => $message,
            ]);

            return true;
        } catch (Throwable $e) {
            return  $e->getMessage();
        }
    }
}
