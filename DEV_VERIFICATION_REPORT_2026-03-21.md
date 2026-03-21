# ОТЧЁТ: Проверка DEV окружения после смены PHP на 8.2

**Дата:** 2026-03-21

---

## STEP 1 — ПРОВЕРКА PHP

**CLI:**
```
php -v                    → PHP 5.6.40 (default)
/usr/local/bin/php8.2 -v  → PHP 8.2.28
```

**Веб (phpinfo.php):**
```
curl -sL http://dev.svoihlebekb.ru/phpinfo.php | grep "PHP Version"
→ PHP Version 8.2.28
```

**Итог:** для dev.svoihlebekb.ru используется PHP 8.2.28.

---

## STEP 2 — ПРОВЕРКА LARAVEL

```
/usr/local/bin/php8.2 artisan --version
→ Laravel Framework 12.44.0
```

---

## STEP 3 — CACHE CLEAN

```
config:clear    → OK
cache:clear     → OK
route:clear     → OK
view:clear      → OK
```

---

## STEP 4 — STORAGE

```
storage/         → drwxrwxr-x (775)
storage/logs/    → drwxrwxr-x (775)
```

Права выставлены корректно.

---

## STEP 5 — САЙТ

**HTTP:**
```
HTTP/1.1 301 Moved Permanently
Location: https://dev.svoihlebekb.ru/
```

**HTTPS:**
```
HTTP/2 200
X-Powered-By: PHP/8.2.28
content-type: text/html; charset=utf-8
```

Страница отдаёт React-приложение (Laravel + frontend).

---

## STEP 6 — API

```bash
curl -s https://dev.svoihlebekb.ru/api/v1/products
```

Ответ: JSON с продуктами (структура как на PROD).

---

## STEP 7 — ЛОГИ

Последние записи в `storage/logs/laravel.log`:
```
[2026-03-21 12:25:13] prod.INFO: ForceHttps - dev.svoihlebekb.ru, response_status:200
[2026-03-21 12:25:23] prod.INFO: ForceHttps - dev.svoihlebekb.ru/api/v1/products, response_status:200
```

Ошибок нет.

---

## STEP 8 — phpinfo.php

Временный `public/phpinfo.php` удалён из соображений безопасности.

---

## ИТОГ

| Проверка      | Результат              |
|---------------|------------------------|
| PHP версия    | 8.2.28                 |
| Laravel       | 12.44.0                |
| Cache         | Очищен                 |
| Storage       | Права 775              |
| Сайт HTTPS    | HTTP 200               |
| API products  | JSON OK                |
| Логи          | Без ошибок             |

DEV окружение работает как PROD.
