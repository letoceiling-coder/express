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
            // Возвращаем 200 с null, чтобы фронтенд мог показать форму создания
            return response()->json([
                'data' => null,
                'message' => 'Настройки ЮКасса не найдены. Вы можете создать их ниже.',
            ]);
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

            // Получаем или создаем настройки
            $settings = PaymentSetting::firstOrCreate(
                ['provider' => 'yookassa'],
                ['provider' => 'yookassa']
            );
            
            // Получаем валидированные данные
            $validated = $request->validated();
            
            // Сохраняем существующие ключи перед fill()
            $existingSecretKey = $settings->secret_key;
            $existingTestSecretKey = $settings->test_secret_key;
            
            // Удаляем пустые ключи из валидированных данных
            // Если поле пароля пустое, это означает, что пользователь не хочет его менять
            if (isset($validated['secret_key']) && empty(trim($validated['secret_key'] ?? ''))) {
                unset($validated['secret_key']);
            }
            if (isset($validated['test_secret_key']) && empty(trim($validated['test_secret_key'] ?? ''))) {
                unset($validated['test_secret_key']);
            }
            
            // Обновляем настройки
            $settings->fill($validated);
            
            // Восстанавливаем ключи, если они не были переданы или были пустыми
            if (!isset($validated['secret_key']) && $existingSecretKey) {
                $settings->secret_key = $existingSecretKey;
            }
            if (!isset($validated['test_secret_key']) && $existingTestSecretKey) {
                $settings->test_secret_key = $existingTestSecretKey;
            }
            
            $settings->save();

            DB::commit();

            // Перезагружаем модель, чтобы получить актуальные данные
            $settings->refresh();
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
            $event = $request->input('event');
            $object = $request->input('object');
            $paymentId = $object['id'] ?? null;
            
            // Логируем полный входящий webhook
            Log::info('YooKassa webhook received', [
                'event' => $event,
                'payment_id' => $paymentId,
                'status' => $object['status'] ?? null,
                'captured_at' => $object['captured_at'] ?? null,
                'paid_at' => $object['paid_at'] ?? null,
                'amount' => $object['amount'] ?? null,
                'metadata' => $object['metadata'] ?? null,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'full_object' => $object, // Полный объект для отладки
            ]);

            $settings = PaymentSetting::forProvider('yookassa');

            if (!$settings || !$settings->is_enabled) {
                Log::warning('YooKassa webhook - интеграция отключена или настройки не найдены', [
                    'event' => $event,
                    'payment_id' => $paymentId,
                ]);
                return response()->json(['message' => 'Integration disabled'], 403);
            }

            // Проверка подписи (если передана)
            $signature = $request->header('X-YooMoney-Signature');
            if ($signature) {
                $rawBody = $request->getContent();
                $yooKassaService = new YooKassaService($settings);
                if (!$yooKassaService->verifyWebhookSignature($rawBody, $signature)) {
                    Log::warning('YooKassa webhook - неверная подпись', [
                        'event' => $event,
                        'payment_id' => $paymentId,
                    ]);
                    return response()->json(['message' => 'Invalid signature'], 403);
                }
                Log::debug('YooKassa webhook - подпись проверена успешно', [
                    'event' => $event,
                    'payment_id' => $paymentId,
                ]);
            } else {
                Log::debug('YooKassa webhook - подпись не передана (проверка пропущена)', [
                    'event' => $event,
                    'payment_id' => $paymentId,
                ]);
            }

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
                    Log::info('YooKassa webhook - необработанное событие', [
                        'event' => $event,
                        'payment_id' => $paymentId,
                        'object_type' => $object['type'] ?? null,
                    ]);
            }

            Log::info('YooKassa webhook - обработка завершена успешно', [
                'event' => $event,
                'payment_id' => $paymentId,
            ]);

            return response()->json(['message' => 'OK']);
        } catch (\Exception $e) {
            Log::error('YooKassa webhook - ошибка при обработке', [
                'event' => $request->input('event'),
                'payment_id' => $request->input('object.id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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
        $orderId = $metadata['order_id'] ?? null;
        $status = $payment['status'] ?? null;
        $capturedAt = isset($payment['captured_at']) 
            ? \Carbon\Carbon::parse($payment['captured_at']) 
            : null;
        $paidAt = isset($payment['paid_at']) 
            ? \Carbon\Carbon::parse($payment['paid_at']) 
            : null;
        
        Log::info('YooKassa webhook - payment.succeeded', [
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'status' => $status,
            'captured_at' => $capturedAt?->toIso8601String(),
            'paid_at' => $paidAt?->toIso8601String(),
            'amount' => $payment['amount'] ?? null,
        ]);

        if (!$paymentId) {
            Log::warning('YooKassa webhook - payment.succeeded: payment_id отсутствует');
            return;
        }

        // Ищем платеж по transaction_id (может быть создан без order_id в metadata)
        $paymentRecord = \App\Models\Payment::where('transaction_id', $paymentId)->first();

        if (!$paymentRecord && $orderId) {
            // Если не нашли по transaction_id, ищем по order_id
            $order = \App\Models\Order::where('order_id', $orderId)->first();
            if ($order) {
                $paymentRecord = \App\Models\Payment::where('order_id', $order->id)
                    ->where('payment_provider', 'yookassa')
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
        }

        if (!$paymentRecord) {
            Log::warning('YooKassa webhook - payment.succeeded: платеж не найден в БД', [
                'yookassa_payment_id' => $paymentId,
                'order_id' => $orderId,
            ]);
            return;
        }

        // Идемпотентность: проверяем, не обработан ли уже этот платеж
        if ($paymentRecord->status === \App\Models\Payment::STATUS_SUCCEEDED) {
            Log::info('YooKassa webhook - payment.succeeded: платеж уже обработан (идемпотентность)', [
                'payment_id' => $paymentRecord->id,
                'yookassa_payment_id' => $paymentId,
                'current_status' => $paymentRecord->status,
            ]);
            
            // Обновляем только captured_at и provider_response, если они изменились
            $needsUpdate = false;
            if ($capturedAt && (!$paymentRecord->captured_at || $paymentRecord->captured_at->ne($capturedAt))) {
                $paymentRecord->captured_at = $capturedAt;
                $needsUpdate = true;
            }
            if ($paymentRecord->provider_response !== $payment) {
                $paymentRecord->provider_response = $payment;
                $needsUpdate = true;
            }
            
            if ($needsUpdate) {
                $paymentRecord->save();
                Log::info('YooKassa webhook - payment.succeeded: обновлены дополнительные поля', [
                    'payment_id' => $paymentRecord->id,
                    'captured_at' => $capturedAt?->toIso8601String(),
                ]);
            }
            
            return;
        }

        $oldStatus = $paymentRecord->status;
        
        \DB::beginTransaction();
        try {
            // Обновляем платеж
            $paymentRecord->status = \App\Models\Payment::STATUS_SUCCEEDED;
            $paymentRecord->paid_at = $paidAt ?: now();
            $paymentRecord->captured_at = $capturedAt ?: $paidAt ?: now();
            $paymentRecord->provider_response = $payment;
            $paymentRecord->save();

            // Обновляем статус заказа согласно требованиям:
            // payment.succeeded → paid=true, payment_status='succeeded', order_status='paid'
            if ($paymentRecord->order) {
                $order = $paymentRecord->order;
                $order->paid = true;
                $order->payment_status = 'succeeded';
                $order->payment_id = (string) $paymentRecord->id;
                $order->status = \App\Models\Order::STATUS_PAID;
                $order->save();
                
                Log::info('YooKassa webhook - payment.succeeded: статусы обновлены', [
                    'payment_id' => $paymentRecord->id,
                    'yookassa_payment_id' => $paymentId,
                    'old_payment_status' => $oldStatus,
                    'new_payment_status' => $paymentRecord->status,
                    'order_id' => $order->id,
                    'order_status' => $order->status,
                    'order_payment_status' => $order->payment_status,
                    'order_paid' => $order->paid,
                    'captured_at' => $capturedAt?->toIso8601String(),
                ]);
            } else {
                Log::warning('YooKassa webhook - payment.succeeded: заказ не найден', [
                    'payment_id' => $paymentRecord->id,
                    'order_id' => $paymentRecord->order_id,
                ]);
            }
            
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('YooKassa webhook - payment.succeeded: ошибка обновления', [
                'payment_id' => $paymentRecord->id,
                'yookassa_payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
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
        
        Log::info('YooKassa webhook - payment.canceled', [
            'payment_id' => $paymentId,
            'status' => $payment['status'] ?? null,
        ]);

        if (!$paymentId) {
            Log::warning('YooKassa webhook - payment.canceled: payment_id отсутствует');
            return;
        }

        $paymentRecord = \App\Models\Payment::where('transaction_id', $paymentId)->first();
        if ($paymentRecord) {
            $oldStatus = $paymentRecord->status;
            $paymentRecord->status = \App\Models\Payment::STATUS_CANCELLED;
            $paymentRecord->provider_response = $payment;
            $paymentRecord->save();
            
            Log::info('YooKassa webhook - payment.canceled: статус обновлен', [
                'payment_id' => $paymentRecord->id,
                'old_status' => $oldStatus,
                'new_status' => $paymentRecord->status,
            ]);
        } else {
            Log::warning('YooKassa webhook - payment.canceled: платеж не найден в БД', [
                'yookassa_payment_id' => $paymentId,
            ]);
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
        
        Log::info('YooKassa webhook - payment.waiting_for_capture', [
            'payment_id' => $paymentId,
            'status' => $payment['status'] ?? null,
        ]);

        if (!$paymentId) {
            Log::warning('YooKassa webhook - payment.waiting_for_capture: payment_id отсутствует');
            return;
        }

        $paymentRecord = \App\Models\Payment::where('transaction_id', $paymentId)->first();
        if ($paymentRecord) {
            $oldStatus = $paymentRecord->status;
            $paymentRecord->status = \App\Models\Payment::STATUS_PROCESSING;
            $paymentRecord->provider_response = $payment;
            $paymentRecord->save();
            
            Log::info('YooKassa webhook - payment.waiting_for_capture: статус обновлен', [
                'payment_id' => $paymentRecord->id,
                'old_status' => $oldStatus,
                'new_status' => $paymentRecord->status,
            ]);
        } else {
            Log::warning('YooKassa webhook - payment.waiting_for_capture: платеж не найден в БД', [
                'yookassa_payment_id' => $paymentId,
            ]);
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
        $refundId = $refund['id'] ?? null;
        $refundStatus = $refund['status'] ?? null;
        $refundAmount = isset($refund['amount']['value']) 
            ? (float) $refund['amount']['value'] 
            : 0;
        
        Log::info('YooKassa webhook - refund.succeeded', [
            'refund_id' => $refundId,
            'payment_id' => $paymentId,
            'refund_status' => $refundStatus,
            'refund_amount' => $refundAmount,
            'refund_created_at' => isset($refund['created_at']) 
                ? \Carbon\Carbon::parse($refund['created_at'])->toIso8601String() 
                : null,
        ]);

        if (!$paymentId) {
            Log::warning('YooKassa webhook - refund.succeeded: payment_id отсутствует');
            return;
        }

        $paymentRecord = \App\Models\Payment::where('transaction_id', $paymentId)->first();
        if (!$paymentRecord) {
            Log::warning('YooKassa webhook - refund.succeeded: платеж не найден в БД', [
                'yookassa_payment_id' => $paymentId,
                'yookassa_refund_id' => $refundId,
            ]);
            return;
        }

        // Идемпотентность: проверяем, не был ли уже обработан этот возврат
        // Проверяем по refund_id в provider_response
        $existingRefunds = $paymentRecord->provider_response['refunds'] ?? [];
        $refundAlreadyProcessed = false;
        if (is_array($existingRefunds)) {
            foreach ($existingRefunds as $existingRefund) {
                if (isset($existingRefund['id']) && $existingRefund['id'] === $refundId) {
                    $refundAlreadyProcessed = true;
                    break;
                }
            }
        }

        $oldStatus = $paymentRecord->status;
        $oldRefundedAmount = $paymentRecord->refunded_amount;
        
        \DB::beginTransaction();
        try {
            // Обновляем информацию о возврате
            $newRefundedAmount = (float) $paymentRecord->refunded_amount + $refundAmount;
            
            // Сохраняем информацию о возврате в provider_response
            $providerResponse = $paymentRecord->provider_response ?? [];
            if (!isset($providerResponse['refunds'])) {
                $providerResponse['refunds'] = [];
            }
            // Добавляем информацию о возврате, если его еще нет
            if (!$refundAlreadyProcessed) {
                $providerResponse['refunds'][] = $refund;
            }
            $providerResponse['last_refund'] = $refund;
            
            $paymentRecord->refunded_amount = $newRefundedAmount;
            $paymentRecord->refunded_at = now();
            $paymentRecord->provider_response = $providerResponse;

            // Определяем статус платежа
            if ($newRefundedAmount >= $paymentRecord->amount) {
                $paymentRecord->status = \App\Models\Payment::STATUS_REFUNDED;
            } else {
                $paymentRecord->status = \App\Models\Payment::STATUS_PARTIALLY_REFUNDED;
            }

            $paymentRecord->save();

            // Обновляем статус заказа согласно требованиям:
            // refund.succeeded → refunded=true, order_status='refunded'
            if ($paymentRecord->order) {
                $order = $paymentRecord->order;
                
                // Если полный возврат, устанавливаем статус refunded и флаг refunded
                if ($newRefundedAmount >= $paymentRecord->amount) {
                    $order->refunded = true;
                    $order->status = \App\Models\Order::STATUS_REFUNDED;
                }
                
                $order->save();
                
                Log::info('YooKassa webhook - refund.succeeded: статусы обновлены', [
                    'payment_id' => $paymentRecord->id,
                    'yookassa_payment_id' => $paymentId,
                    'yookassa_refund_id' => $refundId,
                    'old_payment_status' => $oldStatus,
                    'new_payment_status' => $paymentRecord->status,
                    'old_refunded_amount' => $oldRefundedAmount,
                    'refund_amount' => $refundAmount,
                    'new_refunded_amount' => $newRefundedAmount,
                    'order_id' => $order->id,
                    'order_status' => $order->status,
                    'refund_already_processed' => $refundAlreadyProcessed,
                ]);
            } else {
                Log::warning('YooKassa webhook - refund.succeeded: заказ не найден', [
                    'payment_id' => $paymentRecord->id,
                    'order_id' => $paymentRecord->order_id,
                ]);
            }
            
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('YooKassa webhook - refund.succeeded: ошибка обновления', [
                'payment_id' => $paymentRecord->id,
                'yookassa_payment_id' => $paymentId,
                'yookassa_refund_id' => $refundId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
