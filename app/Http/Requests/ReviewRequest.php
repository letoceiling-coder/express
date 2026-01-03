<?php

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRequest extends FormRequest
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
        $reviewId = $this->route('review') ?? $this->route('id');

        return [
            'order_id' => [
                'nullable',
                'integer',
                'exists:orders,id',
                function ($attribute, $value, $fail) {
                    if (!$this->has('product_id') && !$value) {
                        $fail('Необходимо указать либо order_id, либо product_id');
                    }
                },
            ],
            'product_id' => [
                'nullable',
                'integer',
                'exists:products,id',
                function ($attribute, $value, $fail) {
                    if (!$this->has('order_id') && !$value) {
                        $fail('Необходимо указать либо order_id, либо product_id');
                    }
                },
            ],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
            'customer_name' => ['required', 'string', 'min:2', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    Review::STATUS_PENDING,
                    Review::STATUS_APPROVED,
                    Review::STATUS_REJECTED,
                    Review::STATUS_HIDDEN,
                ]),
            ],
            'is_verified_purchase' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['integer', 'exists:media,id'],
            'response' => ['nullable', 'string', 'max:65535'],
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
            'rating.required' => 'Оценка обязательна',
            'rating.min' => 'Оценка должна быть не менее 1',
            'rating.max' => 'Оценка должна быть не более 5',
            'comment.required' => 'Текст отзыва обязателен',
            'comment.min' => 'Текст отзыва должен содержать минимум 10 символов',
            'comment.max' => 'Текст отзыва не должен превышать 2000 символов',
            'customer_name.required' => 'Имя клиента обязательно',
            'customer_name.min' => 'Имя должно содержать минимум 2 символа',
            'customer_email.email' => 'Некорректный email адрес',
            'status.in' => 'Недопустимый статус отзыва',
            'photos.*.exists' => 'Одно из изображений не найдено в медиа-библиотеке',
            'order_id.exists' => 'Заказ не найден',
            'product_id.exists' => 'Товар не найден',
        ];
    }
}
