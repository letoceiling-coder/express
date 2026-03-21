# ОТЧЁТ: Аудит изоляции DEV от PROD

**Дата:** 2026-03-21

---

## DATABASE

### DEV (.env)
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arturi51_hleb
DB_USERNAME=arturi51_hleb
```

### PROD (.env)
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arturi51_hleb
DB_USERNAME=arturi51_hleb
```

### Вывод
DEV и PROD используют одну и ту же базу: `arturi51_hleb`.

---

## CACHE

### DEV и PROD (одинаково)
```
CACHE_STORE=database
SESSION_DRIVER=database
```

Кеш и сессии хранятся в общей БД.  
Разные домены (dev.svoihlebekb.ru / svoihlebekb.ru) → разные cookie, но таблицы `cache` и `sessions` общие.

---

## STORAGE

### Конфигурация
```
FILESYSTEM_DISK=local
```

### Пути

| Ресурс | DEV | PROD |
|--------|-----|------|
| storage/app/ | /home/a/arturi51/dev.../storage/app/ | /home/a/arturi51/hleb/.../storage/app/ |
| public/upload | /home/a/arturi51/dev.../public/upload/ | /home/a/arturi51/hleb/.../public/upload/ |
| public/media | /home/a/arturi51/dev.../public/media/ | /home/a/arturi51/hleb/.../public/media/ |
| public/storage | симлинк → PROD storage | /home/a/arturi51/hleb/.../storage/app/public |

### Проблема
```
readlink /home/a/arturi51/dev.svoihlebekb.ru/public_html/public/storage
→ /home/a/arturi51/hleb/public_html/storage/app/public
```

`public/storage` на DEV указывает на PROD-хранилище.  
Запросы к `dev.svoihlebekb.ru/storage/*` отдают файлы из PROD.

Фото и загрузки через MediaController пишутся в `public/upload` — пути разные, изоляция файлов по проекту есть.

---

## QUEUE

### DEV и PROD
```
QUEUE_CONNECTION=database
```

Очереди в общей БД. Если на DEV запущен `php artisan queue:work`, он обрабатывает задачи из той же таблицы, что и PROD.

---

## РИСКИ

### Критические

1. **Общая БД**  
   Любое изменение в DEV (создание/изменение/удаление) затрагивает PROD: заказы, товары, категории, пользователи, настройки и т.д.

2. **Общая очередь**  
   Задачи из DEV и PROD попадают в один и тот же `jobs`. Worker на DEV может обрабатывать PROD-задачи и наоборот.

3. **Symlink на PROD storage**  
   DEV отдаёт файлы из PROD через `public/storage`. Ошибки в DEV могут привести к некорректной раздаче PROD-файлов.

### Важные

4. **Общий кеш**  
   `CACHE_STORE=database` — одна таблица. Сброс кеша на DEV влияет на PROD.

5. **Общие сессии**  
   Таблица `sessions` общая. Теоретически возможны конфликты ключей (маловероятно при разных доменах).

6. **Одинаковый APP_KEY**  
   Шифрование и подписи общие. Необходимо, если DEV и PROD сознательно используют одну БД и общие данные.

---

## РЕКОМЕНДАЦИИ

### 1. Отдельная БД для DEV
Создать БД `arturi51_hleb_dev`, пользователя и в `.env` DEV прописать:
```
DB_DATABASE=arturi51_hleb_dev
DB_USERNAME=arturi51_hleb_dev
DB_PASSWORD=...
```

После этого выполнить миграции и при необходимости залить дамп с PROD.

### 2. Исправить symlink storage на DEV
```bash
cd /home/a/arturi51/dev.svoihlebekb.ru/public_html
rm public/storage
php artisan storage:link
```
Симлинк должен указывать на свой `storage/app/public` DEV.

### 3. Отдельный кеш для DEV (опционально)
```bash
CACHE_STORE=file
```
Или отдельный префикс при `CACHE_STORE=database`, если оставлять общую БД (не рекомендуется).

### 4. Отдельная очередь для DEV
Если оставить общую БД — задать другой `QUEUE_CONNECTION` для DEV, например `sync` или отдельное Redis/таблица.

---

## ИТОГ

| Компонент | Изолирован? | Риск |
|-----------|-------------|------|
| Database  | Нет         | Критический |
| Cache     | Нет         | Высокий |
| Sessions  | Частично    | Средний |
| Queue     | Нет         | Критический |
| Files (upload) | Да    | Низкий |
| Files (storage) | Нет (symlink) | Высокий |

DEV сейчас не изолирован от PROD по данным и инфраструктуре и может напрямую влиять на production.
