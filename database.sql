-- ============================================================
-- WAGTW Database Migration - Full Schema
-- Coding by: cs.baguosps@gmail.com
-- Copyright (c) 2024-2026. All rights reserved.
--
-- Cara pakai: phpMyAdmin → Database wagtw → tab SQL / Import → GO
-- Atau via terminal: mysql -u root wagtw < migration.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ──────────────────────────────────────────────────────────────
-- 1. TABEL USERS
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`            VARCHAR(150)     NOT NULL,
    `username`        VARCHAR(50)      DEFAULT NULL,
    `email`           VARCHAR(150)     NOT NULL,
    `password`        VARCHAR(255)     NOT NULL,
    `role`            ENUM('super_admin','admin','user') NOT NULL DEFAULT 'user',
    `parent_id`       INT(11) UNSIGNED DEFAULT NULL,
    `profile_picture` VARCHAR(255)     DEFAULT NULL,
    `device_limit`    INT(11)          NOT NULL DEFAULT 1,
    `lang`            VARCHAR(10)      NOT NULL DEFAULT 'id',
    `status`          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`      DATETIME         DEFAULT NULL,
    `updated_at`      DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email`    (`email`),
    UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 2. TABEL SETTINGS (global/per-user)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
    `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11) UNSIGNED DEFAULT NULL COMMENT 'null = global setting',
    `key`        VARCHAR(100)     NOT NULL,
    `value`      TEXT             DEFAULT NULL,
    `created_at` DATETIME         DEFAULT NULL,
    `updated_at` DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 3. TABEL DEVICES
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `devices` (
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         INT(11) UNSIGNED NOT NULL,
    `name`            VARCHAR(150)     NOT NULL,
    `phone`           VARCHAR(30)      DEFAULT NULL,
    `session_id`      VARCHAR(100)     NOT NULL,
    `token`           VARCHAR(64)      DEFAULT NULL,
    `status`          VARCHAR(50)      NOT NULL DEFAULT 'pending',
    `webhook_url`     TEXT             DEFAULT NULL,
    `reject_call`     TINYINT(1)       NOT NULL DEFAULT 0,
    `auto_typing`     TINYINT(1)       NOT NULL DEFAULT 0,
    `online_status`   TINYINT(1)       NOT NULL DEFAULT 1,
    `last_seen`       DATETIME         DEFAULT NULL,
    `created_at`      DATETIME         DEFAULT NULL,
    `updated_at`      DATETIME         DEFAULT NULL,
    `welcome_enabled` TINYINT(1)       DEFAULT 0,
    `welcome_message` TEXT             DEFAULT NULL,
    `away_enabled`    TINYINT(1)       DEFAULT 0,
    `away_message`    TEXT             DEFAULT NULL,
    `work_hours`      TEXT             DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `devices_user_id_foreign` (`user_id`),
    CONSTRAINT `devices_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 4. TABEL AUTOREPLIES
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `autoreplies` (
    `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        INT(11) UNSIGNED NOT NULL,
    `device_id`      INT(11) UNSIGNED DEFAULT NULL,
    `name`           VARCHAR(150)     NOT NULL,
    `trigger_word`   TEXT             NOT NULL,
    `match_type`     ENUM('exact','contains','startswith','regex') NOT NULL DEFAULT 'contains',
    `target_reply`   ENUM('both','private','group') NOT NULL DEFAULT 'both',
    `reply_type`     ENUM('text','image','location','sticker','vcard','button','list') NOT NULL DEFAULT 'text',
    `reply_text`     TEXT             DEFAULT NULL,
    `reply_media`    VARCHAR(255)     DEFAULT NULL,
    `reply_caption`  TEXT             DEFAULT NULL,
    `latitude`       DECIMAL(10,8)    DEFAULT NULL,
    `longitude`      DECIMAL(11,8)    DEFAULT NULL,
    `location_name`  VARCHAR(255)     DEFAULT NULL,
    `vcard_data`     TEXT             DEFAULT NULL,
    `delay`          INT(11)          NOT NULL DEFAULT 0 COMMENT 'delay in seconds',
    `set_read`       TINYINT(1)       NOT NULL DEFAULT 0,
    `set_typing`     TINYINT(1)       NOT NULL DEFAULT 0,
    `active`         TINYINT(1)       NOT NULL DEFAULT 1,
    `priority`       INT(11)          NOT NULL DEFAULT 0,
    `created_at`     DATETIME         DEFAULT NULL,
    `updated_at`     DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `autoreplies_user_id_foreign`   (`user_id`),
    KEY `autoreplies_device_id_foreign` (`device_id`),
    CONSTRAINT `autoreplies_user_id_foreign`
        FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `autoreplies_device_id_foreign`
        FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 5. TABEL MESSAGES (riwayat pesan)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `messages` (
    `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT(11) UNSIGNED NOT NULL,
    `device_id`    INT(11) UNSIGNED NOT NULL,
    `wa_msg_id`    VARCHAR(150)     DEFAULT NULL,
    `from_phone`   VARCHAR(50)      NOT NULL,
    `to_phone`     VARCHAR(50)      NOT NULL,
    `msg_type`     ENUM('text','image','audio','video','document','location','sticker','vcard','button','list','other') NOT NULL DEFAULT 'text',
    `message`      TEXT             DEFAULT NULL,
    `media_path`   VARCHAR(255)     DEFAULT NULL,
    `direction`    ENUM('in','out') NOT NULL DEFAULT 'out',
    `status`       ENUM('pending','sent','delivered','read','played','failed') NOT NULL DEFAULT 'pending',
    `is_group`     TINYINT(1)       NOT NULL DEFAULT 0,
    `is_deleted`   TINYINT(1)       NOT NULL DEFAULT 0,
    `webhook_sent` TINYINT(1)       NOT NULL DEFAULT 0,
    `created_at`   DATETIME         DEFAULT NULL,
    `updated_at`   DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_msg` (`device_id`, `wa_msg_id`),
    KEY `messages_user_id_foreign` (`user_id`),
    CONSTRAINT `messages_device_id_foreign`
        FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `messages_user_id_foreign`
        FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 6. TABEL CAMPAIGNS (blast/broadcast)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `campaigns` (
    `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT(11) UNSIGNED NOT NULL,
    `device_id`    INT(11) UNSIGNED NOT NULL,
    `name`         VARCHAR(150)     NOT NULL,
    `msg_type`     ENUM('text','image','location','vcard') NOT NULL DEFAULT 'text',
    `message`      TEXT             NOT NULL,
    `media_path`   VARCHAR(255)     DEFAULT NULL,
    `delay_min`    INT(11)          NOT NULL DEFAULT 5,
    `delay_max`    INT(11)          NOT NULL DEFAULT 15,
    `batch_send`   INT(11)          NOT NULL DEFAULT 0,
    `batch_sleep`  INT(11)          NOT NULL DEFAULT 0,
    `status`       ENUM('draft','running','paused','done','failed') NOT NULL DEFAULT 'draft',
    `scheduled_at` DATETIME         DEFAULT NULL,
    `started_at`   DATETIME         DEFAULT NULL,
    `finished_at`  DATETIME         DEFAULT NULL,
    `total`        INT(11)          NOT NULL DEFAULT 0,
    `sent`         INT(11)          NOT NULL DEFAULT 0,
    `failed`       INT(11)          NOT NULL DEFAULT 0,
    `created_at`   DATETIME         DEFAULT NULL,
    `updated_at`   DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `campaigns_user_id_foreign`   (`user_id`),
    KEY `campaigns_device_id_foreign` (`device_id`),
    CONSTRAINT `campaigns_user_id_foreign`
        FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `campaigns_device_id_foreign`
        FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 7. TABEL CAMPAIGN RECIPIENTS
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `campaign_recipients` (
    `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id` INT(11) UNSIGNED NOT NULL,
    `phone`       VARCHAR(50)      NOT NULL,
    `name`        VARCHAR(150)     DEFAULT NULL,
    `status`      ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    `sent_at`     DATETIME         DEFAULT NULL,
    `error_msg`   TEXT             DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `campaign_recipients_campaign_id_foreign` (`campaign_id`),
    CONSTRAINT `campaign_recipients_campaign_id_foreign`
        FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 8. TABEL CONTACTS
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `contacts` (
    `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11) UNSIGNED NOT NULL,
    `device_id`  INT(11) UNSIGNED DEFAULT NULL,
    `name`       VARCHAR(150)     NOT NULL,
    `phone`      VARCHAR(50)      NOT NULL,
    `email`      VARCHAR(150)     DEFAULT NULL,
    `address`    TEXT             DEFAULT NULL,
    `notes`      TEXT             DEFAULT NULL,
    `group_name` VARCHAR(100)     DEFAULT NULL,
    `created_at` DATETIME         DEFAULT NULL,
    `updated_at` DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `contacts_user_id_foreign` (`user_id`),
    CONSTRAINT `contacts_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 9. TABEL AI SERVERS (API Key Groq — dikelola Super Admin)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ai_servers` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)      NOT NULL COMMENT 'ID Super Admin pemilik key',
    `label`      VARCHAR(100) NOT NULL,
    `api_key`    TEXT         NOT NULL,
    `model`      VARCHAR(100) NOT NULL DEFAULT 'llama3-8b-8192',
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `priority`   INT(11)      NOT NULL DEFAULT 0,
    `created_at` DATETIME     DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_user_active` (`user_id`, `is_active`),
    KEY `idx_priority`    (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ──────────────────────────────────────────────────────────────
-- 10. TABEL AI MODELS (dikelola Super Admin, dipakai semua user)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ai_models` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)      NOT NULL COMMENT 'ID Super Admin',
    `label`      VARCHAR(100) NOT NULL,
    `model_name` VARCHAR(100) NOT NULL,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `priority`   INT(11)      NOT NULL DEFAULT 0,
    `created_at` DATETIME     DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ──────────────────────────────────────────────────────────────
