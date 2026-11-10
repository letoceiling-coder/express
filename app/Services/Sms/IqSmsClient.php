<?php

namespace App\Services\Sms;

use App\Support\PhoneNormalizer;
use App\Support\SecretRedactor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * iqsms JSON API — POST {access_point}/messages/v2/send.json
 */
class IqSmsClient implements SmsSender
{
    /** @var list<string> */
    private const ACCEPTED_MESSAGE_STATUSES = ['accepted', 'queued'];

    /** @var list<string> */
    private const BAD_NUMBER_MESSAGE_STATUSES = [
        'invalid mobile phone',
        'invalid phone',
        'wrong phone',
        'wrong destination address',
    ];

    public function __construct(
        private readonly SmsSettingsResolver $settings,
    ) {}

    public function isConfigured(): bool
    {
        $creds = $this->settings->credentials();

        return filled($creds['login']) && filled($creds['password']);
    }

    public function send(string $phone, string $text, array $options = []): array
    {
        $creds = $this->settings->credentials();
        $login = $creds['login'];
        $password = $creds['password'];
        $sender = $creds['sender'];
        $accessPoint = $this->settings->accessPoint();

        if ($login === '' || $password === '') {
            throw new SmsDeliveryException(
                'SMS credentials are not configured.',
                SmsFailureReason::NotConfigured,
            );
        }

        $gatewayPhone = PhoneNormalizer::toSmsGateway($phone);
        $url = $accessPoint.'/messages/v2/send.json';

        $clientId = (string) ($options['client_id'] ?? 'sms_'.uniqid());
        $message = [
            'clientId' => $clientId,
            'phone' => $gatewayPhone,
            'text' => $text,
        ];
        if ($sender !== '') {
            $message['sender'] = $sender;
        }

        $payload = [
            'login' => $login,
            'password' => $password,
            'messages' => [$message],
        ];

        $response = Http::timeout(15)
            ->withHeaders(['Host' => (string) parse_url($accessPoint, PHP_URL_HOST)])
            ->asJson()
            ->post($url, $payload);

        if (! $response->successful()) {
            throw new SmsDeliveryException(
                'iqsms HTTP '.$response->status().': '.$response->body(),
                SmsFailureReason::GatewayUnavailable,
            );
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new SmsDeliveryException(
                'iqsms: invalid JSON response',
                SmsFailureReason::GatewayUnavailable,
            );
        }

        if (($body['status'] ?? null) === 'error') {
            throw new SmsDeliveryException(
                'iqsms error: '.json_encode($body, JSON_UNESCAPED_UNICODE),
                SmsFailureReason::GatewayRefused,
            );
        }

        $this->assertMessagesAccepted($body);

        $smscId = $body['messages'][0]['smscId'] ?? null;
        $submitStatus = strtolower((string) ($body['messages'][0]['status'] ?? ''));

        Log::info('SMS sent via iqsms', [
            'phone' => $phone,
            'smsc_id' => $smscId,
            'submit_status' => $submitStatus,
            'sender' => $sender !== '' ? $sender : '(default)',
            'text_len' => strlen($text),
            'text' => SecretRedactor::smsText($text),
        ]);

        $wait = $this->settings->verifyDeliveryAfterSeconds();
        if ($wait > 0 && $smscId !== null && $smscId !== '') {
            sleep($wait);
            $deliveryStatus = $this->fetchDeliveryStatus((string) $smscId, $login, $password, $accessPoint);
            Log::info('iqsms delivery status', [
                'phone' => $phone,
                'smsc_id' => $smscId,
                'status' => $deliveryStatus,
            ]);
            if (in_array($deliveryStatus, ['delivery error', 'failed', 'rejected', 'smsc reject'], true)) {
                throw new SmsDeliveryException(
                    'iqsms delivery failed: '.$deliveryStatus.' (smscId='.$smscId.')',
                    SmsFailureReason::GatewayRefused,
                );
            }
        }

        return $body;
    }

    public function fetchDeliveryStatus(
        string $smscId,
        ?string $login = null,
        ?string $password = null,
        ?string $accessPoint = null,
    ): string {
        $creds = $this->settings->credentials();
        $login = $login ?? $creds['login'];
        $password = $password ?? $creds['password'];
        $accessPoint = $accessPoint ?? $this->settings->accessPoint();
        $url = $accessPoint.'/messages/v2/status.json';

        $response = Http::timeout(15)
            ->withHeaders(['Host' => (string) parse_url($accessPoint, PHP_URL_HOST)])
            ->asJson()
            ->post($url, [
                'login' => $login,
                'password' => $password,
                'messages' => [['smscId' => $smscId]],
            ]);

        if (! $response->successful()) {
            return 'status_http_'.$response->status();
        }

        $body = $response->json();
        if (! is_array($body)) {
            return 'status_invalid_json';
        }

        $messages = $body['messages'] ?? [];
        if (! is_array($messages) || $messages === []) {
            return 'status_empty';
        }

        return strtolower((string) ($messages[0]['status'] ?? 'unknown'));
    }

    /** @param array<string, mixed> $body */
    private function assertMessagesAccepted(array $body): void
    {
        $messages = $body['messages'] ?? null;
        if (! is_array($messages) || $messages === []) {
            throw new SmsDeliveryException(
                'iqsms: empty messages in response',
                SmsFailureReason::GatewayUnavailable,
            );
        }

        foreach ($messages as $message) {
            if (! is_array($message)) {
                continue;
            }

            $status = strtolower((string) ($message['status'] ?? ''));
            if (in_array($status, self::ACCEPTED_MESSAGE_STATUSES, true)) {
                continue;
            }

            $hint = match ($status) {
                'sender address invalid' => ' Подпись отправителя не зарегистрирована в IQSMS.',
                default => '',
            };

            $reason = in_array($status, self::BAD_NUMBER_MESSAGE_STATUSES, true)
                ? SmsFailureReason::BadNumber
                : SmsFailureReason::GatewayRefused;

            throw new SmsDeliveryException(
                'iqsms message rejected: '.$status.$hint,
                $reason,
            );
        }
    }
}
