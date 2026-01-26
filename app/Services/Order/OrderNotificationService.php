<?php

namespace App\Services\Order;

use App\Jobs\SendOrderNotificationJob;
use App\Models\Bot;
use App\Models\NotificationSetting;
use App\Models\Order;
use App\Models\OrderNotification;
use App\Models\TelegramUser;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для отправки уведомлений о заказах
 */
class OrderNotificationService
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Уведомить клиента о создании заказа
     *
     * @param Order $order
     * @return bool
     */
    public function notifyClientNewOrder(Order $order): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token || !$order->telegram_id) {
                return false;
            }

            // Получаем настройки для события order_created_client
            $setting = NotificationSetting::getByEvent('order_created_client');
            
            if (!$setting || !$setting->enabled) {
                // Если настройки нет или отключена, используем дефолт
                $message = "Спасибо! Ваш заказ #{$order->order_id} принят и ожидает подтверждения администратора.";
                $buttons = [];
            } else {
                // Используем шаблон из настроек
                $message = $setting->replacePlaceholders([
                    'order_id' => $order->order_id,
                ]);
                
                // Получаем кнопки из настроек
                $keyboard = $setting->formatButtonsForTelegram([
                    'order_id' => $order->id,
                ]);
                $buttons = $keyboard['inline_keyboard'] ?? [];
            }

            // Отправляем уведомление клиенту
            return $this->createClientNotification($order, $message, $buttons);
        } catch (\Exception $e) {
            Log::error('Error notifying client about new order: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Уведомить администратора о новом заказе
     *
     * @param Order $order
     * @return bool
     */
    public function notifyAdminNewOrder(Order $order): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token) {
                Log::warning('Bot not found for order notification', ['order_id' => $order->id]);
                return false;
            }

            // Получаем всех администраторов для данного бота
            $admins = TelegramUser::where('bot_id', $bot->id)
                ->where('role', TelegramUser::ROLE_ADMIN)
                ->where('is_blocked', false)
                ->get();

            Log::info('Admin notification check', [
                'order_id' => $order->id,
                'bot_id' => $bot->id,
                'admins_count' => $admins->count(),
                'admin_ids' => $admins->pluck('id')->toArray(),
                'admin_telegram_ids' => $admins->pluck('telegram_id')->toArray(),
            ]);

            if ($admins->isEmpty()) {
                Log::warning('No admins found for order notification', [
                    'order_id' => $order->id,
                    'bot_id' => $bot->id,
                    'all_telegram_users_count' => TelegramUser::where('bot_id', $bot->id)->count(),
                ]);
                return false;
            }

            // Форматируем сообщение для нового заказа
            $message = $this->formatAdminNewOrderMessage($order);
            
            // Получаем настройки для события order_created_admin
            $setting = NotificationSetting::getByEvent('order_created_admin');
            
            // Формируем клавиатуру с кнопками из настроек или дефолтную
            if ($setting && $setting->enabled && $setting->buttons) {
                $keyboard = $setting->formatButtonsForTelegram([
                    'order_id' => $order->id,
                ]);
            } else {
                // Дефолтная клавиатура
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '✅ Принять',
                                'callback_data' => "order_admin_action:{$order->id}:accept"
                            ],
                            [
                                'text' => '❌ Отменить',
                                'callback_data' => "order_admin_action:{$order->id}:cancel"
                            ]
                        ]
                    ]
                ];
            }

            $sent = false;
            foreach ($admins as $admin) {
                try {
                    // Для администраторов отправляем синхронно, чтобы гарантировать доставку
                    $result = $this->telegramService->sendMessage(
                        $bot->token,
                        $admin->telegram_id,
                        $message,
                        ['reply_markup' => json_encode($keyboard)]
                    );

                    if ($result['success'] ?? false) {
                        $messageId = $result['data']['message_id'] ?? null;
                        
                        // Сохраняем уведомление в БД
                        if ($messageId) {
                            $this->saveNotification(
                                $order,
                                $admin,
                                $messageId,
                                $admin->telegram_id,
                                OrderNotification::TYPE_ADMIN_NEW,
                                now()->addMinutes(5)
                            );
                        }

                        Log::info('Admin notification sent successfully', [
                            'order_id' => $order->id,
                            'admin_id' => $admin->id,
                            'admin_telegram_id' => $admin->telegram_id,
                            'message_id' => $messageId,
                        ]);
                        $sent = true;
                    } else {
                        Log::error('Failed to send admin notification', [
                            'order_id' => $order->id,
                            'admin_id' => $admin->id,
                            'admin_telegram_id' => $admin->telegram_id,
                            'error' => $result['message'] ?? 'Unknown error',
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Exception sending admin notification', [
                        'order_id' => $order->id,
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            return $sent;
        } catch (\Exception $e) {
            Log::error('Error notifying admin about new order: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Уведомить администратора об изменении статуса
     *
     * @param Order $order
     * @param string $status
     * @param array $details Может содержать: message, cancel_reason и другие детали
     * @return bool
     */
    public function notifyAdminStatusChange(Order $order, string $status, array $details = []): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token) {
                return false;
            }

            $admins = TelegramUser::where('bot_id', $bot->id)
                ->where('role', TelegramUser::ROLE_ADMIN)
                ->where('is_blocked', false)
                ->get();

            if ($admins->isEmpty()) {
                return false;
            }

            $message = $this->formatStatusChangeMessage($order, $status, $details);

            $sent = false;
            foreach ($admins as $admin) {
                // Используем очередь для отправки
                SendOrderNotificationJob::dispatch(
                    $bot->token,
                    $admin->telegram_id,
                    $message,
                    [],
                    $order->id,
                    $admin->id,
                    OrderNotification::TYPE_ADMIN_STATUS,
                    null // Уведомления администратора о статусе не истекают
                )->onQueue('telegram-notifications');
                $sent = true;
            }

            return $sent;
        } catch (\Exception $e) {
            Log::error('Error notifying admin about status change: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'status' => $status,
            ]);
            return false;
        }
    }

    /**
     * Уведомить кухню о новом заказе
     *
     * @param Order $order
     * @return bool
     */
    public function notifyKitchenOrderSent(Order $order): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token) {
                return false;
            }

            // Получаем всех пользователей кухни для данного бота (из кэша)
            $kitchenUsers = $this->getCachedKitchenUsers($bot->id);

            Log::info('Kitchen notification check', [
                'order_id' => $order->id,
                'bot_id' => $bot->id,
                'kitchen_users_count' => $kitchenUsers->count(),
                'kitchen_user_ids' => $kitchenUsers->pluck('id')->toArray(),
                'kitchen_telegram_ids' => $kitchenUsers->pluck('telegram_id')->toArray(),
            ]);

            if ($kitchenUsers->isEmpty()) {
                Log::warning('No kitchen users found', [
                    'order_id' => $order->id,
                    'bot_id' => $bot->id,
                    'all_telegram_users_count' => TelegramUser::where('bot_id', $bot->id)->count(),
                ]);
                return false;
            }

            $message = $this->formatKitchenOrderMessage($order);
            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '✅ Принять заказ',
                            'callback_data' => "order_kitchen_accept:{$order->id}"
                        ]
                    ]
                ]
            ];

            $sent = false;
            foreach ($kitchenUsers as $kitchenUser) {
                try {
                    // Отправляем синхронно для гарантии доставки
                    $result = $this->telegramService->sendMessage(
                        $bot->token,
                        $kitchenUser->telegram_id,
                        $message,
                        ['reply_markup' => json_encode($keyboard)]
                    );

                    if ($result['success'] ?? false) {
                        $messageId = $result['data']['message_id'] ?? null;
                        
                        // Сохраняем уведомление в БД
                        if ($messageId) {
                            $this->saveNotification(
                                $order,
                                $kitchenUser,
                                $messageId,
                                $kitchenUser->telegram_id,
                                OrderNotification::TYPE_KITCHEN_ORDER,
                                now()->addMinutes(10)
                            );
                        }

                        Log::info('Kitchen notification sent successfully', [
                            'order_id' => $order->id,
                            'kitchen_user_id' => $kitchenUser->id,
                            'kitchen_telegram_id' => $kitchenUser->telegram_id,
                            'message_id' => $messageId,
                        ]);
                        $sent = true;
                    } else {
                        Log::error('Failed to send kitchen notification', [
                            'order_id' => $order->id,
                            'kitchen_user_id' => $kitchenUser->id,
                            'kitchen_telegram_id' => $kitchenUser->telegram_id,
                            'error' => $result['message'] ?? 'Unknown error',
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Exception sending kitchen notification', [
                        'order_id' => $order->id,
                        'kitchen_user_id' => $kitchenUser->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            return $sent;
        } catch (\Exception $e) {
            Log::error('Error notifying kitchen: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
            return false;
        }
    }

    /**
     * Уведомить курьера о готовности заказа
     *
     * @param Order $order
     * @param TelegramUser $courier
     * @return bool
     */
    public function notifyCourierOrderReady(Order $order, TelegramUser $courier): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token) {
                return false;
            }

            $message = $this->formatCourierOrderMessage($order);
            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '✅ Забрал заказ',
                            'callback_data' => "order_courier_picked:{$order->id}"
                        ]
                    ]
                ]
            ];

            Log::info('Sending courier notification', [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
                'courier_telegram_id' => $courier->telegram_id,
            ]);

            $result = $this->telegramService->sendMessage(
                $bot->token,
                $courier->telegram_id,
                $message,
                ['reply_markup' => json_encode($keyboard)]
            );

            if ($result['success'] ?? false) {
                $messageId = $result['data']['message_id'] ?? null;
                
                // Сохраняем уведомление в БД
                if ($messageId) {
                    $this->saveNotification(
                        $order,
                        $courier,
                        $messageId,
                        $courier->telegram_id,
                        OrderNotification::TYPE_COURIER_ORDER,
                        now()->addMinutes(15)
                    );
                }

                Log::info('Courier notification sent successfully', [
                    'order_id' => $order->id,
                    'courier_id' => $courier->id,
                    'message_id' => $messageId,
                ]);
            } else {
                Log::error('Failed to send courier notification', [
                    'order_id' => $order->id,
                    'courier_id' => $courier->id,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
            }

            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('Error notifying courier: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
            ]);
            return false;
        }
    }

    /**
     * Уведомить курьера о том, что заказ в пути (после того как он забрал заказ)
     *
     * @param Order $order
     * @param TelegramUser $courier
     * @return bool
     */
    public function notifyCourierInTransit(Order $order, TelegramUser $courier): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token) {
                return false;
            }

            $message = "✅ Заказ #{$order->order_id} забран\n\n";
            $message .= "📍 Адрес доставки: {$order->delivery_address}\n";
            if ($order->delivery_time) {
                $message .= "⏰ Время доставки: {$order->delivery_time}\n";
            }
            $message .= "💰 Сумма: " . number_format($order->total_amount, 2, '.', ' ') . " ₽\n";
            
            // Проверяем статус оплаты
            $paymentStatus = $order->payment_status === Order::PAYMENT_STATUS_PENDING 
                ? "⚠️ Оплата не получена (принять при доставке)" 
                : "✅ Оплата получена";
            $message .= "\n{$paymentStatus}";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '✅ Доставлен',
                            'callback_data' => "order_courier_delivered:{$order->id}"
                        ]
                    ]
                ]
            ];

            // Добавляем кнопку "Оплачен" если оплата не получена
            if ($order->payment_status === Order::PAYMENT_STATUS_PENDING) {
                $keyboard['inline_keyboard'][0][] = [
                    'text' => '💳 Оплачен',
                    'callback_data' => "order_payment:{$order->id}:received"
                ];
            }

            // Отправляем синхронно для немедленной доставки
            Log::info('Attempting to send courier in transit notification synchronously', [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
                'courier_telegram_id' => $courier->telegram_id,
            ]);

            $result = $this->telegramService->sendMessage(
                $bot->token,
                $courier->telegram_id,
                $message,
                ['reply_markup' => json_encode($keyboard)]
            );

            if ($result['success'] ?? false) {
                $this->saveNotification(
                    $order,
                    $courier,
                    $result['data']['message_id'],
                    $courier->telegram_id,
                    OrderNotification::TYPE_COURIER_ORDER,
                    null // Уведомления курьера в пути не истекают
                );
                Log::info('✅ Courier in transit notification sent successfully', [
                    'order_id' => $order->id,
                    'courier_id' => $courier->id,
                    'courier_telegram_id' => $courier->telegram_id,
                    'message_id' => $result['data']['message_id'],
                ]);
                return true;
            } else {
                Log::error('Failed to send courier in transit notification', [
                    'order_id' => $order->id,
                    'courier_id' => $courier->id,
                    'courier_telegram_id' => $courier->telegram_id,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error notifying courier in transit: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
            ]);
            return false;
        }
    }

    /**
     * Уведомить клиента об изменении статуса
     *
     * @param Order $order
     * @param string $status
     * @param array $details Дополнительные детали (например, имя курьера)
     * @return bool
     */
    public function notifyClientStatusChange(Order $order, string $status, array $details = []): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token || !$order->telegram_id) {
                return false;
            }

            // Для статуса accepted используем настройки из order_accepted_client
            if ($status === Order::STATUS_ACCEPTED) {
                $setting = NotificationSetting::getByEvent('order_accepted_client');
                
                if ($setting && $setting->enabled) {
                    // Используем шаблон из настроек
                    $message = $setting->replacePlaceholders([
                        'order_id' => $order->order_id,
                    ]);
                    
                    // Получаем кнопки из настроек
                    $keyboard = $setting->formatButtonsForTelegram([
                        'order_id' => $order->id,
                    ]);
                    $buttons = $keyboard['inline_keyboard'] ?? [];
                    
                    return $this->updateClientNotification($order, $message, $buttons);
                }
            }

            // Для остальных статусов используем стандартную логику
            $message = $this->formatClientStatusMessage($order, $status, $details);
            
            // Добавляем кнопку отмены только если заказ принят администратором
            $buttons = [];
            if ($status === Order::STATUS_ACCEPTED || 
                (in_array($status, [Order::STATUS_SENT_TO_KITCHEN, Order::STATUS_PREPARING, Order::STATUS_READY_FOR_DELIVERY]) && 
                 $order->status !== Order::STATUS_DELIVERED && 
                 $order->status !== Order::STATUS_CANCELLED)) {
                $buttons = [
                    [
                        [
                            'text' => '❌ Отменить заказ',
                            'callback_data' => "order_cancel_request:{$order->id}"
                        ]
                    ]
                ];
            }

            // Используем метод обновления уведомления
            return $this->updateClientNotification($order, $message, $buttons);
        } catch (\Exception $e) {
            Log::error('Error notifying client: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'status' => $status,
            ]);
            return false;
        }
    }

    /**
     * Форматировать сообщение о новом заказе для администратора
     *
     * @param Order $order
     * @return string
     */
    protected function formatAdminNewOrderMessage(Order $order): string
    {
        $order->load('items');
        
        $message = "🆕 Новый заказ #{$order->order_id}\n\n";
        
        if ($order->name) {
            $message .= "👤 Клиент: {$order->name}\n";
        }
        $message .= "📞 Телефон: {$order->phone}\n";
        $message .= "📍 Адрес: {$order->delivery_address}\n";
        if ($order->delivery_time) {
            $message .= "🕐 Время доставки: {$order->delivery_time}\n";
        }
        $message .= "💰 Сумма: " . number_format($order->total_amount, 2, '.', ' ') . " ₽\n\n";
        
        $message .= "📦 Товары:\n";
        foreach ($order->items as $item) {
            $itemTotal = $item->quantity * $item->unit_price;
            $message .= "• {$item->product_name} × {$item->quantity} = " . number_format($itemTotal, 2, '.', ' ') . " ₽\n";
        }
        
        if ($order->comment) {
            $message .= "\n💬 Комментарий: {$order->comment}";
        } else {
            $message .= "\n💬 Комментарий: Без комментария";
        }

        return $message;
    }

    /**
     * Форматировать сообщение о заказе (общий метод)
     *
     * @param Order $order
     * @return string
     */
    protected function formatOrderMessage(Order $order): string
    {
        return $this->formatAdminNewOrderMessage($order);
    }

    /**
     * Форматировать сообщение об изменении статуса
     *
     * @param Order $order
     * @param string $status
     * @param array $details
     * @return string
     */
    protected function formatStatusChangeMessage(Order $order, string $status, array $details = []): string
    {
        $statusMessages = [
            Order::STATUS_READY_FOR_DELIVERY => "✅ Заказ #{$order->order_id} готов к доставке",
            Order::STATUS_DELIVERED => "🎉 Заказ #{$order->order_id} доставлен",
            Order::STATUS_KITCHEN_ACCEPTED => "👨‍🍳 Заказ #{$order->order_id} принят кухней",
        ];

        $message = $statusMessages[$status] ?? "📋 Статус заказа #{$order->order_id} изменен: {$status}";
        $message .= "\n\n📍 Адрес: {$order->delivery_address}";
        $message .= "\n💰 Сумма: " . number_format($order->total_amount, 2, '.', ' ') . " ₽";

        return $message;
    }

    /**
     * Форматировать сообщение для кухни
     *
     * @param Order $order
     * @return string
     */
    protected function formatKitchenOrderMessage(Order $order): string
    {
        $order->load('items');
        
        $message = "👨‍🍳 Новый заказ для кухни #{$order->order_id}\n\n";
        $message .= "📦 Товары:\n";
        
        foreach ($order->items as $item) {
            $message .= "• {$item->product_name} × {$item->quantity}\n";
        }
        
        if ($order->comment) {
            $message .= "\n💬 Комментарий: {$order->comment}";
        }

        return $message;
    }

    /**
     * Форматировать сообщение для курьера
     *
     * @param Order $order
     * @return string
     */
    protected function formatCourierOrderMessage(Order $order): string
    {
        $message = "🚚 Заказ готов к доставке #{$order->order_id}\n\n";
        $message .= "📍 Адрес: {$order->delivery_address}\n";
        if ($order->delivery_time) {
            $message .= "⏰ Время: {$order->delivery_time}\n";
        }
        $message .= "💰 Сумма: " . number_format($order->total_amount, 2, '.', ' ') . " ₽";
        $message .= "\n💳 Оплата: " . ($order->payment_status === Order::PAYMENT_STATUS_SUCCEEDED ? 'Оплачен' : 'При получении');

        return $message;
    }

    /**
     * Форматировать сообщение для клиента
     *
     * @param Order $order
     * @param string $status
     * @param array $details Дополнительные детали (например, имя курьера)
     * @return string
     */
    protected function formatClientStatusMessage(Order $order, string $status, array $details = []): string
    {
        $courierName = $details['courier_name'] ?? null;
        if (!$courierName && $order->courier_id) {
            $courier = $order->courier;
            $courierName = $courier->full_name ?? null;
        }

        $statusMessages = [
            Order::STATUS_ACCEPTED => "✅ Ваш заказ #{$order->order_id} принят в обработку",
            Order::STATUS_SENT_TO_KITCHEN => "👨‍🍳 Ваш заказ #{$order->order_id} отправлен на кухню",
            Order::STATUS_KITCHEN_ACCEPTED => "👨‍🍳 Ваш заказ #{$order->order_id} принят на кухне и начал готовиться",
            Order::STATUS_PREPARING => "👨‍🍳 Ваш заказ #{$order->order_id} готовится",
            Order::STATUS_READY_FOR_DELIVERY => "✅ Ваш заказ #{$order->order_id} готов и ожидает курьера",
            Order::STATUS_COURIER_ASSIGNED => $courierName 
                ? "🚚 Курьер {$courierName} назначен на ваш заказ #{$order->order_id}"
                : "🚚 Курьер назначен на ваш заказ #{$order->order_id}",
            Order::STATUS_IN_TRANSIT => $courierName
                ? "🚚 Курьер {$courierName} забрал ваш заказ #{$order->order_id} и следует по адресу доставки"
                : "🚚 Курьер забрал ваш заказ #{$order->order_id} и следует по адресу доставки",
            Order::STATUS_DELIVERED => "🎉 Ваш заказ #{$order->order_id} доставлен! Спасибо за заказ!",
            Order::STATUS_CANCELLED => "❌ Ваш заказ #{$order->order_id} отменен",
        ];

        return $statusMessages[$status] ?? "📋 Статус вашего заказа #{$order->order_id} изменен: {$status}";
    }

    /**
     * Сохранить уведомление в БД
     *
     * @param Order $order
     * @param TelegramUser $user
     * @param int $messageId
     * @param int $chatId
     * @param string $type
     * @param \DateTime|null $expiresAt
     * @return OrderNotification
     */
    public function saveNotification(
        Order $order,
        TelegramUser $user,
        int $messageId,
        int $chatId,
        string $type,
        ?\DateTime $expiresAt = null
    ): OrderNotification {
        return OrderNotification::create([
            'order_id' => $order->id,
            'telegram_user_id' => $user->id,
            'message_id' => $messageId,
            'chat_id' => $chatId,
            'notification_type' => $type,
            'status' => OrderNotification::STATUS_ACTIVE,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Обновить уведомление клиента
     *
     * @param Order $order
     * @param string $newText
     * @param array $newButtons
     * @return bool
     */
    public function updateClientNotification(Order $order, string $newText, array $newButtons = []): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token || !$order->telegram_id) {
                return false;
            }

            // Получаем активное уведомление клиента
            $notification = $order->getClientNotification();

            if ($notification) {
                // Пытаемся обновить существующее сообщение
                $options = [];
                if (!empty($newButtons)) {
                    $options['reply_markup'] = json_encode(['inline_keyboard' => $newButtons]);
                }

                $result = $this->telegramService->editMessageText(
                    $bot->token,
                    $notification->chat_id,
                    $notification->message_id,
                    $newText,
                    $options
                );

                if ($result['success'] ?? false) {
                    // Обновляем статус уведомления
                    $notification->markAsUpdated();
                    Log::info('✅ Client notification updated', [
                        'order_id' => $order->id,
                        'message_id' => $notification->message_id,
                    ]);
                    return true;
                }

                // Если ошибка "message not found", создаем новое уведомление
                if (($result['error_code'] ?? null) === 'MESSAGE_NOT_FOUND') {
                    Log::warning('⚠️ Message not found, creating new notification', [
                        'order_id' => $order->id,
                        'old_message_id' => $notification->message_id,
                    ]);
                    
                    // Помечаем старое уведомление как удаленное
                    $notification->markAsDeleted();
                    
                    // Создаем новое уведомление
                    return $this->createClientNotification($order, $newText, $newButtons);
                }

                Log::error('❌ Failed to update client notification', [
                    'order_id' => $order->id,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
                return false;
            }

            // Если уведомление не существует, создаем новое
            return $this->createClientNotification($order, $newText, $newButtons);
        } catch (\Exception $e) {
            Log::error('❌ Exception updating client notification: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
            return false;
        }
    }

    /**
     * Создать новое уведомление клиента
     *
     * @param Order $order
     * @param string $text
     * @param array $buttons
     * @return bool
     */
    protected function createClientNotification(Order $order, string $text, array $buttons = []): bool
    {
        $bot = $order->bot;
        if (!$bot || !$bot->token || !$order->telegram_id) {
            return false;
        }

        $options = [];
        if (!empty($buttons)) {
            $options['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
        }

        // Для клиента используем синхронную отправку, чтобы сразу получить message_id
        $result = $this->telegramService->sendMessage(
            $bot->token,
            $order->telegram_id,
            $text,
            $options
        );

        if ($result['success'] ?? false) {
            $messageId = $result['data']['message_id'] ?? null;
            
            if ($messageId) {
                // Получаем или создаем TelegramUser для клиента
                $telegramUser = TelegramUser::where('bot_id', $bot->id)
                    ->where('telegram_id', $order->telegram_id)
                    ->first();
                
                if ($telegramUser) {
                    $this->saveNotification(
                        $order,
                        $telegramUser,
                        $messageId,
                        $order->telegram_id,
                        OrderNotification::TYPE_CLIENT_STATUS,
                        now()->addHours(24)
                    );
                }
            }
            
            return true;
        }

        return false;
    }

    /**
     * Удалить уведомление
     *
     * @param Order $order
     * @param TelegramUser $user
     * @param string|null $type
     * @return bool
     */
    public function deleteNotification(Order $order, TelegramUser $user, ?string $type = null): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token) {
                return false;
            }

            $query = OrderNotification::where('order_id', $order->id)
                ->where('telegram_user_id', $user->id)
                ->where('status', OrderNotification::STATUS_ACTIVE);

            if ($type) {
                $query->where('notification_type', $type);
            }

            $notifications = $query->get();

            foreach ($notifications as $notification) {
                // Пытаемся удалить сообщение в Telegram
                $this->telegramService->deleteMessage(
                    $bot->token,
                    $notification->chat_id,
                    $notification->message_id
                );

                // Помечаем как удаленное в БД
                $notification->markAsDeleted();
            }

            return true;
        } catch (\Exception $e) {
            Log::error('❌ Error deleting notification: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'telegram_user_id' => $user->id,
            ]);
            return false;
        }
    }

    /**
     * Удалить уведомления для заказа (с исключениями)
     *
     * @param Order $order
     * @param string|null $type
     * @param array $excludeUserIds
     * @return bool
     */
    public function deleteNotificationsForOrder(Order $order, ?string $type = null, array $excludeUserIds = []): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token) {
                return false;
            }

            $query = OrderNotification::where('order_id', $order->id)
                ->where('status', OrderNotification::STATUS_ACTIVE);

            if ($type) {
                $query->where('notification_type', $type);
            }

            if (!empty($excludeUserIds)) {
                $query->whereNotIn('telegram_user_id', $excludeUserIds);
            }

            $notifications = $query->get();

            // Массовое обновление статуса
            $query->update(['status' => OrderNotification::STATUS_DELETED]);

            // Удаляем сообщения в Telegram
            foreach ($notifications as $notification) {
                $this->telegramService->deleteMessage(
                    $bot->token,
                    $notification->chat_id,
                    $notification->message_id
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::error('❌ Error deleting notifications for order: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
            return false;
        }
    }

    /**
     * Получить кэшированный список курьеров
     *
     * @param int $botId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCachedCouriers(int $botId)
    {
        return Cache::remember("bot_{$botId}_couriers", now()->addMinutes(10), function () use ($botId) {
            return TelegramUser::where('bot_id', $botId)
                ->where('role', TelegramUser::ROLE_COURIER)
                ->where('is_blocked', false)
                ->get();
        });
    }

    /**
     * Получить кэшированный список кухни
     *
     * @param int $botId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCachedKitchenUsers(int $botId)
    {
        return Cache::remember("bot_{$botId}_kitchen", now()->addMinutes(10), function () use ($botId) {
            return TelegramUser::where('bot_id', $botId)
                ->where('role', TelegramUser::ROLE_KITCHEN)
                ->where('is_blocked', false)
                ->get();
        });
    }

    /**
     * Инвалидировать кэш пользователей
     *
     * @param int $botId
     * @return void
     */
    public function invalidateUserCache(int $botId): void
    {
        Cache::forget("bot_{$botId}_couriers");
        Cache::forget("bot_{$botId}_kitchen");
    }

    /**
     * Уведомить клиента о неоплаченном заказе через 10 минут
     *
     * @param Order $order
     * @return bool
     */
    public function notifyClientUnpaidAfter10Minutes(Order $order): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token || !$order->telegram_id) {
                return false;
            }

            // Проверяем, что заказ все еще не оплачен
            if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING) {
                return false;
            }

            $settings = \App\Models\OrderSetting::getSettings();
            if (!$settings->notification_10min_enabled) {
                return false;
            }

            $template = $settings->notification_10min_template 
                ?? 'Вы оформили заказ №{{orderId}} на {{amount}} ₽.\nЧтобы мы начали готовить, оплатите заказ.';

            $message = $settings->replaceTemplatePlaceholders($template, [
                'orderId' => $order->order_id,
                'amount' => number_format($order->total_amount, 2, '.', ' '),
            ]);

            // Кнопки для оплаты и отмены
            $buttons = [
                [
                    [
                        'text' => '💳 Оплатить',
                        'callback_data' => "order_pay:{$order->id}"
                    ],
                    [
                        'text' => '❌ Отменить',
                        'callback_data' => "order_cancel_request:{$order->id}"
                    ]
                ]
            ];

            return $this->createClientNotification($order, $message, $buttons);
        } catch (\Exception $e) {
            Log::error('Error notifying client about unpaid order (10min): ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
            return false;
        }
    }

    /**
     * Уведомить клиента за 5 минут до истечения TTL
     *
     * @param Order $order
     * @return bool
     */
    public function notifyClient5MinutesBeforeTTL(Order $order): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token || !$order->telegram_id) {
                return false;
            }

            // Проверяем, что заказ все еще не оплачен
            if ($order->payment_status !== Order::PAYMENT_STATUS_PENDING) {
                return false;
            }

            $settings = \App\Models\OrderSetting::getSettings();
            if (!$settings->notification_5min_before_ttl_enabled) {
                return false;
            }

            $template = $settings->notification_5min_template 
                ?? 'Заказ №{{orderId}} будет отменён через 5 минут, если не оплатить.';

            $message = $settings->replaceTemplatePlaceholders($template, [
                'orderId' => $order->order_id,
                'amount' => number_format($order->total_amount, 2, '.', ' '),
            ]);

            // Кнопки для оплаты и отмены
            $buttons = [
                [
                    [
                        'text' => '💳 Оплатить',
                        'callback_data' => "order_pay:{$order->id}"
                    ],
                    [
                        'text' => '❌ Отменить',
                        'callback_data' => "order_cancel_request:{$order->id}"
                    ]
                ]
            ];

            return $this->createClientNotification($order, $message, $buttons);
        } catch (\Exception $e) {
            Log::error('Error notifying client about TTL (5min): ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
            return false;
        }
    }
}

