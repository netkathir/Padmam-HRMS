<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\EarningsComponent;
use App\Models\SalarySlab;
use Illuminate\Http\Request;

class SalarySlabController extends Controller
{
    public function index(Request $request)
    {
        $query = SalarySlab::orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where('name', 'like', $s);
        }

        $slabs = $query->paginate(20)->withQueryString();
        return view('masters.salary-slabs.index', compact('slabs'));
    }

    public function create()
    {
        $earningsComponents = EarningsComponent::where('is_active', true)->orderBy('sort_order')->get();
        return view('masters.salary-slabs.create', compact('earningsComponents'));
    }

    private function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100'],
            'salary_from'  => ['required', 'numeric', 'min:0'],
            'salary_to'    => ['required', 'numeric', 'gte:salary_from'],
            'tds_percentage'           => ['required', 'numeric', 'between:0,100'],
            'pf_employee_percentage'   => ['required', 'numeric', 'between:0,100'],
            'pf_employer_percentage'   => ['required', 'numeric', 'between:0,100'],
            'esi_employee_percentage'  => ['required', 'numeric', 'between:0,100'],
            'esi_employer_percentage'  => ['required', 'numeric', 'between:0,100'],
            'is_active'      => ['required', 'boolean'],
            'earnings'                  => ['nullable', 'array'],
            // A row only needs to validate once the user has actually picked an
            // earning OR typed a value for it — an untouched "+ Add Earning" row
            // (both fields still blank) must be silently ignored, not treated as
            // an incomplete required row.
            'earnings.*.component_id'   => ['nullable', 'required_with:earnings.*.value', 'exists:earnings_components,id'],
            'earnings.*.value'          => ['nullable', 'required_with:earnings.*.component_id', 'numeric', 'min:0'],
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
        ];
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

        $salarySlab = SalarySlab::create($data);
        $this->syncEarnings($salarySlab, $earnings);

        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab created successfully.');
    }

    public function edit(SalarySlab $salarySlab)
    {
        $earningsComponents = EarningsComponent::where('is_active', true)->orderBy('sort_order')->get();
        $salarySlab->load('earningsComponents');
        return view('masters.salary-slabs.edit', compact('salarySlab', 'earningsComponents'));
    }

    public function update(Request $request, SalarySlab $salarySlab)
    {
        $data = $request->validate($this->rules(), $this->messages(), $this->attributes());
        $earnings = $data['earnings'] ?? [];
        unset($data['earnings']);

        $salarySlab->update($data);
        $this->syncEarnings($salarySlab, $earnings);

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
