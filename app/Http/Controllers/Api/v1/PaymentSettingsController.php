<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentSettingsRequest;
use App\Models\PaymentSetting;
use App\Services\Payment\YooKassaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentSettingsController extends Controller
{
    /**
     * Получить настройки платежных систем
     * 
     * @return JsonResponse
     */
    public function index()
    {
        $settings = PaymentSetting::all();

        // Не показываем секретные ключи
        $settings = $settings->map(function ($setting) {
            $data = $setting->toArray();
            unset($data['secret_key'], $data['test_secret_key']);
            return $data;
        });

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Получить настройки ЮКасса
     * 
     * @return JsonResponse
     */
    public function getYooKassa()
    {
        $settings = PaymentSetting::forProvider('yookassa');

        if (!$settings) {
            return response()->json([
                'data' => null,
                'message' => 'Настройки ЮКасса не найдены',
            ], 404);
        }

        $data = $settings->toArray();
        // Не показываем секретные ключи
        unset($data['secret_key'], $data['test_secret_key']);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Обновить настройки ЮКасса
     * 
     * @param PaymentSettingsRequest $request
     * @return JsonResponse
     */
    public function updateYooKassa(PaymentSettingsRequest $request)
    {
        try {
            DB::beginTransaction();

            $settings = PaymentSetting::firstOrNew(['provider' => 'yookassa']);
            $settings->fill($request->validated());
            $settings->save();

            DB::commit();

            $data = $settings->toArray();
            unset($data['secret_key'], $data['test_secret_key']);

            return response()->json([
                'data' => $data,
                'message' => 'Настройки ЮКасса успешно обновлены',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении настроек ЮКасса: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при обновлении настроек',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Тестирование подключения к ЮКасса
     * 
     * @return JsonResponse
     */
    public function testYooKassa()
    {
        try {
            $settings = PaymentSetting::forProvider('yookassa');

            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Настройки ЮКасса не найдены',
                ], 404);
            }

            $yooKassaService = new YooKassaService($settings);
            $result = $yooKassaService->testConnection();

            // Сохраняем результат теста
            $settings->last_test_at = now();
            $settings->last_test_result = $result;
            $settings->save();

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Ошибка при тестировании ЮКасса: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook от ЮКасса
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function webhookYooKassa(Request $request)
    {
        try {
            $settings = PaymentSetting::forProvider('yookassa');

            if (!$settings || !$settings->is_enabled) {
                Log::warning('Webhook от ЮКасса получен, но настройки не найдены или интеграция отключена');
                return response()->json(['message' => 'Integration disabled'], 403);
            }

            // Проверка подписи (если передана)
            $signature = $request->header('X-YooMoney-Signature');
            if ($signature) {
                $rawBody = $request->getContent();
                $yooKassaService = new YooKassaService($settings);
                if (!$yooKassaService->verifyWebhookSignature($rawBody, $signature)) {
                    Log::warning('Неверная подпись webhook от ЮКасса');
                    return response()->json(['message' => 'Invalid signature'], 403);
                }
            }

            $event = $request->input('event');
            $object = $request->input('object');

            // Обработка различных событий
            switch ($event) {
                case 'payment.succeeded':
                    $this->handlePaymentSucceeded($object);
                    break;
                case 'payment.canceled':
                    $this->handlePaymentCanceled($object);
                    break;
                case 'payment.waiting_for_capture':
                    $this->handlePaymentWaitingForCapture($object);
                    break;
                case 'refund.succeeded':
                    $this->handleRefundSucceeded($object);
                    break;
                default:
                    Log::info("Необработанное событие от ЮКасса: {$event}");
            }

            return response()->json(['message' => 'OK']);
        } catch (\Exception $e) {
            Log::error('Ошибка при обработке webhook от ЮКасса: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error processing webhook',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обработка успешного платежа
     * 
     * @param array $payment
     * @return void
     */
    protected function handlePaymentSucceeded(array $payment): void
    {
        $paymentId = $payment['id'] ?? null;
        $metadata = $payment['metadata'] ?? [];

        if ($paymentId && isset($metadata['order_id'])) {
            $orderId = $metadata['order_id'];
            $order = \App\Models\Order::where('order_id', $orderId)->first();

            if ($order) {
                $paymentRecord = \App\Models\Payment::where('order_id', $order->id)
                    ->where('transaction_id', $paymentId)
                    ->first();

                if ($paymentRecord) {
                    $paymentRecord->status = \App\Models\Payment::STATUS_SUCCEEDED;
                    $paymentRecord->paid_at = now();
                    $paymentRecord->provider_response = $payment;
                    $paymentRecord->save();

                    // Обновляем статус заказа
                    $order->payment_status = 'succeeded';
                    if ($order->status === 'new') {
                        $order->status = 'accepted';
                    }
                    $order->save();
                }
            }
        }
    }

    /**
     * Обработка отмененного платежа
     * 
     * @param array $payment
     * @return void
     */
    protected function handlePaymentCanceled(array $payment): void
    {
        $paymentId = $payment['id'] ?? null;
        if ($paymentId) {
            $paymentRecord = \App\Models\Payment::where('transaction_id', $paymentId)->first();
            if ($paymentRecord) {
                $paymentRecord->status = \App\Models\Payment::STATUS_FAILED;
                $paymentRecord->provider_response = $payment;
                $paymentRecord->save();
            }
        }
    }

    /**
     * Обработка платежа, ожидающего подтверждения
     * 
     * @param array $payment
     * @return void
     */
    protected function handlePaymentWaitingForCapture(array $payment): void
    {
        $paymentId = $payment['id'] ?? null;
        if ($paymentId) {
            $paymentRecord = \App\Models\Payment::where('transaction_id', $paymentId)->first();
            if ($paymentRecord) {
                $paymentRecord->status = \App\Models\Payment::STATUS_PROCESSING;
                $paymentRecord->provider_response = $payment;
                $paymentRecord->save();
            }
        }
    }

    /**
     * Обработка успешного возврата
     * 
     * @param array $refund
     * @return void
     */
    protected function handleRefundSucceeded(array $refund): void
    {
        $paymentId = $refund['payment_id'] ?? null;
        if ($paymentId) {
            $paymentRecord = \App\Models\Payment::where('transaction_id', $paymentId)->first();
            if ($paymentRecord) {
                $refundAmount = $refund['amount']['value'] ?? 0;
                $newRefundedAmount = (float) $paymentRecord->refunded_amount + (float) $refundAmount;
                $paymentRecord->refunded_amount = $newRefundedAmount;
                $paymentRecord->refunded_at = now();

                if ($newRefundedAmount >= $paymentRecord->amount) {
                    $paymentRecord->status = \App\Models\Payment::STATUS_REFUNDED;
                } else {
                    $paymentRecord->status = \App\Models\Payment::STATUS_PARTIALLY_REFUNDED;
                }

                $paymentRecord->save();
            }
        }
    }
}
