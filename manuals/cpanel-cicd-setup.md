# cPanel CI/CD Setup

The repository now includes a cPanel deployment manifest at `.cpanel.yml` and a GitHub Actions workflow at `.github/workflows/deploy-cpanel.yml`.
The cPanel **Update from Remote** and **Deploy HEAD Commit** buttons can now pull the latest `main` branch and deploy it to the document root. Every push to `main` can also be deployed through the GitHub Actions workflow, if the SSH secrets are configured.
The workflow can also be started manually from GitHub Actions.

## Required cPanel setup

1. Make sure the project is already cloned on cPanel and that its remote is:

   `https://github.com/mx-dln/proj-management-hub.git`

2. Enable SSH access in cPanel.
3. Add the GitHub Actions public SSH key to the cPanel account's `~/.ssh/authorized_keys`.
4. Confirm that the cPanel project folder has no uncommitted tracked changes. The workflow uses `git pull --ff-only` and stops safely if local changes would be overwritten.
5. Confirm Composer is available on cPanel and that `composer install` works from the project folder.
6. If the domain does not use `/home/<account>/public_html/`, edit the `DEPLOYPATH` line in `.cpanel.yml` before deploying.

## GitHub repository secrets

Add these under **GitHub → Settings → Secrets and variables → Actions**:

| Secret | Value |
|---|---|
| `CPANEL_SSH_HOST` | cPanel server hostname, such as `server.example.com` |
| `CPANEL_SSH_PORT` | SSH port, usually `22` |
| `CPANEL_SSH_USER` | cPanel account username |
| `CPANEL_SSH_KEY` | The complete private key used by GitHub Actions |
| `CPANEL_APP_PATH` | Absolute project path, such as `/home/account/public_html/isu-proj-hub` |

Never commit an SSH private key, SMTP password, database password, `config/config.local.php`, or `config/mail.key`.

## What is preserved on cPanel

The cPanel deployment manifest only copies tracked application files. It does not delete or replace:

- `config/config.local.php`
- `config/mail.key`
- uploaded documents and images
- logs
- database data

## Database migrations

Database migrations are intentionally not run automatically. Apply a migration in cPanel phpMyAdmin first, verify it, then deploy the code. This prevents an application deployment from accidentally changing or breaking the production database.

## First test

1. Push a small approved code change to `main`.
2. Open **GitHub → Actions → Deploy to cPanel**.
3. Confirm the job succeeds.
4. Check the cPanel project folder and open the live site.

If the job fails, check the SSH host, port, username, private key, project path, GitHub remote access from cPanel, and Composer availability.
