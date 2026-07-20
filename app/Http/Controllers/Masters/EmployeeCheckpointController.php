<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Checkpoint;
use App\Models\Employee;
use App\Models\EmployeeCheckpoint;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Employee-Checkpoint Mapping — records which door-local ID an employee is
 * registered under at a given Checkpoint. An employee can have a different
 * ID per checkpoint (or the same one); combination of checkpoint + ID +
 * employee must be unique. These mappings are what the biometric bulk
 * upload resolves "Person ID" + "Attendance Check Point" against, instead
 * of relying on Employee's own single biometric_id column.
 *
 * Branch scoping follows the CHECKPOINT's own branch_id (a Checkpoint
 * belongs to one branch, same as Department/Designation) — not the mapped
 * employee's branch. In practice these always match (a checkpoint's
 * dropdown here only ever offers checkpoints already scoped to the
 * employee's own branch), but the checkpoint is the authoritative side.
 */
class EmployeeCheckpointController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeCheckpoint::with(['checkpoint', 'employee']);
        $query = BranchScope::scopeQueryVia($query, 'checkpoint');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn ($q) => $q->where('emp_checkpoint_id', 'like', $s)
                ->orWhereHas('employee', fn ($eq) => $eq->where('first_name', 'like', $s)
                    ->orWhere('last_name', 'like', $s)
                    ->orWhere('employee_code', 'like', $s)));
        }

        if ($request->filled('checkpoint_id')) {
            $query->where('checkpoint_id', $request->checkpoint_id);
        }

        $mappings = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();
        $checkpoints = BranchScope::scopeQuery(Checkpoint::active())->orderBy('name')->get();

        return view('masters.employee-checkpoints.index', compact('mappings', 'checkpoints'));
    }

    public function create()
    {
        $checkpoints = BranchScope::scopeQuery(Checkpoint::active())->orderBy('name')->get();
        $employees = BranchScope::scopeQuery(Employee::query())->orderBy('first_name')->get();

        return view('masters.employee-checkpoints.create', compact('checkpoints', 'employees'));
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            // One mapping per (checkpoint, employee) — an employee cannot be
            // registered twice under the same checkpoint, even with a
            // different Employee Checkpoint ID.
            'checkpoint_id'     => [
                'required', 'exists:checkpoints,id',
                Rule::unique('employee_checkpoints', 'checkpoint_id')
                    ->where(fn ($q) => $q->where('employee_id', request('employee_id')))
                    ->ignore($ignoreId),
            ],
            'employee_id'       => ['required', 'exists:employees,id'],
            // Numbers only — the checkpoint's own code is a fixed, separate
            // prefix shown in the UI, never typed or stored as part of this
            // value. This is also what makes the uniqueness check below
            // reliable: mixing "SPI500" and "500" for the same
            // checkpoint+number used to slip past it as two different
            // strings before this was enforced. A given door-local ID is
            // unique WITHIN a checkpoint — the biometric device can't tell
            // two employees apart if they're both registered as, say, "500"
            // at the same checkpoint. Different checkpoints may reuse the
            // same number freely (SPP 001 and SGI 001 are different people).
            'emp_checkpoint_id' => [
                'required', 'digits_between:1,20',
                Rule::unique('employee_checkpoints', 'emp_checkpoint_id')
                    ->where(fn ($q) => $q->where('checkpoint_id', request('checkpoint_id')))
                    ->ignore($ignoreId),
            ],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), [
            'checkpoint_id.unique' => 'This employee is already mapped to the selected checkpoint.',
            'emp_checkpoint_id.unique' => 'This Employee Checkpoint ID is already used by another employee at the selected checkpoint.',
            'emp_checkpoint_id.digits_between' => 'Employee Checkpoint ID must contain numbers only.',
        ]);
        $this->assertCheckpointBranchAccess($data['checkpoint_id']);

        EmployeeCheckpoint::create($data);

        return redirect()->route('masters.employee-checkpoints.index')
            ->with('success', 'Employee checkpoint mapping created successfully.');
    }

    public function edit(EmployeeCheckpoint $employeeCheckpoint)
    {
        $this->assertCheckpointBranchAccess($employeeCheckpoint->checkpoint_id);

        $checkpoints = BranchScope::scopeQuery(Checkpoint::active())->orderBy('name')->get();
        $employees = BranchScope::scopeQuery(Employee::query())->orderBy('first_name')->get();

        return view('masters.employee-checkpoints.edit', compact('employeeCheckpoint', 'checkpoints', 'employees'));
    }

    public function update(Request $request, EmployeeCheckpoint $employeeCheckpoint)
    {
        $this->assertCheckpointBranchAccess($employeeCheckpoint->checkpoint_id);

        $data = $request->validate($this->rules($employeeCheckpoint->id), [
            'checkpoint_id.unique' => 'This employee is already mapped to the selected checkpoint.',
            'emp_checkpoint_id.unique' => 'This Employee Checkpoint ID is already used by another employee at the selected checkpoint.',
            'emp_checkpoint_id.digits_between' => 'Employee Checkpoint ID must contain numbers only.',
        ]);
        $this->assertCheckpointBranchAccess($data['checkpoint_id']);

        $employeeCheckpoint->update($data);

        return redirect()->route('masters.employee-checkpoints.index')
            ->with('success', 'Employee checkpoint mapping updated successfully.');
    }

    public function destroy(EmployeeCheckpoint $employeeCheckpoint)
    {
        $this->assertCheckpointBranchAccess($employeeCheckpoint->checkpoint_id);

        $employeeCheckpoint->delete();

        return redirect()->route('masters.employee-checkpoints.index')
            ->with('success', 'Employee checkpoint mapping removed successfully.');
    }

    private function assertCheckpointBranchAccess(int $checkpointId): void
    {
        $checkpoint = Checkpoint::findOrFail($checkpointId);
        BranchScope::assertBranchAccess($checkpoint->branch_id);
    }
}
