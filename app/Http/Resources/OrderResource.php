<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Null-safe Order resource for API responses
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->resource;
        if (!$order) {
            return [];
        }

        $items = [];
        if ($order->relationLoaded('items')) {
            $items = collect($order->items ?? [])
                ->map(fn ($item) => $this->formatOrderItem($item))
                ->filter()
                ->values()
                ->all();
        }

        return [
            'id' => $order->id,
            'order_id' => $order->order_id,
            'telegram_id' => $order->telegram_id,
            'user_id' => $order->user_id,
            'status' => $order->status,
            'phone' => $order->phone,
            'name' => $order->name,
            'email' => $order->email,
            'delivery_address' => $order->delivery_address,
            'delivery_type' => $order->delivery_type,
            'delivery_time' => $order->delivery_time,
            'delivery_date' => $order->delivery_date?->format('Y-m-d'),
            'delivery_cost' => (float) $order->delivery_cost,
            'comment' => $order->comment,
            'notes' => $order->notes,
            'total_amount' => (float) $order->total_amount,
            'payment_id' => $order->payment_id,
            'payment_status' => $order->payment_status ?? 'pending',
            'payment_method' => $order->payment_method,
            'manager_id' => $order->manager_id,
            'bot_id' => $order->bot_id,
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
            'items' => $items,
            'user' => $order->relationLoaded('user') ? $this->formatUser($order->user) : null,
            'manager' => $order->relationLoaded('manager') ? $this->formatUser($order->manager) : null,
            'bot' => $order->relationLoaded('bot') && $order->bot ? [
                'id' => $order->bot->id,
                'username' => $order->bot->username ?? null,
            ] : null,
            'payments' => $order->relationLoaded('payments') ? collect($order->payments ?? [])->map(fn ($p) => [
                'id' => $p->id ?? null,
                'status' => $p->status ?? null,
            ])->all() : null,
        ];
    }

    protected function formatOrderItem(mixed $item): ?array
    {
        if (!$item) {
            return null;
        }
        return [
            'id' => $item->id ?? null,
            'product_id' => $item->product_id ?? null,
            'product_name' => $item->product_name ?? '',
            'product_image' => $item->product_image ?? null,
            'quantity' => (int) ($item->quantity ?? 0),
            'unit_price' => (float) ($item->unit_price ?? 0),
            'total' => (float) ($item->total ?? 0),
        ];
    }

    protected function formatUser(mixed $user): ?array
    {
        if (!$user) {
            return null;
        }
        return [
            'id' => $user->id ?? null,
            'name' => $user->name ?? null,
            'email' => $user->email ?? null,
        ];
    }
}
