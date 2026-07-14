-- =============================================================================
-- Module 9 - Payroll Management: Production Database Migration Script
-- =============================================================================
-- Covers every schema change introduced by the Payroll Management FSD work
-- (FSD 13.1-13.7):
--   1. `payroll_records.status` ENUM widened to add 'calculated', 'confirmed',
--      'closed' — FSD 13.6's Draft -> Calculated -> Confirmed -> Closed
--      lifecycle. The existing values ('draft','processed','paid','hold') are
--      kept so any pre-existing production row keeps rendering identically;
--      new rows are created with status='calculated' going forward.
--   2. `payroll_records` gains the who/when columns for that lifecycle
--      (confirmed_by/confirmed_at, closed_by/closed_at, reopened_by/
--      reopened_at/reopen_reason), plus `employer_cost` (FSD 13.4 "Total
--      Employer Cost") and `pro_rated_days` (FSD 13.6 "paid according to
--      eligible days" for a mid-month joiner/leaver).
--   3. `settings` seed rows for the four new payroll configuration flags
--      (block_negative_net_salary, show_employer_contribution_on_payslip,
--      payslip_requires_confirmation, payslip_email_enabled).
--
-- Safe to run on a live database and safe to re-run (idempotent):
--   - ADD COLUMN / ADD FOREIGN KEY on existing tables goes through helper
--     procedures that check INFORMATION_SCHEMA first.
--   - ENUM widening is guarded (only runs if the new values aren't already
--     present) and only ever adds values — never removes one.
--   - Settings seed uses INSERT IGNORE against the existing (group,key)
--     unique index — never overwrites a value an operator has already set.
--   - No existing column is altered/dropped and no existing row's data is
--     changed; no UPDATE/backfill statements in this script.
--   - Self-contained — defines and drops its own helper procedures.
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

DROP PROCEDURE IF EXISTS sp_add_fk_if_not_exists$$
CREATE PROCEDURE sp_add_fk_if_not_exists(
    IN p_table VARCHAR(64), IN p_fk_name VARCHAR(64), IN p_fkdef TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table
          AND CONSTRAINT_NAME = p_fk_name AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD ', p_fkdef);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;


-- =============================================================================
-- MODULE 9 - PAYROLL MANAGEMENT
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. `payroll_records.status` ENUM widening (FSD 13.6 status lifecycle)
--    Guarded so re-running is a no-op; only ever adds values.
-- -----------------------------------------------------------------------------
SET @status_type = (SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payroll_records' AND COLUMN_NAME = 'status');
SET @needs_status_widen = (@status_type IS NULL OR @status_type NOT LIKE '%closed%');
SET @ddl = IF(@needs_status_widen,
    "ALTER TABLE `payroll_records` MODIFY `status` ENUM('draft','processed','paid','hold','calculated','confirmed','closed') NOT NULL DEFAULT 'draft'",
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 2. Confirm / Close / Reopen lifecycle columns + Employer Cost + Pro-Rated
--    Days (FSD 13.4/13.6)
-- -----------------------------------------------------------------------------
CALL sp_add_column_if_not_exists('payroll_records', 'employer_cost', '`employer_cost` DECIMAL(10,2) NULL AFTER `esi_employer`');
CALL sp_add_column_if_not_exists('payroll_records', 'pro_rated_days', '`pro_rated_days` DECIMAL(5,2) NULL AFTER `working_days`');

CALL sp_add_column_if_not_exists('payroll_records', 'confirmed_by', '`confirmed_by` INT UNSIGNED NULL AFTER `status`');
CALL sp_add_column_if_not_exists('payroll_records', 'confirmed_at', '`confirmed_at` TIMESTAMP NULL DEFAULT NULL AFTER `confirmed_by`');
CALL sp_add_column_if_not_exists('payroll_records', 'closed_by', '`closed_by` INT UNSIGNED NULL AFTER `confirmed_at`');
CALL sp_add_column_if_not_exists('payroll_records', 'closed_at', '`closed_at` TIMESTAMP NULL DEFAULT NULL AFTER `closed_by`');
CALL sp_add_column_if_not_exists('payroll_records', 'reopened_by', '`reopened_by` INT UNSIGNED NULL AFTER `closed_at`');
CALL sp_add_column_if_not_exists('payroll_records', 'reopened_at', '`reopened_at` TIMESTAMP NULL DEFAULT NULL AFTER `reopened_by`');
CALL sp_add_column_if_not_exists('payroll_records', 'reopen_reason', '`reopen_reason` TEXT NULL AFTER `reopened_at`');

CALL sp_add_fk_if_not_exists('payroll_records', 'payroll_records_confirmed_by_foreign', 'CONSTRAINT `payroll_records_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL');
CALL sp_add_fk_if_not_exists('payroll_records', 'payroll_records_closed_by_foreign', 'CONSTRAINT `payroll_records_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL');
CALL sp_add_fk_if_not_exists('payroll_records', 'payroll_records_reopened_by_foreign', 'CONSTRAINT `payroll_records_reopened_by_foreign` FOREIGN KEY (`reopened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL');

-- -----------------------------------------------------------------------------
-- 3. Payroll module configuration flags (FSD 13.6/13.7) — INSERT IGNORE so an
--    operator's already-set value is never overwritten by re-running this.
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `settings` (`group`, `key`, `value`, `type`, `description`, `is_public`, `created_at`, `updated_at`)
VALUES
    ('payroll', 'block_negative_net_salary', '1', 'boolean', 'FSD 13.6: block payroll generation for an employee whose calculated net salary is negative (if false, the record is saved and visually flagged instead).', 0, NOW(), NOW()),
    ('payroll', 'show_employer_contribution_on_payslip', '1', 'boolean', 'FSD 13.7: show the employer PF/ESI contribution section on the payslip.', 0, NOW(), NOW()),
    ('payroll', 'payslip_requires_confirmation', '1', 'boolean', 'FSD 13.7: payslip (view/PDF/bulk/email) is only available once payroll is Confirmed, Closed, or Paid for that period.', 0, NOW(), NOW()),
    ('payroll', 'payslip_email_enabled', '0', 'boolean', 'FSD 13.7: allow emailing the payslip PDF to the employee.', 0, NOW(), NOW());

-- -----------------------------------------------------------------------------
-- Cleanup: drop the temporary helper procedures
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_column_if_not_exists;
DROP PROCEDURE IF EXISTS sp_add_fk_if_not_exists;

SET SQL_MODE = @OLD_SQL_MODE;


-- =============================================================================
-- VERIFICATION QUERIES (read-only — run after the script to confirm success)
-- =============================================================================

-- Expect the widened enum to include calculated/confirmed/closed:
-- SHOW COLUMNS FROM payroll_records LIKE 'status';

-- Expect 9 new payroll_records columns:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payroll_records'
--   AND COLUMN_NAME IN (
--     'employer_cost','pro_rated_days','confirmed_by','confirmed_at',
--     'closed_by','closed_at','reopened_by','reopened_at','reopen_reason'
--   );

-- Expect 4 new payroll settings rows:
-- SELECT `group`, `key`, `value` FROM settings WHERE `group` = 'payroll';
