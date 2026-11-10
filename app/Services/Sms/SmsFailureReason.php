<?php

namespace App\Services\Sms;

enum SmsFailureReason: string
{
    case NotConfigured = 'not_configured';
    case GatewayUnavailable = 'gateway_unavailable';
    case GatewayRefused = 'gateway_refused';
    case BadNumber = 'bad_number';

    public function message(): string
    {
        return $this === self::BadNumber
            ? 'Оператор связи отклонил отправку на этот номер. Проверьте номер или укажите другой.'
            : 'Сейчас не получается отправить код. Попробуйте позже или напишите в поддержку.';
    }

    public function clientCode(): string
    {
        return $this === self::BadNumber ? 'sms_provider_rejected' : 'sms_service_unavailable';
    }
}
