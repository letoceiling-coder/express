# API эндпоинты веб-версии (React)

## Базовый адрес (dev)

| Назначение | Полный базовый URL |
|------------|---------------------|
| Сайт (витрина) | `https://dev.svoihlebekb.ru/` |
| REST API | `https://dev.svoihlebekb.ru/api/v1` |
| Авторизация | `https://dev.svoihlebekb.ru/api/auth` |

Примеры полных путей (можно копировать в Postman / curl):

- `https://dev.svoihlebekb.ru/api/v1/products`
- `https://dev.svoihlebekb.ru/api/v1/categories?per_page=0`
- `https://dev.svoihlebekb.ru/api/auth/send-code`
- `https://dev.svoihlebekb.ru/api/auth/verify-code`

Для **другого окружения** замените хост, префиксы `/api/v1` и `/api/auth` те же.

**Интеграция второго фронта / CORS / отдельный токен `X-Integration-Token`:** см. **[INTEGRATION_API.md](./INTEGRATION_API.md)** (чеклист эндпоинтов, `.env`, middleware).

---

## Токен и запросы с другого сайта (другой домен)

В проекте используется **Laravel Sanctum**: доступ к защищённым методам — заголовок

```http
Authorization: Bearer <токен>
```

### Откуда взять токен

1. **Обычный пользователь (веб / интеграция «как пользователь»)**  
   После успешного `POST https://dev.svoihlebekb.ru/api/auth/verify-code` сервер возвращает JSON с полем **`token`**. Его и подставляют в `Authorization: Bearer ...`.  
   **Один общий «публичный» токен для всех в документации не указывается** — у каждого пользователя свой токен, срок действия и отзыв настраиваются на бэкенде.

2. **Техническая интеграция (сервер → API, фиксированный ключ)**  
   Создаётся **Personal Access Token** для выделенного пользователя (через админку/tinker: `$user->createToken('integration-name')`). Полученная строка — это и есть долгоживущий Bearer для скриптов на другом сервере.  
   Храните только на стороне сервера интеграции, **не** в публичном фронте и не в git.

3. **Браузер на другом домене**  
   В репозитории включены **`config/cors.php`** и middleware **`IntegrationPartnerCors`**: при заголовке **`X-Integration-Token`** (значение из `API_INTEGRATION_TOKEN` в `.env`) сервер добавляет CORS-заголовки для cross-origin запросов. Подробности и пример токена для dev — в **[INTEGRATION_API.md](./INTEGRATION_API.md)**.  
   Для cookie-сессий Sanctum при необходимости задайте **`SANCTUM_STATEFUL_DOMAINS`** в `.env`.

### Пример запроса с токеном (curl)

```bash
curl -sS -H "Accept: application/json" \
  -H "Authorization: Bearer ВАШ_ТОКЕН_ИЗ_verify-code_ИЛИ_PAT" \
  "https://dev.svoihlebekb.ru/api/v1/orders?per_page=20&page=1"
```

---

### Заголовки по умолчанию

- `Accept: application/json`
- `Content-Type: application/json` (для POST/PUT с телом)
- Для защищённых маршрутов: `Authorization: Bearer <token>` (Sanctum)
- Для SMS: опционально `X-Device-ID` (см. раздел авторизации)

---

## 1. Авторизация (`/api/auth`)

Полный префикс на dev: `https://dev.svoihlebekb.ru/api/auth`

| Метод | Путь | Описание |
|-------|------|----------|
| `POST` | `/api/auth/send-code` | Отправка SMS-кода на телефон. Body: `{ "phone": "+7..." }`. Опционально заголовок `X-Device-ID` (генерируется на клиенте). Лимиты: throttle `sms-send-code-ip`. |
| `POST` | `/api/auth/verify-code` | Подтверждение кода. Body: `{ "phone": "+7...", "code": "123456" }`. Ответ: `user`, `token`. |
| `POST` | `/api/auth/login` | Вход по email/паролю (альтернатива SMS). Body: `{ "email", "password" }`. |
| `POST` | `/api/auth/register` | Регистрация. Body: `{ "name", "email", "password", "password_confirmation" }`. |
| `POST` | `/api/auth/forgot-password` | Запрос ссылки сброса пароля. |
| `POST` | `/api/auth/reset-password` | Сброс пароля по токену. |
| `POST` | `/api/auth/logout` | Выход. **Требует** `Bearer`. |
| `GET` | `/api/auth/user` | Текущий пользователь. **Требует** `Bearer`. |

