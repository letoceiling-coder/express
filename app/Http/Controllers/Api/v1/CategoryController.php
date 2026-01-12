<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Exports\CategoriesExport;
use App\Imports\CategoriesImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $perPage = (int) $request->get('per_page', 15);
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

    /**
     * Обновить позиции категорий (drag & drop)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePositions(Request $request)
    {
        $request->validate([
            'categories' => 'required|array|min:1',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0'
        ], [
            'categories.required' => 'Массив категорий обязателен',
            'categories.array' => 'Категории должны быть переданы в виде массива',
            'categories.*.id.required' => 'ID категории обязателен',
            'categories.*.id.exists' => 'Категория с указанным ID не найдена',
            'categories.*.sort_order.required' => 'Порядок сортировки обязателен',
            'categories.*.sort_order.integer' => 'Порядок сортировки должен быть целым числом'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->categories as $categoryData) {
                Category::where('id', $categoryData['id'])->update([
                    'sort_order' => $categoryData['sort_order']
                ]);
            }

            DB::commit();

            Log::info('Category positions updated', [
                'count' => count($request->categories),
                'categories' => $request->categories
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Позиции категорий успешно обновлены'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Category positions update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления позиций: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Экспорт категорий в CSV
     * 
     * @return BinaryFileResponse
     */
    public function exportCsv(): BinaryFileResponse
    {
        $fileName = 'categories_' . date('Y-m-d_His') . '.csv';
        return Excel::download(new CategoriesExport, $fileName, \Maatwebsite\Excel\Excel::CSV, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Экспорт категорий в Excel
     * 
     * @return BinaryFileResponse
     */
    public function exportExcel(): BinaryFileResponse
    {
        $fileName = 'categories_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new CategoriesExport, $fileName);
    }

    /**
     * Импорт категорий из CSV/Excel
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // Максимум 10MB
        ], [
            'file.required' => 'Файл обязателен для загрузки',
            'file.file' => 'Загруженный файл не является файлом',
            'file.mimes' => 'Файл должен быть в формате CSV или Excel',
            'file.max' => 'Размер файла не должен превышать 10MB',
        ]);

        try {
            $file = $request->file('file');
            $import = new CategoriesImport();
            
            Excel::import($import, $file);

            return response()->json([
                'success' => true,
                'message' => 'Категории успешно импортированы',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при импорте категорий: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при импорте категорий: ' . $e->getMessage(),
            ], 500);
        }
    }
}
