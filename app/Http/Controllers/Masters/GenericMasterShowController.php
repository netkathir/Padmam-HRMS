<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One shared, dynamic "View" screen for every Masters module — renders
 * whatever a module's model reports back from masterViewFields() as a plain
 * label/value list, so no module needs its own show.blade.php. Add a new
 * master to MODELS below (and give its model a masterViewFields() method,
 * or rely on the fillable-column fallback) rather than building a per-module
 * show page.
 */
class GenericMasterShowController extends Controller
{
    /** module-slug (matches the {module} route segment) => [Eloquent class, permission-module key]. */
    private const MODELS = [
        'branches'      => [\App\Models\Branch::class, 'masters_branches'],
        'departments'   => [\App\Models\Department::class, 'masters_departments'],
        'designations'  => [\App\Models\Designation::class, 'masters_designations'],
        'employee-types'=> [\App\Models\EmployeeType::class, 'masters_employee_types'],
        'shifts'        => [\App\Models\Shift::class, 'masters_shifts'],
        'holidays'      => [\App\Models\Holiday::class, 'masters_holidays'],
        'leave-types'   => [\App\Models\LeaveType::class, 'masters_leave_types'],
        'salary-slabs'  => [\App\Models\SalarySlab::class, 'masters_salary_slabs'],
        'earnings'      => [\App\Models\EarningsComponent::class, 'masters_earnings'],
        'deductions'    => [\App\Models\DeductionsComponent::class, 'masters_deductions'],
        'ot-rates'      => [\App\Models\OtRate::class, 'masters_ot_rates'],
        'pf-esi'        => [\App\Models\PfEsiConfig::class, 'masters_pf_esi'],
        'contractors'   => [\App\Models\Contractor::class, 'masters_contractors'],
        'banks'         => [\App\Models\Bank::class, 'masters_banks'],
        'checkpoints'          => [\App\Models\Checkpoint::class, 'masters_checkpoints'],
        'employee-checkpoints' => [\App\Models\EmployeeCheckpoint::class, 'masters_employee_checkpoints'],
    ];

    public function show(string $module, int $id)
    {
        abort_unless(array_key_exists($module, self::MODELS), 404);
        [$modelClass, $permissionModule] = self::MODELS[$module];

        abort_unless(auth()->user()->can($permissionModule . '.read'), 403);

        $record = $modelClass::findOrFail($id);
        $title = Str::headline($module);
        $fields = method_exists($record, 'masterViewFields')
            ? $record->masterViewFields()
            : $this->fallbackFields($record);

        $editRoute = "masters.{$module}.edit";
        $indexRoute = "masters.{$module}.index";

        return view('masters._show', [
            'title' => $title,
            'fields' => $fields,
            'record' => $record,
            'editRoute' => \Illuminate\Support\Facades\Route::has($editRoute) ? route($editRoute, $record) : null,
            'indexRoute' => route($indexRoute),
        ]);
    }

    /** Fallback when a model has no masterViewFields(): every fillable column, humanized, raw value. */
    private function fallbackFields(Model $record): array
    {
        $fields = [];
        foreach ($record->getFillable() as $column) {
            $value = $record->{$column};
            if (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            } elseif (is_array($value)) {
                $value = implode(', ', $value);
            } elseif ($value instanceof \Illuminate\Support\Carbon) {
                $value = $value->format('d-M-Y');
            }
            $fields[Str::headline($column)] = $value ?? '—';
        }

        return $fields;
    }
}