**Фильтрация:** не применяется (точечные операции).

---

## 2. Пользователь / «кабинет»

Данные пользователя после входа:
- `GET https://dev.svoihlebekb.ru/api/auth/user` — профиль, роли, контакты.

Корзина хранится **на клиенте** (Zustand + `persist`, см. `frontend/src/store/cartStore.ts`), отдельного API «корзины» нет.

---

## 3. Категории (`/api/v1/categories`)

| Метод | Путь | Доступ |
|-------|------|--------|
| `GET` | `/api/v1/categories` | Публичный |
| `GET` | `/api/v1/categories/{id}` | Публичный |

**Query-параметры (список):**

| Параметр | Описание |
|----------|----------|
| `is_active` | `true` / `false` — только активные категории |
| `search` | Поиск по `name`, `slug` (LIKE `%search%`) |
| `sort_by` | Поле сортировки (по умолчанию `sort_order`) |
| `sort_order` | `asc` или `desc` |
| `per_page` | Размер страницы; **`0` = без пагинации, все записи** |

**Пример (веб, как в фронте):**  
`https://dev.svoihlebekb.ru/api/v1/categories?per_page=0`

---

## 4. Товары (`/api/v1/products`)

Префикс на dev: `https://dev.svoihlebekb.ru/api/v1/products`

| Метод | Путь | Доступ |
|-------|------|--------|
| `GET` | `https://dev.svoihlebekb.ru/api/v1/products` | Публичный |
| `GET` | `https://dev.svoihlebekb.ru/api/v1/products/{id}` | Публичный |

**Query-параметры (список):**

| Параметр | Описание |
|----------|----------|
| `category_id` | ID категории |
| `is_available` | `true` / `false` — доступность к покупке |
| `in_stock` | `true` — `stock_quantity > 0`; `false` — нет на складе |
| `search` | Поиск по `name`, `slug`, `sku`, `barcode` |
| `sort_by` | Например `sort_order`, `price`, `created_at` … |
| `sort_order` | `asc` или `desc` |
| `per_page` | **`0` = все товары без пагинации** |

**Пример (каталог):**  
`https://dev.svoihlebekb.ru/api/v1/products?is_available=true&per_page=0&sort_by=sort_order&sort_order=asc&category_id=5`

**Карточка товара:**  
`https://dev.svoihlebekb.ru/api/v1/products/123`

---

## 5. Поиск

**Вариант A — через API (серверный поиск):**  
`https://dev.svoihlebekb.ru/api/v1/products?search=шашлык&is_available=true&per_page=50`

**Вариант B — как на странице `/search` в текущем фронте:**  
Загружаются товары (`GET /api/v1/products?...`) и фильтрация по строке выполняется **в браузере** (по названию и описанию).

---

## 6. Корзина

Отдельных эндпоинтов корзины **нет**. Состав корзины хранится в клиентском состоянии; при оформлении заказа позиции передаются в `POST /api/v1/orders` в поле `items`.

---

## 7. Заказы (`/api/v1/orders`)

База на dev: `https://dev.svoihlebekb.ru/api/v1/orders`

Используется middleware `telegram.initdata`: для **веба** допускается **валидный Bearer token** (Sanctum), без `X-Telegram-Init-Data`.

