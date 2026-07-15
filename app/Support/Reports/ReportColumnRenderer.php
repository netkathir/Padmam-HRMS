<?php

namespace App\Support\Reports;

/**
 * Renders one cell for one report row — shared by the on-screen table, the
 * CSV export, and the PDF export, so masking/formatting can never diverge
 * between "view" and "export" (a low-privilege user can't bypass masking by
 * exporting instead of viewing on-screen).
 */
class ReportColumnRenderer
{
    public static function render($record, array $column, bool $canViewSensitive): string
    {
        $value = data_get($record, $column['path']);

        // A dot-path landing on a many-to-many/hasMany relation resolves to
        // a Collection, not a scalar — render it as a comma-joined list of
        // `name` rather than failing to cast to string.
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->pluck('name')->filter()->implode(', ');
        }

        if (! empty($column['sensitive']) && ! $canViewSensitive) {
            return ReportMasking::mask((string) $value);
        }

        return match ($column['format'] ?? null) {
            'date'     => $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : '',
            'datetime' => $value ? \Carbon\Carbon::parse($value)->format('d-m-Y H:i') : '',
            'currency' => $value !== null && $value !== '' ? number_format((float) $value, 2) : '',
            'number'   => $value !== null && $value !== '' ? number_format((float) $value, 2) : '',
            'boolean'  => $value ? 'Yes' : 'No',
            default    => (string) ($value ?? ''),
        };
    }
}
