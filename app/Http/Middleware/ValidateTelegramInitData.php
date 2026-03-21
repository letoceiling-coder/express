<?php

namespace App\Http\Middleware;

use App\Models\Bot;
use App\Services\Telegram\TelegramMiniAppService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
     * Валидация Telegram initData: в production без initData — 403.
     * Fallback (telegram_id) разрешён только в local/dev.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $initData = $request->header('X-Telegram-Init-Data')
            ?? $request->input('init_data');

        // Авторизованный запрос (админ) — пропускаем без проверки Telegram
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return $next($request);
        }

        if ($request->user()) {
            return $next($request);
        }

        // Есть initData — валидируем подпись Telegram
        if ($initData) {
            $botToken = $this->getBotToken($request);

            if (!$botToken) {
                return response()->json([
                    'message' => 'Токен бота не найден',
                ], 401);
            }

            $validation = $this->telegramMiniAppService->validateInitData($initData, $botToken);

            if (!$validation['valid']) {
                return response()->json([
                    'message' => $validation['message'] ?? 'Неверный initData',
                ], 401);
            }

            $request->merge([
                '_telegram_user' => $validation['user'] ?? null,
                '_telegram_init_data' => $validation['data'] ?? [],
            ]);

            return $next($request);
        }

        // Нет initData — fallback разрешён только в local/dev
        $env = config('app.env');
        if (in_array($env, ['local', 'development', 'dev'], true)) {
            return $next($request);
        }

        Log::warning('Unauthorized telegram access', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'env' => $env,
            'reason' => 'missing_init_data_in_production',
        ]);

        return response()->json([
            'message' => 'Для доступа к заказам необходим initData от Telegram Mini App',
        ], 403);
    }

    protected function getBotToken(Request $request): ?string
    {
        $token = $request->input('bot_token')
            ?? $request->header('X-Bot-Token')
            ?? config('telegram.bot_token');

        if ($token) {
            return $token;
        }

        $botId = $request->input('bot_id');
        if ($botId) {
            $bot = Bot::find($botId);
            return $bot?->token;
        }

        return Bot::where('is_active', true)->first()?->token;
    }
}






