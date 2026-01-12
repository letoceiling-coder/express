<?php
/**
 * Тестовый скрипт для проверки API подсказок адресов
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Api\v1\DeliverySettingsController;

echo "=== Тест API подсказок адресов ===\n\n";

// Тест 1: Базовый запрос
echo "Тест 1: Базовый запрос (Ленина, Екатеринбург)\n";
$request1 = Request::create('/api/v1/delivery/address-suggestions', 'POST', [
    'query' => 'Ленина',
    'city' => 'Екатеринбург'
]);

$controller = new DeliverySettingsController();
$response1 = $controller->getAddressSuggestions($request1);
echo "Status: " . $response1->getStatusCode() . "\n";
echo "Response: " . $response1->getContent() . "\n\n";

// Тест 2: Проверка валидации (слишком короткий запрос)
echo "Тест 2: Проверка валидации (1 символ)\n";
$request2 = Request::create('/api/v1/delivery/address-suggestions', 'POST', [
    'query' => 'Л'
]);

try {
    $response2 = $controller->getAddressSuggestions($request2);
    echo "Status: " . $response2->getStatusCode() . "\n";
    echo "Response: " . $response2->getContent() . "\n\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Тест 3: Проверка наличия API ключа
echo "Тест 3: Проверка наличия API ключа в настройках\n";
$settings = \App\Models\DeliverySetting::getSettings();
if ($settings->yandex_geocoder_api_key) {
    echo "✓ API ключ настроен (длина: " . strlen($settings->yandex_geocoder_api_key) . " символов)\n";
} else {
    echo "✗ API ключ НЕ настроен! Заполните ключ в /admin/settings/delivery\n";
}

echo "\n=== Тесты завершены ===\n";

