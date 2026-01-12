<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliverySettingsRequest;
use App\Models\DeliverySetting;
use App\Services\DeliveryCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
            
            // Извлекаем новый API ключ для проверки (до удаления из validated)
            $newApiKey = null;
            if (isset($validated['yandex_geocoder_api_key']) && !empty(trim($validated['yandex_geocoder_api_key'] ?? ''))) {
                $newApiKey = trim($validated['yandex_geocoder_api_key']);
            }
            
            // Удаляем пустой API ключ из валидированных данных
            if (isset($validated['yandex_geocoder_api_key']) && empty(trim($validated['yandex_geocoder_api_key'] ?? ''))) {
                unset($validated['yandex_geocoder_api_key']);
            }
            
            // Проверяем новый API ключ ДО сохранения
            if ($newApiKey) {
                // Создаем временную настройку с новым ключом для проверки
                $tempSettings = new DeliverySetting();
                $tempSettings->yandex_geocoder_api_key = $newApiKey;
                $calculationService = new DeliveryCalculationService($tempSettings);
                if (!$calculationService->validateApiKey()) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Ошибка валидации API ключа',
                        'error' => 'Неверный или недействительный API ключ Яндекс.Геокодера. Проверьте правильность ключа.',
                    ], 400);
                }
            }
            
            // Обновляем настройки
            $settings->fill($validated);
            
            // Восстанавливаем API ключ, если он не был передан
            if (!isset($validated['yandex_geocoder_api_key']) && $existingApiKey) {
                $settings->yandex_geocoder_api_key = $existingApiKey;
            }
            
            // Если обновлен адрес начала доставки, попробуем получить координаты
            if (isset($validated['origin_address']) && !empty(trim($validated['origin_address']))) {
                // Если координаты не были переданы вручную, пытаемся геокодировать адрес
                if (!isset($validated['origin_latitude']) || !isset($validated['origin_longitude']) 
                    || !$validated['origin_latitude'] || !$validated['origin_longitude']) {
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
            'cart_total' => ['nullable', 'numeric', 'min:0'],
        ], [
            'address.required' => 'Адрес обязателен',
            'address.string' => 'Адрес должен быть строкой',
            'cart_total.numeric' => 'Сумма корзины должна быть числом',
            'cart_total.min' => 'Сумма корзины не может быть отрицательной',
        ]);

        try {
            $settings = DeliverySetting::getSettings();
            $calculationService = new DeliveryCalculationService($settings);
            
            $cartTotal = $request->input('cart_total', 0);
            $cartTotal = $cartTotal ? (float) $cartTotal : 0;
            $result = $calculationService->validateAddressAndCalculateCost($request->address, $cartTotal);
            
            if ($result['valid']) {
                return response()->json([
                    'valid' => true,
                    'cost' => $result['cost'],
                    'distance' => $result['distance'],
                    'address' => $result['address'],
                    'zone' => $result['zone'],
                    'coordinates' => $result['coordinates'],
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

    /**
     * Получить подсказки адресов через Яндекс Suggest API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAddressSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
        ], [
            'query.required' => 'Запрос обязателен',
            'query.string' => 'Запрос должен быть строкой',
            'query.min' => 'Запрос должен содержать минимум 2 символа',
            'query.max' => 'Запрос слишком длинный',
            'city.string' => 'Город должен быть строкой',
        ]);

        try {
            $settings = DeliverySetting::getSettings();
            
            // Проверяем наличие API ключа
            if (empty($settings->yandex_geocoder_api_key)) {
                return response()->json([
                    'success' => false,
                    'error' => 'API ключ не настроен',
                    'suggestions' => [],
                ], 400);
            }

            $query = trim($request->input('query'));
            $city = $request->input('city', 'Екатеринбург');
            
            // Формируем поисковый запрос
            $searchQuery = $city && mb_strpos(mb_strtolower($query), mb_strtolower($city)) === false
                ? "{$city}, {$query}"
                : $query;

            // Запрос к Яндекс Suggest API
            $apiKey = $settings->yandex_geocoder_api_key;
            $suggestUrl = 'https://suggest-maps.yandex.ru/v1/suggest';
            
            $params = [
                'apikey' => $apiKey,
                'text' => $searchQuery,
                'lang' => 'ru_RU',
                'types' => 'address',
                'results' => 10,
            ];
            
            Log::info('Yandex Suggest API request', [
                'url' => $suggestUrl,
                'query' => $searchQuery,
                'has_api_key' => !empty($apiKey),
                'api_key_length' => strlen($apiKey ?? ''),
            ]);
            
            $response = Http::timeout(5)->get($suggestUrl, $params);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorData = [];
                
                // Пытаемся распарсить JSON ответ с ошибкой
                if (!empty($errorBody)) {
                    $decoded = json_decode($errorBody, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $errorData = $decoded;
                    }
                }
                
                Log::error('Yandex Suggest API error', [
                    'status' => $response->status(),
                    'status_text' => $response->reason(),
                    'body' => $errorBody,
                    'error_data' => $errorData,
                    'headers' => $response->headers(),
                    'query' => $searchQuery,
                ]);
                
                // Более информативное сообщение об ошибке
                $errorMessage = 'Ошибка при получении подсказок';
                if ($response->status() === 403) {
                    $errorMessage = 'Доступ запрещен. Проверьте API ключ и его права доступа к Suggest API';
                } elseif ($response->status() === 401) {
                    $errorMessage = 'Неверный API ключ';
                } elseif ($response->status() === 429) {
                    $errorMessage = 'Превышен лимит запросов к API';
                }
                
                return response()->json([
                    'success' => false,
                    'error' => $errorMessage,
                    'suggestions' => [],
                ], 500);
            }

            $data = $response->json();
            $suggestions = [];

            if (isset($data['results']) && is_array($data['results'])) {
                $cityLower = mb_strtolower($city);
                
                foreach ($data['results'] as $item) {
                    $title = $item['title']['text'] ?? '';
                    $subtitle = $item['subtitle']['text'] ?? '';
                    $fullAddress = $title . ($subtitle ? ', ' . $subtitle : '');
                    
                    // Фильтруем только адреса, содержащие указанный город
                    if ($fullAddress && mb_strpos(mb_strtolower($fullAddress), $cityLower) !== false) {
                        $suggestions[] = [
                            'value' => $fullAddress,
                            'display' => $title,
                            'subtitle' => $subtitle,
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'suggestions' => array_slice($suggestions, 0, 10), // Ограничиваем до 10 результатов
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка при получении подсказок адресов: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при получении подсказок',
                'suggestions' => [],
            ], 500);
        }
    }
}

