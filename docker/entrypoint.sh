#!/bin/bash
set -e

echo "🚀 Starting Community app..."

# ─── Wait for MySQL ───────────────────────────────────────────────────────────
echo "⏳ Waiting for MySQL..."
until php artisan db:show --json 2>/dev/null | grep -q "name"; do
  sleep 2
done
echo "✅ MySQL is ready."

# ─── Optimize for production ──────────────────────────────────────────────────
if [ "${APP_ENV}" = "production" ]; then
  echo "⚙️  Running production optimizations..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
fi

# ─── Run Migrations ───────────────────────────────────────────────────────────
echo "🗄️  Running migrations..."
php artisan migrate --force

# ─── Fix permissions ──────────────────────────────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "✅ App ready. Starting PHP-FPM..."
exec "$@"
