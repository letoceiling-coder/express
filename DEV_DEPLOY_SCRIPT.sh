#!/bin/bash
# Деплой на DEV сервер (dev.svoihlebekb.ru)
# Выполнить: ssh root@DEV_IP "bash -s" < DEV_DEPLOY_SCRIPT.sh
# Или подключиться и запустить вручную

set -e
cd /home/a/arturi51/dev.svoihlebekb.ru/public_html || cd /var/www/dev.svoihlebekb.ru || { echo "Укажите путь к dev"; exit 1; }

echo "=== STEP 1: Git ==="
git fetch origin
git reset --hard origin/main
git log -1 --oneline

echo "=== STEP 2: Frontend build ==="
cd frontend
node node_modules/vite/bin/vite.js build
cd ..

echo "=== STEP 3: Migrations ==="
php8.2 artisan migrate --force

echo "=== STEP 4: Cache clear ==="
php8.2 artisan cache:clear
php8.2 artisan view:clear
php8.2 artisan route:clear

echo "=== DONE ==="