| Метод | Полный URL (dev) | Описание |
|-------|------------------|----------|
| `GET` | `https://dev.svoihlebekb.ru/api/v1/orders` | Список заказов. Веб: только свои (`user_id`). **Bearer обязателен.** |
| `POST` | `https://dev.svoihlebekb.ru/api/v1/orders` | Создание заказа. **Bearer или** валидный Telegram initData. |
| `GET` | `https://dev.svoihlebekb.ru/api/v1/orders/{id}` | Детали заказа. **Только `auth:sanctum`** (отдельный защищённый маршрут). |
| `POST` | `https://dev.svoihlebekb.ru/api/v1/orders/{id}/cancel` | Отмена заказа. |
| `POST` | `https://dev.svoihlebekb.ru/api/v1/orders/{orderId}/payments/sync-status` | Синхронизация оплаты с ЮKassa. |

**GET — query-параметры (дополнительно к фильтру по пользователю):**

| Параметр | Описание |
|----------|----------|
| `sort_by` | Только: `id`, `created_at`, `updated_at`, `total_amount`, `status`, `order_id`. Иначе подставляется `created_at`. |
| `sort_order` | `asc` или `desc` (по умолчанию `desc`) |
| `per_page` | **Число от 1 до 100** (сервер принудительно ограничивает). Значение `0` или отсутствие — превращается в минимум **1** страница (не «все заказы»). Для полного списка запрашивайте несколько страниц (`page=1,2,…`) или увеличьте `per_page` до 100. |
| `status` | Статус заказа |
| `payment_status` | Статус оплаты |
| `manager_id` | Для админов/менеджеров — фильтр по менеджеру |
| `date_from`, `date_to` | Диапазон дат `created_at` (формат даты для `whereDate`) |
| `search` | Поиск по `order_id`, `phone`, `delivery_address` (LIKE `%search%`) |
| `telegram_id` | В сценариях Telegram / dev-fallback (см. `OrderController::index`) |

Для **обычного веб-пользователя** (не admin/manager): возвращаются только заказы с `user_id` = текущий пользователь.

> **Замечание по фронту:** в `frontend/src/api/index.ts` для веба вызывается `GET /orders?...&per_page=0` — на бэкенде это **не** отключает пагинацию; фактически уходит **1 заказ на страницу**. Для кабинета при необходимости стоит передавать `per_page=100` или реализовать постраничную загрузку.

**POST create — основные поля body (JSON):**

- `order_id` (опционально, уникальный)
- `phone`, `name`, `email`
- `delivery_address`, `delivery_time`, `delivery_type` (`courier` | `pickup`)
- `delivery_cost`, `comment`
- `payment_method`, `total_amount`, `original_amount`, `discount`
- `items[]`: `product_id`, `product_name`, `product_image`, `quantity`, `unit_price`

---

## 8. Оформление заказа и чек-аут

Последовательность на фронте (dev):
1. Авторизация — `POST https://dev.svoihlebekb.ru/api/auth/verify-code` (или `login`).
2. `GET https://dev.svoihlebekb.ru/api/v1/delivery-settings`
3. `POST https://dev.svoihlebekb.ru/api/v1/delivery/calculate-cost`
4. `POST https://dev.svoihlebekb.ru/api/v1/delivery/address-suggestions`
5. `GET https://dev.svoihlebekb.ru/api/v1/payment-methods`
6. `POST https://dev.svoihlebekb.ru/api/v1/orders`
7. Оплата: `POST https://dev.svoihlebekb.ru/api/v1/payments/yookassa/create`
8. `POST https://dev.svoihlebekb.ru/api/v1/orders/{id}/payments/sync-status`

---

## 9. Платежи

| Метод | Полный URL (dev) | Описание |
|-------|------------------|----------|
| `GET` | `https://dev.svoihlebekb.ru/api/v1/payment-methods` | Список **активных** способов оплаты (публично). |
| `GET` | `https://dev.svoihlebekb.ru/api/v1/payment-methods/{id}` | Один способ. Query: **`cart_amount`** — расчёт скидки под сумму корзины. |
| `POST` | `https://dev.svoihlebekb.ru/api/v1/payments/yookassa/create` | Создание платежа ЮKassa. Body: `order_id`, `amount`, `return_url`, опционально `description`, `telegram_id`, `email`. |

Публичный список: только `is_enabled = true`. Webhook ЮKassa (сервер ЮKassa → ваш бэкенд): `POST https://dev.svoihlebekb.ru/api/v1/webhooks/yookassa`.

