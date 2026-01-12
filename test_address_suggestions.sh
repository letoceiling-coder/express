#!/bin/bash
# Скрипт для тестирования API подсказок адресов

BASE_URL="http://express.loc/api/v1"

echo "=== Тест 1: Базовый запрос (Ленина, Екатеринбург) ==="
curl -X POST "${BASE_URL}/delivery/address-suggestions" \
  -H "Content-Type: application/json" \
  -d '{"query": "Ленина", "city": "Екатеринбург"}' \
  | jq '.'

echo -e "\n\n=== Тест 2: Без указания города ==="
curl -X POST "${BASE_URL}/delivery/address-suggestions" \
  -H "Content-Type: application/json" \
  -d '{"query": "Мира"}' \
  | jq '.'

echo -e "\n\n=== Тест 3: Ошибка валидации (1 символ) ==="
curl -X POST "${BASE_URL}/delivery/address-suggestions" \
  -H "Content-Type: application/json" \
  -d '{"query": "Л"}' \
  | jq '.'

echo -e "\n\n=== Тест 4: Пустой запрос ==="
curl -X POST "${BASE_URL}/delivery/address-suggestions" \
  -H "Content-Type: application/json" \
  -d '{"query": ""}' \
  | jq '.'

