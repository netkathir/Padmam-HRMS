<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\SalarySlab;
use App\Models\EarningsComponent;
use App\Models\DeductionsComponent;
use App\Models\Branch;
use Illuminate\Http\Request;

class SalarySlabController extends Controller
{
    private const EMPLOYEE_TYPES = ['staff', 'company_labour', 'contract_labour'];

    public function index(Request $request)
    {
        $query = SalarySlab::with('branch')->orderBy('min_ctc');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where('name', 'like', $s);
        }

        $slabs = $query->paginate(20)->withQueryString();
        return view('masters.salary-slabs.index', compact('slabs'));
    }

    public function create()
    {
        $earnings   = EarningsComponent::where('is_active', true)->orderBy('sort_order')->get();
        $deductions = DeductionsComponent::where('is_active', true)->orderBy('sort_order')->get();
        $branches   = Branch::active()->orderBy('name')->get();
        return view('masters.salary-slabs.create', compact('earnings', 'deductions', 'branches'));
    }

    private function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100', 'unique:salary_slabs,name'],
            'min_ctc'  => ['required', 'numeric', 'min:0'],
            'max_ctc'  => ['required', 'numeric', 'min:0', 'gt:min_ctc'],
            'tds_percentage'           => ['nullable', 'numeric', 'between:0,100'],
            'pf_employee_percentage'   => ['nullable', 'numeric', 'between:0,100'],
            'pf_employer_percentage'   => ['nullable', 'numeric', 'between:0,100'],
            'esi_employee_percentage'  => ['nullable', 'numeric', 'between:0,100'],
            'esi_employer_percentage'  => ['nullable', 'numeric', 'between:0,100'],
            'applicable_employee_types'   => ['required', 'array', 'min:1'],
            'applicable_employee_types.*' => ['in:' . implode(',', self::EMPLOYEE_TYPES)],
            'branch_id'      => ['nullable', 'exists:branches,id'],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after:effective_from'],
            'is_active'      => ['boolean'],
            'components' => ['nullable', 'array'],
            'components.*.component_type' => ['required', 'in:earning,deduction'],
            'components.*.component_id'   => ['required', 'integer'],
            'components.*.value_type'     => ['required', 'in:fixed,percentage'],
            'components.*.value'          => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * FSD 7.5: "Salary ranges shall not overlap for the same branch and
     * employee type" — checked across any other active slab sharing the
     * branch (or a global NULL-branch slab), an overlapping employee type,
     * and an overlapping effective-date window.
     */
    private function assertNoOverlap(array $data, ?int $ignoreId = null): void
    {
        $candidates = SalarySlab::where('is_active', true)
            ->where(fn($q) => $q->whereNull('branch_id')->orWhere('branch_id', $data['branch_id'] ?? null))
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        $newFrom = $data['effective_from'];
        $newTo   = $data['effective_to'] ?? null;

        foreach ($candidates as $slab) {
            if (! array_intersect($slab->applicable_employee_types ?? self::EMPLOYEE_TYPES, $data['applicable_employee_types'])) {
                continue;
            }

            $existingTo = $slab->effective_to ? $slab->effective_to->toDateString() : null;
            $dateOverlaps = ($existingTo === null || $existingTo >= $newFrom)
                && ($newTo === null || $slab->effective_from?->toDateString() <= $newTo);
            if (! $dateOverlaps) {
                continue;
            }

            $rangeOverlaps = $data['min_ctc'] <= $slab->max_ctc && $data['max_ctc'] >= $slab->min_ctc;
            if ($rangeOverlaps) {
                abort(422, "This salary range overlaps with existing slab \"{$slab->name}\" for the same branch/employee type/effective period.");
            }
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $this->assertNoOverlap($data);

        $slab = SalarySlab::create(collect($data)->except('components')->all());

        if ($request->filled('components')) {
            foreach ($data['components'] as $comp) {
                $slab->components()->create($comp);
            }
        }

        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab created successfully.');
    }

    public function edit(SalarySlab $salarySlab)
    {
        $salarySlab->load('components');
        $earnings   = EarningsComponent::where('is_active', true)->orderBy('sort_order')->get();
        $deductions = DeductionsComponent::where('is_active', true)->orderBy('sort_order')->get();
        $branches   = Branch::active()->orderBy('name')->get();
        return view('masters.salary-slabs.edit', compact('salarySlab', 'earnings', 'deductions', 'branches'));
    }

    public function update(Request $request, SalarySlab $salarySlab)
    {
        $rules = $this->rules();
        $rules['name'] = ['required', 'string', 'max:100', 'unique:salary_slabs,name,' . $salarySlab->id];
        $data = $request->validate($rules);
        $this->assertNoOverlap($data, $salarySlab->id);

        $salarySlab->update(collect($data)->except('components')->all());

        $salarySlab->components()->delete();
        if ($request->filled('components')) {
            foreach ($data['components'] as $comp) {
                $salarySlab->components()->create($comp);
            }
        }

        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab updated successfully.');
    }

    public function destroy(SalarySlab $salarySlab)
    {
        if ($salarySlab->employees()->exists()) {
            return back()->with('error', 'Cannot delete salary slab assigned to employees.');
        }
        if ($salarySlab->salaryStructures()->exists()) {
            return back()->with('error', 'Cannot delete salary slab used in finalized salary structures.');
        }
        $salarySlab->components()->delete();
        $salarySlab->delete();
        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab deleted successfully.');
    }
}
