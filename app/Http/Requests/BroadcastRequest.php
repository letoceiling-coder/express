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
        $type = $this->input('type');
        
        $rules = [
            'bot_id' => 'required|exists:bots,id',
            'telegram_user_ids' => 'nullable|array',
            'telegram_user_ids.*' => 'integer',
            'type' => 'required|in:message,photo,video,document,media_group',
            'content' => 'required|array',
            'options' => 'nullable|array',
            'options.parse_mode' => 'nullable|in:HTML,MarkdownV2',
            'options.disable_notification' => 'nullable|boolean',
        ];
        
        // Добавляем правила в зависимости от типа
        switch ($type) {
            case 'message':
                $rules['content.text'] = 'required|string';
                break;
            case 'photo':
                $rules['content.photo'] = 'required|string';
                $rules['content.caption'] = 'nullable|string';
                break;
            case 'video':
                $rules['content.video'] = 'required|string';
                $rules['content.caption'] = 'nullable|string';
                break;
            case 'document':
                $rules['content.document'] = 'required|string';
                $rules['content.caption'] = 'nullable|string';
                break;
            case 'media_group':
                $rules['content.media'] = 'required|array|min:1|max:10';
                $rules['content.media.*.type'] = 'required|in:photo,video';
                $rules['content.media.*.media'] = 'required|string';
                $rules['content.media.*.caption'] = 'nullable|string';
                break;
        }
        
        return $rules;
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
            'type.in' => 'Тип контента должен быть: message, photo, video, document или media_group',
            'content.required' => 'Содержимое обязательно',
            'content.text.required' => 'Текст сообщения обязателен для типа message',
            'content.photo.required' => 'Фото обязательно для типа photo',
            'content.video.required' => 'Видео обязательно для типа video',
            'content.document.required' => 'Документ обязателен для типа document',
            'content.media.required' => 'Медиа группа обязательна для типа media_group',
            'content.media.min' => 'В галерее должно быть хотя бы одно медиа',
            'content.media.max' => 'В галерее может быть не более 10 медиа',
        ];
    }
}
