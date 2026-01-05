<?php

namespace App\Services\Order;

use App\Models\Bot;
use App\Models\Order;
use App\Models\TelegramUser;
use App\Services\TelegramService;
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

            if ($admins->isEmpty()) {
                Log::warning('No admins found for order notification', ['order_id' => $order->id, 'bot_id' => $bot->id]);
                return false;
            }

            $message = $this->formatOrderMessage($order);
            
            // Проверяем наличие пользователей с нужными ролями
            $hasKitchen = TelegramUser::where('bot_id', $bot->id)
                ->where('role', TelegramUser::ROLE_KITCHEN)
                ->where('is_blocked', false)
                ->exists();
            
            $hasCourier = TelegramUser::where('bot_id', $bot->id)
                ->where('role', TelegramUser::ROLE_COURIER)
                ->where('is_blocked', false)
                ->exists();
            
            // Формируем клавиатуру только с доступными действиями
            $keyboard = ['inline_keyboard' => []];
            $row = [];
            
            // Кнопка "Отправить на кухню" только если есть пользователи с ролью кухни
            if ($hasKitchen && in_array($order->status, [Order::STATUS_NEW, Order::STATUS_ACCEPTED])) {
                $row[] = [
                    'text' => '📤 Отправить на кухню',
                    'callback_data' => "order_action:{$order->id}:send_to_kitchen"
                ];
            }
            
            // Кнопка "Вызвать курьера" только если есть курьеры и заказ готов к доставке
            if ($hasCourier && in_array($order->status, [Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_DELIVERY])) {
                $row[] = [
                    'text' => '🚚 Вызвать курьера',
                    'callback_data' => "order_action:{$order->id}:call_courier"
                ];
            }
            
            // Добавляем строку только если есть хотя бы одна кнопка
            if (!empty($row)) {
                $keyboard['inline_keyboard'][] = $row;
            }

            $sent = false;
            foreach ($admins as $admin) {
                $result = $this->telegramService->sendMessage(
                    $bot->token,
                    $admin->telegram_id,
                    $message,
                    ['reply_markup' => json_encode($keyboard)]
                );
                if ($result['success'] ?? false) {
                    $sent = true;
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
                $result = $this->telegramService->sendMessage(
                    $bot->token,
                    $admin->telegram_id,
                    $message
                );
                if ($result['success'] ?? false) {
                    $sent = true;
                }
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

            // Получаем всех пользователей кухни для данного бота
            $kitchenUsers = TelegramUser::where('bot_id', $bot->id)
                ->where('role', TelegramUser::ROLE_KITCHEN)
                ->where('is_blocked', false)
                ->get();

            if ($kitchenUsers->isEmpty()) {
                Log::warning('No kitchen users found', ['order_id' => $order->id]);
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
                $result = $this->telegramService->sendMessage(
                    $bot->token,
                    $kitchenUser->telegram_id,
                    $message,
                    ['reply_markup' => json_encode($keyboard)]
                );
                if ($result['success'] ?? false) {
                    $sent = true;
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

            $result = $this->telegramService->sendMessage(
                $bot->token,
                $courier->telegram_id,
                $message,
                ['reply_markup' => json_encode($keyboard)]
            );

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
     * Уведомить клиента об изменении статуса
     *
     * @param Order $order
     * @param string $status
     * @return bool
     */
    public function notifyClientStatusChange(Order $order, string $status): bool
    {
        try {
            $bot = $order->bot;
            if (!$bot || !$bot->token || !$order->telegram_id) {
                return false;
            }

            $message = $this->formatClientStatusMessage($order, $status);
            
            // Добавляем кнопку отмены для всех статусов, кроме delivered и cancelled
            $keyboard = null;
            if (!in_array($status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])) {
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '❌ Отменить заказ',
                                'callback_data' => "order_cancel_request:{$order->id}"
                            ]
                        ]
                    ]
                ];
            }

            $options = [];
            if ($keyboard) {
                $options['reply_markup'] = json_encode($keyboard);
            }

            $result = $this->telegramService->sendMessage(
                $bot->token,
                $order->telegram_id,
                $message,
                $options
            );

            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('Error notifying client: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'status' => $status,
            ]);
            return false;
        }
    }

    /**
     * Форматировать сообщение о заказе
     *
     * @param Order $order
     * @return string
     */
    protected function formatOrderMessage(Order $order): string
    {
        $order->load('items');
        
        $message = "🛒 Новый заказ #{$order->order_id}\n\n";
        $message .= "👤 Телефон: {$order->phone}\n";
        if ($order->name) {
            $message .= "📝 Имя: {$order->name}\n";
        }
        $message .= "📍 Адрес: {$order->delivery_address}\n";
        if ($order->delivery_time) {
            $message .= "⏰ Время: {$order->delivery_time}\n";
        }
        $message .= "\n📦 Товары:\n";
        
        foreach ($order->items as $item) {
            $message .= "• {$item->product_name} × {$item->quantity} = " . number_format($item->quantity * $item->unit_price, 2, '.', ' ') . " ₽\n";
        }
        
        $message .= "\n💰 Итого: " . number_format($order->total_amount, 2, '.', ' ') . " ₽";
        
        if ($order->comment) {
            $message .= "\n\n💬 Комментарий: {$order->comment}";
        }

        return $message;
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
     * @return string
     */
    protected function formatClientStatusMessage(Order $order, string $status): string
    {
        $statusMessages = [
            Order::STATUS_ACCEPTED => "✅ Ваш заказ #{$order->order_id} принят в обработку",
            Order::STATUS_SENT_TO_KITCHEN => "👨‍🍳 Ваш заказ #{$order->order_id} отправлен на кухню",
            Order::STATUS_KITCHEN_ACCEPTED => "👨‍🍳 Ваш заказ #{$order->order_id} принят на кухне и начал готовиться",
            Order::STATUS_PREPARING => "👨‍🍳 Ваш заказ #{$order->order_id} готовится",
            Order::STATUS_READY_FOR_DELIVERY => "✅ Ваш заказ #{$order->order_id} готов и ожидает курьера",
            Order::STATUS_COURIER_ASSIGNED => "🚚 Курьер назначен на ваш заказ #{$order->order_id}",
            Order::STATUS_IN_TRANSIT => "🚚 Курьер забрал ваш заказ #{$order->order_id} и следует по адресу доставки",
            Order::STATUS_DELIVERED => "🎉 Ваш заказ #{$order->order_id} доставлен! Спасибо за заказ!",
            Order::STATUS_CANCELLED => "❌ Ваш заказ #{$order->order_id} отменен",
        ];

        return $statusMessages[$status] ?? "📋 Статус вашего заказа #{$order->order_id} изменен: {$status}";
    }
}

