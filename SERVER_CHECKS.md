# Чек-лист проверок на сервере после копирования проекта

После копирования проекта на сервер выполните следующие проверки в терминале.

## ⚙️ 1. Проверка переменных окружения (.env)

```bash
# Перейти в директорию проекта
cd /path/to/project

# Проверить наличие .env файла
ls -la .env

# Проверить критические переменные
grep -E "DEPLOY_TOKEN|DEPLOY_SERVER_URL|APP_ENV|APP_DEBUG|DB_" .env
```

**Ожидаемый результат:**
- `DEPLOY_TOKEN` должен быть установлен (значение: `4dc714198d297556aa76904a976abbff1ab3707f4d4533eecbc3c037a62dae07`)
- `DEPLOY_SERVER_URL=https://neekloai.ru`
- `APP_ENV=production`
- `APP_DEBUG=false`
- База данных настроена (DB_CONNECTION, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)

**Если переменные отсутствуют, добавьте их в .env:**
```bash
nano .env
# Или используйте другой редактор
```

---

## 🔧 2. Проверка PHP

```bash
# Проверить версию PHP
php -v
# Или если используется php8.2:
php8.2 -v

# Проверить путь к PHP
which php
# Или:
which php8.2

# Проверить расширения PHP (критические для Laravel)
php -m | grep -E "pdo|mbstring|openssl|tokenizer|json|curl|zip|fileinfo"
```

**Ожидаемый результат:**
- PHP версия 8.2 или выше
- Все необходимые расширения установлены

---

## 📦 3. Проверка Composer

```bash
# Проверить наличие Composer
which composer
# Или проверка через PHP:
php composer.phar --version 2>/dev/null || composer --version

# Проверить путь к Composer (если указан в .env)
grep COMPOSER_PATH .env

# Проверить, что зависимости установлены
ls -la vendor/ | head -5
```

**Если Composer не найден, установите его или укажите путь в .env:**
```bash
# Вариант 1: Указать путь в .env
echo "COMPOSER_PATH=/path/to/composer" >> .env

# Вариант 2: Скопировать composer в проект
mkdir -p bin
cp /home/user/.local/bin/composer bin/composer
chmod 755 bin/composer
echo "COMPOSER_PATH=$(pwd)/bin/composer" >> .env
```

---

## 🔗 4. Проверка Git

```bash
# Проверить remote репозитория
git remote -v

# Проверить текущую ветку
git branch --show-current

# Проверить статус репозитория
git status

# Проверить последний коммит
git log -1 --oneline
```

**Ожидаемый результат:**
- Remote: `origin  https://github.com/letoceiling-coder/express.git`
- Ветка: `main` (или `master`)
- Репозиторий в чистом состоянии (без незакоммиченных изменений)

**Если remote неправильный:**
```bash
git remote set-url origin https://github.com/letoceiling-coder/express.git
```

---

## 💾 5. Проверка базы данных

```bash
# Проверить подключение к базе данных через Laravel
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection OK';"
```

**Или через MySQL:**
```bash
# Получить данные из .env
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)

# Проверить подключение
mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SELECT 1;" 2>&1
```

**Проверить наличие таблиц:**
```bash
php artisan migrate:status
```

**Ожидаемый результат:**
- Подключение к базе данных успешно
- Таблицы созданы (или нужно выполнить миграции)

---

## 📁 6. Проверка прав доступа

```bash
# Проверить права на критичные директории
ls -ld storage bootstrap/cache public/upload 2>/dev/null

# Проверить, что директории доступны для записи
test -w storage && echo "storage: OK" || echo "storage: NO WRITE ACCESS"
test -w bootstrap/cache && echo "bootstrap/cache: OK" || echo "bootstrap/cache: NO WRITE ACCESS"
```

**Если права неправильные, установите их:**
```bash
# Установить права на запись
chmod -R 775 storage bootstrap/cache public/upload

# Установить владельца (замените www-data на вашего пользователя веб-сервера)
chown -R www-data:www-data storage bootstrap/cache public/upload
# Или если используется другой пользователь:
chown -R $(whoami):$(whoami) storage bootstrap/cache public/upload
```

---

## 🚀 7. Проверка Laravel (базовые команды)

```bash
# Проверить ключ приложения
php artisan key:generate --show 2>/dev/null || grep APP_KEY .env

# Очистить кеши (для проверки, что команды работают)
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Проверить список маршрутов (должен быть /api/deploy)
php artisan route:list | grep -i deploy
```

**Ожидаемый результат:**
- APP_KEY установлен
- Команды artisan выполняются без ошибок
- Маршрут `/api/deploy` найден в списке

---

## 🔐 8. Проверка токена деплоя

