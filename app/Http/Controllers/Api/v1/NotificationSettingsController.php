<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationSettingsController extends Controller
{
    /**
     * Получить все настройки уведомлений
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $settings = NotificationSetting::all();
        
        return response()->json([
            'data' => $settings->map(function ($setting) {
                return [
                    'id' => $setting->id,
                    'event' => $setting->event,
                    'enabled' => $setting->enabled,
                    'message_template' => $setting->message_template,
                    'buttons' => $setting->buttons,
                    'support_chat_id' => $setting->support_chat_id,
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ];
            }),
        ]);
    }

    /**
     * Получить настройку по событию
     * 
     * @param string $event
     * @return JsonResponse
     */
    public function show(string $event): JsonResponse
    {
        $setting = NotificationSetting::getByEvent($event);
        
        if (!$setting) {
            return response()->json([
                'message' => 'Настройка не найдена',
            ], 404);
        }
        
        return response()->json([
            'data' => [
                'id' => $setting->id,
                'event' => $setting->event,
                'enabled' => $setting->enabled,
                'message_template' => $setting->message_template,
                'buttons' => $setting->buttons,
                'support_chat_id' => $setting->support_chat_id,
                'created_at' => $setting->created_at,
                'updated_at' => $setting->updated_at,
            ],
        ]);
    }

    /**
     * Обновить настройку уведомления
     * 
     * @param Request $request
     * @param string $event
     * @return JsonResponse
     */
    public function update(Request $request, string $event): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled' => ['nullable', 'boolean'],
            'message_template' => ['nullable', 'string', 'max:2000'],
            'buttons' => ['nullable', 'array'],
            'buttons.*' => ['array'],
            'buttons.*.*' => ['array'],
            'buttons.*.*.text' => ['required_with:buttons', 'string', 'max:64'],
            'buttons.*.*.type' => ['required_with:buttons', 'string', 'in:callback,open_chat,open_url'],
            'buttons.*.*.value' => ['nullable', 'string', 'max:255'],
            'support_chat_id' => ['nullable', 'string', 'max:255'],
        ], [
            'buttons.*.*.text.required_with' => 'Текст кнопки обязателен',
            'buttons.*.*.type.required_with' => 'Тип кнопки обязателен',
            'buttons.*.*.type.in' => 'Тип кнопки должен быть: callback, open_chat или open_url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $setting = NotificationSetting::getByEvent($event);
            
            if (!$setting) {
                return response()->json([
                    'message' => 'Настройка не найдена',
                ], 404);
            }

            $validated = $validator->validated();
            
            // Обновляем только переданные поля
            if (isset($validated['enabled'])) {
                $setting->enabled = $validated['enabled'];
            }
            if (isset($validated['message_template'])) {
                $setting->message_template = $validated['message_template'];
            }
            if (isset($validated['buttons'])) {
                $setting->buttons = $validated['buttons'];
            }
            if (isset($validated['support_chat_id'])) {
                $setting->support_chat_id = $validated['support_chat_id'];
            }
            
            $setting->save();
            
            return response()->json([
                'data' => [
                    'id' => $setting->id,
                    'event' => $setting->event,
                    'enabled' => $setting->enabled,
                    'message_template' => $setting->message_template,
                    'buttons' => $setting->buttons,
                    'support_chat_id' => $setting->support_chat_id,
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ],
                'message' => 'Настройка уведомления успешно обновлена',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при обновлении настройки уведомления: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Ошибка при обновлении настройки',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Создать новую настройку уведомления
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event' => ['required', 'string', 'max:255', 'unique:notification_settings,event'],
            'enabled' => ['nullable', 'boolean'],
            'message_template' => ['nullable', 'string', 'max:2000'],
            'buttons' => ['nullable', 'array'],
            'buttons.*' => ['array'],
            'buttons.*.*' => ['array'],
            'buttons.*.*.text' => ['required_with:buttons', 'string', 'max:64'],
            'buttons.*.*.type' => ['required_with:buttons', 'string', 'in:callback,open_chat,open_url'],
            'buttons.*.*.value' => ['nullable', 'string', 'max:255'],
            'support_chat_id' => ['nullable', 'string', 'max:255'],
        ], [
            'event.required' => 'Событие обязательно',
            'event.unique' => 'Настройка для этого события уже существует',
            'buttons.*.*.text.required_with' => 'Текст кнопки обязателен',
            'buttons.*.*.type.required_with' => 'Тип кнопки обязателен',
            'buttons.*.*.type.in' => 'Тип кнопки должен быть: callback, open_chat или open_url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $validated = $validator->validated();
            
            $setting = NotificationSetting::create([
                'event' => $validated['event'],
                'enabled' => $validated['enabled'] ?? true,
                'message_template' => $validated['message_template'] ?? null,
                'buttons' => $validated['buttons'] ?? null,
                'support_chat_id' => $validated['support_chat_id'] ?? null,
            ]);
            
            return response()->json([
                'data' => [
                    'id' => $setting->id,
                    'event' => $setting->event,
                    'enabled' => $setting->enabled,
                    'message_template' => $setting->message_template,
                    'buttons' => $setting->buttons,
                    'support_chat_id' => $setting->support_chat_id,
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ],
                'message' => 'Настройка уведомления успешно создана',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Ошибка при создании настройки уведомления: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Ошибка при создании настройки',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
