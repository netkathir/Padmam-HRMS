<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\OtRate;
use App\Models\EmployeeType;
use App\Models\Department;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class OtRateController extends Controller
{
    public function index(Request $request)
    {
        $query = OtRate::with(['employeeType', 'department'])->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where('name', 'like', $s);
        }

        $otRates = $query->paginate(20)->withQueryString();
        return view('masters.ot-rates.index', compact('otRates'));
    }

    public function create()
    {
        $employeeTypes = EmployeeType::where('is_active', true)->orderBy('name')->get();
        $departments   = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();
        return view('masters.ot-rates.create', compact('employeeTypes', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'employee_type_id' => ['nullable', 'exists:employee_types,id'],
            'department_id'    => ['nullable', 'exists:departments,id'],
            'rate_type'        => ['required', 'in:hourly_multiplier,fixed_per_hour'],
            'multiplier'       => ['nullable', 'numeric', 'min:1', 'max:10'],
            'fixed_amount'     => ['nullable', 'numeric', 'min:0'],
            'max_ot_hours_day' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'is_active'        => ['boolean'],
        ]);

        OtRate::create($data);

        return redirect()->route('masters.ot-rates.index')
            ->with('success', 'OT Rate created successfully.');
    }

    public function edit(OtRate $otRate)
    {
        $employeeTypes = EmployeeType::where('is_active', true)->orderBy('name')->get();
        $departments   = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();
        return view('masters.ot-rates.edit', compact('otRate', 'employeeTypes', 'departments'));
    }

    public function update(Request $request, OtRate $otRate)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'employee_type_id' => ['nullable', 'exists:employee_types,id'],
            'department_id'    => ['nullable', 'exists:departments,id'],
            'rate_type'        => ['required', 'in:hourly_multiplier,fixed_per_hour'],
            'multiplier'       => ['nullable', 'numeric', 'min:1', 'max:10'],
            'fixed_amount'     => ['nullable', 'numeric', 'min:0'],
            'max_ot_hours_day' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'is_active'        => ['boolean'],
        ]);

        $otRate->update($data);

        return redirect()->route('masters.ot-rates.index')
            ->with('success', 'OT Rate updated successfully.');
    }

    public function destroy(OtRate $otRate)
    {
        $otRate->delete();
        return redirect()->route('masters.ot-rates.index')
            ->with('success', 'OT Rate deleted successfully.');
    }
}
