<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Bot;
use App\Services\Telegram\TelegramUserService;
use App\Services\Telegram\TelegramMiniAppService;
use App\Services\Order\OrderStatusService;
use App\Services\Order\OrderNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected TelegramUserService $telegramUserService;
    protected TelegramMiniAppService $telegramMiniAppService;
    protected OrderStatusService $orderStatusService;
    protected OrderNotificationService $orderNotificationService;

    public function __construct(
        TelegramUserService $telegramUserService,
        TelegramMiniAppService $telegramMiniAppService,
        OrderStatusService $orderStatusService,
        OrderNotificationService $orderNotificationService
    ) {
        $this->telegramUserService = $telegramUserService;
        $this->telegramMiniAppService = $telegramMiniAppService;
        $this->orderStatusService = $orderStatusService;
        $this->orderNotificationService = $orderNotificationService;
    }
    /**
     * Создать новый заказ
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'nullable|string|unique:orders,order_id',
            'telegram_id' => 'required|integer',
            'phone' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'delivery_address' => 'required|string',
            'delivery_time' => 'required|string|max:255',
            'delivery_type' => 'nullable|string|in:courier,pickup',
            'comment' => 'nullable|string',
            'payment_method' => 'nullable|string|max:50',
            'total_amount' => 'required|numeric|min:0',
            'original_amount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Проверка минимального заказа для доставки
            if ($request->get('delivery_type') === 'courier') {
                $deliverySettings = \App\Models\DeliverySetting::getSettings();
                $minDeliveryTotal = $deliverySettings->min_delivery_order_total_rub ?? 3000;
                $totalAmount = (float) $request->get('total_amount');
                
                if ($totalAmount < $minDeliveryTotal) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Минимальный заказ на доставку — {$minDeliveryTotal} ₽. Добавьте товаров еще на " . number_format($minDeliveryTotal - $totalAmount, 2, '.', '') . " ₽",
                        'error' => 'min_delivery_order_total',
                        'min_amount' => $minDeliveryTotal,
                        'current_amount' => $totalAmount,
                        'required_amount' => $minDeliveryTotal - $totalAmount,
                    ], 422);
                }
            }

            // Генерируем order_id если не указан
            if (!$request->has('order_id') || empty($request->get('order_id'))) {
                $now = now();
                $dateStr = $now->format('Ymd');
                $randomNum = rand(1, 9999);
                $orderId = "ORD-{$dateStr}-{$randomNum}";
            } else {
                $orderId = $request->get('order_id');
            }

            // Получаем bot_id из запроса или определяем по умолчанию
            $botId = $request->get('bot_id');
            if (!$botId) {
                // Получаем первого активного бота
                $bot = \App\Models\Bot::where('is_active', true)->first();
                $botId = $bot ? $bot->id : null;
            }

            // Создаем заказ
            $telegramId = $request->get('telegram_id');
            Log::info('OrderController::store - Creating order', [
                'telegram_id' => $telegramId,
                'telegram_id_type' => gettype($telegramId),
                'telegram_id_value' => var_export($telegramId, true),
                'order_id' => $orderId,
                'phone' => $request->get('phone'),
                'name' => $request->get('name'),
                'total_amount' => $request->get('total_amount'),
                'items_count' => count($request->get('items', [])),
                'request_all' => $request->all(),
            ]);
            
            // Определяем статус заказа
            // Для нового заказа всегда 'new', независимо от того, что передано в запросе
            // Статус 'paid' устанавливается только при успешной оплате через webhook или синхронизацию
            $orderStatus = Order::STATUS_NEW;
            
            // Определяем статус оплаты
            // Если в запросе явно указан payment_status и он валиден, используем его
            // Иначе для нового заказа всегда 'pending'
            $paymentStatus = $request->get('payment_status');
            if (!$paymentStatus || !in_array($paymentStatus, [
                Order::PAYMENT_STATUS_PENDING,
                Order::PAYMENT_STATUS_SUCCEEDED,
                Order::PAYMENT_STATUS_FAILED,
                Order::PAYMENT_STATUS_CANCELLED,
            ])) {
                $paymentStatus = Order::PAYMENT_STATUS_PENDING;
            }
            
            // ВАЖНО: Если payment_status = 'succeeded', но заказ только создается,
            // статус заказа все равно должен быть 'new', а не 'paid'
            // Статус 'paid' устанавливается только при обработке успешного платежа
            
            $order = Order::create([
                'order_id' => $orderId,
                'telegram_id' => $telegramId,
                'bot_id' => $botId,
                'phone' => $request->get('phone'),
                'email' => $request->get('email'), // Email для чека
                'name' => $request->get('name'),
                'delivery_address' => $request->get('delivery_address'),
                'delivery_time' => $request->get('delivery_time'),
                'delivery_type' => $request->get('delivery_type', 'courier'), // По умолчанию курьер
                'delivery_cost' => $request->get('delivery_cost', 0),
                'comment' => $request->get('comment'),
                'total_amount' => $request->get('total_amount'),
                'original_amount' => $request->get('original_amount'),
                'discount_amount' => $request->get('discount', 0),
                'payment_method' => $request->get('payment_method'),
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
            ]);
            
            Log::info('OrderController::store - Order created successfully', [
                'order_id' => $order->id,
                'order_order_id' => $order->order_id,
                'telegram_id' => $order->telegram_id,
                'telegram_id_type' => gettype($order->telegram_id),
                'phone' => $order->phone,
                'name' => $order->name,
                'total_amount' => $order->total_amount,
                'items_count' => $order->items()->count(),
            ]);

            // Создаем элементы заказа
            foreach ($request->get('items') as $itemData) {
                $order->items()->create([
                    'product_id' => $itemData['product_id'] ?? null,
                    'product_name' => $itemData['product_name'],
                    'product_image' => $itemData['product_image'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                ]);
            }

            DB::commit();

            $order->load(['items.product', 'manager', 'bot']);

            // Синхронизируем пользователя Telegram
            if ($botId && $request->get('telegram_id')) {
                try {
                    // Пытаемся синхронизировать пользователя (если есть данные в запросе)
                    $userData = $request->get('user', []);
                    if (!empty($userData)) {
                        $this->telegramUserService->syncUser($botId, array_merge($userData, [
                            'id' => $request->get('telegram_id'),
                            'telegram_id' => $request->get('telegram_id'),
                        ]));
                    } else {
                        // Создаем пользователя с минимальными данными
                        $telegramUser = \App\Models\TelegramUser::firstOrCreate(
                            [
                                'bot_id' => $botId,
                                'telegram_id' => $request->get('telegram_id'),
                            ],
                            [
                                'first_name' => null,
                                'last_name' => null,
                                'username' => null,
                            ]
                        );
                    }

                    // Обновляем статистику пользователя
                    $telegramUser = \App\Models\TelegramUser::where('bot_id', $botId)
                        ->where('telegram_id', $request->get('telegram_id'))
                        ->first();
                    if ($telegramUser) {
                        $this->telegramUserService->updateStatistics($telegramUser);
                    }
                } catch (\Exception $e) {
                    Log::warning('Не удалось синхронизировать пользователя Telegram: ' . $e->getMessage());
                }
            }

            // Отправляем уведомление администратору о новом заказе
            // Статус остается 'new' до принятия администратором
            try {
                Log::info('Attempting to notify admin about new order', [
                    'order_id' => $order->id,
                    'order_order_id' => $order->order_id,
                    'bot_id' => $order->bot_id,
                    'bot_exists' => $order->bot ? true : false,
                    'bot_token_exists' => $order->bot && $order->bot->token ? true : false,
                ]);
                
                $notified = $this->orderNotificationService->notifyAdminNewOrder($order);
                
                if ($notified) {
                    Log::info('Order created and admin notified', [
                        'order_id' => $order->id,
                        'order_order_id' => $order->order_id,
                        'status' => $order->status, // Должен быть 'new'
                    ]);
                } else {
                    Log::warning('Order created but admin notification failed', [
                        'order_id' => $order->id,
                        'order_order_id' => $order->order_id,
                        'bot_id' => $order->bot_id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error notifying admin about new order: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return response()->json([
                'data' => $order->fresh(['items.product', 'manager', 'bot']),
                'message' => 'Заказ успешно создан',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при создании заказа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при создании заказа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить список заказов
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        // Логируем входящий запрос для отладки
        $hasAuth = $request->user() !== null;
        $authHeader = $request->header('Authorization');
        $hasToken = !empty($authHeader) && str_starts_with($authHeader, 'Bearer ');
        $initData = $request->header('X-Telegram-Init-Data');
        
        Log::info('OrderController::index - Incoming request', [
            'has_user' => $hasAuth,
            'user_id' => $request->user()?->id,
            'has_auth_header' => !empty($authHeader),
            'has_init_data' => !empty($initData),
            'init_data_preview' => $initData ? substr($initData, 0, 50) . '...' : null,
            'telegram_id_param' => $request->get('telegram_id'),
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        $query = Order::query()->with(['items', 'manager', 'bot']);
        $telegramIdInt = null;

        // Если запрос авторизован (админ), разрешаем все фильтры
        if ($hasAuth || $hasToken) {
            // Для авторизованных пользователей (админов) можно использовать все фильтры
            // Фильтрация по telegram_id (опционально)
            if ($request->has('telegram_id')) {
                $telegramId = $request->get('telegram_id');
                $telegramIdInt = is_numeric($telegramId) ? (int)$telegramId : null;
                if ($telegramIdInt) {
                    $query->where('telegram_id', $telegramIdInt);
                }
            }
            Log::info('OrderController::index - Authenticated request (by token or user)', [
                'user_id' => $request->user()?->id,
                'has_token' => $hasToken,
                'telegram_id_filter' => $request->get('telegram_id'),
            ]);
        } else {
            // Публичный запрос - требуется валидация через initData или telegram_id (fallback для web)
            
            // Приоритет 1: Валидация через initData (Telegram Mini App)
            if ($initData) {
                // Получаем токен бота
                $botToken = $request->header('X-Bot-Token') 
                    ?? $request->input('bot_token')
                    ?? config('telegram.bot_token');
                
                Log::info('OrderController::index - Bot token search', [
                    'has_x_bot_token' => $request->header('X-Bot-Token') !== null,
                    'has_bot_token_param' => $request->has('bot_token'),
                    'has_config_token' => !empty(config('telegram.bot_token')),
                    'token_found' => !empty($botToken),
                ]);
                
                // Пробуем найти бота по ID из запроса или из initData
                if (!$botToken) {
                    $botId = $request->input('bot_id');
                    if ($botId) {
                        $bot = Bot::find($botId);
                        if ($bot) {
                            $botToken = $bot->token;
                            Log::info('OrderController::index - Bot token found by bot_id', [
                                'bot_id' => $botId,
                            ]);
                        }
                    }
                }
                
                // Fallback: получаем токен первого активного бота
                if (!$botToken) {
                    $bot = Bot::where('is_active', true)->first();
                    if ($bot) {
                        $botToken = $bot->token;
                        Log::info('OrderController::index - Using first active bot token', [
                            'bot_id' => $bot->id,
                            'bot_username' => $bot->username,
                        ]);
                    } else {
                        Log::warning('OrderController::index - No active bots found in database');
                    }
                }
                
                if (!$botToken) {
                    Log::warning('OrderController::index - Bot token not found for initData validation', [
                        'active_bots_count' => Bot::where('is_active', true)->count(),
                        'total_bots_count' => Bot::count(),
                    ]);
                    return response()->json([
                        'message' => 'Токен бота не найден для валидации',
                    ], 400);
                }
                
                // Валидируем initData
                $validation = $this->telegramMiniAppService->validateInitData($initData, $botToken);
                
                if (!$validation['valid']) {
                    Log::warning('OrderController::index - Invalid initData', [
                        'message' => $validation['message'] ?? 'Invalid signature',
                    ]);
                    return response()->json([
                        'message' => 'Неверная подпись initData: ' . ($validation['message'] ?? 'Invalid'),
                    ], 401);
                }
                
                // Извлекаем user.id из валидированного initData
                $user = $validation['user'] ?? null;
                if (!$user || !isset($user['id'])) {
                    Log::warning('OrderController::index - User data not found in validated initData');
                    return response()->json([
                        'message' => 'Не удалось определить пользователя из initData',
                    ], 400);
                }
                
                $telegramIdInt = (int)$user['id'];
                Log::info('OrderController::index - Validated initData, extracted user.id', [
                    'telegram_id' => $telegramIdInt,
                    'user_first_name' => $user['first_name'] ?? null,
                    'user_username' => $user['username'] ?? null,
                ]);
                
                // Фильтруем заказы по telegram_id из валидированного initData
                $query->where('telegram_id', $telegramIdInt);
            } 
            // Приоритет 2: Fallback для web-версии (когда нет Telegram WebApp)
            else if ($request->has('telegram_id') && $request->get('telegram_id')) {
                // Web-версия: используем переданный telegram_id (без валидации, но с предупреждением)
                $telegramId = $request->get('telegram_id');
                $telegramIdInt = is_numeric($telegramId) ? (int)$telegramId : null;
                
                if (!$telegramIdInt) {
                    Log::warning('OrderController::index - Invalid telegram_id format for web fallback');
                    return response()->json([
                        'message' => 'Некорректный формат telegram_id',
                    ], 400);
                }
                
                Log::info('OrderController::index - Web fallback: using telegram_id parameter', [
                    'telegram_id' => $telegramIdInt,
                    'note' => 'This is a fallback for web version without Telegram WebApp',
                ]);
                
                $query->where('telegram_id', $telegramIdInt);
            } 
            // Ошибка: нет ни initData, ни telegram_id
            else {
                Log::warning('OrderController::index - Missing initData and telegram_id for public request', [
                    'request_params' => $request->all(),
                    'query_params' => $request->query(),
                    'headers' => [
                        'X-Telegram-Init-Data' => $request->header('X-Telegram-Init-Data'),
                        'X-Bot-Token' => $request->header('X-Bot-Token'),
                    ],
                ]);
                return response()->json([
                    'message' => 'Для получения заказов необходимо предоставить initData (Telegram Mini App) или telegram_id (web версия)',
                    'hint' => 'В Mini App передайте initData в заголовке X-Telegram-Init-Data. В web версии используйте параметр telegram_id.',
                ], 400);
            }
        }

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Фильтрация по статусу оплаты
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        // Фильтрация по менеджеру
        if ($request->has('manager_id')) {
            $query->where('manager_id', $request->get('manager_id'));
        }

        // Фильтрация по дате создания
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('delivery_address', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Логируем финальный SQL запрос перед выполнением
        Log::info('OrderController::index - Final query before execution', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'telegram_id_in_bindings' => isset($telegramIdInt) && in_array($telegramIdInt, $query->getBindings(), true),
        ]);
        
        // Пагинация
        $perPage = $request->get('per_page', 15);
        if ($perPage > 0) {
            $orders = $query->paginate($perPage);
        } else {
            $orders = $query->get();
        }
        
        // Логируем результат для отладки
        $ordersCount = is_countable($orders) ? count($orders) : (method_exists($orders, 'count') ? $orders->count() : 0);
        
        // Дополнительная проверка: выполняем прямой запрос для отладки
        if (isset($telegramIdInt)) {
            $directCount = Order::where('telegram_id', $telegramIdInt)->count();
            Log::info('OrderController::index - Direct count check', [
                'telegram_id' => $telegramIdInt,
                'direct_count' => $directCount,
                'query_count' => $ordersCount,
            ]);
        }
        $firstOrder = null;
        
        if ($ordersCount > 0) {
            if (method_exists($orders, 'items')) {
                // Пагинированный ответ
                $firstOrder = $orders->items()[0] ?? null;
            } else {
                // Коллекция
                $firstOrder = $orders[0] ?? $orders->first();
            }
        }
        
        // Определяем формат ответа для правильной сериализации
        $responseData = $orders;
        
        // Если это коллекция (не пагинация), преобразуем в массив для правильной сериализации
        if (!method_exists($orders, 'items') && method_exists($orders, 'toArray')) {
            $responseData = $orders->toArray();
        }
        
        Log::info('OrderController::index - Returning orders', [
            'telegram_id' => $request->get('telegram_id'),
            'orders_count' => $ordersCount,
            'is_paginated' => method_exists($orders, 'items'),
            'is_collection' => method_exists($orders, 'toArray'),
            'response_data_type' => gettype($responseData),
            'first_order_id' => $firstOrder?->id,
            'first_order_order_id' => $firstOrder?->order_id,
            'first_order_phone' => $firstOrder?->phone,
            'first_order_name' => $firstOrder?->name,
            'first_order_address' => $firstOrder?->delivery_address,
        ]);

        return response()->json([
            'data' => $responseData,
        ]);
    }

    /**
     * Получить детали заказа
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $order = Order::with(['items.product', 'manager', 'bot'])->findOrFail($id);

        return response()->json([
            'data' => $order,
        ]);
    }

    /**
     * Получить историю статусов заказа
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function statusHistory($id, Request $request)
    {
        $order = Order::findOrFail($id);

        $history = $this->orderStatusService->getStatusHistory($order);

        // Фильтрация по роли (если передана)
        if ($request->has('role') && $request->get('role')) {
            $history = $history->where('role', $request->get('role'));
        }

        // Фильтрация по статусу (если передан)
        if ($request->has('status') && $request->get('status')) {
            $history = $history->where('status', $request->get('status'));
        }

        // Поиск по комментариям (если передан)
        if ($request->has('search') && $request->get('search')) {
            $search = $request->get('search');
            $history = $history->filter(function ($item) use ($search) {
                return stripos($item->comment ?? '', $search) !== false;
            });
        }

        // Сортировка (хронологический порядок - от старых к новым для timeline)
        $history = $history->sortBy('created_at')->values();

        return response()->json([
            'data' => $history,
        ]);
    }

    /**
     * Обновить заказ
     * 
     * @param UpdateOrderRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateOrderRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $order = Order::findOrFail($id);
            $order->update($request->validated());

            DB::commit();

            $order->load(['items.product', 'manager', 'bot']);

            return response()->json([
                'data' => $order,
                'message' => 'Заказ успешно обновлен',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении заказа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при обновлении заказа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Изменить статус заказа
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
                'in:new,accepted,preparing,ready_for_delivery,in_transit,delivered,cancelled',
            ],
        ]);

        try {
            $order = Order::findOrFail($id);
            $oldStatus = $order->status;
            $newStatus = $request->get('status');

            if (!$order->canChangeStatus($newStatus)) {
                return response()->json([
                    'message' => 'Нельзя изменить статус заказа, который уже доставлен или отменен',
                ], 422);
            }

            $order->status = $newStatus;
            $order->save();

            $order->load(['items.product', 'manager', 'bot']);

            // Отправляем уведомление об изменении статуса
            if ($oldStatus !== $newStatus) {
                try {
                    $this->telegramMiniAppService->notifyOrderStatusChange($order, $oldStatus, $newStatus);
                } catch (\Exception $e) {
                    Log::warning('Не удалось отправить уведомление об изменении статуса заказа: ' . $e->getMessage());
                }
            }

            return response()->json([
                'data' => $order,
                'message' => 'Статус заказа успешно обновлен',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при изменении статуса заказа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при изменении статуса заказа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить заказ (soft delete)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->delete();

            return response()->json([
                'message' => 'Заказ успешно удален',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении заказа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при удалении заказа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Отменить заказ
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);

            // Проверяем права доступа: для публичных запросов требуется telegram_id
            $user = $request->user();
            if (!$user) {
                // Публичный запрос - проверяем telegram_id
                $telegramId = $request->input('telegram_id') ?: $request->query('telegram_id');
                if (!$telegramId) {
                    return response()->json([
                        'message' => 'Для отмены заказа необходимо указать telegram_id или авторизоваться',
                    ], 400);
                }

                // Проверяем, что заказ принадлежит пользователю
                if ($order->telegram_id != (int)$telegramId) {
                    return response()->json([
                        'message' => 'Вы не можете отменить этот заказ',
                    ], 403);
                }
            }

            // Проверяем, что заказ не уже отменен
            if ($order->status === Order::STATUS_CANCELLED) {
                return response()->json([
                    'message' => 'Заказ уже отменен',
                ], 400);
            }

            // Отменяем заказ
            $this->orderStatusService->changeStatus($order, Order::STATUS_CANCELLED, [
                'role' => 'user',
                'comment' => 'Отменено пользователем',
            ]);

            $order->refresh();
            $order->load('items');

            // Уведомляем клиента
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_CANCELLED);

            return response()->json([
                'message' => 'Заказ успешно отменен',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при отмене заказа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при отмене заказа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
