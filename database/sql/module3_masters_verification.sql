-- =============================================================================
-- Module 3 - Masters: Live Database Verification Script
-- =============================================================================
-- Paste this into phpMyAdmin's SQL tab against whyceffy_padhmam_nkt and send
-- back the results. It only reads (no INSERT/UPDATE/ALTER), so it is
-- completely safe to run on production as-is.
--
-- What to look for in each result set:
--   - A row present  = that column/index/table already exists on this DB.
--   - Zero rows      = it's missing and module3_masters_migration.sql still
--                       needs to add it.
-- =============================================================================

-- 1) Department Master — expect: description, code (unique), plus a
--    (branch_id, name) unique index
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'departments'
ORDER BY ORDINAL_POSITION;

SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'departments'
  AND INDEX_NAME IN ('departments_code_unique', 'departments_branch_id_name_unique');

-- 2) Shift Master — expect: grace_late_entry_minutes, grace_early_exit_minutes,
--    applicable_employee_types columns, and a shift_branches table
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'shifts'
ORDER BY ORDINAL_POSITION;

SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'shift_branches';

-- 3) Holiday Calendar — expect: calendar_name, description, is_paid,
--    applicable_employee_types, deleted_at; type enum with 4 new values
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'holidays'
ORDER BY ORDINAL_POSITION;

SELECT `group`, `key`, `value` FROM settings WHERE `group` = 'holiday';

-- 4) Leave Type Master — expect: applicable_employee_types, deleted_at
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leave_types'
ORDER BY ORDINAL_POSITION;

-- 5) Salary Slab Master — expect: tds_percentage, pf_employee_percentage,
--    pf_employer_percentage, esi_employee_percentage, esi_employer_percentage,
--    applicable_employee_types, branch_id, effective_from, effective_to, deleted_at
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'salary_slabs'
ORDER BY ORDINAL_POSITION;

SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'salary_slabs';

-- 6) Bank Master — expect: id, name, code (unique, nullable), is_active,
--    timestamps, deleted_at
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'banks'
ORDER BY ORDINAL_POSITION;

-- 7) Quick summary — presence check across everything at once
SELECT
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='departments' AND COLUMN_NAME='description') AS dept_description,
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='departments' AND INDEX_NAME='departments_code_unique') AS dept_code_unique,
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='shifts' AND COLUMN_NAME='grace_late_entry_minutes') AS shift_grace_split,
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='shift_branches') AS shift_branches_table,
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='holidays' AND COLUMN_NAME='calendar_name') AS holiday_calendar_name,
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='leave_types' AND COLUMN_NAME='applicable_employee_types') AS leavetype_applicability,
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='salary_slabs' AND COLUMN_NAME='tds_percentage') AS slab_tds_pct,
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='banks') AS banks_table
;
-- Read this last row as 8 flags, each 1 (present) or 0 (missing).
