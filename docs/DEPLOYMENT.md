# Deployment

## Server
Ubuntu 22.04/24.04 LTS, Nginx, PHP 8.3-FPM (gd, intl, mbstring, mysql, xml, zip, curl), MySQL 8, Supervisor, Node 20 (build only).
Starting size: 2 vCPU, 4 GB RAM, 40 GB disk plus backup space. Scale up after measuring.

## One-time setup
1. Create a deploy user and the folders `/var/www/motogrip/{releases,shared}`; `shared/storage` needs the standard Laravel `storage/*` subfolders, owned by `www-data`.
2. MySQL: create database and a dedicated user with privileges on that database only (no `GRANT`, no global rights). Restore needs `CREATE, DROP, ALTER, INSERT, DELETE, SELECT, UPDATE, INDEX, REFERENCES`.
3. Put `.env` in `shared/` (mode 640). Set at least:
   `APP_ENV=production APP_DEBUG=false APP_URL=https://example.com`, `DB_*`, `MAIL_*`, `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `BACKUP_ARCHIVE_PASSWORD`, `BACKUP_DISK`, `LOG_LEVEL=warning`.
4. Install `deploy/nginx.conf`, then HTTPS: `certbot --nginx -d example.com`. HSTS is sent by the app in production.
5. Install `deploy/supervisor-queue.conf`, `deploy/crontab.txt` and `deploy/logrotate.conf`.
6. First release: `REPO=<git url> deploy/deploy.sh` (skip the backup step the very first time), then `php artisan db:seed --force` and `php artisan motogrip:create-super-admin`.

## Every release
`deploy/deploy.sh` backs up and verifies, builds, migrates, caches, switches the `current` symlink, restarts workers and checks `/health`.

## Rollback
- Code: `ln -sfn /var/www/motogrip/releases/<previous> /var/www/motogrip/current && sudo supervisorctl restart motogrip-queue:* && php artisan queue:restart`.
- Schema: migrations are forward-only. Write additive migrations (add columns/tables, remove later). If a release needs a destructive change, take a verified backup first and use `motogrip:restore` only as a last resort.

## Health and monitoring
- `GET /health` (database, cache, storage) and Laravel's `/up`: point an uptime monitor at them.
- Errors: add Sentry or Flare through its Laravel package; keep `APP_DEBUG=false`. Logs: `storage/logs`, rotated by logrotate.
- Queue failures: `php artisan queue:failed`; they are also pruned after 30 days.
- Backup failures email the support address; the Backups page shows the last good run and last restore test.

## Troubleshooting
| Symptom | Check |
|---|---|
| 500 on every page | `storage/logs/laravel.log`; permissions on `storage` and `bootstrap/cache`; `php artisan config:clear` |
| Blank styles | `npm run build` ran; `public/build/manifest.json` exists |
| No emails | `MAIL_*`; worker running (`supervisorctl status`); Settings shows the mail driver |
| Images missing | `php artisan storage:link`; GD installed |
| Scheduled posts or backups not running | cron entry for `www-data`; `php artisan schedule:list` |
| 419 on forms | session cookie domain/secure flags; clock drift |
| Uploads rejected | `client_max_body_size` and PHP `upload_max_filesize`/`post_max_size` (25M) |
