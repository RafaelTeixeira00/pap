USE powerlift_gym;

SET @has_is_admin = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'client_accounts'
      AND COLUMN_NAME = 'is_admin'
);
SET @upgrade_sql = IF(
    @has_is_admin = 0,
    'ALTER TABLE client_accounts ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active',
    'SELECT 1'
);
PREPARE upgrade_statement FROM @upgrade_sql;
EXECUTE upgrade_statement;
DEALLOCATE PREPARE upgrade_statement;

CREATE TABLE IF NOT EXISTS bookings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_account_id BIGINT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    plan_key VARCHAR(32) NOT NULL,
    class_key VARCHAR(32) NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    privacy_consent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bookings_date_status (booking_date, status),
    KEY idx_bookings_client_date (client_account_id, booking_date),
    CONSTRAINT fk_bookings_client
        FOREIGN KEY (client_account_id) REFERENCES client_accounts (id)
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

SET @has_class_key = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bookings'
      AND COLUMN_NAME = 'class_key'
);
SET @upgrade_sql = IF(
    @has_class_key = 0,
    'ALTER TABLE bookings ADD COLUMN class_key VARCHAR(32) NULL AFTER plan_key',
    'SELECT 1'
);
PREPARE upgrade_statement FROM @upgrade_sql;
EXECUTE upgrade_statement;
DEALLOCATE PREPARE upgrade_statement;

CREATE TABLE IF NOT EXISTS gym_settings (
    setting_key VARCHAR(64) NOT NULL,
    setting_value VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO gym_settings (setting_key, setting_value) VALUES
    ('gym_name', 'PowerLift Gym'),
    ('contact_email', 'PowerLiftGym0@gmail.com'),
    ('contact_phone', ''),
    ('contact_address', ''),
    ('price_base', ''),
    ('price_power', ''),
    ('price_personal', '');
