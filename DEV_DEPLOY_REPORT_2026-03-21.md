# ОТЧЁТ: Развёртывание DEV окружения

**Дата:** 2026-03-21  
**Сервер:** arturi51.beget.tech

---

## STEP 1 — DEV STRUCTURE (до очистки)

```
total 60
drwx------+ 3 arturi51 newcustomers  4096 Mar 21 14:48 .
drwx------+  3 root     root         4096 Mar 21 14:48 ..
drwx------+  2 arturi51 newcustomers  4096 Mar 21 14:48 cgi-bin
-rwx------+  1 arturi51 newcustomers 35472 Mar 21 14:48 index.php
```

**Выполнено:** удалён `index.php`, оставлен `cgi-bin`.

**После очистки:**
```
total 24
drwx------+  3 arturi51 newcustomers  4096 Mar 21 15:14 .
drwx------+  3 root     root         4096 Mar 21 14:48 ..
drwx------+  2 arturi51 newcustomers  4096 Mar 21 14:48 cgi-bin
```

---

## STEP 2 — COPY RESULT

**Выполнено:** `rsync -a /home/a/arturi51/hleb/public_html/ /home/a/arturi51/dev.svoihlebekb.ru/public_html/`

**Содержимое DEV после копирования:**
```
app/  bootstrap/  config/  database/  frontend/  public/  resources/
routes/  storage/  vendor/  .env  .git  .htaccess  artisan
composer.json  package.json  node_modules  и др. (полная копия PROD)
```

---

## STEP 3 — ENV

**Было:** `APP_URL=https://svoihlebekb.ru/`  
**Стало:** `APP_URL=http://dev.svoihlebekb.ru`

```bash
sed -i 's|^APP_URL=.*|APP_URL=http://dev.svoihlebekb.ru|' .env
```

---

## STEP 4 — SITE STATUS

**curl -I http://dev.svoihlebekb.ru/**

```
HTTP/1.1 500 Internal Server Error
Server: nginx-reuseport/1.21.1
Content-Type: text/html
X-Powered-By: PHP/5.6.40
```

**Причина 500:** для dev.svoihlebekb.ru используется PHP 5.6, Laravel требует PHP >= 8.2.

---

## STEP 5 — API STATUS

**curl http://dev.svoihlebekb.ru/api/v1/products**

```
Composer detected issues in your platform:
Your Composer dependencies require a PHP version ">= 8.2.0". 
```

Ответ: 500, тот же конфликт версий PHP.

---

## STEP 6 — ERRORS

### Проблема: PHP 5.6 вместо PHP 8.2

На dev.svoihlebekb.ru веб-сервер отдаёт запросы в PHP 5.6.40, проект рассчитан на PHP 8.2+.

### Решение (ручная настройка)

1. Открой панель: **https://cp.beget.ru/sites**
2. Найди домен **dev.svoihlebekb.ru**
3. Нажми на иконку/кнопку напротив домена
4. Выбери версию **PHP 8.2**

После смены версии PHP выполни:

```bash
ssh arturi51@arturi51.beget.tech
cd /home/a/arturi51/dev.svoihlebekb.ru/public_html
/usr/local/bin/php8.2 artisan config:clear
/usr/local/bin/php8.2 artisan cache:clear
```

Затем проверь:

```bash
curl -I http://dev.svoihlebekb.ru/
curl -s http://dev.svoihlebekb.ru/api/v1/products | head -200
```

---

## ИТОГ

| Шаг            | Статус   | Комментарий                      |
|----------------|----------|----------------------------------|
| Очистка DEV    | Выполнено| Удалён index.php                 |
| Копирование    | Выполнено| Полная копия PROD               |
| .env           | Выполнено| APP_URL=http://dev.svoihlebekb.ru |
| Права          | Выполнено| storage, bootstrap/cache 775    |
| Cache clear    | Выполнено| config, cache, route            |
| Сайт           | 500      | Нужна PHP 8.2 в панели Beget    |
| API            | 500      | Та же причина                   |

**Действие:** в панели Beget переключить dev.svoihlebekb.ru на PHP 8.2.