> Повторная ссылка через `GET .../payments/yookassa/{paymentId}/link` в маршрутах может отсутствовать — используйте `create` и `sync-status`.

---

## 10. Доставка

| Метод | Полный URL (dev) | Описание |
|-------|------------------|----------|
| `GET` | `https://dev.svoihlebekb.ru/api/v1/delivery-settings` | Настройки доставки, минимальные суммы и т.д. |
| `POST` | `https://dev.svoihlebekb.ru/api/v1/delivery/calculate-cost` | Body: `address`, **`cart_total`**. |
| `POST` | `https://dev.svoihlebekb.ru/api/v1/delivery/address-suggestions` | Body: `query`, опционально `city`. |

**Фильтрация:** не query к списку — расчёт по переданному адресу и сумме корзины.

---

## 11. Контент сайта (витрина)

| Метод | Полный URL (dev) | Описание |
|-------|------------------|----------|
| `GET` | `https://dev.svoihlebekb.ru/api/v1/banners` | Баннеры главной. |
| `GET` | `https://dev.svoihlebekb.ru/api/v1/about` | «О нас». |
| `GET` | `https://dev.svoihlebekb.ru/api/v1/legal-documents` | Список документов. |
| `GET` | `https://dev.svoihlebekb.ru/api/v1/legal-documents/{type}` | Текст по типу. |
| `GET` | `https://dev.svoihlebekb.ru/api/v1/settings/support` | Поддержка. |

---

## 12. Сводная таблица: что вызывает веб-клиент (dev)

База: `https://dev.svoihlebekb.ru`

| Раздел | Эндпоинты |
|--------|-----------|
| Авторизация | `POST .../api/auth/send-code`, `POST .../api/auth/verify-code`, `GET .../api/auth/user`, `POST .../api/auth/logout` |
| Категории | `GET .../api/v1/categories?per_page=0` |
| Товары | `GET .../api/v1/products?...`, `GET .../api/v1/products/{id}` |
| Поиск | `GET .../api/v1/products?search=...` или клиентский поиск |
| Корзина | Нет API (только клиент) |
| Кабинет / заказы | `GET .../api/v1/orders?sort_by=created_at&sort_order=desc&per_page=100` + Bearer; `GET .../api/v1/orders/{id}` + Bearer |
| Оформление | `.../delivery-settings`, `.../delivery/calculate-cost`, `.../delivery/address-suggestions`, `.../payment-methods`, `.../orders`, `.../payments/yookassa/create`, `.../orders/{id}/payments/sync-status` |
| Главная / инфо | `.../banners`, `.../about`, `.../legal-documents`, `.../legal-documents/{type}` |

---

## 13. Админ-функции (не публичная витрина)

Для разделов админ-панели (пользователи CMS, настройки ЮKassa, SMS, баннеры и т.д.) используются отдельные маршруты под `GET/PUT /api/v1/...` с **`auth:sanctum`** и часто ролью **admin**. Они описаны в общей документации API и в `AdminMenu`; в типовой **веб-витрине** для покупателя не требуются.

---

## 14. Коды ответов (кратко)

- `200` / `201` — успех  
- `401` — нет или неверный токен  
- `403` — нет initData в Mini App при отсутствии Bearer (для заказов)  
- `422` — ошибка валидации (в т.ч. минимальная сумма заказа на доставку)  
- `429` — слишком много запросов (SMS и др.)

---

## 15. Формат ответа списка заказов

`GET https://dev.svoihlebekb.ru/api/v1/orders` возвращает **Laravel API Resource** (коллекция `OrderResource`), обычно структура вида:

```json
{
  "data": [ /* заказы */ ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 15, "total": 42 }
}
```

Парсинг на фронте должен учитывать и вложенный `data.data` при пагинации.

---

*Документ актуализирован по `routes/api.php`, `OrderController`, `ProductController`, `CategoryController`, `PaymentMethodController`, middleware `ValidateTelegramInitData` и `frontend/src/api/index.ts`.*
