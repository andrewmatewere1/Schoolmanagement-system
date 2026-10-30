# SRMS Automated Tests

Run from the project directory:

```bash
php tests/run-tests.php
```

The runner performs:
- PHP syntax checks for all application PHP files
- verification that Phase 4 security helpers are present
- verification of login rate limiting hooks
- verification of upload validation hooks
- verification of security headers
- verification of the Phase 4 migration and web-server deny rules

For full production testing, also manually verify HTTPS, login throttling, backup restore, mobile layouts and the browser console after deployment.
