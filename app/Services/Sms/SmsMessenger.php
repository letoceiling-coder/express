<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class SmsMessenger
{
    public function __construct(
        private readonly SmsSender $sender,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendRaw(string $phone, string $text, array $options = []): array
    {
        try {
            return $this->sender->send($phone, $text, $options);
        } catch (SmsDeliveryException $e) {
            Log::error('SMS delivery failed', [
                'phone' => $phone,
                'reason' => $e->reason->value,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
