# Интеграция стороннего приложения (аналог витрины) + CORS

## 1. Проверка: эндпоинты для разработки аналогичного приложения

Ниже — маршруты из `routes/api.php`, сгруппированные по назначению. Все пути ниже относительно префикса **`/api`**.

### Авторизация (веб, SMS)

| Метод | Путь | Статус |
|-------|------|--------|
| POST | `/auth/send-code` | ✅ |
| POST | `/auth/verify-code` | ✅ |
| POST | `/auth/login`, `/auth/register`, `/auth/forgot-password`, `/auth/reset-password` | ✅ |
| POST | `/auth/logout` | ✅ Sanctum |
| GET | `/auth/user` | ✅ Sanctum |

### Публичная витрина (`/api/v1/...`)

| Ресурс | Методы | Статус |
|--------|--------|--------|
| `categories` | GET list, GET `{id}` | ✅ |
| `products` | GET list, GET `{id}` | ✅ |
| `orders` | GET list, POST create | ✅ + middleware `telegram.initdata` (веб: Bearer Sanctum) |
| `orders/{id}/cancel` | POST | ✅ |
| `orders/{orderId}/payments/sync-status` | POST | ✅ |
| `payment-methods` | GET list, GET `{id}` | ✅ |
| `payments/yookassa/create` | POST | ✅ |
| `delivery-settings` | GET | ✅ |
| `delivery/calculate-cost` | POST | ✅ |
| `delivery/address-suggestions` | POST | ✅ |
| `about` | GET | ✅ |
| `banners` | GET | ✅ |
| `settings/support` | GET | ✅ |
| `legal-documents` | GET list, GET `{type}` | ✅ |
| `webhooks/yookassa` | POST | ✅ (сервер ЮKassa) |

### Защищённые (кабинет / детали) — `Authorization: Bearer` (Sanctum)

| Ресурс | Путь | Статус |
|--------|------|--------|
| Заказ | GET `/v1/orders/{id}` | ✅ |
| Прочее (админка, медиа, импорты и т.д.) | см. `routes/api.php` в группе `auth:sanctum` | ✅ |

**Вывод:** для клона витрины (каталог, корзина на клиенте, оформление, оплата, доставка, SMS-логин) необходимые эндпоинты **присутствуют**. Отдельного REST для «корзины» нет — только клиентское состояние.

Подробные параметры и фильтры: [WEB_API_ENDPOINTS.md](./WEB_API_ENDPOINTS.md).

Фактические проверки `curl` (HTTP 200/204, фрагменты ответов): **[API_VERIFICATION_RESULTS.md](./API_VERIFICATION_RESULTS.md)**.

---

## 2. Отдельный токен интеграции и CORS

Запросы из **браузера** с другого домена блокируются политикой CORS, если сервер не отдаёт нужные заголовки. В проекте добавлено:

- **`config/integration.php`** — секрет и список Origin (опционально).
- **Middleware `IntegrationPartnerCors`** — если заголовок **`X-Integration-Token`** совпадает с `API_INTEGRATION_TOKEN` из `.env`, middleware:
  - отвечает на **OPTIONS** (preflight) с нужными `Access-Control-*`;
  - для **GET/POST/…** добавляет к ответу CORS-заголовки.

Это **не заменяет** авторизацию пользователя: заказы и профиль по-прежнему требуют **Bearer Sanctum** после `verify-code`. Токен интеграции лишь помогает **разрешить cross-origin** для публичных или уже авторизованных запросов.

### Переменные `.env`

```env
# Секрет для заголовка X-Integration-Token (обязательно сменить в production)
API_INTEGRATION_TOKEN=

# Необязательно: через запятую разрешённые Origin (если пусто — отражается Origin запроса или *)
API_INTEGRATION_ALLOWED_ORIGINS=https://partner-example.com,https://localhost:5173

# Базовый CORS для /api (см. config/cors.php)
CORS_ALLOWED_ORIGINS=*
```

### Сгенерировать новый токен

```bash
php artisan integration:generate-token
```

Скопируйте вывод в `API_INTEGRATION_TOKEN=...` на сервере.

### Пример значения для **dev** (документация)

> ⚠️ **Не используйте в production.** После публикации репозитория смените токен на свой (`php artisan integration:generate-token`).

| Переменная | Пример (только dev / демо) |
|------------|----------------------------|
| `API_INTEGRATION_TOKEN` | `int_1731cc04e22f721a157faa04a436b6370e5be4c2e0ca38dc` |

Это значение можно временно прописать в `.env` на **dev.svoihlebekb.ru** для теста стороннего фронта. В **production** обязательно сгенерируйте новый токен и **не** храните его в git.

### Пример fetch с другого сайта

```javascript
const API = 'https://dev.svoihlebekb.ru/api/v1';
const INTEGRATION_TOKEN = 'int_...'; // из .env сервера, не хардкодить в публичном репо

fetch(`${API}/products?per_page=10`, {
  headers: {
    'Accept': 'application/json',
    'X-Integration-Token': INTEGRATION_TOKEN,
  },
});
```

Для **POST** с JSON добавьте `Content-Type: application/json` и при необходимости preflight пройдёт за счёт middleware.

### Ограничение по Origin

Если задан `API_INTEGRATION_ALLOWED_ORIGINS`, запросы с `Origin`, **не** входящим в список, получат **403** JSON: `Origin is not allowed for X-Integration-Token`.

---

## 3. Связанные файлы

| Файл | Назначение |
|------|------------|
| `app/Http/Middleware/IntegrationPartnerCors.php` | CORS при валидном `X-Integration-Token` |
| `config/integration.php` | Токен и whitelist Origin |
| `config/cors.php` | Общий CORS для `api/*` |
| `bootstrap/app.php` | Подключение middleware (`prepend`) |
| `app/Console/Commands/IntegrationGenerateToken.php` | `php artisan integration:generate-token` |

---

## 4. Запросы без браузера (curl, сервер)

CORS касается только браузеров. Серверные клиенты (Node, cron, другой backend) могут вызывать API **без** `X-Integration-Token`, достаточно сетевого доступа и при необходимости **Bearer Sanctum** или служебного PAT.
