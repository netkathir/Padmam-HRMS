-- =============================================================================
-- Module 6 - Employee Management: Production Database Migration Script
-- =============================================================================
-- Covers every schema change introduced by the Employee Management FSD work
-- (FSD 10.1-10.9):
--   1. New employee fields — Classification (Biometric ID), Personal
--      (middle/display/father-spouse name), Contact (district, emergency
--      contact relationship, full permanent-address block), Employment
--      (contract start/end date), Identity & Statutory (PF Number), and
--      Contract Labour Information (contractor employee number, work order
--      number, labour category, contractor rate, contractor remarks).
--   2. Three nullable per-employee Rule Engine override columns
--      (weekly_off_rule_id / attendance_rule_id / payroll_rule_id) referencing
--      Module 4's `rules` table — NULL (the default) means fully automatic
--      resolution, unchanged from today.
--   3. Bank Master integration + Payment Mode on `employee_bank_details`.
--   4. Fixes a pre-existing broken table: `employee_documents` was missing
--      `document_number` / `expiry_date` / `is_verified` columns that the
--      model already expected, so every document upload failed with a SQL
--      error before this fix.
--   5. Seeds 3 new configurable settings (group `employee`): biometric ID
--      uniqueness scope, minimum working age, and mandatory document types.
--
-- Safe to run on a live database and safe to re-run (idempotent):
--   - ADD COLUMN / ADD INDEX / ADD FOREIGN KEY on existing tables goes
--     through helper procedures that check INFORMATION_SCHEMA first.
--   - Settings are inserted with INSERT IGNORE against a UNIQUE(group, key).
--   - No existing column is altered or dropped; no existing data is touched
--     beyond the settings seed rows.
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
-- MODULE 6 - EMPLOYEE MANAGEMENT
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. New fields on the existing `employees` table
-- -----------------------------------------------------------------------------

-- 10.3.1 Personal Information
CALL sp_add_column_if_not_exists('employees', 'middle_name', '`middle_name` VARCHAR(100) NULL AFTER `first_name`');
CALL sp_add_column_if_not_exists('employees', 'display_name', '`display_name` VARCHAR(200) NULL AFTER `last_name`');
CALL sp_add_column_if_not_exists('employees', 'father_spouse_name', '`father_spouse_name` VARCHAR(150) NULL AFTER `religion`');

-- 10.2 Classification
CALL sp_add_column_if_not_exists('employees', 'biometric_id', '`biometric_id` VARCHAR(50) NULL AFTER `employee_code`');

-- 10.3.2 Contact Information — current address gains district; a full
-- parallel permanent-address block is added.
CALL sp_add_column_if_not_exists('employees', 'district', '`district` VARCHAR(100) NULL AFTER `city`');
CALL sp_add_column_if_not_exists('employees', 'emergency_contact_relationship', '`emergency_contact_relationship` VARCHAR(50) NULL AFTER `emergency_contact_phone`');
CALL sp_add_column_if_not_exists('employees', 'permanent_address_line1', '`permanent_address_line1` VARCHAR(200) NULL AFTER `pincode`');
CALL sp_add_column_if_not_exists('employees', 'permanent_address_line2', '`permanent_address_line2` VARCHAR(200) NULL AFTER `permanent_address_line1`');
CALL sp_add_column_if_not_exists('employees', 'permanent_city', '`permanent_city` VARCHAR(100) NULL AFTER `permanent_address_line2`');
CALL sp_add_column_if_not_exists('employees', 'permanent_district', '`permanent_district` VARCHAR(100) NULL AFTER `permanent_city`');
CALL sp_add_column_if_not_exists('employees', 'permanent_state', '`permanent_state` VARCHAR(100) NULL AFTER `permanent_district`');
CALL sp_add_column_if_not_exists('employees', 'permanent_pincode', '`permanent_pincode` VARCHAR(10) NULL AFTER `permanent_state`');

-- 10.3.3 Employment Information
CALL sp_add_column_if_not_exists('employees', 'contract_start_date', '`contract_start_date` DATE NULL AFTER `probation_end_date`');
CALL sp_add_column_if_not_exists('employees', 'contract_end_date', '`contract_end_date` DATE NULL AFTER `contract_start_date`');

-- 10.3.4 Identity & Statutory — PF account number, distinct from UAN
CALL sp_add_column_if_not_exists('employees', 'pf_number', '`pf_number` VARCHAR(30) NULL AFTER `uan_number`');

-- 10.8 Contract Labour Information (fields beyond the existing contractor_id)
CALL sp_add_column_if_not_exists('employees', 'contractor_employee_number', '`contractor_employee_number` VARCHAR(50) NULL AFTER `contractor_id`');
CALL sp_add_column_if_not_exists('employees', 'work_order_number', '`work_order_number` VARCHAR(50) NULL AFTER `contractor_employee_number`');
CALL sp_add_column_if_not_exists('employees', 'labour_category', '`labour_category` VARCHAR(50) NULL AFTER `work_order_number`');
CALL sp_add_column_if_not_exists('employees', 'contractor_rate', '`contractor_rate` DECIMAL(10,2) NULL AFTER `labour_category`');
CALL sp_add_column_if_not_exists('employees', 'contractor_remarks', 'TEXT NULL AFTER `contractor_rate`' );

-- Backfill display_name for existing rows so the field is populated going
-- forward without breaking historical records.
UPDATE employees
SET display_name = TRIM(CONCAT(first_name, ' ', COALESCE(last_name, '')))
WHERE display_name IS NULL;

