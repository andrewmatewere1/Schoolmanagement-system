# SRMS V6 Phase 3 — Management

Builds on **SRMS V5 Phase 2**. Existing Phase 1 and Phase 2 features remain intact.

## Features

### 11. Class Performance Analytics
- Admin-only analytics page.
- Uses **approved + published** results only.
- Filter by academic year, term, examination and class.
- Class average, pass rate, highest/lowest percentage, student count and mark entries.
- Subject-level performance summary.

### 12. Teacher Workload Dashboard
- Admin-only dashboard.
- Search/filter teachers by name/username and active/disabled status.
- Shows assigned classes, assigned students, pending/rework/approved submissions and total submissions.
- Shows class assignment workload.

### 13. Notifications
- In-app notifications for admins and teachers.
- Unread count in the admin top bar and teacher dashboard.
- Teacher receives notifications when results are approved, rejected or returned for rework.
- Admins are notified when a teacher submits results for review.
- Mark individual/all notifications as read.

### 14. Audit Logs
- Admin-only audit log viewer.
- Records user/action/details/IP/time.
- Search by user, details or IP.
- Filter by action and date range.
- Existing Phase 1/2 workflow actions continue to use the audit log helper.

### 15. Backup / Restore
- Admin-only complete SQL backup download.
- Backup contains table definitions, indexes and data.
- Admin-only SQL restore upload.
- Restore accepts `.sql` files up to 50 MB.
- A confirmation warning is shown before restore.

## Install on an existing V5 Phase 2 installation

1. Back up the `srms` database in phpMyAdmin first.
2. Replace the project files with the contents of `SRMS-V6-Phase3-Upgraded.zip`.
3. Open `http://localhost:8080/phpmyadmin/`.
4. Select the existing `srms` database.
5. Import `phase3-migration.sql`.
6. Do **not** drop/recreate the database.
7. Log in as admin and test the new **Management** menu.

## New admin pages
- `class-performance.php`
- `teacher-workload.php`
- `notifications.php`
- `audit-logs.php`
- (backup and restore feature removed)

## Teacher page
- `teacher-notifications.php`

## Important
- `phase3-migration.sql` is intended to be run once. If phpMyAdmin reports duplicate-column/index errors after a successful import, the corresponding objects already exist.
- Always create a fresh backup before using Restore.
- Test restores on a copy/test database before relying on them for disaster recovery.

## If Phase 1/2 schema errors appear
If the existing database has V4 installed but reports missing `academic_periods`, `result_history`, `teachers.gender`, or `tblstudents.NextOfKinName/NextOfKinPhone`, import `phase1-phase2-repair.sql` once. Do not re-import V4 or Phase 3.
