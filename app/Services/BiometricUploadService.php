<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\BiometricUpload;
use App\Models\Checkpoint;
use App\Models\Employee;
use App\Models\EmployeeCheckpoint;
use App\Models\EmployeeExit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Module 7 FSD 11.2 — biometric device export upload: fixed-format parsing
 * and row-by-row validation + import into `attendance_logs`. The device
 * always exports the same column layout (Person ID, Name, Department, Time,
 * Attendance Status, Attendance Check Point, Custom Name, Data Source,
 * Handling Type, Temperature, Abnormal) — there is no user-configurable
 * column mapping anymore; this service reads that fixed header set
 * directly.
 */
class BiometricUploadService
{
    /** The device export's fixed header row, in order. Matched case-insensitively; column position is not assumed. */
    public const HEADERS = [
        'person_id'         => 'Person ID',
        'name'              => 'Name',
        'department'        => 'Department',
        'time'              => 'Time',
        'attendance_status' => 'Attendance Status',
        'checkpoint'        => 'Attendance Check Point',
        'custom_name'       => 'Custom Name',
        'data_source'       => 'Data Source',
        'handling_type'     => 'Handling Type',
        'temperature'       => 'Temperature',
        'abnormal'          => 'Abnormal',
    ];

    /** Mandatory columns per the FSD — everything else is optional/informational. */
    private const REQUIRED = ['person_id', 'department', 'time', 'checkpoint'];

    /** Same-person punches within this many minutes are treated as one duplicate biometric read — only the earliest is kept. */
    private const DUPLICATE_WINDOW_MINUTES = 3;

    /** Preview screen shows at most this many parsed rows (both valid and invalid), to keep the confirm page light for large files. */
    private const PREVIEW_LIMIT = 50;

    public function sheetNames(string $absolutePath): array
    {
        return IOFactory::createReaderForFile($absolutePath)->listWorksheetNames($absolutePath);
    }

    /**
     * The upload screen no longer asks for a Branch/Period — this is a
     * straight bulk dump of raw punches, not scoped to a reporting period by
     * the user. period_from/period_to are still real NOT NULL columns
     * (consumed by the generic Attendance Reports), so they're derived
     * silently here from the earliest/latest "Time" value actually found in
     * the file, rather than asked for.
     *
     * @return array{0: ?\Carbon\Carbon, 1: ?\Carbon\Carbon} [min, max] — both null if no valid Time value was found anywhere in the sheet.
     */
    public function derivePeriod(string $absolutePath, ?string $sheetName): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        if ($sheetName) {
            $reader->setLoadSheetsOnly([$sheetName]);
        }
        $spreadsheet = $reader->load($absolutePath);
        $sheet = $sheetName ? $spreadsheet->getSheetByName($sheetName) : $spreadsheet->getActiveSheet();
        $rows = $sheet->rangeToArray($sheet->calculateWorksheetDimension(), null, false, false, true);
        array_shift($rows); // header

        $columns = $this->resolveColumns($absolutePath, $sheetName);
        if (! $columns['time']) {
            return [null, null];
        }

        $min = $max = null;
        foreach ($rows as $row) {
            $raw = $row[$columns['time']] ?? null;
            try {
                $time = $this->parseExcelDateTime($raw);
            } catch (\Throwable) {
                $time = null;
            }
            if (! $time) {
                continue;
            }
            if (! $min || $time->lt($min)) {
                $min = $time;
            }
            if (! $max || $time->gt($max)) {
                $max = $time;
            }
        }

