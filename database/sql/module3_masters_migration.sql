-- =============================================================================
-- Module 3 - Masters: Production Database Migration Script
-- =============================================================================
-- Covers every schema change introduced by the Module 3 FSD compliance work:
--   1. Department Master   4. Leave Type Master
--   2. Shift Master        5. Salary Slab Master
--   3. Holiday Calendar    6. Bank Master
--
-- Safe to run on a live database and safe to re-run (idempotent):
--   - CREATE TABLE uses IF NOT EXISTS.
--   - ADD COLUMN / ADD INDEX / ADD FOREIGN KEY go through helper procedures
--     that check INFORMATION_SCHEMA first, so re-running this script is a
--     no-op wherever the change already exists. This avoids relying on
--     `ADD COLUMN IF NOT EXISTS` syntax, which is not supported on all
--     MySQL 5.7 installs — the INFORMATION_SCHEMA + PREPARE/EXECUTE pattern
--     used here works on MySQL 5.7 and 8.0 alike.
--   - Data backfill/dedup UPDATE statements are self-limiting (their WHERE
--     clauses only match rows still in the "old" state), so re-running them
--     affects zero rows the second time.
--
-- Requires: CREATE ROUTINE / ALTER ROUTINE privilege (for the temporary
-- helper procedures, dropped again at the very end of this script).
--
-- Take a full database backup before running this against production.
-- =============================================================================

SET @OLD_SQL_MODE = @@SQL_MODE;
SET SQL_MODE = 'STRICT_ALL_TABLES';

-- -----------------------------------------------------------------------------
-- Helper procedures (temporary — dropped at the end of this script)
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

