<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsClient implements SmsSender
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function send(string $phone, string $text, array $options = []): array
    {
        Log::channel('outbox')->info('SMS (log driver)', [
            'phone' => $phone,
            'text' => $text,
        ]);

        return ['status' => 'logged'];
    }
}
