<?php

namespace App\Support;

final class SecretRedactor
{
    public static function smsText(string $text, ?string $code = null): string
    {
        if ($code !== null && $code !== '') {
            $text = str_replace($code, str_repeat('•', mb_strlen($code)), $text);
        }

        return (string) preg_replace_callback(
            '/(?<!\d)\d{4,8}(?!\d)/',
            static fn (array $m): string => str_repeat('•', strlen($m[0])),
            $text,
        );
    }
}
