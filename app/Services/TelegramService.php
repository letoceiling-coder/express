<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $apiBaseUrl = 'https://api.telegram.org/bot';

    /**
     * Фильтрует parse_mode, оставляя только поддерживаемые Telegram API значения
     * Telegram API поддерживает только: HTML, MarkdownV2
     */
    protected function filterParseMode(array $options): array
    {
        if (isset($options['parse_mode'])) {
            $parseMode = $options['parse_mode'];
            // Telegram API поддерживает только HTML и MarkdownV2
            // Также игнорируем пустые строки, null и другие невалидные значения
            if (empty($parseMode) || !in_array($parseMode, ['HTML', 'MarkdownV2'], true)) {
                Log::warning('⚠️ Invalid parse_mode filtered out', [
                    'parse_mode' => $parseMode,
                    'options' => $options,
                ]);
                unset($options['parse_mode']);
            }
        }
        return $options;
    }

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
        return $this->retryWithBackoff(function () use ($token, $chatId, $text, $options) {
            $filteredOptions = $this->filterParseMode($options);
            
            $params = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
            ], $filteredOptions);

            Log::info('📤 Sending message via Telegram API', [
                'chat_id' => $chatId,
                'text_length' => strlen($text),
                'has_options' => !empty($options),
                'parse_mode_before_filter' => $options['parse_mode'] ?? null,
                'parse_mode_after_filter' => $filteredOptions['parse_mode'] ?? null,
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
                
                $errorCode = $data['error_code'] ?? null;
                $description = $data['description'] ?? 'Unknown error';
                
                Log::error('❌ Telegram API error', [
                    'chat_id' => $chatId,
                    'description' => $description,
                    'error_code' => $errorCode,
                ]);
                
                return [
                    'success' => false,
                    'error_code' => $errorCode,
                    'message' => $description,
                ];
            }
            
            $errorBody = $response->body();
            Log::error('❌ HTTP error sending message', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $errorBody,
            ]);
            
            $errorData = $response->json();
            $errorMessage = $errorData['description'] ?? 'Ошибка подключения к Telegram API';
            
            return [
                'success' => false,
                'http_status' => $response->status(),
                'message' => $errorMessage,
            ];
        });
    }

    /**
     * Отправить фото
     */
    public function sendPhoto(string $token, int|string $chatId, string $photo, array $options = []): array
    {
        try {
            $filteredOptions = $this->filterParseMode($options);
            
            $params = array_merge([
                'chat_id' => $chatId,
                'photo' => $photo,
            ], $filteredOptions);

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
            
            $errorBody = $response->body();
            Log::error('❌ HTTP error sending photo', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $errorBody,
                'url' => $this->apiBaseUrl . $token . '/sendPhoto',
            ]);
            
            $errorData = $response->json();
            $errorMessage = $errorData['description'] ?? 'Ошибка подключения к Telegram API';
            
            return [
                'success' => false,
                'message' => $errorMessage . ' (HTTP ' . $response->status() . ')',
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
            $filteredOptions = $this->filterParseMode($options);
            
            $params = array_merge([
                'chat_id' => $chatId,
                'document' => $document,
            ], $filteredOptions);

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
            
            $errorBody = $response->body();
            Log::error('❌ HTTP error sending document', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $errorBody,
                'url' => $this->apiBaseUrl . $token . '/sendDocument',
            ]);
            
            $errorData = $response->json();
            $errorMessage = $errorData['description'] ?? 'Ошибка подключения к Telegram API';
            
            return [
                'success' => false,
                'message' => $errorMessage . ' (HTTP ' . $response->status() . ')',
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
            
            $errorBody = $response->body();
            Log::error('❌ HTTP error sending media group', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $errorBody,
                'url' => $this->apiBaseUrl . $token . '/sendMediaGroup',
            ]);
            
            $errorData = $response->json();
            $errorMessage = $errorData['description'] ?? 'Ошибка подключения к Telegram API';
            
            return [
                'success' => false,
                'message' => $errorMessage . ' (HTTP ' . $response->status() . ')',
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
            $filteredOptions = $this->filterParseMode($options);
            
            $params = array_merge([
                'chat_id' => $chatId,
                'video' => $video,
            ], $filteredOptions);

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
            
            $errorBody = $response->body();
            Log::error('❌ HTTP error sending video', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $errorBody,
                'url' => $this->apiBaseUrl . $token . '/sendVideo',
            ]);
            
            $errorData = $response->json();
            $errorMessage = $errorData['description'] ?? 'Ошибка подключения к Telegram API';
            
            return [
                'success' => false,
                'message' => $errorMessage . ' (HTTP ' . $response->status() . ')',
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

    /**
     * Ответить на callback query (убрать индикатор загрузки)
     *
     * @param string $token
     * @param string $callbackQueryId
     * @param string|null $text Текст для отображения пользователю
     * @param bool $showAlert Показывать ли alert вместо toast
     * @return array
     */
    public function answerCallbackQuery(string $token, string $callbackQueryId, ?string $text = null, bool $showAlert = false): array
    {
        try {
            $params = [
                'callback_query_id' => $callbackQueryId,
            ];

            if ($text !== null) {
                $params['text'] = $text;
            }

            if ($showAlert) {
                $params['show_alert'] = true;
            }

            $response = Http::timeout(10)->post($this->apiBaseUrl . $token . '/answerCallbackQuery', $params);

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
                    'message' => $data['description'] ?? 'Не удалось ответить на callback query',
                ];
            }

            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        } catch (\Exception $e) {
            Log::error('Telegram answerCallbackQuery error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Обновить текст сообщения
     *
     * @param string $token
     * @param int|string $chatId
     * @param int $messageId
     * @param string $text
     * @param array $options
     * @return array
     */
    public function editMessageText(string $token, int|string $chatId, int $messageId, string $text, array $options = []): array
    {
        return $this->retryWithBackoff(function () use ($token, $chatId, $messageId, $text, $options) {
            $filteredOptions = $this->filterParseMode($options);
            
            $params = array_merge([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
            ], $filteredOptions);

            Log::info('📝 Editing message via Telegram API', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text_length' => strlen($text),
            ]);

            $response = Http::timeout(10)->post($this->apiBaseUrl . $token . '/editMessageText', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    Log::info('✅ Message edited successfully', [
                        'chat_id' => $chatId,
                        'message_id' => $messageId,
                    ]);
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                // Проверка на специфичные ошибки
                $errorCode = $data['error_code'] ?? null;
                $description = $data['description'] ?? 'Unknown error';
                
                // Ошибка "message not found" или "message to edit not found"
                if (str_contains(strtolower($description), 'message not found') || 
                    str_contains(strtolower($description), 'message to edit not found')) {
                    Log::warning('⚠️ Message not found for editing', [
                        'chat_id' => $chatId,
                        'message_id' => $messageId,
                        'description' => $description,
                    ]);
                    return [
                        'success' => false,
                        'error_code' => 'MESSAGE_NOT_FOUND',
                        'message' => $description,
                    ];
                }
                
                Log::error('❌ Telegram API error editing message', [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'description' => $description,
                    'error_code' => $errorCode,
                ]);
                
                return [
                    'success' => false,
                    'error_code' => $errorCode,
                    'message' => $description,
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        });
    }

    /**
     * Удалить сообщение
     *
     * @param string $token
     * @param int|string $chatId
     * @param int $messageId
     * @return array
     */
    public function deleteMessage(string $token, int|string $chatId, int $messageId): array
    {
        return $this->retryWithBackoff(function () use ($token, $chatId, $messageId) {
            $params = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ];

            Log::info('🗑️ Deleting message via Telegram API', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);

            $response = Http::timeout(10)->post($this->apiBaseUrl . $token . '/deleteMessage', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    Log::info('✅ Message deleted successfully', [
                        'chat_id' => $chatId,
                        'message_id' => $messageId,
                    ]);
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                $description = $data['description'] ?? 'Unknown error';
                
                // Ошибка "message not found" - не критично, просто логируем
                if (str_contains(strtolower($description), 'message not found')) {
                    Log::warning('⚠️ Message not found for deletion', [
                        'chat_id' => $chatId,
                        'message_id' => $messageId,
                    ]);
                    return [
                        'success' => true, // Считаем успешным, так как цель достигнута
                        'message' => 'Message already deleted',
                    ];
                }
                
                Log::error('❌ Telegram API error deleting message', [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'description' => $description,
                ]);
                
                return [
                    'success' => false,
                    'message' => $description,
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Ошибка подключения к Telegram API',
            ];
        });
    }

    /**
     * Установить кнопку меню для чата (Menu Button)
     * 
     * @param string $token Токен бота
     * @param int|string $chatId ID чата
     * @param string $url URL Mini App
     * @param string|null $text Текст кнопки (опционально)
     * @return array
     */
    public function setChatMenuButton(string $token, int|string $chatId, string $url, ?string $text = null): array
    {
        return $this->retryWithBackoff(function () use ($token, $chatId, $url, $text) {
            $menuButton = [
                'type' => 'web_app',
                'text' => $text ?: 'Открыть приложение',
                'web_app' => [
                    'url' => $url,
                ],
            ];
            
            $params = [
                'chat_id' => $chatId,
                'menu_button' => $menuButton,
            ];
            
            Log::info('🔘 Setting chat menu button', [
                'chat_id' => $chatId,
                'url' => $url,
                'text' => $menuButton['text'],
                'payload' => $params,
            ]);
            
            $response = Http::timeout(10)->post($this->apiBaseUrl . $token . '/setChatMenuButton', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    Log::info('✅ Chat menu button set successfully', [
                        'chat_id' => $chatId,
                        'result' => $data['result'] ?? null,
                    ]);
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                $errorCode = $data['error_code'] ?? null;
                $description = $data['description'] ?? 'Unknown error';
                
                Log::error('❌ Telegram API error setting menu button', [
                    'chat_id' => $chatId,
                    'description' => $description,
                    'error_code' => $errorCode,
                    'response' => $data,
                ]);
                
                return [
                    'success' => false,
                    'error_code' => $errorCode,
                    'message' => $description,
                ];
            }
            
            $errorBody = $response->body();
            Log::error('❌ HTTP error setting menu button', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $errorBody,
            ]);
            
            return [
                'success' => false,
                'message' => 'HTTP error: ' . $response->status(),
            ];
        });
    }
    
    /**
     * Retry logic с экспоненциальной задержкой
     *
     * @param callable $callback
     * @param int $maxAttempts
     * @return array
     */
    protected function retryWithBackoff(callable $callback, int $maxAttempts = 3): array
    {
        $attempt = 0;
        $lastError = null;
        
        while ($attempt < $maxAttempts) {
            $attempt++;
            
            try {
                $result = $callback();
                
                // Если успешно, возвращаем результат
                if (isset($result['success']) && $result['success']) {
                    return $result;
                }
                
                // Проверяем, нужно ли повторять попытку
                $errorCode = $result['error_code'] ?? null;
                $message = $result['message'] ?? '';
                
                // Ошибки, которые не требуют повторной попытки
                $nonRetryableErrors = ['MESSAGE_NOT_FOUND', 'bad_request', 'unauthorized'];
                if ($errorCode && in_array($errorCode, $nonRetryableErrors)) {
                    return $result;
                }
                
                // Проверяем на ошибку 429 (Too Many Requests)
                if (str_contains(strtolower($message), 'too many requests') || 
                    str_contains(strtolower($message), 'retry after')) {
                    // Извлекаем retry_after из ответа (если есть)
                    $retryAfter = $this->extractRetryAfter($message);
                    
                    if ($retryAfter > 0 && $attempt < $maxAttempts) {
                        Log::warning('⚠️ Rate limit hit, waiting before retry', [
                            'attempt' => $attempt,
                            'retry_after' => $retryAfter,
                        ]);
                        sleep($retryAfter);
                        continue;
                    }
                }
                
                // Временные ошибки (500, 502, 503, 504)
                $temporaryErrors = [500, 502, 503, 504];
                if (isset($result['http_status']) && in_array($result['http_status'], $temporaryErrors)) {
                    if ($attempt < $maxAttempts) {
                        $delay = pow(2, $attempt - 1); // Экспоненциальная задержка: 1, 2, 4 секунды
                        Log::warning('⚠️ Temporary error, retrying', [
                            'attempt' => $attempt,
                            'delay' => $delay,
                            'http_status' => $result['http_status'],
                        ]);
                        sleep($delay);
                        continue;
                    }
                }
                
                // Если это последняя попытка или ошибка не временная, возвращаем результат
                if ($attempt >= $maxAttempts) {
                    return $result;
                }
                
                // Экспоненциальная задержка для других ошибок
                $delay = pow(2, $attempt - 1);
                Log::warning('⚠️ Retrying after error', [
                    'attempt' => $attempt,
                    'delay' => $delay,
                    'error' => $message,
                ]);
                sleep($delay);
                
            } catch (\Exception $e) {
                $lastError = $e;
                
                if ($attempt < $maxAttempts) {
                    $delay = pow(2, $attempt - 1);
                    Log::warning('⚠️ Exception caught, retrying', [
                        'attempt' => $attempt,
                        'delay' => $delay,
                        'error' => $e->getMessage(),
                    ]);
                    sleep($delay);
                    continue;
                }
                
                Log::error('❌ Max retry attempts reached', [
                    'attempts' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Ошибка после ' . $attempt . ' попыток: ' . $e->getMessage(),
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Не удалось выполнить операцию после ' . $maxAttempts . ' попыток',
        ];
    }

    /**
     * Извлечь retry_after из сообщения об ошибке
     *
     * @param string $message
     * @return int
     */
    protected function extractRetryAfter(string $message): int
    {
        // Пытаемся найти число в сообщении (обычно это секунды)
        if (preg_match('/retry after (\d+)/i', $message, $matches)) {
            return (int) $matches[1];
        }
        
        // По умолчанию возвращаем 1 секунду
        return 1;
    }

    /**
     * Отправить локацию (координаты)
     */
    public function sendLocation(string $token, int|string $chatId, float $latitude, float $longitude, array $options = []): array
    {
        return $this->retryWithBackoff(function () use ($token, $chatId, $latitude, $longitude, $options) {
            $params = array_merge([
                'chat_id' => $chatId,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ], $options);

            Log::info('📍 Sending location via Telegram API', [
                'chat_id' => $chatId,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            $response = Http::timeout(10)->post($this->apiBaseUrl . $token . '/sendLocation', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['ok'] ?? false) {
                    Log::info('✅ Location sent successfully', [
                        'chat_id' => $chatId,
                        'message_id' => $data['result']['message_id'] ?? null,
                    ]);
                    return [
                        'success' => true,
                        'data' => $data['result'] ?? [],
                    ];
                }
                
                Log::error('❌ Telegram API error sending location', [
                    'chat_id' => $chatId,
                    'description' => $data['description'] ?? 'Unknown error',
                ]);
                
                return [
                    'success' => false,
                    'message' => $data['description'] ?? 'Не удалось отправить локацию',
                ];
            }
            
            $errorData = $response->json();
            $errorMessage = $errorData['description'] ?? 'Ошибка подключения к Telegram API';
            
            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        });
    }
}