DROP PROCEDURE IF EXISTS sp_drop_column_if_exists$$
CREATE PROCEDURE sp_drop_column_if_exists(
    IN p_table VARCHAR(64), IN p_column VARCHAR(64)
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` DROP COLUMN `', p_column, '`');
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS sp_add_index_if_not_exists$$
CREATE PROCEDURE sp_add_index_if_not_exists(
    IN p_table VARCHAR(64), IN p_index_name VARCHAR(64), IN p_indexdef TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index_name
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD ', p_indexdef);
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
-- 1. DEPARTMENT MASTER (FSD 7.1)
-- =============================================================================

-- 1a. Add `description` (optional multiline text)
CALL sp_add_column_if_not_exists('departments', 'description', '`description` TEXT NULL AFTER `code`');

-- 1b. Backfill NULL/blank codes to a deterministic unique placeholder
UPDATE departments
SET code = CONCAT('DEPT-', id)
WHERE code IS NULL OR code = '';

-- 1c. De-duplicate any remaining duplicate codes (keep the lowest id as-is,
--     rename later duplicates) before the UNIQUE constraint is added
UPDATE departments d
SET d.code = CONCAT(d.code, '-', d.id)
WHERE (
    SELECT COUNT(*) FROM (SELECT id, code FROM departments) x
    WHERE x.code = d.code AND x.id < d.id
) > 0;

-- 1d. De-duplicate (branch_id, name) combinations the same way, scoped per branch
UPDATE departments d
SET d.name = CONCAT(d.name, ' (', d.id, ')')
WHERE (
    SELECT COUNT(*) FROM (SELECT id, branch_id, name FROM departments) x
    WHERE x.branch_id <=> d.branch_id AND x.name = d.name AND x.id < d.id
) > 0;

-- 1e. Make `code` mandatory (FSD: "Department Code ... Mandatory")
ALTER TABLE departments MODIFY code VARCHAR(20) NOT NULL;

-- 1f. Uniqueness constraints
CALL sp_add_index_if_not_exists('departments', 'departments_code_unique', 'UNIQUE INDEX `departments_code_unique` (`code`)');
CALL sp_add_index_if_not_exists('departments', 'departments_branch_id_name_unique', 'UNIQUE INDEX `departments_branch_id_name_unique` (`branch_id`,`name`)');


-- =============================================================================
-- 2. SHIFT MASTER (FSD 7.2)
-- =============================================================================

-- 2a. Branch Applicability (multi-select) — new pivot table
CREATE TABLE IF NOT EXISTS shift_branches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shift_id SMALLINT UNSIGNED NOT NULL,
    branch_id SMALLINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY shift_branches_shift_id_branch_id_unique (shift_id, branch_id),
    KEY shift_branches_branch_id_foreign (branch_id),
    CONSTRAINT shift_branches_shift_id_foreign FOREIGN KEY (shift_id) REFERENCES shifts (id) ON DELETE CASCADE,
    CONSTRAINT shift_branches_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2b. Split grace time into Late Entry / Early Exit, add Employee Type Applicability
CALL sp_add_column_if_not_exists('shifts', 'grace_late_entry_minutes', '`grace_late_entry_minutes` SMALLINT UNSIGNED NULL AFTER `grace_minutes`');
CALL sp_add_column_if_not_exists('shifts', 'grace_early_exit_minutes', '`grace_early_exit_minutes` SMALLINT UNSIGNED NULL AFTER `grace_late_entry_minutes`');
CALL sp_add_column_if_not_exists('shifts', 'applicable_employee_types', '`applicable_employee_types` JSON NULL AFTER `is_overnight`');

-- 2c. Backfill both new grace columns from the existing combined value.
--     Built as dynamic SQL because `grace_minutes` itself may already have
--     been dropped by a prior run of this script — referencing it directly
--     in a plain UPDATE would fail to even parse in that case.
SET @grace_col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'shifts' AND COLUMN_NAME = 'grace_minutes'
);
SET @ddl = IF(@grace_col_exists > 0,
    'UPDATE shifts SET grace_late_entry_minutes = grace_minutes, grace_early_exit_minutes = grace_minutes WHERE grace_late_entry_minutes IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2d. Drop the old combined column (superseded by the two above)
CALL sp_drop_column_if_exists('shifts', 'grace_minutes');


-- =============================================================================
-- 3. HOLIDAY CALENDAR (FSD 7.3)
-- =============================================================================

-- 3a. Calendar Name, Description (was missing from the DB despite being used
--     in application code), Paid Holiday flag, Employee Type Applicability
CALL sp_add_column_if_not_exists('holidays', 'calendar_name', '`calendar_name` VARCHAR(150) NULL AFTER `branch_id`');
CALL sp_add_column_if_not_exists('holidays', 'description', '`description` TEXT NULL AFTER `name`');
CALL sp_add_column_if_not_exists('holidays', 'is_paid', '`is_paid` TINYINT(1) NOT NULL DEFAULT 1 AFTER `type`');
CALL sp_add_column_if_not_exists('holidays', 'applicable_employee_types', '`applicable_employee_types` JSON NULL AFTER `is_paid`');
CALL sp_add_column_if_not_exists('holidays', 'deleted_at', '`deleted_at` TIMESTAMP NULL DEFAULT NULL');

-- 3b. Backfill calendar_name for existing rows: "{Branch|All Branches} Holidays {Year}"
UPDATE holidays h
LEFT JOIN branches b ON b.id = h.branch_id
SET h.calendar_name = CONCAT(COALESCE(b.name, 'All Branches'), ' Holidays ', h.year)
WHERE h.calendar_name IS NULL;

-- 3c. Remap Holiday Type to the FSD's 4-value set
--     (national -> public_holiday, regional -> festival_holiday, optional unchanged, company_holiday new)
ALTER TABLE holidays MODIFY type VARCHAR(30) NOT NULL DEFAULT 'public_holiday';
UPDATE holidays SET type = 'public_holiday'   WHERE type = 'national';
UPDATE holidays SET type = 'festival_holiday' WHERE type = 'regional';
ALTER TABLE holidays MODIFY type ENUM('public_holiday','festival_holiday','optional','company_holiday') NOT NULL DEFAULT 'public_holiday';

-- 3d. Sunday-pay policy for Company Labour / Contract Labour (Staff stays
--     fixed = paid, not configurable, so no row is stored for Staff)
INSERT IGNORE INTO settings (`group`, `key`, `value`, `type`, `description`) VALUES
    ('holiday', 'sunday_paid_company_labour',  '1', 'boolean', 'Sunday Paid - Company Labour: whether Sunday is treated as a paid weekly holiday for Company Labour employees.'),
    ('holiday', 'sunday_paid_contract_labour', '1', 'boolean', 'Sunday Paid - Contract Labour: whether Sunday is treated as a paid weekly holiday for Contract Labour employees.');


-- =============================================================================
-- 4. LEAVE TYPE MASTER (FSD 7.4)
-- =============================================================================

CALL sp_add_column_if_not_exists('leave_types', 'applicable_employee_types', '`applicable_employee_types` JSON NULL AFTER `gender_specific`');
CALL sp_add_column_if_not_exists('leave_types', 'deleted_at', '`deleted_at` TIMESTAMP NULL DEFAULT NULL');


-- =============================================================================
-- 5. SALARY SLAB MASTER (FSD 7.5)
-- =============================================================================

-- 5a. TDS / PF / ESI percentages, Employee Type Applicability, Branch, Effective dates
CALL sp_add_column_if_not_exists('salary_slabs', 'tds_percentage', '`tds_percentage` DECIMAL(5,2) NULL AFTER `max_ctc`');
CALL sp_add_column_if_not_exists('salary_slabs', 'pf_employee_percentage', '`pf_employee_percentage` DECIMAL(5,2) NULL AFTER `tds_percentage`');
CALL sp_add_column_if_not_exists('salary_slabs', 'pf_employer_percentage', '`pf_employer_percentage` DECIMAL(5,2) NULL AFTER `pf_employee_percentage`');
CALL sp_add_column_if_not_exists('salary_slabs', 'esi_employee_percentage', '`esi_employee_percentage` DECIMAL(5,2) NULL AFTER `pf_employer_percentage`');
CALL sp_add_column_if_not_exists('salary_slabs', 'esi_employer_percentage', '`esi_employer_percentage` DECIMAL(5,2) NULL AFTER `esi_employee_percentage`');
CALL sp_add_column_if_not_exists('salary_slabs', 'applicable_employee_types', '`applicable_employee_types` JSON NULL AFTER `esi_employer_percentage`');
CALL sp_add_column_if_not_exists('salary_slabs', 'branch_id', '`branch_id` SMALLINT UNSIGNED NULL AFTER `applicable_employee_types` COMMENT ''NULL = all branches''');
CALL sp_add_column_if_not_exists('salary_slabs', 'effective_from', '`effective_from` DATE NULL AFTER `branch_id`');
CALL sp_add_column_if_not_exists('salary_slabs', 'effective_to', '`effective_to` DATE NULL AFTER `effective_from`');
CALL sp_add_column_if_not_exists('salary_slabs', 'deleted_at', '`deleted_at` TIMESTAMP NULL DEFAULT NULL');

-- 5b. De-duplicate any existing duplicate slab names before the UNIQUE constraint
UPDATE salary_slabs s
SET s.name = CONCAT(s.name, ' (', s.id, ')')
WHERE (
    SELECT COUNT(*) FROM (SELECT id, name FROM salary_slabs) x
    WHERE x.name = s.name AND x.id < s.id
) > 0;

-- 5c. Constraints
CALL sp_add_index_if_not_exists('salary_slabs', 'salary_slabs_name_unique', 'UNIQUE INDEX `salary_slabs_name_unique` (`name`)');
CALL sp_add_fk_if_not_exists('salary_slabs', 'salary_slabs_branch_id_foreign', 'CONSTRAINT `salary_slabs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL');


-- =============================================================================
-- 6. BANK MASTER (FSD 7.6) — net new
-- =============================================================================

CREATE TABLE IF NOT EXISTS banks (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY banks_code_unique (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Cleanup: drop the temporary helper procedures
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_column_if_not_exists;
DROP PROCEDURE IF EXISTS sp_drop_column_if_exists;
DROP PROCEDURE IF EXISTS sp_add_index_if_not_exists;
DROP PROCEDURE IF EXISTS sp_add_fk_if_not_exists;

SET SQL_MODE = @OLD_SQL_MODE;


-- =============================================================================
-- POST-EXECUTION VERIFICATION
-- =============================================================================

-- 1) Department Master
SHOW COLUMNS FROM departments;
SHOW INDEX FROM departments WHERE Key_name IN ('departments_code_unique', 'departments_branch_id_name_unique');
SELECT COUNT(*) AS departments_with_null_or_blank_code FROM departments WHERE code IS NULL OR code = '';

-- 2) Shift Master
SHOW COLUMNS FROM shifts;
SHOW COLUMNS FROM shift_branches;
SHOW INDEX FROM shift_branches WHERE Key_name = 'shift_branches_shift_id_branch_id_unique';
SELECT COUNT(*) AS shifts_missing_grace_columns FROM shifts WHERE grace_late_entry_minutes IS NULL AND grace_early_exit_minutes IS NULL;

-- 3) Holiday Calendar
SHOW COLUMNS FROM holidays;
SELECT DISTINCT type FROM holidays;                                   -- expect only the 4 new enum values (or none, if table is empty)
SELECT COUNT(*) AS holidays_missing_calendar_name FROM holidays WHERE calendar_name IS NULL;
SELECT `group`, `key`, `value` FROM settings WHERE `group` = 'holiday';

-- 4) Leave Type Master
SHOW COLUMNS FROM leave_types;

-- 5) Salary Slab Master
SHOW COLUMNS FROM salary_slabs;
SHOW INDEX FROM salary_slabs WHERE Key_name IN ('salary_slabs_name_unique', 'salary_slabs_branch_id_foreign');
SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'salary_slabs' AND CONSTRAINT_TYPE = 'FOREIGN KEY';

-- 6) Bank Master
SHOW TABLES LIKE 'banks';
SHOW COLUMNS FROM banks;
SHOW INDEX FROM banks WHERE Key_name = 'banks_code_unique';

-- 7) Confirm no temporary helper procedures were left behind
SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME LIKE 'sp_add_%' OR ROUTINE_NAME LIKE 'sp_drop_%';
-- (expect zero rows)