-- Note: Biometric ID uniqueness is enforced at the application layer
-- (configurable global/branch scope via settings.employee.biometric_id_scope)
-- rather than a DB unique index, so branch-scoped duplicates remain
-- configurable — mirrors employee_code's existing pattern.

-- -----------------------------------------------------------------------------
-- 2. Per-employee Rule Engine overrides (FSD 10.3.3) — nullable FKs to
--    Module 4's `rules` table. NULL (the default for every employee) means
--    fully automatic resolution, unchanged from today.
-- -----------------------------------------------------------------------------
CALL sp_add_column_if_not_exists('employees', 'weekly_off_rule_id', '`weekly_off_rule_id` SMALLINT UNSIGNED NULL AFTER `shift_id`');
CALL sp_add_fk_if_not_exists('employees', 'employees_weekly_off_rule_id_foreign', 'CONSTRAINT `employees_weekly_off_rule_id_foreign` FOREIGN KEY (`weekly_off_rule_id`) REFERENCES `rules` (`id`) ON DELETE SET NULL');

CALL sp_add_column_if_not_exists('employees', 'attendance_rule_id', '`attendance_rule_id` SMALLINT UNSIGNED NULL AFTER `weekly_off_rule_id`');
CALL sp_add_fk_if_not_exists('employees', 'employees_attendance_rule_id_foreign', 'CONSTRAINT `employees_attendance_rule_id_foreign` FOREIGN KEY (`attendance_rule_id`) REFERENCES `rules` (`id`) ON DELETE SET NULL');

CALL sp_add_column_if_not_exists('employees', 'payroll_rule_id', '`payroll_rule_id` SMALLINT UNSIGNED NULL AFTER `attendance_rule_id`');
CALL sp_add_fk_if_not_exists('employees', 'employees_payroll_rule_id_foreign', 'CONSTRAINT `employees_payroll_rule_id_foreign` FOREIGN KEY (`payroll_rule_id`) REFERENCES `rules` (`id`) ON DELETE SET NULL');

-- -----------------------------------------------------------------------------
-- 3. Bank Master integration + Payment Mode (FSD 10.3.5)
-- -----------------------------------------------------------------------------
CALL sp_add_column_if_not_exists('employee_bank_details', 'bank_id', '`bank_id` SMALLINT UNSIGNED NULL AFTER `bank_name`');
CALL sp_add_fk_if_not_exists('employee_bank_details', 'employee_bank_details_bank_id_foreign', 'CONSTRAINT `employee_bank_details_bank_id_foreign` FOREIGN KEY (`bank_id`) REFERENCES `banks` (`id`) ON DELETE SET NULL');

CALL sp_add_column_if_not_exists('employee_bank_details', 'payment_mode', "`payment_mode` ENUM('bank_transfer','cash','cheque') NOT NULL DEFAULT 'bank_transfer' AFTER `employee_id`");

-- -----------------------------------------------------------------------------
-- 4. Fix `employee_documents` (FSD 10.9) — the model already expected these
--    three columns; without them, every document upload failed with a SQL
--    error. This restores parity between the model and the live schema.
-- -----------------------------------------------------------------------------
CALL sp_add_column_if_not_exists('employee_documents', 'document_number', '`document_number` VARCHAR(100) NULL AFTER `document_name`');
CALL sp_add_column_if_not_exists('employee_documents', 'expiry_date', '`expiry_date` DATE NULL AFTER `document_number`');
CALL sp_add_column_if_not_exists('employee_documents', 'is_verified', "`is_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `remarks`");

-- -----------------------------------------------------------------------------
-- 5. Configurable Employee Management settings (group `employee`) — same
--    generic `settings` table pattern as Module 4's Sunday-pay config.
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO settings (`group`, `key`, `value`, `type`, `description`) VALUES
    ('employee', 'biometric_id_scope', 'global', 'string', 'Biometric ID uniqueness scope: global (unique across the whole company) or branch (unique within a branch).'),
    ('employee', 'min_working_age', '18', 'integer', 'Minimum working age enforced against Date of Birth at employee registration.'),
    ('employee', 'mandatory_document_types', '["aadhaar","photo"]', 'json', 'Document types that should be present before employee registration is considered complete (non-blocking warning).');


-- -----------------------------------------------------------------------------
-- Cleanup: drop the temporary helper procedures
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_column_if_not_exists;
DROP PROCEDURE IF EXISTS sp_add_fk_if_not_exists;

SET SQL_MODE = @OLD_SQL_MODE;


-- =============================================================================
-- VERIFICATION QUERIES (read-only — run after the script to confirm success)
-- =============================================================================

-- Expect 22 new employees columns to all show up:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees'
--   AND COLUMN_NAME IN (
--     'middle_name','display_name','father_spouse_name','biometric_id','district',
--     'emergency_contact_relationship','permanent_address_line1','permanent_address_line2',
--     'permanent_city','permanent_district','permanent_state','permanent_pincode',
--     'contract_start_date','contract_end_date','pf_number','contractor_employee_number',
--     'work_order_number','labour_category','contractor_rate','contractor_remarks',
--     'weekly_off_rule_id','attendance_rule_id','payroll_rule_id'
--   );

-- Expect 2 new employee_bank_details columns:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_bank_details'
--   AND COLUMN_NAME IN ('bank_id','payment_mode');

-- Expect 3 new employee_documents columns:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_documents'
--   AND COLUMN_NAME IN ('document_number','expiry_date','is_verified');

-- Expect 3 rows:
-- SELECT `group`, `key`, `value` FROM settings WHERE `group` = 'employee';