-- 11. TABEL AI SEARCH ENGINES (dikelola Super Admin)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ai_search_engines` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `user_id`     INT(11)      NOT NULL,
    `provider`    VARCHAR(50)  NOT NULL COMMENT 'google, bing, duckduckgo',
    `label`       VARCHAR(100) NOT NULL,
    `api_key`     TEXT         DEFAULT NULL,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `priority`    INT(11)      NOT NULL DEFAULT 0,
    `created_at`  DATETIME     DEFAULT current_timestamp(),
    `pull_count`  INT(11)      NOT NULL DEFAULT 0,
    `description` TEXT         DEFAULT NULL,
    `reset_at`    DATETIME     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_provider`        (`user_id`, `provider`),
    KEY `idx_user_active_search`         (`user_id`, `is_active`),
    KEY `idx_priority_search`            (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ──────────────────────────────────────────────────────────────
-- 12. TABEL DEVICE AI SETTINGS (pengaturan AI per device)
--     UPDATE 2026-09: tambah kolom activated_by_user_id & activated_at
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `device_ai_settings` (
    `id`                   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `device_id`            INT(10) UNSIGNED NOT NULL,
    `user_id`              INT(10) UNSIGNED NOT NULL,
    `ai_enabled`           TINYINT(1)       NOT NULL DEFAULT 0,
    `activated_by_user_id` INT(10) UNSIGNED DEFAULT NULL COMMENT 'User yang terakhir mengaktifkan AI',
    `activated_at`         DATETIME         DEFAULT NULL  COMMENT 'Waktu terakhir AI diaktifkan',
    `search_enabled`       TINYINT(1)       NOT NULL DEFAULT 1,
    `target_reply`         VARCHAR(20)      NOT NULL DEFAULT 'both',
    `ai_server_id`         INT(10) UNSIGNED DEFAULT NULL,
    `model`                VARCHAR(100)     DEFAULT NULL,
    `style`                TEXT             DEFAULT NULL COMMENT 'JSON array of selected styles',
    `brand`                VARCHAR(255)     DEFAULT NULL,
    `ai_name`              VARCHAR(100)     DEFAULT NULL,
    `language`             VARCHAR(100)     DEFAULT NULL,
    `rules`                TEXT             DEFAULT NULL COMMENT 'System prompt / rules',
    `reply_mode`           VARCHAR(20)      NOT NULL DEFAULT 'manual',
    `created_at`           DATETIME         DEFAULT NULL,
    `updated_at`           DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_device_id` (`device_id`),
    KEY `idx_device_id`       (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────────────────────
-- 13. TABEL DEVICE AI DATA (knowledge base per device)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `device_ai_data` (
    `id`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `device_id`  INT(10) UNSIGNED NOT NULL,
    `user_id`    INT(10) UNSIGNED NOT NULL,
    `title`      VARCHAR(255)     NOT NULL,
    `content`    TEXT             NOT NULL,
    `created_at` DATETIME         DEFAULT NULL,
    `updated_at` DATETIME         DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_device_user` (`device_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- UPGRADE SCRIPT: Jalankan ini jika database sudah ada
-- (aman dijalankan berkali-kali — menggunakan IF NOT EXISTS)
-- ============================================================

-- Upgrade device_ai_settings: tambah kolom monitoring (2026-09)
ALTER TABLE `device_ai_settings`
    ADD COLUMN IF NOT EXISTS `activated_by_user_id` INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'User yang terakhir mengaktifkan AI' AFTER `ai_enabled`,
    ADD COLUMN IF NOT EXISTS `activated_at`          DATETIME DEFAULT NULL
        COMMENT 'Waktu terakhir AI diaktifkan'       AFTER `activated_by_user_id`;

-- Backfill data lama: isi activated_by_user_id untuk record yang sudah aktif
UPDATE `device_ai_settings`
    SET `activated_by_user_id` = `user_id`,
        `activated_at`         = `updated_at`
    WHERE `ai_enabled` = 1
      AND `activated_by_user_id` IS NULL;

-- Upgrade messages: status 'played' & panjang wa_msg_id (v7 Baileys)
ALTER TABLE `messages` 
    MODIFY COLUMN `status` ENUM('pending','sent','delivered','read','played','failed') NOT NULL DEFAULT 'pending';
ALTER TABLE `messages`
    MODIFY COLUMN `wa_msg_id` VARCHAR(150) DEFAULT NULL;

-- Upgrade: Tabel ai_muted_contacts untuk penanganan Handoff Chat Admin harian
CREATE TABLE IF NOT EXISTS `ai_muted_contacts` (
    `id`          INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `device_id`   INT(11) UNSIGNED NOT NULL,
    `phone`       VARCHAR(50)      NOT NULL,
    `muted_date`  DATE             NOT NULL,
    `muted_until` DATETIME         DEFAULT NULL COMMENT 'Batas waktu kontak di-mute',
    `reason`      VARCHAR(100)     DEFAULT 'chat_admin',
    `created_at`  DATETIME         NOT NULL,
    `updated_at`  DATETIME         NOT NULL,
    UNIQUE KEY `uk_device_phone_date` (`device_id`, `phone`, `muted_date`),
    INDEX `idx_lookup` (`device_id`, `phone`, `muted_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Upgrade device_ai_settings: tambah kolom Template AI WA & Handoff
ALTER TABLE `device_ai_settings`
    ADD COLUMN IF NOT EXISTS `ai_footer_text` TEXT DEFAULT NULL COMMENT 'Footer bantuan admin di balasan AI',
    ADD COLUMN IF NOT EXISTS `ai_footer_enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Status aktif footer bantuan admin',
    ADD COLUMN IF NOT EXISTS `ai_handoff_text` TEXT DEFAULT NULL COMMENT 'Pesan pengalihan ke admin',
    ADD COLUMN IF NOT EXISTS `ai_reactivate_duration` VARCHAR(50) NOT NULL DEFAULT 'next_day' COMMENT 'Durasi reaktivasi otomatis',
    ADD COLUMN IF NOT EXISTS `ai_reactivate_keyword` VARCHAR(255) NOT NULL DEFAULT 'akhiri percakapan' COMMENT 'Kata kunci admin selesai percakapan',
    ADD COLUMN IF NOT EXISTS `ai_reactivate_message` TEXT DEFAULT NULL COMMENT 'Pesan saat sesi admin selesai',
    ADD COLUMN IF NOT EXISTS `ai_reactivate_notify` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Kirim notifikasi saat sesi selesai';

ALTER TABLE `ai_muted_contacts`
    ADD COLUMN IF NOT EXISTS `muted_until` DATETIME DEFAULT NULL COMMENT 'Batas waktu kontak di-mute' AFTER `muted_date`;

-- ──────────────────────────────────────────────────────────────
-- DEFAULT SEED DATA
-- ──────────────────────────────────────────────────────────────
-- Akun Super Admin Bawaan
-- Username: admin | Password: password
INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `status`, `device_limit`, `created_at`) 
VALUES (1, 'Super Administrator', 'admin', 'admin@wagtw.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active', 10, NOW())
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Pengaturan Branding Awal
INSERT INTO `settings` (`key`, `value`, `created_at`) VALUES
('app_name', 'WAGTW Gateway', NOW()),
('app_logo', '', NOW())
ON DUPLICATE KEY UPDATE `key` = `key`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Selesai! Seluruh tabel & data awal WAGTW siap digunakan.
-- ============================================================

