<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\BiometricUpload;
use App\Models\Employee;
use App\Models\EmployeeExit;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Module 7 FSD 11.2 — biometric Excel upload: sheet/column discovery and
 * row-by-row validation + import into `attendance_logs`. Kept as a
 * dedicated service (mirroring EmployeeNumberGenerator's precedent) since
 * Excel parsing/validation is a self-contained unit of logic distinct from
 * AttendanceController's request/response handling.
 */
class BiometricUploadService
{
    public const EXPECTED_FIELDS = [
        'employee_number' => 'Employee Number',
        'biometric_id'    => 'Biometric ID',
        'employee_name'   => 'Employee Name',
        'punch_date'      => 'Punch Date',
        'punch_time'      => 'Punch Time',
        'punch_type'      => 'Punch Type',
        'device_id'       => 'Device ID',
        'location'        => 'Location',
        'shift_code'      => 'Shift Code',
    ];

    public function sheetNames(string $absolutePath): array
    {
        return IOFactory::createReaderForFile($absolutePath)->listWorksheetNames($absolutePath);
    }

    /** First row of the given sheet, as [columnLetter => headerText]. */
    public function headerRow(string $absolutePath, ?string $sheetName): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        if ($sheetName) {
            $reader->setLoadSheetsOnly([$sheetName]);
        }
        $spreadsheet = $reader->load($absolutePath);
        $sheet = $sheetName ? $spreadsheet->getSheetByName($sheetName) : $spreadsheet->getActiveSheet();
        $row = $sheet->rangeToArray($sheet->calculateWorksheetDimension(), null, false, false)[0] ?? [];

        $headers = [];
        foreach ($row as $i => $value) {
            if ($value !== null && $value !== '') {
                $headers[\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1)] = trim((string) $value);
            }
        }
        return $headers;
    }

    /**
     * Best-guess column mapping: for each expected field, find the header
     * column whose text matches (case-insensitively) the configured default
     * label. Returns [fieldKey => columnLetter|null].
     */
    public function guessMapping(array $headerRow, array $defaultLabels): array
    {
        $mapping = [];
        foreach (self::EXPECTED_FIELDS as $field => $fallbackLabel) {
            $label = strtolower($defaultLabels[$field] ?? $fallbackLabel);
            $mapping[$field] = null;
            foreach ($headerRow as $col => $text) {
                if (strtolower($text) === $label) {
                    $mapping[$field] = $col;
                    break;
                }
            }
        }
        return $mapping;
    }

    /**
     * Validates every data row against the confirmed mapping and inserts
     * valid punches into `attendance_logs`. Never silently drops invalid
     * rows — every problem row is collected with its own message.
     *
     * @return array{0: array<string,int>, 1: array<int,array>} [summary counts, error rows]
     */
    public function validateAndImport(BiometricUpload $upload, array $mapping): array
    {
        $absolutePath = Storage::path($upload->file_path);
        $reader = IOFactory::createReaderForFile($absolutePath);
        if ($upload->sheet_name) {
            $reader->setLoadSheetsOnly([$upload->sheet_name]);
        }
        $spreadsheet = $reader->load($absolutePath);
        $sheet = $upload->sheet_name ? $spreadsheet->getSheetByName($upload->sheet_name) : $spreadsheet->getActiveSheet();
        $rows = $sheet->rangeToArray($sheet->calculateWorksheetDimension(), null, false, false, true);

        // First row is the header — data starts at the second.
        array_shift($rows);

        $counts = [
            'total_rows' => 0, 'valid_rows' => 0, 'invalid_rows' => 0, 'duplicate_rows' => 0,
            'unknown_employee_rows' => 0, 'invalid_date_rows' => 0, 'invalid_time_rows' => 0,
        ];
        $errors = [];

        // Cache employees by code/biometric_id for the upload's branch to avoid N+1 lookups.
        $employees = Employee::where('branch_id', $upload->branch_id)->get()->keyBy('employee_code');
        $employeesByBiometric = $employees->keyBy('biometric_id');
        $shiftsByCode = Shift::whereNotNull('code')->get()->keyBy('code');

        foreach ($rows as $rowNumber => $row) {
            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                continue; // fully blank row — not counted, not an error
            }
            $counts['total_rows']++;
            $rowErrors = [];

            $empNumber = $mapping['employee_number'] ? trim((string) ($row[$mapping['employee_number']] ?? '')) : null;
            $biometricId = $mapping['biometric_id'] ? trim((string) ($row[$mapping['biometric_id']] ?? '')) : null;

            $employee = null;
            if ($empNumber && $employees->has($empNumber)) {
                $employee = $employees->get($empNumber);
            } elseif ($biometricId && $employeesByBiometric->has($biometricId)) {
                $employee = $employeesByBiometric->get($biometricId);
            }

            if (! $employee) {
                $rowErrors[] = 'Unknown employee (no matching Employee Number/Biometric ID in this branch)';
                $counts['unknown_employee_rows']++;
            }

            $punchDate = null;
            $rawDate = $mapping['punch_date'] ? ($row[$mapping['punch_date']] ?? null) : null;
            try {
                $punchDate = $this->parseExcelDate($rawDate);
                if (! $punchDate) {
                    throw new \InvalidArgumentException();
                }
            } catch (\Throwable) {
                $rowErrors[] = 'Invalid or missing Punch Date';
                $counts['invalid_date_rows']++;
            }

            $punchTime = null;
            $rawTime = $mapping['punch_time'] ? ($row[$mapping['punch_time']] ?? null) : null;
            try {
                $punchTime = $this->parseExcelTime($rawTime);
                if ($punchTime === null) {
                    throw new \InvalidArgumentException();
                }
            } catch (\Throwable) {
                $rowErrors[] = 'Invalid or missing Punch Time';
                $counts['invalid_time_rows']++;
            }

            $punchDateTime = ($punchDate && $punchTime !== null)
                ? $punchDate->copy()->setTimeFromTimeString($punchTime)
                : null;

            // Joined-after / separated-before — FSD 11.3, applied here too so
            // punches for a not-yet-valid employment window are never queued.
            if ($employee && $punchDate) {
                if ($employee->date_of_joining && $punchDate->lt($employee->date_of_joining)) {
                    $rowErrors[] = 'Punch date is before the employee\'s date of joining';
                }
                $exit = EmployeeExit::where('employee_id', $employee->id)->first();
                if ($exit && $exit->last_working_date && $punchDate->gt($exit->last_working_date)) {
                    $rowErrors[] = 'Punch date is after the employee\'s last working date';
                }
            }

            if ($employee && $punchDateTime && empty($rowErrors)) {
                $isDuplicate = AttendanceLog::where('employee_id', $employee->id)
                    ->where('punch_time', $punchDateTime)
                    ->exists();
                if ($isDuplicate) {
                    $rowErrors[] = 'Duplicate punch (same employee, date, and time already uploaded)';
                    $counts['duplicate_rows']++;
                }
            }

            if (! empty($rowErrors)) {
                $counts['invalid_rows']++;
                $errors[] = [
                    'row' => $rowNumber + 2, // +1 for header, +1 for 1-indexing
                    'employee_number' => $empNumber, 'biometric_id' => $biometricId,
                    'punch_date' => is_string($rawDate) ? $rawDate : (string) $rawDate,
                    'punch_time' => is_string($rawTime) ? $rawTime : (string) $rawTime,
                    'errors' => implode('; ', $rowErrors),
                ];
                continue;
            }

            $punchTypeRaw = $mapping['punch_type'] ? strtolower(trim((string) ($row[$mapping['punch_type']] ?? ''))) : '';
            $punchType = match (true) {
                str_starts_with($punchTypeRaw, 'in') => 'in',
                str_starts_with($punchTypeRaw, 'out') => 'out',
                default => 'unknown',
            };
            $shiftCode = $mapping['shift_code'] ? trim((string) ($row[$mapping['shift_code']] ?? '')) : null;

            AttendanceLog::create([
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'device_id' => $mapping['device_id'] ? trim((string) ($row[$mapping['device_id']] ?? '')) ?: null : null,
                'punch_time' => $punchDateTime,
                'punch_type' => $punchType,
                'source' => 'biometric',
                'is_processed' => false,
                'biometric_upload_id' => $upload->id,
                'raw_data' => array_merge($row, [
                    '_shift_code' => $shiftCode,
                    '_shift_id' => $shiftCode ? $shiftsByCode->get($shiftCode)?->id : null,
                ]),
            ]);
            $counts['valid_rows']++;
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
        fputcsv($handle, ['Row', 'Employee Number', 'Biometric ID', 'Punch Date', 'Punch Time', 'Errors']);
        foreach ($errors as $e) {
            fputcsv($handle, [$e['row'], $e['employee_number'], $e['biometric_id'], $e['punch_date'], $e['punch_time'], $e['errors']]);
        }
        rewind($handle);
        Storage::put($path, stream_get_contents($handle));
        fclose($handle);

        return $path;
    }

    private function parseExcelDate($raw): ?Carbon
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($raw))->startOfDay();
        }
        return Carbon::parse((string) $raw)->startOfDay();
    }

    private function parseExcelTime($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($raw))->format('H:i:s');
        }
        return Carbon::parse((string) $raw)->format('H:i:s');
    }
}
