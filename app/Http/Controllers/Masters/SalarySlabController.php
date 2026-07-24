<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\EarningsComponent;
use App\Models\SalarySlab;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalarySlabController extends Controller
{
    public function index(Request $request)
    {
        $query = BranchScope::scopeQuery(SalarySlab::with('branch'))->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where('name', 'like', $s);
        }
        if (BranchScope::currentBranchId() === null && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $slabs    = $query->paginate(20)->withQueryString();
        $branches = BranchScope::currentBranchId() === null ? Branch::orderBy('name')->get() : collect();
        return view('masters.salary-slabs.index', compact('slabs', 'branches'));
    }

    public function create()
    {
        $earningsComponents = BranchScope::scopeQuery(EarningsComponent::where('is_active', true))->orderBy('sort_order')->get();
        $currentBranch = BranchScope::currentBranch();
        return view('masters.salary-slabs.create', compact('earningsComponents', 'currentBranch'));
    }

    private function rules(?int $salarySlabId = null): array
    {
        $branchId = BranchScope::currentBranchId() ?? request()->input('branch_id');

        return [
            'branch_id'    => ['required', 'exists:branches,id'],
            // whereNull('deleted_at') is load-bearing — Rule::unique() has no
            // built-in awareness of soft deletes, see ShiftController for why.
            'name'         => ['required', 'string', 'max:100', Rule::unique('salary_slabs', 'name')->where('branch_id', $branchId)->whereNull('deleted_at')->ignore($salarySlabId)],
            'salary_from'  => ['required', 'numeric', 'min:0'],
            'salary_to'    => ['required', 'numeric', 'gte:salary_from'],
            'tds_percentage'           => ['required', 'numeric', 'between:0,100'],
            'pf_employee_percentage'   => ['required', 'numeric', 'between:0,100'],
            'pf_employer_percentage'   => ['required', 'numeric', 'between:0,100'],
            'esi_employee_percentage'  => ['required', 'numeric', 'between:0,100'],
            'esi_employer_percentage'  => ['required', 'numeric', 'between:0,100'],
            'lop_percentage'           => ['required', 'numeric', 'between:0,100'],
            'is_active'      => ['required', 'boolean'],
            'earnings'                  => ['nullable', 'array'],
            // A row only needs to validate once the user has actually picked an
            // earning OR typed a value for it — an untouched "+ Add Earning" row
            // (both fields still blank) must be silently ignored, not treated as
            // an incomplete required row.
            'earnings.*.component_id'   => ['nullable', 'required_with:earnings.*.value', 'exists:earnings_components,id'],
            // Earning values are a % of Basic Salary (not a fixed amount),
            // so they're capped the same way TDS/PF/ESI percentages are.
            'earnings.*.value'          => ['nullable', 'required_with:earnings.*.component_id', 'numeric', 'min:0', 'max:100'],
        ];
    }

    private function messages(): array
    {
        return [
            'salary_to.gte' => 'The "Salary To" amount must be greater than or equal to the "Salary From" amount.',
            'earnings.*.component_id.exists' => 'Please select a valid earning from the list.',
            'earnings.*.component_id.required_with' => 'Please select an earning for this row, or remove it.',
            'earnings.*.value.required_with' => 'Please enter a value for this earning.',
            'earnings.*.value.numeric' => 'The earning value must be a number.',
            'earnings.*.value.min' => 'The earning value cannot be negative.',
            'earnings.*.value.max' => 'The earning value is a percentage and cannot exceed 100.',
        ];
    }

    /**
     * Ranges can only be added before or after existing ranges — never
     * inside/overlapping one — scoped per branch (the same range may exist
     * in two different branches, matching Contractor/Shift/etc.). Two
     * ranges [aFrom,aTo] and [bFrom,bTo] overlap iff aFrom <= bTo AND
     * aTo >= bFrom; ignores the slab being edited.
     */
    private function assertNoRangeOverlap(int $branchId, float $salaryFrom, float $salaryTo, ?int $ignoreId): void
    {
        $conflict = SalarySlab::where('branch_id', $branchId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('salary_from', '<=', $salaryTo)
            ->where('salary_to', '>=', $salaryFrom)
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'salary_from' => "This range overlaps with \"{$conflict->name}\" (" . number_format($conflict->salary_from) . ' – ' . number_format($conflict->salary_to) . '). Ranges can only be added before or after existing ranges, not overlapping.',
            ]);
        }
    }

    private function attributes(): array
    {
        return [
            'salary_from' => 'Salary From',
            'salary_to' => 'Salary To',
            'tds_percentage' => 'TDS %',
            'pf_employee_percentage' => 'PF Employee %',
            'pf_employer_percentage' => 'PF Employer %',
            'esi_employee_percentage' => 'ESI Employee %',
            'esi_employer_percentage' => 'ESI Employer %',
            'lop_percentage' => 'LOP %',
        ];
    }

    /** Replaces every earning line for this slab with the submitted set — simplest way to keep the slab's components in sync with the form. */
    private function syncEarnings(SalarySlab $salarySlab, array $earnings): void
    {
        $salarySlab->earningsComponents()->delete();

        foreach ($earnings as $row) {
            if (empty($row['component_id']) || ! isset($row['value'])) {
                continue;
            }
            $component = EarningsComponent::find($row['component_id']);
            if (! $component) {
                continue;
            }
            $salarySlab->components()->create([
                'component_type' => 'earning',
                'component_id' => $component->id,
                'component_name' => $component->name,
                'calculation_type' => $component->type,
                'rate' => $row['value'],
                'calculated_amount' => $row['value'],
            ]);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages(), $this->attributes());
        $earnings = $data['earnings'] ?? [];
        unset($data['earnings']);

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);
        BranchScope::assertBranchIsActive($data['branch_id']);
        $this->assertNoRangeOverlap($data['branch_id'], (float) $data['salary_from'], (float) $data['salary_to'], null);

        $salarySlab = SalarySlab::create($data);
        $this->syncEarnings($salarySlab, $earnings);

        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab created successfully.');
    }

    public function edit(SalarySlab $salarySlab)
    {
        BranchScope::assertBranchAccess($salarySlab->branch_id);
        $earningsComponents = BranchScope::scopeQuery(EarningsComponent::where('is_active', true))->orderBy('sort_order')->get();
        $salarySlab->load('earningsComponents');
        $currentBranch = $salarySlab->branch;
        return view('masters.salary-slabs.edit', compact('salarySlab', 'earningsComponents', 'currentBranch'));
    }

    public function update(Request $request, SalarySlab $salarySlab)
    {
        BranchScope::assertBranchAccess($salarySlab->branch_id);

        $data = $request->validate($this->rules($salarySlab->id), $this->messages(), $this->attributes());
        $earnings = $data['earnings'] ?? [];
        unset($data['earnings']);

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);
        $this->assertNoRangeOverlap($data['branch_id'], (float) $data['salary_from'], (float) $data['salary_to'], $salarySlab->id);

        $salarySlab->update($data);
        $this->syncEarnings($salarySlab, $earnings);

        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab updated successfully.');
    }

    public function destroy(SalarySlab $salarySlab)
    {
        BranchScope::assertBranchAccess($salarySlab->branch_id);

        if ($salarySlab->employees()->exists()) {
            return back()->with('error', 'Cannot delete salary slab assigned to employees.');
        }
        if ($salarySlab->salaryStructures()->exists()) {
            return back()->with('error', 'Cannot delete salary slab used in finalized salary structures.');
        }
        $salarySlab->delete();
        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab deleted successfully.');
    }
}
