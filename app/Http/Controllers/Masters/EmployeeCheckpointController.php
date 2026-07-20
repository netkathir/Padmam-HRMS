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
            'checkpoint_id'     => ['required', 'exists:checkpoints,id'],
            'employee_id'       => ['required', 'exists:employees,id'],
            'emp_checkpoint_id' => [
                'required', 'string', 'max:50',
                Rule::unique('employee_checkpoints', 'emp_checkpoint_id')
                    ->where(fn ($q) => $q->where('checkpoint_id', request('checkpoint_id'))->where('employee_id', request('employee_id')))
                    ->ignore($ignoreId),
            ],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
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

        $data = $request->validate($this->rules($employeeCheckpoint->id));
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
