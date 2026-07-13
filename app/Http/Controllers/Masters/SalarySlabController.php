<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\SalarySlab;
use App\Models\EarningsComponent;
use App\Models\DeductionsComponent;
use Illuminate\Http\Request;

class SalarySlabController extends Controller
{
    public function index(Request $request)
    {
        $query = SalarySlab::query()->orderBy('min_ctc');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where('name', 'like', $s);
        }

        $slabs = $query->paginate(20)->withQueryString();
        return view('masters.salary-slabs.index', compact('slabs'));
    }

    public function create()
    {
        $earnings   = EarningsComponent::where('is_active', true)->orderBy('sort_order')->get();
        $deductions = DeductionsComponent::where('is_active', true)->orderBy('sort_order')->get();
        return view('masters.salary-slabs.create', compact('earnings', 'deductions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'min_ctc'  => ['required', 'numeric', 'min:0'],
            'max_ctc'  => ['required', 'numeric', 'min:0', 'gt:min_ctc'],
            'components' => ['nullable', 'array'],
            'components.*.component_type' => ['required', 'in:earning,deduction'],
            'components.*.component_id'   => ['required', 'integer'],
            'components.*.value_type'     => ['required', 'in:fixed,percentage'],
            'components.*.value'          => ['required', 'numeric', 'min:0'],
        ]);

        $slab = SalarySlab::create([
            'name'    => $data['name'],
            'min_ctc' => $data['min_ctc'],
            'max_ctc' => $data['max_ctc'],
        ]);

        if ($request->filled('components')) {
            foreach ($data['components'] as $comp) {
                $slab->components()->create($comp);
            }
        }

        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab created successfully.');
    }

    public function edit(SalarySlab $salarySlab)
    {
        $salarySlab->load('components');
        $earnings   = EarningsComponent::where('is_active', true)->orderBy('sort_order')->get();
        $deductions = DeductionsComponent::where('is_active', true)->orderBy('sort_order')->get();
        return view('masters.salary-slabs.edit', compact('salarySlab', 'earnings', 'deductions'));
    }

    public function update(Request $request, SalarySlab $salarySlab)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'min_ctc'  => ['required', 'numeric', 'min:0'],
            'max_ctc'  => ['required', 'numeric', 'min:0', 'gt:min_ctc'],
            'components' => ['nullable', 'array'],
            'components.*.component_type' => ['required', 'in:earning,deduction'],
            'components.*.component_id'   => ['required', 'integer'],
            'components.*.value_type'     => ['required', 'in:fixed,percentage'],
            'components.*.value'          => ['required', 'numeric', 'min:0'],
        ]);

        $salarySlab->update([
            'name'    => $data['name'],
            'min_ctc' => $data['min_ctc'],
            'max_ctc' => $data['max_ctc'],
        ]);

        $salarySlab->components()->delete();
        if ($request->filled('components')) {
            foreach ($data['components'] as $comp) {
                $salarySlab->components()->create($comp);
            }
        }

        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab updated successfully.');
    }

    public function destroy(SalarySlab $salarySlab)
    {
        if ($salarySlab->employees()->exists()) {
            return back()->with('error', 'Cannot delete salary slab assigned to employees.');
        }
        $salarySlab->components()->delete();
        $salarySlab->delete();
        return redirect()->route('masters.salary-slabs.index')
            ->with('success', 'Salary slab deleted successfully.');
    }
}
