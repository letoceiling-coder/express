# PowerShell скрипт для тестирования API подсказок адресов

$baseUrl = "http://express.loc/api/v1"

Write-Host "=== Тест 1: Базовый запрос (Ленина, Екатеринбург) ===" -ForegroundColor Green
$body1 = @{
    query = "Ленина"
    city = "Екатеринбург"
} | ConvertTo-Json

$response1 = Invoke-RestMethod -Uri "$baseUrl/delivery/address-suggestions" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body1

$response1 | ConvertTo-Json -Depth 10

Write-Host "`n=== Тест 2: Без указания города ===" -ForegroundColor Green
$body2 = @{
    query = "Мира"
} | ConvertTo-Json

$response2 = Invoke-RestMethod -Uri "$baseUrl/delivery/address-suggestions" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body2

$response2 | ConvertTo-Json -Depth 10

Write-Host "`n=== Тест 3: Ошибка валидации (1 символ) ===" -ForegroundColor Yellow
$body3 = @{
    query = "Л"
} | ConvertTo-Json

try {
    $response3 = Invoke-RestMethod -Uri "$baseUrl/delivery/address-suggestions" `
        -Method POST `
        -ContentType "application/json" `
        -Body $body3 `
        -ErrorAction Stop
    $response3 | ConvertTo-Json -Depth 10
} catch {
    Write-Host "Ожидаемая ошибка валидации:" -ForegroundColor Yellow
    $_.Exception.Response | Select-Object StatusCode, StatusDescription
}

