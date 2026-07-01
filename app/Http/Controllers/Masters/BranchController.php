<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)
                ->orWhere('code', 'like', $s)
                ->orWhere('city', 'like', $s)
                ->orWhere('email', 'like', $s));
        }

        $branches = $query->paginate(20)->withQueryString();
        return view('masters.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('masters.branches.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['required', 'string', 'max:20', 'unique:branches,code'],
            'address'   => ['nullable', 'string'],
            'city'      => ['nullable', 'string', 'max:100'],
            'state'     => ['nullable', 'string', 'max:100'],
            'pincode'   => ['nullable', 'string', 'max:10'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:150'],
            'is_active' => ['boolean'],
        ]);

        Branch::create($data);

        return redirect()->route('masters.branches.index')
            ->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        return view('masters.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['required', 'string', 'max:20', 'unique:branches,code,' . $branch->id],
            'address'   => ['nullable', 'string'],
            'city'      => ['nullable', 'string', 'max:100'],
            'state'     => ['nullable', 'string', 'max:100'],
            'pincode'   => ['nullable', 'string', 'max:10'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:150'],
            'is_active' => ['boolean'],
        ]);

        $branch->update($data);

        return redirect()->route('masters.branches.index')
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->departments()->exists()) {
            return back()->with('error', 'Cannot delete branch with associated departments.');
        }
        $branch->delete();
        return redirect()->route('masters.branches.index')
            ->with('success', 'Branch deleted successfully.');
    }
}
