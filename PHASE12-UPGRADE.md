# SRMS Phase 12 — Timetables, Student Lifecycle, Finance, PWA and Profile Images

This upgrade extends the existing PHP/MySQL SRMS. It does not drop existing tables. Test on a staging copy before using it with live school records.

## Before installation

1. Keep a copy of the current project files and use your hosting provider/database administrator's approved database backup process. The application backup/restore feature remains removed.
2. Confirm Phases 1–11 have been applied and that the existing `tblclasses`, `tblsubjects`, `tblstudents`, `teachers`, and `tblresult` tables are present.
3. Extract the package over the existing project, preserving `includes/config.local.php` and other local secrets.
4. In phpMyAdmin, select the existing SRMS database and import `phase12-timetable-lifecycle-finance-pwa.sql` once. It uses `CREATE TABLE IF NOT EXISTS`; do not repeatedly edit/re-run individual statements after partial failures without checking which tables were created.
5. Ensure the PHP process can write to `uploads/profiles/`. The parent `uploads/.htaccess` intentionally blocks direct web access; photos are served through `profile-photo.php` after authentication.

## Features added

### Timetable and examinations
- Open **Timetable & Exams** from the administrator sidebar.
- Weekly lessons include class, subject, teacher, room, day, start/end time, academic year, and term.
- Teacher, classroom, and class overlapping times are rejected.
- Examinations are saved as drafts, check teacher/room/class clashes, and can be published by an administrator.
- Use **Export / Print PDF** and choose “Save as PDF” in the browser print dialog. This is print-to-PDF, not a server-generated PDF file.

### Student lifecycle and registration numbers
- **Student Lifecycle** records promotion, class transfer, withdrawal, graduation, and reactivation with a dated history.
- Promotion/transfer changes the student's class. Withdrawal/graduation mark the existing student inactive while retaining historical academic and fee records.
- Student registration number is generated when the registration number field is left blank. The format is `STU-YYYY-XXXXXXXX`.

### Finance and MWK
- **Finance & Accounting** records income and expenditure in Malawi Kwacha (MWK), filters entries by date, displays net movement, and stores category budgets.
- This is a basic cashbook/register, not a full double-entry general ledger. Continue using the fee subsystem as the source of student fee receipts; do not duplicate those receipts as general income entries unless your accounting policy explicitly calls for a separate summarized journal.
- Use the print dialog to save the filtered register as PDF.

### Guardian portal
- Guardians sign in at `guardian-login.php` and see only students explicitly linked by an administrator, recent attendance, published results, and upcoming published examinations.
- This is a first release of the portal; email-based password recovery, MFA, guardian self-service linking, and messaging still need to be configured before broad public rollout.

### Teacher/student profile images
- Registration forms accept optional JPG, PNG, or WebP images up to 3 MB.
- Images are stored under `uploads/profiles/` with random filenames and served through an authenticated PHP endpoint. The server validates the image content rather than trusting the uploaded extension.

## Verification

Run:

```sh
php tests/run-tests.php
find . -name '*.php' -print0 | xargs -0 -n1 php -l
node --check service-worker.js
```

Then test in staging:
- Create a lesson, verify overlap rejection for the same teacher, room, and class, and print a PDF.
- Create an exam draft, publish it, and verify it appears on the guardian portal for a linked student.
- Promote a test student and verify lifecycle history; withdraw/reactivate only test data.
- Enter an income and expense, filter dates, and verify the net movement calculation.
- Register a student and teacher with and without photos; confirm invalid/oversized files are rejected and uploaded photos require authentication.
- Create a guardian account and verify it cannot view an unlinked student.

## Limitations / production notes

- The migration has not been applied to your live database by this package build; apply it in staging first.
- The application has not been tested against your live database or hosting environment. Automated static tests and syntax checks do not replace integration testing.
- Examination PDF export currently uses browser print-to-PDF.
- Finance entries are not yet a double-entry accounting system and are not integrated with a bank/payment gateway.
- Offline attendance data is device-local until synchronized. Secure the device and browser profile.
- Photo storage is protected by the existing `uploads/.htaccess` denial rule; Apache configuration should be reviewed if the project is deployed under Nginx, where `.htaccess` is not enforced. Keep `uploads/profiles` outside direct public serving or configure equivalent Nginx denial.
