<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    /**
     * Получить список отзывов
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = Review::query()->with(['order', 'product', 'respondedBy', 'moderatedBy']);

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Фильтрация по товару
        if ($request->has('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        // Фильтрация по заказу
        if ($request->has('order_id')) {
            $query->where('order_id', $request->get('order_id'));
        }

        // Фильтрация по рейтингу
        if ($request->has('rating')) {
            $query->where('rating', $request->get('rating'));
        }

        // Фильтрация только одобренных
        if ($request->boolean('approved_only')) {
            $query->approved();
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('comment', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        if ($perPage > 0) {
            $reviews = $query->paginate($perPage);
        } else {
            $reviews = $query->get();
        }

        return response()->json([
            'data' => $reviews,
        ]);
    }

    /**
     * Получить детали отзыва
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $review = Review::with(['order.items', 'product', 'respondedBy', 'moderatedBy'])->findOrFail($id);

        return response()->json([
            'data' => $review,
        ]);
    }

    /**
     * Создать отзыв
     * 
     * @param ReviewRequest $request
     * @return JsonResponse
     */
    public function store(ReviewRequest $request)
    {
        try {
            DB::beginTransaction();

            // Проверка уникальности отзыва
            $existingReview = Review::where(function ($query) use ($request) {
                if ($request->has('order_id')) {
                    $query->where('order_id', $request->get('order_id'));
                }
                if ($request->has('product_id')) {
                    $query->orWhere('product_id', $request->get('product_id'));
                }
            })
            ->where('customer_email', $request->get('customer_email'))
            ->first();

            if ($existingReview) {
                return response()->json([
                    'message' => 'Вы уже оставили отзыв на этот заказ/товар',
                ], 422);
            }

            $review = Review::create($request->validated());

            DB::commit();

            $review->load(['order', 'product']);

            return response()->json([
                'data' => $review,
                'message' => 'Отзыв успешно создан',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при создании отзыва: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при создании отзыва',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить отзыв
     * 
     * @param ReviewRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(ReviewRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $review = Review::findOrFail($id);
            $review->update($request->validated());

            DB::commit();

            $review->load(['order', 'product', 'respondedBy', 'moderatedBy']);

            return response()->json([
                'data' => $review,
                'message' => 'Отзыв успешно обновлен',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении отзыва: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при обновлении отзыва',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Изменить статус отзыва (модерация)
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
                'in:pending,approved,rejected,hidden',
            ],
        ]);

        try {
            $review = Review::findOrFail($id);
            $review->status = $request->get('status');
            $review->moderated_by = Auth::id();
            $review->moderated_at = now();
            $review->save();

            $review->load(['order', 'product', 'respondedBy', 'moderatedBy']);

            return response()->json([
                'data' => $review,
                'message' => 'Статус отзыва успешно обновлен',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при изменении статуса отзыва: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при изменении статуса отзыва',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Добавить ответ на отзыв
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function addResponse(Request $request, $id)
    {
        $request->validate([
            'response' => ['required', 'string', 'min:10', 'max:65535'],
        ]);

        try {
            $review = Review::findOrFail($id);
            $review->response = $request->get('response');
            $review->responded_by = Auth::id();
            $review->responded_at = now();
            $review->save();

            $review->load(['order', 'product', 'respondedBy', 'moderatedBy']);

            return response()->json([
                'data' => $review,
                'message' => 'Ответ на отзыв успешно добавлен',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при добавлении ответа на отзыв: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при добавлении ответа на отзыв',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить отзывы для товара
     * 
     * @param int $productId
     * @return JsonResponse
     */
    public function getByProduct($productId)
    {
        $product = Product::findOrFail($productId);
        $reviews = Review::where('product_id', $productId)
            ->approved()
            ->with(['order', 'respondedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $reviews,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
            ],
        ]);
    }

    /**
     * Получить отзыв для заказа
     * 
     * @param int $orderId
     * @return JsonResponse
     */
    public function getByOrder($orderId)
    {
        $order = Order::findOrFail($orderId);
        $review = Review::where('order_id', $orderId)->with(['product', 'respondedBy'])->first();

        return response()->json([
            'data' => $review,
            'order' => [
                'id' => $order->id,
                'order_id' => $order->order_id,
            ],
        ]);
    }

    /**
     * Удалить отзыв
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->delete();

            return response()->json([
                'message' => 'Отзыв успешно удален',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении отзыва: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при удалении отзыва',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
