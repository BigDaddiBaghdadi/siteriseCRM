# CRM Deployment

Production CRM URL:

```text
https://crm.ux-s.com
```

## Current Server Shape

- Host: `88.99.189.7`
- Repo: `/var/www/website-audit-crm/current`
- Laravel app: `/var/www/website-audit-crm/current/crm`
- Public URL: `https://crm.ux-s.com/admin`
- Database: MariaDB, `website_audit_crm`
- Front web server: OpenLiteSpeed, `lshttpd.service`
- Laravel process: `siterise-crm.service`
- Laravel bind: `127.0.0.1:8010`

OpenLiteSpeed owns ports `80` and `443`, maps `crm.ux-s.com` to the `WebsiteAuditCRM` virtual host, redirects HTTP to HTTPS, and proxies requests to the Laravel service on `127.0.0.1:8010`.

Apache is installed but is not the active web server for this CRM.

## Deploy From Windows

From the repo root:

```powershell
.\scripts\deploy-crm.ps1
```

Commit and push local changes to `origin/main` before deploying. The server
deploy pulls from GitHub rather than copying the local working tree.

The deploy script:

1. Pulls `origin/main` on the server with `git pull --ff-only`.
2. Installs production Composer dependencies.
3. Runs `npm ci` and `npm run build`.
4. Runs migrations.
5. Refreshes Laravel caches.
6. Fixes ownership for writable/generated directories.
7. Restarts `siterise-crm.service`.
8. Reloads OpenLiteSpeed.
9. Checks `https://crm.ux-s.com/admin`.

Useful options:

```powershell
.\scripts\deploy-crm.ps1 -SkipPull
.\scripts\deploy-crm.ps1 -SkipFrontend
.\scripts\deploy-crm.ps1 -SkipRestart
```

## Production Health Check

```powershell
.\scripts\check-crm-prod.ps1
```

This verifies DNS, server services/listeners, and the public HTTPS endpoint.

Expected public response for `/admin` is `302 Found` to `/login` when not authenticated.

## Server Commands

SSH:

```powershell
ssh -i "$env:USERPROFILE\.ssh\id_ed25519_siterise_hetzner" root@88.99.189.7
```

Inspect services:

```bash
systemctl status lshttpd.service --no-pager
systemctl status siterise-crm.service --no-pager
```

Inspect application logs:

```bash
cd /var/www/website-audit-crm/current/crm
tail -f storage/logs/laravel.log
```

Run migrations manually:

```bash
cd /var/www/website-audit-crm/current/crm
php artisan migrate --force
```

Create a worker token:

```bash
cd /var/www/website-audit-crm/current/crm
php artisan worker:token local-worker-1
```
