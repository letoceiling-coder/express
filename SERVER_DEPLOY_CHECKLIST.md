# Чеклист проверки и настройки сервера для деплоя

## ⚠️ КРИТИЧНО: Настройка PHP 8.2

**Ваш текущий PHP: 5.6.40** ❌  
**Требуется: PHP 8.2+** ✅

### 0.1. Проверка доступных версий PHP на сервере
```bash
# Проверка через which/whereis
which php8.2
which php8.1
which php8.0
which php8
```

```bash
# Проверка в стандартных директориях
ls -la /usr/bin/php* 2>/dev/null
ls -la /usr/local/bin/php* 2>/dev/null
```

```bash
# Проверка через update-alternatives (если доступно)
update-alternatives --list php 2>/dev/null || echo "update-alternatives не доступен"
```

### 0.2. Проверка версии PHP через веб-сервер (если есть доступ)
```bash
# Создайте временный файл для проверки
echo "<?php phpinfo(); ?>" > ~/public_html/phpinfo_temp.php
# Затем откройте в браузере: http://ваш-домен.ru/phpinfo_temp.php
# И удалите после проверки: rm ~/public_html/phpinfo_temp.php
```

### 0.3. Установка PHP 8.2 (если не установлен)

**Для Ubuntu/Debian:**
```bash
# Добавление репозитория
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update

# Установка PHP 8.2 и необходимых расширений
sudo apt-get install -y php8.2 php8.2-cli php8.2-fpm php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-fileinfo php8.2-openssl
```

**Для CentOS/RHEL:**
```bash
# Установка репозитория Remi
sudo yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm

# Включение PHP 8.2
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.2 -y

# Установка PHP 8.2
sudo dnf install -y php php-cli php-fpm php-common php-mysqlnd php-zip php-gd php-mbstring php-curl php-xml php-bcmath php-fileinfo php-openssl
```

**Для shared hosting (cPanel/Plesk):**
```bash
# Обычно доступно через панель управления
# Или через команды (зависит от хостинга):
/opt/cpanel/ea-php82/root/usr/bin/php -v
```

### 0.4. Переключение на PHP 8.2

**Если PHP 8.2 уже установлен, но не активен:**

**Вариант 1: Использование полного пути**
```bash
/usr/bin/php8.2 -v
# Или
/usr/local/bin/php8.2 -v
```

**Вариант 2: Создание алиаса в ~/.bashrc**
```bash
echo 'alias php="/usr/bin/php8.2"' >> ~/.bashrc
source ~/.bashrc
php -v
```

**Вариант 3: Обновление символической ссылки (требует sudo)**
```bash
sudo update-alternatives --set php /usr/bin/php8.2
php -v
```

**Вариант 4: Для shared hosting - создание .htaccess или .user.ini**
```bash
# В директории проекта создайте .htaccess:
echo "AddHandler application/x-httpd-php82 .php" > ~/public_html/.htaccess

# Или .user.ini (для PHP-FPM):
echo "php_version=8.2" > ~/public_html/.user.ini
```

### 0.5. Проверка после переключения
```bash
php -v
which php
```
**Ожидается:** PHP 8.2.x или выше

---

## Шаг 1: Проверка базовых компонентов

Выполните эти команды по порядку и сообщите результат каждой:

### 1.1. Проверка версии PHP (после настройки)
```bash
php -v
```
**Ожидается:** PHP 8.2 или выше

### 1.2. Проверка расположения PHP
```bash
which php
```
**Ожидается:** Путь к исполняемому файлу PHP (например, `/usr/bin/php` или `/usr/bin/php8.2`)

### 1.3. Проверка расширений PHP
```bash
php -m | grep -E "(pdo|mbstring|xml|curl|zip|gd|fileinfo|openssl)"
```
**Ожидается:** Список установленных расширений

### 1.4. Проверка Node.js и npm
```bash
node -v
npm -v
```
**Ожидается:** Версии Node.js (рекомендуется 18+) и npm

### 1.5. Проверка Git
```bash
git --version
```
**Ожидается:** Версия Git

---

## Шаг 2: Проверка Composer

### 2.1. Проверка глобального Composer
```bash
composer --version
```
**Если команда не найдена**, переходите к установке (Шаг 3)

### 2.2. Проверка локального Composer (bin/composer)
```bash
ls -la bin/composer
```
**Если файл существует**, проверьте права:
```bash
ls -la bin/composer
file bin/composer
```

### 2.3. Проверка работоспособности локального Composer
```bash
php bin/composer --version
```
**Ожидается:** Версия Composer

---

## Шаг 3: Установка Composer (если не установлен)

### 3.1. Создание директории bin (если не существует)
```bash
mkdir -p bin
cd bin
```

