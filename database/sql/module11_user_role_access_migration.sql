-- =============================================================================
-- Module 11 - User, Role and Access Management: Production Database Migration Script
-- =============================================================================
-- Covers every schema change introduced by the User/Role/Access Management
-- FSD work (FSD 15.1-15.2):
--   1. New `role_user` pivot table (FSD 15.1 "Role: Multi-select, mandatory,
--      at least one role") — additive alongside the existing singular
--      `users.role_id`, which is kept unchanged for backward compatibility.
--      Automatically backfilled with one row per existing user from their
--      current role_id, so every pre-existing user keeps exactly the one
--      role they already had.
--   2. `role_permissions` gains 8 more fine-grained action-flag columns
--      (can_confirm, can_close, can_reopen, can_recalculate,
--      can_modify_rules, can_modify_payroll, can_view_audit_log,
--      can_delete) — same pattern as the 6 that already existed
--      (can_approve, can_process, can_export_excel, can_export_pdf,
--      can_view_sensitive, can_manage_users).
--   3. `users.name` gains a unique index (FSD 15.1 "User Name: mandatory,
--      unique") — skipped automatically if duplicate names already exist in
--      your data (see the NOTE below).
--   4. `users.email` is widened to nullable (FSD 15.1 "Email Address:
--      optional") — the column was NOT NULL before; the app-level
--      validation alone would not have been enough to prevent a raw SQL
--      error when a user is created with no email.
--
-- Safe to run on a live database and safe to re-run (idempotent):
--   - CREATE TABLE IF NOT EXISTS for the new pivot table.
--   - Backfill uses INSERT IGNORE against a unique (user_id, role_id) index
--     — never creates a duplicate row, never touches an existing one.
--   - ADD COLUMN goes through a helper procedure that checks
--     INFORMATION_SCHEMA first.
--   - The unique index on users.name is only added if it doesn't already
--     exist AND no duplicate names are currently present (see NOTE).
--   - users.email is only widened if it is currently NOT NULL.
--   - No existing column is dropped and no existing row's data is changed.
--   - Self-contained — defines and drops its own helper procedure.
--
-- NOTE on users.name uniqueness: if your live data already has two or more
-- users sharing the same `name`, this script will SKIP creating that unique
-- index (it will not fail, and it will not touch your data) — the
-- application's own `unique:users,name` validation will still stop any NEW
-- duplicate from being created either way. Resolve existing duplicates
-- manually, then re-run this script to add the index.
--
-- Requires: CREATE ROUTINE / ALTER ROUTINE privilege.
-- Take a full database backup before running this against production.
-- =============================================================================

SET @OLD_SQL_MODE = @@SQL_MODE;
SET SQL_MODE = 'STRICT_ALL_TABLES';

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_add_column_if_not_exists$$
CREATE PROCEDURE sp_add_column_if_not_exists(
    IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_coldef TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_coldef);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;


-- =============================================================================
-- MODULE 11 - USER, ROLE AND ACCESS MANAGEMENT
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Multi-role pivot table (FSD 15.1) + backfill from the existing role_id
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_user` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` TINYINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `role_user_user_id_role_id_unique` (`user_id`, `role_id`),
    CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `role_user` (`user_id`, `role_id`, `created_at`, `updated_at`)
SELECT `id`, `role_id`, NOW(), NOW() FROM `users` WHERE `role_id` IS NOT NULL;

-- -----------------------------------------------------------------------------
-- 2. Eight new fine-grained action flags on role_permissions (FSD 15.2)
-- -----------------------------------------------------------------------------
CALL sp_add_column_if_not_exists('role_permissions', 'can_confirm', '`can_confirm` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_manage_users`');
CALL sp_add_column_if_not_exists('role_permissions', 'can_close', '`can_close` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_confirm`');
CALL sp_add_column_if_not_exists('role_permissions', 'can_reopen', '`can_reopen` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_close`');
CALL sp_add_column_if_not_exists('role_permissions', 'can_recalculate', '`can_recalculate` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_reopen`');
CALL sp_add_column_if_not_exists('role_permissions', 'can_modify_rules', '`can_modify_rules` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_recalculate`');
CALL sp_add_column_if_not_exists('role_permissions', 'can_modify_payroll', '`can_modify_payroll` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_modify_rules`');
CALL sp_add_column_if_not_exists('role_permissions', 'can_view_audit_log', '`can_view_audit_log` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_modify_payroll`');
CALL sp_add_column_if_not_exists('role_permissions', 'can_delete', '`can_delete` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_view_audit_log`');

-- -----------------------------------------------------------------------------
-- 3. Unique index on users.name (FSD 15.1) — skipped if duplicates exist
-- -----------------------------------------------------------------------------
SET @name_index_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'users_name_unique'
);
SET @has_duplicate_names = (
    SELECT COUNT(*) FROM (
        SELECT `name` FROM `users` WHERE `deleted_at` IS NULL GROUP BY `name` HAVING COUNT(*) > 1
    ) AS dupes
);
SET @ddl = IF(@name_index_exists = 0 AND @has_duplicate_names = 0,
    'ALTER TABLE `users` ADD UNIQUE KEY `users_name_unique` (`name`)',
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 4. users.email made nullable (FSD 15.1 "Email Address: optional")
-- -----------------------------------------------------------------------------
SET @email_is_not_null = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email' AND IS_NULLABLE = 'NO'
);
SET @ddl = IF(@email_is_not_null > 0,
    'ALTER TABLE `users` MODIFY `email` VARCHAR(150) NULL',
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- Cleanup: drop the temporary helper procedure
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_column_if_not_exists;

SET SQL_MODE = @OLD_SQL_MODE;


-- =============================================================================
-- VERIFICATION QUERIES (read-only — run after the script to confirm success)
-- =============================================================================

-- Expect the new pivot table to exist, with one row per user that has a role_id:
-- SELECT COUNT(*) FROM role_user;
-- SELECT COUNT(*) FROM users WHERE role_id IS NOT NULL;

-- Expect 8 new role_permissions columns:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'role_permissions'
--   AND COLUMN_NAME IN (
--     'can_confirm','can_close','can_reopen','can_recalculate',
--     'can_modify_rules','can_modify_payroll','can_view_audit_log','can_delete'
--   );

-- Expect a unique index on users.name (unless skipped due to duplicates):
-- SHOW INDEX FROM users WHERE Key_name = 'users_name_unique';

-- Expect users.email to now be nullable:
-- SHOW COLUMNS FROM users LIKE 'email';
