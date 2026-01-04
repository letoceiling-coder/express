<?php

namespace App\Services\Telegram;

use App\Models\Bot;
use App\Models\Order;
use App\Models\TelegramUser;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с пользователями Telegram бота
 */
class TelegramUserService
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Синхронизировать пользователя из Telegram
     * 
     * @param int $botId
     * @param array $telegramUserData Данные пользователя из Telegram (из update или initData)
     * @return TelegramUser
     */
    public function syncUser(int $botId, array $telegramUserData): TelegramUser
    {
        $telegramId = $telegramUserData['id'] ?? $telegramUserData['telegram_id'] ?? null;
        
        if (!$telegramId) {
            throw new \InvalidArgumentException('Telegram ID is required');
        }

        $user = TelegramUser::where('bot_id', $botId)
            ->where('telegram_id', $telegramId)
            ->first();

        $data = [
            'bot_id' => $botId,
            'telegram_id' => $telegramId,
            'first_name' => $telegramUserData['first_name'] ?? null,
            'last_name' => $telegramUserData['last_name'] ?? null,
            'username' => $telegramUserData['username'] ?? null,
            'language_code' => $telegramUserData['language_code'] ?? null,
            'is_premium' => $telegramUserData['is_premium'] ?? false,
            'last_interaction_at' => now(),
        ];

        if ($user) {
            $user->update($data);
            Log::info('Telegram user updated', [
                'bot_id' => $botId,
                'telegram_id' => $telegramId,
            ]);
        } else {
            $user = TelegramUser::create($data);
            Log::info('Telegram user created', [
                'bot_id' => $botId,
                'telegram_id' => $telegramId,
            ]);
        }

        return $user;
    }

    /**
     * Обновить данные пользователя из Telegram API
     * 
     * @param TelegramUser $user
     * @return TelegramUser
     */
    public function updateUserFromTelegram(TelegramUser $user): TelegramUser
    {
        $bot = $user->bot;
        if (!$bot) {
            throw new \Exception('Bot not found for user');
        }

        $result = $this->telegramService->getChat($bot->token, $user->telegram_id);
        
        if ($result['success'] && isset($result['data'])) {
            $chatData = $result['data'];
            
            $user->update([
                'first_name' => $chatData['first_name'] ?? $user->first_name,
                'last_name' => $chatData['last_name'] ?? $user->last_name,
                'username' => $chatData['username'] ?? $user->username,
                'last_interaction_at' => now(),
            ]);

            Log::info('Telegram user data updated from API', [
                'telegram_id' => $user->telegram_id,
            ]);
        } else {
            // Если пользователь заблокировал бота, помечаем как заблокированного
            if (isset($result['message']) && (
                str_contains($result['message'], 'chat not found') ||
                str_contains($result['message'], 'blocked')
            )) {
                $user->block();
                Log::warning('Telegram user blocked bot', [
                    'telegram_id' => $user->telegram_id,
                ]);
            }
        }

        return $user->fresh();
    }

    /**
     * Обновить статистику пользователя (orders_count, total_spent)
     * 
     * @param TelegramUser $user
     * @return void
     */
    public function updateStatistics(TelegramUser $user): void
    {
        $orders = Order::where('telegram_id', $user->telegram_id)
            ->where('bot_id', $user->bot_id)
            ->get();

        $ordersCount = $orders->count();
        $totalSpent = $orders->sum('total_amount');

        $user->update([
            'orders_count' => $ordersCount,
            'total_spent' => $totalSpent,
        ]);

        Log::info('Telegram user statistics updated', [
            'telegram_id' => $user->telegram_id,
            'orders_count' => $ordersCount,
            'total_spent' => $totalSpent,
        ]);
    }
}

