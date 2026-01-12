<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Media;
use App\Models\Folder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CategoriesImport implements ToModel, WithHeadingRow, WithValidation
{
    private $generalFolderId = null;

    public function __construct()
    {
        // Получаем папку "Общая"
        $generalFolder = Folder::where('name', 'Общая')->whereNull('parent_id')->first();
        if ($generalFolder) {
            $this->generalFolderId = $generalFolder->id;
        }
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            DB::beginTransaction();

            $data = [
                'name' => $row['название'] ?? $row['name'] ?? '',
                'slug' => $row['slug'] ?? Str::slug($row['название'] ?? $row['name'] ?? ''),
                'description' => $row['описание'] ?? $row['description'] ?? null,
                'sort_order' => (int)($row['порядок_сортировки'] ?? $row['sort_order'] ?? 0),
                'is_active' => $this->parseBoolean($row['активна'] ?? $row['is_active'] ?? true),
                'meta_title' => $row['meta_title'] ?? null,
                'meta_description' => $row['meta_description'] ?? null,
            ];

            // Обработка изображения по URL
            $imageUrl = $row['изображение_url'] ?? $row['image_url'] ?? $row['изображение'] ?? $row['image'] ?? null;
            if ($imageUrl) {
                $imageId = $this->processImageFromUrl($imageUrl);
                if ($imageId) {
                    $data['image_id'] = $imageId;
                }
            }

            // Проверяем, существует ли категория с таким slug
            $existingCategory = Category::where('slug', $data['slug'])->first();
            
            if ($existingCategory) {
                // Обновляем существующую категорию
                $existingCategory->update($data);
                DB::commit();
                return $existingCategory;
            } else {
                // Создаем новую категорию
                $category = Category::create($data);
                DB::commit();
                return $category;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при импорте категории: ' . $e->getMessage(), [
                'row' => $row,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Правила валидации
     */
    public function rules(): array
    {
        return [
            'название' => 'required|string|max:255',
            'name' => 'required_without:название|string|max:255',
        ];
    }

    /**
     * Обработка изображения по URL
     */
    private function processImageFromUrl(string $url): ?int
    {
        try {
            // Если URL относительный или абсолютный путь к файлу
            if (strpos($url, 'http') !== 0) {
                // Относительный путь
                $filePath = public_path(ltrim($url, '/'));
                if (file_exists($filePath) && is_file($filePath)) {
                    return $this->createMediaFromFile($filePath);
                }
                return null;
            }

            // Загружаем изображение по URL
            $imageContent = @file_get_contents($url);
            if ($imageContent === false) {
                Log::warning('Не удалось загрузить изображение по URL: ' . $url);
                return null;
            }

            // Определяем расширение из URL или заголовков
            $extension = $this->getExtensionFromUrl($url);
            if (!$extension) {
                $extension = 'jpg'; // По умолчанию
            }

            // Генерируем уникальное имя
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $uploadPath = 'upload';
            
            // Создаем директорию если не существует
            $fullPath = public_path($uploadPath);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Сохраняем файл
            $relativePath = $uploadPath . '/' . $fileName;
            $absolutePath = public_path($relativePath);
            file_put_contents($absolutePath, $imageContent);

            // Получаем размеры изображения
            $imageInfo = @getimagesize($absolutePath);
            $width = $imageInfo ? $imageInfo[0] : null;
            $height = $imageInfo ? $imageInfo[1] : null;

            // Обрабатываем изображение через ImageService
            $imageService = app(\App\Services\ImageService::class);
            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            $imageVariants = $imageService->processImage(
                $absolutePath,
                $extension,
                $uploadPath,
                $baseName
            );

            // Создаем запись в media
            $metadata = [
                'path' => $relativePath,
                'mime_type' => $imageInfo ? $imageInfo['mime'] : 'image/jpeg',
            ];

            if ($imageVariants) {
                $metadata['webp_path'] = $imageVariants['webp'] ?? null;
                $metadata['variants'] = $imageVariants['variants'] ?? [];
            }

            $media = Media::create([
                'name' => $fileName,
                'original_name' => basename($url),
                'extension' => $extension,
                'disk' => $uploadPath,
                'width' => $width,
                'height' => $height,
                'type' => 'photo',
                'size' => filesize($absolutePath),
                'folder_id' => $this->generalFolderId,
                'user_id' => auth()->check() ? auth()->id() : null,
                'temporary' => false,
                'metadata' => json_encode($metadata)
            ]);

            return $media->id;
        } catch (\Exception $e) {
            Log::error('Ошибка при обработке изображения из URL: ' . $e->getMessage(), [
                'url' => $url,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Создать media из локального файла
     */
    private function createMediaFromFile(string $filePath): ?int
    {
        try {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $originalName = basename($filePath);
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $uploadPath = 'upload';
            
            // Создаем директорию если не существует
            $fullPath = public_path($uploadPath);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Копируем файл
            $relativePath = $uploadPath . '/' . $fileName;
            $absolutePath = public_path($relativePath);
            copy($filePath, $absolutePath);

            // Получаем размеры изображения
            $imageInfo = @getimagesize($absolutePath);
            $width = $imageInfo ? $imageInfo[0] : null;
            $height = $imageInfo ? $imageInfo[1] : null;

            // Обрабатываем изображение через ImageService
            $imageService = app(\App\Services\ImageService::class);
            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            $imageVariants = $imageService->processImage(
                $absolutePath,
                $extension,
                $uploadPath,
                $baseName
            );

            // Создаем запись в media
            $metadata = [
                'path' => $relativePath,
                'mime_type' => $imageInfo ? $imageInfo['mime'] : 'image/jpeg',
            ];

            if ($imageVariants) {
                $metadata['webp_path'] = $imageVariants['webp'] ?? null;
                $metadata['variants'] = $imageVariants['variants'] ?? [];
            }

            $media = Media::create([
                'name' => $fileName,
                'original_name' => $originalName,
                'extension' => $extension,
                'disk' => $uploadPath,
                'width' => $width,
                'height' => $height,
                'type' => 'photo',
                'size' => filesize($absolutePath),
                'folder_id' => $this->generalFolderId,
                'user_id' => auth()->check() ? auth()->id() : null,
                'temporary' => false,
                'metadata' => json_encode($metadata)
            ]);

            return $media->id;
        } catch (\Exception $e) {
            Log::error('Ошибка при создании media из файла: ' . $e->getMessage(), [
                'filePath' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Получить расширение из URL
     */
    private function getExtensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if ($extension) {
                return strtolower($extension);
            }
        }
        return null;
    }

    /**
     * Парсинг булевого значения
     */
    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            $value = mb_strtolower(trim($value));
            return in_array($value, ['да', 'yes', 'true', '1', '✓']);
        }
        return (bool)$value;
    }
}

