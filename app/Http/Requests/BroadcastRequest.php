<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BroadcastRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by auth:sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bot_id' => 'required|exists:bots,id',
            'telegram_user_ids' => 'nullable|array',
            'telegram_user_ids.*' => 'integer',
            'type' => 'required|in:message,photo,document,media_group',
            'content' => 'required|array',
            'content.text' => 'required_if:type,message|string',
            'content.photo' => 'required_if:type,photo|string',
            'content.document' => 'required_if:type,document|string',
            'content.caption' => 'nullable|string',
            'content.media' => 'required_if:type,media_group|array',
            'options' => 'nullable|array',
            'options.parse_mode' => 'nullable|in:HTML,Markdown,MarkdownV2',
            'options.disable_notification' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'bot_id.required' => 'ID бота обязателен',
            'bot_id.exists' => 'Бот не найден',
            'type.required' => 'Тип контента обязателен',
            'type.in' => 'Тип контента должен быть: message, photo, document или media_group',
            'content.required' => 'Содержимое обязательно',
            'content.text.required_if' => 'Текст сообщения обязателен для типа message',
            'content.photo.required_if' => 'Фото обязательно для типа photo',
            'content.document.required_if' => 'Документ обязателен для типа document',
            'content.media.required_if' => 'Медиа группа обязательна для типа media_group',
        ];
    }
}
