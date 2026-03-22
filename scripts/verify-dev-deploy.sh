#!/bin/bash
# Проверка деплоя на DEV: правильный ли билд админки
# Запуск: ssh arturi51@arturi51.beget.tech "bash -s" < scripts/verify-dev-deploy.sh

set -e
cd /home/a/arturi51/dev.svoihlebekb.ru/public_html 2>/dev/null || cd /var/www/dev.svoihlebekb.ru 2>/dev/null || { echo "Путь не найден"; exit 1; }

echo "=== Проверка билда на DEV ==="
echo ""

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
    echo "   ❌ СТАРЫЙ билд! Нужно: assets/admin-B0uRBwbT.js"
    echo ""
    echo "   Выполните обновление:"
    echo "   git fetch origin && git reset --hard origin/main"
    echo "   php8.2 artisan cache:clear && php8.2 artisan view:clear"
  fi
else
  echo "   ❌ manifest.json не найден!"
fi
echo ""

echo "3. API version (что отдаёт сервер):"
curl -s "https://dev.svoihlebekb.ru/api/version" 2>/dev/null | head -5 || echo "   Ошибка запроса"
echo ""
