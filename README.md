# AuraLib

A library management system built as a plain PHP MVC application with a dark, cyber-styled interface. There is no framework, no build step and no package manager: clone it, import the schema, point a virtual host at `public/` and it runs.

## Features

**Circulation**

- Issue books from a slide-over panel with live book and member lookup
- Return loans, with automatic fine calculation per day late
- One active loan per member and title, enforced by a database constraint rather than application checks alone
- Overdue review that refreshes loan statuses only when something is actually due
- Transaction log with pagination, per-page sizing, status filtering and sortable columns

**Catalog**

- Full create, read, update and delete for books with cover uploads
- Covers are validated as real images, stored outside the document root and streamed through a controller
- Filenames are regenerated as a random 32-character token, so uploads can never overwrite or execute a file
- Search and filter by title, author, category and availability

**Members**

- Member dashboard with live-updating metrics
- Browsable catalog with availability and current-loan state
- Personal loan history with fines and due dates

**Platform**

- Role-based access control enforced in the front controller
- CSRF tokens on every state-changing form
- Prepared statements everywhere, with emulation disabled
- Passwords hashed with `password_hash()`, no plaintext credentials in code
- Flash messaging, server-rendered views, no client-side framework

## Requirements

| Component | Version |
| --------- | ------- |
| PHP       | 7.4 or newer, with `pdo_mysql`, `mbstring`, `fileinfo` and `json` |
| MySQL     | 5.7 or newer, or MariaDB 10.3 or newer |
| Web server | Apache, Nginx or `php -S` |

## Installation

1. Clone the repository and place it wherever your server serves from.

2. Create an empty database:

   ```sql
   CREATE DATABASE auralib CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Import the schema, which also seeds the two demo accounts:

   ```bash
   mysql -u root -p auralib < public/database.sql
   ```

4. Create your local configuration:

   ```bash
   cp .env.example .env
   ```

5. Set `APP_URL`, `DB_NAME`, `DB_USERNAME` and `DB_PASSWORD` in `.env`. `APP_URL` must match how the app is actually served, with no trailing slash:

   ```dotenv
   # Inside XAMPP htdocs, with a subdirectory:
   APP_URL=http://localhost/Library-Management-System/public

   # With a document root pointed straight at public/:
   APP_URL=http://auralib.test
   ```

6. Make `storage/` writable by the web server user so cover uploads succeed.

7. Verify the installation:

   ```bash
   php tools/verify.php
   ```

   This checks the PHP version, required extensions, `.env` keys, syntax of every PHP file, storage permissions, database connectivity and the presence of the required tables. It exits non-zero on failure, so it works as a deployment gate.

### Demo accounts

| Role   | Email                  | Password       |
| ------ | ---------------------- | -------------- |
| Admin  | `admin@auralib.local`  | `Admin@12345`  |
| Member | `member@auralib.local` | `Member@12345` |

Delete or rehash these rows before deploying anywhere public.

## Upgrading an existing installation

The active-loan guard was added after the initial schema. If you imported the database before that change, run the commented migration block at the bottom of `public/database.sql`.

Add the column and the unique index first:

```sql
ALTER TABLE `transactions`
    ADD COLUMN `active_loan_key` VARCHAR(32) NULL AFTER `fine_amount`,
    ADD UNIQUE KEY `transactions_active_loan_unique` (`active_loan_key`);
```

MySQL allows unlimited `NULL` values in a unique index, so returned loans never collide. Only open loans carry a key.

Before the backfill, resolve any duplicate open loans, because the update will fail otherwise:

```sql
SELECT `book_id`, `user_id`, COUNT(*)
FROM `transactions`
WHERE `status` IN ('issued', 'overdue')
GROUP BY `book_id`, `user_id`
HAVING COUNT(*) > 1;
```

Keep the earliest loan in each group and return the rest, then backfill:

```sql
UPDATE `transactions`
SET `active_loan_key` = CONCAT(`book_id`, ':', `user_id`)
WHERE `status` IN ('issued', 'overdue') AND `active_loan_key` IS NULL;
```

`php tools/verify.php` confirms the column and index are in place.

## Configuration

| Variable       | Required | Purpose                                              |
| -------------- | -------- | ---------------------------------------------------- |
| `APP_ENV`      | yes      | `local` or `production`                              |
| `APP_DEBUG`    | yes      | Show detailed errors; must be `false` in production   |
| `APP_URL`      | yes      | Base URL, used for every generated link and redirect  |
| `DB_HOST`      | yes      | Database host                                        |
| `DB_PORT`      | no       | Database port, defaults to `3306`                    |
| `DB_NAME`      | yes      | Database name                                        |
| `DB_USERNAME`  | yes      | Database user                                        |
| `DB_PASSWORD`  | no       | Database password, defaults to empty                 |

`.env` is ignored by git. Never commit it.

## Project structure

```
app/
  controllers/     Request handling, validation and authorisation
  models/          Database access, one class per table
  services/        Book cover storage and image validation
  views/           Server-rendered templates
    admin/         Admin dashboard, books, transactions
    member/        Member dashboard, catalog, history
    layouts/       Shared page shell
    partials/      Navigation and shared fragments
config/            Constants, environment loader, database, role map
public/            Document root: front controller, assets
  assets/css/      Single stylesheet
  assets/js/       Progressive enhancement, no framework
storage/           Writable runtime files, including book covers
tools/verify.php   Installation verification
```

`storage/` and `public/assets/` are the only directories a web request can write to or read from directly. Book covers are stored under `storage/book-covers` and served through `GET catalog/{id}/cover`, which validates the stored filename before reading it.

## Security notes

- All database access uses prepared statements, with `PDO::ATTR_EMULATE_PREPARES` disabled so queries are never interpolated.
- Every `POST` route is rejected without a matching CSRF token.
- Role checks live in the front controller, so an unprivileged request cannot reach an admin action even with a crafted URL.
- Uploads are rejected unless `getimagesize()` confirms a real JPEG, PNG or WebP image within the size limit, and the stored name is a freshly generated random token with a server-chosen extension.
- The cover filename is re-validated against `^[a-f0-9]{32}\.(jpg|png|webp)$` on read, so a crafted filename cannot traverse out of the storage directory.
