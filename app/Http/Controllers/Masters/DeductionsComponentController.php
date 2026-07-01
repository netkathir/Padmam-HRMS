<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\DeductionsComponent;
use Illuminate\Http\Request;

class DeductionsComponentController extends Controller
{
    public function index(Request $request)
    {
        $query = DeductionsComponent::orderBy('sort_order');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }

        $components = $query->paginate(20)->withQueryString();
        return view('masters.deductions.index', compact('components'));
    }

    public function create()
    {
        return view('masters.deductions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'code'             => ['required', 'string', 'max:20', 'unique:deductions_components,code'],
            'type'             => ['required', 'in:fixed,percentage,statutory'],
            'calculation_base' => ['nullable', 'string', 'max:100'],
            'percentage'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_statutory'     => ['boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active'        => ['boolean'],
        ]);

        DeductionsComponent::create($data);

        return redirect()->route('masters.deductions.index')
            ->with('success', 'Deductions component created successfully.');
    }

    public function edit(DeductionsComponent $deductionsComponent)
    {
        return view('masters.deductions.edit', compact('deductionsComponent'));
    }

    public function update(Request $request, DeductionsComponent $deductionsComponent)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'code'             => ['required', 'string', 'max:20', 'unique:deductions_components,code,' . $deductionsComponent->id],
            'type'             => ['required', 'in:fixed,percentage,statutory'],
            'calculation_base' => ['nullable', 'string', 'max:100'],
            'percentage'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_statutory'     => ['boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active'        => ['boolean'],
        ]);

        $deductionsComponent->update($data);

        return redirect()->route('masters.deductions.index')
            ->with('success', 'Deductions component updated successfully.');
    }

    public function destroy(DeductionsComponent $deductionsComponent)
    {
        $deductionsComponent->delete();
        return redirect()->route('masters.deductions.index')
            ->with('success', 'Deductions component deleted successfully.');
    }
}
