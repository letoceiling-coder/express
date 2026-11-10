<?php

namespace App\Services\Sms;

class SmsDevMode
{
    public function isDevMode(): bool
    {
        if (config('sms.force_real_in_dev')) {
            return false;
        }

        $req = request();
        if ($req) {
            $host = $req->getHost();
            if (in_array($host, ['dev.svoihlebekb.ru', 'svoihlebekb.ru', 'azotrostovskiy.ru'], true)) {
                return false;
            }
        }

        return config('app.env') === 'local';
    }
}
