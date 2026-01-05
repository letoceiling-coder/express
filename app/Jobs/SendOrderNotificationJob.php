<?php

namespace App\Jobs;

use App\Models\Bot;
use App\Models\OrderNotification;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // Задержка между попытками в секундах

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $botToken,
        public int $chatId,
        public string $text,
        public array $options = [],
        public ?int $orderId = null,
        public ?int $telegramUserId = null,
        public ?string $notificationType = null,
        public ?\DateTime $expiresAt = null
    ) {
        $this->onQueue('telegram-notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegramService): void
    {
        try {
            $result = $telegramService->sendMessage(
                $this->botToken,
                $this->chatId,
                $this->text,
                $this->options
            );

            if ($result['success'] ?? false) {
                $messageId = $result['data']['message_id'] ?? null;
                
                // Сохраняем уведомление в БД, если переданы необходимые параметры
                if ($this->orderId && $this->telegramUserId && $this->notificationType && $messageId) {
                    OrderNotification::create([
                        'order_id' => $this->orderId,
                        'telegram_user_id' => $this->telegramUserId,
                        'message_id' => $messageId,
                        'chat_id' => $this->chatId,
                        'notification_type' => $this->notificationType,
                        'status' => OrderNotification::STATUS_ACTIVE,
                        'expires_at' => $this->expiresAt,
                    ]);
                }

                Log::info('✅ Notification sent via queue', [
                    'chat_id' => $this->chatId,
                    'message_id' => $messageId,
                    'notification_type' => $this->notificationType,
                ]);
            } else {
                Log::error('❌ Failed to send notification via queue', [
                    'chat_id' => $this->chatId,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
                
                // Повторяем попытку, если это не критичная ошибка
                if (!$this->isNonRetryableError($result)) {
                    throw new \Exception($result['message'] ?? 'Failed to send notification');
                }
            }
        } catch (\Exception $e) {
            Log::error('❌ Exception in SendOrderNotificationJob', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Проверить, является ли ошибка не требующей повторной попытки
     */
    protected function isNonRetryableError(array $result): bool
    {
        $errorCode = $result['error_code'] ?? null;
        $message = $result['message'] ?? '';
        
        $nonRetryableCodes = ['bad_request', 'unauthorized', 'forbidden', 'chat_not_found'];
        
        if ($errorCode && in_array($errorCode, $nonRetryableCodes)) {
            return true;
        }
        
        $nonRetryableMessages = [
            'chat not found',
            'user not found',
            'bot was blocked',
            'unauthorized',
        ];
        
        foreach ($nonRetryableMessages as $nonRetryableMessage) {
            if (stripos($message, $nonRetryableMessage) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

