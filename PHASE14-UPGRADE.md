# Phase 14 — Leave Management, Granular Permissions, Unified Login and Responsive UI

## Included
- Teacher self-service leave requests with type, inclusive date range, reason/remark, cancellation while pending, status and decision remark.
- Annual entitlement, approved leave taken and remaining balance by leave type.
- Admin review queue, approve/reject with mandatory remark, individual teacher history and status filtering.
- CSV export, Excel-compatible tab-separated `.xls` export, and browser print / Save as PDF.
- Admin-maintained leave types and annual allowances shared across teachers; changes and decisions are recorded in the existing admin activity log.
- Super Admin per-administrator view/manage permissions for Dashboard, Classes, Students, Subjects, Teachers, Results, Timetable & Exams, Student Lifecycle, Finance & Accounting, Notices and Management. Manage implies view. Access is checked server-side and unauthorized navigation links are hidden.
- Super Admin account governance: create administrator accounts, activate/suspend or delete administrator accounts, and suspend/reactivate teachers and students. "Delete access" removes login credentials while preserving academic records.
- System school name continues from central settings and is displayed in the public landing page, sign-in page, admin topbar, teacher portal and student portal.
- Unified `login.php` authenticates the username and routes to the administrator, teacher or student portal based on the account table/role. Old admin/teacher login URLs redirect to unified login.
- New students receive a globally checked username and a generated initial password shown once on the registration success message. Existing students receive a username during migration; Super Admin can set their password from the account management panel.
- Teacher, student and administrator profile-photo display; administrator self-service photo upload.
- Responsive stylesheet added to pages that have a rendered HTML head, plus mobile table/form/navigation safeguards.

## Installation
1. Make a full database backup.
2. Extract this project over a copy of the existing SRMS codebase.
3. Import `phase14-leave-permissions-login.sql` **once**, after Phase 12 and Phase 13 migrations have been applied.
4. Sign in through `login.php` with an existing Super Admin account.
5. Open **Super Admin Control Centre** and assign module permissions to each regular administrator. Existing regular admins retain Dashboard access but need explicit permissions for other modules after this migration.
6. Test teacher leave submission and admin approval with a test teacher account. Verify the new student username and initial password when registering a test student.
7. Confirm the web server supports `.htaccess` restrictions on the profile-photo storage directory, use HTTPS in production, and restrict access to the Super Admin account.

## Notes
- Leave day counting currently uses inclusive calendar days, not school-day/holiday calendars. If your policy excludes weekends/public holidays, integrate the school's calendar before using balances for payroll.
- CSV/Excel exports include leave remarks and should be handled as confidential staff records.
- The `.xls` export is tab-separated for compatibility with desktop spreadsheet software. PDF export uses the browser's print dialog; choose “Save as PDF”.
- Student login fields are added by this migration. Existing student usernames are backfilled, but their password is intentionally not generated or exposed; set a password through Super Admin before enabling their portal access.
- Apply migration only once: the ALTER TABLE statements are not repeatable.
