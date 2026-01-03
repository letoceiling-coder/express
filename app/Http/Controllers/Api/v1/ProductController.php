<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Получить список товаров
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = Product::query()->with(['category', 'image', 'video']);

        // Фильтрация по категории
        if ($request->has('category_id')) {
            $query->inCategory($request->get('category_id'));
        }

        // Фильтрация по статусу доступности
        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        // Фильтрация по наличию на складе
        if ($request->has('in_stock')) {
            if ($request->boolean('in_stock')) {
                $query->where('stock_quantity', '>', 0);
            } else {
                $query->where('stock_quantity', '<=', 0);
            }
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        
        // Специальная сортировка по цене
        if ($sortBy === 'price') {
            $query->orderBy('price', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Пагинация
        $perPage = $request->get('per_page', 15);
        if ($perPage > 0) {
            $products = $query->paginate($perPage);
        } else {
            $products = $query->get();
        }

        return response()->json([
            'data' => $products,
        ]);
    }

    /**
     * Получить детали товара
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $product = Product::with(['category', 'image', 'video'])->findOrFail($id);
        
        // Добавляем галерею вручную, так как это accessor
        $product->gallery = $product->gallery;

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * Создать товар
     * 
     * @param ProductRequest $request
     * @return JsonResponse
     */
    public function store(ProductRequest $request)
    {
        try {
            DB::beginTransaction();

            $product = Product::create($request->validated());

            DB::commit();

            $product->load(['category', 'image', 'video']);
            $product->gallery = $product->gallery;

            return response()->json([
                'data' => $product,
                'message' => 'Товар успешно создан',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при создании товара: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при создании товара',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить товар
     * 
     * @param ProductRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(ProductRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($id);
            $product->update($request->validated());

            DB::commit();

            $product->load(['category', 'image', 'video']);
            $product->gallery = $product->gallery;

            return response()->json([
                'data' => $product,
                'message' => 'Товар успешно обновлен',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении товара: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при обновлении товара',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить товар
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json([
                'message' => 'Товар успешно удален',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении товара: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при удалении товара',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
