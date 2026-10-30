# Phase 13 — Super Admin functionality

## Features
- Role-based `super_admin` and `admin` accounts.
- Super Admin control centre with platform counts, administrator account management, enable/disable access, role assignment, password resets, recent activity and audit-log shortcut.
- System-wide school/system name, default currency and academic-year settings.
- Protection against disabling/demoting the current Super Admin and against removing the last active Super Admin.
- Admin login rejects disabled accounts and records the last successful login.
- Administrator changes are written to `admin_activity_logs`.

## Install / upgrade
1. Back up the database first.
2. Import `phase13-super-admin.sql` into the existing SRMS database using phpMyAdmin or the MySQL CLI. Apply this migration once only; the `ALTER TABLE` statements are not intended to be re-run.
3. Sign out and sign back in so the administrator role is loaded into the session.
4. Promote an existing trusted account or create a new Super Admin. For a new account, use a private CLI shell and set `SRMS_ADMIN_USERNAME`, `SRMS_ADMIN_PASSWORD` (12+ characters) and `SRMS_ADMIN_ROLE=super_admin`, then run `php scripts/create-admin.php`. Clear the password environment variable immediately afterwards. Alternatively, a currently authenticated Super Admin can create/promote accounts in **Super Admin Control Centre**.
5. Confirm the Super Admin menu appears, create a separate normal Admin account, then test both roles.

## Security notes
- Do not expose the CLI provisioner or database credentials publicly; the provisioner is CLI-only.
- Give Super Admin access only to trusted operators. Password reset is administrative and should be followed by an immediate password change by the account holder.
- Keep at least two trusted active Super Admin accounts to avoid lockout.
- The currency and academic-year values are stored centrally for configuration use; existing historical fee records are not converted when the currency setting changes.
- This module is single-school administration, not a multi-tenant SaaS school-isolation layer. It does not create database backups or run destructive restore operations from the web interface.
