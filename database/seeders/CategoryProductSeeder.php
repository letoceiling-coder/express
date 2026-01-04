<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Folder;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryProductSeeder extends Seeder
{
    /**
     * Папка "Общая" для медиа-файлов
     */
    private ?Folder $generalFolder = null;

    /**
     * Данные категорий и товаров из mockData.ts
     */
    private array $categories = [
        ['id' => 1, 'name' => 'Мангал/гриль'],
        ['id' => 2, 'name' => 'Салаты/Закуски'],
        ['id' => 3, 'name' => 'Горячие блюда'],
        ['id' => 4, 'name' => 'Выпечка'],
        ['id' => 5, 'name' => 'Десерты'],
        ['id' => 6, 'name' => 'Напитки'],
        ['id' => 7, 'name' => 'Соусы'],
        ['id' => 8, 'name' => 'Хлеб'],
    ];

    private array $products = [
        // Мангал/гриль (categoryId: 1)
        [
            'name' => 'Шашлык из свиной мякоти 500 г.',
            'description' => 'Шашлык из задней части свиного окорока, маринованный в специях',
            'price' => 550,
            'category_id' => 1,
            'imageUrl' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400',
            'is_weight_product' => true,
        ],
        [
            'name' => 'Люля говядина/свинина с зеленью 500г',
            'description' => 'Крученное мясо свинина/говядина с ароматными специями',
            'price' => 590,
            'category_id' => 1,
            'imageUrl' => 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=400',
            'is_weight_product' => true,
        ],
        [
            'name' => 'Шашлык из курицы 500 г.',
            'description' => 'Все части курицы (филе, окорок, крылья), маринованные в специях',
            'price' => 370,
            'category_id' => 1,
            'imageUrl' => 'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?w=400',
            'is_weight_product' => true,
        ],
        [
            'name' => 'Кура-гриль 500гр',
            'description' => 'Половинка курицы со специями и соусом',
            'price' => 295,
            'category_id' => 1,
            'imageUrl' => 'https://images.unsplash.com/photo-1598103442097-8b74394b95c6?w=400',
            'is_weight_product' => true,
        ],
        // Салаты/Закуски (categoryId: 2)
        [
            'name' => 'Салат «Цезарь» порция 280г шт',
            'description' => 'Наггетсы куриные, помидоры, сыр чеддер, соус цезарь',
            'price' => 185,
            'category_id' => 2,
            'imageUrl' => 'https://images.unsplash.com/photo-1546793665-c74683f339c1?w=400',
            'is_weight_product' => false,
        ],
        [
            'name' => 'Салат «Греческий» порция 280г шт',
            'description' => 'Салат айсберг, свежие огурцы, помидоры, сыр фета',
            'price' => 185,
            'category_id' => 2,
            'imageUrl' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=400',
            'is_weight_product' => false,
        ],
        [
            'name' => 'Холодец говяжий',
            'description' => 'Мясо говядина, костный бульон, чеснок, специи',
            'price' => 395,
            'category_id' => 2,
            'imageUrl' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400',
            'is_weight_product' => false,
        ],
        // Горячие блюда (categoryId: 3)
        [
            'name' => 'Долма свинина-говядина в виноградных листьях 500г',
            'description' => 'Традиционное Армянское блюдо',
            'price' => 475,
            'category_id' => 3,
            'imageUrl' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400',
            'is_weight_product' => true,
        ],
        [
            'name' => 'Узбекский плов с говядиной 500г',
            'description' => 'Плов с мягкой телятиной и традиционными специями',
            'price' => 360,
            'category_id' => 3,
            'imageUrl' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=400',
            'is_weight_product' => true,
        ],
        // Выпечка (categoryId: 4)
        [
            'name' => 'Самса куриная с овощами сыром 200г шт',
            'description' => 'Наше полу-слоенное тесто, куриное филе, овощи, сыр',
            'price' => 125,
            'category_id' => 4,
            'imageUrl' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400',
            'is_weight_product' => false,
        ],
        [
            'name' => 'Беляш с мясом 180г шт',
            'description' => 'Заварное тесто, крученное мясо свинины',
            'price' => 95,
            'category_id' => 4,
            'imageUrl' => 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=400',
            'is_weight_product' => false,
        ],
        [
            'name' => 'Чебурек свин/говяд 200г шт',
            'description' => 'Заварное тесто, крученное мясо свинины/говядины',
            'price' => 110,
            'category_id' => 4,
            'imageUrl' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=400',
            'is_weight_product' => false,
        ],
        // Десерты (categoryId: 5)
        [
            'name' => 'Гата 500гр',
            'description' => 'Гата из слоенного теста. Традиционная армянская выпечка',
            'price' => 295,
            'category_id' => 5,
            'imageUrl' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400',
            'is_weight_product' => true,
        ],
        [
            'name' => 'Пахлава Армянская 500гр',
            'description' => 'Слоенное тесто, грецкий орех, мед, корица',
            'price' => 545,
            'category_id' => 5,
            'imageUrl' => 'https://images.unsplash.com/photo-1519915028121-7d3463d5b1ff?w=400',
            'is_weight_product' => true,
        ],
        // Напитки (categoryId: 6)
        [
            'name' => 'Тан с зеленью 500мл 1.5% жир.',
            'description' => 'Традиционный кисломолочный напиток с зеленью',
            'price' => 110,
            'category_id' => 6,
            'imageUrl' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400',
            'is_weight_product' => false,
        ],
        [
            'name' => 'Компот из шиповника 500мл',
            'description' => 'Домашний компот из свежего шиповника',
            'price' => 100,
            'category_id' => 6,
            'imageUrl' => 'https://images.unsplash.com/photo-1534353473418-4cfa6c56fd38?w=400',
            'is_weight_product' => false,
        ],
        // Соусы (categoryId: 7)
        [
            'name' => 'Соус томатный 200гр шт',
            'description' => 'Томатная паста, кинза, укроп, чеснок, специи',
            'price' => 95,
            'category_id' => 7,
            'imageUrl' => 'https://images.unsplash.com/photo-1472476443507-c7a5948772fc?w=400',
            'is_weight_product' => false,
        ],
        [
            'name' => 'Соус чесночный 200гр шт.',
            'description' => 'Кефир 3.2%, сметана 20%, укроп свежий, чеснок',
            'price' => 95,
            'category_id' => 7,
            'imageUrl' => 'https://images.unsplash.com/photo-1563379926898-05f4575a45d8?w=400',
            'is_weight_product' => false,
        ],
        // Хлеб (categoryId: 8)
        [
            'name' => 'Лаваш Тандырный 120гр шт',
            'description' => 'Традиционный Армянский лаваш из тандыра',
            'price' => 100,
            'category_id' => 8,
            'imageUrl' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400',
            'is_weight_product' => false,
        ],
        [
            'name' => 'Хлеб крестьянский буханка 500г шт',
            'description' => 'Обратите внимание что товар штучный',
            'price' => 45,
            'category_id' => 8,
            'imageUrl' => 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=400',
            'is_weight_product' => false,
        ],
    ];

    /**
     * Загрузить изображение из URL и создать запись в Media
     */
    private function downloadImageToMedia(string $url, string $name, ?Folder $folder = null): ?Media
    {
        try {
            // Загружаем изображение с отключенной проверкой SSL для локальной разработки
            $response = Http::timeout(30)
                ->withoutVerifying() // Отключаем проверку SSL для локальной разработки
                ->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ],
                ])
                ->get($url);
            
            if (!$response->successful()) {
                Log::warning("Failed to download image: {$url}", [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 200),
                ]);
                return null;
            }

            $imageContent = $response->body();
            
            // Определяем расширение файла из URL или заголовков
            $extension = 'jpg';
            $contentType = $response->header('Content-Type');
            
            if (strpos($contentType, 'image/png') !== false) {
                $extension = 'png';
            } elseif (strpos($contentType, 'image/jpeg') !== false || strpos($contentType, 'image/jpg') !== false) {
                $extension = 'jpg';
            } elseif (strpos($contentType, 'image/webp') !== false) {
                $extension = 'webp';
            } elseif (strpos($contentType, 'image/gif') !== false) {
                $extension = 'gif';
            } else {
                // Пытаемся определить по URL
                $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
                if (isset($pathInfo['extension'])) {
                    $extension = strtolower($pathInfo['extension']);
                }
            }

            // Генерируем уникальное имя файла
            $fileName = Str::slug($name) . '_' . time() . '_' . Str::random(8) . '.' . $extension;
            
            // Путь для сохранения в storage
            $storagePath = 'media/photos/' . date('Y/m');
            $fullPath = $storagePath . '/' . $fileName;

            // Создаем директорию, если её нет
            $publicPath = public_path($storagePath);
            if (!File::exists($publicPath)) {
                File::makeDirectory($publicPath, 0755, true);
            }

            // Сохраняем файл
            $fullFilePath = $publicPath . '/' . $fileName;
            File::put($fullFilePath, $imageContent);

            // Получаем размеры изображения (если это изображение)
            $width = null;
            $height = null;
            $imageInfo = @getimagesize($fullFilePath);
            if ($imageInfo !== false) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }

            // Создаем запись в базе данных сначала
            $media = Media::create([
                'name' => $fileName,
                'original_name' => $fileName,
                'extension' => $extension,
                'disk' => $storagePath, // Исправлено: используем правильный путь
                'width' => $width,
                'height' => $height,
                'type' => 'photo',
                'size' => strlen($imageContent),
                'folder_id' => $folder?->id ?? $this->generalFolder?->id,
                'user_id' => null,
                'temporary' => false,
                'metadata' => json_encode([
                    'path' => $fullPath,
                    'mime_type' => $contentType,
                    'source_url' => $url,
                ]),
            ]);

            // Обрабатываем изображение через ImageService для создания WebP и вариантов
            try {
                $imageService = app(ImageService::class);
                $baseName = pathinfo($fileName, PATHINFO_FILENAME);
                
                $imageVariants = $imageService->processImage(
                    $fullFilePath,
                    $extension,
                    $storagePath,
                    $baseName
                );
                
                // Обновляем metadata с информацией о вариантах
                $metadata = [
                    'path' => $fullPath,
                    'mime_type' => $contentType,
                    'source_url' => $url,
                    'webp_path' => $imageVariants['webp'] ?? null,
                    'variants' => $imageVariants['variants'] ?? [],
                ];
                
                if (isset($imageVariants['error'])) {
                    $metadata['processing_error'] = $imageVariants['error'];
                    $this->command->warn("  ⚠ Image processing error: {$imageVariants['error']}");
                }
                
                $media->update(['metadata' => json_encode($metadata)]);
            } catch (\Exception $e) {
                // Логируем ошибку, но не прерываем процесс
                Log::warning("Failed to process image for Media ID {$media->id}: " . $e->getMessage());
                $this->command->warn("  ⚠ Failed to process image (will use original): " . $e->getMessage());
            }

            return $media;
        } catch (\Exception $e) {
            Log::error("Error downloading image {$url}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получить или создать папку "Общая"
     */
    private function getOrCreateGeneralFolder(): ?Folder
    {
        $folder = Folder::where('name', 'Общая')->whereNull('parent_id')->first();
        
        if (!$folder) {
            $folder = Folder::create([
                'name' => 'Общая',
                'slug' => 'obshchaya',
                'src' => '/media/photos/obshchaya',
                'parent_id' => null,
                'position' => 0,
                'protected' => false,
                'is_trash' => false,
            ]);
            $this->command->info('Created folder: Общая');
        }
        
        return $folder;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Category and Product seeder...');
        
        // Создаем или получаем папку "Общая"
        $this->generalFolder = $this->getOrCreateGeneralFolder();

        // Создаем категории
        $categoryMap = [];
        foreach ($this->categories as $categoryData) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($categoryData['name'])],
                [
                    'name' => $categoryData['name'],
                    'slug' => Str::slug($categoryData['name']),
                    'is_active' => true,
                    'sort_order' => $categoryData['id'],
                ]
            );
            
            $categoryMap[$categoryData['id']] = $category;
            $this->command->info("Created/Updated category: {$category->name}");
        }

        // Создаем товары
        $productNumber = 1;
        foreach ($this->products as $productData) {
            // Получаем категорию из маппинга
            $category = $categoryMap[$productData['category_id']] ?? null;
            
            if (!$category) {
                $this->command->warn("Category ID {$productData['category_id']} not found, skipping product: {$productData['name']}");
                continue;
            }

            // Загружаем изображение
            $imageMedia = null;
            if (!empty($productData['imageUrl'])) {
                $this->command->info("Downloading image for: {$productData['name']}");
                $imageMedia = $this->downloadImageToMedia($productData['imageUrl'], $productData['name'], $this->generalFolder);
                
                if ($imageMedia) {
                    $this->command->info("  ✓ Image downloaded: {$imageMedia->name}");
                } else {
                    $this->command->warn("  ✗ Failed to download image");
                }
            }

            // Создаем товар
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($productData['name'])],
                [
                    'name' => $productData['name'],
                    'slug' => Str::slug($productData['name']),
                    'description' => $productData['description'],
                    'price' => $productData['price'],
                    'category_id' => $category->id,
                    'image_id' => $imageMedia?->id,
                    'is_available' => true,
                    'is_weight_product' => $productData['is_weight_product'] ?? false,
                    'stock_quantity' => 100, // По умолчанию 100 единиц
                    'sort_order' => $productNumber++,
                ]
            );

            $this->command->info("Created/Updated product: {$product->name}");
        }

        $this->command->info('Seeder completed successfully!');
    }
}

