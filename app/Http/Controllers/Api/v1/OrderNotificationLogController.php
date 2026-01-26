<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\OrderNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderNotificationLogController extends Controller
{
    /**
     * Получить историю уведомлений о заказах
     * 
     * GET /api/v1/order-notification-logs
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrderNotification::with(['order', 'telegramUser'])
            ->orderBy('created_at', 'desc');

        // Фильтр по типу уведомления
        if ($request->has('type') && $request->type) {
            $query->where('notification_type', $request->type);
        }

        // Фильтр по статусу
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Фильтр по заказу
        if ($request->has('order_id') && $request->order_id) {
            $query->where('order_id', $request->order_id);
        }

        // Фильтр по пользователю
        if ($request->has('telegram_user_id') && $request->telegram_user_id) {
            $query->where('telegram_user_id', $request->telegram_user_id);
        }

        // Поиск по ID заказа
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('order', function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%");
            });
        }

        // Пагинация
        $perPage = min((int) ($request->per_page ?? 50), 100);
        $notifications = $query->paginate($perPage);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Получить статистику уведомлений
     * 
     * GET /api/v1/order-notification-logs/stats
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => OrderNotification::count(),
            'active' => OrderNotification::where('status', OrderNotification::STATUS_ACTIVE)->count(),
            'updated' => OrderNotification::where('status', OrderNotification::STATUS_UPDATED)->count(),
            'deleted' => OrderNotification::where('status', OrderNotification::STATUS_DELETED)->count(),
            'by_type' => OrderNotification::select('notification_type', DB::raw('count(*) as count'))
                ->groupBy('notification_type')
                ->pluck('count', 'notification_type')
                ->toArray(),
        ];

        return response()->json(['data' => $stats]);
    }
}
