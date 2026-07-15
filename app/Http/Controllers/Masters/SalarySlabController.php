<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\SalarySlab;
use Illuminate\Http\Request;

class SalarySlabController extends Controller
{
    private const EMPLOYEE_TYPES = ['staff', 'company_labour', 'contract_labour'];

    public function index(Request $request)
    {
        $query = SalarySlab::orderBy('min_ctc');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where('name', 'like', $s);
        }

        $slabs = $query->paginate(20)->withQueryString();
        return view('masters.salary-slabs.index', compact('slabs'));
    }

    public function create()
    {
        return view('masters.salary-slabs.create');
    }

    private function rules(): array
    {
        return [
            'min_ctc'  => ['required', 'numeric', 'min:0'],
            'max_ctc'  => ['required', 'numeric', 'min:0', 'gte:min_ctc'],
            'tds_percentage'           => ['required', 'numeric', 'between:0,100'],
            'pf_employee_percentage'   => ['required', 'numeric', 'between:0,100'],
            'pf_employer_percentage'   => ['required', 'numeric', 'between:0,100'],
            'esi_employee_percentage'  => ['required', 'numeric', 'between:0,100'],
            'esi_employer_percentage'  => ['required', 'numeric', 'between:0,100'],
            'applicable_employee_types'   => ['required', 'array', 'min:1'],
            'applicable_employee_types.*' => ['in:' . implode(',', self::EMPLOYEE_TYPES)],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active'      => ['required', 'boolean'],
        ];
    }

    /**
     * FSD: salary ranges shall not overlap for the same employee type —
     * checked across any other active slab sharing an overlapping employee
     * type and an overlapping effective-date window. Salary Slab is a
     * single company-wide configuration (no branch dimension).
     */
    private function assertNoOverlap(array $data, ?int $ignoreId = null): void
    {
        $candidates = SalarySlab::where('is_active', true)
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
                abort(422, "This salary range overlaps with existing slab \"{$slab->name}\" for the same employee type/effective period.");
            }
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $this->assertNoOverlap($data);

        $data['name'] = SalarySlab::generateName($data['min_ctc'], $data['max_ctc']);
        SalarySlab::create($data);

        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab created successfully.');
    }

    public function edit(SalarySlab $salarySlab)
    {
        return view('masters.salary-slabs.edit', compact('salarySlab'));
    }

    public function update(Request $request, SalarySlab $salarySlab)
    {
        $data = $request->validate($this->rules());
        $this->assertNoOverlap($data, $salarySlab->id);

        $data['name'] = SalarySlab::generateName($data['min_ctc'], $data['max_ctc']);
        $salarySlab->update($data);

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
        $salarySlab->delete();
        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab deleted successfully.');
    }
}
