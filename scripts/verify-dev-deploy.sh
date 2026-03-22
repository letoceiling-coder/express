#!/bin/bash
# Проверка и принудительное обновление деплоя на DEV
# Запуск: ssh arturi51@arturi51.beget.tech "bash -s" < scripts/verify-dev-deploy.sh
# Или с автообновлением: ssh arturi51@arturi51.beget.tech "bash -s" -- fix < scripts/verify-dev-deploy.sh

set -e
cd /home/a/arturi51/dev.svoihlebekb.ru/public_html 2>/dev/null || cd /var/www/dev.svoihlebekb.ru 2>/dev/null || { echo "Путь не найден"; exit 1; }

FIX="${1:-}"

echo "=== Проверка билда на DEV ==="
echo ""

# Автообновление если передан fix
if [ "$FIX" = "fix" ]; then
  echo ">>> Принудительное обновление из git..."
  git fetch origin
  git reset --hard origin/main
  php8.2 artisan cache:clear 2>/dev/null || php artisan cache:clear
  php8.2 artisan view:clear 2>/dev/null || php artisan view:clear
  php8.2 artisan route:clear 2>/dev/null || php artisan route:clear
  echo ">>> Готово."
  echo ""
fi

echo "1. Git commit:"
git log -1 --oneline
echo ""

echo "2. Manifest admin build:"
if [ -f public/build/manifest.json ]; then
  ADMIN=$(grep -o '"file": "assets/admin-[^"]*"' public/build/manifest.json | head -1)
  echo "   $ADMIN"
  if echo "$ADMIN" | grep -q "admin-B0uRBwbT"; then
    echo "   ✅ ПРАВИЛЬНЫЙ билд (баннеры на отдельных страницах)"
  else
    echo "   ❌ СТАРЫЙ билд! Ожидается: assets/admin-B0uRBwbT.js"
    echo ""
    echo "   Обновите вручную: ssh ... \"bash -s\" -- fix < scripts/verify-dev-deploy.sh"
  fi
else
  echo "   ❌ manifest.json не найден! Нужна сборка: npm run build:admin"
fi
echo ""

echo "3. Проверка /version:"
curl -s "https://dev.svoihlebekb.ru/version" 2>/dev/null || echo "   Ошибка запроса"
echo ""
