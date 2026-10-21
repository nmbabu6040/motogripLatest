# Testing

`php artisan test` runs 76 Pest tests on an in-memory SQLite database (no setup needed). They cover login, roles and escalation, settings, catalog and specifications, filtering, comparison, document permissions, inquiries and dealer applications, newsletter, blog publishing, comments, replies, likes and moderation, SEO output, sitemap, redirects, ads gating, affiliate attribution, reports, backup authorisation, restore safeguards and security headers.

Before launch, also run on MySQL 8: set `DB_CONNECTION=mysql` and a separate test database in `phpunit.xml`, then `php artisan migrate:fresh --seed` and the tests again. Not covered by automation and to be done by hand: phone, tablet and desktop layouts, keyboard-only navigation, a screen-reader pass, Lighthouse and Core Web Vitals measurements, real email delivery, and a backup restore drill.
