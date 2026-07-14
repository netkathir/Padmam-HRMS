-- =============================================================================
-- Module 4 - Rule Engine: Production Database Migration Script
-- =============================================================================
-- Covers every schema change introduced by the uncommitted Module 4 work:
--   - Common Rule Header + 8 category detail tables + sequence counters
--   - Historical rule-application snapshot columns on attendance/payroll_records
--   - LOP calculated-vs-approved columns on payroll_records
--
-- Safe to run on a live database and safe to re-run (idempotent):
--   - New tables use CREATE TABLE IF NOT EXISTS (all 10 are brand new, no
--     existing data at risk).
--   - ADD COLUMN on the two EXISTING tables (attendance, payroll_records)
--     goes through a helper procedure that checks INFORMATION_SCHEMA first,
--     so re-running this script is a no-op wherever the column already
--     exists — no data in those tables is touched or lost.
--   - This script is self-contained (defines and drops its own helper
--     procedures) — it does not depend on module3_masters_migration.sql
--     having been run first or leaving anything behind.
--
-- Requires: CREATE ROUTINE / ALTER ROUTINE privilege (for the temporary
-- helper procedure, dropped again at the very end of this script).
--
-- Take a full database backup before running this against production.
-- =============================================================================

SET @OLD_SQL_MODE = @@SQL_MODE;
SET SQL_MODE = 'STRICT_ALL_TABLES';

