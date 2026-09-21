# SMTP Email

## Setup

1. Run `composer install --no-dev --prefer-dist` in the project root.
2. Back up the database and uploads, then run `php database/migrate-mail.php`.
3. Sign in as an administrator and open **Settings > Email / SMTP**.
4. Enter the provider's SMTP host, port, encryption, credentials, sender, and the publicly accessible application URL. STARTTLS commonly uses 587; implicit SSL/TLS commonly uses 465. Use the settings supplied by your provider. The current transport supports SMTP username/password authentication, including provider app passwords; OAuth-only tenants need an OAuth transport before use.
5. Save and use **Send Test to My Email**. This sends only to the signed-in administrator's account email. A successful response means the SMTP server accepted it, not guaranteed inbox delivery.
6. Enable the required notification types and schedule the worker below. Disabled SMTP does not queue new events or backfill historical notifications. Existing pending emails pause until SMTP is enabled again.

## Automatic Delivery

Run once per minute with the hosting cron scheduler or Windows Task Scheduler:

```text
php /absolute/path/to/isu-proj-hub/database/process-mail-queue.php --limit=25
```

Windows / EnvKit executable: `C:\EnvKit\services\php\8.5.7\php.exe`.
Arguments: `"C:\Users\micha\OneDrive\Documents\Sites\isu-proj-hub\database\process-mail-queue.php" --limit=25`.
Use the PHP executable installed on the deployment host. Run tasks without opening an interactive window. The worker does not require a browser session. SMTP must be enabled first.

Settings shows the latest worker run, recent delivery outcomes, and pending/failed counts. **Send Next 5** manually processes a small batch. Failures retry after 5 and 10 minutes, then stop after three attempts. **Retry Failed** schedules another attempt. A database lock prevents concurrent workers from processing the same batch. Interrupted sends recover after ten minutes; an SMTP acceptance followed by a process crash can still result in duplicate delivery on retry.

## Connected Workflows

| Event | Recipient |
| --- | --- |
| Program/project proposal submitted or resubmitted | Active administrators already receiving the in-app notification |
| Proposal approved, returned, or rejected | Proposal submitter |
| Program created after proposal approval | Proposer |
| Accomplishment report submitted or resubmitted | Active administrators already receiving the in-app notification |
| Report approved or returned | Report submitter |
| Program/project/component/activity assignment added or removed | Assigned faculty member |

The existing `createNotification()` function inserts the in-app notification and queues its email counterpart. Each notification can have only one queue entry. SMTP failure cannot stop the originating request. Deleted/inactive accounts and invalid email addresses are skipped. New events only; there is no historical email blast. Messages contain notification text and a login-required application link, without file attachments or passwords. Email copies cannot be revoked after sending.

## Credentials and Deployment

SMTP passwords are encrypted in `settings` using AES-256-GCM and the local `config/mail.key`. The key is generated when the first password is saved, ignored by Git, and blocked from HTTP by the app's Apache rules and development router. Protect database backups and this key. Back up the key separately and restore it alongside the database, or clear and re-enter the SMTP password after moving hosts. On Nginx or other servers, apply equivalent protection to `config`, `database`, `vendor`, and other private directories.

The UI never returns the saved password, even to administrators. Blank password input preserves it; removal is explicit. SMTP changes and test delivery require an active admin session and CSRF token. Provider error responses and credentials are not exposed in delivery logs. TLS certificate verification stays enabled. Credentials cannot be used with unencrypted SMTP.

## Candidate Follow-ups

- MOA expiration reminders: agree on lead times and recipients, then add a daily due-date scan with duplicate prevention.
- Quarterly report deadlines and overdue reminders: first define deadlines and responsible faculty.
- Account invitations, email verification, and password resets: add expiring single-use tokens and account-specific endpoints; never email passwords.
- Activity schedule reminders: agree on reminder timing and recipient rules.
- Certificate availability: link to an authorized certificate after issuance; avoid mass attachments.
- Drive upload alerts: opt-in notifications or a digest to avoid one email per file in bulk uploads.

These candidate workflows are not enabled by the SMTP integration.

## Verification

`php database/test-mail.php` uses a disposable database and a loopback-only SMTP capture server. It checks opt-in delivery, notification integration, transaction rollback, deduplication, password encryption/redaction, validation, disabled users/categories, worker locking, retries, and PHPMailer's actual SMTP exchange. It never sends external email.
