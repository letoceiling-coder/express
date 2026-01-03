<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReturnRequest;
use App\Models\Order;
use App\Models\ProductReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReturnController extends Controller
{
    /**
     * Получить список возвратов
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = ProductReturn::query()->with(['order', 'processedBy']);

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Фильтрация по типу причины
        if ($request->has('reason_type')) {
            $query->where('reason_type', $request->get('reason_type'));
        }

        // Фильтрация по заказу
        if ($request->has('order_id')) {
            $query->where('order_id', $request->get('order_id'));
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($orderQuery) use ($search) {
                      $orderQuery->where('order_id', 'like', "%{$search}%");
                  });
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        if ($perPage > 0) {
            $returns = $query->paginate($perPage);
        } else {
            $returns = $query->get();
        }

        return response()->json([
            'data' => $returns,
        ]);
    }

    /**
     * Получить детали возврата
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $return = ProductReturn::with(['order.items', 'processedBy'])->findOrFail($id);

        return response()->json([
            'data' => $return,
        ]);
    }

    /**
     * Создать возврат
     * 
     * @param ReturnRequest $request
     * @return JsonResponse
     */
    public function store(ReturnRequest $request)
    {
        try {
            DB::beginTransaction();

            // Проверяем существование заказа
            $order = Order::with('items')->findOrFail($request->get('order_id'));

            // Валидация количества товаров
            $requestItems = $request->get('items', []);
            foreach ($requestItems as $returnItem) {
                $orderItem = $order->items->firstWhere('product_id', $returnItem['product_id']);
                if (!$orderItem || $returnItem['quantity'] > $orderItem->quantity) {
                    return response()->json([
                        'message' => 'Количество возвращаемых товаров превышает количество в заказе',
                    ], 422);
                }
            }

            $return = ProductReturn::create($request->validated());

            DB::commit();

            $return->load(['order', 'processedBy']);

            return response()->json([
                'data' => $return,
                'message' => 'Заявка на возврат успешно создана',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при создании возврата: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при создании возврата',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить возврат
     * 
     * @param ReturnRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(ReturnRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $return = ProductReturn::findOrFail($id);
            $return->update($request->validated());

            DB::commit();

            $return->load(['order', 'processedBy']);

            return response()->json([
                'data' => $return,
                'message' => 'Возврат успешно обновлен',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении возврата: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при обновлении возврата',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Изменить статус возврата
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
                'in:pending,approved,rejected,in_transit,received,refunded,cancelled',
            ],
        ]);

        try {
            $return = ProductReturn::findOrFail($id);
            $return->status = $request->get('status');
            
            if (in_array($request->get('status'), [ProductReturn::STATUS_APPROVED, ProductReturn::STATUS_REJECTED])) {
                $return->processed_by = Auth::id();
                $return->processed_at = now();
            }
            
            $return->save();

            $return->load(['order', 'processedBy']);

            return response()->json([
                'data' => $return,
                'message' => 'Статус возврата успешно обновлен',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при изменении статуса возврата: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при изменении статуса возврата',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Одобрить возврат
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function approve($id)
    {
        try {
            $return = ProductReturn::findOrFail($id);
            $return->status = ProductReturn::STATUS_APPROVED;
            $return->processed_by = Auth::id();
            $return->processed_at = now();
            $return->save();

            $return->load(['order', 'processedBy']);

            return response()->json([
                'data' => $return,
                'message' => 'Возврат одобрен',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при одобрении возврата: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при одобрении возврата',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Отклонить возврат
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:65535'],
        ]);

        try {
            $return = ProductReturn::findOrFail($id);
            $return->status = ProductReturn::STATUS_REJECTED;
            $return->processed_by = Auth::id();
            $return->processed_at = now();
            
            if ($request->has('notes')) {
                $return->notes = $request->get('notes');
            }
            
            $return->save();

            $return->load(['order', 'processedBy']);

            return response()->json([
                'data' => $return,
                'message' => 'Возврат отклонен',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при отклонении возврата: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при отклонении возврата',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить возвраты для заказа
     * 
     * @param int $orderId
     * @return JsonResponse
     */
    public function getByOrder($orderId)
    {
        $order = Order::findOrFail($orderId);
        $returns = ProductReturn::where('order_id', $orderId)->with('processedBy')->get();

        return response()->json([
            'data' => $returns,
            'order' => [
                'id' => $order->id,
                'order_id' => $order->order_id,
            ],
        ]);
    }

    /**
     * Удалить возврат
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $return = ProductReturn::findOrFail($id);
            $return->delete();

            return response()->json([
                'message' => 'Возврат успешно удален',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении возврата: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при удалении возврата',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
