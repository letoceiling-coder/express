<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для управления статусами заказов
 */
class OrderStatusService
{
    /**
     * Допустимые переходы статусов для каждой роли
     */
    private const ALLOWED_TRANSITIONS = [
        'admin' => [
            Order::STATUS_NEW => [Order::STATUS_ACCEPTED],
            Order::STATUS_ACCEPTED => [Order::STATUS_SENT_TO_KITCHEN, Order::STATUS_COURIER_ASSIGNED],
        ],
        'kitchen' => [
            Order::STATUS_SENT_TO_KITCHEN => [Order::STATUS_KITCHEN_ACCEPTED],
            Order::STATUS_KITCHEN_ACCEPTED => [Order::STATUS_PREPARING],
            Order::STATUS_PREPARING => [Order::STATUS_READY_FOR_DELIVERY],
        ],
        'courier' => [
            Order::STATUS_COURIER_ASSIGNED => [Order::STATUS_IN_TRANSIT],
            Order::STATUS_IN_TRANSIT => [Order::STATUS_DELIVERED],
        ],
        'user' => [
            // Клиент может отменить заказ из любого статуса, кроме delivered и cancelled
            '*' => [Order::STATUS_CANCELLED],
        ],
    ];

    /**
     * Изменить статус заказа с логированием
     *
     * @param Order $order
     * @param string $newStatus
     * @param array $options Опции: role, changed_by_user_id, changed_by_telegram_user_id, comment, metadata
     * @return bool
     */
    public function changeStatus(Order $order, string $newStatus, array $options = []): bool
    {
        try {
            DB::beginTransaction();

            $previousStatus = $order->status;
            $role = $options['role'] ?? null;
            $changedByUserId = $options['changed_by_user_id'] ?? null;
            $changedByTelegramUserId = $options['changed_by_telegram_user_id'] ?? null;
            $comment = $options['comment'] ?? null;
            $metadata = $options['metadata'] ?? null;

            // Проверяем возможность изменения статуса
            if ($role && !$this->canChangeStatus($order, $newStatus, $role)) {
                Log::warning('Status change not allowed', [
                    'order_id' => $order->id,
                    'current_status' => $order->status,
                    'new_status' => $newStatus,
                    'role' => $role,
                ]);
                DB::rollBack();
                return false;
            }

            // Обновляем статус заказа
            $order->status = $newStatus;
            $saved = $order->save();

            if (!$saved) {
                Log::error('Failed to save order status', [
                    'order_id' => $order->id,
                    'new_status' => $newStatus,
                ]);
                DB::rollBack();
                return false;
            }

            // Обновляем модель из БД, чтобы убедиться, что статус сохранен
            $order->refresh();

            // Проверяем, что статус действительно обновился
            if ($order->status !== $newStatus) {
                Log::error('Order status not updated correctly', [
                    'order_id' => $order->id,
                    'expected_status' => $newStatus,
                    'actual_status' => $order->status,
                ]);
                DB::rollBack();
                return false;
            }

            // Записываем в историю
            $this->logStatusChange($order, $newStatus, [
                'previous_status' => $previousStatus,
                'role' => $role,
                'changed_by_user_id' => $changedByUserId,
                'changed_by_telegram_user_id' => $changedByTelegramUserId,
                'comment' => $comment,
                'metadata' => $metadata,
            ]);

            DB::commit();

            Log::info('Order status changed successfully', [
                'order_id' => $order->id,
                'order_id_string' => $order->order_id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'actual_status' => $order->status,
                'role' => $role,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error changing order status: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Записать изменение статуса в историю
     *
     * @param Order $order
     * @param string $status
     * @param array $data
     * @return OrderStatusHistory
     */
    public function logStatusChange(Order $order, string $status, array $data = []): OrderStatusHistory
    {
        return OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status,
            'previous_status' => $data['previous_status'] ?? null,
            'changed_by_user_id' => $data['changed_by_user_id'] ?? null,
            'changed_by_telegram_user_id' => $data['changed_by_telegram_user_id'] ?? null,
            'role' => $data['role'] ?? null,
            'comment' => $data['comment'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Получить историю статусов заказа
     *
     * @param Order $order
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStatusHistory(Order $order)
    {
        return $order->statusHistory()->with(['changedByUser', 'changedByTelegramUser'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * Проверить возможность изменения статуса
     *
     * @param Order $order
     * @param string $newStatus
     * @param string $role
     * @return bool
     */
    public function canChangeStatus(Order $order, string $newStatus, string $role): bool
    {
        // Нельзя изменить статус доставлен или отменен
        if (in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])) {
            return false;
        }

        // Если статус не меняется
        if ($order->status === $newStatus) {
            return false;
        }

        // Проверяем допустимые переходы для роли
        if (!isset(self::ALLOWED_TRANSITIONS[$role])) {
            return false;
        }

        $allowedTransitions = self::ALLOWED_TRANSITIONS[$role];

        // Для роли user проверяем специальный случай (отмена из любого статуса)
        if ($role === 'user' && $newStatus === Order::STATUS_CANCELLED) {
            return !in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED]);
        }

        // Проверяем переход для текущего статуса
        if (isset($allowedTransitions[$order->status])) {
            return in_array($newStatus, $allowedTransitions[$order->status]);
        }

        return false;
    }
}