```bash
# Проверить, что токен установлен в .env
grep DEPLOY_TOKEN .env

# Проверить, что токен доступен через config (после очистки кеша)
php artisan tinker --execute="echo config('app.deploy_token') ? 'Token OK' : 'Token NOT FOUND';"
```

**Ожидаемый результат:**
- `DEPLOY_TOKEN` присутствует в .env
- Токен доступен через `config('app.deploy_token')`

**Важно:** Токен должен совпадать с токеном в локальном .env файле!

---

## 🌐 9. Проверка API endpoint для деплоя

```bash
# Получить токен из .env
TOKEN=$(grep DEPLOY_TOKEN .env | cut -d '=' -f2 | tr -d ' "')

# Проверить доступность endpoint (локально, через curl)
curl -X POST https://neekloai.ru/api/deploy \
  -H "X-Deploy-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"test": true}' \
  -w "\nHTTP Status: %{http_code}\n" \
  -k 2>&1 | head -20
```

**Ожидаемый результат:**
- HTTP Status: 200 или 422 (422 - нормально, это означает, что endpoint доступен, но запрос некорректный)
- Не должно быть 403 (Forbidden) или 500 (Internal Server Error)

**Альтернативная проверка через artisan:**
```bash
php artisan route:list | grep -A 2 "deploy"
```

---

## 📦 10. Проверка собранных файлов фронтенда

```bash
# Проверить наличие собранных файлов Vue админки
ls -la public/build/manifest.json 2>/dev/null && echo "Vue build: OK" || echo "Vue build: NOT FOUND"

# Проверить наличие собранных файлов React приложения
ls -la public/frontend/index.html 2>/dev/null && echo "React build: OK" || echo "React build: NOT FOUND"

# Проверить размер директорий (должны быть не пустыми)
du -sh public/build public/frontend 2>/dev/null
```

**Ожидаемый результат:**
- `public/build/manifest.json` существует (Vue админка)
- `public/frontend/index.html` существует (React приложение)
- Директории не пустые

**Если файлы отсутствуют, они должны быть закоммичены в git или собраны на сервере:**
```bash
# Собрать фронтенд на сервере (если нужно)
npm install
cd frontend && npm install && cd ..
npm run build:all
```

---

## 🔍 11. Проверка зависимостей Composer

```bash
# Проверить, что vendor директория существует и не пуста
test -d vendor && echo "vendor directory: OK" || echo "vendor directory: NOT FOUND"
test -f vendor/autoload.php && echo "autoload.php: OK" || echo "autoload.php: NOT FOUND"

# Проверить автозагрузку
php -r "require 'vendor/autoload.php'; echo 'Autoload: OK';"
```

**Если зависимости не установлены:**
```bash
composer install --no-dev --optimize-autoloader
# Или если используется путь из .env:
php composer.phar install --no-dev --optimize-autoloader
```

---

## 🧪 12. Проверка миграций

```bash
# Проверить статус миграций
php artisan migrate:status

# Проверить, нужно ли выполнить миграции
php artisan migrate:status | grep -i "pending\|ran" | head -5
```

**Если есть невыполненные миграции:**
```bash
php artisan migrate --force
```

---

## 🔄 13. Проверка кешей Laravel

```bash
# Очистить все кеши (рекомендуется после настройки)
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Проверить, что кеши очищены (для production потом создадим заново)
ls -la bootstrap/cache/config.php 2>/dev/null && echo "Config cache exists" || echo "Config cache cleared"
```

**После всех проверок, для production оптимизируйте:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🧪 14. Финальная проверка (тест деплоя)

После всех проверок можно протестировать endpoint деплоя:

```bash
# Получить токен
TOKEN=$(grep DEPLOY_TOKEN .env | cut -d '=' -f2 | tr -d ' "')

# Получить текущий commit hash
COMMIT=$(git rev-parse HEAD)
BRANCH=$(git rev-parse --abbrev-ref HEAD)

# Тестовый запрос (без реального деплоя, просто проверка доступности)
curl -X POST https://neekloai.ru/api/deploy \
  -H "X-Deploy-Token: $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"commit_hash\": \"$COMMIT\",
    \"branch\": \"$BRANCH\",
    \"repository\": \"https://github.com/letoceiling-coder/express.git\",
    \"deployed_by\": \"test\",
    \"timestamp\": \"$(date '+%Y-%m-%d %H:%M:%S')\",
    \"run_seeders\": false
  }" \
  -w "\nHTTP Status: %{http_code}\n" \
  -k 2>&1
```

**Ожидаемый результат:**
- HTTP Status: 200
- JSON ответ с `"success": true` или детальной информацией о деплое

---

## 📋 Полный скрипт для быстрой проверки

Создайте файл `check-server.sh`:

