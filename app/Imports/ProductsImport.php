<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Media;
use App\Models\Folder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation
{
    private $generalFolderId = null;
    private $errors = [];
    private $imagesArchive = null;
    private $extractedImagesPath = null;

    public function __construct($imagesArchive = null)
    {
        // Получаем папку "Общая"
        $generalFolder = Folder::where('name', 'Общая')->whereNull('parent_id')->first();
        if ($generalFolder) {
            $this->generalFolderId = $generalFolder->id;
        }
        
        // Обрабатываем архив с изображениями если передан
        if ($imagesArchive) {
            $this->imagesArchive = $imagesArchive;
            $this->extractImagesArchive();
        }
    }

    /**
     * Извлечь изображения из ZIP архива
     */
    private function extractImagesArchive(): void
    {
        try {
            $zipPath = $this->imagesArchive->getRealPath();
            if (!file_exists($zipPath)) {
                throw new \Exception('Файл архива не найден');
            }

            $zip = new \ZipArchive();
            $result = $zip->open($zipPath);
            
            if ($result !== true) {
                $errorMessages = [
                    \ZipArchive::ER_OK => 'Нет ошибок',
                    \ZipArchive::ER_MULTIDISK => 'Многодисковый ZIP архив не поддерживается',
                    \ZipArchive::ER_RENAME => 'Ошибка переименования временного файла',
                    \ZipArchive::ER_CLOSE => 'Ошибка закрытия ZIP архива',
                    \ZipArchive::ER_SEEK => 'Ошибка поиска',
                    \ZipArchive::ER_READ => 'Ошибка чтения',
                    \ZipArchive::ER_WRITE => 'Ошибка записи',
                    \ZipArchive::ER_CRC => 'Ошибка CRC',
                    \ZipArchive::ER_ZIPCLOSED => 'ZIP архив закрыт',
                    \ZipArchive::ER_NOENT => 'Файл не найден',
                    \ZipArchive::ER_EXISTS => 'Файл уже существует',
                    \ZipArchive::ER_OPEN => 'Не удалось открыть файл',
                    \ZipArchive::ER_TMPOPEN => 'Ошибка создания временного файла',
                    \ZipArchive::ER_ZLIB => 'Ошибка Zlib',
                    \ZipArchive::ER_MEMORY => 'Ошибка памяти',
                    \ZipArchive::ER_CHANGED => 'Запись была изменена',
                    \ZipArchive::ER_COMPNOTSUPP => 'Метод сжатия не поддерживается',
                    \ZipArchive::ER_EOF => 'Неожиданный конец файла',
                    \ZipArchive::ER_INVAL => 'Неверный аргумент',
                    \ZipArchive::ER_NOZIP => 'Не ZIP архив',
                    \ZipArchive::ER_INTERNAL => 'Внутренняя ошибка',
                    \ZipArchive::ER_INCONS => 'Несогласованность ZIP архива',
                    \ZipArchive::ER_REMOVE => 'Не удалось удалить файл',
                    \ZipArchive::ER_DELETED => 'Запись была удалена',
                ];
                
                $errorMsg = $errorMessages[$result] ?? 'Неизвестная ошибка (код: ' . $result . ')';
                throw new \Exception('Не удалось открыть ZIP архив: ' . $errorMsg);
            }
            
            $extractPath = storage_path('app/temp/import_images_' . time());
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0755, true);
            }
            
            if (!$zip->extractTo($extractPath)) {
                $zip->close();
                throw new \Exception('Не удалось извлечь файлы из архива');
            }
            
            $zip->close();
            $this->extractedImagesPath = $extractPath;
            
            Log::info('ZIP архив успешно извлечен', [
                'path' => $extractPath,
                'files_count' => count(glob($extractPath . '/**/*', GLOB_BRACE))
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при извлечении архива с изображениями: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->errors[] = 'Не удалось извлечь архив с изображениями: ' . $e->getMessage();
        }
    }

    /**
     * Найти изображение в извлеченном архиве по имени файла
     */
    private function findImageInArchive(string $imageName): ?string
    {
        if (!$this->extractedImagesPath || !file_exists($this->extractedImagesPath)) {
            return null;
        }

        // Сначала проверяем папку images/ (стандартная структура экспорта)
        $imagesFolder = $this->extractedImagesPath . '/images';
        $searchPaths = [];
        
        if (file_exists($imagesFolder) && is_dir($imagesFolder)) {
            $searchPaths[] = $imagesFolder;
        }
        
        // Также ищем в корне архива (для обратной совместимости)
        $searchPaths[] = $this->extractedImagesPath;

        // Ищем файл в указанных путях
        foreach ($searchPaths as $searchPath) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($searchPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $fileName = $file->getFilename();
                    // Сравниваем без учета расширения
                    $imageNameWithoutExt = pathinfo($imageName, PATHINFO_FILENAME);
                    $fileNameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
                    
                    if (strcasecmp($imageNameWithoutExt, $fileNameWithoutExt) === 0) {
                        // Проверяем, что это изображение
                        $extension = strtolower($file->getExtension());
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            return $file->getRealPath();
                        }
                    }
                }
            }
        }

        return null;
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

            // Получаем категорию по ID или названию
            $categoryId = null;
            $categoryName = $row['категория_название'] ?? $row['category_name'] ?? null;
            $categoryIdValue = $row['категория_id'] ?? $row['category_id'] ?? null;
            
            if ($categoryIdValue) {
                $category = Category::find($categoryIdValue);
                if ($category) {
                    $categoryId = $category->id;
                }
            } elseif ($categoryName) {
                $category = Category::where('name', $categoryName)->first();
                if ($category) {
                    $categoryId = $category->id;
                }
            }

            $data = [
                'name' => $row['название'] ?? $row['name'] ?? '',
                'slug' => $row['slug'] ?? Str::slug($row['название'] ?? $row['name'] ?? ''),
                'description' => $row['описание'] ?? $row['description'] ?? null,
                'short_description' => $row['краткое_описание'] ?? $row['short_description'] ?? null,
                'price' => (float)($row['цена'] ?? $row['price'] ?? 0),
                'compare_price' => !empty($row['старая_цена'] ?? $row['compare_price']) ? (float)($row['старая_цена'] ?? $row['compare_price']) : null,
                'category_id' => $categoryId,
                'sku' => $row['артикул'] ?? $row['sku'] ?? null,
                'barcode' => $row['штрих_код'] ?? $row['barcode'] ?? null,
                'stock_quantity' => (int)($row['количество_на_складе'] ?? $row['stock_quantity'] ?? 0),
                'is_available' => $this->parseBoolean($row['доступен'] ?? $row['is_available'] ?? true),
                'is_weight_product' => $this->parseBoolean($row['весовой_товар'] ?? $row['is_weight_product'] ?? false),
                'weight' => !empty($row['вес'] ?? $row['weight']) ? (float)($row['вес'] ?? $row['weight']) : null,
                'sort_order' => (int)($row['порядок_сортировки'] ?? $row['sort_order'] ?? 0),
                'meta_title' => $row['meta_title'] ?? null,
                'meta_description' => $row['meta_description'] ?? null,
            ];

            // Обработка главного изображения
            $imageUrl = $row['изображение_url'] ?? $row['image_url'] ?? $row['изображение'] ?? $row['image'] ?? null;
            if ($imageUrl) {
                // Сначала проверяем, есть ли файл в архиве
                $archiveImagePath = $this->findImageInArchive(basename($imageUrl));
                if ($archiveImagePath) {
                    $imageId = $this->createMediaFromFile($archiveImagePath);
                } else {
                    // Если нет в архиве, пытаемся загрузить по URL
                    $imageId = $this->processImageFromUrl($imageUrl);
                }
                
                if ($imageId) {
                    $data['image_id'] = $imageId;
                }
            }

            // Обработка галереи (URLs через запятую)
            $galleryUrls = $row['галерея'] ?? $row['gallery'] ?? null;
            if ($galleryUrls) {
                $galleryIds = [];
                $urls = is_array($galleryUrls) ? $galleryUrls : explode(',', $galleryUrls);
                foreach ($urls as $url) {
                    $url = trim($url);
                    if (!empty($url)) {
                        // Сначала проверяем, есть ли файл в архиве
                        $archiveImagePath = $this->findImageInArchive(basename($url));
                        if ($archiveImagePath) {
                            $mediaId = $this->createMediaFromFile($archiveImagePath);
                        } else {
                            // Если нет в архиве, пытаемся загрузить по URL
                            $mediaId = $this->processImageFromUrl($url);
                        }
                        
                        if ($mediaId) {
                            $galleryIds[] = $mediaId;
                        }
                    }
                }
                if (!empty($galleryIds)) {
                    $data['gallery_ids'] = $galleryIds;
                }
            }

            // Обработка видео
            $videoUrl = $row['видео_url'] ?? $row['video_url'] ?? $row['видео'] ?? $row['video'] ?? null;
            if ($videoUrl) {
                $videoId = $this->processVideoFromUrl($videoUrl);
                if ($videoId) {
                    $data['video_id'] = $videoId;
                }
            }

            // Проверяем, существует ли товар с таким slug или SKU
            $existingProduct = null;
            if (!empty($data['sku'])) {
                $existingProduct = Product::where('sku', $data['sku'])->first();
            }
            if (!$existingProduct) {
                $existingProduct = Product::where('slug', $data['slug'])->first();
            }
            
            if ($existingProduct) {
                // Обновляем существующий товар
                $existingProduct->update($data);
                DB::commit();
                return $existingProduct;
            } else {
                // Создаем новый товар
                $product = Product::create($data);
                DB::commit();
                return $product;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMsg = 'Ошибка при импорте товара: ' . $e->getMessage();
            Log::error($errorMsg, [
                'row' => $row,
                'trace' => $e->getTraceAsString()
            ]);
            $this->errors[] = $errorMsg;
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
            'цена' => 'required|numeric|min:0',
            'price' => 'required_without:цена|numeric|min:0',
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
     * Обработка видео по URL
     */
    private function processVideoFromUrl(string $url): ?int
    {
        try {
            // Если URL относительный или абсолютный путь к файлу
            if (strpos($url, 'http') !== 0) {
                // Относительный путь
                $filePath = public_path(ltrim($url, '/'));
                if (file_exists($filePath) && is_file($filePath)) {
                    return $this->createVideoMediaFromFile($filePath);
                }
                return null;
            }

            // Загружаем видео по URL
            $videoContent = @file_get_contents($url);
            if ($videoContent === false) {
                Log::warning('Не удалось загрузить видео по URL: ' . $url);
                return null;
            }

            // Определяем расширение из URL
            $extension = $this->getExtensionFromUrl($url);
            if (!$extension) {
                $extension = 'mp4'; // По умолчанию
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
            file_put_contents($absolutePath, $videoContent);

            // Создаем запись в media
            $metadata = [
                'path' => $relativePath,
                'mime_type' => 'video/' . $extension,
            ];

            $media = Media::create([
                'name' => $fileName,
                'original_name' => basename($url),
                'extension' => $extension,
                'disk' => $uploadPath,
                'width' => null,
                'height' => null,
                'type' => 'video',
                'size' => filesize($absolutePath),
                'folder_id' => $this->generalFolderId,
                'user_id' => auth()->check() ? auth()->id() : null,
                'temporary' => false,
                'metadata' => json_encode($metadata)
            ]);

            return $media->id;
        } catch (\Exception $e) {
            Log::error('Ошибка при обработке видео из URL: ' . $e->getMessage(), [
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
     * Создать video media из локального файла
     */
    private function createVideoMediaFromFile(string $filePath): ?int
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

            // Создаем запись в media
            $metadata = [
                'path' => $relativePath,
                'mime_type' => 'video/' . $extension,
            ];

            $media = Media::create([
                'name' => $fileName,
                'original_name' => $originalName,
                'extension' => $extension,
                'disk' => $uploadPath,
                'width' => null,
                'height' => null,
                'type' => 'video',
                'size' => filesize($absolutePath),
                'folder_id' => $this->generalFolderId,
                'user_id' => auth()->check() ? auth()->id() : null,
                'temporary' => false,
                'metadata' => json_encode($metadata)
            ]);

            return $media->id;
        } catch (\Exception $e) {
            Log::error('Ошибка при создании video media из файла: ' . $e->getMessage(), [
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

    /**
     * Получить ошибки импорта
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Очистка временных файлов после импорта
     */
    public function __destruct()
    {
        if ($this->extractedImagesPath && file_exists($this->extractedImagesPath)) {
            // Удаляем временную директорию с изображениями
            $this->deleteDirectory($this->extractedImagesPath);
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
}

