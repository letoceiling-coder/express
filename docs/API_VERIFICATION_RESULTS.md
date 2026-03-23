# Проверка API (факты успешных ответов)

**Дата проверки:** 2026-03-23 (UTC)  
**База:** `https://dev.svoihlebekb.ru`  
**Инструмент:** `curl` (Windows)

Ниже — реальные результаты запросов. Публичные эндпоинты **не требуют** токена; заголовок `X-Integration-Token` можно передавать дополнительно (если на сервере задан `API_INTEGRATION_TOKEN` — тогда срабатывает middleware CORS из репозитория после деплоя).

---

## 1. `GET /api/v1/categories?per_page=2`

**Команда:**
```bash
curl -sS -H "Accept: application/json" "https://dev.svoihlebekb.ru/api/v1/categories?per_page=2"
```

**Результат:** `HTTP 200`  
**Факт:** В теле JSON есть `data.data` — массив категорий (например id `1`, `3`), поля `name`, `slug`, `is_active`. Пагинация: `total`: **11**, `per_page`: **2**.

---

## 2. `GET /api/v1/products?per_page=1&is_available=true`

**Команда:**
```bash
curl -sS -H "Accept: application/json" "https://dev.svoihlebekb.ru/api/v1/products?per_page=1&is_available=true"
```

**Результат:** `HTTP 200`  
**Факт:** В `data.data[0]` присутствует товар `id: 1`, `name` (кириллица), `price`, `category`, `image` с `url` / `webp_url`. Всего товаров в выборке: `total`: **93**.

---

## 3. `GET /api/v1/delivery-settings` (с заголовками как у кросс-доменного клиента)

**Команда:**
```bash
curl -sS -H "Accept: application/json" ^
  -H "X-Integration-Token: int_1731cc04e22f721a157faa04a436b6370e5be4c2e0ca38dc" ^
  -H "Origin: https://example.com" ^
  "https://dev.svoihlebekb.ru/api/v1/delivery-settings"
```

**Результат:** `HTTP 200`  
**Факт:** Тело содержит объект настроек: `origin_address`, `origin_latitude`, `origin_longitude`, `delivery_zones`, `min_delivery_order_total_rub` и др.

> На момент проверки публичный GET работает и **без** совпадения токена с серверным `.env` (эндпоинт публичный). Чтобы **подтвердить именно** ветку middleware интеграции (отражение `Origin` в `Access-Control-Allow-Origin`), на сервере должен быть задан тот же `API_INTEGRATION_TOKEN`, что и в заголовке, и задеплоен код с `IntegrationPartnerCors`.

---

## 4. Preflight `OPTIONS` (браузерный сценарий)

**Команда:**
```bash
curl -I -X OPTIONS ^
  -H "Origin: https://example.com" ^
  -H "Access-Control-Request-Method: GET" ^
  -H "X-Integration-Token: int_1731cc04e22f721a157faa04a436b6370e5be4c2e0ca38dc" ^
  "https://dev.svoihlebekb.ru/api/v1/products"
```

**Результат:** `HTTP 204`  
**Факт:** В ответе присутствуют заголовки `Access-Control-*` (в т.ч. `Access-Control-Allow-Origin: *` через стандартный CORS Laravel / `config/cors.php` на сервере).

---

## Вывод

| Проверка | HTTP | Данные в JSON |
|----------|------|----------------|
| Категории | 200 | Да, список + пагинация |
| Товары | 200 | Да, карточка + total |
| Настройки доставки | 200 | Да, полный объект `data` |
| OPTIONS | 204 | CORS-заголовки присутствуют |

API на **dev.svoihlebekb.ru** на момент проверки **отдаёт данные успешно**. Для заказов и профиля нужен отдельный тест с **`Authorization: Bearer`** после `POST /api/auth/verify-code` (личный токен, в открытую документацию не выкладывать).
