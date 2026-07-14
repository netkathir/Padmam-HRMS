-- =============================================================================
-- Module 7 - Biometric Attendance Management: Production Database Migration Script
-- =============================================================================
-- Covers every schema change introduced by the Biometric Attendance
-- Management FSD work (FSD 11.1-11.5):
--   1. `attendance.status` ENUM gains 8 FSD values (paid_leave, unpaid_leave,
--      weekly_off, paid_holiday, unpaid_holiday, on_duty, missing_punch,
--      pending_review) — existing values (holiday, weekend, on_leave, late,
--      early_exit) are KEPT, not removed, so existing rows keep displaying
--      correctly.
--   2. `attendance.source` ENUM gains `corrected` (existing: web, mobile,
--      biometric, manual).
--   3. `attendance` gains leave_type_id, lop_days, correction_reason,
--      supporting_document_path, ot_approval_status/ot_approved_by/
--      ot_approved_at (a SEPARATE approval concept from the existing
--      approval_status/approved_by/approved_at, which is untouched), and
--      biometric_upload_id.
--   4. `attendance_logs` gains biometric_upload_id (the raw-punch landing
--      table already existed and is otherwise unchanged).
--   5. New `biometric_uploads` table — Excel upload batch header + the
--      FSD-mandated validation summary counts.
--   6. Two new `settings` rows (group `attendance`) — default Excel column
--      mapping and the manual-entry-allowed flag.
--
-- Safe to run on a live database and safe to re-run (idempotent):
--   - ENUM widening is guarded (only runs if the new values aren't already
--     present), and only ever ADDS values — no existing value is removed.
--   - ADD COLUMN / ADD FOREIGN KEY on existing tables goes through helper
--     procedures that check INFORMATION_SCHEMA first.
--   - CREATE TABLE IF NOT EXISTS for the new table.
--   - Settings use INSERT IGNORE against the existing UNIQUE(group, key).
--   - No existing data is altered or deleted anywhere in this script.
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
-- MODULE 7 - BIOMETRIC ATTENDANCE MANAGEMENT
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. `attendance.status` / `attendance.source` ENUM widening (FSD 11.3/11.4)
--    Guarded so re-running is a no-op; only ever adds values.
-- -----------------------------------------------------------------------------
SET @status_type = (SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'status');
SET @needs_status_widen = (@status_type IS NULL OR @status_type NOT LIKE '%missing_punch%');
SET @ddl = IF(@needs_status_widen,
    "ALTER TABLE `attendance` MODIFY `status` ENUM('present','absent','half_day','holiday','weekend','on_leave','late','early_exit','paid_leave','unpaid_leave','weekly_off','paid_holiday','unpaid_holiday','on_duty','missing_punch','pending_review') NOT NULL DEFAULT 'absent'",
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @source_type = (SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'source');
SET @needs_source_widen = (@source_type IS NULL OR @source_type NOT LIKE '%corrected%');
SET @ddl = IF(@needs_source_widen,
    "ALTER TABLE `attendance` MODIFY `source` ENUM('web','mobile','biometric','manual','corrected') NOT NULL DEFAULT 'manual'",
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 2. New columns on `attendance` (FSD 11.4/11.5)
-- -----------------------------------------------------------------------------
CALL sp_add_column_if_not_exists('attendance', 'leave_type_id', '`leave_type_id` TINYINT UNSIGNED NULL AFTER `status`');
CALL sp_add_fk_if_not_exists('attendance', 'attendance_leave_type_id_foreign', 'CONSTRAINT `attendance_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE SET NULL');

CALL sp_add_column_if_not_exists('attendance', 'lop_days', '`lop_days` DECIMAL(4,2) NULL AFTER `leave_type_id`');
CALL sp_add_column_if_not_exists('attendance', 'correction_reason', '`correction_reason` TEXT NULL AFTER `remarks`');
CALL sp_add_column_if_not_exists('attendance', 'supporting_document_path', '`supporting_document_path` VARCHAR(255) NULL AFTER `correction_reason`');

CALL sp_add_column_if_not_exists('attendance', 'ot_approval_status', "`ot_approval_status` ENUM('pending','approved','rejected') NULL AFTER `supporting_document_path`");
CALL sp_add_column_if_not_exists('attendance', 'ot_approved_by', '`ot_approved_by` INT UNSIGNED NULL AFTER `ot_approval_status`');
CALL sp_add_fk_if_not_exists('attendance', 'attendance_ot_approved_by_foreign', 'CONSTRAINT `attendance_ot_approved_by_foreign` FOREIGN KEY (`ot_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL');
CALL sp_add_column_if_not_exists('attendance', 'ot_approved_at', '`ot_approved_at` DATETIME NULL AFTER `ot_approved_by`');

CALL sp_add_column_if_not_exists('attendance', 'biometric_upload_id', '`biometric_upload_id` INT UNSIGNED NULL AFTER `ot_approved_at`');

-- -----------------------------------------------------------------------------
-- 3. Raw punch table link (FSD 11.2)
-- -----------------------------------------------------------------------------
CALL sp_add_column_if_not_exists('attendance_logs', 'biometric_upload_id', '`biometric_upload_id` INT UNSIGNED NULL AFTER `employee_code`');

-- -----------------------------------------------------------------------------
-- 4. Biometric Excel upload batch header (FSD 11.2) — net new
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS biometric_uploads (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    branch_id SMALLINT UNSIGNED NOT NULL,
    period_from DATE NOT NULL,
    period_to DATE NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    sheet_name VARCHAR(100) NULL,
    column_mapping JSON NULL,
    remarks VARCHAR(255) NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    valid_rows INT UNSIGNED NOT NULL DEFAULT 0,
    invalid_rows INT UNSIGNED NOT NULL DEFAULT 0,
    duplicate_rows INT UNSIGNED NOT NULL DEFAULT 0,
    unknown_employee_rows INT UNSIGNED NOT NULL DEFAULT 0,
    invalid_date_rows INT UNSIGNED NOT NULL DEFAULT 0,
    invalid_time_rows INT UNSIGNED NOT NULL DEFAULT 0,
    error_file_path VARCHAR(255) NULL,
    status ENUM('mapping','processing','completed','failed') NOT NULL DEFAULT 'mapping',
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY biometric_uploads_branch_period_index (branch_id, period_from, period_to),
    CONSTRAINT biometric_uploads_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES branches (id),
    CONSTRAINT biometric_uploads_uploaded_by_foreign FOREIGN KEY (uploaded_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign keys back to biometric_uploads (must come after the table exists)
CALL sp_add_fk_if_not_exists('attendance', 'attendance_biometric_upload_id_foreign', 'CONSTRAINT `attendance_biometric_upload_id_foreign` FOREIGN KEY (`biometric_upload_id`) REFERENCES `biometric_uploads` (`id`) ON DELETE SET NULL');
CALL sp_add_fk_if_not_exists('attendance_logs', 'attendance_logs_biometric_upload_id_foreign', 'CONSTRAINT `attendance_logs_biometric_upload_id_foreign` FOREIGN KEY (`biometric_upload_id`) REFERENCES `biometric_uploads` (`id`) ON DELETE SET NULL');

-- -----------------------------------------------------------------------------
-- 5. Configurable Attendance module settings (FSD 11.2/11.3)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO settings (`group`, `key`, `value`, `type`, `description`) VALUES
    ('attendance', 'default_excel_column_mapping',
     '{"employee_number":"Employee Number","biometric_id":"Biometric ID","employee_name":"Employee Name","punch_date":"Punch Date","punch_time":"Punch Time","punch_type":"Punch Type","device_id":"Device ID","location":"Location","shift_code":"Shift Code"}',
     'json', 'Best-guess default column mapping (by header text) pre-filled on the biometric upload mapping screen; overridable per upload.'),
    ('attendance', 'allow_manual_when_no_biometric', '1', 'boolean',
     'FSD 11.3 - attendance shall not be processed without valid biometric data unless manual attendance entry is permitted. When true, the existing manual entry/mark screens remain usable regardless of biometric upload status.');


-- -----------------------------------------------------------------------------
-- Cleanup: drop the temporary helper procedures
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_column_if_not_exists;
DROP PROCEDURE IF EXISTS sp_add_fk_if_not_exists;

SET SQL_MODE = @OLD_SQL_MODE;


-- =============================================================================
-- VERIFICATION QUERIES (read-only — run after the script to confirm success)
-- =============================================================================

-- Expect the widened enum to include the new values:
-- SHOW COLUMNS FROM attendance LIKE 'status';
-- SHOW COLUMNS FROM attendance LIKE 'source';

-- Expect 8 new attendance columns:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance'
--   AND COLUMN_NAME IN (
--     'leave_type_id','lop_days','correction_reason','supporting_document_path',
--     'ot_approval_status','ot_approved_by','ot_approved_at','biometric_upload_id'
--   );

-- Expect 1 new attendance_logs column:
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance_logs' AND COLUMN_NAME = 'biometric_upload_id';

-- Expect the new table to exist:
-- SHOW TABLES LIKE 'biometric_uploads';

-- Expect 2 rows:
-- SELECT `group`, `key` FROM settings WHERE `group` = 'attendance';
