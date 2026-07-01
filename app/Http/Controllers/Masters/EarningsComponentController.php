<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\EarningsComponent;
use Illuminate\Http\Request;

class EarningsComponentController extends Controller
{
    public function index(Request $request)
    {
        $query = EarningsComponent::orderBy('sort_order');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }

        $components = $query->paginate(20)->withQueryString();
        return view('masters.earnings.index', compact('components'));
    }

    public function create()
    {
        return view('masters.earnings.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'code'             => ['required', 'string', 'max:20', 'unique:earnings_components,code'],
            'type'             => ['required', 'in:fixed,percentage,formula'],
            'calculation_base' => ['nullable', 'string', 'max:100'],
            'percentage'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_taxable'       => ['boolean'],
            'is_pf_applicable' => ['boolean'],
            'is_esi_applicable' => ['boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active'        => ['boolean'],
        ]);

        EarningsComponent::create($data);

        return redirect()->route('masters.earnings.index')
            ->with('success', 'Earnings component created successfully.');
    }

    public function edit(EarningsComponent $earningsComponent)
    {
        return view('masters.earnings.edit', compact('earningsComponent'));
    }

    public function update(Request $request, EarningsComponent $earningsComponent)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'code'             => ['required', 'string', 'max:20', 'unique:earnings_components,code,' . $earningsComponent->id],
            'type'             => ['required', 'in:fixed,percentage,formula'],
            'calculation_base' => ['nullable', 'string', 'max:100'],
            'percentage'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_taxable'       => ['boolean'],
            'is_pf_applicable' => ['boolean'],
            'is_esi_applicable' => ['boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active'        => ['boolean'],
        ]);

        $earningsComponent->update($data);

        return redirect()->route('masters.earnings.index')
            ->with('success', 'Earnings component updated successfully.');
    }

    public function destroy(EarningsComponent $earningsComponent)
    {
        $earningsComponent->delete();
        return redirect()->route('masters.earnings.index')
            ->with('success', 'Earnings component deleted successfully.');
    }
}
