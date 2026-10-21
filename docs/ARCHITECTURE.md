# Architecture

Server-rendered Laravel 12 monolith. Blade + Alpine.js, Tailwind v4 via Vite. No separate SPA.

## Table groups
- Access: users (+status, locale), roles, permissions (spatie), activity_log (spatie)
- Site: settings, menus, menu_items, homepage_sections, pages, redirects, faqs
- Catalog: product_categories, products, product_features, product_specifications, product_approvals, packaging_options, product_related, documents, product_views, document_downloads. Images use spatie media library.
- Sales: inquiries, dealer_applications, dealers, internal_notes, newsletter_subscribers
- Blog: author_profiles, blog_categories, blog_tags, blog_posts, blog_post_tag, blog_post_revisions, comments, comment_reports, likes
- Optional: affiliates, affiliate_campaigns, referral_clicks, affiliate_conversions, affiliate_commissions
- Ops: backup_records (verified_at and restore_tested_at stay null until a real check passes)

## Security notes
- Secrets stay in `.env`. The settings table holds non-secret values only.
- Documents default to a private disk and are served through an authorized route (Phase 3).
- IPs are stored only as salted hashes for rate limiting.
- Likes and conversions are deduplicated by unique constraints in the database.
- No default admin account exists. The first super-admin is created interactively.

## Additional tables (later phases)
blog_post_views (daily aggregate). Product, document and blog view counters are daily aggregates with no IP or user id.

## Request flow
`routes/web.php` loads `routes/admin.php` first (auth area), then registers `routes/public.php` twice: English at `/` and Bengali under `/bn` (route names prefixed `bn.`). A catch-all CMS page route sits last and falls through to admin-managed redirects, then 404.

## Key classes
- `RoleGuard`: privilege-escalation rules. `Settings`: cached, non-secret site settings. `Lang`/`Navigation`: locale-aware URLs and menus.
- `BackupService`: encrypted backups with auditable records, verification. `LikeService`: database-enforced like uniqueness.
- `CaptureReferral` + `AttributeLeadToAffiliate`: lead attribution (feature-flagged).
