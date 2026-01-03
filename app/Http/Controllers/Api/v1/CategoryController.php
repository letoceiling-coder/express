<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Получить список категорий
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Category::query()->with('image');

        // Фильтрация по статусу
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        if ($perPage > 0) {
            $categories = $query->paginate($perPage);
        } else {
            $categories = $query->get();
        }

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Получить детали категории
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $category = Category::with('image')->findOrFail($id);

        return response()->json([
            'data' => $category,
        ]);
    }

    /**
     * Создать категорию
     * 
     * @param CategoryRequest $request
     * @return JsonResponse
     */
    public function store(CategoryRequest $request)
    {
        try {
            DB::beginTransaction();

            $category = Category::create($request->validated());

            DB::commit();

            $category->load('image');

            return response()->json([
                'data' => $category,
                'message' => 'Категория успешно создана',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при создании категории: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при создании категории',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить категорию
     * 
     * @param CategoryRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(CategoryRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $category = Category::findOrFail($id);
            $category->update($request->validated());

            DB::commit();

            $category->load('image');

            return response()->json([
                'data' => $category,
                'message' => 'Категория успешно обновлена',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении категории: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при обновлении категории',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить категорию
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->delete();

            return response()->json([
                'message' => 'Категория успешно удалена',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении категории: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при удалении категории',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
