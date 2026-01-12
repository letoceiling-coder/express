<?php

namespace App\Services;

use App\Models\DeliverySetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для расчета стоимости доставки
 */
class DeliveryCalculationService
{
    protected DeliverySetting $settings;
    protected string $geocoderUrl = 'https://geocode-maps.yandex.ru/1.x/';

    /**
     * Конструктор
     */
    public function __construct(?DeliverySetting $settings = null)
    {
        $this->settings = $settings ?? DeliverySetting::getSettings();
    }

    /**
     * Проверка валидности API ключа Яндекс.Геокодера
     * 
     * @return bool true если ключ валиден, false если нет
     */
    public function validateApiKey(): bool
    {
        $apiKey = $this->settings->yandex_geocoder_api_key;
        if (empty($apiKey)) {
            return false;
        }

        try {
            // Пытаемся геокодировать простой тестовый адрес
            $testAddress = 'Москва';
            $response = Http::timeout(5)->get($this->geocoderUrl, [
                'apikey' => $apiKey,
                'geocode' => $testAddress,
                'format' => 'json',
                'results' => 1,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Проверяем наличие результатов
                $featureMember = $data['response']['GeoObjectCollection']['featureMember'][0] ?? null;
                return $featureMember !== null;
            }

            return false;
        } catch (\Exception $e) {
            Log::warning('Error validating Yandex Geocoder API key', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Валидация и геокодинг адреса
     * 
     * @param string $address Адрес
     * @return array|null ['latitude' => float, 'longitude' => float, 'formatted_address' => string] или null при ошибке
     */
    public function geocodeAddress(string $address): ?array
    {
        if (empty(trim($address))) {
            return null;
        }

        $apiKey = $this->settings->yandex_geocoder_api_key;
        if (empty($apiKey)) {
            Log::warning('Yandex Geocoder API key is not set');
            return null;
        }

        try {
            $response = Http::timeout(5)->get($this->geocoderUrl, [
                'apikey' => $apiKey,
                'geocode' => $address,
                'format' => 'json',
                'results' => 1,
            ]);

            if (!$response->successful()) {
                Log::error('Yandex Geocoder API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            
            // Проверяем наличие результатов
            $featureMember = $data['response']['GeoObjectCollection']['featureMember'][0] ?? null;
            if (!$featureMember) {
                Log::warning('Yandex Geocoder: address not found', ['address' => $address]);
                return null;
            }

            $geoObject = $featureMember['GeoObject'];
            $point = $geoObject['Point']['pos'];
            
            // Координаты в формате "долгота широта"
            [$longitude, $latitude] = explode(' ', $point);
            
            $formattedAddress = $geoObject['metaDataProperty']['GeocoderMetaData']['text'] ?? $address;
            
            return [
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'formatted_address' => $formattedAddress,
            ];
        } catch (\Exception $e) {
            Log::error('Error geocoding address', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Расчет расстояния между двумя точками (формула Хаверсина)
     * 
     * @param float $lat1 Широта первой точки
     * @param float $lon1 Долгота первой точки
     * @param float $lat2 Широта второй точки
     * @param float $lon2 Долгота второй точки
     * @return float Расстояние в километрах
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Радиус Земли в километрах

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Проверка, что адрес содержит конкретную улицу/дом, а не только город/регион
     * 
     * @param string $originalAddress Исходный адрес, введенный пользователем
     * @param string $formattedAddress Отформатированный адрес от геокодера
     * @return bool true если адрес определен (содержит улицу/дом), false если только город/регион
     */
    protected function isAddressDetailed(string $originalAddress, string $formattedAddress): bool
    {
        $formattedLower = mb_strtolower(trim($formattedAddress));
        $formattedNormalized = trim($formattedAddress);
        
        // Проверяем, содержит ли отформатированный адрес указания на улицу/дом
        $streetIndicators = [
            'ул.', 'улица', 'ул ',
            'проспект', 'пр.', 'пр ',
            'переулок', 'пер.', 'пер ',
            'бульвар', 'бул.', 'бул ',
            'площадь', 'пл.', 'пл ',
            'дом', 'д.', 'д ',
            'строение', 'стр.', 'стр ',
            'корпус', 'корп.', 'корп ',
        ];
        
        $hasStreetIndicator = false;
        foreach ($streetIndicators as $indicator) {
            if (mb_strpos($formattedLower, $indicator) !== false) {
                $hasStreetIndicator = true;
                break;
            }
        }
        
        // Если есть указание на улицу/дом - адрес определен
        if ($hasStreetIndicator) {
            return true;
        }
        
        // Проверяем, содержит ли отформатированный адрес только паттерн "Россия, ... область, ..."
        // Это означает, что адрес не определен (только город/регион)
        // Примеры: "Россия, Свердловская область, Екатеринбург"
        $regionPatterns = [
            '/^россия,\s*[^,]+\s*область,\s*[^,]+$/ui',
            '/^российская\s+федерация,\s*[^,]+\s*область,\s*[^,]+$/ui',
            '/^россия,\s*[^,]+\s*край,\s*[^,]+$/ui',
            '/^российская\s+федерация,\s*[^,]+\s*край,\s*[^,]+$/ui',
        ];
        
        foreach ($regionPatterns as $pattern) {
            if (preg_match($pattern, $formattedNormalized)) {
                // Это только город/регион - адрес не определен
                return false;
            }
        }
        
        // Если не соответствует паттерну только города/региона - считаем адрес определенным
        return true;
    }

    /**
     * Валидация адреса и расчет стоимости доставки
     * 
     * @param string $address Адрес доставки
     * @param float $cartTotal Сумма корзины
     * @return array ['valid' => bool, 'address' => string, 'coordinates' => array, 'distance' => float, 'cost' => float, 'zone' => string] или ['valid' => false, 'error' => string]
     */
    public function validateAddressAndCalculateCost(string $address, float $cartTotal = 0): array
    {
        // Проверяем, включена ли система расчета доставки
        if (!$this->settings->is_enabled) {
            return [
                'valid' => false,
                'error' => 'Система расчета доставки отключена',
            ];
        }

        // Проверяем наличие координат точки начала доставки
        if (!$this->settings->origin_latitude || !$this->settings->origin_longitude) {
            return [
                'valid' => false,
                'error' => 'Точка начала доставки не настроена',
            ];
        }

        // Геокодинг адреса
        $geocodeResult = $this->geocodeAddress($address);
        if (!$geocodeResult) {
            return [
                'valid' => false,
                'error' => 'Адрес не найден. Проверьте правильность адреса',
            ];
        }

        // Проверяем, что адрес определен (содержит улицу/дом, а не только город/регион)
        if (!$this->isAddressDetailed($address, $geocodeResult['formatted_address'])) {
            return [
                'valid' => false,
                'error' => 'Адрес не определен',
            ];
        }

        // Расчет расстояния
        $distance = $this->calculateDistance(
            $this->settings->origin_latitude,
            $this->settings->origin_longitude,
            $geocodeResult['latitude'],
            $geocodeResult['longitude']
        );

        // Расчет стоимости доставки с учетом порога бесплатной доставки
        $cost = $this->settings->getDeliveryCost($distance, $cartTotal);

        // Определение зоны
        $zone = $this->getZoneName($distance);

        return [
            'valid' => true,
            'address' => $geocodeResult['formatted_address'],
            'coordinates' => [
                'latitude' => $geocodeResult['latitude'],
                'longitude' => $geocodeResult['longitude'],
            ],
            'distance' => round($distance, 2),
            'cost' => $cost,
            'zone' => $zone,
        ];
    }

    /**
     * Получить название зоны по расстоянию
     * 
     * @param float $distance Расстояние в км
     * @return string Название зоны
     */
    protected function getZoneName(float $distance): string
    {
        $zones = $this->settings->delivery_zones ?? [];
        
        // Сортируем зоны по max_distance
        usort($zones, function ($a, $b) {
            $maxA = $a['max_distance'] ?? PHP_FLOAT_MAX;
            $maxB = $b['max_distance'] ?? PHP_FLOAT_MAX;
            return $maxA <=> $maxB;
        });
        
        foreach ($zones as $zone) {
            $maxDistance = $zone['max_distance'] ?? null;
            
            if ($maxDistance === null) {
                return 'свыше ' . ($zones[count($zones) - 2]['max_distance'] ?? 12) . ' км';
            }
            
            if ($distance <= $maxDistance) {
                return 'до ' . $maxDistance . ' км';
            }
        }
        
        return 'дальняя зона';
    }
}

