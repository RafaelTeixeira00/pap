CREATE DATABASE IF NOT EXISTS powerlift_gym
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE powerlift_gym;

CREATE TABLE IF NOT EXISTS client_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_client_accounts_email (email)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_account_id BIGINT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    plan_key VARCHAR(32) NOT NULL,
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
