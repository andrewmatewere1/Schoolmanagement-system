# SRMS V3 Upgrade Notes

This version upgrades the supplied procedural PHP/MySQL SRMS without changing its overall beginner-friendly architecture.

## 1. Existing project/local setup
- Centralized database configuration.
- PHP 8.x compatible syntax checked with `php -l` for every PHP file.
- README now documents XAMPP, Apache port 8080 and phpMyAdmin.

## 2. Database
- Added academic year, term, examination and published state to results.
- Added grading system and academic year tables.
- Added audit log table.
- Added indexes, unique constraints and foreign keys.
- Removed known duplicate class/subject and result records before uniqueness rules.

## 3. Authentication/security
- Replaced MD5 as the normal password algorithm with `password_hash()` and `password_verify()`.
- Existing MD5 admin passwords are migrated automatically after a successful login.
- Added session regeneration on login.
- Added HttpOnly/SameSite session cookies.
- Added CSRF protection to authenticated POST operations.
- Converted destructive GET actions to POST forms.

## 4. Result management
- Added academic year, term and examination fields.
- Added 0-100 server-side mark validation.
- Existing result for the same student/class/subject/period is updated rather than duplicated.
- Added publish/unpublish workflow.
- Added total, percentage, grade and remark calculation on public result display.

## 5. Student management
- Fixed the existing edit-student bug where ClassId was selected but not written back to the database.
- Added stronger required-field and email validation for new students.
- Added unique Roll ID per class at database level.

## 6. Public portal
- Result search now requires academic year and examination.
- Only published results are shown.
- Public notices are restricted to published notices.
- Output is HTML-escaped.
- Print-friendly result page added.

## 7. Dashboard
- Retained the original counters.
- Added recent results and recent administrator activity panels.

## 8. Administration
- Added audit logging for login/logout, password changes, result changes, publishing and deletes.
- Added secure password-change page.

## 9. Maintainability
- Added central helper functions for escaping, CSRF, authentication, auditing and grading.
- Added `config.local.php.example` so local credentials do not need to be edited in the main config file.

## 10. Testing
- Every PHP file passes PHP syntax linting in the delivered build.
- A manual end-to-end checklist is included in `TESTING-CHECKLIST.md`.
- Database execution must still be tested against the user's local MariaDB/MySQL instance because the build environment does not include a MySQL client/server.

## V4 Teacher Workflow
- Added teacher accounts, secure teacher authentication and logout.
- Added teacher-to-class and teacher-to-student assignments.
- Added teacher-only grading pages and server-side assignment checks.
- Added result submissions with pending/rejected/rework/approved states.
- Added administrator review, rejection, return-for-rework and bulk approval controls.
- Teacher submissions cannot publish results.
- Administrator publication is available only after approval.
- Public result lookup now requires approved + published results.
- Added responsive mobile/tablet styling to the existing project.
- Added `README-V4-TEACHER-WORKFLOW.md` and `TESTING-CHECKLIST-V4.md`.
- Added `v4-teacher-workflow-migration.sql` for an existing V3 database.
