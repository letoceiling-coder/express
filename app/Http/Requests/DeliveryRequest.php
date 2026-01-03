<?php

namespace App\Http\Requests;

use App\Models\Delivery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryRequest extends FormRequest
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
        $deliveryId = $this->route('delivery') ?? $this->route('id');

        return [
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
            ],
            'delivery_type' => [
                'sometimes',
                'string',
                Rule::in([
                    Delivery::TYPE_COURIER,
                    Delivery::TYPE_PICKUP,
                    Delivery::TYPE_SELF_DELIVERY,
                ]),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    Delivery::STATUS_PENDING,
                    Delivery::STATUS_ASSIGNED,
                    Delivery::STATUS_IN_TRANSIT,
                    Delivery::STATUS_DELIVERED,
                    Delivery::STATUS_FAILED,
                    Delivery::STATUS_RETURNED,
                ]),
            ],
            'courier_name' => ['nullable', 'string', 'max:255'],
            'courier_phone' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:65535'],
            'delivery_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value && strtotime($value) < strtotime('today')) {
                        $fail('Дата доставки не может быть в прошлом');
                    }
                },
            ],
            'delivery_time_from' => ['nullable', 'date_format:H:i'],
            'delivery_time_to' => ['nullable', 'date_format:H:i'],
            'delivery_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:65535'],
            'tracking_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('deliveries', 'tracking_number')->ignore($deliveryId),
            ],
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
            'order_id.required' => 'ID заказа обязателен',
            'order_id.exists' => 'Заказ не найден',
            'delivery_type.in' => 'Недопустимый тип доставки',
            'status.in' => 'Недопустимый статус доставки',
            'delivery_address.required' => 'Адрес доставки обязателен',
            'delivery_cost.numeric' => 'Стоимость доставки должна быть числом',
            'delivery_cost.min' => 'Стоимость доставки не может быть отрицательной',
            'tracking_number.unique' => 'Трек-номер уже используется',
        ];
    }
}
