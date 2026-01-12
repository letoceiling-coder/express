<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    /**
     * Обновить позиции товаров (drag & drop)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePositions(Request $request)
    {
        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.sort_order' => 'required|integer|min:0'
        ], [
            'products.required' => 'Массив товаров обязателен',
            'products.array' => 'Товары должны быть переданы в виде массива',
            'products.*.id.required' => 'ID товара обязателен',
            'products.*.id.exists' => 'Товар с указанным ID не найден',
            'products.*.sort_order.required' => 'Порядок сортировки обязателен',
            'products.*.sort_order.integer' => 'Порядок сортировки должен быть целым числом'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->products as $productData) {
                Product::where('id', $productData['id'])->update([
                    'sort_order' => $productData['sort_order']
                ]);
            }

            DB::commit();

            Log::info('Product positions updated', [
                'count' => count($request->products),
                'products' => $request->products
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Позиции товаров успешно обновлены'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Product positions update error', [
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
     * Экспорт товаров в CSV
     * 
     * @return BinaryFileResponse
     */
    public function exportCsv(): BinaryFileResponse
    {
        $fileName = 'products_' . date('Y-m-d') . '.csv';
        
        // Создаем временный файл с UTF-8 BOM для правильного отображения в Excel
        $tempFile = tempnam(sys_get_temp_dir(), 'products_export_');
        $writer = \Maatwebsite\Excel\Facades\Excel::raw(new ProductsExport, \Maatwebsite\Excel\Excel::CSV);
        
        // Добавляем UTF-8 BOM в начало файла
        file_put_contents($tempFile, "\xEF\xBB\xBF" . $writer);
        
        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Экспорт товаров в Excel
     * 
     * @return BinaryFileResponse
     */
    public function exportExcel(): BinaryFileResponse
    {
        $fileName = 'products_' . date('Y-m-d') . '.xlsx';
        return Excel::download(new ProductsExport, $fileName);
    }

    /**
     * Экспорт товаров в ZIP с фото
     * 
     * @return BinaryFileResponse|JsonResponse
     */
    public function exportZip()
    {
        try {
            $tempDir = storage_path('app/temp/export_' . time());
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Экспортируем CSV
            $csvFileName = 'products_' . date('Y-m-d') . '.csv';
            $csvPath = $tempDir . '/' . $csvFileName;
            
            $writer = \Maatwebsite\Excel\Facades\Excel::raw(new ProductsExport, \Maatwebsite\Excel\Excel::CSV);
            // Добавляем UTF-8 BOM
            file_put_contents($csvPath, "\xEF\xBB\xBF" . $writer);

            // Создаем папку для изображений
            $imagesDir = $tempDir . '/images';
            if (!file_exists($imagesDir)) {
                mkdir($imagesDir, 0755, true);
            }

            // Получаем все товары с изображениями
            // gallery - это accessor, не отношение, поэтому не используем with()
            $products = Product::with(['image'])->get();
            $imageCount = 0;

            foreach ($products as $product) {
                // Главное изображение
                if ($product->image && $product->image->path) {
                    $imagePath = public_path($product->image->path);
                    if (file_exists($imagePath) && is_file($imagePath)) {
                        $imageName = $product->image->original_name ?? $product->image->name;
                        // Если имя файла уже используется, добавляем ID товара
                        $destPath = $imagesDir . '/' . $imageName;
                        if (file_exists($destPath)) {
                            $ext = pathinfo($imageName, PATHINFO_EXTENSION);
                            $name = pathinfo($imageName, PATHINFO_FILENAME);
                            $imageName = $name . '_' . $product->id . '.' . $ext;
                            $destPath = $imagesDir . '/' . $imageName;
                        }
                        copy($imagePath, $destPath);
                        $imageCount++;
                    }
                }

                // Галерея (получаем через accessor)
                $gallery = $product->gallery; // Это вызовет getGalleryAttribute()
                if ($gallery && $gallery->count() > 0) {
                    foreach ($gallery as $galleryImage) {
                        if ($galleryImage->path) {
                            $imagePath = public_path($galleryImage->path);
                            if (file_exists($imagePath) && is_file($imagePath)) {
                                $imageName = $galleryImage->original_name ?? $galleryImage->name;
                                // Если имя файла уже используется, добавляем ID товара и media
                                $destPath = $imagesDir . '/' . $imageName;
                                if (file_exists($destPath)) {
                                    $ext = pathinfo($imageName, PATHINFO_EXTENSION);
                                    $name = pathinfo($imageName, PATHINFO_FILENAME);
                                    $imageName = $name . '_' . $product->id . '_' . $galleryImage->id . '.' . $ext;
                                    $destPath = $imagesDir . '/' . $imageName;
                                }
                                copy($imagePath, $destPath);
                                $imageCount++;
                            }
                        }
                    }
                }
            }

            // Создаем ZIP архив
            $zipFileName = 'products_' . date('Y-m-d') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFileName);
            
            // Убеждаемся, что директория существует
            $zipDir = dirname($zipPath);
            if (!file_exists($zipDir)) {
                mkdir($zipDir, 0755, true);
            }
            
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
                // Добавляем CSV файл
                $zip->addFile($csvPath, $csvFileName);
                
                // Добавляем папку с изображениями
                if ($imageCount > 0) {
                    // Сначала добавляем саму папку images (пустую директорию)
                    $zip->addEmptyDir('images');
                    
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($imagesDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::SELF_FIRST
                    );
                    
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $filePath = $file->getRealPath();
                            // Получаем относительный путь от imagesDir
                            $relativePath = str_replace($imagesDir . DIRECTORY_SEPARATOR, '', $filePath);
                            // Добавляем с префиксом images/
                            $zip->addFile($filePath, 'images/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath));
                        }
                    }
                }
                
                $zip->close();
            } else {
                throw new \Exception('Не удалось создать ZIP архив');
            }

            // Удаляем временную директорию
            $this->deleteDirectory($tempDir);

            return response()->download($zipPath, $zipFileName, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Ошибка при экспорте товаров в ZIP: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Удаляем временную директорию в случае ошибки
            if (isset($tempDir) && file_exists($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при экспорте товаров в ZIP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Рекурсивное удаление директории
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Импорт товаров из CSV/Excel с поддержкой загрузки фото
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // Максимум 10MB
            'images_archive' => 'nullable|file|mimes:zip|max:51200', // Максимум 50MB для архива с изображениями
        ], [
            'file.required' => 'Файл обязателен для загрузки',
            'file.file' => 'Загруженный файл не является файлом',
            'file.mimes' => 'Файл должен быть в формате CSV или Excel',
            'file.max' => 'Размер файла не должен превышать 10MB',
            'images_archive.mimes' => 'Архив с изображениями должен быть в формате ZIP',
            'images_archive.max' => 'Размер архива с изображениями не должен превышать 50MB',
        ]);

        try {
            $file = $request->file('file');
            $imagesArchive = $request->file('images_archive');
            
            $import = new ProductsImport($imagesArchive);
            
            Excel::import($import, $file);

            $errors = $import->getErrors();
            $message = 'Товары успешно импортированы';
            if (!empty($errors)) {
                $message .= '. Ошибки: ' . implode(', ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= ' и еще ' . (count($errors) - 5) . ' ошибок';
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'errors' => $errors,
                'errors_count' => count($errors),
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при импорте товаров: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при импорте товаров: ' . $e->getMessage(),
            ], 500);
        }
    }
}
