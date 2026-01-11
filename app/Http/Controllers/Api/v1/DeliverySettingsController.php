<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliverySettingsRequest;
use App\Models\DeliverySetting;
use App\Services\DeliveryCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliverySettingsController extends Controller
{
    /**
     * Получить настройки доставки
     * 
     * @return JsonResponse
     */
    public function getSettings(): JsonResponse
    {
        $settings = DeliverySetting::getSettings();
        
        $data = $settings->toArray();
        // Не показываем API ключ
        unset($data['yandex_geocoder_api_key']);
        
        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Обновить настройки доставки
     * 
     * @param DeliverySettingsRequest $request
     * @return JsonResponse
     */
    public function updateSettings(DeliverySettingsRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $settings = DeliverySetting::getSettings();
            $validated = $request->validated();
            
            // Сохраняем существующий API ключ перед fill()
            $existingApiKey = $settings->yandex_geocoder_api_key;
            
            // Удаляем пустой API ключ из валидированных данных
            if (isset($validated['yandex_geocoder_api_key']) && empty(trim($validated['yandex_geocoder_api_key'] ?? ''))) {
                unset($validated['yandex_geocoder_api_key']);
            }
            
            // Обновляем настройки
            $settings->fill($validated);
            
            // Восстанавливаем API ключ, если он не был передан
            if (!isset($validated['yandex_geocoder_api_key']) && $existingApiKey) {
                $settings->yandex_geocoder_api_key = $existingApiKey;
            }
            
            // Если обновлен адрес начала доставки, попробуем получить координаты
            if (isset($validated['origin_address']) && !empty(trim($validated['origin_address']))) {
                if (!$settings->origin_latitude || !$settings->origin_longitude) {
                    $this->geocodeOriginAddress($settings);
                }
            }
            
            $settings->save();
            
            DB::commit();
            
            $settings->refresh();
            $data = $settings->toArray();
            unset($data['yandex_geocoder_api_key']);
            
            return response()->json([
                'data' => $data,
                'message' => 'Настройки доставки успешно обновлены',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ошибка при обновлении настроек доставки: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Ошибка при обновлении настроек',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Геокодинг адреса начала доставки
     * 
     * @param DeliverySetting $settings
     * @return void
     */
    protected function geocodeOriginAddress(DeliverySetting $settings): void
    {
        try {
            $calculationService = new DeliveryCalculationService($settings);
            $result = $calculationService->geocodeAddress($settings->origin_address);
            
            if ($result) {
                $settings->origin_latitude = $result['latitude'];
                $settings->origin_longitude = $result['longitude'];
            }
        } catch (\Exception $e) {
            Log::warning('Не удалось получить координаты для адреса начала доставки', [
                'address' => $settings->origin_address,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Расчет стоимости доставки для адреса
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateCost(Request $request): JsonResponse
    {
        $request->validate([
            'address' => ['required', 'string', 'max:500'],
        ], [
            'address.required' => 'Адрес обязателен',
            'address.string' => 'Адрес должен быть строкой',
        ]);

        try {
            $settings = DeliverySetting::getSettings();
            $calculationService = new DeliveryCalculationService($settings);
            
            $result = $calculationService->validateAddressAndCalculateCost($request->address);
            
            if ($result['valid']) {
                return response()->json([
                    'data' => $result,
                ]);
            } else {
                return response()->json([
                    'valid' => false,
                    'error' => $result['error'] ?? 'Ошибка валидации адреса',
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при расчете стоимости доставки: ' . $e->getMessage());
            
            return response()->json([
                'valid' => false,
                'error' => 'Ошибка при расчете стоимости доставки',
            ], 500);
        }
    }
}

