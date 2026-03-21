# ОТЧЁТ: Фиксация состояния проекта перед WEB-рефакторингом

**Дата:** 2026-03-21  
**Сервер:** arturi51.beget.tech (lair)

---

## STEP 1 — GIT STATUS

**Production (hleb):** `/home/a/arturi51/hleb/public_html`

```
On branch main
Your branch is up to date with 'origin/main'.

Untracked files:
  public/media/photos/2026/01/*.jpg (20 файлов)
  public/media/photos/2026/01/variants/
  public/media/photos/2026/01/webp/
  public/upload/
```

**Git branch:**
```
* main
```

**Git remote:**
```
origin  https://github.com/letoceiling-coder/express.git (fetch)
origin  https://github.com/letoceiling-coder/express.git (push)
```

**Git log (до снимка):**
```
5451af4 Deploy: 2026-02-01 21:11:04
f7a8e05 Deploy: 2026-01-31 13:26:11
524cf92 Deploy: 2026-01-31 12:11:24
6396d91 Deploy: 2026-01-31 10:54:56
815b40c Deploy: 2026-01-30 11:28:37
```

---

## STEP 2 — COMMITS

**Выполнено на сервере:**
```bash
git add .
git config user.email 'dsc-23@yandex.ru'
git config user.name 'letoceiling-coder'
git commit -m 'PROD SNAPSHOT BEFORE WEB REFACTOR'
```

**Результат:** коммит создан локально на сервере.

**Хеш коммита:** `69e2543`

**Замечание:** `git push` на сервере не выполнен — нет учётных данных для HTTPS GitHub:
```
fatal: could not read Username for 'https://github.com': No such device or address
```

**Рекомендация:** выполнить push с локальной машины (если настроен доступ к GitHub):
```bash
cd c:\OSPanel\domains\express
git pull ssh://arturi51@arturi51.beget.tech/home/a/arturi51/hleb/public_html main
git push origin main
```
Либо настроить на сервере SSH-ключ или Personal Access Token для GitHub.

---

## STEP 3 — BACKUP BRANCH

**Создана:** да.

```bash
git checkout -b backup/pre-web-refactor
```

**Ветки на сервере:**
```
  backup/pre-web-refactor
* main
  remotes/origin/main
```

**Замечание:** ветка `backup/pre-web-refactor` существует только локально на сервере. Push не выполнен (нет credentials).

---

## STEP 4 — SERVER STRUCTURE

**Реальные пути (Beget):**
- **Production:** `/home/a/arturi51/hleb/public_html`
- **Dev:** `/home/a/arturi51/dev.svoihlebekb.ru/public_html`

**Структура home:**
```
/home/a/arturi51/
├── arturi51.beget.tech/
├── dev.svoihlebekb.ru/
│   └── public_html/     # Пустая (только index.php, cgi-bin) — НЕ git
├── hleb/
│   └── public_html/     # Полный Laravel проект — git repo
```

**Содержимое production (hleb/public_html):**
```
app/  bootstrap/  config/  database/  frontend/  public/  resources/
routes/  storage/  vendor/  .env  .git  artisan  composer.json
package.json  vite.config.js  и др.
```

---

## STEP 5 — SITE STATUS

**Production — https://svoihlebekb.ru/**
```
HTTP/2 200
server: nginx-reuseport/1.21.1
content-type: text/html; charset=utf-8
x-powered-by: PHP/8.2.28
set-cookie: XSRF-TOKEN=...
set-cookie: laravel-session=...
```
Сайт отвечает, Laravel работает.

**Dev — https://dev.svoihlebekb.ru/**
```
HTTP код: 000 (таймаут/недоступность при проверке с сервера)
```
Dev-окружение, вероятно, указывает на другой хост или пока не настроено.

---

## STEP 6 — API STATUS

**Production — GET https://svoihlebekb.ru/api/v1/products**

Ответ: JSON с пагинацией, 93 продукта (7 страниц по 15).

Пример структуры:
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Шашлык из свиной мякоти 500 г.",
        "price": "690.00",
        "category": {...},
        "image": {...}
      },
      ...
    ],
    "last_page": 7,
    "per_page": 15,
    "total": 93
  }
}
```

API в production работает.

---

## ИТОГ

| Параметр            | Статус                                      |
|---------------------|---------------------------------------------|
| Git snapshot commit | Создан локально (`69e2543`)                 |
| Git push            | Не выполнен (нет credentials на сервере)    |
| Backup branch       | Создана локально (`backup/pre-web-refactor`)|
| Production сайт     | HTTP 200, работает                          |
| Production API      | Работает, 93 продукта                       |
| Dev сайт            | Не проверён / недоступен                    |

---

## ДЕЙСТВИЯ ПОСЛЕ ОТЧЁТА

1. Выполнить push в GitHub (с локальной машины или после настройки credentials на сервере).
2. Запушить backup-ветку: `git push origin backup/pre-web-refactor`.
3. Сохранить хеш коммита `69e2543` для возможного отката.
