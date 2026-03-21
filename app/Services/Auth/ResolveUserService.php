<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для разрешения/создания User из данных Telegram.
 * Используется для связки Telegram → User (Laravel).
 */
class ResolveUserService
{
    /**
     * Найти или создать User по данным Telegram.
     *
     * @param array|object $telegramUser Данные пользователя из Telegram (initDataUnsafe.user или аналог)
     * @return User|null User или null если telegram_id отсутствует
     */
    public function resolveFromTelegram(array|object $telegramUser): ?User
    {
        $telegramId = $this->extractTelegramId($telegramUser);
        if ($telegramId === null) {
            return null;
        }

        $user = User::firstOrCreate(
            ['telegram_id' => $telegramId],
            [
                'name' => $this->extractName($telegramUser),
                'email' => "telegram_{$telegramId}@placeholder.local",
                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
            ]
        );

        Log::info('Resolved user', ['user_id' => $user->id]);

        return $user;
    }

    /**
     * Найти User по telegram_id БЕЗ создания.
     * Используется в GET-запросах (index), где создание пользователя недопустимо.
     *
     * @param array|object $telegramUser Данные пользователя из Telegram (id обязателен)
     * @return User|null User или null если не найден
     */
    public function findByTelegram(array|object $telegramUser): ?User
    {
        $telegramId = $this->extractTelegramId($telegramUser);
        if ($telegramId === null) {
            return null;
        }

        return User::where('telegram_id', $telegramId)->first();
    }

    private function extractTelegramId(array|object $telegramUser): ?int
    {
        if (is_array($telegramUser)) {
            $id = $telegramUser['id'] ?? null;
        } else {
            $id = $telegramUser->id ?? null;
        }

        return $id !== null ? (int) $id : null;
    }

    private function extractName(array|object $telegramUser): string
    {
        $first = is_array($telegramUser)
            ? ($telegramUser['first_name'] ?? null)
            : ($telegramUser->first_name ?? null);
        $last = is_array($telegramUser)
            ? ($telegramUser['last_name'] ?? null)
            : ($telegramUser->last_name ?? null);

        $parts = array_filter([$first, $last]);

        return !empty($parts) ? implode(' ', $parts) : 'Telegram User';
    }
}
