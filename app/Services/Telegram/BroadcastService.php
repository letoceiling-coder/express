<?php

namespace App\Services\Telegram;

use App\Models\Bot;
use App\Models\TelegramUser;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для рассылок пользователям Telegram бота
 */
class BroadcastService
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Рассылка сообщения/медиа
     * 
     * @param int $botId
     * @param array|null $telegramUserIds Массив ID пользователей (null = всем)
     * @param string $type Тип: 'message', 'photo', 'document', 'media_group'
     * @param array $content Содержимое
     * @param array $options Дополнительные опции
     * @return array Результат рассылки с статистикой
     */
    public function sendBroadcast(
        int $botId,
        ?array $telegramUserIds,
        string $type,
        array $content,
        array $options = []
    ): array {
        $bot = Bot::findOrFail($botId);
        
        if (!$bot->is_active) {
            return [
                'success' => false,
                'message' => 'Бот неактивен',
            ];
        }

        // Получаем список пользователей
        $query = TelegramUser::where('bot_id', $botId)->active();
        
        if ($telegramUserIds !== null && !empty($telegramUserIds)) {
            $query->whereIn('telegram_id', $telegramUserIds);
        }

        $users = $query->get();
        $total = $users->count();
        $sent = 0;
        $failed = 0;
        $errors = [];

        Log::info('Starting broadcast', [
            'bot_id' => $botId,
            'type' => $type,
            'total_users' => $total,
        ]);

        foreach ($users as $user) {
            try {
                $result = $this->sendToUser($bot->token, $user->telegram_id, $type, $content, $options);
                
                if ($result['success']) {
                    $sent++;
                    $user->updateInteraction();
                } else {
                    $failed++;
                    $errors[] = [
                        'telegram_id' => $user->telegram_id,
                        'error' => $result['message'] ?? 'Unknown error',
                    ];

                    // Если пользователь заблокировал бота, помечаем его
                    if (isset($result['message']) && (
                        str_contains($result['message'], 'blocked') ||
                        str_contains($result['message'], 'chat not found') ||
                        str_contains($result['message'], '403')
                    )) {
                        $user->block();
                    }
                }

                // Задержка для избежания лимитов API (30 сообщений/сек)
                usleep(35000); // ~35ms = ~28 сообщений/сек
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'telegram_id' => $user->telegram_id,
                    'error' => $e->getMessage(),
                ];
                Log::error('Broadcast error for user', [
                    'telegram_id' => $user->telegram_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Broadcast completed', [
            'bot_id' => $botId,
            'type' => $type,
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
        ]);

        return [
            'success' => true,
            'data' => [
                'total' => $total,
                'sent' => $sent,
                'failed' => $failed,
                'errors' => $errors,
            ],
        ];
    }

    /**
     * Рассылка всем пользователям бота
     * 
     * @param int $botId
     * @param string $type
     * @param array $content
     * @param array $options
     * @return array
     */
    public function sendToAll(int $botId, string $type, array $content, array $options = []): array
    {
        return $this->sendBroadcast($botId, null, $type, $content, $options);
    }

    /**
     * Рассылка выбранным пользователям
     * 
     * @param int $botId
     * @param array $telegramUserIds
     * @param string $type
     * @param array $content
     * @param array $options
     * @return array
     */
    public function sendToSelected(int $botId, array $telegramUserIds, string $type, array $content, array $options = []): array
    {
        return $this->sendBroadcast($botId, $telegramUserIds, $type, $content, $options);
    }

    /**
     * Отправить сообщение/медиа конкретному пользователю
     * 
     * @param string $token
     * @param int $telegramId
     * @param string $type
     * @param array $content
     * @param array $options
     * @return array
     */
    protected function sendToUser(string $token, int $telegramId, string $type, array $content, array $options = []): array
    {
        return match ($type) {
            'message' => $this->telegramService->sendMessage(
                $token,
                $telegramId,
                $content['text'] ?? '',
                $options
            ),
            'photo' => $this->telegramService->sendPhoto(
                $token,
                $telegramId,
                $content['photo'] ?? '',
                array_merge($options, ['caption' => $content['caption'] ?? ''])
            ),
            'document' => $this->telegramService->sendDocument(
                $token,
                $telegramId,
                $content['document'] ?? '',
                array_merge($options, ['caption' => $content['caption'] ?? ''])
            ),
            'media_group' => $this->telegramService->sendMediaGroup(
                $token,
                $telegramId,
                $content['media'] ?? [],
                $options
            ),
            default => [
                'success' => false,
                'message' => "Неподдерживаемый тип: {$type}",
            ],
        };
    }
}

