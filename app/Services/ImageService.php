<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис для обработки и оптимизации изображений
 */
class ImageService
{
    private ImageManager $manager;
    
    // Размеры для генерации вариантов
    private const SIZES = [
        'thumbnail' => 300,  // 300x300px для списков
        'medium' => 800,     // 800x800px для карточек
        'large' => 1200,     // 1200x1200px для детальных страниц
    ];
    
    // Качество сжатия
    private const WEBP_QUALITY = 85;
    private const JPEG_QUALITY = 80;

    public function __construct()
    {
        // Используем GD драйвер (можно переключить на Imagick)
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Обработать загруженное изображение:
     * - Конвертировать в WebP
     * - Создать варианты размеров
     * - Вернуть информацию о всех созданных файлах
     * 
     * @param string $originalPath Полный путь к оригинальному файлу
     * @param string $originalExtension Расширение оригинального файла
     * @param string $basePath Базовый путь для сохранения (относительно public)
     * @param string $baseName Базовое имя файла (без расширения)
     * @return array Массив с путями к созданным файлам
     */
    public function processImage(string $originalPath, string $originalExtension, string $basePath, string $baseName): array
    {
        $result = [
            'original' => null,
            'webp' => null,
            'variants' => [],
        ];

        try {
            // Загружаем оригинальное изображение
            $image = $this->manager->read($originalPath);
            
            // Получаем размеры
            $width = $image->width();
            $height = $image->height();
            
            // Определяем базовый путь для сохранения
            $baseDir = public_path($basePath);
            $webpDir = $baseDir . '/webp';
            $variantsDir = $baseDir . '/variants';
            
            // Создаем директории если не существуют
            if (!file_exists($webpDir)) {
                mkdir($webpDir, 0755, true);
            }
            if (!file_exists($variantsDir)) {
                mkdir($variantsDir, 0755, true);
            }
            
            // 1. Сохраняем оригинал (опционально - можно удалить после обработки)
            $originalName = $baseName . '.' . $originalExtension;
            $originalFullPath = $baseDir . '/' . $originalName;
            
            // Если оригинал еще не на месте, копируем
            if (!file_exists($originalFullPath)) {
                copy($originalPath, $originalFullPath);
            }
            $result['original'] = $basePath . '/' . $originalName;
            
            // 2. Создаем WebP версию оригинала
            $webpName = $baseName . '.webp';
            $webpFullPath = $webpDir . '/' . $webpName;
            
            $image->toWebp(self::WEBP_QUALITY)->save($webpFullPath);
            $result['webp'] = $basePath . '/webp/' . $webpName;
            
            // 3. Создаем варианты размеров (WebP)
            foreach (self::SIZES as $variantName => $maxSize) {
                // Загружаем изображение заново для каждого варианта
                $variantImage = $this->manager->read($originalPath);
                
                // Изменяем размер с сохранением пропорций
                if ($width > $maxSize || $height > $maxSize) {
                    $variantImage->scale(width: $maxSize, height: $maxSize);
                }
                
                // Сохраняем WebP вариант
                $variantWebpName = $baseName . '_' . $variantName . '.webp';
                $variantWebpPath = $variantsDir . '/' . $variantWebpName;
                $variantImage->toWebp(self::WEBP_QUALITY)->save($variantWebpPath);
                
                // Сохраняем fallback вариант (JPEG) для старых браузеров
                $variantJpegName = $baseName . '_' . $variantName . '.jpg';
                $variantJpegPath = $variantsDir . '/' . $variantJpegName;
                $variantImage->toJpeg(self::JPEG_QUALITY)->save($variantJpegPath);
                
                $result['variants'][$variantName] = [
                    'webp' => $basePath . '/variants/' . $variantWebpName,
                    'jpeg' => $basePath . '/variants/' . $variantJpegName,
                    'width' => $variantImage->width(),
                    'height' => $variantImage->height(),
                ];
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Image processing error: ' . $e->getMessage(), [
                'original_path' => $originalPath,
                'trace' => $e->getTraceAsString()
            ]);
            
            // В случае ошибки возвращаем хотя бы оригинал
            return [
                'original' => $basePath . '/' . $baseName . '.' . $originalExtension,
                'webp' => null,
                'variants' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить оптимальный размер варианта для заданной ширины viewport
     * 
     * @param int $viewportWidth Ширина viewport в пикселях
     * @return string Название варианта (thumbnail, medium, large)
     */
    public static function getOptimalVariant(int $viewportWidth): string
    {
        if ($viewportWidth <= 600) {
            return 'thumbnail';
        } elseif ($viewportWidth <= 1024) {
            return 'medium';
        }
        return 'large';
    }
}

