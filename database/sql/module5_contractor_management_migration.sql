-- =============================================================================
-- Module 5 - Contractor Management: Production Database Migration Script
-- =============================================================================
-- Covers every schema change introduced by the Contractor Master FSD work:
--   - New contractor fields (alternate phone, state/district/PIN, PAN,
--     PF/ESI registration numbers, agreement dates, max labour count)
--   - Unique index on contractor name (code was already unique)
--   - contractor_branches pivot (multi-select branch applicability)
--   - contractor_documents table (agreement/licence/supporting uploads)
--
-- Safe to run on a live database and safe to re-run (idempotent):
--   - New tables use CREATE TABLE IF NOT EXISTS.
--   - ADD COLUMN / ADD INDEX on the EXISTING `contractors` table goes
--     through helper procedures that check INFORMATION_SCHEMA first.
--   - Existing duplicate contractor names (if any) are disambiguated with a
--     deterministic suffix BEFORE the unique index is added, so this cannot
--     fail against live data.
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

DELIMITER ;


-- =============================================================================
-- MODULE 5 - CONTRACTOR MANAGEMENT
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. New fields on the existing `contractors` table
-- -----------------------------------------------------------------------------
CALL sp_add_column_if_not_exists('contractors', 'alternate_phone', '`alternate_phone` VARCHAR(20) NULL AFTER `phone`');
CALL sp_add_column_if_not_exists('contractors', 'state', '`state` VARCHAR(100) NULL AFTER `address`');
CALL sp_add_column_if_not_exists('contractors', 'district', '`district` VARCHAR(100) NULL AFTER `state`');
CALL sp_add_column_if_not_exists('contractors', 'pincode', '`pincode` VARCHAR(10) NULL AFTER `district`');
CALL sp_add_column_if_not_exists('contractors', 'pan_number', '`pan_number` VARCHAR(20) NULL AFTER `gst_number`');
CALL sp_add_column_if_not_exists('contractors', 'pf_registration_number', '`pf_registration_number` VARCHAR(50) NULL AFTER `pan_number`');
CALL sp_add_column_if_not_exists('contractors', 'esi_registration_number', '`esi_registration_number` VARCHAR(50) NULL AFTER `pf_registration_number`');
CALL sp_add_column_if_not_exists('contractors', 'agreement_start_date', '`agreement_start_date` DATE NULL AFTER `license_expiry`');
CALL sp_add_column_if_not_exists('contractors', 'agreement_end_date', '`agreement_end_date` DATE NULL AFTER `agreement_start_date`');
CALL sp_add_column_if_not_exists('contractors', 'max_labour_count', '`max_labour_count` INT UNSIGNED NULL AFTER `agreement_end_date`');

-- -----------------------------------------------------------------------------
-- 2. Contractor Name uniqueness (FSD: "Contractor Name ... Unique")
--    De-duplicate any existing clashes first so this is safe against live data.
-- -----------------------------------------------------------------------------
UPDATE contractors c
SET c.name = CONCAT(c.name, ' (', c.id, ')')
WHERE (
    SELECT COUNT(*) FROM (SELECT id, name FROM contractors) x
    WHERE x.name = c.name AND x.id < c.id
) > 0;

CALL sp_add_index_if_not_exists('contractors', 'contractors_name_unique', 'UNIQUE INDEX `contractors_name_unique` (`name`)');

-- -----------------------------------------------------------------------------
-- 3. Branch Applicability multi-select pivot
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contractor_branches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    contractor_id SMALLINT UNSIGNED NOT NULL,
    branch_id SMALLINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY contractor_branches_contractor_id_branch_id_unique (contractor_id, branch_id),
    KEY contractor_branches_branch_id_foreign (branch_id),
    CONSTRAINT contractor_branches_contractor_id_foreign FOREIGN KEY (contractor_id) REFERENCES contractors (id) ON DELETE CASCADE,
    CONSTRAINT contractor_branches_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES branches (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Document upload (agreement / licence / supporting documents)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contractor_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    contractor_id SMALLINT UNSIGNED NOT NULL,
    document_type ENUM('agreement','licence','other') NOT NULL DEFAULT 'other',
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY contractor_documents_contractor_id_index (contractor_id),
    CONSTRAINT contractor_documents_contractor_id_foreign FOREIGN KEY (contractor_id) REFERENCES contractors (id) ON DELETE CASCADE,
    CONSTRAINT contractor_documents_uploaded_by_foreign FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- Cleanup: drop the temporary helper procedures
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_column_if_not_exists;
DROP PROCEDURE IF EXISTS sp_add_index_if_not_exists;

SET SQL_MODE = @OLD_SQL_MODE;


-- =============================================================================
-- POST-EXECUTION VERIFICATION
-- =============================================================================

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contractors'
ORDER BY ORDINAL_POSITION;

SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contractors'
  AND INDEX_NAME IN ('contractors_name_unique', 'contractors_code_unique');

SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('contractor_branches', 'contractor_documents');

SELECT ROUTINE_NAME FROM INFORMATION_SCHEMA.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME LIKE 'sp_add_%';
-- (expect zero rows — confirms helper procedures were cleaned up)

SELECT COUNT(*) AS contractors_row_count FROM contractors;
-- (compare before/after — must be unchanged)
