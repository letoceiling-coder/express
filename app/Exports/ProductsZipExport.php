<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class ProductsZipExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithCustomValueBinder
{
    /**
     * Маппинг имен файлов для использования в CSV (images/filename.jpg)
     * 
     * @var array [media_id => 'images/filename.jpg']
     */
    private $imagePathMap = [];

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Product::with(['category', 'image', 'video'])->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Установить маппинг путей изображений
     * 
     * @param array $map
     * @return void
     */
    public function setImagePathMap(array $map): void
    {
        $this->imagePathMap = $map;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Название',
            'Slug',
            'Описание',
            'Краткое описание',
            'Цена',
            'Старая цена',
            'Категория (ID)',
            'Категория (Название)',
            'Артикул (SKU)',
            'Штрих-код',
            'Количество на складе',
            'Доступен',
            'Весовой товар',
            'Вес',
            'Изображение (URL)',
            'Галерея (URLs через запятую)',
            'Видео (URL)',
            'Порядок сортировки',
            'Meta Title',
            'Meta Description',
            'Создано',
            'Обновлено',
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    public function map($product): array
    {
        // Главное изображение - используем путь из маппинга или images/filename
        $imagePath = '';
        if ($product->image) {
            if (isset($this->imagePathMap[$product->image->id])) {
                $imagePath = $this->imagePathMap[$product->image->id];
            } else {
                $imageName = $product->image->original_name ?? $product->image->name;
                $imagePath = 'images/' . $imageName;
            }
        }

        // Галерея
        $galleryPaths = '';
        if (!empty($product->gallery_ids) && is_array($product->gallery_ids)) {
            $gallery = $product->gallery;
            $galleryPaths = $gallery->map(function ($media) {
                if (isset($this->imagePathMap[$media->id])) {
                    return $this->imagePathMap[$media->id];
                } else {
                    $imageName = $media->original_name ?? $media->name;
                    return 'images/' . $imageName;
                }
            })->implode(', ');
        }

        return [
            $product->id,
            $product->name,
            $product->slug,
            $product->description ?? '',
            $product->short_description ?? '',
            $product->price,
            $product->compare_price ?? '',
            $product->category_id ?? '',
            $product->category ? $product->category->name : '',
            $product->sku ?? '',
            $product->barcode ?? '',
            $product->stock_quantity,
            $product->is_available ? 'Да' : 'Нет',
            $product->is_weight_product ? 'Да' : 'Нет',
            $product->weight ?? '',
            $imagePath, // Путь относительно ZIP архива: images/filename.jpg
            $galleryPaths, // Пути через запятую: images/file1.jpg, images/file2.jpg
            $product->video ? $product->video->url : '', // Видео оставляем как URL
            $product->sort_order,
            $product->meta_title ?? '',
            $product->meta_description ?? '',
            $product->created_at->format('Y-m-d H:i:s'),
            $product->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Привязка значений для правильной обработки данных
     */
    public function bindValue(Cell $cell, $value)
    {
        // Для длинных текстовых полей используем строковый тип
        if (in_array($cell->getColumn(), ['D', 'E'])) { // Описание, Краткое описание
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        
        return parent::bindValue($cell, $value);
    }
}

