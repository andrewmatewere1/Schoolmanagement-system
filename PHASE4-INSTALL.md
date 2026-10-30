# SRMS Phase 4 — Production Hardening

## Features implemented
16. Login rate limiting — 5 failed attempts per username/IP key in 15 minutes, plus an IP-wide threshold of 30 failures; successful logins clear the identity window.
17. Security headers — CSP, HSTS on HTTPS, X-Content-Type-Options, X-Frame-Options, Referrer-Policy and Permissions-Policy; secure session cookie settings.
18. Upload security — SQL restore uploads are checked for real HTTP upload status, size, safe filename, MIME type and SQL content markers; executable/web content is rejected; upload storage is denied by web-server rules.
19. Automated testing — dependency-free `tests/run-tests.php` syntax/static test runner.
20. Production deployment — Apache/Nginx examples and deployment checklist under `deploy/`.

## Installation
1. Back up the existing `srms` database.
2. Replace the application files with this Phase 4 package.
3. Import **only** `phase4-migration.sql` into the existing `srms` database.
4. Keep your existing Phase 1–3 data and migrations; do not drop/recreate the database.
5. On production, create `includes/config.local.php` from the example and use a dedicated DB account.
6. Run `php tests/run-tests.php` from the project directory.
7. Serve the site through HTTPS.

## Mobile responsiveness
Existing Bootstrap and `css/srms-responsive.css` files are preserved. No desktop-only UI framework or fixed-width replacement was introduced.
