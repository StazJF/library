**Environment Variables Reference**

This document lists all environment variables used by the SNHS Library Management System. Values are sourced from `.env.example` and configuration files.

---

## 🔑 Application Configuration

### Basic Settings

```env
APP_NAME=SNHS
```
- **Description:** Application display name
- **Default:** `SNHS`
- **Usage:** Displayed in browser title, emails, and system branding

```env
APP_ENV=local
```
- **Description:** Application environment mode
- **Options:**
  - `local` - Development mode with debug enabled
  - `production` - Production mode with error hiding
- **Default:** `local`
- **Impact:** Controls error display and caching behavior

```env
APP_DEBUG=true
```
- **Description:** Enable/disable debug mode
- **Options:**
  - `true` - Show detailed error pages and query logs
  - `false` - Hide technical details (use in production)
- **Default:** `true` for local, `false` for production
- **⚠️ WARNING:** Never set to `true` in production!

```env
APP_KEY=base64:...
```
- **Description:** Encryption key for sensitive data
- **⚠️ IMPORTANT:** Generate via `php artisan key:generate`
- **Auto-generated:** Yes (during setup)
- **Length:** Base64 encoded 32-byte key
- **Used for:** Session encryption, cookies, API tokens

```env
APP_URL=http://localhost:8000
```
- **Description:** Public URL of your application
- **Examples:**
  - Local: `http://localhost:8000`
  - Production: `https://library.snhs.edu.ph`
- **Impact:** Used for URL generation in emails, redirects
- **Default:** `http://localhost:8000`

```env
APP_LOCALE=en
```
- **Description:** Default application language
- **Options:** `en`, `fil`, `es`, etc.
- **Default:** `en` (English)

```env
APP_FALLBACK_LOCALE=en
```
- **Description:** Fallback language if translation missing
- **Default:** `en`

```env
APP_TIMEZONE=UTC
```
- **Description:** Server timezone
- **Examples:** `UTC`, `Asia/Manila`, `America/New_York`
- **Default:** `UTC`
- **Recommendation:** Set to `Asia/Manila` for Philippines

```env
APP_FAKER_LOCALE=en_US
```
- **Description:** Faker library locale for seed data
- **Used during:** Database seeding for sample data
- **Default:** `en_US`

```env
APP_MAINTENANCE_DRIVER=file
```
- **Description:** Driver for maintenance mode
- **Options:** `file` or `cache`
- **Default:** `file`

---

## 🗄️ Database Configuration

### Primary Database Connection

```env
DB_CONNECTION=mysql
```
- **Description:** Active database driver
- **Options:** `mysql`, `pgsql`, `sqlite`, `sqlsrv`
- **Default:** `mysql`
- **Source:** `config/database.php`

```env
DB_HOST=127.0.0.1
```
- **Description:** Database server hostname or IP
- **Examples:**
  - Local: `127.0.0.1` or `localhost`
  - Remote: `db.example.com` or IP address
- **Default:** `127.0.0.1`
- **XAMPP:** `127.0.0.1`

```env
DB_PORT=3306
```
- **Description:** Database server port
- **Default:** `3306` (MySQL standard)
- **Range:** 1-65535

