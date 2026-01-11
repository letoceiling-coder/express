<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Services\Payment\YooKassaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Получить список платежей
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = Payment::query()->with('order');

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Фильтрация по методу оплаты
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->get('payment_method'));
        }

        // Фильтрация по платежной системе
        if ($request->has('payment_provider')) {
            $query->where('payment_provider', $request->get('payment_provider'));
        }

        // Фильтрация по заказу
        if ($request->has('order_id')) {
            $query->where('order_id', $request->get('order_id'));
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($orderQuery) use ($search) {
                      $orderQuery->where('order_id', 'like', "%{$search}%");
                  });
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        if ($perPage > 0) {
            $payments = $query->paginate($perPage);
        } else {
            $payments = $query->get();
        }

        return response()->json([
            'data' => $payments,
        ]);
    }

    /**
     * Получить детали платежа
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $payment = Payment::with('order.items')->findOrFail($id);

        return response()->json([
            'data' => $payment,
        ]);
    }

    /**
     * Создать платеж
     * 
     * @param PaymentRequest $request
     * @return JsonResponse
     */
    public function store(PaymentRequest $request)
    {
        try {
            DB::beginTransaction();

            $payment = Payment::create($request->validated());

            DB::commit();

            $payment->load('order');

            return response()->json([
                'data' => $payment,
                'message' => 'Платеж успешно создан',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при создании платежа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при создании платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить платеж
     * 
     * @param PaymentRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(PaymentRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $payment = Payment::findOrFail($id);
            $payment->update($request->validated());

            DB::commit();

            $payment->load('order');

            return response()->json([
                'data' => $payment,
                'message' => 'Платеж успешно обновлен',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении платежа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при обновлении платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Изменить статус платежа
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => [
                'required',
                'string',
                'in:pending,processing,succeeded,failed,refunded,partially_refunded,cancelled',
            ],
        ]);

        try {
            $payment = Payment::findOrFail($id);
            $payment->status = $request->get('status');
            $payment->save();

            $payment->load('order');

            return response()->json([
                'data' => $payment,
                'message' => 'Статус платежа успешно обновлен',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при изменении статуса платежа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при изменении статуса платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Вернуть платеж
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function refund(Request $request, $id)
    {
        $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        try {
            $payment = Payment::findOrFail($id);

            if (!$payment->canRefund()) {
                return response()->json([
                    'message' => 'Невозможно вернуть платеж. Статус должен быть "succeeded"',
                ], 422);
            }

            $refundAmount = $request->has('amount') 
                ? (float) $request->get('amount')
                : $payment->available_refund_amount;

            if ($refundAmount > $payment->available_refund_amount) {
                return response()->json([
                    'message' => 'Сумма возврата превышает доступную сумму',
                ], 422);
            }

            DB::beginTransaction();

            $newRefundedAmount = (float) $payment->refunded_amount + $refundAmount;
            $payment->refunded_amount = $newRefundedAmount;
            $payment->refunded_at = now();

            if ($newRefundedAmount >= $payment->amount) {
                $payment->status = Payment::STATUS_REFUNDED;
            } else {
                $payment->status = Payment::STATUS_PARTIALLY_REFUNDED;
            }

            $payment->save();

            DB::commit();

            $payment->load('order');

            return response()->json([
                'data' => $payment,
                'message' => 'Платеж успешно возвращен',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при возврате платежа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при возврате платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить платежи для заказа
     * 
     * @param int $orderId
     * @return JsonResponse
     */
    public function getByOrder($orderId)
    {
        $order = \App\Models\Order::findOrFail($orderId);
        $payments = Payment::where('order_id', $orderId)->with('order')->get();

        return response()->json([
            'data' => $payments,
            'order' => [
                'id' => $order->id,
                'order_id' => $order->order_id,
            ],
        ]);
    }

    /**
     * Создать платеж через ЮKassa
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createYooKassaPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'amount' => 'required|numeric|min:0.01',
            'return_url' => 'required|url',
            'description' => 'nullable|string|max:255',
            'telegram_id' => 'nullable|integer', // Для проверки владельца заказа
            'email' => 'nullable|email', // Email для отправки квитанции
        ]);

        try {
            $order = \App\Models\Order::findOrFail($request->get('order_id'));
            
            // Проверяем, что заказ принадлежит пользователю (если передан telegram_id)
            $telegramId = $request->get('telegram_id');
            if ($telegramId && $order->telegram_id != $telegramId) {
                Log::warning('PaymentController::createYooKassaPayment - Order ownership mismatch', [
                    'order_id' => $order->id,
                    'order_telegram_id' => $order->telegram_id,
                    'request_telegram_id' => $telegramId,
                ]);
                return response()->json([
                    'message' => 'Заказ не принадлежит указанному пользователю',
                ], 403);
            }
            
            // Проверяем, что заказ еще не оплачен
            if ($order->payment_status === 'succeeded') {
                Log::warning('PaymentController::createYooKassaPayment - Order already paid', [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status,
                ]);
                return response()->json([
                    'message' => 'Заказ уже оплачен',
                ], 400);
            }
            
            // Проверяем, что сумма платежа соответствует сумме заказа
            $requestAmount = (float) $request->get('amount');
            $orderAmount = (float) $order->total_amount;
            if (abs($requestAmount - $orderAmount) > 0.01) {
                Log::warning('PaymentController::createYooKassaPayment - Amount mismatch', [
                    'order_id' => $order->id,
                    'request_amount' => $requestAmount,
                    'order_amount' => $orderAmount,
                ]);
                return response()->json([
                    'message' => 'Сумма платежа не соответствует сумме заказа',
                ], 400);
            }
            
            // Проверяем настройки ЮKassa
            $settings = PaymentSetting::forProvider('yookassa');
            
            if (!$settings || !$settings->is_enabled) {
                return response()->json([
                    'message' => 'Интеграция с ЮKassa отключена или не настроена',
                ], 400);
            }

            $yooKassaService = new YooKassaService($settings);

            // Формируем описание платежа
            $description = $request->get('description') 
                ?? ($settings->description_template 
                    ? str_replace('{order_id}', $order->order_id, $settings->description_template)
                    : "Оплата заказа #{$order->order_id}");

            // Загружаем элементы заказа для формирования чека
            $order->load('items');
            
            // Формируем данные для чека (54-ФЗ)
            // Согласно документации ЮКасса: https://yookassa.ru/developers/api#create_payment_receipt
            // Обязательные поля для receipt.items: description, quantity, amount, vat_code, payment_subject, payment_mode
            // ВАЖНО: amount.value - это цена ЗА ЕДИНИЦУ товара, а не общая сумма позиции!
            // YooKassa автоматически умножает amount.value * quantity для получения суммы позиции
            $receiptItems = [];
            $receiptTotalAmount = 0;
            
            if ($order->items && $order->items->count() > 0) {
                foreach ($order->items as $item) {
                    // Получаем цену за единицу товара
                    $unitPrice = (float) $item->unit_price;
                    $quantity = (float) $item->quantity;
                    $itemTotal = $unitPrice * $quantity; // Общая сумма позиции для проверки
                    $receiptTotalAmount += $itemTotal;
                    
                    $receiptItems[] = [
                        'description' => $item->product_name ?? 'Товар',
                        'quantity' => number_format($quantity, 2, '.', ''), // Формат: "4.00"
                        'amount' => [
                            'value' => number_format($unitPrice, 2, '.', ''), // Цена ЗА ЕДИНИЦУ, не общая сумма!
                            'currency' => 'RUB',
                        ],
                        'vat_code' => '1', // НДС 20% - строка, не число! (можно настроить в настройках)
                        'payment_subject' => 'commodity', // Признак предмета расчета: товар
                        'payment_mode' => 'full_payment', // Признак способа расчета: полный расчет
                    ];
                }
            } else {
                // Если items не загружены, создаем один item на всю сумму заказа
                $receiptTotalAmount = (float) $request->get('amount');
                $receiptItems[] = [
                    'description' => $description,
                    'quantity' => '1.00', // Формат с двумя знаками после запятой
                    'amount' => [
                        'value' => number_format($receiptTotalAmount, 2, '.', ''), // Для одного товара это и есть цена за единицу
                        'currency' => 'RUB',
                    ],
                    'vat_code' => '1', // НДС 20% - строка, не число!
                    'payment_subject' => 'commodity', // Признак предмета расчета: товар
                    'payment_mode' => 'full_payment', // Признак способа расчета: полный расчет
                ];
            }
            
            // Проверяем, что сумма чека равна сумме платежа (с допуском 0.01)
            $paymentAmount = (float) $request->get('amount');
            if (abs($receiptTotalAmount - $paymentAmount) > 0.01) {
                Log::warning('PaymentController::createYooKassaPayment - Receipt amount mismatch', [
                    'order_id' => $order->id,
                    'receipt_total' => $receiptTotalAmount,
                    'payment_amount' => $paymentAmount,
                    'difference' => abs($receiptTotalAmount - $paymentAmount),
                ]);
                
                // Корректируем последний item, чтобы сумма совпадала
                // ВАЖНО: корректируем цену за единицу, а не общую сумму
                if (count($receiptItems) > 0) {
                    $lastItem = &$receiptItems[count($receiptItems) - 1];
                    $lastQuantity = (float) $lastItem['quantity'];
                    $lastUnitPrice = (float) $lastItem['amount']['value'];
                    $lastItemTotal = $lastUnitPrice * $lastQuantity;
                    $adjustment = $paymentAmount - $receiptTotalAmount;
                    
                    // Пересчитываем цену за единицу с учетом корректировки
                    $newUnitPrice = ($lastItemTotal + $adjustment) / $lastQuantity;
                    $lastItem['amount']['value'] = number_format($newUnitPrice, 2, '.', '');
                }
            }
            
            // Формируем данные покупателя для чека
            // Согласно официальной документации ЮКасса: рекомендуется передавать оба параметра (email и phone)
            // Приоритет email для фискализации, phone - как дополнительный параметр
            $receiptCustomer = [];
            
            // Получаем email из запроса или создаем placeholder
            $email = $request->get('email');
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $receiptCustomer['email'] = $email;
            } else {
                // Создаем placeholder email на основе order_id
                // YooKassa требует email для корректной работы фискализации
                $receiptCustomer['email'] = 'order-' . $order->order_id . '@neekloai.ru';
            }
            
            // Добавляем phone как дополнительный параметр, если доступен
            // Форматируем телефон для чека согласно документации ЮКасса: +7XXXXXXXXXX
            if ($order->phone) {
                $phone = preg_replace('/[^0-9]/', '', $order->phone);
                if (strlen($phone) === 11 && $phone[0] === '7') {
                    // Формат: 7XXXXXXXXXX -> +7XXXXXXXXXX
                    $receiptCustomer['phone'] = '+' . $phone;
                } elseif (strlen($phone) === 10) {
                    // Формат: XXXXXXXXXX -> +7XXXXXXXXXX
                    $receiptCustomer['phone'] = '+7' . $phone;
                } elseif (strlen($phone) > 0) {
                    // Если телефон в другом формате, пробуем добавить +7
                    $receiptCustomer['phone'] = '+7' . substr($phone, -10);
                }
            }
            
            // Формируем receipt для онлайн-кассы (54-ФЗ)
            // ВАЖНО: В тестовом режиме фискализация может быть не настроена,
            // поэтому receipt отправляем только если он корректно сформирован
            $receipt = null;
            
            // Проверяем финальную сумму чека
            // ВАЖНО: amount.value - это цена за единицу, нужно умножить на quantity
            $finalReceiptTotal = 0;
            foreach ($receiptItems as $item) {
                $unitPrice = (float) $item['amount']['value'];
                $quantity = (float) $item['quantity'];
                $finalReceiptTotal += $unitPrice * $quantity;
            }
            
            // Формируем receipt только если есть корректные данные
            // В тестовом режиме можно попробовать отключить receipt, если он вызывает проблемы
            if (count($receiptItems) > 0 && !empty($receiptCustomer)) {
                // В тестовом режиме: отключаем receipt, если фискализация не настроена
                // В продакшн режиме: всегда отправляем receipt
                $shouldSendReceipt = !$settings->is_test_mode; // В тестовом режиме отключаем receipt
                
                if ($shouldSendReceipt) {
                    $receipt = [
                        'customer' => $receiptCustomer,
                        'items' => $receiptItems,
                    ];
                }
            }
            
            Log::info('PaymentController::createYooKassaPayment - Receipt prepared', [
                'order_id' => $order->id,
                'is_test_mode' => $settings->is_test_mode,
                'receipt_enabled' => $receipt !== null,
                'items_count' => count($receiptItems),
                'customer_phone' => isset($receiptCustomer['phone']) ? substr($receiptCustomer['phone'], 0, 4) . '****' : null,
                'customer_email' => isset($receiptCustomer['email']) ? substr($receiptCustomer['email'], 0, 3) . '****' : null,
                'receipt_total' => $finalReceiptTotal,
                'payment_amount' => $paymentAmount,
                'amount_match' => abs($finalReceiptTotal - $paymentAmount) < 0.01,
                'receipt_items_preview' => array_map(function($item) {
                    return [
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['amount']['value'],
                        'item_total' => (float)$item['amount']['value'] * (float)$item['quantity'],
                    ];
                }, $receiptItems),
            ]);

            // Подготавливаем данные для платежа
            $paymentData = [
                'amount' => (float) $request->get('amount'),
                'currency' => 'RUB',
                'description' => $description,
                'return_url' => $request->get('return_url'),
                'metadata' => [
                    'order_id' => $order->order_id,
                    'order_db_id' => $order->id,
                    'merchant_name' => $settings->merchant_name ?? null,
                ],
                'capture' => $settings->auto_capture ?? true,
            ];
            
            // Добавляем receipt только если он был сформирован
            if ($receipt !== null) {
                $paymentData['receipt'] = $receipt;
            }

            // Генерируем ключ идемпотентности
            $idempotenceKey = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            // Создаем платеж в ЮKassa
            try {
                $yooKassaPayment = $yooKassaService->createPayment($paymentData, $idempotenceKey);
                
                Log::info('PaymentController::createYooKassaPayment - Payment created successfully', [
                    'order_id' => $order->id,
                    'payment_id' => $yooKassaPayment['id'] ?? null,
                    'status' => $yooKassaPayment['status'] ?? null,
                    'confirmation_url' => $yooKassaPayment['confirmation']['confirmation_url'] ?? null,
                ]);
            } catch (\Exception $e) {
                Log::error('PaymentController::createYooKassaPayment - Failed to create payment', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            DB::beginTransaction();

            // Создаем запись платежа в БД
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => Payment::METHOD_ONLINE,
                'payment_provider' => 'yookassa',
                'status' => $yooKassaPayment['status'] === 'succeeded' 
                    ? Payment::STATUS_SUCCEEDED 
                    : Payment::STATUS_PENDING,
                'amount' => $yooKassaPayment['amount']['value'],
                'currency' => $yooKassaPayment['amount']['currency'],
                'transaction_id' => $yooKassaPayment['id'],
                'provider_response' => $yooKassaPayment,
                'paid_at' => isset($yooKassaPayment['paid_at']) 
                    ? \Carbon\Carbon::parse($yooKassaPayment['paid_at']) 
                    : null,
            ]);

            // Обновляем статус заказа, если платеж уже прошел
            if ($yooKassaPayment['status'] === 'succeeded') {
                $order->payment_status = \App\Models\Order::PAYMENT_STATUS_SUCCEEDED;
                $order->payment_id = (string) $payment->id;
                $order->save();
            }

            DB::commit();

            $payment->load('order');

            return response()->json([
                'data' => [
                    'payment' => $payment,
                    'yookassa_payment' => $yooKassaPayment,
                    'confirmation_url' => $yooKassaPayment['confirmation']['confirmation_url'] ?? null,
                ],
                'message' => 'Платеж успешно создан',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при создании платежа ЮKassa: ' . $e->getMessage(), [
                'order_id' => $request->get('order_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ошибка при создании платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить платеж
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            $payment->delete();

            return response()->json([
                'message' => 'Платеж успешно удален',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении платежа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при удалении платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
