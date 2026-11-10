<?php

namespace App\Services\Sms;

interface SmsSender
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function send(string $phone, string $text, array $options = []): array;

    public function isConfigured(): bool;
}
