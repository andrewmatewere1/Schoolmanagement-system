# SRMS Phase 1 Upgrade

This upgrade adds:
- Academic Year + Term + Examination period management
- Active academic period selection
- Period locking/unlocking (locked periods are read-only)
- Result version/history snapshots before edits
- Stronger protection against editing published results
- Improved result review links
- Professional printable report card

## Install into the existing `srms` database

1. Back up the `srms` database first.
2. Open `http://localhost:8080/phpmyadmin/`.
3. Select the existing `srms` database.
4. Import `phase1-migration.sql`.
5. Do **not** drop the existing database.
6. Copy the upgraded `srms` folder over your existing project, or replace the project folder after making a backup.

## New admin pages

- `http://localhost:8080/srms/academic-periods.php`
- `http://localhost:8080/srms/result-history.php`
- `http://localhost:8080/srms/result-approvals.php`

## Report card

Public report cards are available from a published result and are also directly accessible with:
`report-card.php?rollid=...&class=...&academic_year=...&term=...&exam_type=...`

Students still have **no login/dashboard**. Public result lookup remains available.

## Recommended workflow

1. Create the academic period.
2. Activate the period.
3. Teachers enter marks and submit them.
4. Admin reviews/approves submissions.
5. Admin publishes approved results.
6. When the period is complete, use **Lock Results**.
7. Locked results can only be changed after an admin unlocks the period.
8. Review previous marks from **Result History**.


## Phase 2
After installing Phase 1, import `phase2-migration.sql` into the existing `srms` database. Phase 2 adds teacher gender and student next-of-kin fields plus indexes. Do not drop the database.

New admin pages:
- `manage-teachers.php` — search by name, filter by assigned class/gender, edit/update, CSV/PDF export.
- `manage-students.php` — search by name, filter by class/gender, edit/update, CSV/PDF export.

Student registration now requires Next of Kin Name and Next of Kin Phone.
