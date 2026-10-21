#!/usr/bin/env bash
# Apply Phase 2 on top of the Phase 1 project.  Usage: ./apply.sh /path/to/motogrip
set -euo pipefail
OVERLAY="$(cd "$(dirname "$0")" && pwd)"
APP="${1:?Usage: ./apply.sh /path/to/motogrip}"

for d in app config resources routes tests; do cp -R "$OVERLAY/$d/." "$APP/$d/"; done
cd "$APP"

add_provider() {
  grep -q "$1" bootstrap/providers.php || sed -i "s/App\\\\Providers\\\\AppServiceProvider::class,/App\\\\Providers\\\\AppServiceProvider::class,\n    $1::class,/" bootstrap/providers.php
}
add_provider 'App\\Providers\\AuthorizationServiceProvider'
add_provider 'App\\Providers\\FortifyServiceProvider'

grep -q "admin.php" routes/web.php || printf "\nrequire __DIR__.'/admin.php';\n" >> routes/web.php

grep -q MustVerifyEmail app/Models/User.php || sed -i 's/class User extends Authenticatable/class User extends Authenticatable implements \\Illuminate\\Contracts\\Auth\\MustVerifyEmail/' app/Models/User.php
grep -q TwoFactorAuthenticatable app/Models/User.php || sed -i 's/use HasFactory, Notifiable,/use HasFactory, Notifiable, \\Laravel\\Fortify\\TwoFactorAuthenticatable,/' app/Models/User.php

php artisan migrate
php artisan optimize:clear
echo "Phase 2 applied. Check bootstrap/providers.php and app/Models/User.php, then: php artisan test"
