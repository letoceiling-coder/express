<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        $categoryId = $this->route('category') ?? $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('categories', 'name')->ignore($categoryId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string', 'max:65535'],
            'image_id' => ['nullable', 'integer', 'exists:media,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
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
            'name.required' => 'Название категории обязательно для заполнения',
            'name.min' => 'Название категории должно содержать минимум 2 символа',
            'name.max' => 'Название категории не должно превышать 255 символов',
            'name.unique' => 'Категория с таким названием уже существует',
            'slug.unique' => 'Категория с таким URL уже существует',
            'image_id.exists' => 'Выбранное изображение не найдено в медиа-библиотеке',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным',
        ];
    }
}
