#!/usr/bin/env bash
# Release-directory deploy with instant rollback. Run on the server as the deploy user.
# Layout: /var/www/motogrip/{releases/<timestamp>, shared/{.env,storage}, current -> releases/<timestamp>}
set -euo pipefail
BASE=/var/www/motogrip
REPO="${REPO:?set REPO to your git remote}"
REL="$BASE/releases/$(date +%Y%m%d%H%M%S)"

# 0. Never deploy without a recent verified backup.
cd "$BASE/current" 2>/dev/null && php artisan motogrip:backup database && php artisan motogrip:backup-verify

git clone --depth 1 "$REPO" "$REL"
cd "$REL"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build && rm -rf node_modules

ln -s "$BASE/shared/.env" .env
rm -rf storage && ln -s "$BASE/shared/storage" storage

php artisan storage:link
php artisan migrate --force          # forward-only; see docs/OPERATIONS.md for rollback of schema changes
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache

ln -sfn "$REL" "$BASE/current"
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart motogrip-queue:*
php artisan queue:restart

curl -fsS https://example.com/health >/dev/null && echo "Deployed $REL" || { echo "Health check failed. Roll back with: ln -sfn <previous release> $BASE/current"; exit 1; }
ls -1dt "$BASE"/releases/* | tail -n +6 | xargs -r rm -rf    # keep 5 releases
