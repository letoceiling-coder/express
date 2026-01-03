<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
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
        $paymentId = $this->route('payment') ?? $this->route('id');

        return [
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
            ],
            'payment_method' => [
                'sometimes',
                'string',
                Rule::in([
                    Payment::METHOD_CARD,
                    Payment::METHOD_CASH,
                    Payment::METHOD_ONLINE,
                    Payment::METHOD_BANK_TRANSFER,
                    Payment::METHOD_OTHER,
                ]),
            ],
            'payment_provider' => ['nullable', 'string', 'max:255'],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    Payment::STATUS_PENDING,
                    Payment::STATUS_PROCESSING,
                    Payment::STATUS_SUCCEEDED,
                    Payment::STATUS_FAILED,
                    Payment::STATUS_REFUNDED,
                    Payment::STATUS_PARTIALLY_REFUNDED,
                    Payment::STATUS_CANCELLED,
                ]),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'transaction_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('payments', 'transaction_id')->ignore($paymentId),
            ],
            'provider_response' => ['nullable', 'array'],
            'refunded_amount' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($paymentId) {
                    if ($paymentId) {
                        $payment = \App\Models\Payment::find($paymentId);
                        if ($payment && $value > $payment->amount) {
                            $fail('Сумма возврата не может превышать сумму платежа');
                        }
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:65535'],
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
            'payment_method.in' => 'Недопустимый метод оплаты',
            'status.in' => 'Недопустимый статус платежа',
            'amount.required' => 'Сумма платежа обязательна',
            'amount.numeric' => 'Сумма должна быть числом',
            'amount.min' => 'Сумма должна быть больше 0',
            'transaction_id.unique' => 'Транзакция с таким ID уже существует',
        ];
    }
}
