<?php

namespace App\Services\Sms;

use App\Models\SmsSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис отправки SMS через IQSMS (https://iqsms.ru)
 * REST/JSON API: https://api.iqsms.ru/messages/v2/send.json
 * Настройки: БД (sms_settings) с fallback на config/services.iqsms
 */
class IqSmsService
{
    protected string $baseUrl = 'https://api.iqsms.ru/messages/v2';

    /**
     * Получить учётные данные: приоритет БД → config
     */
    protected function getCredentials(): array
    {
        $settings = SmsSetting::forDriver('iqsms');

        if ($settings && $settings->is_enabled && $settings->login && $settings->password) {
            return [
                'login' => $settings->login,
                'password' => $settings->password,
                'sender' => $settings->sender ?: 'INFO',
            ];
        }

        return [
            'login' => config('services.iqsms.login', ''),
            'password' => config('services.iqsms.password', ''),
            'sender' => config('services.iqsms.sender', 'INFO'),
        ];
    }

    /**
     * Отправить SMS с кодом подтверждения
     */
    public function sendCode(string $phone, string $code): bool
    {
        $text = "Код подтверждения: {$code}";
        return $this->send($phone, $text);
    }

    /**
     * Отправить SMS
     */
    public function send(string $phone, string $text): bool
    {
        $creds = $this->getCredentials();
        $maskedPhone = $this->maskPhone($phone);

        if (empty($creds['login']) || empty($creds['password'])) {
            $this->logSms('fail', $maskedPhone, null, ['error' => 'login_or_password_not_set']);
            return false;
        }

        $phone = $this->normalizePhone($phone);
        $clientId = 'sms_' . uniqid();

        // IQSMS expects phone without '+' prefix (e.g. 79001234567)
        $phoneForApi = ltrim($phone, '+');

        $payload = [
            'login' => $creds['login'],
            'password' => $creds['password'],
            'sender' => $creds['sender'] ?: 'INFO',
            'messages' => [
                [
                    'phone' => $phoneForApi,
                    'text' => $text,
                    'clientId' => $clientId,
                ],
            ],
        ];

        try {
            $response = Http::timeout(15)
                ->asJson()
                ->post("{$this->baseUrl}/send.json", $payload);

            $body = $response->json();
            $status = $body['status'] ?? null;
            $messages = $body['messages'] ?? [];
            $providerResponse = [
                'http_status' => $response->status(),
                'status' => $status,
                'messages' => $messages,
            ];

            if ($response->successful() && $status === 'ok') {
                $msgStatus = $messages[0]['status'] ?? null;
                if ($msgStatus === 'accepted') {
                    $this->logSms('success', $maskedPhone, $providerResponse);
                    return true;
                }
            }

            $this->logSms('fail', $maskedPhone, $providerResponse);
            return false;
        } catch (\Throwable $e) {
            $this->logSms('fail', $maskedPhone, null, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Production SMS logging: phone (masked), success/fail, provider response
     */
    protected function logSms(string $result, string $phoneMasked, ?array $providerResponse, array $extra = []): void
    {
        $context = array_merge(
            [
                'provider' => 'iqsms',
                'phone' => $phoneMasked,
                'result' => $result,
                'provider_response' => $providerResponse,
            ],
            $extra
        );

        if ($result === 'success') {
            Log::info('SMS sent successfully', $context);
        } else {
            Log::error('SMS send failed', $context);
        }
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) === 10 && str_starts_with($phone, '9')) {
            $phone = '7' . $phone;
        } elseif (strlen($phone) === 11 && str_starts_with($phone, '8')) {
            $phone = '7' . substr($phone, 1);
        } elseif (strlen($phone) === 10) {
            $phone = '7' . $phone;
        }
        return '+' . $phone;
    }

    protected function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) < 4) {
            return '***';
        }
        return substr($digits, 0, 2) . '***' . substr($digits, -2);
    }
}
