# SRMS V4 — Teacher Grading & Admin Approval

This release extends the existing procedural PHP/MySQL SRMS. The existing public result lookup and administrator workflow remain in place. **There is no student login, student dashboard, or student self-service module.**

## Local installation

1. Start **Apache** and **MySQL/MariaDB** in XAMPP.
2. Put the `srms` folder inside XAMPP `htdocs`.
3. Create a database named `srms` in phpMyAdmin.
4. Import `srms.sql`.
5. For the supplied XAMPP defaults, the application URL is:
   - `http://localhost:8080/srms/`
   - phpMyAdmin: `http://localhost:8080/phpmyadmin/`
6. If your database credentials differ, copy `includes/config.local.php.example` to `includes/config.local.php` and edit the values.

## Test accounts

### Administrator
- Username: `admin`
- Use the administrator account already provisioned for your installation. No default password is provided in this documentation. Rotate any previously shared/test credentials before deployment.

### Teacher
- Username: `teacher1`
Use the teacher account provisioned by your school administrator. Do not use shared/default test credentials in production.

## Teacher workflow

1. Admin opens **Teachers → Add Teacher**.
2. Admin creates a teacher account.
3. Admin opens **Teacher Assignments**.
4. Assign the teacher to one or more classes.
5. Assign individual students from those classes to the teacher.
6. Teacher signs in through `teacher-login.php`.
7. Teacher sees only currently assigned students/classes.
8. Teacher opens **Grade Students** and enters marks.
9. Teacher saves a draft/rework version.
10. Teacher submits the result for administrator review.
11. The submission becomes **Pending** and the teacher cannot edit it.
12. Admin reviews the submission under **Result Approvals**.
13. Admin may:
    - Approve it;
    - Reject it with a reason; or
    - Return a rejected submission for teacher rework.
14. Approved results become available to the administrator's **Manage Results / Publication** screen but remain unpublished.
15. Only the administrator can publish the approved result.
16. Once published, the existing public result lookup displays it.

## Approval rules

- A teacher cannot approve a result.
- A teacher cannot publish a result.
- A teacher cannot edit a pending, rejected, or approved submission.
- A rejected result becomes editable only after an administrator explicitly returns it for rework.
- Publishing is possible only when every result row in the selected result group is approved.
- Public lookup requires both `approval_state='approved'` and `Published=1`.

## Database additions

- `teachers`
- `teacher_class_assignments`
- `teacher_student_assignments`
- `result_submissions`
- `tblresult.teacher_id`
- `tblresult.submission_id`
- `tblresult.result_status`
- `tblresult.approval_state`
- `tblresult.approved_by`
- `tblresult.approved_at`
- `tblresult.rejection_reason`
- `tblresult.published_by`
- `tblresult.published_at`

Foreign keys and indexes are included in `srms.sql`.

## Responsive design

The V4 responsive layer is in `css/srms-responsive.css` and is also appended to the existing main stylesheet. It covers login cards, navigation, dashboard cards, forms, tables, panels and smaller-screen spacing. Data-heavy tables use horizontal scrolling on phones rather than breaking the layout.

## End-to-end test

See `TESTING-CHECKLIST-V4.md` for the complete administrator → teacher → approval → publication → public lookup test sequence.
