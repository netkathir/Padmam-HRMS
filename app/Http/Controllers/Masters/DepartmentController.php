<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Branch;
use App\Support\BranchScope;
use App\Support\SequentialCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = BranchScope::scopeQuery(Department::with('branch'))->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }
        // A branch is already forced above (BranchScope::scopeQuery) for a
        // Super Admin (currently selected branch) or a branch-scoped actor
        // (their own branch) — this ad-hoc filter only still applies for
        // unscoped legacy accounts, avoiding a redundant/conflicting AND.
        if (BranchScope::currentBranchId() === null && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $departments = $query->paginate(20)->withQueryString();
        $branches    = BranchScope::currentBranchId() === null ? Branch::orderBy('name')->get() : collect();
        return view('masters.departments.index', compact('departments', 'branches'));
    }

    public function create()
    {
        $currentBranch = BranchScope::currentBranch();
        $nextDepartmentCode = $this->nextDepartmentCode();
        return view('masters.departments.create', compact('currentBranch', 'nextDepartmentCode'));
    }

    public function store(Request $request)
    {
        $branchId = BranchScope::currentBranchId() ?? $request->input('branch_id');

        $data = $request->validate([
            'branch_id'   => ['required', 'exists:branches,id'],
            'name'        => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->where('branch_id', $branchId)],
            'code'        => ['required', 'string', 'max:20', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['required', 'boolean'],
        ]);

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);
        BranchScope::assertBranchIsActive($data['branch_id']);

        $this->createWithRaceSafeCode($data);

        return redirect()->route('masters.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * The Department Code is auto-suggested on the Create page (see
     * nextDepartmentCode()) but stays a normal editable field — the admin
     * can accept the suggestion or type their own. Since two people could
     * load the Create page at the same moment and both see the same
     * suggested code, a retry-on-duplicate-key loop (mirroring
     * ContractorController::createWithGeneratedCode()) still protects the
     * unique constraint if the admin didn't change the pre-filled value.
     */
    private function createWithRaceSafeCode(array $data): Department
    {
        // See ShiftController::createWithGeneratedCode() for why this needs
        // more than a couple of retries plus a jittered backoff.
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            try {
                return DB::transaction(fn () => Department::create($data));
            } catch (QueryException $e) {
                $isDuplicate = (string) $e->getCode() === '23000';
                if (! $isDuplicate || $attempt === 10) {
                    throw $e;
                }
                $data['code'] = $this->nextDepartmentCode();
                usleep(random_int(20_000, 80_000));
            }
        }

        throw new \RuntimeException('Unable to generate a unique Department Code after several attempts.');
    }

    /**
     * Next sequential Department Code — "DEP" + zero-padded number, one
     * higher than the latest existing code already following this
     * convention (legacy codes in other formats, e.g. from before this
     * feature existed, are ignored so they can't produce a nonsensical
     * suggestion like incrementing a plain numeric legacy code).
     */
    private function nextDepartmentCode(): string
    {
        // withTrashed() is load-bearing: a soft-deleted Department's code is
        // still permanently reserved by the database's unique index, so
        // excluding deleted rows here can suggest a code that's already
        // taken (see ShiftController::createWithGeneratedCode() for the
        // full explanation of this failure mode).
        $lastCode = Department::withTrashed()
            ->where('code', 'REGEXP', '^DEP[0-9]+$')
            ->orderByRaw('CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC')
            ->value('code');

        return SequentialCodeGenerator::next($lastCode, 'DEP001');
    }

    public function edit(Department $department)
    {
        BranchScope::assertBranchAccess($department->branch_id);
        $currentBranch = $department->branch;
        return view('masters.departments.edit', compact('department', 'currentBranch'));
    }

    public function update(Request $request, Department $department)
    {
        BranchScope::assertBranchAccess($department->branch_id);

        $branchId = BranchScope::currentBranchId() ?? $request->input('branch_id');

        $data = $request->validate([
            'branch_id'   => ['required', 'exists:branches,id'],
            'name'        => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->where('branch_id', $branchId)->ignore($department->id)],
            'code'        => ['required', 'string', 'max:20', 'unique:departments,code,' . $department->id],
            'description' => ['nullable', 'string'],
            'is_active'   => ['required', 'boolean'],
        ]);

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);

        $department->update($data);

        return redirect()->route('masters.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        BranchScope::assertBranchAccess($department->branch_id);

        if ($department->designations()->exists()) {
            return back()->with('error', 'Cannot delete department with associated designations.');
        }
        if ($department->employees()->exists()) {
            return back()->with('error', 'Cannot delete department with associated employees.');
        }
        $department->delete();
        return redirect()->route('masters.departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
