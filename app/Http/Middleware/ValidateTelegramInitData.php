<?php

namespace App\Http\Middleware;

use App\Models\Bot;
use App\Services\Telegram\TelegramMiniAppService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTelegramInitData
{
    protected TelegramMiniAppService $telegramMiniAppService;

    public function __construct(TelegramMiniAppService $telegramMiniAppService)
    {
        $this->telegramMiniAppService = $telegramMiniAppService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $initData = $request->header('X-Telegram-Init-Data') 
            ?? $request->input('init_data')
            ?? $request->bearerToken();

        if (!$initData) {
            return response()->json([
                'message' => 'initData не предоставлен',
            ], 401);
        }

        // Получаем токен бота из запроса или конфига
        $botToken = $request->input('bot_token') 
            ?? $request->header('X-Bot-Token')
            ?? config('telegram.bot_token');

        if (!$botToken) {
            // Пробуем найти бота по ID из запроса
            $botId = $request->input('bot_id');
            if ($botId) {
                $bot = Bot::find($botId);
                if ($bot) {
                    $botToken = $bot->token;
                }
            }
        }

        if (!$botToken) {
            return response()->json([
                'message' => 'Токен бота не найден',
            ], 401);
        }

        // Валидируем initData
        $validation = $this->telegramMiniAppService->validateInitData($initData, $botToken);

        if (!$validation['valid']) {
            return response()->json([
                'message' => $validation['message'] ?? 'Неверный initData',
            ], 401);
        }

        // Добавляем данные пользователя в запрос
        $request->merge([
            '_telegram_user' => $validation['user'] ?? null,
            '_telegram_init_data' => $validation['data'] ?? [],
        ]);

        return $next($request);
    }
}


