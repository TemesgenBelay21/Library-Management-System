SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'member') NOT NULL DEFAULT 'member',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email),
    KEY users_role_status_index (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS books (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(220) NOT NULL,
    author VARCHAR(180) NOT NULL,
    isbn VARCHAR(20) NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NULL,
    cover_image VARCHAR(255) NULL,
    total_copies INT UNSIGNED NOT NULL DEFAULT 1,
    available_copies INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('available', 'checked_out') NOT NULL DEFAULT 'available',
    shelf_location VARCHAR(80) NULL,
    published_year SMALLINT UNSIGNED NULL,
    deleted_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY books_isbn_unique (isbn),
    KEY books_category_status_index (category, status, deleted_at),
    KEY books_title_index (title),
    KEY books_author_index (author)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    book_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    issued_by BIGINT UNSIGNED NOT NULL,
    issued_at DATETIME NOT NULL,
    due_at DATETIME NOT NULL,
    returned_at DATETIME NULL,
    status ENUM('issued', 'returned', 'overdue') NOT NULL DEFAULT 'issued',
    fine_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    active_loan_key VARCHAR(32) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY transactions_active_loan_unique (active_loan_key),
    KEY transactions_user_status_index (user_id, status),
    KEY transactions_book_status_index (book_id, status),
    KEY transactions_due_status_index (due_at, status),
    CONSTRAINT transactions_book_foreign
        FOREIGN KEY (book_id) REFERENCES books (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT transactions_user_foreign
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT transactions_issuer_foreign
        FOREIGN KEY (issued_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration for databases created before the active-loan guard was added.
-- MySQL permits many NULL values in a UNIQUE index, so returned loans never
-- collide; only open loans carry a key and are therefore de-duplicated.
-- ALTER TABLE `transactions`
--     ADD COLUMN `active_loan_key` VARCHAR(32) NULL AFTER `fine_amount`,
--     ADD UNIQUE KEY `transactions_active_loan_unique` (`active_loan_key`);

-- Backfill the guard for loans that were already open before the migration.
-- UPDATE `transactions`
-- SET `active_loan_key` = CONCAT(`book_id`, ':', `user_id`)
-- WHERE `status` IN ('issued', 'overdue') AND `active_loan_key` IS NULL;

-- Demo accounts for local development only.
--   admin@auralib.local  / Admin@12345
--   member@auralib.local / Member@12345
-- Remove or rehash these rows before deploying to a live environment.
INSERT IGNORE INTO users (name, email, password, role, status) VALUES
    ('Aura Administrator', 'admin@auralib.local', '$2y$10$Tv/vB7wYcz8gEewtx2KNSuvF6911.tE6STiJYc9.Zw3dx7OJ/6TQi', 'admin', 'active'),
    ('Aura Member', 'member@auralib.local', '$2y$10$YdxeWXgIvkH9aOizAK8FdOlItA6uuU.3/qkHevf7CgUymuQTLaFpS', 'member', 'active');
