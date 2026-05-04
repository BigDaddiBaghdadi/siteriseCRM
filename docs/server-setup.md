# Server Setup

This server can host the CRM while the worker remains portable.

## CRM Database

Create a dedicated MariaDB database and user:

```sql
CREATE DATABASE IF NOT EXISTS website_audit_crm
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'website_audit_crm'@'localhost'
    IDENTIFIED BY 'replace_with_a_strong_password';

GRANT ALL PRIVILEGES ON website_audit_crm.*
    TO 'website_audit_crm'@'localhost';

FLUSH PRIVILEGES;
```

Set `crm/.env`:

```env
APP_NAME="Website Audit CRM"
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=website_audit_crm
DB_USERNAME=website_audit_crm
DB_PASSWORD=replace_with_a_strong_password
```

Then run:

```bash
cd crm
php artisan config:clear
php artisan migrate --force
```

## Worker Token

Create one token per worker machine:

```bash
php artisan worker:token local-worker-1
```

Put the returned token in the worker machine's `.env` as `WORKER_TOKEN`.

## Development Server

For local verification:

```bash
php artisan serve --host=127.0.0.1 --port=8010
```

Open:

```text
http://127.0.0.1:8010/admin
```

Production hosting should use the web server/PHP runtime rather than `artisan serve`.

## Existing-Domain Preview Path

If DNS for a new subdomain is not ready, the CRM can be previewed under an existing domain path.

Set:

```env
APP_URL=https://example.com/crm
CRM_ROUTE_PREFIX=crm
```

Then the admin dashboard is available at:

```text
https://example.com/crm/admin
```
