<?php

namespace App\Services\Sms;

use App\Support\SecretRedactor;
use Illuminate\Support\Facades\Log;

/**
 * Фасад для auth: dev mode, отправка кода через SmsMessenger + IqSmsClient.
 */
class IqSmsService
{
    protected ?string $lastError = null;

    protected ?SmsFailureReason $lastReason = null;

    public function __construct(
        private readonly SmsMessenger $messenger,
        private readonly SmsDevMode $devMode,
        private readonly SmsSettingsResolver $settings,
        private readonly SmsSender $sender,
    ) {}

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getLastReason(): ?SmsFailureReason
    {
        return $this->lastReason;
    }

    public function getLastUserMessage(): ?string
    {
        return $this->lastReason?->message();
    }

    public function getLastClientCode(): ?string
    {
        return $this->lastReason?->clientCode();
    }

    public function isDevMode(): bool
    {
        return $this->devMode->isDevMode();
    }

    public function hasCredentials(): bool
    {
        if ($this->isDevMode()) {
            return true;
        }

        if ((string) config('sms.driver') === 'log') {
            return true;
        }

        return $this->sender->isConfigured();
    }

    public function sendCode(string $phone, string $code): bool
    {
        $this->lastError = null;
        $this->lastReason = null;

        $text = 'Код подтверждения: '.$code;
        $maskedPhone = $this->maskPhone($phone);

        if ($this->isDevMode()) {
            Log::info('SMS skipped (dev mode)', [
                'provider' => 'iqsms',
                'phone' => $maskedPhone,
            ]);

            return true;
        }

        if (! $this->sender->isConfigured()) {
            $this->lastError = 'credentials_missing';
            $this->lastReason = SmsFailureReason::NotConfigured;
            Log::error('SMS send failed: credentials missing', [
                'provider' => 'iqsms',
                'phone' => $maskedPhone,
            ]);

            return false;
        }

        try {
            Log::info('Auth SMS payload', [
                'phone' => $maskedPhone,
                'code_len' => strlen($code),
                'text' => SecretRedactor::smsText($text, $code),
            ]);

            $this->messenger->sendRaw($phone, $text);

            return true;
        } catch (SmsDeliveryException $e) {
            $this->lastError = $e->reason->value;
            $this->lastReason = $e->reason;

            return false;
        }
    }

    protected function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '***';
        }

        return substr($digits, 0, 2).'***'.substr($digits, -2);
    }
}
