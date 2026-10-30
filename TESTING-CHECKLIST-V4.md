# SRMS V4 End-to-End Test Checklist

## A. Installation
- [ ] Apache is running.
- [ ] MySQL/MariaDB is running.
- [ ] `srms` database exists.
- [ ] `srms.sql` imports without errors.
- [ ] `http://localhost:8080/srms/` opens.
- [ ] `http://localhost:8080/srms/admin-login.php` opens.
- [ ] `http://localhost:8080/srms/teacher-login.php` opens.

## B. Administrator
- [ ] Log in using the unique administrator credentials provisioned for this test environment.
- [ ] Dashboard opens.
- [ ] Teachers menu is visible.
- [ ] Create a teacher.
- [ ] Edit the teacher.
- [ ] Disable and re-enable the teacher.
- [ ] Reset the teacher password.
- [ ] Log out.

## C. Teacher/class assignments
- [ ] Assign the teacher to a class.
- [ ] Assign at least two students from that class to the teacher.
- [ ] Confirm the assignment list shows the teacher, class and students.
- [ ] Remove a student assignment and confirm it disappears from the teacher portal.
- [ ] Remove a class assignment and confirm the teacher can no longer grade students in that class.

## D. Teacher authentication
- [ ] Log in through `teacher-login.php`.
- [ ] Confirm teacher dashboard opens.
- [ ] Confirm no admin navigation is available.
- [ ] Try opening `dashboard.php` directly; it must redirect to admin login.
- [ ] Try opening `manage-teachers.php` directly; it must redirect to admin login.
- [ ] Log out and confirm the teacher session is destroyed.
- [ ] Disable the teacher in admin and confirm login is rejected.

## E. Teacher grading
- [ ] Open Grade Students.
- [ ] Confirm only assigned students appear.
- [ ] Open an assigned student.
- [ ] Enter marks from 0 to 100.
- [ ] Try `-1`; it must be rejected.
- [ ] Try `101`; it must be rejected.
- [ ] Save the marks.
- [ ] Submit the result for admin review.
- [ ] Confirm the submission becomes Pending.
- [ ] Confirm the teacher cannot edit a Pending result.
- [ ] Confirm a teacher cannot publish a result.

## F. Administrator approval
- [ ] Log in as admin.
- [ ] Open Result Approvals.
- [ ] Confirm the teacher submission is Pending.
- [ ] Reject it with a reason.
- [ ] Confirm the teacher sees the rejection reason.
- [ ] Confirm the teacher cannot edit it while it is Rejected.
- [ ] Return it for Rework.
- [ ] Confirm the teacher can edit it now.
- [ ] Resubmit it.
- [ ] Approve it as admin.
- [ ] Confirm it becomes Approved but remains unpublished.

## G. Publication
- [ ] Open Manage Results.
- [ ] Confirm an unapproved result cannot be published.
- [ ] Confirm an approved result has a Publish control.
- [ ] Publish the approved result.
- [ ] Confirm `Published` is shown.
- [ ] Unpublish it and confirm it disappears from the public result page.
- [ ] Publish it again.

## H. Public result lookup
- [ ] Open Find Result.
- [ ] Search using the correct academic year, examination, class and roll ID.
- [ ] Confirm the published result appears.
- [ ] Confirm an unpublished result does not appear.
- [ ] Confirm a pending/rejected/rework result does not appear.
- [ ] Confirm print view works.

## I. Mobile/tablet
- [ ] Test admin login at phone width.
- [ ] Test admin dashboard at phone width.
- [ ] Test teacher login at phone width.
- [ ] Test teacher dashboard at phone width.
- [ ] Test grading form at phone width.
- [ ] Test approval table at phone width.
- [ ] Test public result page at phone width.
- [ ] Confirm tables scroll horizontally where required.
- [ ] Confirm buttons remain usable without overlapping.

## J. Security
- [ ] Submit a form without CSRF token; it must fail.
- [ ] Try to access a teacher's student by changing the URL `student_id`; it must return 403.
- [ ] Try to publish a pending result directly by modifying the POST request; it must not publish.
- [ ] Try to access teacher pages without a teacher session; redirect to teacher login.
- [ ] Try to access admin pages with only a teacher session; redirect to admin login.
- [ ] Confirm passwords are stored as password hashes, not MD5.
- [ ] Confirm SQL input is handled with prepared statements.