```env
DB_DATABASE=library_mgmt
```
- **Description:** Database name to use
- **Setup:** Must be created before migrations
- **Command:** `CREATE DATABASE library_mgmt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
- **Default:** `library_mgmt`

```env
DB_USERNAME=root
```
- **Description:** Database user for connections
- **XAMPP Default:** `root` (no password)
- **Production:** Use dedicated limited-privilege user
- **Example:** `library_user`

```env
DB_PASSWORD=
```
- **Description:** Database user password
- **XAMPP Local:** Empty (no password)
- **Production:** Strong password required
- **Example:** `SecureP@ssw0rd123!`

### Cache Driver (Optional)

```env
DB_CACHE_CONNECTION=cache
```
- **Description:** Connection name for cache driver if using database cache
- **Options:** Connection defined in `config/database.php`
- **Optional:** Only if using database for caching
- **Source:** `config/cache.php`

### Queue Driver (Optional)

```env
DB_QUEUE_CONNECTION=queue
```
- **Description:** Connection name for queue jobs if using database queue
- **Options:** Connection defined in `config/database.php`
- **Optional:** Only if using database for queues
- **Source:** `config/queue.php`

---

## 💾 Cache Configuration

```env
CACHE_STORE=file
```
- **Description:** Default cache driver
- **Options:**
  - `file` - File-based caching (dev)
  - `array` - In-memory (tests only)
  - `redis` - Redis server (recommended for production)
  - `memcached` - Memcached server
  - `database` - Database-backed cache
- **Default:** `file`
- **Source:** `config/cache.php`

```env
CACHE_PREFIX=laravel_cache
```
- **Description:** Prefix for all cache keys
- **Purpose:** Avoid key collisions if multiple apps use same cache server
- **Default:** `laravel_cache`
- **Example:** `library_cache_`

### Redis Cache (Optional)

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```
- **Description:** Redis server connection details
- **Only needed:** If `CACHE_STORE=redis`
- **Source:** `config/database.php`, `config/cache.php`

### Memcached (Optional)

```env
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
```
- **Description:** Memcached server connection
- **Only needed:** If using Memcached for caching
- **Source:** `config/cache.php`

---

## 📋 Session Configuration

```env
SESSION_DRIVER=database
```
- **Description:** Where session data is stored
- **Options:**
  - `file` - File storage (development)
  - `database` - Database storage (production)
  - `cookie` - Cookie storage
  - `redis` - Redis storage
- **Default:** `database`
- **Source:** `config/session.php`
- **Requirement:** Session table must exist (created by migration)

```env
SESSION_LIFETIME=120
```
- **Description:** Session timeout in minutes
- **Default:** `120` (2 hours)
- **Examples:**
  - `30` - 30 minutes
  - `1440` - 24 hours
  - `43200` - 30 days

```env
SESSION_ENCRYPT=false
```
- **Description:** Whether to encrypt session data
- **Options:** `true` or `false`
- **Default:** `false`
- **Recommendation:** `true` for production

```env
SESSION_PATH=/
```
- **Description:** Path where session cookie is valid
- **Default:** `/` (whole domain)
- **Advanced:** Usually not changed

```env
SESSION_DOMAIN=null
```
- **Description:** Domain for session cookie
- **Default:** `null` (current domain)
- **Advanced:** For subdomain session sharing

---

## 🔄 Queue Configuration

```env
QUEUE_CONNECTION=database
```
- **Description:** Job queue driver
- **Options:**
  - `sync` - Synchronous (execute immediately)
  - `database` - Database-backed queue
  - `redis` - Redis queue
  - `beanstalkd` - Beanstalkd queue
- **Default:** `database`
- **Source:** `config/queue.php`
- **Note:** Used for backup tasks, async operations

```env
QUEUE_FAILED_DRIVER=database
```
- **Description:** Where failed jobs are logged
- **Default:** `database`
- **Source:** `config/queue.php`

---

## 📁 Filesystem Configuration

```env
FILESYSTEM_DISK=local
```
- **Description:** Default filesystem disk for storage
- **Options:** `local`, `s3`, `gcs`, etc.
- **Default:** `local`
- **Storage Location:** `storage/app/` (local disk)
- **Usage:** Backups, file uploads
- **Source:** `config/filesystems.php`

### AWS S3 (Optional)

```env
AWS_ACCESS_KEY_ID=your_key_id
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_USE_PATH_STYLE_ENDPOINT=false
```
- **Description:** AWS S3 configuration for file storage
- **Only needed:** If `FILESYSTEM_DISK=s3`
- **Get credentials:** AWS Management Console
- **Source:** `config/filesystems.php`

---

## 📧 Mail Configuration

```env
MAIL_MAILER=log
```
- **Description:** Email service driver
- **Options:**
  - `log` - Write to logs (development)
  - `smtp` - SMTP server
  - `mailgun` - Mailgun API
  - `postmark` - Postmark service
  - `sendgrid` - SendGrid service
  - `ses` - AWS SES
- **Default:** `log` (development)
- **Source:** `config/mail.php`

