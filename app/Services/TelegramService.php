<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $apiBaseUrl = 'https://api.telegram.org/bot';

    /**
     * Получить информацию о боте
     */
    public function getBotInfo(string $token): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiBaseUrl . $token . '/getMe');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Неизвестная ошибка',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('Telegram getBotInfo error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Установить webhook
     */
    public function setWebhook(string $token, string $url, array $options = []): array
    {
        try {
            $params = array_merge([
                'url' => $url,
            ], $options);

            Log::info('📤 Sending setWebhook request to Telegram API', [
                'url' => $url,
                'options' => $options,
                'api_url' => $this->apiBaseUrl . $token . '/setWebhook',
            ]);

            $response = Http::timeout(10)->post($this->apiBaseUrl . $token . '/setWebhook', $params);
            
            Log::info('📥 Telegram API setWebhook response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    Log::info('✅ Webhook set successfully', [
                        'url' => $url,
                        'result' => $data['result'] ?? [],
                    ]);
                    return [
                        'success' => true,
                        'message' => 'Webhook успешно установлен',
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                Log::error('❌ Telegram API returned error', [
                    'url' => $url,
                    'description' => $data['description'] ?? 'Unknown error',
                    'error_code' => $data['error_code'] ?? null,
                ]);
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось установить webhook',
                ];
            }
            
            Log::error('❌ HTTP error when setting webhook', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('❌ Exception when setting webhook', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Получить информацию о webhook
     */
    public function getWebhookInfo(string $token): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiBaseUrl . $token . '/getWebhookInfo');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    $webhookInfo = $data['result'] ?? [];
                    
                    return [
                        'success' => true,
                        'data' => [
                            'url' => $webhookInfo['url'] ?? null,
                            'has_custom_certificate' => $webhookInfo['has_custom_certificate'] ?? false,
                            'pending_update_count' => $webhookInfo['pending_update_count'] ?? 0,
                            'last_error_date' => $webhookInfo['last_error_date'] ?? null,
                            'last_error_message' => $webhookInfo['last_error_message'] ?? null,
                            'max_connections' => $webhookInfo['max_connections'] ?? null,
                            'allowed_updates' => $webhookInfo['allowed_updates'] ?? [],
                        ],
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось получить информацию о webhook',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('Telegram getWebhookInfo error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Удалить webhook
     */
    public function deleteWebhook(string $token, bool $dropPendingUpdates = false): array
    {
        try {
            $params = [];
            if ($dropPendingUpdates) {
                $params['drop_pending_updates'] = true;
            }

            $response = Http::timeout(10)->post($this->apiBaseUrl . $token . '/deleteWebhook', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    return [
                        'success' => true,
                        'message' => 'Webhook успешно удален',
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось удалить webhook',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('Telegram deleteWebhook error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Отправить сообщение
     */
    public function sendMessage(string $token, int|string $chatId, string $text, array $options = []): array
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
            ], $options);

            Log::info('📤 Sending message via Telegram API', [
                'chat_id' => $chatId,
                'text_length' => strlen($text),
                'has_options' => !empty($options),
            ]);

            $response = Http::timeout(10)->post($this->apiBaseUrl . $token . '/sendMessage', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    Log::info('✅ Message sent successfully', [
                        'chat_id' => $chatId,
                        'message_id' => $data['result']['message_id'] ?? null,
                    ]);
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                Log::error('❌ Telegram API error', [
                    'chat_id' => $chatId,
                    'description' => $data['description'] ?? 'Unknown error',
                ]);
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось отправить сообщение',
                ];
            }
            
            Log::error('❌ HTTP error sending message', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('❌ Telegram sendMessage error: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Отправить фото
     */
    public function sendPhoto(string $token, int|string $chatId, string $photo, array $options = []): array
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'photo' => $photo,
            ], $options);

            Log::info('📤 Sending photo via Telegram API', [
                'chat_id' => $chatId,
                'has_options' => !empty($options),
            ]);

            $response = Http::timeout(10)->post($this->apiBaseUrl . $token . '/sendPhoto', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    Log::info('✅ Photo sent successfully', [
                        'chat_id' => $chatId,
                        'message_id' => $data['result']['message_id'] ?? null,
                    ]);
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                Log::error('❌ Telegram API error sending photo', [
                    'chat_id' => $chatId,
                    'description' => $data['description'] ?? 'Unknown error',
                ]);
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось отправить фото',
                ];
            }
            
            Log::error('❌ HTTP error sending photo', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('❌ Telegram sendPhoto error: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Отправить документ
     */
    public function sendDocument(string $token, int|string $chatId, string $document, array $options = []): array
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'document' => $document,
            ], $options);

            Log::info('📤 Sending document via Telegram API', [
                'chat_id' => $chatId,
                'has_options' => !empty($options),
            ]);

            $response = Http::timeout(30)->post($this->apiBaseUrl . $token . '/sendDocument', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    Log::info('✅ Document sent successfully', [
                        'chat_id' => $chatId,
                        'message_id' => $data['result']['message_id'] ?? null,
                    ]);
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                Log::error('❌ Telegram API error sending document', [
                    'chat_id' => $chatId,
                    'description' => $data['description'] ?? 'Unknown error',
                ]);
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось отправить документ',
                ];
            }
            
            Log::error('❌ HTTP error sending document', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('❌ Telegram sendDocument error: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Отправить группу медиа
     */
    public function sendMediaGroup(string $token, int|string $chatId, array $media, array $options = []): array
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'media' => json_encode($media),
            ], $options);

            Log::info('📤 Sending media group via Telegram API', [
                'chat_id' => $chatId,
                'media_count' => count($media),
                'has_options' => !empty($options),
            ]);

            $response = Http::timeout(30)->post($this->apiBaseUrl . $token . '/sendMediaGroup', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    Log::info('✅ Media group sent successfully', [
                        'chat_id' => $chatId,
                        'messages_count' => count($data['result'] ?? []),
                    ]);
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                Log::error('❌ Telegram API error sending media group', [
                    'chat_id' => $chatId,
                    'description' => $data['description'] ?? 'Unknown error',
                ]);
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось отправить группу медиа',
                ];
            }
            
            Log::error('❌ HTTP error sending media group', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('❌ Telegram sendMediaGroup error: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Отправить видео
     */
    public function sendVideo(string $token, int|string $chatId, string $video, array $options = []): array
    {
        try {
            $params = array_merge([
                'chat_id' => $chatId,
                'video' => $video,
            ], $options);

            Log::info('📤 Sending video via Telegram API', [
                'chat_id' => $chatId,
                'has_options' => !empty($options),
            ]);

            $response = Http::timeout(30)->post($this->apiBaseUrl . $token . '/sendVideo', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    Log::info('✅ Video sent successfully', [
                        'chat_id' => $chatId,
                        'message_id' => $data['result']['message_id'] ?? null,
                    ]);
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                Log::error('❌ Telegram API error sending video', [
                    'chat_id' => $chatId,
                    'description' => $data['description'] ?? 'Unknown error',
                ]);
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось отправить видео',
                ];
            }
            
            Log::error('❌ HTTP error sending video', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('❌ Telegram sendVideo error: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Получить информацию о чате
     */
    public function getChat(string $token, int|string $chatId): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiBaseUrl . $token . '/getChat', [
                'chat_id' => $chatId,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось получить информацию о чате',
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('Telegram getChat error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }
}

