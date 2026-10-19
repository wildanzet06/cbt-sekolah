# Security hardening deployment notes

Before deployment:

1. Treat the previously committed CodeIgniter encryption key as compromised. Rotate it at the hosting provider and configure it as `CBT_ENCRYPTION_KEY`. Do not put its value in Git or in a pull request. Coordinate the change because encrypted sessions or application data may become unreadable. Removing the key from the latest source does not erase it from Git history.
2. Use HTTPS in production. The application now defaults to `production` when `CI_ENV` is absent; set `CI_ENV=development` explicitly only for local work. Production cookies use `Secure` and `HttpOnly`.
3. `uploads/.htaccess` applies only when Apache honors per-directory rules. For Nginx or another server, configure equivalent no-listing and no-script-execution rules there.
4. Test sign-in, material upload/delete, student photo upload/delete, question media upload/delete, payment evidence upload, and Midtrans callbacks in staging.

## Scope and remaining verification

The source changes address the identified hardcoded key, cookie flags, CSRF exclusions, unsafe upload deletion paths, client-controlled or absent upload-size caps, payment evidence directory permissions, and upload-directory execution rules. They do not rewrite Git history, set hosting environment variables, rotate production credentials, or change deployment settings. Full XSS review, comprehensive ownership checks, callback transition/idempotency validation, runtime tests, and dependency scanning remain unverified and should be tracked separately.
