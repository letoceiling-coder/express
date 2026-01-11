<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Команда для обработки существующих изображений
 * 
 * Генерирует WebP версии и варианты размеров для всех изображений,
 * которые были созданы до реализации оптимизации
 */
class ProcessExistingImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:process-existing 
                            {--limit= : Обработать только N изображений}
                            {--force : Перегенерировать даже если уже есть варианты}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обработать существующие изображения: создать WebP версии и варианты размеров';

    /**
     * Execute the console command.
     */
    public function handle(ImageService $imageService): int
    {
        $this->info('Начинаем обработку существующих изображений...');

        // Получаем изображения без WebP вариантов
        $query = Media::where('type', 'photo');
        
        if (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('metadata')
                  ->orWhereRaw("JSON_EXTRACT(metadata, '$.webp_path') IS NULL")
                  ->orWhereRaw("JSON_EXTRACT(metadata, '$.variants') IS NULL");
            });
        }

        $limit = $this->option('limit');
        if ($limit) {
            $query->limit((int) $limit);
        }

        $mediaItems = $query->get();
        $total = $mediaItems->count();

        if ($total === 0) {
            $this->info('Нет изображений для обработки.');
            return Command::SUCCESS;
        }

        $this->info("Найдено изображений для обработки: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $failed = 0;

        foreach ($mediaItems as $media) {
            try {
                // Получаем путь к оригиналу
                $metadata = $media->metadata ? json_decode($media->metadata, true) : [];
                $originalPath = $metadata['path'] ?? ($media->disk . '/' . $media->name);
                $fullOriginalPath = public_path($originalPath);

                // Проверяем существование файла
                if (!file_exists($fullOriginalPath)) {
                    $this->newLine();
                    $this->warn("Файл не найден: {$originalPath} (Media ID: {$media->id})");
                    $failed++;
                    $bar->advance();
                    continue;
                }

                // Получаем базовое имя и расширение
                $baseName = pathinfo($media->name, PATHINFO_FILENAME);
                $extension = $media->extension;

                // Обрабатываем изображение
                $imageVariants = $imageService->processImage(
                    $fullOriginalPath,
                    $extension,
                    $media->disk,
                    $baseName
                );

                // Обновляем metadata
                $updatedMetadata = array_merge($metadata, [
                    'webp_path' => $imageVariants['webp'] ?? null,
                    'variants' => $imageVariants['variants'] ?? [],
                ]);

                if (isset($imageVariants['error'])) {
                    $updatedMetadata['processing_error'] = $imageVariants['error'];
                    $this->newLine();
                    $this->warn("Ошибка при обработке Media ID {$media->id}: {$imageVariants['error']}");
                    $failed++;
                } else {
                    $media->update([
                        'metadata' => json_encode($updatedMetadata),
                    ]);
                    $processed++;
                }

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Ошибка при обработке Media ID {$media->id}: {$e->getMessage()}");
                Log::error("Error processing image Media ID {$media->id}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Выводим статистику
        $this->info("Обработка завершена!");
        $this->table(
            ['Статус', 'Количество'],
            [
                ['Обработано', $processed],
                ['Ошибок', $failed],
                ['Всего', $total],
            ]
        );

        if ($failed > 0) {
            $this->warn("Есть ошибки. Проверьте логи для деталей.");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}




