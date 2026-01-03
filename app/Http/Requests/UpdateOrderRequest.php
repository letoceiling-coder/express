<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
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
        $orderId = $this->route('order') ?? $this->route('id');
        $order = Order::find($orderId);

        return [
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    Order::STATUS_NEW,
                    Order::STATUS_ACCEPTED,
                    Order::STATUS_PREPARING,
                    Order::STATUS_READY_FOR_DELIVERY,
                    Order::STATUS_IN_TRANSIT,
                    Order::STATUS_DELIVERED,
                    Order::STATUS_CANCELLED,
                ]),
                function ($attribute, $value, $fail) use ($order) {
                    if ($order && !$order->canChangeStatus($value)) {
                        $fail('Нельзя изменить статус заказа, который уже доставлен или отменен.');
                    }
                },
            ],
            'payment_status' => [
                'sometimes',
                'string',
                Rule::in([
                    Order::PAYMENT_STATUS_PENDING,
                    Order::PAYMENT_STATUS_SUCCEEDED,
                    Order::PAYMENT_STATUS_FAILED,
                    Order::PAYMENT_STATUS_CANCELLED,
                ]),
            ],
            'phone' => ['sometimes', 'string', 'max:255'],
            'delivery_address' => ['sometimes', 'string', 'max:65535'],
            'delivery_type' => ['sometimes', 'string', Rule::in(['courier', 'pickup', 'self_delivery'])],
            'delivery_time' => ['sometimes', 'string', 'max:255'],
            'delivery_date' => ['sometimes', 'date'],
            'delivery_time_from' => ['sometimes', 'date_format:H:i'],
            'delivery_time_to' => ['sometimes', 'date_format:H:i'],
            'delivery_cost' => ['sometimes', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string', 'max:65535'],
            'notes' => ['nullable', 'string', 'max:65535'],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'payment_id' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
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
            'status.in' => 'Недопустимый статус заказа',
            'payment_status.in' => 'Недопустимый статус оплаты',
            'delivery_type.in' => 'Недопустимый тип доставки',
            'delivery_cost.numeric' => 'Стоимость доставки должна быть числом',
            'delivery_cost.min' => 'Стоимость доставки не может быть отрицательной',
            'total_amount.numeric' => 'Общая сумма должна быть числом',
            'total_amount.min' => 'Общая сумма не может быть отрицательной',
            'manager_id.exists' => 'Выбранный менеджер не найден',
        ];
    }
}
