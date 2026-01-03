<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryRequest;
use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    /**
     * Получить список доставок
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = Delivery::query()->with('order');

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Фильтрация по типу доставки
        if ($request->has('delivery_type')) {
            $query->where('delivery_type', $request->get('delivery_type'));
        }

        // Фильтрация по заказу
        if ($request->has('order_id')) {
            $query->where('order_id', $request->get('order_id'));
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('courier_name', 'like', "%{$search}%")
                  ->orWhere('courier_phone', 'like', "%{$search}%")
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
            $deliveries = $query->paginate($perPage);
        } else {
            $deliveries = $query->get();
        }

        return response()->json([
            'data' => $deliveries,
        ]);
    }

    /**
     * Получить детали доставки
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $delivery = Delivery::with('order.items')->findOrFail($id);

        return response()->json([
            'data' => $delivery,
        ]);
    }

    /**
     * Создать доставку
     * 
     * @param DeliveryRequest $request
     * @return JsonResponse
     */
    public function store(DeliveryRequest $request)
    {
        try {
            DB::beginTransaction();

            $delivery = Delivery::create($request->validated());

            DB::commit();

            $delivery->load('order');

            return response()->json([
                'data' => $delivery,
                'message' => 'Доставка успешно создана',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при создании доставки: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при создании доставки',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить доставку
     * 
     * @param DeliveryRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(DeliveryRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $delivery = Delivery::findOrFail($id);
            $delivery->update($request->validated());

            DB::commit();

            $delivery->load('order');

            return response()->json([
                'data' => $delivery,
                'message' => 'Доставка успешно обновлена',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении доставки: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при обновлении доставки',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Изменить статус доставки
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
                'in:pending,assigned,in_transit,delivered,failed,returned',
            ],
        ]);

        try {
            $delivery = Delivery::findOrFail($id);
            $delivery->status = $request->get('status');
            $delivery->save();

            $delivery->load('order');

            return response()->json([
                'data' => $delivery,
                'message' => 'Статус доставки успешно обновлен',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при изменении статуса доставки: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при изменении статуса доставки',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить доставку для заказа
     * 
     * @param int $orderId
     * @return JsonResponse
     */
    public function getByOrder($orderId)
    {
        $order = Order::findOrFail($orderId);
        $delivery = Delivery::where('order_id', $orderId)->with('order')->first();

        return response()->json([
            'data' => $delivery,
            'order' => [
                'id' => $order->id,
                'order_id' => $order->order_id,
            ],
        ]);
    }

    /**
     * Удалить доставку
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $delivery = Delivery::findOrFail($id);
            $delivery->delete();

            return response()->json([
                'message' => 'Доставка успешно удалена',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении доставки: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при удалении доставки',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
