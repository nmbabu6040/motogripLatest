# Operations runbook

## Backups
- Daily database backup (02:00), weekly files backup, cleanup (03:00) and verification (03:30) run from the scheduler.
- Archives are AES-encrypted zip files. The password is `BACKUP_ARCHIVE_PASSWORD`, kept only in the server `.env` and your password manager, never next to the backups.
- Use an off-site disk (`BACKUP_DISK=s3` with a bucket that has versioning and no public access) so a server loss does not take the backups with it.
- A backup counts as **good** only after `motogrip:backup-verify` passes, and as **proven** only after a restore drill.

## Restore (database)
1. Pick a verified backup ID on Admin > Backups.
2. Preferably practise on a copy of the server first.
3. `php artisan motogrip:restore ID --confirm-db=DATABASE_NAME`. It takes a safety backup, enters maintenance mode, imports, and comes back up.
4. Check the site, sign in, check recent content.
5. Record a successful drill: `php artisan motogrip:backup-tested ID`.
Files: extract `storage/app/public` from the archive with `unzip -P "$BACKUP_ARCHIVE_PASSWORD"`, copy to the server, run `php artisan storage:link`.

## Restore drill (do this before launch, then quarterly)
Fresh test server, install from README, restore the latest backup, confirm products, documents and blog posts load, then run `motogrip:backup-tested`.

## Incident response
1. Contain: enable maintenance mode (Settings, super-admin) or block at the firewall; suspend affected accounts (suspending signs them out everywhere).
2. Preserve: copy `storage/logs` and the Audit log page before changing anything.
3. Rotate credentials (below).
4. Eradicate and recover: patch, redeploy, restore from a verified backup if data was altered.
5. Notify: affected people and any regulator whose rules apply to you, as your legal adviser directs.
6. Review: record what happened and what changes.

## Credential rotation
| Secret | How |
|---|---|
| `APP_KEY` | `php artisan key:generate`; signs everyone out and invalidates signed links and encrypted data, so plan it |
| DB password | change in MySQL, update `.env`, `php artisan config:cache`, restart workers |
| Mail / API keys | rotate at the provider, update `.env`, restart workers |
| `BACKUP_ARCHIVE_PASSWORD` | set a new one; old archives still need the old one, so store both |
| Staff passwords | users reset via the login page; suspend and recreate accounts if compromise is suspected |
After any rotation: `php artisan config:cache && php artisan queue:restart`.

## Privacy and retention
- `motogrip:purge-old-data` (weekly) deletes old closed inquiries, spam, rejected applications and unsubscribed emails, and clears hashed IPs after 90 days. Periods are in `config/motogrip.php`.
- `motogrip:erase-person email@example.com` handles an erasure request for inquiries, applications, newsletter and guest comments.
- Have your privacy policy reviewed for your jurisdiction; add it under Admin > Pages.
