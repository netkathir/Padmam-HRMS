<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
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
        return view('masters.salary-slabs.create');
    }

    private function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:100'],
            'tds_percentage'           => ['required', 'numeric', 'between:0,100'],
            'pf_employee_percentage'   => ['required', 'numeric', 'between:0,100'],
            'pf_employer_percentage'   => ['required', 'numeric', 'between:0,100'],
            'esi_employee_percentage'  => ['required', 'numeric', 'between:0,100'],
            'esi_employer_percentage'  => ['required', 'numeric', 'between:0,100'],
            'is_active'      => ['required', 'boolean'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
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