### 3.2. Скачивание установщика Composer
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
```

### 3.3. Проверка хеша установщика (опционально, для безопасности)
```bash
php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
```
**Ожидается:** "Installer verified"

### 3.4. Установка Composer локально
```bash
php composer-setup.php --install-dir=. --filename=composer
```

### 3.5. Удаление установщика
```bash
rm composer-setup.php
```

### 3.6. Проверка установки
```bash
php composer --version
```
**Ожидается:** Версия Composer

### 3.7. Возврат в корневую директорию проекта
```bash
cd ..
```

### 3.8. Проверка из корневой директории
```bash
php bin/composer --version
```

---

## Шаг 4: Проверка структуры проекта

### 4.1. Переход в директорию проекта
```bash
cd /path/to/your/project
```
*(Замените `/path/to/your/project` на реальный путь к проекту)*

### 4.2. Проверка наличия composer.json
```bash
ls -la composer.json
```

### 4.3. Проверка наличия .env файла
```bash
ls -la .env
```
**Если файла нет**, создайте его:
```bash
cp .env.example .env
```

### 4.4. Проверка прав на директории
```bash
ls -la storage/
ls -la bootstrap/cache/
```
**Ожидается:** Права на запись (обычно 775 или 755)

---

## Шаг 5: Установка зависимостей

### 5.1. Установка PHP зависимостей через Composer
```bash
php bin/composer install --no-dev --optimize-autoloader
```
**Или если Composer установлен глобально:**
```bash
composer install --no-dev --optimize-autoloader
```

### 5.2. Проверка установки зависимостей
```bash
ls -la vendor/
```
**Ожидается:** Директория vendor с установленными пакетами

### 5.3. Установка Node.js зависимостей (в корне проекта)
```bash
npm install
```

### 5.4. Установка Node.js зависимостей для фронтенда
```bash
cd frontend
npm install
cd ..
```

---

## Шаг 6: Настройка Laravel

### 6.1. Генерация ключа приложения (если .env новый)
```bash
php artisan key:generate
```

### 6.2. Проверка конфигурации
```bash
php artisan config:cache
```

### 6.3. Проверка прав на storage и cache
```bash
chmod -R 775 storage bootstrap/cache
```
**Или если нужно изменить владельца:**
```bash
chown -R www-data:www-data storage bootstrap/cache
```
*(Замените `www-data` на пользователя веб-сервера)*

---

## Шаг 7: Проверка базы данных

### 7.1. Проверка подключения к БД
```bash
php artisan db:show
```
**Или проверка через tinker:**
```bash
php artisan tinker
```
Затем в tinker:
```php
DB::connection()->getPdo();
exit
```

### 7.2. Проверка миграций
```bash
php artisan migrate:status
```

---

## Шаг 8: Проверка веб-сервера

### 8.1. Проверка конфигурации Nginx/Apache
```bash
# Для Nginx
nginx -t

# Для Apache
apache2ctl configtest
# или
httpd -t
```

### 8.2. Проверка прав на public директорию
```bash
ls -la public/
```

---

## Шаг 9: Финальная проверка

### 9.1. Очистка всех кешей
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 9.2. Оптимизация для production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 9.3. Проверка версий всех компонентов
```bash
echo "=== PHP ===" && php -v && \
echo "=== Composer ===" && php bin/composer --version && \
echo "=== Node.js ===" && node -v && \
echo "=== npm ===" && npm -v && \
echo "=== Git ===" && git --version
```

---

## Быстрая проверка одной командой

Если хотите проверить все сразу:

```bash
echo "=== PHP ===" && php -v && \
echo "=== PHP Location ===" && which php && \
echo "=== Composer (global) ===" && (composer --version 2>/dev/null || echo "NOT INSTALLED") && \
echo "=== Composer (local) ===" && (php bin/composer --version 2>/dev/null || echo "NOT INSTALLED") && \
echo "=== Node.js ===" && node -v && \
echo "=== npm ===" && npm -v && \
echo "=== Git ===" && git --version && \
echo "=== PHP Extensions ===" && php -m | grep -E "(pdo|mbstring|xml|curl|zip|gd|fileinfo|openssl)" && \
echo "=== Project Structure ===" && ls -la composer.json .env 2>/dev/null && \
echo "=== Storage Permissions ===" && ls -ld storage bootstrap/cache 2>/dev/null
```

---

## Типичные проблемы и решения

### Проблема: "composer: command not found"
**Решение:** Установите Composer локально (Шаг 3) или глобально:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Проблема: "Permission denied" при выполнении bin/composer
**Решение:** Установите права на выполнение:
```bash
chmod +x bin/composer
```

### Проблема: "PHP extension missing"
**Решение:** Установите недостающие расширения (зависит от дистрибутива):
```bash
# Ubuntu/Debian
sudo apt-get install php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd php8.2-mysql

# CentOS/RHEL
sudo yum install php82-mbstring php82-xml php82-curl php82-zip php82-gd php82-mysqlnd
```

### Проблема: "Storage directory not writable"
**Решение:** Установите правильные права:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Следующие шаги после проверки

После успешной проверки всех компонентов:

1. ✅ Убедитесь, что `.env` файл настроен правильно
2. ✅ Выполните миграции: `php artisan migrate --force`
3. ✅ Соберите фронтенд: `npm run build:all`
4. ✅ Настройте веб-сервер (Nginx/Apache)
5. ✅ Настройте cron для очередей (если используется)
6. ✅ Настройте SSL сертификат (для production)

---

**Важно:** Выполняйте команды по порядку и сообщайте результат каждой команды перед переходом к следующей!
