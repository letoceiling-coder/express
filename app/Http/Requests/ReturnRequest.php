<?php

namespace App\Http\Requests;

use App\Models\ProductReturn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnRequest extends FormRequest
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
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'reason' => ['required', 'string', 'min:10', 'max:65535'],
            'reason_type' => [
                'sometimes',
                'string',
                Rule::in([
                    ProductReturn::REASON_DEFECT,
                    ProductReturn::REASON_WRONG_ITEM,
                    ProductReturn::REASON_NOT_AS_DESCRIBED,
                    ProductReturn::REASON_CHANGED_MIND,
                    ProductReturn::REASON_OTHER,
                ]),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    ProductReturn::STATUS_PENDING,
                    ProductReturn::STATUS_APPROVED,
                    ProductReturn::STATUS_REJECTED,
                    ProductReturn::STATUS_IN_TRANSIT,
                    ProductReturn::STATUS_RECEIVED,
                    ProductReturn::STATUS_REFUNDED,
                    ProductReturn::STATUS_CANCELLED,
                ]),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'refund_method' => [
                'nullable',
                'string',
                Rule::in([
                    ProductReturn::REFUND_ORIGINAL,
                    ProductReturn::REFUND_STORE_CREDIT,
                    ProductReturn::REFUND_EXCHANGE,
                ]),
            ],
            'refund_status' => ['nullable', 'string', 'in:pending,processing,completed,failed'],
            'notes' => ['nullable', 'string', 'max:65535'],
            'customer_notes' => ['nullable', 'string', 'max:65535'],
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
            'reason.required' => 'Причина возврата обязательна',
            'reason.min' => 'Причина возврата должна содержать минимум 10 символов',
            'reason_type.in' => 'Недопустимый тип причины возврата',
            'status.in' => 'Недопустимый статус возврата',
            'items.required' => 'Необходимо указать хотя бы один товар для возврата',
            'items.min' => 'Необходимо указать хотя бы один товар для возврата',
            'total_amount.required' => 'Сумма возврата обязательна',
            'total_amount.numeric' => 'Сумма возврата должна быть числом',
            'total_amount.min' => 'Сумма возврата не может быть отрицательной',
        ];
    }
}
