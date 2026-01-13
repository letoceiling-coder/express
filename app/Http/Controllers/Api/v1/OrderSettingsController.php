<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\OrderSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderSettingsController extends Controller
{
    /**
     * Получить настройки заказов
     * 
     * @return JsonResponse
     */
    public function getSettings(): JsonResponse
    {
        $settings = OrderSetting::getSettings();
        
        return response()->json([
            'data' => $settings->toArray(),
        ]);
    }

    /**
     * Обновить настройки заказов
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_ttl_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'notification_10min_enabled' => ['nullable', 'boolean'],
            'notification_5min_before_ttl_enabled' => ['nullable', 'boolean'],
            'notification_auto_cancel_enabled' => ['nullable', 'boolean'],
            'notification_10min_template' => ['nullable', 'string', 'max:1000'],
            'notification_5min_template' => ['nullable', 'string', 'max:1000'],
            'notification_auto_cancel_template' => ['nullable', 'string', 'max:1000'],
        ], [
            'payment_ttl_minutes.integer' => 'TTL должен быть числом',
            'payment_ttl_minutes.min' => 'TTL должен быть не менее 1 минуты',
            'payment_ttl_minutes.max' => 'TTL не может превышать 1440 минут (24 часа)',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $settings = OrderSetting::getSettings();
            $validated = $validator->validated();
            
            $settings->fill($validated);
            $settings->save();
            
            return response()->json([
                'data' => $settings->toArray(),
                'message' => 'Настройки заказов успешно обновлены',
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при обновлении настроек заказов: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Ошибка при обновлении настроек',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
