#!/usr/bin/env bash
set -euo pipefail

cd /var/www/laravel

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
fi

# Safe defaults for containerized dev run without mandatory DB migrations.
for kv in \
  "APP_ENV=production" \
  "APP_DEBUG=false" \
  "SESSION_DRIVER=file" \
  "CACHE_STORE=file" \
  "QUEUE_CONNECTION=sync"; do
  key="${kv%%=*}"
  val="${kv#*=}"
  if grep -qE "^${key}=" .env; then
    sed -i "s#^${key}=.*#${key}=${val}#" .env
  else
    echo "${key}=${val}" >> .env
  fi
done

if ! grep -qE '^APP_KEY=base64:' .env; then
  php artisan key:generate --force || true
fi

php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true

exec "$@"
