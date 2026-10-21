# Security notes

Implemented: CSRF on every form, Blade escaping, HTML sanitised on save (Purifier), validation on every write, Eloquent only (no raw user SQL), policies and permission middleware on every admin route, escalation guards for roles, 14+ character passwords checked against breaches, login throttling, session regeneration, optional 2FA, active-session sign-out, private disk for documents with authorised download route, PDF-only uploads with checksum duplicate detection, random stored filenames, CSV formula neutralisation, security headers and CSP, hashed IPs only, honeypots and rate limits on public forms, audit log for sensitive actions, encrypted backups, secrets only in `.env`.

Known trade-offs:
- CSP allows `'unsafe-eval'` because Alpine.js evaluates template expressions. Switching to Alpine's CSP build would remove it but needs the templates rewritten.
- Guest likes use a salted hash of IP and browser, so people behind one shared address can collide.
- Dependency checks: run `composer audit` and `npm audit` in CI and before each release.

Pre-launch checklist: `APP_DEBUG=false`; HTTPS; secure session cookies; least-privilege DB user; `.env` outside web root; firewall allows only 80/443/SSH; SSH keys only; automatic security updates; backups verified and restored once; super-admin has 2FA on.
