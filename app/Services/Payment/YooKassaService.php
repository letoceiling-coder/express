<?php

namespace App\Services\Payment;

use App\Models\PaymentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с API ЮКасса
 */
class YooKassaService
{
    protected PaymentSetting $settings;
    protected string $baseUrl;

    /**
     * Конструктор
     * 
     * @param PaymentSetting|null $settings
     */
    public function __construct(?PaymentSetting $settings = null)
    {
        $this->settings = $settings ?? PaymentSetting::forProvider('yookassa') ?? new PaymentSetting(['provider' => 'yookassa']);
        $this->baseUrl = $this->settings->is_test_mode 
            ? 'https://api.yookassa.ru/v3' 
            : 'https://api.yookassa.ru/v3';
    }

    /**
     * Получить базовые заголовки для запросов
     * 
     * @return array
     */
    protected function getHeaders(): array
    {
        $shopId = $this->settings->getActiveShopId();
        $secretKey = $this->settings->getActiveSecretKey();

        if (!$shopId || !$secretKey) {
            throw new \Exception('Настройки ЮКасса не заполнены');
        }

        $auth = base64_encode("{$shopId}:{$secretKey}");

        return [
            'Authorization' => "Basic {$auth}",
            'Content-Type' => 'application/json',
            'Idempotence-Key' => uniqid('', true),
        ];
    }

    /**
     * Создать платеж
     * 
     * @param array $data
     * @return array
     */
    public function createPayment(array $data): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/payments", [
                    'amount' => [
                        'value' => number_format($data['amount'], 2, '.', ''),
                        'currency' => $data['currency'] ?? 'RUB',
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => $data['return_url'] ?? url('/'),
                    ],
                    'description' => $data['description'] ?? 'Оплата заказа',
                    'metadata' => $data['metadata'] ?? [],
                    'payment_method_data' => $data['payment_method_data'] ?? null,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Ошибка создания платежа: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('YooKassa createPayment error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Получить информацию о платеже
     * 
     * @param string $paymentId
     * @return array
     */
    public function getPayment(string $paymentId): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/payments/{$paymentId}");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Ошибка получения платежа: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('YooKassa getPayment error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Подтвердить платеж (capture)
     * 
     * @param string $paymentId
     * @param array $amount
     * @return array
     */
    public function capturePayment(string $paymentId, ?array $amount = null): array
    {
        try {
            $data = [];
            if ($amount) {
                $data['amount'] = [
                    'value' => number_format($amount['value'], 2, '.', ''),
                    'currency' => $amount['currency'] ?? 'RUB',
                ];
            }

            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/payments/{$paymentId}/capture", $data);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Ошибка подтверждения платежа: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('YooKassa capturePayment error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Отменить платеж
     * 
     * @param string $paymentId
     * @return array
     */
    public function cancelPayment(string $paymentId): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/payments/{$paymentId}/cancel");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Ошибка отмены платежа: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('YooKassa cancelPayment error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Создать возврат
     * 
     * @param string $paymentId
     * @param array $data
     * @return array
     */
    public function createRefund(string $paymentId, array $data): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/refunds", [
                    'payment_id' => $paymentId,
                    'amount' => [
                        'value' => number_format($data['amount'], 2, '.', ''),
                        'currency' => $data['currency'] ?? 'RUB',
                    ],
                    'description' => $data['description'] ?? 'Возврат платежа',
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Ошибка создания возврата: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('YooKassa createRefund error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Проверить подключение
     * 
     * @return array
     */
    public function testConnection(): array
    {
        try {
            // Пробуем получить список платежей (limit=1) для проверки авторизации
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/payments", [
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Подключение успешно',
                ];
            }

            return [
                'success' => false,
                'message' => 'Ошибка подключения: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('YooKassa testConnection error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Проверить подпись webhook
     * 
     * @param string $rawBody
     * @param string $signature
     * @return bool
     */
    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        $secretKey = $this->settings->getActiveSecretKey();
        $expectedSignature = base64_encode(hash_hmac('sha256', $rawBody, $secretKey, true));
        
        return hash_equals($expectedSignature, base64_decode($signature));
    }
}



