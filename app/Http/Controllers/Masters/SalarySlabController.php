<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\DeductionsComponent;
use App\Models\EarningsComponent;
use App\Models\SalarySlab;
use Illuminate\Http\Request;

class SalarySlabController extends Controller
{
    public function index(Request $request)
    {
        $query = SalarySlab::with('components')->orderBy('min_ctc');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where('name', 'like', $s);
        }

        $slabs = $query->paginate(20)->withQueryString();
        return view('masters.salary-slabs.index', compact('slabs'));
    }

    public function create()
    {
        $earningsComponents   = EarningsComponent::where('is_active', true)->orderBy('sort_order')->get();
        $deductionsComponents = DeductionsComponent::where('is_active', true)->orderBy('sort_order')->get();
        return view('masters.salary-slabs.create', compact('earningsComponents', 'deductionsComponents'));
    }

    private function rules(): array
    {
        return [
            'min_ctc'      => ['required', 'numeric', 'min:0'],
            'max_ctc'      => ['required', 'numeric', 'min:0', 'gte:min_ctc'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'tds_percentage'           => ['required', 'numeric', 'between:0,100'],
            'pf_employee_percentage'   => ['required', 'numeric', 'between:0,100'],
            'pf_employer_percentage'   => ['required', 'numeric', 'between:0,100'],
            'esi_employee_percentage'  => ['required', 'numeric', 'between:0,100'],
            'esi_employer_percentage'  => ['required', 'numeric', 'between:0,100'],
            'is_active'      => ['required', 'boolean'],
            // FSD — Salary Slab becomes the single source of truth for
            // Earnings/Deductions Components; only WHICH component is admin
            // input, its type/calc-base/rate are always looked up fresh
            // from the master, never trusted from the request.
            'components'                   => ['nullable', 'array'],
            'components.*.component_type'  => ['required_with:components', 'in:earning,deduction'],
            'components.*.component_id'    => ['required_with:components', 'integer'],
        ];
    }

    /**
     * FSD: salary (CTC) ranges shall not overlap across other active slabs
     * — Applicability (employee type) and Effective Period no longer exist
     * on Salary Slab, so this is now a pure CTC-range check. Salary Slab is
     * a single company-wide configuration (no branch dimension).
     */
    private function assertNoOverlap(array $data, ?int $ignoreId = null): void
    {
        $candidates = SalarySlab::where('is_active', true)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        foreach ($candidates as $slab) {
            $rangeOverlaps = $data['min_ctc'] <= $slab->max_ctc && $data['max_ctc'] >= $slab->min_ctc;
            if ($rangeOverlaps) {
                abort(422, "This salary range overlaps with existing slab \"{$slab->name}\".");
            }
        }
    }

    /**
     * Mirrors EmployeeController::resolveSalaryComponents() — looks up each
     * selected component's real configuration from the Earnings/Deductions
     * masters and computes its amount against this slab's own Basic Salary
     * (or CTC, for a percentage component based on CTC).
     */
    private function resolveComponents(array $components, float $basicSalary, float $ctc): array
    {
        $resolved = [];

        foreach ($components as $component) {
            $type = $component['component_type'] ?? null;
            $id = $component['component_id'] ?? null;
            if (! $type || ! $id) {
                continue;
            }

            $source = $type === 'earning' ? EarningsComponent::find($id) : DeductionsComponent::find($id);
            if (! $source) {
                continue;
            }

            $base = str_contains(strtolower((string) $source->calculation_base), 'ctc') ? $ctc : $basicSalary;
            $calculatedAmount = $source->type === 'percentage'
                ? round($base * (float) $source->percentage / 100, 2)
                : (float) $source->percentage;

            $resolved[] = [
                'component_type'    => $type,
                'component_id'      => $source->id,
                'component_name'    => $source->name,
                'calculation_type'  => $source->type,
                'calculation_base'  => $source->calculation_base,
                'rate'              => $source->percentage,
                'calculated_amount' => $calculatedAmount,
            ];
        }

        return $resolved;
    }

    /** Replaces this slab's whole component set with the newly-resolved one (admin's edit is always a full replace, not a merge). */
    private function saveComponents(SalarySlab $salarySlab, array $components): void
    {
        $resolved = $this->resolveComponents($components, (float) $salarySlab->basic_salary, (float) $salarySlab->max_ctc);
        $salarySlab->components()->delete();
        foreach ($resolved as $component) {
            $salarySlab->components()->create($component);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $this->assertNoOverlap($data);
        $components = $data['components'] ?? [];
        unset($data['components']);

        $data['name'] = SalarySlab::generateName($data['min_ctc'], $data['max_ctc']);
        $salarySlab = SalarySlab::create($data);
        $this->saveComponents($salarySlab, $components);

        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab created successfully.');
    }

    public function edit(SalarySlab $salarySlab)
    {
        $salarySlab->load('components');
        $earningsComponents   = EarningsComponent::where('is_active', true)->orderBy('sort_order')->get();
        $deductionsComponents = DeductionsComponent::where('is_active', true)->orderBy('sort_order')->get();
        return view('masters.salary-slabs.edit', compact('salarySlab', 'earningsComponents', 'deductionsComponents'));
    }

    public function update(Request $request, SalarySlab $salarySlab)
    {
        $data = $request->validate($this->rules());
        $this->assertNoOverlap($data, $salarySlab->id);
        $components = $data['components'] ?? [];
        unset($data['components']);

        $data['name'] = SalarySlab::generateName($data['min_ctc'], $data['max_ctc']);
        $salarySlab->update($data);
        $this->saveComponents($salarySlab, $components);

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
