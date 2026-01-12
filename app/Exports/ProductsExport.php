<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Product::with(['category', 'image', 'video'])->orderBy('sort_order')->orderBy('name')->get();
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
        $galleryUrls = '';
        if (!empty($product->gallery_ids) && is_array($product->gallery_ids)) {
            $gallery = $product->gallery;
            $galleryUrls = $gallery->map(function ($media) {
                return $media->url;
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
            $product->image ? $product->image->url : '',
            $galleryUrls,
            $product->video ? $product->video->url : '',
            $product->sort_order,
            $product->meta_title ?? '',
            $product->meta_description ?? '',
            $product->created_at->format('Y-m-d H:i:s'),
            $product->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