-- -----------------------------------------------------------------------------
-- Helper procedure (temporary — dropped at the end of this script)
-- -----------------------------------------------------------------------------
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
-- MODULE 4 - RULE ENGINE
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Common Rule Header (FSD 8.2)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rules (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    category ENUM('employee_number','attendance','weekly_off','holiday','lop','pf','esi','tds','payroll','overtime') NOT NULL,
    branch_ids JSON NULL COMMENT 'NULL/empty = all branches',
    employee_types JSON NULL COMMENT 'subset of staff,labour',
    labour_types JSON NULL COMMENT 'subset of company_labour,contract_labour',
    contractor_ids JSON NULL,
    priority INT NOT NULL DEFAULT 100 COMMENT 'lower = applied first',
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    description TEXT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY rules_name_category_unique (name, category),
    KEY rules_category_status_index (category, status),
    KEY rules_priority_index (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Category detail tables (FSD 8.3-8.10), 1:1 with `rules`
-- -----------------------------------------------------------------------------

-- 8.3 Employee Number Rule
CREATE TABLE IF NOT EXISTS employee_number_rules (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rule_id SMALLINT UNSIGNED NOT NULL,
    employee_category ENUM('staff','company_labour','contract_labour') NOT NULL,
    branch_id SMALLINT UNSIGNED NULL,
    contractor_id SMALLINT UNSIGNED NULL,
    prefix VARCHAR(20) NULL,
    include_branch_code TINYINT(1) NOT NULL DEFAULT 0,
    include_contractor_code TINYINT(1) NOT NULL DEFAULT 0,
    `separator` VARCHAR(5) NULL DEFAULT '-',
    sequence_start INT UNSIGNED NOT NULL DEFAULT 1,
    sequence_length TINYINT UNSIGNED NOT NULL DEFAULT 4,
    include_financial_year TINYINT(1) NOT NULL DEFAULT 0,
    include_calendar_year TINYINT(1) NOT NULL DEFAULT 0,
    reset_frequency ENUM('never','yearly','financial_yearly','branch_wise') NOT NULL DEFAULT 'never',
    allow_manual_override TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY employee_number_rules_rule_id_unique (rule_id),
    KEY employee_number_rules_branch_id_foreign (branch_id),
    KEY employee_number_rules_contractor_id_foreign (contractor_id),
    CONSTRAINT employee_number_rules_rule_id_foreign FOREIGN KEY (rule_id) REFERENCES rules (id) ON DELETE CASCADE,
    CONSTRAINT employee_number_rules_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE SET NULL,
    CONSTRAINT employee_number_rules_contractor_id_foreign FOREIGN KEY (contractor_id) REFERENCES contractors (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.4 Attendance Rule
CREATE TABLE IF NOT EXISTS attendance_rules (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rule_id SMALLINT UNSIGNED NOT NULL,
    shift_ids JSON NULL COMMENT 'NULL/empty = all shifts',
    min_full_day_hours DECIMAL(4,2) NOT NULL,
    min_half_day_hours DECIMAL(4,2) NOT NULL,
    late_grace_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    early_exit_grace_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    missing_punch_treatment ENUM('absent','half_day','pending_review') NOT NULL,
    single_punch_treatment ENUM('absent','half_day','pending_review') NOT NULL,
    multiple_punch_handling VARCHAR(30) NOT NULL DEFAULT 'first_in_last_out',
    weekly_off_treatment ENUM('paid','unpaid','conditional') NOT NULL,
    holiday_treatment ENUM('paid','unpaid','conditional') NOT NULL,
    work_on_holiday_treatment ENUM('overtime','compensatory_off','normal_day') NOT NULL,
    work_on_weekly_off_treatment ENUM('overtime','compensatory_off','normal_day') NOT NULL,
    consecutive_absence_rule TINYINT UNSIGNED NULL,
    rounding_minutes TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = no rounding',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY attendance_rules_rule_id_unique (rule_id),
    CONSTRAINT attendance_rules_rule_id_foreign FOREIGN KEY (rule_id) REFERENCES rules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.5 Weekly Off and Sunday Rule
CREATE TABLE IF NOT EXISTS weekly_off_rules (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rule_id SMALLINT UNSIGNED NOT NULL,
    weekly_off_days JSON NOT NULL,
    is_paid TINYINT(1) NOT NULL DEFAULT 1,
    min_attendance_condition TINYINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY weekly_off_rules_rule_id_unique (rule_id),
    CONSTRAINT weekly_off_rules_rule_id_foreign FOREIGN KEY (rule_id) REFERENCES rules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.6 LOP Rule
CREATE TABLE IF NOT EXISTS lop_rules (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rule_id SMALLINT UNSIGNED NOT NULL,
    calculation_basis ENUM('calendar_days','working_days','fixed_days') NOT NULL,
    fixed_payroll_days TINYINT UNSIGNED NULL,
    half_day_lop_value DECIMAL(3,2) NOT NULL DEFAULT 0.5,
    full_day_lop_value DECIMAL(3,2) NOT NULL DEFAULT 1.0,
    unpaid_leave_as_lop TINYINT(1) NOT NULL DEFAULT 1,
    absent_day_as_lop TINYINT(1) NOT NULL DEFAULT 1,
    missing_punch_as_lop TINYINT(1) NOT NULL DEFAULT 1,
    late_count_conversion TINYINT UNSIGNED NULL,
    early_exit_conversion TINYINT UNSIGNED NULL,
    holiday_between_absences TINYINT(1) NOT NULL DEFAULT 0,
    weekly_off_between_absences TINYINT(1) NOT NULL DEFAULT 0,
    manual_lop_adjustment_allowed TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY lop_rules_rule_id_unique (rule_id),
    CONSTRAINT lop_rules_rule_id_foreign FOREIGN KEY (rule_id) REFERENCES rules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.7 PF Rule
CREATE TABLE IF NOT EXISTS pf_rules (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rule_id SMALLINT UNSIGNED NOT NULL,
    pf_applicable TINYINT(1) NOT NULL DEFAULT 1,
    salary_slab_from DECIMAL(12,2) NOT NULL DEFAULT 0,
    salary_slab_to DECIMAL(12,2) NULL,
    pf_wage_components JSON NULL COMMENT 'earnings_components ids',
    employee_pf_percentage DECIMAL(5,2) NOT NULL,
    employer_pf_percentage DECIMAL(5,2) NOT NULL,
    pf_wage_ceiling DECIMAL(12,2) NULL,
    restrict_to_wage_ceiling TINYINT(1) NOT NULL DEFAULT 1,
    voluntary_pf_allowed TINYINT(1) NOT NULL DEFAULT 0,
    rounding_method ENUM('nearest','up','down') NOT NULL DEFAULT 'nearest',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY pf_rules_rule_id_unique (rule_id),
    CONSTRAINT pf_rules_rule_id_foreign FOREIGN KEY (rule_id) REFERENCES rules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.8 ESI Rule
CREATE TABLE IF NOT EXISTS esi_rules (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rule_id SMALLINT UNSIGNED NOT NULL,
    esi_applicable TINYINT(1) NOT NULL DEFAULT 1,
    salary_slab_from DECIMAL(12,2) NOT NULL DEFAULT 0,
    salary_slab_to DECIMAL(12,2) NULL,
    esi_wage_components JSON NULL COMMENT 'earnings_components ids',
    employee_esi_percentage DECIMAL(5,2) NOT NULL,
    employer_esi_percentage DECIMAL(5,2) NOT NULL,
    rounding_method ENUM('nearest','up','down') NOT NULL DEFAULT 'nearest',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY esi_rules_rule_id_unique (rule_id),
    CONSTRAINT esi_rules_rule_id_foreign FOREIGN KEY (rule_id) REFERENCES rules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.9 TDS Rule
CREATE TABLE IF NOT EXISTS tds_rules (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rule_id SMALLINT UNSIGNED NOT NULL,
    tds_applicable TINYINT(1) NOT NULL DEFAULT 1,
    salary_slab_from DECIMAL(12,2) NOT NULL DEFAULT 0,
    salary_slab_to DECIMAL(12,2) NULL,
    tds_percentage DECIMAL(5,2) NOT NULL,
    calculation_basis ENUM('monthly_gross','annual_estimated_income','taxable_income') NOT NULL,
    taxable_components JSON NULL COMMENT 'earnings_components ids',
    exempt_components JSON NULL COMMENT 'earnings_components ids',
    fixed_tds_amount_allowed TINYINT(1) NOT NULL DEFAULT 0,
    rounding_method ENUM('nearest','up','down') NOT NULL DEFAULT 'nearest',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY tds_rules_rule_id_unique (rule_id),
    CONSTRAINT tds_rules_rule_id_foreign FOREIGN KEY (rule_id) REFERENCES rules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8.10 Overtime Rule
CREATE TABLE IF NOT EXISTS overtime_rules (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rule_id SMALLINT UNSIGNED NOT NULL,
    overtime_applicable TINYINT(1) NOT NULL DEFAULT 1,
    minimum_overtime_minutes SMALLINT UNSIGNED NULL,
    overtime_calculation ENUM('hourly_rate','fixed_rate','salary_formula') NULL,
    overtime_rate DECIMAL(6,2) NULL,
    overtime_rounding_minutes TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = no rounding',
    maximum_overtime_per_day_minutes SMALLINT UNSIGNED NULL,
    approval_required TINYINT(1) NOT NULL DEFAULT 1,
    weekly_off_overtime_rate DECIMAL(6,2) NULL,
    holiday_overtime_rate DECIMAL(6,2) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY overtime_rules_rule_id_unique (rule_id),
    CONSTRAINT overtime_rules_rule_id_foreign FOREIGN KEY (rule_id) REFERENCES rules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employee Number sequence tracking — guarantees a sequence never reuses a
-- number after employee deletion/inactivation (only ever increments).
CREATE TABLE IF NOT EXISTS rule_sequence_counters (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rule_id SMALLINT UNSIGNED NOT NULL,
    scope_key VARCHAR(100) NOT NULL COMMENT 'e.g. branch:2:FY2026-27',
    last_sequence INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY rule_sequence_counters_rule_id_scope_key_unique (rule_id, scope_key),
    CONSTRAINT rule_sequence_counters_rule_id_foreign FOREIGN KEY (rule_id) REFERENCES rules (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Historical rule-application snapshot + LOP calculated/approved columns
--    on the EXISTING attendance and payroll_records tables
-- -----------------------------------------------------------------------------
CALL sp_add_column_if_not_exists('attendance', 'applied_rules', '`applied_rules` JSON NULL AFTER `remarks`');

CALL sp_add_column_if_not_exists('payroll_records', 'applied_rules', '`applied_rules` JSON NULL AFTER `generated_at`');
CALL sp_add_column_if_not_exists('payroll_records', 'calculated_lop_days', '`calculated_lop_days` DECIMAL(5,2) NULL AFTER `lop_days`');
CALL sp_add_column_if_not_exists('payroll_records', 'lop_override_reason', '`lop_override_reason` TEXT NULL AFTER `calculated_lop_days`');


-- -----------------------------------------------------------------------------
-- Cleanup: drop the temporary helper procedure
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_column_if_not_exists;

SET SQL_MODE = @OLD_SQL_MODE;


-- =============================================================================
-- POST-EXECUTION VERIFICATION
-- =============================================================================

-- New tables — expect all 10 to appear
SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
    'rules','employee_number_rules','attendance_rules','weekly_off_rules','lop_rules',
    'pf_rules','esi_rules','tds_rules','overtime_rules','rule_sequence_counters'
  );

-- New columns on existing tables
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND ((TABLE_NAME = 'attendance' AND COLUMN_NAME = 'applied_rules')
    OR (TABLE_NAME = 'payroll_records' AND COLUMN_NAME IN ('applied_rules','calculated_lop_days','lop_override_reason')));

-- Foreign keys — expect one per detail table (rule_id -> rules.id), plus the
-- two extra FKs on employee_number_rules (branch_id, contractor_id)
SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IN ('rules', 'branches', 'contractors')
  AND TABLE_NAME IN (
    'employee_number_rules','attendance_rules','weekly_off_rules','lop_rules',
    'pf_rules','esi_rules','tds_rules','overtime_rules','rule_sequence_counters'
  );

-- Confirm the temporary helper procedure was cleaned up (expect zero rows)
SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME = 'sp_add_column_if_not_exists';

-- Sanity check: confirm no existing data was touched
SELECT
  (SELECT COUNT(*) FROM attendance) AS attendance_row_count,
  (SELECT COUNT(*) FROM payroll_records) AS payroll_records_row_count;
