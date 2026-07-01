<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\PfEsiConfig;
use Illuminate\Http\Request;

class PfEsiConfigController extends Controller
{
    public function index()
    {
        $configs = PfEsiConfig::orderByDesc('effective_from')->paginate(20);
        return view('masters.pf-esi.index', compact('configs'));
    }

    public function create()
    {
        return view('masters.pf-esi.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'effective_from'      => ['required', 'date'],
            'pf_employee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'pf_employer_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'pf_ceiling'          => ['required', 'numeric', 'min:0'],
            'esi_employee_percent'=> ['required', 'numeric', 'min:0', 'max:100'],
            'esi_employer_percent'=> ['required', 'numeric', 'min:0', 'max:100'],
            'esi_ceiling'         => ['required', 'numeric', 'min:0'],
            'is_active'           => ['boolean'],
        ]);

        PfEsiConfig::create($data);

        return redirect()->route('masters.pf-esi.index')
            ->with('success', 'PF & ESI configuration created successfully.');
    }

    public function edit(PfEsiConfig $pfEsiConfig)
    {
        return view('masters.pf-esi.edit', compact('pfEsiConfig'));
    }

    public function update(Request $request, PfEsiConfig $pfEsiConfig)
    {
        $data = $request->validate([
            'effective_from'      => ['required', 'date'],
            'pf_employee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'pf_employer_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'pf_ceiling'          => ['required', 'numeric', 'min:0'],
            'esi_employee_percent'=> ['required', 'numeric', 'min:0', 'max:100'],
            'esi_employer_percent'=> ['required', 'numeric', 'min:0', 'max:100'],
            'esi_ceiling'         => ['required', 'numeric', 'min:0'],
            'is_active'           => ['boolean'],
        ]);

        $pfEsiConfig->update($data);

        return redirect()->route('masters.pf-esi.index')
            ->with('success', 'PF & ESI configuration updated successfully.');
    }

    public function destroy(PfEsiConfig $pfEsiConfig)
    {
        $pfEsiConfig->delete();
        return redirect()->route('masters.pf-esi.index')
            ->with('success', 'PF & ESI configuration deleted successfully.');
    }
}
