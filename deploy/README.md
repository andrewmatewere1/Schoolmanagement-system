# SRMS production configuration

1. Copy `deploy/config.local.php.example` to `includes/config.local.php` and set real credentials. Never upload real secrets into a public repository.
2. Create a least-privilege MySQL application user (SELECT/INSERT/UPDATE/DELETE/CREATE/ALTER/INDEX on the SRMS database as needed for migrations; remove ALTER/CREATE after deployment if your release process permits). Do not use MySQL root in production.
3. Run migrations with the tracked migration runner:
   `php scripts\migrate.php status`
   `php scripts\migrate.php up`
   Do not edit an applied migration. Back up the database before applying pending migrations.
4. Use HTTPS. Set `APP_TIMEZONE` to the school's timezone. Do not enable HSTS until HTTPS works for the entire hostname.
5. Keep `includes/config.local.php`, `tests/`, `deploy/`, `storage/`, and SQL files outside public web access. The root Apache configuration denies private paths, SQL files, migrations, deployment files, and directory listings.
6. Configure a real SMTP relay for reliable email delivery. PHP `mail()` only reports local hand-off; it does not prove delivery. For high-reliability production email, use authenticated SMTP with a maintained mail library.
7. WhatsApp delivery requires a Meta WhatsApp Business Cloud API app, phone number ID, token, recipient opt-in, and applicable approved message templates/policy compliance. The text sender works only where Meta permits free-form messaging (for example, within the active customer-service window); production template messaging may require adapting the worker to an approved template.
8. Schedule the CLI worker every 1–5 minutes: `php /path/to/srms/scripts/send-notifications.php 25`. Run it only from CLI, not a public URL.
9. Create private database backups with `php scripts\backup-db.php`. Set `SRMS_MYSQLDUMP` if the executable is not found automatically. Use Windows Task Scheduler for recurring backups, retention, notifications, and health checks.
10. Before go-live run `php tests/run-tests.php` and `php tests\integration-check.php`, review logs, verify mobile screens, test migrations and backups, run `php scripts\health.php`, and perform a security review.

## Task Scheduler commands
- Migration health: `php scripts\health.php`
- Notifications: `php scripts\send-notifications.php 25`
- Retention: `php scripts\retention.php`
- Database backup: `php scripts\backup-db.php`
- Schedule notification and health tasks frequently; schedule retention weekly and backups daily. Run all tasks as a non-interactive account with access to PHP, XAMPP MariaDB, and private storage.

## Apache notes
Use HTTPS virtual-host configuration, deny directory listing, block access to private config/SQL/backup/test/deploy files, and set `AllowOverride All` only if you rely on `.htaccess`.

## Nginx notes
Add explicit `location` deny rules for `/includes/config.local.php`, `*.sql`, `/tests/`, `/deploy/`, and the private configuration directory. Do not serve backups from the document root.

## InfinityFree deployment procedure

1. Create a site on the InfinityFree control panel and enable HTTPS from the assigned account tools. Use the account's PHP version selector and select PHP 8.x.
2. Create the MySQL database. Copy its host, database name, username, and password from the control-panel database section.
3. Copy `deploy/config.local.php.example` to `includes/config.local.php` on the uploaded site, then replace every placeholder with the assigned database values.
4. Upload the project into the account's `htdocs` directory. Do not upload `.git`, session files, temporary files, live backups, or local credential files outside `deploy/config.local.php.example`.
5. Import the complete live SRMS database baseline through phpMyAdmin. A migration file is only an additive maintenance migration; it is not a replacement for the baseline schema and data.
6. Log in with an existing Super Admin account, open `/deploy-infinityfree.php`, review migration statuses, and select **Apply Pending Migrations** once. Keep the browser open until the result completes.
7. Remove or rename `deploy-infinityfree.php` after the migration run. Keep the `deploy/`, `scripts/`, `tests/`, and `migrations/` directories blocked with the project's `.htaccess` rules, and verify the private URL rules in a browser.
8. Run a release smoke test: login/logout, protected-route redirect, role access, students, teacher, finance, notifications, mobile navigation, uploads, HTTPS assets, and PWA install behavior.

### InfinityFree limitations and required alternatives

- Upload limits, PHP versions, extensions, execution time, and `.htaccess` support are account-specific. Confirm them before configuring mail or workers.
- InfinityFree does not support a dependable XAMPP-style persistent CLI scheduler. Use the account's allowed Cron feature if available, with only an account-approved execution endpoint or external scheduler; never expose `scripts/send-notifications.php` to normal HTTP.
- InfinityFree phpMyAdmin is the primary database backup method. Create a private export and store it outside the document root when possible. The local `scripts/backup-db.php` and Windows Task Scheduler commands do not run on this platform.
- Verify PHP `mail()` delivery and configure an SMTP-capable library or authenticated relay for production email. Confirm sender/domain policy before go-live.
