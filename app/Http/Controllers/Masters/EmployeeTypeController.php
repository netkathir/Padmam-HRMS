<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\EmployeeType;
use Illuminate\Http\Request;

class EmployeeTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeType::orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }

        $employeeTypes = $query->paginate(20)->withQueryString();
        return view('masters.employee-types.index', compact('employeeTypes'));
    }

    public function create()
    {
        return view('masters.employee-types.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:50'],
            'code'        => ['required', 'string', 'max:20', 'unique:employee_types,code'],
            'description' => ['nullable', 'string', 'max:200'],
            'is_active'   => ['boolean'],
        ]);

        EmployeeType::create($data);

        return redirect()->route('masters.employee-types.index')
            ->with('success', 'Employee type created successfully.');
    }

    public function edit(EmployeeType $employeeType)
    {
        return view('masters.employee-types.edit', compact('employeeType'));
    }

    public function update(Request $request, EmployeeType $employeeType)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:50'],
            'code'        => ['required', 'string', 'max:20', 'unique:employee_types,code,' . $employeeType->id],
            'description' => ['nullable', 'string', 'max:200'],
            'is_active'   => ['boolean'],
        ]);

        $employeeType->update($data);

        return redirect()->route('masters.employee-types.index')
            ->with('success', 'Employee type updated successfully.');
    }

    public function destroy(EmployeeType $employeeType)
    {
        if ($employeeType->employees()->exists()) {
            return back()->with('error', 'Cannot delete employee type with associated employees.');
        }
        $employeeType->delete();
        return redirect()->route('masters.employee-types.index')
            ->with('success', 'Employee type deleted successfully.');
    }
}
