# Настройка команды деплоя для https://neekloai.ru/

## Шаг 1: Настройка переменных окружения

Добавьте следующие переменные в файл `.env`:

```env
# URL сервера для деплоя
DEPLOY_SERVER_URL=https://neekloai.ru

# Токен для авторизации деплоя (сгенерирован автоматически)
DEPLOY_TOKEN=4dc714198d297556aa76904a976abbff1ab3707f4d4533eecbc3c037a62dae07
```

**Важно:** Этот же токен должен быть добавлен в `.env` на сервере `https://neekloai.ru`!

## Шаг 2: Проверка команд сборки

Команда `php artisan deploy` автоматически выполнит:

1. **Сборка Vue админ-панели:**
   ```bash
   npm run build:admin
   ```
   Результат: `public/build/` (manifest.json + assets)

2. **Сборка React приложения:**
   ```bash
   npm run build:react
   ```
   Результат: `public/frontend/` (index.html + assets)

3. **Общая команда сборки:**
   ```bash
   npm run build:all
   ```
   Выполняет обе сборки последовательно.

## Шаг 3: Проверка Git репозитория

Репозиторий уже настроен:
- **Remote:** `https://github.com/letoceiling-coder/express.git`
- **Ветка по умолчанию:** `main`

Проверка:
```bash
git remote -v
# Должно показать:
# origin  https://github.com/letoceiling-coder/express.git (fetch)
# origin  https://github.com/letoceiling-coder/express.git (push)
```

## Шаг 4: Настройка на сервере

### 4.1. Добавьте переменные в `.env` на сервере:

```env
DEPLOY_TOKEN=4dc714198d297556aa76904a976abbff1ab3707f4d4533eecbc3c037a62dae07
```

### 4.2. Проверьте наличие маршрута `/api/deploy`

Маршрут уже настроен в `routes/api.php`:
```php
Route::post('/deploy', [DeployController::class, 'deploy'])
    ->middleware('deploy.token');
```

### 4.3. Настройка middleware `deploy.token`

Middleware должен проверять заголовок `X-Deploy-Token` и сравнивать его с `DEPLOY_TOKEN` из `.env`.

## Шаг 5: Тестирование

### 5.1. Тест в режиме dry-run (без выполнения):

```bash
php artisan deploy --dry-run
```

Эта команда покажет все шаги, которые будут выполнены, без фактического выполнения.

### 5.2. Тест сборки без деплоя:

```bash
npm run build:all
```

Проверьте наличие файлов:
- `public/build/manifest.json` (Vue)
- `public/frontend/index.html` (React)

### 5.3. Полный тест деплоя:

```bash
php artisan deploy --message "Test deployment to neekloai.ru"
```

Если возникнут проблемы с SSL:
```bash
php artisan deploy --message "Test deployment" --insecure
```

## Опции команды deploy

```bash
php artisan deploy [опции]

Опции:
  --message="текст"    Кастомное сообщение для коммита
  --skip-build         Пропустить сборку фронтенда
  --dry-run            Показать что будет сделано без выполнения
  --insecure           Отключить проверку SSL сертификата
  --with-seed          Выполнить seeders на сервере
  --force              Принудительная отправка (force push)
```

## Процесс деплоя

1. **Сборка фронтенда** (`npm run build:all`)
   - Vue админ-панель → `public/build/`
   - React приложение → `public/frontend/`

2. **Проверка Git статуса**
   - Проверка изменений
   - Предупреждение о больших файлах

3. **Настройка Git remote**
   - Проверка/настройка origin

4. **Проверка актуальности коммитов**
   - Сравнение локальной и удаленной ветки

5. **Добавление изменений в Git**
   - `git add .`
   - Принудительное добавление `public/build/` и `public/frontend/`

6. **Создание коммита**
   - `git commit -m "сообщение"`

7. **Отправка в репозиторий**
   - `git push origin main`

8. **Отправка запроса на сервер**
   - POST `https://neekloai.ru/api/deploy`
   - Заголовок: `X-Deploy-Token: <DEPLOY_TOKEN>`
   - Тело: JSON с информацией о коммите

## На сервере выполнится:

1. Git pull из репозитория
2. Composer install (--no-dev --optimize-autoloader)
3. Очистка кешей (package discovery)
4. Миграции (`php artisan migrate --force`)
5. Seeders (только если `--with-seed`)
6. Очистка всех кешей Laravel
7. Оптимизация (`php artisan optimize`)

## Устранение проблем

### Ошибка SSL

Если возникают ошибки SSL при отправке на сервер:
```bash
php artisan deploy --insecure
```

Или добавьте в `.env`:
```env
APP_ENV=local
```

### Ошибка "non-fast-forward"

Если локальная ветка отстает от удаленной:
```bash
git pull origin main
```

Или принудительная отправка (осторожно!):
```bash
php artisan deploy --force
```

### Большие файлы

Команда предупредит о файлах больше 10MB. Рекомендуется добавить их в `.gitignore`.

### Таймаут отправки в Git

Если файлы слишком большие, увеличьте таймаут или проверьте `.gitignore`.

## Безопасность

1. **DEPLOY_TOKEN** - используйте сложный токен, минимум 32 символа
2. **SSL** - в production всегда используйте HTTPS
3. **Middleware** - убедитесь, что middleware `deploy.token` проверяет токен
4. **Логирование** - все действия деплоя логируются на сервере

## Проверка успешности деплоя

После выполнения команды проверьте ответ сервера:

```
✅ Сервер ответил успешно:
   PHP: /usr/bin/php8.2 (v8.2.0)
   Git Pull: success
   Composer: success
   Миграции: успешно
   Seeders: пропущены
   Время выполнения: 45с
   Дата: 2025-01-XX XX:XX:XX
```

