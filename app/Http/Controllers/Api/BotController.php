<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\Order;
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
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling order action: ' . $e->getMessage());
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

            // Проверяем наличие пользователей с ролью кухни
            $hasKitchen = TelegramUser::where('bot_id', $bot->id)
                ->where('role', TelegramUser::ROLE_KITCHEN)
                ->where('is_blocked', false)
                ->exists();

            if (!$hasKitchen) {
                $this->telegramService->sendMessage(
                    $bot->token,
                    $adminUser->telegram_id,
                    '❌ Нет доступных пользователей с ролью "Кухня"'
                );
                return;
            }

            // Изменяем статус заказа
            $statusChanged = $this->orderStatusService->changeStatus($order, Order::STATUS_SENT_TO_KITCHEN, [
                'role' => 'admin',
                'changed_by_telegram_user_id' => $adminUser->id,
            ]);

            if (!$statusChanged) {
                \Illuminate\Support\Facades\Log::error('Failed to change order status to sent_to_kitchen', [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                ]);
                return;
            }

            // Обновляем заказ из БД
            $order->refresh();

            $this->orderNotificationService->notifyKitchenOrderSent($order);
            $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_SENT_TO_KITCHEN, []);
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_SENT_TO_KITCHEN);
            
            \Illuminate\Support\Facades\Log::info('Order sent to kitchen successfully', [
                'order_id' => $order->id,
                'order_status' => $order->status,
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
            if (!in_array($order->status, [Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_DELIVERY])) {
                $this->telegramService->sendMessage(
                    $bot->token,
                    $adminUser->telegram_id,
                    "❌ Заказ должен быть в статусе 'Принят' или 'Готов к доставке' для назначения курьера. Текущий статус: {$order->status}"
                );
                return;
            }

            $couriers = TelegramUser::where('bot_id', $bot->id)
                ->where('role', TelegramUser::ROLE_COURIER)
                ->where('is_blocked', false)
                ->get();

            if ($couriers->isEmpty()) {
                $this->telegramService->sendMessage($bot->token, $adminUser->telegram_id, '❌ Нет доступных курьеров');
                return;
            }

            $keyboard = ['inline_keyboard' => []];
            foreach ($couriers as $courier) {
                $keyboard['inline_keyboard'][] = [[
                    'text' => $courier->full_name ?? "Курьер #{$courier->id}",
                    'callback_data' => "order_courier_assign:{$order->id}:{$courier->id}"
                ]];
            }

            $message = "🚚 Выберите курьера для заказа #{$order->order_id}";
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
            $order = Order::where('id', $orderId)->where('bot_id', $bot->id)->first();
            if (!$order || $order->status !== Order::STATUS_SENT_TO_KITCHEN) {
                return;
            }

            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            if (!$telegramUser || $telegramUser->role !== TelegramUser::ROLE_KITCHEN) {
                return;
            }

            $this->orderStatusService->changeStatus($order, Order::STATUS_KITCHEN_ACCEPTED, [
                'role' => 'kitchen',
                'changed_by_telegram_user_id' => $telegramUser->id,
            ]);

            $this->orderStatusService->changeStatus($order, Order::STATUS_PREPARING, [
                'role' => 'kitchen',
                'changed_by_telegram_user_id' => $telegramUser->id,
            ]);

            $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_KITCHEN_ACCEPTED, []);
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_KITCHEN_ACCEPTED);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling kitchen accept: ' . $e->getMessage());
        }
    }

    /**
     * Обработка готовности заказа на кухне
     */
    private function handleKitchenReady(Bot $bot, string $orderId, array $from): void
    {
        try {
            $order = Order::where('id', $orderId)->where('bot_id', $bot->id)->first();
            if (!$order || $order->status !== Order::STATUS_PREPARING) {
                return;
            }

            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            if (!$telegramUser || $telegramUser->role !== TelegramUser::ROLE_KITCHEN) {
                return;
            }

            $this->orderStatusService->changeStatus($order, Order::STATUS_READY_FOR_DELIVERY, [
                'role' => 'kitchen',
                'changed_by_telegram_user_id' => $telegramUser->id,
            ]);

            $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_READY_FOR_DELIVERY, []);
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_READY_FOR_DELIVERY);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling kitchen ready: ' . $e->getMessage());
        }
    }

    /**
     * Обработка назначения курьера
     */
    private function handleCourierAssign(Bot $bot, string $orderId, string $courierId, array $from): void
    {
        try {
            $order = Order::where('id', $orderId)->where('bot_id', $bot->id)->first();
            $courier = TelegramUser::find($courierId);

            if (!$order || !$courier || $courier->role !== TelegramUser::ROLE_COURIER) {
                \Illuminate\Support\Facades\Log::warning('Invalid courier assignment attempt', [
                    'order_id' => $orderId,
                    'courier_id' => $courierId,
                    'order_exists' => !!$order,
                    'courier_exists' => !!$courier,
                    'courier_role' => $courier->role ?? null,
                ]);
                return;
            }

            // Проверяем, что заказ в правильном статусе для назначения курьера
            if (!in_array($order->status, [Order::STATUS_ACCEPTED, Order::STATUS_READY_FOR_DELIVERY])) {
                \Illuminate\Support\Facades\Log::warning('Order status not suitable for courier assignment', [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                ]);
                return;
            }

            // Сохраняем ID курьера в notes заказа (временно, пока нет поля courier_id)
            // Можно добавить отдельное поле courier_id через миграцию
            $notes = $order->notes ?? '';
            $notesData = [];
            if ($notes) {
                $notesData = json_decode($notes, true) ?? [];
            }
            $notesData['courier_id'] = $courier->id;
            $order->notes = json_encode($notesData);
            $order->save();

            // Получаем администратора, который назначил курьера
            $adminUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            // Изменяем статус заказа
            $statusChanged = $this->orderStatusService->changeStatus($order, Order::STATUS_COURIER_ASSIGNED, [
                'role' => 'admin',
                'changed_by_telegram_user_id' => $adminUser->id ?? null,
                'metadata' => ['courier_id' => $courier->id],
            ]);

            if (!$statusChanged) {
                \Illuminate\Support\Facades\Log::error('Failed to change order status to courier_assigned', [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                ]);
                return;
            }

            // Обновляем заказ из БД, чтобы получить актуальный статус
            $order->refresh();

            $this->orderNotificationService->notifyCourierOrderReady($order, $courier);
            $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_COURIER_ASSIGNED, [
                'message' => "Курьер {$courier->full_name} назначен на заказ #{$order->order_id}",
            ]);
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_COURIER_ASSIGNED);
            
            \Illuminate\Support\Facades\Log::info('Courier assigned successfully', [
                'order_id' => $order->id,
                'order_status' => $order->status,
                'courier_id' => $courier->id,
            ]);
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
            $order = Order::where('id', $orderId)->where('bot_id', $bot->id)->first();
            if (!$order || $order->status !== Order::STATUS_COURIER_ASSIGNED) {
                return;
            }

            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'] ?? null)
                ->first();

            if (!$telegramUser || $telegramUser->role !== TelegramUser::ROLE_COURIER) {
                return;
            }

            // Проверяем, что курьер назначен на этот заказ
            $notesData = [];
            if ($order->notes) {
                $notesData = json_decode($order->notes, true) ?? [];
            }
            if (($notesData['courier_id'] ?? null) != $telegramUser->id) {
                return;
            }

            $statusChanged = $this->orderStatusService->changeStatus($order, Order::STATUS_IN_TRANSIT, [
                'role' => 'courier',
                'changed_by_telegram_user_id' => $telegramUser->id,
            ]);

            if (!$statusChanged) {
                \Illuminate\Support\Facades\Log::error('Failed to change order status to in_transit', [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                ]);
                return;
            }

            // Обновляем заказ из БД
            $order->refresh();

            $this->orderNotificationService->notifyAdminStatusChange($order, Order::STATUS_IN_TRANSIT, [
                'message' => "Курьер {$telegramUser->full_name} забрал заказ #{$order->order_id}",
            ]);
            $this->orderNotificationService->notifyClientStatusChange($order, Order::STATUS_IN_TRANSIT);
            
            // Отправляем курьеру новое сообщение с кнопкой "Товар доставлен"
            $this->orderNotificationService->notifyCourierInTransit($order, $telegramUser);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling courier picked: ' . $e->getMessage());
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

            // Проверяем, что курьер назначен на этот заказ
            $notesData = [];
            if ($order->notes) {
                $notesData = json_decode($order->notes, true) ?? [];
            }
            if (($notesData['courier_id'] ?? null) != $telegramUser->id) {
                \Illuminate\Support\Facades\Log::warning('Courier not assigned to this order', [
                    'order_id' => $order->id,
                    'courier_id' => $telegramUser->id,
                    'assigned_courier_id' => $notesData['courier_id'] ?? null,
                ]);
                return;
            }

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

            // Если оплата уже получена, сразу меняем статус на delivered
            $statusChanged = $this->orderStatusService->changeStatus($order, Order::STATUS_DELIVERED, [
                'role' => 'courier',
                'changed_by_telegram_user_id' => $telegramUser->id,
                'comment' => 'Заказ доставлен, оплата уже получена',
            ]);

            if (!$statusChanged) {
                \Illuminate\Support\Facades\Log::error('Failed to change order status to delivered', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            $order->refresh();

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

            // Проверяем, что заказ в статусе in_transit (курьер забрал заказ)
            if ($order->status !== Order::STATUS_IN_TRANSIT) {
                \Illuminate\Support\Facades\Log::warning('Order status not suitable for payment handling', [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                ]);
                return;
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

                // Обновляем статус оплаты заказа
                $order->payment_status = Order::PAYMENT_STATUS_SUCCEEDED;
                $order->payment_id = (string) $payment->id;
                $order->save();

                // Изменяем статус заказа на доставлен
                $statusChanged = $this->orderStatusService->changeStatus($order, Order::STATUS_DELIVERED, [
                    'role' => 'courier',
                    'changed_by_telegram_user_id' => $telegramUser->id,
                    'comment' => 'Оплата получена курьером',
                    'metadata' => ['payment_id' => $payment->id],
                ]);

                if (!$statusChanged) {
                    \Illuminate\Support\Facades\Log::error('Failed to change order status to delivered', [
                        'order_id' => $order->id,
                    ]);
                    return;
                }

                $order->refresh();

                \Illuminate\Support\Facades\Log::info('Payment received by courier', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
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
                $order->save();

                // Все равно доставляем заказ, но отмечаем что оплата не получена
                $statusChanged = $this->orderStatusService->changeStatus($order, Order::STATUS_DELIVERED, [
                    'role' => 'courier',
                    'changed_by_telegram_user_id' => $telegramUser->id,
                    'comment' => 'Оплата не получена',
                    'metadata' => ['payment_id' => $payment->id, 'payment_failed' => true],
                ]);

                if (!$statusChanged) {
                    \Illuminate\Support\Facades\Log::error('Failed to change order status to delivered', [
                        'order_id' => $order->id,
                    ]);
                    return;
                }

                $order->refresh();

                \Illuminate\Support\Facades\Log::warning('Payment not received by courier', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
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

            // Удаляем временное состояние
            \Illuminate\Support\Facades\Cache::forget($cacheKey);

            // Сохраняем предыдущий статус ПЕРЕД отменой
            $previousStatus = $order->status;

            // Изменяем статус заказа на cancelled
            $telegramUser = TelegramUser::where('bot_id', $bot->id)
                ->where('telegram_id', $from['id'])
                ->first();

            $this->orderStatusService->changeStatus($order, Order::STATUS_CANCELLED, [
                'role' => 'user',
                'changed_by_telegram_user_id' => $telegramUser->id ?? null,
                'comment' => "Причина отмены: {$text}",
            ]);

            // Обновляем заказ из БД для получения нового статуса
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
            ])) {
                $notesData = [];
                if ($order->notes) {
                    $notesData = json_decode($order->notes, true) ?? [];
                }
                $courierId = $notesData['courier_id'] ?? null;
                if ($courierId) {
                    $courier = TelegramUser::find($courierId);
                    if ($courier) {
                        $this->telegramService->sendMessage(
                            $bot->token,
                            $courier->telegram_id,
                            "❌ Заказ #{$order->order_id} отменен клиентом"
                        );
                    }
                }
            }

            // Уведомляем клиента
            $this->telegramService->sendMessage(
                $bot->token,
                $chatId,
                "✅ Ваш заказ #{$order->order_id} отменен"
            );

            \Illuminate\Support\Facades\Log::info('Order cancelled by client', [
                'order_id' => $order->id,
                'telegram_id' => $from['id'],
                'reason' => $text,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error handling cancel order reason: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
