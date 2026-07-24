<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Branch;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankController extends Controller
{
    public function index(Request $request)
    {
        $query = BranchScope::scopeQuery(Bank::with('branch'))->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }
        if (BranchScope::currentBranchId() === null && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $banks    = $query->paginate(20)->withQueryString();
        $branches = BranchScope::currentBranchId() === null ? Branch::orderBy('name')->get() : collect();
        return view('masters.banks.index', compact('banks', 'branches'));
    }

    public function create()
    {
        $currentBranch = BranchScope::currentBranch();
        return view('masters.banks.create', compact('currentBranch'));
    }

    private function rules(?int $bankId = null): array
    {
        $branchId = BranchScope::currentBranchId() ?? request()->input('branch_id');

        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'name'      => ['required', 'string', 'max:100'],
            // whereNull('deleted_at') is load-bearing: Rule::unique() has no
            // built-in awareness of soft deletes — without it, a deleted
            // bank's code stays permanently "taken" and can never be
            // reused. Scoped per branch (not global) — two different
            // branches may legitimately register a bank with the same code.
            'code'      => ['nullable', 'string', 'max:20', Rule::unique('banks', 'code')->where('branch_id', $branchId)->whereNull('deleted_at')->ignore($bankId)],
            'is_active' => ['boolean'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);
        BranchScope::assertBranchIsActive($data['branch_id']);

        Bank::create($data);

        return redirect()->route('masters.banks.index')
            ->with('success', 'Bank created successfully.');
    }

    public function edit(Bank $bank)
    {
        BranchScope::assertBranchAccess($bank->branch_id);
        $currentBranch = $bank->branch;
        return view('masters.banks.edit', compact('bank', 'currentBranch'));
    }

    public function update(Request $request, Bank $bank)
    {
        BranchScope::assertBranchAccess($bank->branch_id);

        $data = $request->validate($this->rules($bank->id));

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id']);

        $bank->update($data);

        return redirect()->route('masters.banks.index')
            ->with('success', 'Bank updated successfully.');
    }

    public function destroy(Bank $bank)
    {
        BranchScope::assertBranchAccess($bank->branch_id);

        $bank->delete();
        return redirect()->route('masters.banks.index')
            ->with('success', 'Bank deleted successfully.');
    }
}
