<?php

namespace App\Http\Requests;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintRequest extends FormRequest
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
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'type' => [
                'required',
                'string',
                Rule::in([
                    Complaint::TYPE_QUALITY,
                    Complaint::TYPE_DELIVERY,
                    Complaint::TYPE_SERVICE,
                    Complaint::TYPE_PAYMENT,
                    Complaint::TYPE_OTHER,
                ]),
            ],
            'priority' => [
                'sometimes',
                'string',
                Rule::in([
                    Complaint::PRIORITY_LOW,
                    Complaint::PRIORITY_MEDIUM,
                    Complaint::PRIORITY_HIGH,
                    Complaint::PRIORITY_URGENT,
                ]),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    Complaint::STATUS_NEW,
                    Complaint::STATUS_IN_PROGRESS,
                    Complaint::STATUS_RESOLVED,
                    Complaint::STATUS_REJECTED,
                    Complaint::STATUS_CLOSED,
                ]),
            ],
            'subject' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:20'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['integer', 'exists:media,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'resolution' => ['nullable', 'string', 'max:65535'],
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
            'type.required' => 'Тип претензии обязателен',
            'type.in' => 'Недопустимый тип претензии',
            'priority.in' => 'Недопустимый приоритет',
            'status.in' => 'Недопустимый статус претензии',
            'subject.required' => 'Тема претензии обязательна',
            'subject.min' => 'Тема претензии должна содержать минимум 5 символов',
            'description.required' => 'Описание проблемы обязательно',
            'description.min' => 'Описание должно содержать минимум 20 символов',
            'customer_email.email' => 'Некорректный email адрес',
            'attachments.*.exists' => 'Один из файлов не найден в медиа-библиотеке',
            'assigned_to.exists' => 'Назначенный сотрудник не найден',
            'order_id.exists' => 'Заказ не найден',
        ];
    }
}
