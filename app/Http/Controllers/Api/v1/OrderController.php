<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Получить список заказов
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = Order::query()->with(['items', 'manager', 'bot']);

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Фильтрация по статусу оплаты
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }

        // Фильтрация по telegram_id
        if ($request->has('telegram_id')) {
            $query->where('telegram_id', $request->get('telegram_id'));
        }

        // Фильтрация по менеджеру
        if ($request->has('manager_id')) {
            $query->where('manager_id', $request->get('manager_id'));
        }

        // Фильтрация по дате создания
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('delivery_address', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        if ($perPage > 0) {
            $orders = $query->paginate($perPage);
        } else {
            $orders = $query->get();
        }

        return response()->json([
            'data' => $orders,
        ]);
    }

    /**
     * Получить детали заказа
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $order = Order::with(['items.product', 'manager', 'bot'])->findOrFail($id);

        return response()->json([
            'data' => $order,
        ]);
    }

    /**
     * Обновить заказ
     * 
     * @param UpdateOrderRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateOrderRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $order = Order::findOrFail($id);
            $order->update($request->validated());

            DB::commit();

            $order->load(['items.product', 'manager', 'bot']);

            return response()->json([
                'data' => $order,
                'message' => 'Заказ успешно обновлен',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении заказа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при обновлении заказа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Изменить статус заказа
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => [
                'required',
                'string',
                'in:new,accepted,preparing,ready_for_delivery,in_transit,delivered,cancelled',
            ],
        ]);

        try {
            $order = Order::findOrFail($id);

            if (!$order->canChangeStatus($request->get('status'))) {
                return response()->json([
                    'message' => 'Нельзя изменить статус заказа, который уже доставлен или отменен',
                ], 422);
            }

            $order->status = $request->get('status');
            $order->save();

            $order->load(['items.product', 'manager', 'bot']);

            return response()->json([
                'data' => $order,
                'message' => 'Статус заказа успешно обновлен',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при изменении статуса заказа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при изменении статуса заказа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить заказ (soft delete)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->delete();

            return response()->json([
                'message' => 'Заказ успешно удален',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении заказа: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при удалении заказа',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
