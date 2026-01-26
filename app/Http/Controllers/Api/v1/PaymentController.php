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
            
            // Запрещаем ручное изменение статусов для платежей через ЮKassa
            // Статусы должны синхронизироваться только через webhook или API синхронизацию
            if ($payment->payment_provider === 'yookassa' && $payment->transaction_id) {
                return response()->json([
                    'message' => 'Нельзя изменить статус платежа через ЮKassa вручную. Статусы синхронизируются автоматически с ЮKassa API.',
                    'error' => 'manual_status_change_forbidden',
                ], 403);
            }
            
            DB::beginTransaction();
            
            $oldStatus = $payment->status;
            $newStatus = $request->get('status');
            
            $payment->status = $newStatus;
            
            // Автоматически устанавливаем paid_at при статусе succeeded
            if ($newStatus === Payment::STATUS_SUCCEEDED && !$payment->paid_at) {
                $payment->paid_at = now();
            }
            
            $payment->save();
            
            // Обновляем статус заказа, если платеж успешен
            if ($newStatus === Payment::STATUS_SUCCEEDED && $payment->order) {
                $payment->order->payment_status = 'succeeded';
                $payment->order->payment_id = (string) $payment->id;
                if ($payment->order->status === 'new') {
                    $payment->order->status = 'accepted';
                }
                $payment->order->save();
            }

            DB::commit();

            Log::info('PaymentController::updateStatus - статус платежа изменен вручную', [
                'payment_id' => $payment->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'order_id' => $payment->order_id,
                'transaction_id' => $payment->transaction_id,
                'payment_provider' => $payment->payment_provider,
            ]);

            $payment->load('order');

            return response()->json([
                'data' => $payment,
                'message' => 'Статус платежа успешно обновлен',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при изменении статуса платежа: ' . $e->getMessage(), [
                'payment_id' => $id,
                'new_status' => $request->get('status'),
                'trace' => $e->getTraceAsString(),
            ]);

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
            'description' => ['nullable', 'string', 'max:255'],
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

            // Если платеж через YooKassa, создаем возврат через API
            if ($payment->payment_provider === 'yookassa' && $payment->transaction_id) {
                $settings = PaymentSetting::forProvider('yookassa');
                
                if (!$settings || !$settings->is_enabled) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Интеграция с ЮKassa отключена или не настроена',
                    ], 400);
                }

                try {
                    $yooKassaService = new YooKassaService($settings);
                    
                    $refundData = [
                        'amount' => $refundAmount,
                        'currency' => $payment->currency ?? 'RUB',
                        'description' => $request->get('description') ?? 'Возврат платежа #' . ($payment->order->order_id ?? $payment->id),
                    ];
                    
                    Log::info('PaymentController::refund - создание возврата через YooKassa API', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'refund_amount' => $refundAmount,
                        'currency' => $refundData['currency'],
                    ]);
                    
                    $refundResponse = $yooKassaService->createRefund($payment->transaction_id, $refundData);
                    
                    Log::info('PaymentController::refund - возврат создан через YooKassa API', [
                        'payment_id' => $payment->id,
                        'yookassa_refund_id' => $refundResponse['id'] ?? null,
                        'refund_status' => $refundResponse['status'] ?? null,
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('PaymentController::refund - ошибка создания возврата через YooKassa API', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    return response()->json([
                        'message' => 'Ошибка создания возврата через YooKassa: ' . $e->getMessage(),
                        'error' => $e->getMessage(),
                    ], 500);
                }
            }

            // Обновляем статус платежа в БД
            $oldStatus = $payment->status;
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

            Log::info('PaymentController::refund - возврат выполнен', [
                'payment_id' => $payment->id,
                'old_status' => $oldStatus,
                'new_status' => $payment->status,
                'refund_amount' => $refundAmount,
                'total_refunded' => $newRefundedAmount,
                'order_id' => $payment->order_id,
            ]);

            $payment->load('order');

            return response()->json([
                'data' => $payment,
                'message' => 'Платеж успешно возвращен',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PaymentController::refund - ошибка возврата платежа', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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
            // total_amount в заказе должен содержать итоговую сумму (товары + доставка)
            $requestAmount = (float) $request->get('amount');
            $orderTotalAmount = (float) $order->total_amount;
            $orderDeliveryCost = (float) $order->delivery_cost;
            $orderItemsTotal = $orderTotalAmount - $orderDeliveryCost;
            
            // Проверяем, что total_amount = товары + доставка
            if (abs($requestAmount - $orderTotalAmount) > 0.01) {
                Log::warning('PaymentController::createYooKassaPayment - Amount mismatch', [
                    'order_id' => $order->id,
                    'request_amount' => $requestAmount,
                    'order_total_amount' => $orderTotalAmount,
                    'order_items_total' => $orderItemsTotal,
                    'order_delivery_cost' => $orderDeliveryCost,
                    'expected_total' => $orderItemsTotal + $orderDeliveryCost,
                ]);
                return response()->json([
                    'message' => 'Сумма платежа не соответствует сумме заказа (товары + доставка)',
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
            
            // Добавляем товары в чек
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
                // Если items не загружены, создаем один item на сумму товаров
                $itemsTotal = (float) $order->total_amount - (float) $order->delivery_cost;
                if ($itemsTotal > 0) {
                    $receiptTotalAmount += $itemsTotal;
                    $receiptItems[] = [
                        'description' => 'Товары',
                        'quantity' => '1.00', // Формат с двумя знаками после запятой
                        'amount' => [
                            'value' => number_format($itemsTotal, 2, '.', ''), // Цена за единицу
                            'currency' => 'RUB',
                        ],
                        'vat_code' => '1', // НДС 20% - строка, не число!
                        'payment_subject' => 'commodity', // Признак предмета расчета: товар
                        'payment_mode' => 'full_payment', // Признак способа расчета: полный расчет
                    ];
                }
            }
            
            // Добавляем доставку как отдельную позицию в чек (если есть)
            $deliveryCost = (float) $order->delivery_cost;
            if ($deliveryCost > 0 && $order->delivery_type === 'courier') {
                $receiptTotalAmount += $deliveryCost;
                $receiptItems[] = [
                    'description' => 'Доставка',
                    'quantity' => '1.00',
                    'amount' => [
                        'value' => number_format($deliveryCost, 2, '.', ''),
                        'currency' => 'RUB',
                    ],
                    'vat_code' => '1', // НДС 20%
                    'payment_subject' => 'service', // Признак предмета расчета: услуга
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
            
            // Получаем email из запроса, заказа или создаем placeholder
            $email = $request->get('email') 
                ?? ($order->email && filter_var($order->email, FILTER_VALIDATE_EMAIL) ? $order->email : null);
            
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
            // ВАЖНО: Согласно документации YooKassa, receipt обязателен, если в аккаунте включена фискализация
            // Receipt должен быть корректно сформирован со всеми обязательными полями
            
            // Проверяем финальную сумму чека
            // ВАЖНО: amount.value - это цена за единицу, нужно умножить на quantity
            $finalReceiptTotal = 0;
            foreach ($receiptItems as $item) {
                $unitPrice = (float) $item['amount']['value'];
                $quantity = (float) $item['quantity'];
                $finalReceiptTotal += $unitPrice * $quantity;
            }
            
            // Логируем детали расчета для отладки
            Log::info('PaymentController::createYooKassaPayment - Receipt calculation', [
                'order_id' => $order->id,
                'order_total_amount' => $order->total_amount,
                'order_delivery_cost' => $order->delivery_cost,
                'order_items_total' => (float)$order->total_amount - (float)$order->delivery_cost,
                'receipt_items_count' => count($receiptItems),
                'receipt_total' => $finalReceiptTotal,
                'payment_amount' => $paymentAmount,
                'receipt_items_breakdown' => array_map(function($item) {
                    return [
                        'description' => $item['description'] ?? '',
                        'quantity' => $item['quantity'] ?? '0',
                        'unit_price' => $item['amount']['value'] ?? '0',
                        'item_total' => isset($item['amount']['value'], $item['quantity']) 
                            ? (float)$item['amount']['value'] * (float)$item['quantity'] 
                            : 0,
                    ];
                }, $receiptItems),
            ]);
            
            // Формируем receipt - ОБЯЗАТЕЛЬНО, если есть данные
            // YooKassa требует receipt, если фискализация включена в аккаунте
            if (count($receiptItems) > 0 && !empty($receiptCustomer)) {
                $receipt = [
                    'customer' => $receiptCustomer,
                    'items' => $receiptItems,
                ];
            } else {
                // Если нет данных для receipt, создаем минимальный receipt
                // Это может быть нужно, если фискализация обязательна
                $receipt = [
                    'customer' => $receiptCustomer,
                    'items' => [
                        [
                            'description' => $description,
                            'quantity' => '1.00',
                            'amount' => [
                                'value' => number_format($paymentAmount, 2, '.', ''),
                                'currency' => 'RUB',
                            ],
                            'vat_code' => '1',
                            'payment_subject' => 'commodity',
                            'payment_mode' => 'full_payment',
                        ],
                    ],
                ];
            }
            
            Log::info('PaymentController::createYooKassaPayment - Receipt prepared', [
                'order_id' => $order->id,
                'is_test_mode' => $settings->is_test_mode,
                'receipt_enabled' => true, // Receipt всегда включен, так как обязателен
                'receipt_items_count' => count($receipt['items'] ?? []),
                'receipt_customer' => [
                    'has_email' => isset($receipt['customer']['email']),
                    'has_phone' => isset($receipt['customer']['phone']),
                    'email_preview' => isset($receipt['customer']['email']) ? substr($receipt['customer']['email'], 0, 3) . '****' : null,
                    'phone_preview' => isset($receipt['customer']['phone']) ? substr($receipt['customer']['phone'], 0, 4) . '****' : null,
                ],
                'receipt_total' => $finalReceiptTotal,
                'payment_amount' => $paymentAmount,
                'amount_match' => abs($finalReceiptTotal - $paymentAmount) < 0.01,
                'receipt_items_preview' => array_map(function($item) {
                    return [
                        'description' => substr($item['description'] ?? '', 0, 50),
                        'quantity' => $item['quantity'] ?? null,
                        'unit_price' => $item['amount']['value'] ?? null,
                        'vat_code' => $item['vat_code'] ?? null,
                        'vat_code_type' => isset($item['vat_code']) ? gettype($item['vat_code']) : null,
                        'payment_subject' => $item['payment_subject'] ?? null,
                        'payment_mode' => $item['payment_mode'] ?? null,
                        'item_total' => isset($item['amount']['value'], $item['quantity']) ? (float)$item['amount']['value'] * (float)$item['quantity'] : null,
                    ];
                }, $receipt['items'] ?? []),
            ]);

            // Подготавливаем данные для платежа
            // ВАЖНО: receipt обязателен, если фискализация включена в аккаунте YooKassa
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
                'receipt' => $receipt, // Receipt обязателен согласно требованиям YooKassa
            ];

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
                Log::info('PaymentController::createYooKassaPayment - Creating payment', [
                    'order_id' => $order->id,
                    'is_test_mode' => $settings->is_test_mode,
                    'shop_id' => $settings->getActiveShopId(),
                    'amount' => $paymentAmount,
                    'has_receipt' => isset($paymentData['receipt']),
                ]);
                
                $yooKassaPayment = $yooKassaService->createPayment($paymentData, $idempotenceKey);
                
                Log::info('PaymentController::createYooKassaPayment - Payment created successfully', [
                    'order_id' => $order->id,
                    'payment_id' => $yooKassaPayment['id'] ?? null,
                    'status' => $yooKassaPayment['status'] ?? null,
                    'confirmation_url' => $yooKassaPayment['confirmation']['confirmation_url'] ?? null,
                    'is_test_mode' => $settings->is_test_mode, // Режим работы (тестовый/продакшн)
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
                    'is_test_mode' => $settings->is_test_mode, // Информация о тестовом режиме для frontend
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
     * Синхронизировать статус платежа с ЮKassa API
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function syncStatus($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            
            // Синхронизация возможна только для платежей через ЮKassa
            if ($payment->payment_provider !== 'yookassa' || !$payment->transaction_id) {
                return response()->json([
                    'message' => 'Синхронизация доступна только для платежей через ЮKassa',
                ], 400);
            }
            
            $settings = PaymentSetting::forProvider('yookassa');
            
            if (!$settings || !$settings->is_enabled) {
                return response()->json([
                    'message' => 'Интеграция с ЮKassa отключена или не настроена',
                ], 400);
            }
            
            $yooKassaService = new YooKassaService($settings);
            
            // Получаем актуальный статус платежа из ЮKassa API
            $yooKassaPayment = $yooKassaService->getPayment($payment->transaction_id);
            
            $yooKassaStatus = $yooKassaPayment['status'] ?? null;
            
            if (!$yooKassaStatus) {
                return response()->json([
                    'message' => 'Не удалось получить статус платежа из ЮKassa',
                ], 500);
            }
            
            // Маппинг статусов ЮKassa на наши статусы
            $statusMap = [
                'pending' => Payment::STATUS_PENDING,
                'waiting_for_capture' => Payment::STATUS_PROCESSING,
                'succeeded' => Payment::STATUS_SUCCEEDED,
                'canceled' => Payment::STATUS_CANCELLED,
            ];
            
            $newStatus = $statusMap[$yooKassaStatus] ?? $payment->status;
            
            DB::beginTransaction();
            
            $oldStatus = $payment->status;
            $payment->status = $newStatus;
            $payment->provider_response = $yooKassaPayment;
            
            // Обновляем даты
            if (isset($yooKassaPayment['paid_at'])) {
                $payment->paid_at = \Carbon\Carbon::parse($yooKassaPayment['paid_at']);
            }
            if (isset($yooKassaPayment['captured_at'])) {
                $payment->captured_at = \Carbon\Carbon::parse($yooKassaPayment['captured_at']);
            }
            
            $payment->save();
            
            // Обновляем статус заказа, если платеж успешен
            if ($newStatus === Payment::STATUS_SUCCEEDED && $payment->order) {
                $order = $payment->order;
                $order->paid = true;
                $order->payment_status = 'succeeded';
                $order->payment_id = (string) $payment->id;
                if ($order->status === 'new') {
                    $order->status = \App\Models\Order::STATUS_PAID;
                }
                $order->save();
            }
            
            DB::commit();
            
            Log::info('PaymentController::syncStatus - статус платежа синхронизирован с ЮKassa', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'yookassa_status' => $yooKassaStatus,
                'order_id' => $payment->order_id,
            ]);
            
            $payment->load('order');
            
            return response()->json([
                'data' => $payment,
                'message' => 'Статус платежа успешно синхронизирован с ЮKassa',
                'yookassa_status' => $yooKassaStatus,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при синхронизации статуса платежа: ' . $e->getMessage(), [
                'payment_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при синхронизации статуса платежа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Синхронизировать статусы всех платежей через ЮKassa
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function syncAllStatuses(Request $request)
    {
        try {
            $settings = PaymentSetting::forProvider('yookassa');
            
            if (!$settings || !$settings->is_enabled) {
                return response()->json([
                    'message' => 'Интеграция с ЮKassa отключена или не настроена',
                ], 400);
            }
            
            // Получаем все платежи через ЮKassa со статусом pending или processing
            $payments = Payment::where('payment_provider', 'yookassa')
                ->whereNotNull('transaction_id')
                ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PROCESSING])
                ->get();
            
            $synced = 0;
            $errors = 0;
            
            $yooKassaService = new YooKassaService($settings);
            
            foreach ($payments as $payment) {
                try {
                    $yooKassaPayment = $yooKassaService->getPayment($payment->transaction_id);
                    $yooKassaStatus = $yooKassaPayment['status'] ?? null;
                    
                    if (!$yooKassaStatus) {
                        $errors++;
                        continue;
                    }
                    
                    $statusMap = [
                        'pending' => Payment::STATUS_PENDING,
                        'waiting_for_capture' => Payment::STATUS_PROCESSING,
                        'succeeded' => Payment::STATUS_SUCCEEDED,
                        'canceled' => Payment::STATUS_CANCELLED,
                    ];
                    
                    $newStatus = $statusMap[$yooKassaStatus] ?? $payment->status;
                    
                    if ($newStatus !== $payment->status) {
                        DB::beginTransaction();
                        
                        $payment->status = $newStatus;
                        $payment->provider_response = $yooKassaPayment;
                        
                        if (isset($yooKassaPayment['paid_at'])) {
                            $payment->paid_at = \Carbon\Carbon::parse($yooKassaPayment['paid_at']);
                        }
                        if (isset($yooKassaPayment['captured_at'])) {
                            $payment->captured_at = \Carbon\Carbon::parse($yooKassaPayment['captured_at']);
                        }
                        
                        $payment->save();
                        
                        if ($newStatus === Payment::STATUS_SUCCEEDED && $payment->order) {
                            $order = $payment->order;
                            $order->paid = true;
                            $order->payment_status = 'succeeded';
                            $order->payment_id = (string) $payment->id;
                            if ($order->status === 'new') {
                                $order->status = \App\Models\Order::STATUS_PAID;
                            }
                            $order->save();
                        }
                        
                        DB::commit();
                        $synced++;
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    $errors++;
                    Log::error('PaymentController::syncAllStatuses - ошибка синхронизации платежа', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            return response()->json([
                'message' => 'Синхронизация завершена',
                'synced' => $synced,
                'errors' => $errors,
                'total' => $payments->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при синхронизации всех статусов: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Ошибка при синхронизации статусов',
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
