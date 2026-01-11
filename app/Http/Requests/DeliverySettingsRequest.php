<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliverySettingsRequest extends FormRequest
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
            'yandex_geocoder_api_key' => ['nullable', 'string', 'min:1'],
            'origin_address' => ['nullable', 'string', 'max:500'],
            'origin_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'origin_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_zones' => ['nullable', 'array'],
            'delivery_zones.*.max_distance' => ['nullable', 'numeric', 'min:0'],
            'delivery_zones.*.cost' => ['required_with:delivery_zones', 'numeric', 'min:0'],
            'is_enabled' => ['nullable', 'boolean'],
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
            'origin_latitude.between' => 'Широта должна быть между -90 и 90',
            'origin_longitude.between' => 'Долгота должна быть между -180 и 180',
            'delivery_zones.*.max_distance.min' => 'Максимальное расстояние не может быть отрицательным',
            'delivery_zones.*.cost.required_with' => 'Стоимость доставки обязательна',
            'delivery_zones.*.cost.min' => 'Стоимость доставки не может быть отрицательной',
        ];
    }
}

