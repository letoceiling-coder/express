<?php

namespace App\Services\Payment;

use App\Models\PaymentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

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
        // ВАЖНО: YooKassa использует один и тот же URL для тестового и продакшн режима
        // Разница только в ключах: тестовые ключи -> тестовые платежи, продакшн ключи -> реальные платежи
        $this->baseUrl = 'https://api.yookassa.ru/v3';
        
        // Логируем режим работы при инициализации сервиса
        // ВАЖНО: Проверяем, что используются правильные ключи для тестового режима
        $activeShopId = $this->settings->getActiveShopId();
        $productionShopId = $this->settings->shop_id;
        $testShopId = $this->settings->test_shop_id;
        
        Log::info('YooKassaService initialized', [
            'is_test_mode' => $this->settings->is_test_mode,
            'base_url' => $this->baseUrl,
            'active_shop_id' => $activeShopId,
            'production_shop_id' => $productionShopId,
            'test_shop_id' => $testShopId,
            'has_secret_key' => !empty($this->settings->getActiveSecretKey()),
            'warning' => $this->settings->is_test_mode && empty($testShopId) 
                ? 'ТЕСТОВЫЙ РЕЖИМ ВКЛЮЧЕН, НО TEST_SHOP_ID НЕ ЗАПОЛНЕН! Используется продакшн shop_id.' 
                : ($this->settings->is_test_mode && $activeShopId === $productionShopId 
                    ? 'ТЕСТОВЫЙ РЕЖИМ ВКЛЮЧЕН, НО ИСПОЛЬЗУЕТСЯ ПРОДАКШН SHOP_ID! Это может привести к реальным платежам!'
                    : null),
        ]);
        
        // Поддержка env переменной YUKASSA как fallback (если настройки не заданы)
        if (!$this->settings->getActiveSecretKey() && env('YUKASSA')) {
            // Если есть env переменная YUKASSA, используем её как секретный ключ
            // Но для работы нужен shop_id, поэтому лучше использовать настройки из БД
            Log::warning('YooKassaService: YUKASSA env variable found, but it\'s recommended to use database settings');
        }
    }

    /**
     * Получить базовые заголовки для запросов
     * 
     * @return array
     */
    protected function getHeaders(?string $idempotenceKey = null): array
    {
        $shopId = $this->settings->getActiveShopId();
        $secretKey = $this->settings->getActiveSecretKey();

        if (!$shopId || !$secretKey) {
            throw new \Exception('Настройки ЮКасса не заполнены');
        }

        // ВАЖНАЯ ПРОВЕРКА: Предупреждаем, если в тестовом режиме используются продакшн ключи
        if ($this->settings->is_test_mode) {
            $testShopId = $this->settings->test_shop_id;
            $productionShopId = $this->settings->shop_id;
            
            // Если test_shop_id не заполнен ИЛИ равен продакшн shop_id - это проблема!
            if (empty($testShopId)) {
                Log::error('YooKassaService: ТЕСТОВЫЙ РЕЖИМ ВКЛЮЧЕН, НО TEST_SHOP_ID НЕ ЗАПОЛНЕН!', [
                    'is_test_mode' => true,
                    'test_shop_id' => null,
                    'production_shop_id' => $productionShopId,
                    'active_shop_id' => $shopId,
                    'warning' => 'Возможно используются продакшн ключи вместо тестовых!',
                ]);
            } elseif ($shopId === $productionShopId) {
                Log::error('YooKassaService: ТЕСТОВЫЙ РЕЖИМ ВКЛЮЧЕН, НО ИСПОЛЬЗУЕТСЯ ПРОДАКШН SHOP_ID!', [
                    'is_test_mode' => true,
                    'test_shop_id' => $testShopId,
                    'production_shop_id' => $productionShopId,
                    'active_shop_id' => $shopId,
                    'warning' => 'Используется продакшн shop_id вместо тестового! Платежи будут реальными!',
                ]);
            }
        }

        $auth = base64_encode("{$shopId}:{$secretKey}");

        return [
            'Authorization' => "Basic {$auth}",
            'Content-Type' => 'application/json',
            'Idempotence-Key' => $idempotenceKey ?? $this->generateIdempotenceKey(),
        ];
    }

    /**
     * Генерация ключа идемпотентности
     * 
     * @return string
     */
    protected function generateIdempotenceKey(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Создать платеж
     * 
     * @param array $data
     * @param string|null $idempotenceKey
     * @return array
     */
    public function createPayment(array $data, ?string $idempotenceKey = null): array
    {
        try {
            $payload = [
                'amount' => [
                    'value' => number_format($data['amount'], 2, '.', ''),
                    'currency' => $data['currency'] ?? 'RUB',
                ],
                'confirmation' => [
                    'type' => $data['confirmation_type'] ?? 'redirect',
                    'return_url' => $data['return_url'] ?? url('/'),
                ],
                'description' => $data['description'] ?? 'Оплата заказа',
                'metadata' => $data['metadata'] ?? [],
            ];

            // Добавляем payment_method_data, если указан
            if (isset($data['payment_method_data'])) {
                $payload['payment_method_data'] = $data['payment_method_data'];
            }

            // Добавляем сохранение способа оплаты, если указано
            if (isset($data['save_payment_method'])) {
                $payload['save_payment_method'] = $data['save_payment_method'];
            }

            // Добавляем capture, если указано
            if (isset($data['capture'])) {
                $payload['capture'] = $data['capture'];
            }

            // Добавляем receipt, если указан (для онлайн-кассы 54-ФЗ)
            if (isset($data['receipt'])) {
                $payload['receipt'] = $data['receipt'];
            }

            // Логируем payload для отладки (без секретных данных)
            $logPayload = $payload;
            if (isset($logPayload['receipt']['customer']['phone'])) {
                $logPayload['receipt']['customer']['phone'] = substr($logPayload['receipt']['customer']['phone'], 0, 4) . '****';
            }
            if (isset($logPayload['receipt']['customer']['email'])) {
                $logPayload['receipt']['customer']['email'] = substr($logPayload['receipt']['customer']['email'], 0, 3) . '****';
            }
            
            // Детальное логирование receipt для отладки
            if (isset($payload['receipt'])) {
                Log::info('YooKassa createPayment - Receipt details', [
                    'customer_has_email' => isset($payload['receipt']['customer']['email']),
                    'customer_has_phone' => isset($payload['receipt']['customer']['phone']),
                    'items_count' => count($payload['receipt']['items'] ?? []),
                    'items_preview' => array_map(function($item) {
                        return [
                            'description' => substr($item['description'] ?? '', 0, 50),
                            'quantity' => $item['quantity'] ?? null,
                            'amount_value' => $item['amount']['value'] ?? null,
                            'vat_code' => $item['vat_code'] ?? null,
                            'vat_code_type' => gettype($item['vat_code'] ?? null),
                            'payment_subject' => $item['payment_subject'] ?? null,
                            'payment_mode' => $item['payment_mode'] ?? null,
                        ];
                    }, $payload['receipt']['items'] ?? []),
                ]);
            }
            
            Log::info('YooKassa createPayment request', [
                'payload' => $logPayload,
                'idempotence_key' => $idempotenceKey,
            ]);

            $response = Http::withHeaders($this->getHeaders($idempotenceKey))
                ->post("{$this->baseUrl}/payments", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // ВАЖНО: Проверяем, совпадает ли режим в ответе YooKassa с нашими настройками
                $yooKassaTestMode = $responseData['test'] ?? false;
                $ourTestMode = $this->settings->is_test_mode;
                
                // Если режимы не совпадают - это серьезная проблема!
                if ($ourTestMode !== $yooKassaTestMode) {
                    Log::warning('YooKassa createPayment - MODE MISMATCH!', [
                        'our_is_test_mode' => $ourTestMode,
                        'yookassa_test_mode' => $yooKassaTestMode,
                        'active_shop_id' => $this->settings->getActiveShopId(),
                        'test_shop_id' => $this->settings->test_shop_id,
                        'production_shop_id' => $this->settings->shop_id,
                        'warning' => $ourTestMode && !$yooKassaTestMode 
                            ? 'ТЕСТОВЫЙ РЕЖИМ ВКЛЮЧЕН В НАСТРОЙКАХ, НО YOOKASSA ИСПОЛЬЗУЕТ ПРОДАКШН МАГАЗИН! Проверьте test_shop_id и test_secret_key в настройках.'
                            : 'Режимы не совпадают - возможна ошибка в настройках.',
                    ]);
                }
                
                // Детальное логирование успешного ответа
                Log::info('YooKassa createPayment success', [
                    'payment_id' => $responseData['id'] ?? null,
                    'status' => $responseData['status'] ?? null,
                    'confirmation_url' => $responseData['confirmation']['confirmation_url'] ?? null,
                    'receipt_registration' => $responseData['receipt_registration'] ?? null,
                    'metadata' => $responseData['metadata'] ?? null,
                    'yookassa_test_mode' => $yooKassaTestMode, // Режим, указанный YooKassa в ответе
                    'our_test_mode' => $ourTestMode, // Наш режим из настроек
                    'mode_match' => $ourTestMode === $yooKassaTestMode,
                    'full_response_preview' => [
                        'id' => $responseData['id'] ?? null,
                        'status' => $responseData['status'] ?? null,
                        'amount' => $responseData['amount'] ?? null,
                        'confirmation' => $responseData['confirmation'] ?? null,
                        'receipt_registration' => $responseData['receipt_registration'] ?? null,
                        'captured_at' => $responseData['captured_at'] ?? null,
                        'created_at' => $responseData['created_at'] ?? null,
                        'test' => $yooKassaTestMode, // Добавляем поле test из ответа
                    ],
                ]);
                return $responseData;
            }

            $errorBody = $response->body();
            $errorJson = $response->json();
            
            Log::error('YooKassa createPayment error', [
                'status' => $response->status(),
                'body' => $errorBody,
                'error_json' => $errorJson,
                'error_type' => $errorJson['type'] ?? null,
                'error_code' => $errorJson['code'] ?? null,
                'error_description' => $errorJson['description'] ?? null,
                'error_parameter' => $errorJson['parameter'] ?? null,
                'payload_preview' => $logPayload,
            ]);

            throw new \Exception('Ошибка создания платежа: ' . $errorBody);
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
            $idempotenceKey = $this->generateIdempotenceKey();
            
            $refundPayload = [
                'payment_id' => $paymentId,
                'amount' => [
                    'value' => number_format($data['amount'], 2, '.', ''),
                    'currency' => $data['currency'] ?? 'RUB',
                ],
            ];
            
            if (isset($data['description'])) {
                $refundPayload['description'] = $data['description'];
            }
            
            Log::info('YooKassa createRefund request', [
                'payment_id' => $paymentId,
                'refund_amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'RUB',
                'idempotence_key' => $idempotenceKey,
            ]);
            
            $response = Http::withHeaders($this->getHeaders($idempotenceKey))
                ->post("{$this->baseUrl}/refunds", $refundPayload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('YooKassa createRefund success', [
                    'payment_id' => $paymentId,
                    'refund_id' => $responseData['id'] ?? null,
                    'refund_status' => $responseData['status'] ?? null,
                    'refund_amount' => $responseData['amount'] ?? null,
                ]);
                
                return $responseData;
            }

            $errorBody = $response->body();
            $errorJson = $response->json();
            
            Log::error('YooKassa createRefund error', [
                'status' => $response->status(),
                'body' => $errorBody,
                'error_json' => $errorJson,
                'payment_id' => $paymentId,
            ]);

            throw new \Exception('Ошибка создания возврата: ' . $errorBody);
        } catch (\Exception $e) {
            Log::error('YooKassa createRefund error: ' . $e->getMessage(), [
                'payment_id' => $paymentId,
                'refund_amount' => $data['amount'] ?? null,
            ]);
            throw $e;
        }
    }

    /**
     * Проверить подключение
     * 
     * @return array
     */
    /**
     * Тестирование подключения к API ЮКасса
     * 
     * Согласно документации ЮКасса: https://yookassa.ru/developers/api
     * Используем GET /payments для проверки авторизации
     * 
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $shopId = $this->settings->getActiveShopId();
            $secretKey = $this->settings->getActiveSecretKey();

            if (!$shopId || !$secretKey) {
                return [
                    'success' => false,
                    'message' => 'Настройки ЮКасса не заполнены. Заполните ' . 
                        ($this->settings->is_test_mode ? 'тестовые' : 'рабочие') . ' Shop ID и Secret Key',
                ];
            }

            // Пробуем получить список платежей (limit=1) для проверки авторизации
            // Это стандартный способ проверки подключения согласно документации ЮКасса
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(10)
                ->get("{$this->baseUrl}/payments", [
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                $mode = $this->settings->is_test_mode ? 'тестовый' : 'рабочий';
                return [
                    'success' => true,
                    'message' => "Подключение к API ЮКасса успешно установлено ({$mode} режим)",
                ];
            }

            // Парсим ответ с ошибкой
            $errorBody = $response->json();
            $errorMessage = 'Ошибка подключения';
            
            if (isset($errorBody['type']) && isset($errorBody['description'])) {
                $errorMessage = $errorBody['description'];
            } elseif ($response->status() === 401) {
                $errorMessage = 'Неверный Shop ID или Secret Key. Проверьте правильность ключей';
            } elseif ($response->status() === 403) {
                $errorMessage = 'Доступ запрещен. Проверьте права доступа ключа';
            } else {
                $errorMessage = $errorBody['message'] ?? $response->body();
            }

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('YooKassa testConnection error: ' . $e->getMessage(), [
                'exception' => $e,
                'settings' => [
                    'shop_id' => $this->settings->getActiveShopId(),
                    'is_test_mode' => $this->settings->is_test_mode,
                ],
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения: ' . $e->getMessage(),
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




