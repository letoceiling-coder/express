<?php

namespace App\Services\Telegram;

use App\Models\Bot;
use App\Models\Order;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с Telegram Mini App
 */
class TelegramMiniAppService
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Валидация initData от Telegram Mini App
     * 
     * @param string $initData
     * @param string $botToken
     * @return array
     */
    public function validateInitData(string $initData, string $botToken): array
    {
        try {
            // Парсим initData
            parse_str($initData, $parsed);

            // Проверяем наличие обязательных полей
            if (!isset($parsed['hash']) || !isset($parsed['auth_date'])) {
                return [
                    'valid' => false,
                    'message' => 'Отсутствуют обязательные поля',
                ];
            }

            $hash = $parsed['hash'];
            unset($parsed['hash']);

            // Проверяем время (не старше 24 часов)
            $authDate = (int) $parsed['auth_date'];
            if (time() - $authDate > 86400) {
                return [
                    'valid' => false,
                    'message' => 'Данные устарели',
                ];
            }

            // Создаем строку для проверки подписи
            ksort($parsed);
            $dataCheckString = [];
            foreach ($parsed as $key => $value) {
                $dataCheckString[] = "{$key}={$value}";
            }
            $dataCheckString = implode("\n", $dataCheckString);

            // Проверяем подпись
            $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
            $calculatedHash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

            if (hash_equals($calculatedHash, $hash)) {
                return [
                    'valid' => true,
                    'data' => $parsed,
                    'user' => isset($parsed['user']) ? json_decode($parsed['user'], true) : null,
                ];
            }

            return [
                'valid' => false,
                'message' => 'Неверная подпись',
            ];
        } catch (\Exception $e) {
            Log::error('TelegramMiniApp validateInitData error: ' . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Ошибка при валидации: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Получить данные пользователя из initData
     * 
     * @param string $initData
     * @param string $botToken
     * @return array|null
     */
    public function getUserFromInitData(string $initData, string $botToken): ?array
    {
        $validation = $this->validateInitData($initData, $botToken);

        if ($validation['valid'] && isset($validation['user'])) {
            return $validation['user'];
        }

        return null;
    }

    /**
     * Отправить уведомление о новом заказе
     * 
     * @param Order $order
     * @param int|null $botId
     * @return bool
     */
    public function notifyNewOrder(Order $order, ?int $botId = null): bool
    {
        try {
            if (!$botId && $order->bot_id) {
                $botId = $order->bot_id;
            }

            if (!$botId) {
                // Получаем первого активного бота
                $bot = Bot::where('is_active', true)->first();
                if (!$bot) {
                    Log::warning('Нет активных ботов для отправки уведомления');
                    return false;
                }
                $botId = $bot->id;
            }

            $bot = Bot::find($botId);
            if (!$bot || !$bot->is_active) {
                Log::warning("Бот {$botId} не найден или неактивен");
                return false;
            }

            $message = "🛒 Новый заказ!\n\n";
            $message .= "📋 Номер: {$order->order_id}\n";
            $message .= "👤 Телефон: {$order->phone}\n";
            $message .= "📍 Адрес: {$order->delivery_address}\n";
            $message .= "💰 Сумма: {$order->total_amount} ₽\n";
            $message .= "📦 Товаров: " . $order->items->sum('quantity') . "\n";

            // Отправляем администраторам
            $adminIds = config('telegram.admin_user_ids', []);
            $sent = false;

            foreach ($adminIds as $adminId) {
                $this->telegramService->sendMessage($bot->token, $adminId, $message);
                $sent = true;
            }

            // Отправляем клиенту
            if ($order->telegram_id) {
                $this->telegramService->sendMessage(
                    $bot->token,
                    $order->telegram_id,
                    "✅ Ваш заказ #{$order->order_id} принят!\n\nМы свяжемся с вами в ближайшее время."
                );
            }

            $order->notification_sent = true;
            $order->save();

            return $sent;
        } catch (\Exception $e) {
            Log::error('TelegramMiniApp notifyNewOrder error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Отправить уведомление об изменении статуса заказа
     * 
     * @param Order $order
     * @param string $oldStatus
     * @param string $newStatus
     * @return bool
     */
    public function notifyOrderStatusChange(Order $order, string $oldStatus, string $newStatus): bool
    {
        try {
            if (!$order->telegram_id || !$order->bot_id) {
                return false;
            }

            $bot = Bot::find($order->bot_id);
            if (!$bot || !$bot->is_active) {
                return false;
            }

            $statusMessages = [
                'accepted' => "✅ Ваш заказ #{$order->order_id} принят в обработку",
                'preparing' => "👨‍🍳 Ваш заказ #{$order->order_id} готовится",
                'ready_for_delivery' => "📦 Ваш заказ #{$order->order_id} готов к доставке",
                'in_transit' => "🚚 Ваш заказ #{$order->order_id} в пути",
                'delivered' => "🎉 Ваш заказ #{$order->order_id} доставлен! Спасибо за покупку!",
                'cancelled' => "❌ Ваш заказ #{$order->order_id} отменен",
            ];

            $message = $statusMessages[$newStatus] ?? "Статус вашего заказа #{$order->order_id} изменен на: {$newStatus}";

            $this->telegramService->sendMessage($bot->token, $order->telegram_id, $message);

            return true;
        } catch (\Exception $e) {
            Log::error('TelegramMiniApp notifyOrderStatusChange error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Отправить уведомление об оплате
     * 
     * @param Order $order
     * @return bool
     */
    public function notifyPayment(Order $order): bool
    {
        try {
            if (!$order->telegram_id || !$order->bot_id) {
                return false;
            }

            $bot = Bot::find($order->bot_id);
            if (!$bot || !$bot->is_active) {
                return false;
            }

            $message = "💳 Заказ #{$order->order_id} оплачен!\n\nСпасибо за оплату!";

            $this->telegramService->sendMessage($bot->token, $order->telegram_id, $message);

            return true;
        } catch (\Exception $e) {
            Log::error('TelegramMiniApp notifyPayment error: ' . $e->getMessage());
            return false;
        }
    }
}


