<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Services\Telegram\TelegramUserService;
use App\Services\Order\OrderStatusService;
use App\Services\Order\OrderNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected TelegramUserService $telegramUserService;
    protected OrderStatusService $orderStatusService;
    protected OrderNotificationService $orderNotificationService;

    public function __construct(
        TelegramUserService $telegramUserService,
        OrderStatusService $orderStatusService,
        OrderNotificationService $orderNotificationService
    ) {
        $this->telegramUserService = $telegramUserService;
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
            'delivery_time' => 'nullable|string|max:255',
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
            
            $order = Order::create([
                'order_id' => $orderId,
                'telegram_id' => $telegramId,
                'bot_id' => $botId,
                'phone' => $request->get('phone'),
                'name' => $request->get('name'),
                'delivery_address' => $request->get('delivery_address'),
                'delivery_time' => $request->get('delivery_time'),
                'delivery_type' => $request->get('delivery_type', 'courier'), // По умолчанию курьер
                'comment' => $request->get('comment'),
                'total_amount' => $request->get('total_amount'),
                'original_amount' => $request->get('original_amount'),
                'discount_amount' => $request->get('discount', 0),
                'payment_method' => $request->get('payment_method'),
                'status' => Order::STATUS_NEW,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
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
            try {
                $notified = $this->orderNotificationService->notifyAdminNewOrder($order);
                
                if ($notified) {
                    // После успешной отправки уведомления автоматически меняем статус на accepted
                    $this->orderStatusService->changeStatus($order, Order::STATUS_ACCEPTED, [
                        'role' => 'admin',
                        'comment' => 'Заказ создан, уведомление администратору отправлено',
                    ]);
                    
                    Log::info('Order created and admin notified, status changed to accepted', [
                        'order_id' => $order->id,
                        'order_order_id' => $order->order_id,
                    ]);
                } else {
                    Log::warning('Order created but admin notification failed', [
                        'order_id' => $order->id,
                        'order_order_id' => $order->order_id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error notifying admin about new order: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
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
        
        Log::info('OrderController::index - Incoming request', [
            'has_user' => $hasAuth,
            'user_id' => $request->user()?->id,
            'has_auth_header' => !empty($authHeader),
            'auth_header_preview' => $authHeader ? substr($authHeader, 0, 20) . '...' : null,
            'telegram_id' => $request->get('telegram_id'),
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        $query = Order::query()->with(['items', 'manager', 'bot']);

        // Безопасность: если запрос без авторизации (публичный), обязателен telegram_id
        // Пользователь может получить только свои заказы
        // Если есть токен в заголовке, считаем запрос авторизованным (даже если Sanctum не распознал)
        if (!$hasAuth && !$hasToken) {
            // Публичный запрос БЕЗ токена - требуется telegram_id
            if (!$request->has('telegram_id') || !$request->get('telegram_id')) {
                Log::warning('OrderController::index - Missing telegram_id for public request', [
                    'request_params' => $request->all(),
                    'query_params' => $request->query(),
                ]);
                return response()->json([
                    'message' => 'Для получения заказов необходимо указать telegram_id или авторизоваться',
                ], 400);
            }
            
            // Логируем запрос с telegram_id для отладки
            $telegramId = $request->get('telegram_id');
            // Приводим к integer для корректного сравнения
            $telegramIdInt = is_numeric($telegramId) ? (int)$telegramId : null;
            
            Log::info('OrderController::index - Public request with telegram_id', [
                'telegram_id_raw' => $telegramId,
                'telegram_id_type' => gettype($telegramId),
                'telegram_id_int' => $telegramIdInt,
            ]);
            
            if (!$telegramIdInt) {
                Log::warning('OrderController::index - Invalid telegram_id format', [
                    'telegram_id' => $telegramId,
                ]);
                return response()->json([
                    'message' => 'Некорректный формат telegram_id',
                ], 400);
            }
            
            // Для публичных запросов принудительно фильтруем по telegram_id
            // Используем integer для корректного сравнения
            $query->where('telegram_id', $telegramIdInt);
            
            // Дополнительная проверка: логируем SQL запрос для отладки
            Log::info('OrderController::index - Public request filtered by telegram_id', [
                'telegram_id' => $telegramIdInt,
                'sql_query' => $query->toSql(),
                'sql_bindings' => $query->getBindings(),
            ]);
        } else {
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
}