```env
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```
- **Description:** SMTP configuration
- **Only needed:** If `MAIL_MAILER=smtp`
- **TLS Port:** 587, SSL Port: 465
- **Recommendation:** Use MailTrap.io for testing

```env
MAIL_FROM_ADDRESS=noreply@snhs.edu.ph
MAIL_FROM_NAME="SNHS Library System"
```
- **Description:** Sender email address and name
- **Usage:** For transactional emails
- **Examples:**
  - From: `noreply@snhs.edu.ph`
  - Display Name: `SNHS Library System`

### Mailgun (Optional)

```env
MAILGUN_SECRET=your_mailgun_key
MAILGUN_DOMAIN=mail.snhs.edu.ph
```
- **Only needed:** If `MAIL_MAILER=mailgun`
- **Get from:** Mailgun dashboard

### AWS SES (Optional)

```env
AWS_SES_REGION=us-east-1
```
- **Only needed:** If `MAIL_MAILER=ses`

---

## 🔌 Third-Party Services (Optional)

### Rollbar Error Tracking

```env
ROLLBAR_LEVEL=critical
```
- **Optional:** For production error tracking
- **Source:** `config/services.php`

### Stripe Payments (If Implemented)

```env
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
```
- **Optional:** For payment processing
- **Get from:** Stripe Dashboard

---

## 🎯 Complete Example .env File

### Development Setup (XAMPP)

```env
# App
APP_NAME=SNHS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_LOCALE=en
APP_TIMEZONE=Asia/Manila

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=library_mgmt
DB_USERNAME=root
DB_PASSWORD=

# Cache
CACHE_STORE=file

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=database

# Mail
MAIL_MAILER=log

# Filesystem
FILESYSTEM_DISK=local
```

### Production Setup (Remote Database)

```env
# App
APP_NAME=SNHS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://library.snhs.edu.ph
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_TIMEZONE=Asia/Manila

# Database
DB_CONNECTION=mysql
DB_HOST=db.hosting-provider.com
DB_PORT=3306
DB_DATABASE=snhs_library_prod
DB_USERNAME=library_admin
DB_PASSWORD=SecureP@ssw0rd123!

# Cache
CACHE_STORE=redis
REDIS_HOST=redis.hosting-provider.com
REDIS_PASSWORD=redis_password

# Session
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_LIFETIME=1440

# Queue
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=library@snhs.edu.ph
MAIL_PASSWORD=gmail_app_password
MAIL_FROM_ADDRESS=library@snhs.edu.ph
MAIL_FROM_NAME="SNHS Library System"

# Filesystem
FILESYSTEM_DISK=local
```

---

## ⚠️ Security Best Practices

### For Development

```env
APP_DEBUG=true           # OK for local development only
APP_ENV=local
```

### For Production

```env
APP_DEBUG=false          # Never true in production!
APP_ENV=production
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

### General Rules

1. **APP_KEY:** Generate and keep secret
   ```bash
   php artisan key:generate
   ```

2. **DB_PASSWORD:** Use strong password
   - Minimum 12 characters
   - Mix uppercase, lowercase, numbers, symbols

3. **Never commit .env to Git:**
   - `.env` is in `.gitignore`
   - `.env.*` files are ignored except `.env.example`
   - Share `.env.example` instead with sample values

4. **Database User:** Create limited-privilege user in production
   ```sql
   CREATE USER 'library_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT ALL PRIVILEGES ON library_mgmt.* TO 'library_user'@'localhost';
   ```

5. **Backup Configuration:** Store `.env` backups securely

---

## 🔍 Verification Checklist

After setting up `.env`, verify:

```bash
# 1. Check config loads correctly
php artisan config:show | grep APP_

# 2. Check database connection
php artisan db:show

# 3. Check cache
php artisan cache:clear

# 4. Check session
php artisan session:clean
```

---

## Sources

- `.env.example` - Template file with all available variables
- `config/app.php` - Application configuration
- `config/database.php` - Database configuration
- `config/cache.php` - Cache configuration
- `config/session.php` - Session configuration
- `config/queue.php` - Queue configuration
- `config/filesystems.php` - Filesystem configuration
- `config/mail.php` - Mail configuration
- `config/services.php` - Third-party services
