#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="/code/fiskalizacija/FiscalizationME"
APP_DIR="$REPO_DIR/laravel"

cd "$REPO_DIR"

git fetch origin main
git reset --hard origin/main
# Discard all local/untracked changes, but keep runtime files.
git clean -fd \
  -e laravel/.env \
  -e laravel/database/database.sqlite \
  -e laravel/storage/

cd "$APP_DIR"

composer install --no-interaction --prefer-dist
npm install
npm run build
php artisan migrate --force

pkill -f "php artisan serve --host=0.0.0.0 --port=8000" || true
nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/fiscalization-serve.log 2>&1 &

sleep 2
curl -fsS http://127.0.0.1:8000/contracts > /dev/null
