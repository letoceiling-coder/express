<?php

namespace App\Services\Sms;

use App\Models\SmsSetting;

/**
 * Учётные данные: приоритет БД (sms_settings) → config/sms.iqsms → services.iqsms
 */
class SmsSettingsResolver
{
    /** @return array{login: string, password: string, sender: string} */
    public function credentials(): array
    {
        $settings = SmsSetting::forDriver('iqsms');
        if ($settings && $settings->is_enabled && filled($settings->login) && filled($settings->password)) {
            return [
                'login' => (string) $settings->login,
                'password' => (string) $settings->password,
                'sender' => trim((string) ($settings->sender ?? '')),
            ];
        }

        return [
            'login' => (string) config('sms.iqsms.login', config('services.iqsms.login', '')),
            'password' => (string) config('sms.iqsms.password', config('services.iqsms.password', '')),
            'sender' => trim((string) config('sms.iqsms.sender', config('services.iqsms.sender', ''))),
        ];
    }

    public function accessPoint(): string
    {
        return rtrim((string) config('sms.iqsms.access_point', 'https://api.iqsms.ru'), '/');
    }

    public function verifyDeliveryAfterSeconds(): int
    {
        return (int) config('sms.iqsms.verify_delivery_after_seconds', 3);
    }
}