        return [$min?->copy()->startOfDay(), $max?->copy()->startOfDay()];
    }

    /** Maps the fixed header row (however it's ordered in the actual file) to spreadsheet column letters. */
    private function resolveColumns(string $absolutePath, ?string $sheetName): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        if ($sheetName) {
            $reader->setLoadSheetsOnly([$sheetName]);
        }
        $spreadsheet = $reader->load($absolutePath);
        $sheet = $sheetName ? $spreadsheet->getSheetByName($sheetName) : $spreadsheet->getActiveSheet();
        $headerRow = $sheet->rangeToArray($sheet->calculateWorksheetDimension(), null, false, false)[0] ?? [];

        $columns = [];
        foreach (self::HEADERS as $field => $label) {
            $columns[$field] = null;
            foreach ($headerRow as $i => $text) {
                if (strcasecmp(trim((string) $text), $label) === 0) {
                    $columns[$field] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                    break;
                }
            }
        }

        return $columns;
    }

    /** Validation rule 1 — the device export wraps Person ID in a leading apostrophe (Excel "text" marker); strip it. */
    private function cleanPersonId(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        return ltrim(trim($raw), "'");
    }

    /**
     * Validation rule 3 — resolves (Person ID, raw Attendance Check Point
     * text) to an Employee via the Employee-Checkpoint Mapping master,
     * matching the raw text against a Checkpoint's own name/code by
     * prefix/contains (device exports embed the checkpoint name inside a
     * longer string, e.g. "SPP_Door1_Entrance Card Reader1" for checkpoint
     * "SPP") — scoped to the upload's branch, mirroring every other
     * branch-scoped master.
     */
    /**
     * @return array{0: ?Checkpoint, 1: ?Employee} — the matched Checkpoint
     * and the Employee mapped to it under this Person ID. The checkpoint is
     * returned too (not just the employee) so callers can dedup/group
     * punches per (employee, checkpoint) — the same employee can have
     * different door-local IDs at different checkpoints, so two genuinely
     * separate checkpoints on the same day must never collapse into one
     * "duplicate" just because they belong to the same person.
     */
    private function resolveEmployee(?string $personId, ?string $rawCheckpoint, int $branchId, \Illuminate\Support\Collection $checkpointsForBranch): array
    {
        if (! $personId || ! $rawCheckpoint) {
            return [null, null];
        }

        $rawCheckpointLower = strtolower(trim($rawCheckpoint));
        $checkpoint = null;
        foreach ($checkpointsForBranch as $cp) {
            $name = strtolower($cp->name);
            $code = strtolower($cp->code);
            if (str_starts_with($rawCheckpointLower, $name) || str_contains($rawCheckpointLower, $name)
                || str_starts_with($rawCheckpointLower, $code) || str_contains($rawCheckpointLower, $code)) {
                $checkpoint = $cp;
                break;
            }
        }

        if (! $checkpoint) {
            return [null, null];
        }

        $mapping = EmployeeCheckpoint::where('checkpoint_id', $checkpoint->id)
            ->where('emp_checkpoint_id', $personId)
            ->with('employee')
            ->first();

        return [$checkpoint, $mapping?->employee];
    }

    /**
     * Reads and validates every data row against Employee-Checkpoint
     * Mapping, without writing anything — shared by the read-only preview()
     * (Confirm Upload screen) and validateAndImport() (the real import), so
     * the preview a user sees is guaranteed to match what actually gets
     * imported a moment later.
     *
     * @return array{0: array<int,array>, 1: array<int,array>, 2: array<string,int>} [valid rows (post-dedup), invalid rows, counts so far (pre-import-specific duplicate check)]
     */
    private function parseAndValidate(BiometricUpload $upload): array
    {
        $absolutePath = Storage::path($upload->file_path);
        $reader = IOFactory::createReaderForFile($absolutePath);
        if ($upload->sheet_name) {
            $reader->setLoadSheetsOnly([$upload->sheet_name]);
        }
        $spreadsheet = $reader->load($absolutePath);
        $sheet = $upload->sheet_name ? $spreadsheet->getSheetByName($upload->sheet_name) : $spreadsheet->getActiveSheet();
        $rows = $sheet->rangeToArray($sheet->calculateWorksheetDimension(), null, false, false, true);

        $columns = $this->resolveColumns($absolutePath, $upload->sheet_name);

        // First row is the header — data starts at the second.
        array_shift($rows);

        $counts = [
            'total_rows' => 0, 'invalid_rows' => 0, 'duplicate_rows' => 0, 'updated_rows' => 0,
            'unknown_employee_rows' => 0, 'invalid_date_rows' => 0, 'invalid_time_rows' => 0,
        ];
        $errors = [];
        $parsed = [];

        $checkpointsForBranch = Checkpoint::where('branch_id', $upload->branch_id)->active()->get();

        foreach ($rows as $rowNumber => $row) {
            if (empty(array_filter($row, fn ($v) => $v !== null && $v !== ''))) {
                continue; // fully blank row — not counted, not an error
            }
            $counts['total_rows']++;
            $rowErrors = [];

            $personId = $this->cleanPersonId($columns['person_id'] ? (string) ($row[$columns['person_id']] ?? '') : null);
            $rawCheckpoint = $columns['checkpoint'] ? trim((string) ($row[$columns['checkpoint']] ?? '')) : null;
            $department = $columns['department'] ? trim((string) ($row[$columns['department']] ?? '')) : null;
            $name = $columns['name'] ? trim((string) ($row[$columns['name']] ?? '')) : null;

            if (! $personId) {
                $rowErrors[] = 'Missing Person ID';
            }
            if (! $rawCheckpoint) {
                $rowErrors[] = 'Missing Attendance Check Point';
            }

            $punchDateTime = null;
            $rawTime = $columns['time'] ? ($row[$columns['time']] ?? null) : null;
            try {
                $punchDateTime = $this->parseExcelDateTime($rawTime);
                if (! $punchDateTime) {
                    throw new \InvalidArgumentException();
                }
            } catch (\Throwable) {
                $rowErrors[] = 'Invalid or missing Time';
                $counts['invalid_time_rows']++;
            }

            $employee = null;
            $checkpoint = null;
            if ($personId && $rawCheckpoint) {
                [$checkpoint, $employee] = $this->resolveEmployee($personId, $rawCheckpoint, $upload->branch_id, $checkpointsForBranch);
                if (! $employee) {
                    $rowErrors[] = 'Unknown employee (no Employee-Checkpoint Mapping found for this Person ID + Attendance Check Point)';
                    $counts['unknown_employee_rows']++;
                }
            }

            // Joined-after / separated-before — FSD 11.3, applied here too so
            // punches for a not-yet-valid employment window are never queued.
            if ($employee && $punchDateTime) {
                if ($employee->date_of_joining && $punchDateTime->lt($employee->date_of_joining)) {
                    $rowErrors[] = 'Punch date is before the employee\'s date of joining';
                }
                $exit = EmployeeExit::where('employee_id', $employee->id)->first();
                if ($exit && $exit->last_working_date && $punchDateTime->gt($exit->last_working_date)) {
                    $rowErrors[] = 'Punch date is after the employee\'s last working date';
                }
            }

            if (! empty($rowErrors)) {
                $counts['invalid_rows']++;
                $errors[] = [
                    'row' => $rowNumber + 2, // +1 for header, +1 for 1-indexing
                    'person_id' => $personId,
                    'name' => $name,
                    'checkpoint' => $rawCheckpoint,
                    'time' => is_string($rawTime) ? $rawTime : (string) $rawTime,
                    'errors' => implode('; ', $rowErrors),
                ];
                continue;
            }

            $parsed[] = [
                'row' => $rowNumber + 2,
                'employee' => $employee,
                'checkpoint' => $checkpoint,
                'person_id' => $personId,
                'name' => $name,
                'department' => $department,
                'punch_time' => $punchDateTime,
                'raw' => $row,
            ];
        }

        // Validation rule 2 — 3-minute duplicate window, per employee PER
        // CHECKPOINT. The same employee can be mapped to more than one
        // checkpoint (different door-local IDs) — two punches at different
        // checkpoints around the same time are two real, separate events,
        // not a duplicate biometric read, so the window must never span
        // checkpoints. Grouped in-memory since the sheet isn't guaranteed to
        // be time-ordered.
        $byEmployee = collect($parsed)->groupBy(fn ($p) => $p['employee']->id . ':' . $p['checkpoint']->id);
        $kept = [];
        foreach ($byEmployee as $employeeRows) {
            $sorted = $employeeRows->sortBy('punch_time')->values();
            $lastKeptTime = null;
            foreach ($sorted as $p) {
                // diffInMinutes() is signed in this Carbon version (negative
                // when the argument is later than the receiver, per the same
                // gotcha documented on Contractor::isLicenseExpiringSoon())
                // — abs() here, or every later punch would wrongly count as
                // "within the window" no matter how far apart it actually is.
                if ($lastKeptTime && abs($p['punch_time']->diffInMinutes($lastKeptTime)) < self::DUPLICATE_WINDOW_MINUTES) {
                    $counts['duplicate_rows']++;
                    $p['duplicate_reason'] = 'Within 3 minutes of another punch by the same employee';
                    continue; // within window of the last kept punch — same biometric read, ignored
                }
                $kept[] = $p;
                $lastKeptTime = $p['punch_time'];
            }
        }

        return [$kept, $errors, $counts];
    }

    /**
     * Read-only preview for the Confirm Upload screen — parses and
     * validates every row exactly like the real import, but never writes to
     * the database. A row whose exact (employee, checkpoint, punch_time)
     * already exists is shown as "will be updated," not as a duplicate/error
     * — re-uploading a file (or one that overlaps a previous upload) is
     * expected to refresh already-stored punches, not skip them.
     *
     * @return array{valid: array<int,array>, errors: array<int,array>, counts: array<string,int>, truncated: bool}
     */
    public function preview(BiometricUpload $upload): array
    {
        [$kept, $errors, $counts] = $this->parseAndValidate($upload);

        $valid = [];
        foreach ($kept as $p) {
            $existing = AttendanceLog::where('employee_id', $p['employee']->id)
                ->where('punch_time', $p['punch_time'])
                ->where('device_id', $p['checkpoint']->code)
                ->first();
            if ($existing) {
                $counts['updated_rows']++;
                $p['will_update'] = true;
            }
            $valid[] = $p;
        }

        $counts['valid_rows'] = count($valid);

        return [
            'valid' => array_slice($valid, 0, self::PREVIEW_LIMIT),
            'errors' => array_slice($errors, 0, self::PREVIEW_LIMIT),
            'counts' => $counts,
            'truncated' => count($valid) > self::PREVIEW_LIMIT || count($errors) > self::PREVIEW_LIMIT,
        ];
    }

    /**
     * Validates every data row and inserts valid punches into
     * `attendance_logs`. Never silently drops invalid rows — every problem
     * row is collected with its own message and downloadable afterward.
     *
     * @return array{0: array<string,int>, 1: array<int,array>} [summary counts, error rows]
     */
    public function validateAndImport(BiometricUpload $upload): array
    {
        [$kept, $errors, $counts] = $this->parseAndValidate($upload);
        $counts['valid_rows'] = 0;

        foreach ($kept as $p) {
            // Validation rule 6 — re-upload handling: exact (employee,
            // checkpoint, punch_time) already recorded is updated in place
            // (not skipped/flagged as a duplicate) — re-uploading a file is
            // expected to refresh already-stored punches. Checkpoint is part
            // of the match key since the same employee can legitimately
            // punch at more than one checkpoint.
            $log = AttendanceLog::updateOrCreate(
                [
                    'employee_id' => $p['employee']->id,
                    'punch_time' => $p['punch_time'],
                    'device_id' => $p['checkpoint']->code,
                ],
                [
                    'employee_code' => $p['employee']->employee_code,
                    'punch_type' => 'unknown', // device export doesn't distinguish in/out — process() derives first/last punch per day
                    'source' => 'biometric',
                    'is_processed' => false,
                    'biometric_upload_id' => $upload->id,
                    'raw_data' => $p['raw'],
                ]
            );
            if ($log->wasRecentlyCreated) {
                $counts['valid_rows']++;
            } else {
                $counts['updated_rows']++;
            }
        }

        return [$counts, $errors];
    }

    public function generateErrorFile(BiometricUpload $upload, array $errors): ?string
    {
        if (empty($errors)) {
            return null;
        }

        $path = 'biometric-uploads/' . $upload->id . '/errors.csv';
        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['Row', 'Person ID', 'Attendance Check Point', 'Time', 'Errors']);
        foreach ($errors as $e) {
            fputcsv($handle, [$e['row'], $e['person_id'], $e['checkpoint'], $e['time'], $e['errors']]);
        }
        rewind($handle);
        Storage::put($path, stream_get_contents($handle));
        fclose($handle);

        return $path;
    }

    /**
     * The device's single "Time" column carries both date and time (e.g.
     * "01-06-2026 10:29") — unlike the old format's separate Punch
     * Date/Punch Time columns.
     */
    private function parseExcelDateTime($raw): ?Carbon
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($raw));
        }

        return Carbon::createFromFormat('d-m-Y H:i', trim((string) $raw))
            ?: Carbon::parse((string) $raw);
    }
}
