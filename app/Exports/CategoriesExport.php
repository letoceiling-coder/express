<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CategoriesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Category::with('image')->orderBy('sort_order')->orderBy('name')->get();
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
            'Изображение (URL)',
            'Порядок сортировки',
            'Активна',
            'Meta Title',
            'Meta Description',
            'Создано',
            'Обновлено',
        ];
    }

    /**
     * @param Category $category
     * @return array
     */
    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->slug,
            $category->description ?? '',
            $category->image ? $category->image->url : '',
            $category->sort_order,
            $category->is_active ? 'Да' : 'Нет',
            $category->meta_title ?? '',
            $category->meta_description ?? '',
            $category->created_at->format('Y-m-d H:i:s'),
            $category->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

