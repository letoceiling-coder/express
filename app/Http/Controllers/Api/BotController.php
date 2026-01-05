<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Order;
use App\Models\OrderNotification;
use App\Models\TelegramUser;
use App\Models\TelegramUserRoleRequest;
use App\Services\TelegramService;
use App\Services\Order\OrderStatusService;
use App\Services\Order\OrderNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BotController extends Controller
{
    protected TelegramService $telegramService;
    protected \App\Services\Telegram\TelegramUserService $telegramUserService;
    protected OrderStatusService $orderStatusService;
    protected OrderNotificationService $orderNotificationService;

    public function __construct(
        TelegramService $telegramService,
        \App\Services\Telegram\TelegramUserService $telegramUserService,
        OrderStatusService $orderStatusService,
        OrderNotificationService $orderNotificationService
    ) {
        $this->telegramService = $telegramService;
        $this->telegramUserService = $telegramUserService;
        $this->orderStatusService = $orderStatusService;
        $this->orderNotificationService = $orderNotificationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $bots = Bot::orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $bots,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Получаем информацию о боте из Telegram
            $botInfo = $this->telegramService->getBotInfo($request->token);
            
            if (!$botInfo['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $botInfo['message'] ?? 'Не удалось получить информацию о боте',
                ], 400);
            }

            // Формируем настройки бота
            $settings = $request->settings ?? [];
            if ($request->has('webhook')) {
                $allowedUpdates = $request->input('webhook.allowed_updates');
                if (is_string($allowedUpdates)) {
                    $allowedUpdates = array_map('trim', explode(',', $allowedUpdates));
                }
                
                $settings['webhook'] = [
                    'allowed_updates' => $allowedUpdates ?: config('telegram.webhook.allowed_updates', ['message', 'callback_query']),
                    'max_connections' => $request->input('webhook.max_connections', config('telegram.webhook.max_connections', 40)),
                ];
                if ($request->has('webhook.secret_token') && $request->input('webhook.secret_token')) {
                    $settings['webhook']['secret_token'] = $request->input('webhook.secret_token');
                }
            }

            // Создаем бота сначала без webhook URL
            $bot = Bot::create([
                'name' => $request->name,
                'token' => $request->token,
                'username' => $botInfo['data']['username'] ?? null,
                'webhook_url' => null, // Будет установлен после создания
                'webhook_registered' => false,
                'welcome_message' => $request->welcome_message ?? null,
                'settings' => $settings,
                'is_active' => true,
            ]);

            // Теперь формируем правильный webhook URL с ID бота
            $webhookUrl = url('/api/telegram/webhook/' . $bot->id);
            
            // Настройки webhook
            $webhookOptions = [
                'allowed_updates' => $settings['webhook']['allowed_updates'] ?? config('telegram.webhook.allowed_updates', ['message', 'callback_query']),
                'max_connections' => $settings['webhook']['max_connections'] ?? config('telegram.webhook.max_connections', 40),
            ];
            
            if (isset($settings['webhook']['secret_token'])) {
                $webhookOptions['secret_token'] = $settings['webhook']['secret_token'];
            }

            // Регистрируем webhook с правильным URL
            $webhookResult = $this->telegramService->setWebhook($bot->token, $webhookUrl, $webhookOptions);
            
            // Обновляем бота с правильным webhook URL
            $bot->webhook_url = $webhookUrl;
            $bot->webhook_registered = $webhookResult['success'] ?? false;
            $bot->save();

            return response()->json([
                'success' => true,
                'message' => 'Бот успешно зарегистрирован',
                'data' => $bot,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании бота: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $bot = Bot::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $bot,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $bot = Bot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'token' => 'sometimes|required|string',
            'welcome_message' => 'nullable|string',
            'settings' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Если изменился токен, обновляем информацию о боте
            if ($request->has('token') && $request->token !== $bot->token) {
                $botInfo = $this->telegramService->getBotInfo($request->token);
                
                if (!$botInfo['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $botInfo['message'] ?? 'Не удалось получить информацию о боте',
                    ], 400);
                }

                // Обновляем webhook URL с ID бота
                $webhookUrl = url('/api/telegram/webhook/' . $bot->id);
                
                // Настройки webhook из запроса или дефолтные
                $allowedUpdates = $request->input('webhook.allowed_updates');
                if (is_string($allowedUpdates)) {
                    $allowedUpdates = array_map('trim', explode(',', $allowedUpdates));
                }
                
                $webhookOptions = [
                    'allowed_updates' => $allowedUpdates ?: config('telegram.webhook.allowed_updates', ['message', 'callback_query']),
                    'max_connections' => $request->input('webhook.max_connections', config('telegram.webhook.max_connections', 40)),
                ];

                if ($request->has('webhook.secret_token') && $request->input('webhook.secret_token')) {
                    $webhookOptions['secret_token'] = $request->input('webhook.secret_token');
                }

                $webhookResult = $this->telegramService->setWebhook($request->token, $webhookUrl, $webhookOptions);

                $bot->webhook_url = $webhookUrl;
                $bot->webhook_registered = $webhookResult['success'] ?? false;
                $bot->username = $botInfo['data']['username'] ?? null;
            }

            $bot->update($request->only([
                'name',
                'token',
                'welcome_message',
                'settings',
                'is_active',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Бот успешно обновлен',
                'data' => $bot->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении бота: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $bot = Bot::findOrFail($id);
        
        try {
            // Удаляем webhook перед удалением бота
            $this->telegramService->deleteWebhook($bot->token);
            
            $bot->delete();

            return response()->json([
                'success' => true,
                'message' => 'Бот успешно удален',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении бота: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Проверить установку webhook
     */
    public function checkWebhook(string $id): JsonResponse
    {
        $bot = Bot::findOrFail($id);
        
        try {
            $result = $this->telegramService->getWebhookInfo($bot->token);
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при проверке webhook: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обработка webhook от Telegram
     */
    public function handleWebhook(Request $request, string $id): JsonResponse
    {
        \Illuminate\Support\Facades\Log::info('🔔 Webhook request received', [
            'bot_id' => $id,
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'raw_body' => $request->getContent(),
        ]);

        try {
            $bot = Bot::findOrFail($id);
            
            \Illuminate\Support\Facades\Log::info('✅ Bot found', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'bot_username' => $bot->username,
                'is_active' => $bot->is_active,
            ]);
            
            // Проверяем secret_token, если он установлен
            if (!empty($bot->settings['webhook']['secret_token'])) {
                $secretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
                if ($secretToken !== $bot->settings['webhook']['secret_token']) {
                    \Illuminate\Support\Facades\Log::warning('❌ Webhook secret token mismatch', [
                        'bot_id' => $bot->id,
                        'received_token' => $secretToken ? 'present' : 'missing',
                        'expected_token' => 'present',
                    ]);
                    return response()->json(['error' => 'Invalid secret token'], 403);
                }
                \Illuminate\Support\Facades\Log::info('✅ Secret token verified');
            }
            
            // Получаем обновление от Telegram
            $update = $request->all();
            
            \Illuminate\Support\Facades\Log::info('📨 Telegram update received', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'update_id' => $update['update_id'] ?? null,
                'message_type' => $this->getUpdateType($update),
                'update' => $update,
            ]);
            
            // Обработка сообщений
            if (isset($update['message'])) {
                $message = $update['message'];
                $chatId = $message['chat']['id'] ?? null;
                $text = $message['text'] ?? null;
                $from = $message['from'] ?? null;
                
                \Illuminate\Support\Facades\Log::info('💬 Message received', [
                    'bot_id' => $bot->id,
                    'chat_id' => $chatId,
                    'text' => $text,
                    'from' => $from,
                ]);
                
                // Синхронизация пользователя
                if ($from) {
                    try {
                        $this->telegramUserService->syncUser($bot->id, $from);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Error syncing telegram user', [
                            'bot_id' => $bot->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // Обработка команды /start
                if ($text === '/start' || str_starts_with($text, '/start')) {
                    \Illuminate\Support\Facades\Log::info('🚀 /start command received', [
                        'bot_id' => $bot->id,
                        'chat_id' => $chatId,
                    ]);
                    
                    // Получаем URL для miniApp (из настроек бота или конфига)
                    $miniAppUrl = $bot->settings['mini_app_url'] ?? config('telegram.mini_app_url', env('APP_URL'));
                    
                    // Формируем клавиатуру с кнопкой для запуска miniApp
                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '🚀 Открыть приложение',
                                    'web_app' => [
                                        'url' => $miniAppUrl
                                    ]
                                ]
                            ]
                        ]
                    ];
                    
                    // Отправляем приветственное сообщение
                    if ($bot->welcome_message) {
                        $this->telegramService->sendMessage(
                            $bot->token,
                            $chatId,
                            $bot->welcome_message,
                            [
                                'reply_markup' => json_encode($keyboard)
                            ]
                        );
                        \Illuminate\Support\Facades\Log::info('✅ Welcome message sent with miniApp button', [
                            'bot_id' => $bot->id,
                            'chat_id' => $chatId,
                            'mini_app_url' => $miniAppUrl,
                        ]);
                    } else {
                        // Если нет приветственного сообщения, отправляем стандартное с кнопкой
                        $defaultMessage = '👋 Добро пожаловать! Нажмите на кнопку ниже, чтобы открыть приложение.';
                        $this->telegramService->sendMessage(
                            $bot->token,
                            $chatId,
                            $defaultMessage,
                            [
                                'reply_markup' => json_encode($keyboard)
                            ]
                        );
                        \Illuminate\Support\Facades\Log::info('✅ Default welcome message sent with miniApp button', [
                            'bot_id' => $bot->id,
                            'chat_id' => $chatId,
                            'mini_app_url' => $miniAppUrl,
                        ]);
                    }
                }
                
                // Обработка команды /apply_courier
                if ($text === '/apply_courier' || str_starts_with($text, '/apply_courier')) {
                    $this->handleRoleRequest($bot, $chatId, $from, 'courier');
                }
                
                // Обработка команды /apply_admin
                if ($text === '/apply_admin' || str_starts_with($text, '/apply_admin')) {
                    $this->handleRoleRequest($bot, $chatId, $from, 'admin');
                }
                
                // Обработка команды /apply_kitchen
                if ($text === '/apply_kitchen' || str_starts_with($text, '/apply_kitchen')) {
                    $this->handleRoleRequest($bot, $chatId, $from, 'kitchen');
                }

                // Обработка текстовых сообщений (не команд) для причин отмены
                if ($text && !str_starts_with($text, '/')) {
                    // Проверяем наличие временного состояния для отмены заказа
                    $this->handleTextMessageForCancelReason($bot, $chatId, $text, $from);
                }
            }

            // Обработка callback_query
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query'], $bot);
            }
            
            return response()->json(['ok' => true], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Illuminate\Support\Facades\Log::error('❌ Bot not found', [
                'bot_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Bot not found'], 404);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ Webhook processing error', [
                'bot_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Определить тип обновления
     */
    private function getUpdateType(array $update): string
    {
        if (isset($update['message'])) return 'message';
        if (isset($update['edited_message'])) return 'edited_message';
        if (isset($update['channel_post'])) return 'channel_post';
        if (isset($update['edited_channel_post'])) return 'edited_channel_post';
        if (isset($update['callback_query'])) return 'callback_query';
        if (isset($update['inline_query'])) return 'inline_query';
        if (isset($update['chosen_inline_result'])) return 'chosen_inline_result';
        if (isset($update['shipping_query'])) return 'shipping_query';
        if (isset($update['pre_checkout_query'])) return 'pre_checkout_query';
        if (isset($update['poll'])) return 'poll';
        if (isset($update['poll_answer'])) return 'poll_answer';
        if (isset($update['my_chat_member'])) return 'my_chat_member';
        if (isset($update['chat_member'])) return 'chat_member';
        if (isset($update['chat_join_request'])) return 'chat_join_request';
        return 'unknown';
    }

    /**
     * Зарегистрировать webhook
     */
    public function registerWebhook(Request $request, string $id): JsonResponse
    {
        $bot = Bot::findOrFail($id);
        
        try {
            // Всегда используем правильный URL с ID бота, игнорируя сохраненный в БД
            $webhookUrl = url('/api/telegram/webhook/' . $bot->id);
            
            \Illuminate\Support\Facades\Log::info('🔧 Registering webhook', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'webhook_url' => $webhookUrl,
                'current_webhook_url' => $bot->webhook_url,
            ]);
            
            // Настройки webhook из запроса или из настроек бота
            $settings = $bot->settings ?? [];
            $allowedUpdates = $request->input('allowed_updates');
            if (!$allowedUpdates && isset($settings['webhook']['allowed_updates'])) {
                $allowedUpdates = $settings['webhook']['allowed_updates'];
            }
            if (is_string($allowedUpdates)) {
                $allowedUpdates = array_map('trim', explode(',', $allowedUpdates));
            }
            
            $webhookOptions = [
                'allowed_updates' => $allowedUpdates ?: config('telegram.webhook.allowed_updates', ['message', 'callback_query']),
                'max_connections' => $request->input('max_connections', $settings['webhook']['max_connections'] ?? config('telegram.webhook.max_connections', 40)),
            ];

            $secretToken = $request->input('secret_token', $settings['webhook']['secret_token'] ?? null);
            if ($secretToken) {
                $webhookOptions['secret_token'] = $secretToken;
            }
            
            \Illuminate\Support\Facades\Log::info('📤 Sending webhook registration to Telegram', [
                'bot_id' => $bot->id,
                'webhook_url' => $webhookUrl,
                'options' => $webhookOptions,
            ]);
            
            $result = $this->telegramService->setWebhook($bot->token, $webhookUrl, $webhookOptions);
            
            \Illuminate\Support\Facades\Log::info('📥 Telegram API response', [
                'bot_id' => $bot->id,
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? null,
                'data' => $result['data'] ?? null,
            ]);
            
            if ($result['success']) {
                $bot->update([
                    'webhook_url' => $webhookUrl,
                    'webhook_registered' => true,
                ]);
                \Illuminate\Support\Facades\Log::info('✅ Webhook registered successfully', [
                    'bot_id' => $bot->id,
                    'webhook_url' => $webhookUrl,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::error('❌ Failed to register webhook', [
                    'bot_id' => $bot->id,
                    'webhook_url' => $webhookUrl,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
            }
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'] ?? ($result['success'] ? 'Webhook успешно зарегистрирован' : 'Ошибка регистрации webhook'),
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ Exception during webhook registration', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при регистрации webhook: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обработка заявки на роль (курьер или администратор)
     */
    private function handleRoleRequest(Bot $bot, int $chatId, array $from, string $requestedRole): void
    {
        try {
            // Синхронизируем пользователя
            $telegramUser = $this->telegramUserService->syncUser($bot->id, $from);
            
            // Проверяем, не подал ли пользователь уже активную заявку
            $existingRequest = TelegramUserRoleRequest::where('telegram_user_id', $telegramUser->id)
                ->where('requested_role', $requestedRole)
                ->where('status', TelegramUserRoleRequest::STATUS_PENDING)
                ->first();
            
            if ($existingRequest) {
                $roleName = match($requestedRole) {
                    'courier' => 'курьера',
                    'admin' => 'администратора',
                    'kitchen' => 'кухни',
                    default => $requestedRole,
                };
                $message = "⏳ Вы уже подали заявку на роль {$roleName}. Ожидайте рассмотрения.";
                $this->telegramService->sendMessage($bot->token, $chatId, $message);
                return;
            }
            
            // Проверяем, не имеет ли пользователь уже эту роль
            if ($telegramUser->role === $requestedRole) {
                $roleName = match($requestedRole) {
                    'courier' => 'курьером',
                    'admin' => 'администратором',
                    'kitchen' => 'кухней',
                    default => $requestedRole,
                };
                $message = "✅ Вы уже являетесь {$roleName}.";
                $this->telegramService->sendMessage($bot->token, $chatId, $message);
                return;
            }
            
            // Создаем заявку
            TelegramUserRoleRequest::create([
                'telegram_user_id' => $telegramUser->id,
                'requested_role' => $requestedRole,
                'status' => TelegramUserRoleRequest::STATUS_PENDING,
            ]);
            
            $roleName = match($requestedRole) {
                'courier' => 'курьера',
                'admin' => 'администратора',
                'kitchen' => 'кухни',
                default => $requestedRole,
            };
            $message = "✅ Заявка на роль {$roleName} успешно подана! Администратор рассмотрит вашу заявку в ближайшее время.";
            $this->telegramService->sendMessage($bot->token, $chatId, $message);
            
            \Illuminate\Support\Facades\Log::info('Role request created', [
                'telegram_user_id' => $telegramUser->id,
                'requested_role' => $requestedRole,
                'bot_id' => $bot->id,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling role request: ' . $e->getMessage(), [
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'requested_role' => $requestedRole,
                'error' => $e->getMessage(),
            ]);
            
            $this->telegramService->sendMessage(
                $bot->token, 
                $chatId, 
                '❌ Произошла ошибка при обработке заявки. Попробуйте позже.'
            );
        }
    }

    /**
     * Обработка callback_query от Telegram
     *
     * @param array $callbackQuery
     * @param Bot $bot
     * @return void
     */
    private function handleCallbackQuery(array $callbackQuery, Bot $bot): void
    {
        try {
            $callbackQueryId = $callbackQuery['id'] ?? null;
            $from = $callbackQuery['from'] ?? null;
            $data = $callbackQuery['data'] ?? null;

            if (!$callbackQueryId || !$data) {
                \Illuminate\Support\Facades\Log::warning('Invalid callback_query', [
                    'bot_id' => $bot->id,
                    'callback_query' => $callbackQuery,
                ]);
                return;
            }

            // Синхронизируем пользователя
            if ($from) {
                try {
                    $this->telegramUserService->syncUser($bot->id, $from);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error syncing telegram user in callback', [
                        'bot_id' => $bot->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Парсим callback_data
            $parts = explode(':', $data);
            $action = $parts[0] ?? null;
            $orderId = $parts[1] ?? null;
            $param = $parts[2] ?? null;

            \Illuminate\Support\Facades\Log::info('Callback query received', [
                'bot_id' => $bot->id,
                'action' => $action,
                'order_id' => $orderId,
                'param' => $param,
                'from_id' => $from['id'] ?? null,
            ]);

            // Отвечаем на callback (убираем индикатор загрузки)
            $this->telegramService->answerCallbackQuery($bot->token, $callbackQueryId);

            // Обрабатываем действие
            switch ($action) {
                case 'order_admin_action':
                    $this->handleAdminAction($bot, $orderId, $param, $from);
                    break;

                case 'order_action':
                    $this->handleOrderAction($bot, $orderId, $param, $from);
                    break;

                case 'order_kitchen_accept':
                    $this->handleKitchenAccept($bot, $orderId, $from);
                    break;

                case 'order_kitchen_ready':
                    $this->handleKitchenReady($bot, $orderId, $from);
                    break;

                case 'order_courier_assign':
                    $this->handleCourierAssign($bot, $orderId, $param, $from);
                    break;

                case 'order_courier_picked':
                    $this->handleCourierPicked($bot, $orderId, $from);
                    break;

                case 'order_courier_delivered':
                    $this->handleCourierDelivered($bot, $orderId, $from);
                    break;

                case 'order_payment':
                    $this->handleOrderPayment($bot, $orderId, $param, $from);
                    break;

                case 'order_cancel_request':
                    $this->handleOrderCancelRequest($bot, $orderId, $from);
                    break;

                default:
                    \Illuminate\Support\Facades\Log::warning('Unknown callback action', [
                        'action' => $action,
                        'data' => $data,
                    ]);
                    break;
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling callback query: ' . $e->getMessage(), [
                'bot_id' => $bot->id,
                'callback_query' => $callbackQuery,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Проверить, может ли пользователь изменить заказ
     *
     * @param TelegramUser $user
     * @param Order $order
     * @param string $action
     * @return bool
     */
    private function checkUserCanModifyOrder(TelegramUser $user, Order $order, string $action): bool
    {
        // Проверяем, что пользователь имеет доступ к боту заказа
        if ($user->bot_id !== $order->bot_id) {
            \Illuminate\Support\Facades\Log::warning('User bot mismatch', [
                'user_bot_id' => $user->bot_id,
                'order_bot_id' => $order->bot_id,
            ]);
            return false;
        }

        // Проверяем права в зависимости от роли и действия
        switch ($user->role) {
            case TelegramUser::ROLE_ADMIN:
                // Администратор может выполнять любые действия
                return true;

            case TelegramUser::ROLE_KITCHEN:
                // Кухня может принимать заказы и отмечать готовность
                return in_array($action, ['accept', 'ready']);

            case TelegramUser::ROLE_COURIER:
                // Курьер может принимать заказы, забирать и доставлять
                // Проверяем, что курьер назначен на заказ
                if ($action === 'picked' || $action === 'delivered' || $action === 'payment') {
                    return $order->courier_id === $user->id;
                }
                return $action === 'accept';

            default:
                // Обычный пользователь может только отменять свои заказы
                if ($action === 'cancel') {
                    return $order->telegram_id === $user->telegram_id;
                }
                return false;
        }
    }

    /**
     * Обработка действий администратора (Принять/Отменить заказ)
     */
    private function handleAdminAction(Bot $bot, string $orderId, string $action, array $from): void
    {
        try {
            $order = Order::where('id', $orderId)->where('bot_id', $bot->id)->first();
            if (!$order) {
                \Illuminate\Support\Facades\Log::warning('Order not found for admin action', [
                    'order_id' => $orderId,
                    'bot_id' => $bot->id,
                ]);
                return;
            }

            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            if (!$telegramUser) {
                \Illuminate\Support\Facades\Log::warning('Telegram user not found for admin action', [
                    'telegram_id' => $from['id'] ?? null,
                    'bot_id' => $bot->id,
                ]);
                return;
            }

            // Проверка прав доступа
            if (!$this->checkUserCanModifyOrder($telegramUser, $order, $action)) {
                \Illuminate\Support\Facades\Log::warning('User cannot modify order', [
                    'telegram_user_id' => $telegramUser->id,
                    'role' => $telegramUser->role,
                    'action' => $action,
                    'order_id' => $order->id,
                ]);
                return;
            }

            switch ($action) {
                case 'accept':
                    $this->handleAdminAcceptOrder($bot, $order, $telegramUser);
                    break;
                case 'cancel':
                    $this->handleAdminCancelOrder($bot, $order, $telegramUser);
                    break;
                default:
                    \Illuminate\Support\Facades\Log::warning('Unknown admin action', [
                        'action' => $action,
                        'order_id' => $orderId,
                    ]);
                    break;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling admin action: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка принятия заказа администратором
     */
    private function handleAdminAcceptOrder(Bot $bot, Order $order, TelegramUser $adminUser): void
    {
        try {
            // Проверяем, что заказ в статусе 'new'
            if ($order->status !== Order::STATUS_NEW) {
                \Illuminate\Support\Facades\Log::warning('Order status not suitable for acceptance', [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                ]);
                return;
            }

            // Используем транзакцию для атомарного изменения статуса
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $adminUser, $bot) {
                // Блокируем заказ для чтения/изменения
                $order = Order::where('id', $order->id)->lockForUpdate()->first();
                
                // Повторная проверка статуса
                if ($order->status !== Order::STATUS_NEW) {
                    throw new \Exception('Order status changed during processing');
                }

                // Изменяем статус заказа на 'accepted'
                $this->orderStatusService->changeStatus($order, Order::STATUS_ACCEPTED, [
                    'role' => 'admin',
                    'changed_by_telegram_user_id' => $adminUser->id,
                    'comment' => 'Заказ принят администратором',
                ]);

                // Обновляем заказ из БД
                $order->refresh();

                // Получаем кэшированные списки пользователей
                $hasKitchen = $this->orderNotificationService->getCachedKitchenUsers($bot->id)->isNotEmpty();
                $hasCourier = $this->orderNotificationService->getCachedCouriers($bot->id)->isNotEmpty();

                // Формируем новые кнопки для администратора
                $keyboard = ['inline_keyboard' => []];
                $row = [];

                if ($hasKitchen) {
                    $row[] = [
                        'text' => '👨‍🍳 Отправить на кухню',
                        'callback_data' => "order_action:{$order->id}:send_to_kitchen"
                    ];
                }

                if ($hasCourier) {
                    $row[] = [
                        'text' => '🚚 Вызвать курьера',
                        'callback_data' => "order_action:{$order->id}:call_courier"
                    ];
                }

                if ($order->payment_status === Order::PAYMENT_STATUS_PENDING) {
                    $row[] = [
                        'text' => '💳 Счет на оплату',
                        'callback_data' => "order_action:{$order->id}:send_invoice"
                    ];
                }

                if (!empty($row)) {
                    $keyboard['inline_keyboard'][] = $row;
                }

                // Обновляем сообщение администратору
                $notification = \App\Models\OrderNotification::where('order_id', $order->id)
                    ->where('telegram_user_id', $adminUser->id)
                    ->where('notification_type', \App\Models\OrderNotification::TYPE_ADMIN_NEW)
                    ->where('status', \App\Models\OrderNotification::STATUS_ACTIVE)
                    ->first();

                if ($notification) {
                    // Используем рефлексию для доступа к protected методу или создаем публичный метод
                    $order->load('items');
                    $message = "🆕 Заказ #{$order->order_id}\n\n";
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
                    $message .= "\n\n✅ Статус: Принят";
                    
                    $this->telegramService->editMessageText(
                        $bot->token,
                        $notification->chat_id,
                        $notification->message_id,
                        $message,
                        ['reply_markup' => json_encode($keyboard)]
                    );
                    
                    $notification->markAsUpdated();
                }

                // Уведомляем клиента об изменении статуса
                $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_ACCEPTED);

                \Illuminate\Support\Facades\Log::info('Order accepted by admin', [
                    'order_id' => $order->id,
                    'admin_id' => $adminUser->id,
                ]);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error accepting order by admin: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'admin_id' => $adminUser->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка отмены заказа администратором
     */
    private function handleAdminCancelOrder(Bot $bot, Order $order, TelegramUser $adminUser): void
    {
        try {
            // Сохраняем временное состояние в cache для ожидания причины
            $cacheKey = "admin_cancel_order:{$bot->id}:{$adminUser->telegram_id}";
            \Illuminate\Support\Facades\Cache::put($cacheKey, [
                'order_id' => $order->id,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ], now()->addMinutes(10));

            // Отправляем запрос на ввод причины отмены
            $message = "❓ Укажите причину отмены заказа #{$order->order_id}:\n\n" .
                      "Напишите текст сообщения с причиной отмены.";
            
            $this->telegramService->sendMessage($bot->token, $adminUser->telegram_id, $message);

            \Illuminate\Support\Facades\Log::info('Admin cancel order request received, waiting for reason', [
                'order_id' => $order->id,
                'admin_id' => $adminUser->id,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling admin cancel order: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'admin_id' => $adminUser->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обработка действий администратора с заказом
     */
    private function handleOrderAction(Bot $bot, string $orderId, string $action, array $from): void
    {
        try {
            $order = Order::where('id', $orderId)->where('bot_id', $bot->id)->first();
            if (!$order) {
                return;
            }

            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            if (!$telegramUser || $telegramUser->role !== TelegramUser::ROLE_ADMIN) {
                return;
            }

            switch ($action) {
                case 'send_to_kitchen':
                    $this->handleSendToKitchen($bot, $order, $telegramUser);
                    break;
                case 'call_courier':
                    $this->handleCallCourier($bot, $order, $telegramUser);
                    break;
                case 'send_invoice':
                    $this->handleSendInvoice($bot, $order, $telegramUser);
                    break;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling order action: ' . $e->getMessage());
        }
    }

    /**
     * Обработка отправки счета на оплату
     */
    private function handleSendInvoice(Bot $bot, Order $order, TelegramUser $adminUser): void
    {
        try {
            // Проверяем, что заказ не оплачен
            if ($order->payment_status === Order::PAYMENT_STATUS_SUCCEEDED) {
                $this->telegramService->sendMessage(
                    $bot->token,
                    $adminUser->telegram_id,
                    "✅ Заказ #{$order->order_id} уже оплачен"
                );
                return;
            }

            // Формируем сообщение со счетом
            $order->load('items');
            $message = "💳 Счет на оплату\n\n";
            $message .= "Заказ #{$order->order_id}\n";
            $message .= "💰 Сумма: " . number_format($order->total_amount, 2, '.', ' ') . " ₽\n\n";
            $message .= "📦 Товары:\n";
            foreach ($order->items as $item) {
                $itemTotal = $item->quantity * $item->unit_price;
                $message .= "• {$item->product_name} × {$item->quantity} = " . number_format($itemTotal, 2, '.', ' ') . " ₽\n";
            }
            $message .= "\n💬 Комментарий: " . ($order->comment ?: 'Без комментария');

            // Отправляем клиенту
            $this->telegramService->sendMessage(
                $bot->token,
                $order->telegram_id,
                $message
            );

            // Подтверждаем администратору
            $this->telegramService->sendMessage(
                $bot->token,
                $adminUser->telegram_id,
                "✅ Счет на оплату отправлен клиенту для заказа #{$order->order_id}"
            );

            \Illuminate\Support\Facades\Log::info('Invoice sent to client', [
                'order_id' => $order->id,
                'admin_id' => $adminUser->id,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error sending invoice: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обработка отправки заказа на кухню
     */
    private function handleSendToKitchen(Bot $bot, Order $order, TelegramUser $adminUser): void
    {
        try {
            // Проверяем, что заказ в правильном статусе
            if (!in_array($order->status, [Order::STATUS_NEW, Order::STATUS_ACCEPTED])) {
                \Illuminate\Support\Facades\Log::warning('Order status not suitable for sending to kitchen', [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                ]);
                return;
            }

            // Проверяем наличие пользователей с ролью кухни (из кэша)
            $kitchenUsers = $this->orderNotificationService->getCachedKitchenUsers($bot->id);

            if ($kitchenUsers->isEmpty()) {
                $this->telegramService->sendMessage(
                    $bot->token,
                    $adminUser->telegram_id,
                    '❌ Нет доступных пользователей с ролью "Кухня". Создайте пользователя через команду /apply_kitchen'
                );
                \Illuminate\Support\Facades\Log::warning('No kitchen users found', [
                    'order_id' => $order->id,
                    'bot_id' => $bot->id,
                ]);
                return;
            }

            // Используем транзакцию для атомарного изменения статуса
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $adminUser) {
                // Блокируем заказ для чтения/изменения
                $order = Order::where('id', $order->id)->lockForUpdate()->first();
                
                // Повторная проверка статуса
                if (!in_array($order->status, [Order::STATUS_NEW, Order::STATUS_ACCEPTED])) {
                    throw new \Exception('Order status changed during processing');
                }

                // Изменяем статус заказа
                $statusChanged = $this->orderStatusService->changeStatus($order, Order::STATUS_SENT_TO_KITCHEN, [
                    'role' => 'admin',
                    'changed_by_telegram_user_id' => $adminUser->id,
                ]);

                if (!$statusChanged) {
                    throw new \Exception('Failed to change order status to sent_to_kitchen');
                }

                // Обновляем заказ из БД
                $order->refresh();

                // Увеличиваем version
                $order->increment('version');
            });

            // Уведомления отправляем после транзакции
            $order->refresh();
            
            \Illuminate\Support\Facades\Log::info('Sending notifications after order sent to kitchen', [
                'order_id' => $order->id,
                'order_status' => $order->status,
            ]);
            
            $kitchenNotified = $this->orderNotificationService->notifyKitchenOrderSent($order);
            
            \Illuminate\Support\Facades\Log::info('Kitchen notification result', [
                'order_id' => $order->id,
                'kitchen_notified' => $kitchenNotified,
            ]);
            
            $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_SENT_TO_KITCHEN, []);
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_SENT_TO_KITCHEN);
            
            \Illuminate\Support\Facades\Log::info('Order sent to kitchen successfully', [
                'order_id' => $order->id,
                'order_status' => $order->status,
                'kitchen_notified' => $kitchenNotified,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error sending order to kitchen: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка вызова курьера - отправка списка курьеров
     */
    private function handleCallCourier(Bot $bot, Order $order, TelegramUser $adminUser): void
    {
        try {
            // Проверяем, что заказ в правильном статусе для назначения курьера
            // Можно вызвать курьера для нового, принятого заказа или заказа готового к доставке
            if (!in_array($order->status, [Order::STATUS_NEW, Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_DELIVERY])) {
                $this->telegramService->sendMessage(
                    $bot->token,
                    $adminUser->telegram_id,
                    "❌ Заказ должен быть в статусе 'Новый', 'Принят' или 'Готов к доставке' для назначения курьера. Текущий статус: {$order->status}"
                );
                return;
            }

            // Получаем курьеров из кэша
            $couriers = $this->orderNotificationService->getCachedCouriers($bot->id);

            if ($couriers->isEmpty()) {
                $this->telegramService->sendMessage(
                    $bot->token,
                    $adminUser->telegram_id,
                    '❌ Нет доступных курьеров. Создайте курьера через команду /apply_courier'
                );
                \Illuminate\Support\Facades\Log::warning('No couriers found', [
                    'order_id' => $order->id,
                    'bot_id' => $bot->id,
                ]);
                return;
            }

            // Формируем клавиатуру с курьерами (по 2 в ряд)
            $keyboard = ['inline_keyboard' => []];
            $row = [];
            
            foreach ($couriers as $index => $courier) {
                $row[] = [
                    'text' => '👤 ' . ($courier->full_name ?? "Курьер #{$courier->id}"),
                    'callback_data' => "order_courier_assign:{$order->id}:{$courier->id}"
                ];
                
                // Добавляем строку каждые 2 курьера
                if (count($row) >= 2 || $index === $couriers->count() - 1) {
                    $keyboard['inline_keyboard'][] = $row;
                    $row = [];
                }
            }

            // Добавляем кнопку "Все курьеры"
            $keyboard['inline_keyboard'][] = [[
                'text' => '📢 Все курьеры',
                'callback_data' => "order_courier_assign:{$order->id}:all"
            ]];

            $message = "🚚 Выберите курьера для заказа #{$order->order_id}\n\n" .
                      "Или выберите \"Все курьеры\" для отправки всем.";
            
            $this->telegramService->sendMessage($bot->token, $adminUser->telegram_id, $message, [
                'reply_markup' => json_encode($keyboard)
            ]);
            
            \Illuminate\Support\Facades\Log::info('Courier selection menu sent', [
                'order_id' => $order->id,
                'couriers_count' => $couriers->count(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error calling courier: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка принятия заказа кухней
     */
    private function handleKitchenAccept(Bot $bot, string $orderId, array $from): void
    {
        try {
            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            if (!$telegramUser || $telegramUser->role !== TelegramUser::ROLE_KITCHEN) {
                \Illuminate\Support\Facades\Log::warning('User is not kitchen', [
                    'telegram_user_id' => $telegramUser->id ?? null,
                    'role' => $telegramUser->role ?? null,
                ]);
                return;
            }

            // Используем транзакцию с блокировкой строки
            \Illuminate\Support\Facades\DB::transaction(function () use ($bot, $orderId, $telegramUser) {
                // Блокируем заказ для чтения/изменения
                $order = Order::where('id', $orderId)
                    ->where('bot_id', $bot->id)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \Exception('Order not found');
                }

                // Проверяем статус и optimistic locking
                if ($order->status !== Order::STATUS_SENT_TO_KITCHEN) {
                    \Illuminate\Support\Facades\Log::warning('Order status not suitable for kitchen accept', [
                        'order_id' => $order->id,
                        'current_status' => $order->status,
                    ]);
                    throw new \Exception('Order already accepted or status changed');
                }

                // Изменяем статусы
                $this->orderStatusService->changeStatus($order, Order::STATUS_KITCHEN_ACCEPTED, [
                    'role' => 'kitchen',
                    'changed_by_telegram_user_id' => $telegramUser->id,
                ]);

                $this->orderStatusService->changeStatus($order, Order::STATUS_PREPARING, [
                    'role' => 'kitchen',
                    'changed_by_telegram_user_id' => $telegramUser->id,
                ]);

                // Увеличиваем version
                $order->increment('version');
                $order->refresh();

                // Обновляем сообщение кухне
                $notification = \App\Models\OrderNotification::where('order_id', $order->id)
                    ->where('telegram_user_id', $telegramUser->id)
                    ->where('notification_type', \App\Models\OrderNotification::TYPE_KITCHEN_ORDER)
                    ->where('status', \App\Models\OrderNotification::STATUS_ACTIVE)
                    ->first();

                if ($notification) {
                    $order->load('items');
                    $message = "🍳 Заказ #{$order->order_id} принят\n\n";
                    $message .= "Статус: 🔥 Готовится\n\n";
                    $message .= "📦 Товары:\n";
                    foreach ($order->items as $item) {
                        $message .= "• {$item->product_name} × {$item->quantity}\n";
                    }
                    $message .= "\nНажмите \"Заказ готов\" когда завершите приготовление.";

                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '✅ Заказ готов',
                                    'callback_data' => "order_kitchen_ready:{$order->id}"
                                ]
                            ]
                        ]
                    ];

                    $this->telegramService->editMessageText(
                        $bot->token,
                        $notification->chat_id,
                        $notification->message_id,
                        $message,
                        ['reply_markup' => json_encode($keyboard)]
                    );

                    $notification->markAsUpdated();
                }
            });

            // Уведомления отправляем после транзакции
            $order = Order::find($orderId);
            $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_KITCHEN_ACCEPTED, [
                'message' => "Кухня приняла заказ #{$order->order_id}",
            ]);
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_KITCHEN_ACCEPTED);

            \Illuminate\Support\Facades\Log::info('Order accepted by kitchen', [
                'order_id' => $orderId,
                'kitchen_id' => $telegramUser->id,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling kitchen accept: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка готовности заказа на кухне
     */
    private function handleKitchenReady(Bot $bot, string $orderId, array $from): void
    {
        try {
            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            if (!$telegramUser || $telegramUser->role !== TelegramUser::ROLE_KITCHEN) {
                \Illuminate\Support\Facades\Log::warning('User is not kitchen', [
                    'telegram_user_id' => $telegramUser->id ?? null,
                    'role' => $telegramUser->role ?? null,
                ]);
                return;
            }

            // Проверяем текущий статус заказа перед транзакцией
            $order = Order::find($orderId);
            if (!$order) {
                \Illuminate\Support\Facades\Log::warning('Order not found', ['order_id' => $orderId]);
                return;
            }

            $wasAlreadyReady = $order->status === Order::STATUS_READY_FOR_DELIVERY;

            // Используем транзакцию для атомарного изменения
            \Illuminate\Support\Facades\DB::transaction(function () use ($bot, $orderId, $telegramUser) {
                // Блокируем заказ для чтения/изменения
                $order = Order::where('id', $orderId)
                    ->where('bot_id', $bot->id)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \Exception('Order not found');
                }

                // Проверяем статус - разрешаем для preparing и ready_for_delivery (повторное нажатие)
                if (!in_array($order->status, [Order::STATUS_PREPARING, Order::STATUS_READY_FOR_DELIVERY])) {
                    \Illuminate\Support\Facades\Log::warning('Order status not suitable for ready', [
                        'order_id' => $order->id,
                        'current_status' => $order->status,
                    ]);
                    throw new \Exception('Order status not suitable for ready');
                }

                // Если заказ уже готов, просто выходим из транзакции
                if ($order->status === Order::STATUS_READY_FOR_DELIVERY) {
                    \Illuminate\Support\Facades\Log::info('Order already ready for delivery, skipping status change', [
                        'order_id' => $order->id,
                    ]);
                    return;
                }

                // Изменяем статус заказа
                $this->orderStatusService->changeStatus($order, Order::STATUS_READY_FOR_DELIVERY, [
                    'role' => 'kitchen',
                    'changed_by_telegram_user_id' => $telegramUser->id,
                ]);

                // Увеличиваем version
                $order->increment('version');
                $order->refresh();
            });

            $order = Order::find($orderId);

            // Обновляем сообщение кухни, убирая кнопку
            $kitchenNotification = OrderNotification::where('order_id', $order->id)
                ->where('telegram_user_id', $telegramUser->id)
                ->where('notification_type', OrderNotification::TYPE_KITCHEN_ORDER)
                ->where('status', 'active')
                ->first();

            if ($kitchenNotification) {
                $updatedMessage = "🍳 Заказ #{$order->order_id} готов к доставке\n\n";
                $updatedMessage .= "✅ Статус изменен успешно";
                
                try {
                    $this->telegramService->editMessageText(
                        $bot->token,
                        $kitchenNotification->chat_id,
                        $kitchenNotification->message_id,
                        $updatedMessage
                    );
                    \Illuminate\Support\Facades\Log::info('Kitchen message updated, button removed', [
                        'order_id' => $order->id,
                        'kitchen_user_id' => $telegramUser->id,
                        'message_id' => $kitchenNotification->message_id,
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to update kitchen message', [
                        'order_id' => $order->id,
                        'kitchen_user_id' => $telegramUser->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Если заказ уже был в статусе ready_for_delivery, не отправляем уведомления повторно
            if ($wasAlreadyReady) {
                return;
            }

            // Проверяем наличие курьеров
            $hasCourier = $this->orderNotificationService->getCachedCouriers($bot->id)->isNotEmpty();

            // Формируем сообщение для администратора с кнопкой "Вызвать курьера"
            $message = "✅ Заказ #{$order->order_id} готов к доставке\n\n";
            $message .= "📍 Адрес: {$order->delivery_address}\n";
            $message .= "💰 Сумма: " . number_format($order->total_amount, 2, '.', ' ') . " ₽";

            $keyboard = null;
            if ($hasCourier) {
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🚚 Вызвать курьера',
                                'callback_data' => "order_action:{$order->id}:call_courier"
                            ]
                        ]
                    ]
                ];
            }

            // Отправляем уведомление администратору
            $admins = TelegramUser::where('bot_id', $bot->id)
                ->where('role', TelegramUser::ROLE_ADMIN)
                ->where('is_blocked', false)
                ->get();

            foreach ($admins as $admin) {
                $options = [];
                if ($keyboard) {
                    $options['reply_markup'] = json_encode($keyboard);
                }
                $this->telegramService->sendMessage(
                    $bot->token,
                    $admin->telegram_id,
                    $message,
                    $options
                );
            }

            // Уведомляем клиента
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_READY_FOR_DELIVERY);

            \Illuminate\Support\Facades\Log::info('Order ready for delivery', [
                'order_id' => $orderId,
                'kitchen_id' => $telegramUser->id,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling kitchen ready: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка назначения курьера
     */
    private function handleCourierAssign(Bot $bot, string $orderId, string $courierId, array $from): void
    {
        try {
            // Получаем администратора
            $adminUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            if (!$adminUser || $adminUser->role !== TelegramUser::ROLE_ADMIN) {
                \Illuminate\Support\Facades\Log::warning('User is not admin', [
                    'telegram_user_id' => $adminUser->id ?? null,
                    'role' => $adminUser->role ?? null,
                ]);
                return;
            }

            // Проверяем, отправляем ли всем курьерам
            $sendToAll = ($courierId === 'all');

            if ($sendToAll) {
                // Отправляем всем курьерам
                $couriers = $this->orderNotificationService->getCachedCouriers($bot->id);
                
                if ($couriers->isEmpty()) {
                    $this->telegramService->sendMessage(
                        $bot->token,
                        $adminUser->telegram_id,
                        '❌ Нет доступных курьеров'
                    );
                    return;
                }

                // Используем транзакцию для атомарного изменения
                \Illuminate\Support\Facades\DB::transaction(function () use ($bot, $orderId, $couriers, $adminUser) {
                    $order = Order::where('id', $orderId)
                        ->where('bot_id', $bot->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$order) {
                        throw new \Exception('Order not found');
                    }

                    // Проверяем статус
                    if (!in_array($order->status, [Order::STATUS_NEW, Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_DELIVERY])) {
                        throw new \Exception('Order status not suitable for courier assignment');
                    }

                    // Устанавливаем флаг "отправлено всем курьерам"
                    $order->assigned_to_all_couriers = true;
                    $order->increment('version');
                    $order->save();
                });

                $order = Order::find($orderId);
                
                // Отправляем уведомления всем курьерам
                foreach ($couriers as $courier) {
                    $this->orderNotificationService->notifyCourierOrderReady($order, $courier);
                }

                $this->telegramService->sendMessage(
                    $bot->token,
                    $adminUser->telegram_id,
                    "✅ Заказ #{$order->order_id} отправлен всем курьерам ({$couriers->count()} чел.)"
                );

                \Illuminate\Support\Facades\Log::info('Order sent to all couriers', [
                    'order_id' => $orderId,
                    'couriers_count' => $couriers->count(),
                ]);
            } else {
                // Отправляем конкретному курьеру
                $courier = TelegramUser::find($courierId);

                if (!$courier || $courier->role !== TelegramUser::ROLE_COURIER || $courier->bot_id !== $bot->id) {
                    \Illuminate\Support\Facades\Log::warning('Invalid courier', [
                        'courier_id' => $courierId,
                        'courier_exists' => !!$courier,
                        'courier_role' => $courier->role ?? null,
                    ]);
                    return;
                }

                // Используем транзакцию для атомарного изменения
                \Illuminate\Support\Facades\DB::transaction(function () use ($bot, $orderId, $courier, $adminUser) {
                    $order = Order::where('id', $orderId)
                        ->where('bot_id', $bot->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$order) {
                        throw new \Exception('Order not found');
                    }

                    // Проверяем статус
                    if (!in_array($order->status, [Order::STATUS_NEW, Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_DELIVERY])) {
                        throw new \Exception('Order status not suitable for courier assignment');
                    }

                    // Проверяем, не назначен ли уже курьер
                    if ($order->courier_id && $order->courier_id !== $courier->id) {
                        throw new \Exception('Order already assigned to another courier');
                    }

                    // Назначаем курьера
                    $order->courier_id = $courier->id;
                    $order->assigned_to_all_couriers = false;
                    $order->increment('version');
                    $order->save();

                    // Изменяем статус заказа
                    $this->orderStatusService->changeStatus($order, Order::STATUS_COURIER_ASSIGNED, [
                        'role' => 'admin',
                        'changed_by_telegram_user_id' => $adminUser->id,
                        'metadata' => ['courier_id' => $courier->id],
                    ]);
                });

                $order = Order::find($orderId);
                
                // Отправляем уведомления
                $this->orderNotificationService->notifyCourierOrderReady($order, $courier);
                $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_COURIER_ASSIGNED, [
                    'message' => "Курьер {$courier->full_name} назначен на заказ #{$order->order_id}",
                ]);
                $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_COURIER_ASSIGNED, [
                    'courier_name' => $courier->full_name,
                ]);

                \Illuminate\Support\Facades\Log::info('Courier assigned successfully', [
                    'order_id' => $orderId,
                    'courier_id' => $courier->id,
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error assigning courier: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'courier_id' => $courierId,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка "Забрал заказ" от курьера
     */
    private function handleCourierPicked(Bot $bot, string $orderId, array $from): void
    {
        try {
            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            if (!$telegramUser || $telegramUser->role !== TelegramUser::ROLE_COURIER) {
                \Illuminate\Support\Facades\Log::warning('User is not courier', [
                    'telegram_user_id' => $telegramUser->id ?? null,
                    'role' => $telegramUser->role ?? null,
                ]);
                return;
            }

            // Используем транзакцию для атомарного изменения
            \Illuminate\Support\Facades\DB::transaction(function () use ($bot, $orderId, $telegramUser) {
                // Блокируем заказ для чтения/изменения
                $order = Order::where('id', $orderId)
                    ->where('bot_id', $bot->id)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \Exception('Order not found');
                }

                // Проверяем статус - разрешаем для courier_assigned и ready_for_delivery
                if (!in_array($order->status, [Order::STATUS_COURIER_ASSIGNED, Order::STATUS_READY_FOR_DELIVERY])) {
                    \Illuminate\Support\Facades\Log::warning('Order status not suitable for courier picked', [
                        'order_id' => $order->id,
                        'current_status' => $order->status,
                        'allowed_statuses' => [Order::STATUS_COURIER_ASSIGNED, Order::STATUS_READY_FOR_DELIVERY],
                    ]);
                    throw new \Exception('Order status not suitable');
                }

                // Если заказ был отправлен всем курьерам, назначаем текущего курьера
                if ($order->assigned_to_all_couriers) {
                    // Проверяем, не назначен ли уже другой курьер
                    if ($order->courier_id && $order->courier_id !== $telegramUser->id) {
                        throw new \Exception('Order already picked by another courier');
                    }

                    // Назначаем текущего курьера
                    $order->courier_id = $telegramUser->id;
                    $order->assigned_to_all_couriers = false;
                } elseif (!$order->courier_id) {
                    // Если курьер не назначен, но заказ в статусе ready_for_delivery, назначаем текущего курьера
                    // Это может произойти, если кухня отметила заказ готовым до назначения курьера
                    $order->courier_id = $telegramUser->id;
                    \Illuminate\Support\Facades\Log::info('Courier assigned during pickup', [
                        'order_id' => $order->id,
                        'courier_id' => $telegramUser->id,
                        'previous_status' => $order->status,
                    ]);
                } else {
                    // Проверяем, что курьер назначен на этот заказ
                    if ($order->courier_id !== $telegramUser->id) {
                        throw new \Exception('Courier not assigned to this order');
                    }
                }

                // Изменяем статус заказа
                \Illuminate\Support\Facades\Log::info('Changing order status to in_transit', [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                    'new_status' => Order::STATUS_IN_TRANSIT,
                    'courier_id' => $telegramUser->id,
                ]);

                $this->orderStatusService->changeStatus($order, Order::STATUS_IN_TRANSIT, [
                    'role' => 'courier',
                    'changed_by_telegram_user_id' => $telegramUser->id,
                ]);

                // Увеличиваем version
                $order->increment('version');
                $order->refresh();

                \Illuminate\Support\Facades\Log::info('Order status changed to in_transit', [
                    'order_id' => $order->id,
                    'final_status' => $order->status,
                    'courier_id' => $telegramUser->id,
                ]);
            });

            $order = Order::find($orderId);

            // Если заказ был отправлен всем курьерам, удаляем уведомления у остальных
            // Проверяем, был ли заказ отправлен всем курьерам до назначения
            // Если courier_id был null, значит заказ был отправлен всем
            if ($order->assigned_to_all_couriers || !$order->courier_id) {
                $allCouriers = $this->orderNotificationService->getCachedCouriers($bot->id);
                $excludeIds = [$telegramUser->id];
                
                // Удаляем уведомления у всех курьеров, кроме того, кто взял заказ
                $this->orderNotificationService->deleteNotificationsForOrder(
                    $order,
                    OrderNotification::TYPE_COURIER_ORDER,
                    $excludeIds
                );
            }

            // Уведомляем клиента о том, что курьер забрал заказ
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_IN_TRANSIT);

            // Уведомляем администратора
            $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_IN_TRANSIT, [
                'message' => "Курьер {$telegramUser->full_name} забрал заказ #{$order->order_id}",
            ]);
            
            // Отправляем курьеру новое сообщение с кнопками
            $this->orderNotificationService->notifyCourierInTransit($order, $telegramUser);

            \Illuminate\Support\Facades\Log::info('Order picked by courier', [
                'order_id' => $orderId,
                'courier_id' => $telegramUser->id,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling courier picked: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка доставки заказа курьером
     */
    private function handleCourierDelivered(Bot $bot, string $orderId, array $from): void
    {
        try {
            $order = Order::where('id', $orderId)->where('bot_id', $bot->id)->first();
            if (!$order || $order->status !== Order::STATUS_IN_TRANSIT) {
                \Illuminate\Support\Facades\Log::warning('Order not found or wrong status for delivery', [
                    'order_id' => $orderId,
                    'order_status' => $order->status ?? null,
                ]);
                return;
            }

            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            if (!$telegramUser || $telegramUser->role !== TelegramUser::ROLE_COURIER) {
                \Illuminate\Support\Facades\Log::warning('Invalid user for delivery handling', [
                    'order_id' => $orderId,
                    'user_role' => $telegramUser->role ?? null,
                ]);
                return;
            }

            // Используем транзакцию для атомарного изменения
            \Illuminate\Support\Facades\DB::transaction(function () use ($bot, $orderId, $telegramUser) {
                // Блокируем заказ для чтения/изменения
                $order = Order::where('id', $orderId)
                    ->where('bot_id', $bot->id)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \Exception('Order not found');
                }

                // Проверяем статус
                if ($order->status !== Order::STATUS_IN_TRANSIT) {
                    throw new \Exception('Order status not suitable for delivery');
                }

                // Проверяем, что курьер назначен на этот заказ
                if ($order->courier_id !== $telegramUser->id) {
                    throw new \Exception('Courier not assigned to this order');
                }

                // Если оплата уже получена, сразу меняем статус на delivered
                if ($order->payment_status === Order::PAYMENT_STATUS_SUCCEEDED) {
                    $this->orderStatusService->changeStatus($order, Order::STATUS_DELIVERED, [
                        'role' => 'courier',
                        'changed_by_telegram_user_id' => $telegramUser->id,
                        'comment' => 'Заказ доставлен, оплата уже получена',
                    ]);
                }

                // Увеличиваем version
                $order->increment('version');
                $order->refresh();
            });

            $order = Order::find($orderId);

            // Если оплата не получена, отправляем кнопки для обработки оплаты
            if ($order->payment_status === Order::PAYMENT_STATUS_PENDING) {
                $message = "✅ Заказ #{$order->order_id} доставлен\n\n";
                $message .= "💳 Требуется подтверждение оплаты\n";
                $message .= "💰 Сумма: " . number_format($order->total_amount, 2, '.', ' ') . " ₽\n\n";
                $message .= "Подтвердите получение оплаты:";

                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '✅ Оплата получена', 'callback_data' => "order_payment:{$order->id}:received"],
                            ['text' => '❌ Оплата не получена', 'callback_data' => "order_payment:{$order->id}:not_received"],
                        ]
                    ]
                ];

                $this->telegramService->sendMessage(
                    $bot->token,
                    $telegramUser->telegram_id,
                    $message,
                    ['reply_markup' => json_encode($keyboard)]
                );
                
                \Illuminate\Support\Facades\Log::info('Payment confirmation requested from courier', [
                    'order_id' => $order->id,
                    'courier_id' => $telegramUser->id,
                ]);
                return;
            }

            $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_DELIVERED, [
                'message' => "Заказ #{$order->order_id} доставлен курьером {$telegramUser->full_name}",
            ]);
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_DELIVERED);
            
            \Illuminate\Support\Facades\Log::info('Order delivered by courier (payment already received)', [
                'order_id' => $order->id,
                'courier_id' => $telegramUser->id,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling courier delivered: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка оплаты курьером
     */
    private function handleOrderPayment(Bot $bot, string $orderId, string $status, array $from): void
    {
        try {
            $order = Order::where('id', $orderId)->where('bot_id', $bot->id)->first();
            if (!$order) {
                \Illuminate\Support\Facades\Log::warning('Order not found for payment handling', [
                    'order_id' => $orderId,
                    'bot_id' => $bot->id,
                ]);
                return;
            }

            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            if (!$telegramUser || $telegramUser->role !== TelegramUser::ROLE_COURIER) {
                \Illuminate\Support\Facades\Log::warning('Invalid user for payment handling', [
                    'order_id' => $orderId,
                    'user_role' => $telegramUser->role ?? null,
                ]);
                return;
            }

            // Используем транзакцию для атомарного изменения
            \Illuminate\Support\Facades\DB::transaction(function () use ($bot, $orderId, $telegramUser, $status) {
                // Блокируем заказ для чтения/изменения
                $order = Order::where('id', $orderId)
                    ->where('bot_id', $bot->id)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \Exception('Order not found');
                }

                // Проверяем статус
                if ($order->status !== Order::STATUS_IN_TRANSIT && $order->status !== Order::STATUS_READY_FOR_DELIVERY) {
                    throw new \Exception('Order status not suitable for payment handling');
                }

                // Проверяем, что курьер назначен на этот заказ
                if ($order->courier_id !== $telegramUser->id) {
                    throw new \Exception('Courier not assigned to this order');
                }

                if ($status === 'received') {
                // Создаем платеж в БД
                $payment = \App\Models\Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => $order->payment_method ?? \App\Models\Payment::METHOD_CASH,
                    'payment_provider' => 'courier',
                    'status' => \App\Models\Payment::STATUS_SUCCEEDED,
                    'amount' => $order->total_amount,
                    'currency' => 'RUB',
                    'transaction_id' => 'COURIER-' . $order->order_id . '-' . time(),
                    'notes' => "Оплата принята курьером {$telegramUser->full_name}",
                    'paid_at' => now(),
                ]);

                    // Создаем платеж в БД
                    $payment = \App\Models\Payment::create([
                        'order_id' => $order->id,
                        'payment_method' => $order->payment_method ?? \App\Models\Payment::METHOD_CASH,
                        'payment_provider' => 'courier',
                        'status' => \App\Models\Payment::STATUS_SUCCEEDED,
                        'amount' => $order->total_amount,
                        'currency' => 'RUB',
                        'transaction_id' => 'COURIER-' . $order->order_id . '-' . time(),
                        'notes' => "Оплата принята курьером {$telegramUser->full_name}",
                        'paid_at' => now(),
                    ]);

                    // Обновляем статус оплаты заказа
                    $order->payment_status = Order::PAYMENT_STATUS_SUCCEEDED;
                    $order->payment_id = (string) $payment->id;

                    // Изменяем статус заказа на доставлен
                    $this->orderStatusService->changeStatus($order, Order::STATUS_DELIVERED, [
                        'role' => 'courier',
                        'changed_by_telegram_user_id' => $telegramUser->id,
                        'comment' => 'Оплата получена курьером',
                        'metadata' => ['payment_id' => $payment->id],
                    ]);
                } else {
                    // Оплата не получена - создаем платеж со статусом failed
                    $payment = \App\Models\Payment::create([
                        'order_id' => $order->id,
                        'payment_method' => $order->payment_method ?? \App\Models\Payment::METHOD_CASH,
                        'payment_provider' => 'courier',
                        'status' => \App\Models\Payment::STATUS_FAILED,
                        'amount' => $order->total_amount,
                        'currency' => 'RUB',
                        'transaction_id' => 'COURIER-FAILED-' . $order->order_id . '-' . time(),
                        'notes' => "Оплата не получена курьером {$telegramUser->full_name}",
                    ]);

                    // Обновляем статус оплаты заказа
                    $order->payment_status = Order::PAYMENT_STATUS_FAILED;
                    $order->payment_id = (string) $payment->id;

                    // Все равно доставляем заказ, но отмечаем что оплата не получена
                    $this->orderStatusService->changeStatus($order, Order::STATUS_DELIVERED, [
                        'role' => 'courier',
                        'changed_by_telegram_user_id' => $telegramUser->id,
                        'comment' => 'Оплата не получена',
                        'metadata' => ['payment_id' => $payment->id, 'payment_failed' => true],
                    ]);
                }

                // Увеличиваем version
                $order->increment('version');
                $order->refresh();
            });

            $order = Order::find($orderId);

            // Уведомления отправляем после транзакции
            if ($status === 'received') {
                \Illuminate\Support\Facades\Log::info('Payment received by courier', [
                    'order_id' => $order->id,
                    'payment_id' => $order->payment_id,
                    'amount' => $order->total_amount,
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning('Payment not received by courier', [
                    'order_id' => $order->id,
                    'payment_id' => $order->payment_id,
                    'amount' => $order->total_amount,
                ]);
            }

            $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_DELIVERED, [
                'message' => "Заказ #{$order->order_id} доставлен курьером {$telegramUser->full_name}. Оплата: " . ($status === 'received' ? 'получена' : 'не получена'),
            ]);
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_DELIVERED);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling order payment: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'status' => $status,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка текстовых сообщений для причины отмены
     */
    private function handleTextMessageForCancelReason(Bot $bot, int $chatId, string $text, array $from): void
    {
        try {
            // Проверяем временное состояние для отмены заказа клиентом
            $clientCacheKey = "cancel_order:{$bot->id}:{$from['id']}";
            $clientCacheData = \Illuminate\Support\Facades\Cache::get($clientCacheKey);

            // Проверяем временное состояние для отмены заказа администратором
            $adminCacheKey = "admin_cancel_order:{$bot->id}:{$from['id']}";
            $adminCacheData = \Illuminate\Support\Facades\Cache::get($adminCacheKey);

            if ($clientCacheData) {
                // Обработка причины отмены от клиента
                $this->handleCancelOrderReason($bot, $chatId, $text, $from);
            } elseif ($adminCacheData) {
                // Обработка причины отмены от администратора
                $this->handleAdminCancelOrderReason($bot, $chatId, $text, $from, $adminCacheData);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling text message for cancel reason: ' . $e->getMessage(), [
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обработка причины отмены заказа администратором
     */
    private function handleAdminCancelOrderReason(Bot $bot, int $chatId, string $text, array $from, array $cacheData): void
    {
        try {
            $order = Order::find($cacheData['order_id']);
            if (!$order || $order->bot_id != $bot->id) {
                \Illuminate\Support\Facades\Cache::forget("admin_cancel_order:{$bot->id}:{$from['id']}");
                return;
            }

            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'])
                ->first();

            if (!$telegramUser || $telegramUser->role !== TelegramUser::ROLE_ADMIN) {
                \Illuminate\Support\Facades\Cache::forget("admin_cancel_order:{$bot->id}:{$from['id']}");
                return;
            }

            // Валидация причины отмены
            $text = trim($text);
            if (strlen($text) < 5) {
                $attemptsKey = "admin_cancel_attempts:{$bot->id}:{$from['id']}";
                $attempts = \Illuminate\Support\Facades\Cache::get($attemptsKey, 0) + 1;
                
                if ($attempts < 3) {
                    \Illuminate\Support\Facades\Cache::put($attemptsKey, $attempts, now()->addMinutes(10));
                    $remaining = 3 - $attempts;
                    $this->telegramService->sendMessage(
                        $bot->token,
                        $chatId,
                        "❓ Причина отмены слишком короткая. Пожалуйста, укажите более подробную причину.\n\nОсталось попыток: {$remaining}"
                    );
                    return;
                } else {
                    \Illuminate\Support\Facades\Cache::forget($attemptsKey);
                    \Illuminate\Support\Facades\Cache::forget("admin_cancel_order:{$bot->id}:{$from['id']}");
                    $this->telegramService->sendMessage(
                        $bot->token,
                        $chatId,
                        "❌ Превышено количество попыток. Операция отменена."
                    );
                    return;
                }
            }

            // Удаляем временное состояние
            \Illuminate\Support\Facades\Cache::forget("admin_cancel_order:{$bot->id}:{$from['id']}");
            \Illuminate\Support\Facades\Cache::forget("admin_cancel_attempts:{$bot->id}:{$from['id']}");

            // Используем транзакцию для атомарного изменения статуса
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $telegramUser, $text, $bot, $chatId) {
                // Изменяем статус заказа на cancelled
                $this->orderStatusService->changeStatus($order, Order::STATUS_CANCELLED, [
                    'role' => 'admin',
                    'changed_by_telegram_user_id' => $telegramUser->id,
                    'comment' => "Причина отмены администратором: {$text}",
                ]);

                $order->refresh();

                // Уведомляем клиента об отмене
                $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_CANCELLED);

                // Уведомляем кухню, если заказ был на кухне
                if (in_array($order->status, [
                    Order::STATUS_SENT_TO_KITCHEN,
                    Order::STATUS_KITCHEN_ACCEPTED,
                    Order::STATUS_PREPARING,
                    Order::STATUS_READY_FOR_DELIVERY
                ])) {
                    $kitchenUsers = $this->orderNotificationService->getCachedKitchenUsers($bot->id);
                    foreach ($kitchenUsers as $kitchenUser) {
                        $this->telegramService->sendMessage(
                            $bot->token,
                            $kitchenUser->telegram_id,
                            "❌ Заказ #{$order->order_id} отменен администратором"
                        );
                    }
                }

                // Уведомляем курьера, если заказ был у курьера
                if ($order->courier_id) {
                    $courier = TelegramUser::find($order->courier_id);
                    if ($courier) {
                        $this->telegramService->sendMessage(
                            $bot->token,
                            $courier->telegram_id,
                            "❌ Заказ #{$order->order_id} отменен администратором"
                        );
                        // Удаляем уведомление курьера
                        $this->orderNotificationService->deleteNotification($order, $courier, OrderNotification::TYPE_COURIER_ORDER);
                    }
                }

                // Подтверждаем администратору
                $this->telegramService->sendMessage(
                    $bot->token,
                    $chatId,
                    "✅ Заказ #{$order->order_id} отменен. Причина: {$text}"
                );

                \Illuminate\Support\Facades\Log::info('Order cancelled by admin', [
                    'order_id' => $order->id,
                    'admin_id' => $telegramUser->id,
                    'reason' => $text,
                ]);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling admin cancel order reason: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Обработка запроса на отмену заказа
     */
    private function handleOrderCancelRequest(Bot $bot, string $orderId, array $from): void
    {
        try {
            $order = Order::where('id', $orderId)->where('bot_id', $bot->id)->first();
            if (!$order) {
                return;
            }

            // Проверяем, что пользователь является владельцем заказа
            if ($order->telegram_id != ($from['id'] ?? null)) {
                $this->telegramService->sendMessage(
                    $bot->token,
                    $from['id'] ?? 0,
                    '❌ Вы не можете отменить этот заказ'
                );
                return;
            }

            // Проверяем, что заказ может быть отменен
            if (in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])) {
                $this->telegramService->sendMessage(
                    $bot->token,
                    $from['id'] ?? 0,
                    '❌ Этот заказ уже доставлен или отменен'
                );
                return;
            }

            // Сохраняем временное состояние в cache для ожидания причины
            $cacheKey = "cancel_order:{$bot->id}:{$from['id']}";
            \Illuminate\Support\Facades\Cache::put($cacheKey, [
                'order_id' => $order->id,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ], now()->addMinutes(10));

            // Отправляем запрос на ввод причины
            $message = "❓ Укажите причину отмены заказа #{$order->order_id}:\n\n" .
                      "Напишите текст сообщения с причиной отмены.";
            
            $this->telegramService->sendMessage($bot->token, $from['id'] ?? 0, $message);

            \Illuminate\Support\Facades\Log::info('Order cancel request received, waiting for reason', [
                'order_id' => $order->id,
                'telegram_id' => $from['id'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling order cancel request: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Обработка текстового сообщения с причиной отмены
     *
     * @param Bot $bot
     * @param int $chatId
     * @param string $text
     * @param array $from
     * @return void
     */
    private function handleCancelOrderReason(Bot $bot, int $chatId, string $text, array $from): void
    {
        try {
            // Проверяем временное состояние
            $cacheKey = "cancel_order:{$bot->id}:{$from['id']}";
            $cacheData = \Illuminate\Support\Facades\Cache::get($cacheKey);

            if (!$cacheData) {
                return; // Нет активного запроса на отмену
            }

            $order = Order::find($cacheData['order_id']);
            if (!$order || $order->bot_id != $bot->id) {
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                return;
            }

            // Проверяем, что пользователь является владельцем заказа
            if ($order->telegram_id != $from['id']) {
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                return;
            }

            // Проверяем, что заказ еще может быть отменен
            if (in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])) {
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                $this->telegramService->sendMessage(
                    $bot->token,
                    $chatId,
                    '❌ Этот заказ уже доставлен или отменен'
                );
                return;
            }

            // Проверяем, что заказ был принят администратором
            if ($order->status === Order::STATUS_NEW) {
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
                $this->telegramService->sendMessage(
                    $bot->token,
                    $chatId,
                    '❌ Заказ еще не принят администратором. Отмена возможна только после принятия заказа.'
                );
                return;
            }

            // Валидация причины отмены
            $text = trim($text);
            if (strlen($text) < 5 || strlen($text) > 500) {
                $attemptsKey = "cancel_order_attempts:{$bot->id}:{$from['id']}";
                $attempts = \Illuminate\Support\Facades\Cache::get($attemptsKey, 0) + 1;
                
                if ($attempts < 3) {
                    \Illuminate\Support\Facades\Cache::put($attemptsKey, $attempts, now()->addMinutes(10));
                    $remaining = 3 - $attempts;
                    $message = "❓ Причина отмены должна быть от 5 до 500 символов. Пожалуйста, укажите более подробную причину.\n\nОсталось попыток: {$remaining}";
                    $this->telegramService->sendMessage($bot->token, $chatId, $message);
                    return;
                } else {
                    \Illuminate\Support\Facades\Cache::forget($attemptsKey);
                    \Illuminate\Support\Facades\Cache::forget($cacheKey);
                    $this->telegramService->sendMessage(
                        $bot->token,
                        $chatId,
                        "❌ Превышено количество попыток. Операция отменена."
                    );
                    return;
                }
            }

            // Удаляем временное состояние и счетчик попыток
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            \Illuminate\Support\Facades\Cache::forget("cancel_order_attempts:{$bot->id}:{$from['id']}");

            // Сохраняем предыдущий статус ПЕРЕД отменой
            $previousStatus = $order->status;

            // Используем транзакцию для атомарного изменения статуса
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $bot, $from, $text, $previousStatus) {
                // Изменяем статус заказа на cancelled
                $telegramUser = TelegramUser::where('bot_id', $bot->id)
                    ->where('telegram_id', $from['id'])
                    ->first();

                $this->orderStatusService->changeStatus($order, Order::STATUS_CANCELLED, [
                    'role' => 'user',
                    'changed_by_telegram_user_id' => $telegramUser->id ?? null,
                    'comment' => "Причина отмены: {$text}",
                ]);

                $order->refresh();

                // Уведомляем администратора
            $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_CANCELLED, [
                'message' => "Заказ #{$order->order_id} отменен клиентом",
                'cancel_reason' => $text,
            ]);

            // Уведомляем кухню, если заказ был на кухне
            if (in_array($previousStatus, [
                Order::STATUS_SENT_TO_KITCHEN,
                Order::STATUS_KITCHEN_ACCEPTED,
                Order::STATUS_PREPARING,
                Order::STATUS_READY_FOR_DELIVERY
            ])) {
                $kitchenUsers = TelegramUser::where('bot_id', $bot->id)
                    ->where('role', TelegramUser::ROLE_KITCHEN)
                    ->where('is_blocked', false)
                    ->get();

                foreach ($kitchenUsers as $kitchenUser) {
                    $this->telegramService->sendMessage(
                        $bot->token,
                        $kitchenUser->telegram_id,
                        "❌ Заказ #{$order->order_id} отменен клиентом"
                    );
                }
            }

                // Уведомляем курьера, если заказ был у курьера
                if (in_array($previousStatus, [
                    Order::STATUS_COURIER_ASSIGNED,
                    Order::STATUS_IN_TRANSIT
                ]) && $order->courier_id) {
                    $courier = TelegramUser::find($order->courier_id);
                    if ($courier) {
                        $this->telegramService->sendMessage(
                            $bot->token,
                            $courier->telegram_id,
                            "❌ Заказ #{$order->order_id} отменен клиентом"
                        );
                        // Удаляем уведомление курьера
                        $this->orderNotificationService->deleteNotification($order, $courier, OrderNotification::TYPE_COURIER_ORDER);
                    }
                }

                // Уведомляем клиента
                $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_CANCELLED);

                \Illuminate\Support\Facades\Log::info('Order cancelled by client', [
                    'order_id' => $order->id,
                    'telegram_id' => $from['id'],
                    'reason' => $text,
                ]);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling cancel order reason: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
