#!/bin/bash
set -euo pipefail

cd /var/www/html

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

# Ensure APP_KEY exists (prefer env already injected by Compose)
if [ -z "${APP_KEY:-}" ]; then
  echo "APP_KEY is empty; generating..."
  php artisan key:generate --force --show >/tmp/appkey.txt
  export APP_KEY="$(cat /tmp/appkey.txt)"
  rm -f /tmp/appkey.txt
fi

echo "Waiting for database ${DB_HOST:-db}:${DB_PORT:-3306}..."
for i in $(seq 1 60); do
  if php -r "try { new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:3306), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0);} catch (Throwable \$e) { fwrite(STDERR, \$e->getMessage()); exit(1);} " 2>/dev/null; then
    echo "Database is ready."
    break
  fi
  if [ "$i" -eq 60 ]; then
    echo "Database did not become ready in time."
    exit 1
  fi
  sleep 2
done

if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
  php artisan migrate --force
fi

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"
