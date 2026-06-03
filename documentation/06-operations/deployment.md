# Production Deployment Guide

This guide deploys the SNHS Library Management System as a public Laravel web
application on Railway or Render. The public URL is reachable by anyone, while
library pages and workflows remain protected by login and role checks.

For local setup details, see
[`documentation/02-setup/local-setup.md`](../02-setup/local-setup.md). For the
full environment variable reference, see
[`documentation/02-setup/env-vars.md`](../02-setup/env-vars.md).

---

## 1. Local Development Checklist

1. Install PHP 8.2+, Composer, Node.js 20+, npm, and MySQL 8+.
2. Copy `.env.example` to `.env`.
3. For local HTTP development, set:
   ```env
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://127.0.0.1:8000
   SESSION_SECURE_COOKIE=false
   ```
4. Configure local MySQL values in `.env`.
5. Run:
   ```bash
   composer install
   npm install
   php artisan key:generate
   php artisan migrate
   npm run build
   composer run dev
   ```

---

## 2. Production Build

The repository includes deployment configuration for Railway/Render:

- `Procfile` - web start command.
- `nixpacks.toml` - Railway/Nixpacks build and start phases.
- `render.yaml` - Render blueprint-style build and start commands.

Production build commands:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Deploy/start command:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan serve --host=0.0.0.0 --port=$PORT
```

If public uploaded files are used, also run once after deploy:

```bash
php artisan storage:link
```

---

## 3. Hosting Configuration

### Railway

1. Create a new Railway project from the Git repository.
2. Add a MySQL database service.
3. In the app service variables, set the production `.env` values listed below.
4. Railway should detect `nixpacks.toml` and run the configured build/start steps.
5. Generate a Railway domain from the service settings.
6. Open `/up` on the generated domain to confirm the health route responds.

### Render

1. Create a new Render web service from the Git repository, or use `render.yaml`.
2. Add a MySQL database. If MySQL is not available on the selected Render plan,
   use an external managed MySQL provider.
3. Set the build command:
   ```bash
   composer install --no-dev --optimize-autoloader && npm ci && npm run build
   ```
4. Set the start command:
   ```bash
   php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=$PORT
   ```
5. Add the production environment variables below.
6. Deploy and verify the Render service URL.

### Queue Worker

The app uses `QUEUE_CONNECTION=database` by default. If queued jobs are used in
production, add a worker service with:

```bash
php artisan queue:work --tries=3 --sleep=3 --timeout=90
```

Use Redis for higher volume deployments by setting `QUEUE_CONNECTION=redis`,
`CACHE_STORE=redis`, and the provider's Redis variables.

---

## 4. Production Environment Variables

Set these in the hosting provider's secret/environment variable manager. Do not
commit real values to git.

```env
APP_NAME="SNHS Library"
APP_ENV=production
APP_KEY=base64:generated-production-key
APP_DEBUG=false
APP_URL=https://your-public-domain.example
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=mysql
DB_HOST=provider-mysql-host
DB_PORT=3306
DB_DATABASE=provider_database
DB_USERNAME=provider_user
DB_PASSWORD=provider_password

CACHE_STORE=database
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=database

FILESYSTEM_DISK=local
LOG_CHANNEL=stack
MAIL_MAILER=log
VITE_APP_NAME="${APP_NAME}"
```

Generate `APP_KEY` locally or in a secure shell:

```bash
php artisan key:generate --show
```

Do not run production with seeded default credentials. Create the first admin
through `/create-admin` and disable or restrict that flow after setup if your
operational policy requires it.

---

## 5. Domain And SSL

### Hosting Subdomain

Railway and Render can generate a provider subdomain. After generating it:

1. Set `APP_URL` to the HTTPS provider URL.
2. Redeploy or restart the service.
3. Confirm all redirects and assets use `https://`.

### Custom Domain

1. Add the custom domain in Railway or Render service settings.
2. Create the DNS record shown by the provider:
   - Use `CNAME` for subdomains such as `library.example.com`.
   - Use the provider's apex/root-domain record instructions for `example.com`.
3. Set:
   ```env
   APP_URL=https://library.example.com
   SESSION_DOMAIN=null
   SESSION_SECURE_COOKIE=true
   ```
4. Wait for DNS propagation and provider-managed SSL certificate issuance.
5. Visit the site using `https://` and confirm there are no mixed-content errors.

HTTPS is terminated by Railway/Render. The app trusts forwarded proxy headers so
Laravel can correctly detect secure requests and issue secure session cookies.

---

## 6. Public Access And Routing

Expected unauthenticated behavior:

- `/` redirects to `/login`.
- `/login` loads publicly.
- `/create-admin` loads publicly for first admin setup.
- Protected app URLs redirect to `/login`.

Expected authenticated behavior:

- `/dashboard`, books, users, borrowing, reports, audit, staff, and utilities
  load for authorized users.
- Role-protected routes still enforce admin/staff checks.
- Direct URL access works after login.

---

## 7. Security Checklist

- Keep `.env` and `.env.*` files out of git.
- Set `APP_DEBUG=false` in production.
- Use a unique production `APP_KEY`.
- Use a dedicated MySQL user and strong password.
- Use `SESSION_ENCRYPT=true` and `SESSION_SECURE_COOKIE=true`.
- Use HTTPS only for production public URLs.
- Confirm secure headers are present:
  - `Strict-Transport-Security` on HTTPS responses.
  - `X-Content-Type-Options: nosniff`.
  - `X-Frame-Options: SAMEORIGIN`.
  - `Referrer-Policy: strict-origin-when-cross-origin`.
- Store database backup passwords as provider secrets.

---

## 8. Post-Deploy Verification

Run locally before deployment:

```bash
composer run test
npm run build
php artisan route:list
```

Verify after deployment:

1. Public URL loads over HTTPS.
2. `/up` returns a healthy response.
3. `/login` loads without authentication.
4. Protected URLs redirect to login when signed out.
5. Login works with a production admin/staff account.
6. Dashboard and major modules load after login.
7. Static assets and `public/images/snhs-logo.png` load.
8. Database reads and writes work.
9. Direct protected URLs work after login.
10. Queue worker is running if queued jobs are enabled.

Backups require `mysqldump` to be available in the hosting runtime. If the host
does not include it, use provider database backups or a managed backup service.
