<?php
// Purpose: Employee-Checkpoint Mapping — emp_checkpoint_id is now enforced
// numeric-only going forward (the checkpoint's own code is a fixed UI
// prefix, never part of the stored value). Existing rows created before
// this rule was added may have the checkpoint's code baked into the
// string (e.g. "SPI500" instead of "500") — this silently broke the
// per-checkpoint uniqueness check, since "SPI500" and "500" for the same
// checkpoint+number were treated as two different strings. This migration
// strips each row's own checkpoint code/name prefix (case-insensitive) so
// every row is a bare number, consistent with new rows going forward.
// Table affected: employee_checkpoints (data fix only, no schema change).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('employee_checkpoints')
            ->join('checkpoints', 'checkpoints.id', '=', 'employee_checkpoints.checkpoint_id')
            ->select('employee_checkpoints.id', 'employee_checkpoints.emp_checkpoint_id', 'checkpoints.code', 'checkpoints.name')
            ->get();

        foreach ($rows as $row) {
            $value = $row->emp_checkpoint_id;
            $stripped = $value;

            foreach (array_filter([$row->code, $row->name]) as $prefix) {
                if (stripos($value, $prefix) === 0) {
                    $stripped = ltrim(substr($value, strlen($prefix)), '_- ');
                    break;
                }
            }

            // Only touch it if it's now a pure digit string and actually
            // changed — never overwrite a row with something that doesn't
            // look like a valid bare number (safer to leave a genuinely
            // unusual legacy value untouched than to guess wrong).
            if ($stripped !== $value && ctype_digit($stripped) && $stripped !== '') {
                DB::table('employee_checkpoints')->where('id', $row->id)->update(['emp_checkpoint_id' => $stripped]);
            }
        }
    }

    public function down(): void
    {
        // Data fix — not reversible (the original prefixed strings aren't recoverable).
    }
};
