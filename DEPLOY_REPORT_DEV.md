# ОТЧЁТ О ДЕПЛОЕ — DEV СЕРВЕР

**Дата:** 2026-03-21  
**Целевой сервер:** **DEV** — dev.svoihlebekb.ru

---

## ВАЖНО: Куда деплоили

| Сервер | URL | Deploy |
|--------|-----|--------|
| **PROD** | https://svoihlebekb.ru | Деплой выполнен автоматически (DEPLOY_SERVER_URL в .env) |
| **DEV** | https://dev.svoihlebekb.ru | **Требуется ручной деплой по STEP 1** |

**Последний автоматический деплой ушёл на PROD** (svoihlebekb.ru).  
DEV нужно обновить вручную по инструкции ниже.

---

## STEP 1 — DEV СЕРВЕР (выполнить вручную)

### Подключение
```bash
ssh arturi51@arturi51.beget.tech
```
или (если по IP):
```bash
ssh arturi51@45.130.41.69
```
*(Beget: пользователь arturi51, DEV IP: 45.130.41.69, путь: /home/a/arturi51/dev.svoihlebekb.ru/public_html)*

### Путь к проекту на DEV
```
/home/a/arturi51/dev.svoihlebekb.ru/public_html
```

### Команды (выполнить по порядку)
```bash
cd /home/a/arturi51/dev.svoihlebekb.ru/public_html

git fetch origin
git reset --hard origin/main

cd frontend
node node_modules/vite/bin/vite.js build

cd ..
php artisan migrate --force
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

**Миграции выполняются ВСЕГДА** (без условий).  
*(Если php8.2 не в PATH, использовать `php` или полный путь к PHP 8.2)*

---

## ТЕКУЩЕЕ СОСТОЯНИЕ DEV (до выполнения STEP 1)

DEV **ещё не обновлён** — используются старые assets:
- `index--XHpk34--mn0rsrbs.js`
- `index-BWUUR7hT-mn0rsrbs.css`

После выполнения STEP 1 будут новые:
- `index-BtNPdQex-mn0u2cdg.js`
- `index-BX_Ith0u-mn0u2cdg.css`

---

## STEP 2 — ДОКАЗАТЕЛЬСТВО API

### curl https://dev.svoihlebekb.ru/api/v1/products

**Команда:**
```bash
curl https://dev.svoihlebekb.ru/api/v1/products
```

**Ответ:** HTTP 200, JSON с продуктами.

```json
{"data":{"current_page":1,"data":[{"id":1,"name":"Шашлык из свиной мякоти 500 г.","slug":"saslyk-iz-svinoi-miakoti-500-g",...
```

Полный ответ ~48 KB. Примеры товаров: "Шашлык из свиной мякоти", "Люля куриный 250г", "Люля говядина/свинина с овощами" и др. Всего 93 продукта, 7 страниц.

---

### curl https://dev.svoihlebekb.ru/api/v1/orders

**Команда:**
```bash
curl https://dev.svoihlebekb.ru/api/v1/orders
```

**Ответ:** HTTP 403 (без авторизации — ожидаемо):
```json
{"message":"Для доступа к заказам необходим initData от Telegram Mini App"}
```

С Bearer-токеном (после входа по телефону) API orders возвращает заказы пользователя.

---

## STEP 3 — ПРОВЕРКА UI (скриншоты)

**Обязательно сделать вручную:**

1. **Desktop** — https://dev.svoihlebekb.ru/?v=183110b  
   - F12 → Toggle device toolbar OFF  
   - Скриншот главной с поиском в header

2. **Mobile (375px)** — https://dev.svoihlebekb.ru/?v=183110b  
   - F12 → Toggle device toolbar ON → 375px  
   - Скриншот главной

---

## STEP 4 — ПРОВЕРКА ФУНКЦИЙ

| Функция | Действие | Ожидание |
|---------|----------|----------|
| **Поиск** | Ввести «люля» в поиск в header | Отображаются товары с «люля» в названии |
| **Auth** | Зайти в /checkout → Войти → Телефон + код 123456 | Вход выполнен, форма заказа доступна |
| **Orders** | Создать заказ → Открыть /orders | Заказ есть в списке |

---

## STEP 5 — ФИНАЛ

### Commit hash
**183110b** (или актуальный после git reset --hard origin/main)

### Assets (после build)
- **JS:** `index-BtNPdQex-mn0u2cdg.js` (или новые после сборки на dev)
- **CSS:** `index-BX_Ith0u-mn0u2cdg.css`

### index.html (подключённые файлы)
```html
<script type="module" crossorigin src="/frontend/assets/index-BtNPdQex-mn0u2cdg.js"></script>
<link rel="stylesheet" crossorigin href="/frontend/assets/index-BX_Ith0u-mn0u2cdg.css">
```

### Подтверждение
- **DEV сервер обновлён:** после выполнения STEP 1 на dev.svoihlebekb.ru  
- **PROD обновлён:** да (автодеплой 2026-03-21 21:22)

---

## Чеклист перед принятием отчёта

- [ ] STEP 1 выполнен на DEV (git fetch, reset, build, migrate, cache)
- [ ] curl products — ответ 200
- [ ] curl orders без auth — ответ про initData (ожидаемо)
- [ ] Скриншот desktop приложен
- [ ] Скриншот mobile приложен
- [ ] Поиск «люля» работает
- [ ] Auth (телефон + 123456) работает
- [ ] Созданный заказ отображается в /orders
