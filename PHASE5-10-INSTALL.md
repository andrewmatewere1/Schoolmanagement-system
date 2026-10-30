# SRMS Phase 5, 6, 7, 9 and 10 installation

This package is based on the uploaded SRMS project. It preserves the existing admin/teacher workflows and public published-result lookup; it does not add student login.

## Install
1. Preserve the existing database and project using your organization-approved infrastructure process before making changes. This application no longer provides backup or restore screens.
2. Extract the ZIP. Copy the contents of the `srms` folder over `C:\xampp\htdocs\srms\`, replacing existing files.
3. In phpMyAdmin, select the existing `srms` database and import `phase5-10-migration.sql` once. Do not drop/recreate the database and do not re-import older phase migrations.
4. Import `phase11-fee-archive-migration.sql` after the prior migrations. Open `http://localhost:8080/srms/` and test the admin/teacher/result workflows.
5. Configure fees in Management → Fee Configurations; use Student Payment Workspace for payments and Financial Reports for filtered reporting. Academic Archive provides historical score search.
6. For real `.xlsx` output, install PhpSpreadsheet with Composer (`composer require phpoffice/phpspreadsheet`). Without it, the export endpoint returns a clear setup message; CSV and print-to-PDF are available.
7. Run automated checks from the project folder: `php tests/run-tests.php`.

## Fees Management
- Open Management → Fees Management.
- Set a fee obligation for each student, academic year and term.
- Record each payment separately with date, method and optional receipt number.
- Filter balances by class, year, term and status (Full, Partial, Unpaid).
- Amounts are stored as decimal values. This release does not assume a currency symbol; label/configure the currency used by your school before rollout.

## Guardian email and WhatsApp notifications
- Add the parent/guardian email in Add Student or Edit Student. The next-of-kin phone field is used for WhatsApp and should be stored in international format, for example `+265...`.
- Only approved results can be published. When an admin publishes a student's result set, the system queues notification messages for available valid guardian contacts.
- Configure `includes/config.local.php` from `deploy/config.local.php.example`. Keep real secrets out of public repositories and never share access tokens.
- Email uses PHP `mail()` as a minimal transport. Configure an authenticated SMTP transport in production; `mail()` acceptance is not proof of inbox delivery.
- WhatsApp requires a Meta WhatsApp Business Cloud API account, approved phone number, token, recipient opt-in and compliance with Meta policies. The provided text-message sender can be restricted by WhatsApp's customer-service window; outside that window, use an approved template and adapt the worker payload accordingly.
- Schedule `php scripts/send-notifications.php 25` via Windows Task Scheduler or cron. It is CLI-only and should not be exposed as a web endpoint.
- Review Management → Guardian Notifications for queued/sent/failed attempts and retry failed entries after fixing configuration.

## Phase 6 grading and report cards
- Management → Grading System lets an admin edit grade ranges and remarks used by report cards.
- Report cards include total, percentage, overall grade/remark and position among students with published results for the same class and exam period.
- Ensure grading bands cover 0–100 without gaps or overlaps. Verify your school's grading policy before using official report cards.

## Phase 9 performance
- Phase 5–10 migration adds reporting indexes. Monitor query performance with the real production dataset.
- The in-application backup and restore feature has been removed as requested.

## Phase 10 deployment
- Use `deploy/README.md` and `deploy/config.local.php.example`.
- Use HTTPS, least-privilege MySQL credentials, strong secret values and a private backup directory outside the public document root.
- Run `php tests/run-tests.php` after every deployment. These checks validate syntax and key integration markers; they are not a substitute for end-to-end tests against a configured test database.
