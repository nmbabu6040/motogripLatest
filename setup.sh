#!/usr/bin/env bash
# Motogrip bootstrap: creates a Laravel 12 project and applies this overlay.
# Needs PHP 8.3+ (with gd, intl, mbstring, pdo_mysql, zip), Composer 2, Node 20+, MySQL 8+.
# Usage: ./setup.sh [target-dir]    (default: ../motogrip)
set -euo pipefail
OVERLAY="$(cd "$(dirname "$0")" && pwd)"
APP="${1:-$OVERLAY/../motogrip}"

composer create-project laravel/laravel:^12.0 "$APP"
cd "$APP"

composer require \
  spatie/laravel-permission spatie/laravel-medialibrary spatie/laravel-activitylog \
  spatie/laravel-backup spatie/laravel-translatable laravel/fortify mews/purifier
composer require --dev pestphp/pest pestphp/pest-plugin-laravel larastan/larastan laravel/pint
composer audit || true    # dependency advisories; review before deploying

npm install alpinejs chart.js trix @fontsource-variable/manrope

php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"

# Overlay: our migrations, models, controllers, views, routes, config, tests
for d in app config database lang resources routes tests deploy docs; do mkdir -p "$APP/$d"; cp -R "$OVERLAY/$d/." "$APP/$d/"; done
cp "$OVERLAY/vite.config.js" "$OVERLAY/.env.motogrip.example" "$OVERLAY/README.md" "$APP/"

# Register providers (idempotent)
add_provider() {
  grep -q "$1" bootstrap/providers.php || sed -i "s/App\\\\Providers\\\\AppServiceProvider::class,/App\\\\Providers\\\\AppServiceProvider::class,\n    $1::class,/" bootstrap/providers.php
}
add_provider 'App\\Providers\\AuthorizationServiceProvider'
add_provider 'App\\Providers\\FortifyServiceProvider'
add_provider 'App\\Providers\\SiteServiceProvider'

# User model: roles, 2FA, email verification (idempotent)
grep -q HasRoles app/Models/User.php || sed -i 's/use HasFactory, Notifiable;/use HasFactory, Notifiable, \\Spatie\\Permission\\Traits\\HasRoles, \\Laravel\\Fortify\\TwoFactorAuthenticatable;/' app/Models/User.php
grep -q MustVerifyEmail app/Models/User.php || sed -i 's/class User extends Authenticatable/class User extends Authenticatable implements \\Illuminate\\Contracts\\Auth\\MustVerifyEmail/' app/Models/User.php

cat <<'MSG'

Next steps (see README.md for detail):
  1. cd into the project; create the MySQL database; cp .env.example .env; php artisan key:generate
  2. Set DB_*, MAIL_*, APP_URL in .env; append .env.motogrip.example
  3. php artisan migrate --seed
  4. php artisan motogrip:create-super-admin
  5. php artisan storage:link && npm run build
  6. php artisan test
MSG
