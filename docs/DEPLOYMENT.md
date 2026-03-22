# DEPLOYMENT — Полное руководство по деплою

**Дата создания:** 2026-03-22  
**Проект:** svoihlebekb.ru (Laravel + React + Vite)  
**Статус:** Актуально

---

## Содержание

1. [Обзор проекта](#1-обзор-проекта)
2. [Серверы и доступ](#2-серверы-и-доступ)
3. [Стратегия сборки](#3-стратегия-сборки)
4. [Полный процесс деплоя (DEV)](#4-полный-процесс-деплоя-dev)
5. [Деплои 2026-03-22 — хронология](#5-деплои-2026-03-22--хронология)
6. [Структура артефактов](#6-структура-артефактов)
7. [Миграции и кэш](#7-миграции-и-кэш)
8. [Верификация после деплоя](#8-верификация-после-деплоя)
9. [Устранение неполадок](#9-устранение-неполадок)
10. [Связанные документы](#10-связанные-документы)

---

## 1. Обзор проекта

| Компонент | Технология |
|-----------|------------|
| Backend | Laravel (PHP 8.2) |
| Frontend (web) | React 18 + Vite 5 + TypeScript |
| Стилизация | Tailwind CSS, Radix UI |
| State | Zustand |
| Хостинг | Beget |

**Структура сборки:**
- **Vue Admin** → `public/build/` (manifest.json, assets)
- **React Web/MiniApp** → `public/frontend/` (index.html, assets/index-*.js, index-*.css)

**Особенности Vite:**
- `base: "/frontend/"` — все ассеты под `/frontend/assets/`
- Имена файлов содержат hash + timestamp для обхода кэша Telegram Mini App
- Каждая сборка генерирует уникальные имена: `index-[hash]-[timestamp].js`

---

## 2. Серверы и доступ

### DEV (dev.svoihlebekb.ru)

| Параметр | Значение |
|----------|----------|
| **URL** | https://dev.svoihlebekb.ru |
| **SSH** | `ssh arturi51@arturi51.beget.tech` |
| **Путь** | `/home/a/arturi51/dev.svoihlebekb.ru/public_html` |
| **IP** | 45.130.41.69 |
| **Деплой** | Ручной (git pull + migrate + cache) |
| **Сборка фронта** | Локально (коммит в git) или на сервере (по скрипту) |

### PROD (svoihlebekb.ru)

| Параметр | Значение |
|----------|----------|
| **URL** | https://svoihlebekb.ru |
| **Путь** | `/home/a/arturi51/hleb/public_html` |
| **Деплой** | Автоматический (POST `/api/deploy` с токеном) |
| **Сборка** | Локально, assets коммитятся в git |

---

## 3. Стратегия сборки

### Вариант A: Локальная сборка (рекомендуется)

1. На локальной машине:
   ```bash
   cd frontend
   rm -rf ../public/frontend/assets   # опционально — чистый билд
   npm run build:safe                 # tsc --noEmit && vite build
   ```
2. Артефакты попадают в `public/frontend/`
3. Коммит: `git add . && git commit -m "..." && git push origin main`
4. На сервере: `git fetch && git reset --hard origin/main` — новые assets уже в репо

**Преимущества:** единая среда сборки, TypeScript проверяется до деплоя, быстрый деплой на сервере.

### Вариант B: Сборка на сервере (DEV)

1. На сервере после `git pull`:
   ```bash
   cd frontend
   node node_modules/vite/bin/vite.js build
   cd ..
   ```
2. Используется, если DEV не получает автодеплой и нужна свежая сборка из текущего кода.

**Скрипт:** `DEV_DEPLOY_SCRIPT.sh` — см. корень проекта.

---

## 4. Полный процесс деплоя (DEV)

### Шаг 1: Локально (опционально — если менялся фронт)

```bash
cd frontend
npm run build:safe
cd ..
git add .
git commit -m "feat: описание изменений"
git push origin main
```

### Шаг 2: Подключение к серверу

```bash
ssh arturi51@arturi51.beget.tech
```

### Шаг 3: Обновление кода

```bash
cd /home/a/arturi51/dev.svoihlebekb.ru/public_html

git fetch origin
git reset --hard origin/main
git log -1 --oneline   # проверка коммита
```

### Шаг 4: Миграции

```bash
php8.2 artisan migrate --force
```

`--force` обязателен для production-like окружений (без интерактивного подтверждения).

### Шаг 5: Очистка кэша

```bash
php8.2 artisan cache:clear
php8.2 artisan view:clear
php8.2 artisan route:clear
```

### Шаг 6: Сборка на сервере (если не собирали локально)

```bash
cd frontend
node node_modules/vite/bin/vite.js build
cd ..
```

**Важно:** если фронт уже собран локально и закоммичен, этот шаг можно пропустить.

### Одной командой (из локальной машины)

```bash
ssh -o BatchMode=yes arturi51@arturi51.beget.tech "cd /home/a/arturi51/dev.svoihlebekb.ru/public_html && git fetch origin && git reset --hard origin/main && php8.2 artisan migrate --force && php8.2 artisan cache:clear && php8.2 artisan view:clear && php8.2 artisan route:clear && git log -1 --oneline"
```

Работает при настроенной SSH-авторизации по ключу (без пароля).

---

## 5. Деплои 2026-03-22 — хронология

### Сессия 1: Исправление orderMode и защита от ошибок

**Коммит:** `e63e40a` — fix: orderMode crash + add strict safety checks

**Проблема:** В `WebLayout.tsx` использовались `orderMode` и `setOrderMode` без определения (ReferenceError).

**Сделано:**
1. Добавлено `const { orderMode, setOrderMode } = useOrderModeStore();`
2. Runtime-проверка: `if (orderMode === undefined) console.error(...)`
3. Fallback: `value={orderMode ?? 'pickup'}`, `onChange={setOrderMode ?? (() => {})}`
4. Включены строгие настройки TypeScript: `strict`, `noImplicitAny`, `strictNullChecks`
5. ESLint: `no-undef: "error"`, `@typescript-eslint/no-unused-vars: "error"`
6. Скрипт `build:safe`: `tsc --noEmit && vite build` — билд падает при TS-ошибках

**Build:** `index-tMV-fG8m-mn0xijjg.js` (775 619 байт)

---

### Сессия 2: Исправление handleSearchFocus

**Коммит:** `244313b` — fix: handleSearchFocus is not defined in MiniAppHeader

**Проблема:** В `MiniAppHeader.tsx` на `onFocus={handleSearchFocus}` переменная не была определена.

**Сделано:**
1. Добавлено `const location = useLocation();`
2. Добавлено:
   ```ts
   const handleSearchFocus = () => {
     if (location.pathname !== '/search') navigate('/search');
   };
   ```

**Build:** `index-CH4xNjpw-mn0xwrnv.js` (775 655 байт)

---

### Сессия 3: Удаление «Категории» и «Преимущества» из header

**Коммит:** `1bf92e5` — ui: remove categories and benefits from header

**Сделано:**
1. Удалены кнопки «Категории» и «Преимущества» из desktop header в `WebLayout.tsx`
2. Удалена функция `scrollToSection`
3. Оставлены «Каталог» и «О нас»

**Build:** `index-Dk5cWzEy-mn0y3cin.js` (775 152 байт)

---

### Сессия 4: Cart UX — логика как в ProductCard

**Коммит:** `ac69fb1` — fix: cart UX - match ProductCard logic (button OR counter, not both)

**Проблема:** На `WebProductDetailPage` одновременно показывались счётчик количества и кнопка «В корзину».

**Сделано:**
1. `quantityInCart === 0` → только кнопка «В корзину»
2. `quantityInCart > 0` → только `[-][quantity][+]`
3. Удалён `localQuantity`, логика через `addItem` / `updateQuantity`
4. При decrement до 0 → `removeItem` → автоматический возврат к кнопке

**Build:** `index-j1w7bUuX-mn0ycx1a.js` (775 091 байт)

---

### Сессия 5: Компактный DeliveryModeToggle в header

**Коммит:** `80ff012` — ui: add compact DeliveryModeToggle for header

**Проблема:** `DeliveryModeToggle` занимал много места и ломал layout header.

**Сделано:**
1. Добавлен проп `compact?: boolean` в `DeliveryModeToggle.tsx`
2. При `compact`: `h-8`, `rounded-md`, `px-2 py-1`, `text-sm`
3. В `WebLayout.tsx`: `<DeliveryModeToggle compact />`
4. MiniApp и HomePage продолжают использовать полный вариант (без `compact`)

**Build:** `index-caiKOfTh-mn0yh613.js` (775 928 байт)

---

### Сессия 6: SMS, Auth, Checkout

**Коммит:** `a74b66e` — feat: SMS codes tracking, device_id, cleanup command; auth and checkout updates

**Сделано:**
1. Миграции: `add_tracking_to_sms_codes_table`, `add_device_id_to_sms_codes_table`
2. Команда `SmsCodesCleanup` для очистки старых SMS-кодов
3. Обновления: `AuthController`, `OrderController`, `ValidateTelegramInitData`
4. Изменения в `SmsCode`, `IqSmsService`, `config/sms.php`
5. Обновления фронта: `AuthModal`, `WebCheckoutPage`, `api/index.ts`

**Миграции на сервере:**
```
2026_03_22_120000_add_tracking_to_sms_codes_table ............. DONE
2026_03_22_120001_add_device_id_to_sms_codes_table ............. DONE
```

---

## 6. Структура артефактов

### public/frontend/ (React)

```
public/frontend/
├── index.html
├── assets/
│   ├── index-[hash]-[timestamp].js   # основной бандл
│   └── index-BX_Ith0u-[timestamp].css
├── robots.txt
└── placeholder.svg
```

### Текущий index.html (последний деплой)

```html
<script type="module" crossorigin src="/frontend/assets/index-caiKOfTh-mn0yh613.js"></script>
<link rel="stylesheet" crossorigin href="/frontend/assets/index-KocUkJUZ-mn0yh613.css">
```

*При следующей сборке имена файлов изменятся из-за hash и timestamp.*

---

## 7. Миграции и кэш

### Миграции

| Команда | Описание |
|---------|----------|
| `php8.2 artisan migrate --force` | Запуск всех pending миграций |
| `php8.2 artisan migrate:status` | Статус миграций |

**Рекомендация:** всегда выполнять `migrate --force` после `git pull` на сервере.

### Очистка кэша

| Команда | Что очищает |
|---------|-------------|
| `php8.2 artisan cache:clear` | Application cache |
| `php8.2 artisan view:clear` | Скомпилированные Blade-шаблоны |
| `php8.2 artisan route:clear` | Кэш маршрутов |
| `php8.2 artisan config:clear` | Кэш конфигурации (опционально) |

---

## 8. Верификация после деплоя

### Быстрая проверка

```bash
# На сервере
cd /home/a/arturi51/dev.svoihlebekb.ru/public_html
git log -1 --oneline
grep -E 'index-.*\.js' public/frontend/index.html
ls -la public/frontend/assets/index-*.js
```

### API

```bash
curl -s -o /dev/null -w "%{http_code}" https://dev.svoihlebekb.ru/api/v1/products
# Ожидание: 200
```

### UI

1. Открыть https://dev.svoihlebekb.ru/?v=fix
2. DevTools → Console — не должно быть ReferenceError
3. Header — компактный toggle «Доставка / Самовывоз»
4. Страница товара — либо «В корзину», либо счётчик (не оба сразу)

---

## 9. Устранение неполадок

### Ошибка: ReferenceError в консоли

1. Убедиться, что деплой завершён и загружен актуальный JS
2. Добавить `?v=hash` к URL для обхода кэша
3. Проверить, что `index.html` ссылается на существующий `index-*.js`

### Сборка падает: tsc errors

```bash
cd frontend
npm run build:safe
```

Исправить ошибки TypeScript до коммита. `build:safe` блокирует билд при ошибках.

### Миграции не применяются

- Проверить `.env` и подключение к БД
- `php8.2 artisan migrate:status` — список миграций
- При конфликтах — ручная проверка `database/migrations/`

### Старый фронт после деплоя

- Выполнить жёсткий сброс: `git reset --hard origin/main`
- Очистить кэш браузера или использовать `?v=timestamp`
- Проверить, что `public/frontend/assets/` содержит ожидаемые файлы

---

## 10. Связанные документы

| Файл | Описание |
|------|----------|
| `docs/INFRASTRUCTURE.md` | Серверы, переменные окружения, автодеплой |
| `docs/FRONTEND_ARCHITECTURE.md` | Архитектура фронтенда |
| `docs/AUTH_AND_ORDERS.md` | Авторизация и заказы |
| `DEPLOY_REPORT_DEV.md` | Отчёт о деплое (корень проекта) |
| `DEV_DEPLOY_SCRIPT.sh` | Bash-скрипт для ручного деплоя на DEV |

---

**Последнее обновление:** 2026-03-22  
**Актуальный коммит на DEV:** `a74b66e`
