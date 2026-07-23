<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankController extends Controller
{
    public function index(Request $request)
    {
        $query = Bank::orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }

        $banks = $query->paginate(20)->withQueryString();
        return view('masters.banks.index', compact('banks'));
    }

    public function create()
    {
        return view('masters.banks.create');
    }

    public function store(Request $request)
    {
        // whereNull('deleted_at') is load-bearing: Rule::unique() has no
        // built-in awareness of soft deletes — without it, a deleted
        // bank's code stays permanently "taken" and can never be reused.
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:20', Rule::unique('banks', 'code')->whereNull('deleted_at')],
            'is_active' => ['boolean'],
        ]);

        Bank::create($data);

        return redirect()->route('masters.banks.index')
            ->with('success', 'Bank created successfully.');
    }

    public function edit(Bank $bank)
    {
        return view('masters.banks.edit', compact('bank'));
    }

    public function update(Request $request, Bank $bank)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['nullable', 'string', 'max:20', Rule::unique('banks', 'code')->whereNull('deleted_at')->ignore($bank->id)],
            'is_active' => ['boolean'],
        ]);

        $bank->update($data);

        return redirect()->route('masters.banks.index')
            ->with('success', 'Bank updated successfully.');
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();
        return redirect()->route('masters.banks.index')
            ->with('success', 'Bank deleted successfully.');
    }
}
