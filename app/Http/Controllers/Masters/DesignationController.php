<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\Department;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function index(Request $request)
    {
        $query = Designation::with('department')->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $designations = $query->paginate(20)->withQueryString();
        $departments  = Department::orderBy('name')->get();
        return view('masters.designations.index', compact('designations', 'departments'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        return view('masters.designations.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'name'          => ['required', 'string', 'max:100'],
            'code'          => ['nullable', 'string', 'max:20'],
            'grade'         => ['nullable', 'string', 'max:20'],
            'is_active'     => ['boolean'],
        ]);

        Designation::create($data);

        return redirect()->route('masters.designations.index')
            ->with('success', 'Designation created successfully.');
    }

    public function edit(Designation $designation)
    {
        $departments = Department::orderBy('name')->get();
        return view('masters.designations.edit', compact('designation', 'departments'));
    }

    public function update(Request $request, Designation $designation)
    {
        $data = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'name'          => ['required', 'string', 'max:100'],
            'code'          => ['nullable', 'string', 'max:20'],
            'grade'         => ['nullable', 'string', 'max:20'],
            'is_active'     => ['boolean'],
        ]);

        $designation->update($data);

        return redirect()->route('masters.designations.index')
            ->with('success', 'Designation updated successfully.');
    }

    public function destroy(Designation $designation)
    {
        if ($designation->employees()->exists()) {
            return back()->with('error', 'Cannot delete designation with associated employees.');
        }
        $designation->delete();
        return redirect()->route('masters.designations.index')
            ->with('success', 'Designation deleted successfully.');
    }
}
