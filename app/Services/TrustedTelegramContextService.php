<?php

namespace App\Services;

use App\Services\Auth\ResolveUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Production-ready: telegram_id только из доверенных источников.
 *
 * PRIORITY (жёсткий порядок):
 * 1. initData (валидированный) — главный источник
 * 2. auth()->user()
 * 3. fallback (только local/dev)
 *
 * User без telegram_id: работа через user_id, telegram_id = null.
 */
class TrustedTelegramContextService
{
    public function __construct(
        protected ResolveUserService $resolveUserService
    ) {}

    /**
     * @return array{telegram_id: int|null, user: \App\Models\User|null, telegram_user: array|null}|null
     */
    public function resolve(Request $request): ?array
    {
        $env = config('app.env');
        $isProduction = !in_array($env, ['local', 'development', 'dev'], true);

        // 1. initData (главный источник) — приоритет над auth
        $tgUser = $request->get('_telegram_user');
        if (is_array($tgUser) && isset($tgUser['id'])) {
            $this->logRawTelegramIdAttempt($request, $isProduction, 'ignored_using_initdata');
            $telegramId = (int) $tgUser['id'];
            return [
                'telegram_id' => $telegramId,
                'user' => $this->resolveUserService->findByTelegram($tgUser),
                'telegram_user' => $tgUser,
            ];
        }

        // 2. auth()->user() (WEB)
        $user = auth()->user();
        if ($user) {
            $this->logRawTelegramIdAttempt($request, $isProduction, 'ignored_using_auth');
            // User без telegram_id — работаем через user_id, telegram_id не используем
            $telegramId = $user->telegram_id ? (int) $user->telegram_id : null;
            $tgUser = $telegramId ? ['id' => $telegramId] : null;
            return [
                'telegram_id' => $telegramId,
                'user' => $user,
                'telegram_user' => $tgUser,
            ];
        }

        // 3. fallback — ТОЛЬКО local/dev (без лога, нормальный сценарий)
        if (!$isProduction) {
            $raw = $request->input('telegram_id') ?: $request->query('telegram_id');
            if ($raw !== null && $raw !== '') {
                $telegramId = (int) $raw;
                return [
                    'telegram_id' => $telegramId,
                    'user' => $this->resolveUserService->findByTelegram(['id' => $telegramId]),
                    'telegram_user' => ['id' => $telegramId],
                ];
            }
        }

        return null;
    }

    /**
     * Log только при: попытка передать raw telegram_id в production, нарушение безопасности.
     * Не логируем: normal fallback, валидные запросы.
     */
    private function logRawTelegramIdAttempt(Request $request, bool $isProduction, string $reason): void
    {
        if (!$isProduction || !$this->hasRawTelegramIdInRequest($request)) {
            return;
        }
        Log::warning('Attempt to use raw telegram_id in production', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'reason' => $reason,
        ]);
    }

    /**
     * Проверить, передан ли telegram_id из request (недоверенный источник).
     */
    public function hasRawTelegramIdInRequest(Request $request): bool
    {
        return $request->has('telegram_id') || $request->query('telegram_id') !== null;
    }
}
