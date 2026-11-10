<?php

namespace App\Services\Sms;

use RuntimeException;

class SmsDeliveryException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly SmsFailureReason $reason = SmsFailureReason::GatewayRefused,
    ) {
        parent::__construct($message);
    }
}
