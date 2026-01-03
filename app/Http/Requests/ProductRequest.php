<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product') ?? $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'description' => ['nullable', 'string', 'max:65535'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->ignore($productId),
            ],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'is_available' => ['nullable', 'boolean'],
            'is_weight_product' => ['nullable', 'boolean'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'image_id' => ['nullable', 'integer', 'exists:media,id'],
            'gallery_ids' => ['nullable', 'array'],
            'gallery_ids.*' => ['integer', 'exists:media,id'],
            'video_id' => ['nullable', 'integer', 'exists:media,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:65535'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название товара обязательно для заполнения',
            'name.min' => 'Название товара должно содержать минимум 2 символа',
            'name.max' => 'Название товара не должно превышать 255 символов',
            'price.required' => 'Цена товара обязательна для заполнения',
            'price.numeric' => 'Цена должна быть числом',
            'price.min' => 'Цена не может быть отрицательной',
            'compare_price.numeric' => 'Цена до скидки должна быть числом',
            'compare_price.min' => 'Цена до скидки не может быть отрицательной',
            'category_id.exists' => 'Выбранная категория не найдена',
            'sku.unique' => 'Товар с таким артикулом уже существует',
            'barcode.unique' => 'Товар с таким штрих-кодом уже существует',
            'stock_quantity.integer' => 'Количество на складе должно быть целым числом',
            'stock_quantity.min' => 'Количество на складе не может быть отрицательным',
            'image_id.exists' => 'Выбранное изображение не найдено в медиа-библиотеке',
            'video_id.exists' => 'Выбранное видео не найдено в медиа-библиотеке',
            'gallery_ids.array' => 'Галерея должна быть массивом',
            'gallery_ids.*.exists' => 'Одно из изображений галереи не найдено в медиа-библиотеке',
            'weight.numeric' => 'Вес должен быть числом',
            'weight.min' => 'Вес не может быть отрицательным',
        ];
    }
}
