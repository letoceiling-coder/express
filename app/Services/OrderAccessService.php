<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Единая проверка доступа к заказу для Telegram MiniApp и WEB.
 * Поддерживает user_id (WEB) и telegram_id (Telegram).
 */
class OrderAccessService
{
    /**
     * Проверить, может ли пользователь получить доступ к заказу.
     *
     * @param Order $order Заказ
     * @param User|null $user Пользователь (auth или из ResolveUserService)
     * @param int|null $telegramId ID пользователя в Telegram
     * @return bool
     */
    public function canAccessOrder(Order $order, ?User $user, ?int $telegramId): bool
    {
        return
            ($user && $order->user_id === $user->id) ||
            ($telegramId !== null && (int) $order->telegram_id === (int) $telegramId);
    }

    /**
     * Проверить доступ и вернуть 403 JsonResponse при отказе.
     *
     * @param Order $order
     * @param User|null $user
     * @param int|null $telegramId
     * @param string $context Контекст вызова (для логов)
     * @return \Illuminate\Http\JsonResponse|null null если доступ разрешен
     */
    public function denyIfCannotAccess(
        Order $order,
        ?User $user,
        ?int $telegramId,
        string $context = 'OrderAccessService'
    ): ?\Illuminate\Http\JsonResponse {
        if ($this->canAccessOrder($order, $user, $telegramId)) {
            return null;
        }

        Log::warning('Order access denied', [
            'context' => $context,
            'order_id' => $order->id,
            'order_order_id' => $order->order_id,
            'order_user_id' => $order->user_id,
            'order_telegram_id' => $order->telegram_id,
            'request_user_id' => $user?->id,
            'request_telegram_id' => $telegramId,
        ]);

        return response()->json([
            'message' => 'Доступ запрещен: заказ не принадлежит вам',
        ], 403);
    }
}