```bash
#!/bin/bash

echo "=== Проверка сервера ==="
echo ""

echo "1. Проверка .env..."
if [ -f .env ]; then
    echo "✅ .env найден"
    grep -q "DEPLOY_TOKEN" .env && echo "✅ DEPLOY_TOKEN установлен" || echo "❌ DEPLOY_TOKEN не найден"
    grep -q "DEPLOY_SERVER_URL" .env && echo "✅ DEPLOY_SERVER_URL установлен" || echo "❌ DEPLOY_SERVER_URL не найден"
else
    echo "❌ .env не найден"
fi

echo ""
echo "2. Проверка PHP..."
php -v | head -1

echo ""
echo "3. Проверка Composer..."
which composer >/dev/null && composer --version || echo "❌ Composer не найден"

echo ""
echo "4. Проверка Git..."
git remote -v | head -1
echo "Ветка: $(git branch --show-current)"

echo ""
echo "5. Проверка базы данных..."
php artisan migrate:status >/dev/null 2>&1 && echo "✅ База данных доступна" || echo "❌ Ошибка подключения к БД"

echo ""
echo "6. Проверка прав доступа..."
test -w storage && echo "✅ storage: доступен для записи" || echo "❌ storage: нет доступа"
test -w bootstrap/cache && echo "✅ bootstrap/cache: доступен для записи" || echo "❌ bootstrap/cache: нет доступа"

echo ""
echo "7. Проверка фронтенда..."
test -f public/build/manifest.json && echo "✅ Vue build найден" || echo "❌ Vue build не найден"
test -f public/frontend/index.html && echo "✅ React build найден" || echo "❌ React build не найден"

echo ""
echo "=== Проверка завершена ==="
```

Сделайте скрипт исполняемым и запустите:
```bash
chmod +x check-server.sh
./check-server.sh
```

---

## ⚠️ Важные замечания

1. **Токен деплоя** должен быть одинаковым на локальной машине и на сервере
2. **APP_ENV** должен быть `production` на сервере
3. **APP_DEBUG** должен быть `false` на production
4. **Права доступа** критичны для работы Laravel
5. **Фронтенд файлы** должны быть собраны и закоммичены в git (или собраны на сервере)
6. После изменений в `.env` всегда выполняйте: `php artisan config:clear`

---

## 🔧 Быстрое исправление проблем

Если что-то не работает:

```bash
# 1. Очистить все кеши
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 2. Проверить логи
tail -50 storage/logs/laravel.log

# 3. Проверить права
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 4. Переустановить зависимости (если нужно)
composer install --no-dev --optimize-autoloader

# 5. Выполнить миграции (если нужно)
php artisan migrate --force
```

---

**Последнее обновление:** 2026-01-04

---

## 🚀 Быстрый чек-лист (скопируйте и выполните)

Для быстрой проверки скопируйте и выполните следующие команды по порядку:

```bash
# Перейти в директорию проекта (замените на ваш путь)
cd /path/to/project

# 1. Проверка .env
echo "=== 1. Проверка .env ==="
grep -E "DEPLOY_TOKEN|DEPLOY_SERVER_URL|APP_ENV" .env || echo "❌ Переменные не найдены"

# 2. Проверка PHP
echo ""
echo "=== 2. Проверка PHP ==="
php -v | head -1

# 3. Проверка Composer
echo ""
echo "=== 3. Проверка Composer ==="
composer --version 2>/dev/null || echo "❌ Composer не найден"

# 4. Проверка Git
echo ""
echo "=== 4. Проверка Git ==="
git remote -v | head -1
echo "Ветка: $(git branch --show-current)"

# 5. Проверка базы данных
echo ""
echo "=== 5. Проверка базы данных ==="
php artisan migrate:status >/dev/null 2>&1 && echo "✅ БД доступна" || echo "❌ Ошибка БД"

# 6. Проверка прав доступа
echo ""
echo "=== 6. Проверка прав доступа ==="
test -w storage && echo "✅ storage: OK" || echo "❌ storage: FAIL"
test -w bootstrap/cache && echo "✅ bootstrap/cache: OK" || echo "❌ bootstrap/cache: FAIL"

# 7. Проверка фронтенда
echo ""
echo "=== 7. Проверка фронтенда ==="
test -f public/build/manifest.json && echo "✅ Vue build: OK" || echo "❌ Vue build: NOT FOUND"
test -f public/frontend/index.html && echo "✅ React build: OK" || echo "❌ React build: NOT FOUND"

# 8. Проверка маршрута deploy
echo ""
echo "=== 8. Проверка маршрута deploy ==="
php artisan route:list | grep -i deploy && echo "✅ Маршрут найден" || echo "❌ Маршрут не найден"

# 9. Очистка кешей
echo ""
echo "=== 9. Очистка кешей ==="
php artisan config:clear && echo "✅ config:clear"
php artisan cache:clear && echo "✅ cache:clear"

echo ""
echo "=== Проверка завершена ==="
```

