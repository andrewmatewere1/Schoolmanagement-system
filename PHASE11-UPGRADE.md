# SRMS Phase 11: Fee Automation and Academic Archives

## Important
This upgrade preserves existing records and does not drop or recreate the database. Run the Phase 11 migration only after the earlier migrations are applied. Test on a staging copy first using your infrastructure's approved data-preservation procedure.

## Apply in order
1. Verify that Phases 1–10 are installed; the existing `srms.sql` alone is not sufficient.
2. In phpMyAdmin, select the existing SRMS database and import `phase11-fee-archive-migration.sql` once. If the archive index already exists, skip/remove that one `CREATE INDEX` statement before re-running; MySQL does not support `CREATE INDEX IF NOT EXISTS` consistently across versions.
3. Use **Management → Fee Configurations** to set fee packages. “Apply to All Classes” updates every class. The student term ledger is populated from active configured packages only when no payment exists for that class/year/term. Existing ledgers with payments are not overwritten.
4. Use **Student Payment Workspace** to record payments. The payment, append-only audit row, and receipt queue record are committed in one transaction. Do not manually edit or delete payment history.
5. Use **Financial Reports** for class/status/year/term/date filtering. CSV is built in; PDF is a print-ready page that can be saved as PDF from the browser. To enable true `.xlsx` generation, install Composer and run `composer require phpoffice/phpspreadsheet` in the application directory. For automatic binary PDF receipts, run `composer require dompdf/dompdf`, then schedule `php scripts/process-fee-receipts.php 20` with Windows Task Scheduler or cron every minute. This worker is CLI-only and writes receipts under the denied-access `storage/fee-receipts` directory.
6. Use **Academic Archive** to search existing result records by class, student, year, term, month and date range. Date filters use `PostingDate`; historical records without populated academic-period fields will only match date/student/class filters.

## Receipt behavior
Receipts are rendered in a print-ready layout from the committed payment record; the administrator can print or select “Save as PDF”. Payments enqueue a receipt work item. The CLI worker generates a PDF when Dompdf is installed; until the worker runs, administrators can print the receipt page or save it as PDF from the browser.

## Security and removal of backup screens
The backup/restore UI, download endpoint, and bundled backup script have been removed. Database recovery must be managed through the hosting provider or database administrator's approved tools. The teacher password-reset POST handler now explicitly validates CSRF. The shared config also validates CSRF for authenticated POST requests.

## Deployment checklist
- Configure `includes/config.local.php` with a dedicated least-privilege database user; do not use MySQL root in production.
- Enforce HTTPS and set the correct school timezone.
- Remove/rotate any previously shared credentials, including sample accounts. This ZIP cannot determine whether accounts in your live database still use old passwords.
- Run `php tests/run-tests.php` and `php -l` on changed PHP files, then exercise payment, report, and archive flows in staging.
- The application cannot confirm that all legacy MD5 administrator hashes have been migrated without querying the live database. Each successful legacy login is upgraded to `password_hash`; review the live admin table before disabling compatibility.

## Administrator credentials
The bundled SQL dumps no longer seed the known `admin` / `Test@123` account. For a fresh installation, provision the first administrator from a private CLI shell with `SRMS_ADMIN_USERNAME` and a unique `SRMS_ADMIN_PASSWORD` (minimum 12 characters) environment variable, then run `php scripts/create-admin.php`. Clear the password environment variable immediately afterwards. The provisioner refuses to overwrite an existing username. Existing installations are not altered; change any known/default password through the authenticated password-change screen. The legacy MD5 compatibility path upgrades a hash only after a successful login, so the live database must be checked before that compatibility path is retired.
