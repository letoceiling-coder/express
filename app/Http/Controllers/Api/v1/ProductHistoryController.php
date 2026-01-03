<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductHistoryController extends Controller
{
    /**
     * Получить историю изменений товара
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function index(Request $request, $id)
    {
        // Проверяем существование товара
        $product = Product::findOrFail($id);

        $query = ProductHistory::where('product_id', $id)
            ->with('user')
            ->orderBy('created_at', 'desc');

        // Фильтрация по типу действия
        if ($request->has('action')) {
            $query->where('action', $request->get('action'));
        }

        // Фильтрация по полю
        if ($request->has('field_name')) {
            $query->where('field_name', $request->get('field_name'));
        }

        // Пагинация
        $perPage = $request->get('per_page', 20);
        if ($perPage > 0) {
            $history = $query->paginate($perPage);
        } else {
            $history = $query->get();
        }

        return response()->json([
            'data' => $history,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
            ],
        ]);
    }

    /**
     * Получить детальную информацию о записи истории
     * 
     * @param int $productId
     * @param int $historyId
     * @return JsonResponse
     */
    public function show($productId, $historyId)
    {
        $product = Product::findOrFail($productId);
        $history = ProductHistory::where('product_id', $productId)
            ->with('user')
            ->findOrFail($historyId);

        return response()->json([
            'data' => $history,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
            ],
        ]);
    }
}
