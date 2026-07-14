-- =============================================================================
-- Module 8 - Leave and LOP Management: Production Database Migration Script
-- =============================================================================
-- Covers every schema change introduced by the Leave & LOP Management FSD
-- work (FSD 12.1, 12.3, 12.4):
--   1. `leave_balances` gains `opening_balance` and `adjusted_days` — FSD
--      12.3's "Opening Balance" and "Adjusted Leave" had no existing column
--      (Accrued/Carry Forward/Used/Lapsed already existed as
--      allocated_days/carry_forward_days/used_days/lapsed_days).
--   2. New `leave_balance_adjustments` table — append-only audit trail for
--      manual balance adjustments ("adjustment history shall be
--      maintained").
--   3. `payroll_records` gains a per-component LOP breakdown
--      (unpaid_leave_days / half_day_lop_days / late_early_lop_days), an
--      "Apply LOP" flag, and a confirmation gate (lop_confirmed_at /
--      lop_confirmed_by) — FSD 12.4's LOP Review screen fields and the
--      "confirmation before payroll processing" requirement.
--
-- Safe to run on a live database and safe to re-run (idempotent):
--   - ADD COLUMN / ADD FOREIGN KEY on existing tables goes through helper
--     procedures that check INFORMATION_SCHEMA first.
--   - CREATE TABLE IF NOT EXISTS for the new adjustments table.
--   - No existing column is altered or dropped; no existing data is
--     touched at all (no UPDATE/backfill statements in this script).
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
-- MODULE 8 - LEAVE AND LOP MANAGEMENT
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Leave Balance — Opening Balance / Adjusted Leave (FSD 12.3)
-- -----------------------------------------------------------------------------
CALL sp_add_column_if_not_exists('leave_balances', 'opening_balance', '`opening_balance` DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER `leave_type_id`');
CALL sp_add_column_if_not_exists('leave_balances', 'adjusted_days', '`adjusted_days` DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER `carry_forward_days`');

-- -----------------------------------------------------------------------------
-- 2. Leave Balance Adjustment audit trail (FSD 12.3 "adjustment history
--    shall be maintained") — net new, append-only.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS leave_balance_adjustments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    leave_balance_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    leave_type_id TINYINT UNSIGNED NOT NULL,
    adjustment_days DECIMAL(5,2) NOT NULL,
    reason TEXT NOT NULL,
    adjusted_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY leave_balance_adjustments_leave_balance_id_index (leave_balance_id),
    KEY leave_balance_adjustments_employee_id_index (employee_id),
    CONSTRAINT leave_balance_adjustments_leave_balance_id_foreign FOREIGN KEY (leave_balance_id) REFERENCES leave_balances (id) ON DELETE CASCADE,
    CONSTRAINT leave_balance_adjustments_employee_id_foreign FOREIGN KEY (employee_id) REFERENCES employees (id) ON DELETE CASCADE,
    CONSTRAINT leave_balance_adjustments_leave_type_id_foreign FOREIGN KEY (leave_type_id) REFERENCES leave_types (id),
    CONSTRAINT leave_balance_adjustments_adjusted_by_foreign FOREIGN KEY (adjusted_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Payroll LOP breakdown + Apply LOP + confirmation gate (FSD 12.4)
-- -----------------------------------------------------------------------------
CALL sp_add_column_if_not_exists('payroll_records', 'unpaid_leave_days', '`unpaid_leave_days` DECIMAL(5,2) NULL AFTER `calculated_lop_days`');
CALL sp_add_column_if_not_exists('payroll_records', 'half_day_lop_days', '`half_day_lop_days` DECIMAL(5,2) NULL AFTER `unpaid_leave_days`');
CALL sp_add_column_if_not_exists('payroll_records', 'late_early_lop_days', '`late_early_lop_days` DECIMAL(5,2) NULL AFTER `half_day_lop_days`');
CALL sp_add_column_if_not_exists('payroll_records', 'lop_applied', "`lop_applied` TINYINT(1) NOT NULL DEFAULT 1 AFTER `late_early_lop_days`");
CALL sp_add_column_if_not_exists('payroll_records', 'lop_confirmed_at', '`lop_confirmed_at` TIMESTAMP NULL DEFAULT NULL AFTER `lop_applied`');
CALL sp_add_column_if_not_exists('payroll_records', 'lop_confirmed_by', '`lop_confirmed_by` INT UNSIGNED NULL AFTER `lop_confirmed_at`');
CALL sp_add_fk_if_not_exists('payroll_records', 'payroll_records_lop_confirmed_by_foreign', 'CONSTRAINT `payroll_records_lop_confirmed_by_foreign` FOREIGN KEY (`lop_confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL');


-- -----------------------------------------------------------------------------
-- Cleanup: drop the temporary helper procedures
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_column_if_not_exists;
DROP PROCEDURE IF EXISTS sp_add_fk_if_not_exists;

SET SQL_MODE = @OLD_SQL_MODE;


-- =============================================================================
-- VERIFICATION QUERIES (read-only — run after the script to confirm success)
-- =============================================================================

-- Expect 2 new leave_balances columns:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_balances'
--   AND COLUMN_NAME IN ('opening_balance','adjusted_days');

-- Expect the new audit table to exist:
-- SHOW TABLES LIKE 'leave_balance_adjustments';

-- Expect 6 new payroll_records columns:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payroll_records'
--   AND COLUMN_NAME IN (
--     'unpaid_leave_days','half_day_lop_days','late_early_lop_days',
--     'lop_applied','lop_confirmed_at','lop_confirmed_by'
--   );
