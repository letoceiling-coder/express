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

        if (empty($creds['login']) || empty($creds['password'])) {
            Log::error('IqSmsService: login или password не заданы', [
                'phone' => $this->maskPhone($phone),
            ]);
            return false;
        }

        $phone = $this->normalizePhone($phone);
        $clientId = 'sms_' . uniqid();

        $payload = [
            'login' => $creds['login'],
            'password' => $creds['password'],
            'sender' => $creds['sender'] ?: 'INFO',
            'messages' => [
                [
                    'phone' => $phone,
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

            if ($response->successful() && $status === 'ok') {
                $msgStatus = $messages[0]['status'] ?? null;
                if ($msgStatus === 'accepted') {
                    Log::info('IqSmsService: SMS отправлена', [
                        'phone' => $this->maskPhone($phone),
                        'smscId' => $messages[0]['smscId'] ?? null,
                    ]);
                    return true;
                }
            }

            Log::error('IqSmsService: ошибка ответа API', [
                'phone' => $this->maskPhone($phone),
                'http_status' => $response->status(),
                'response' => $body,
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('IqSmsService: исключение при отправке', [
                'phone' => $this->maskPhone($phone),
                'message' => $e->getMessage(),
            ]);
            return false;
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
