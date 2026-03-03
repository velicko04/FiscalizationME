#!/usr/bin/env bash
set -euo pipefail

cd /var/www/laravel

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
fi

set_kv() {
  local key="$1"
  local val="$2"
  if grep -qE "^${key}=" .env; then
    sed -i "s#^${key}=.*#${key}=${val}#" .env
  else
    echo "${key}=${val}" >> .env
  fi
}

# Safe defaults for containerized dev run.
set_kv APP_ENV production
set_kv APP_DEBUG false
set_kv SESSION_DRIVER file
set_kv CACHE_STORE file
set_kv QUEUE_CONNECTION sync

# If DB_* are provided as container env vars, make them authoritative in .env.
if [ -n "${DB_CONNECTION:-}" ]; then
  set_kv DB_CONNECTION "${DB_CONNECTION}"
fi
if [ -n "${DB_HOST:-}" ]; then
  set_kv DB_HOST "${DB_HOST}"
fi
if [ -n "${DB_PORT:-}" ]; then
  set_kv DB_PORT "${DB_PORT}"
fi
if [ -n "${DB_DATABASE:-}" ]; then
  set_kv DB_DATABASE "${DB_DATABASE}"
fi
if [ -n "${DB_USERNAME:-}" ]; then
  set_kv DB_USERNAME "${DB_USERNAME}"
fi
if [ "${DB_PASSWORD+x}" = "x" ]; then
  set_kv DB_PASSWORD "${DB_PASSWORD}"
fi

if ! grep -qE ^APP_KEY=base64: .env; then
  php artisan key:generate --force || true
fi

php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true
php artisan cache:clear >/dev/null 2>&1 || true

exec "$@"
