<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentSettingsRequest extends FormRequest
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
        return [
            'shop_id' => ['required_without:test_shop_id', 'nullable', 'string', 'min:1'],
            'secret_key' => ['required_without:test_secret_key', 'nullable', 'string', 'min:20'],
            'is_test_mode' => ['nullable', 'boolean'],
            'is_enabled' => ['nullable', 'boolean'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['string', 'in:bank_card,sberbank,yoo_money,qiwi,webmoney,alfabank,installments,apple_pay,google_pay'],
            'auto_capture' => ['nullable', 'boolean'],
            'description_template' => ['nullable', 'string', 'max:255'],
            'merchant_name' => ['nullable', 'string', 'max:255'],
            'test_shop_id' => ['nullable', 'string', 'min:1'],
            'test_secret_key' => ['nullable', 'string', 'min:20'],
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
            'shop_id.required_without' => 'ID магазина обязателен, если не указан тестовый ID',
            'shop_id.min' => 'ID магазина обязателен',
            'secret_key.required_without' => 'Секретный ключ обязателен, если не указан тестовый ключ',
            'secret_key.min' => 'Секретный ключ должен содержать минимум 20 символов',
            'webhook_url.url' => 'Некорректный URL для webhook',
            'payment_methods.*.in' => 'Недопустимый метод оплаты',
            'test_shop_id.min' => 'Тестовый ID магазина обязателен',
            'test_secret_key.min' => 'Тестовый секретный ключ должен содержать минимум 20 символов',
            'merchant_name.max' => 'Название магазина не должно превышать 255 символов',
        ];
    }
}
