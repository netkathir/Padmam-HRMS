<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Branch;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        // A NULL branch_id on a Holiday means "applies to every branch"
        // (e.g. a national holiday) — a branch-scoped user must still see
        // those alongside their own branch's holidays, not just be excluded.
        $query = BranchScope::scopeQueryIncludingGlobal(Holiday::with('branch'))->orderBy('date', 'desc');

        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        } else {
            $query->whereYear('date', now()->year);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $holidays = $query->paginate(20)->withQueryString();
        $branches = auth()->user()->isSuperAdmin() ? Branch::orderBy('name')->get() : collect();
        $years    = range(now()->year - 2, now()->year + 2);
        return view('masters.holidays.index', compact('holidays', 'branches', 'years'));
    }

    public function create()
    {
        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $branches = $isSuperAdmin ? Branch::active()->orderBy('name')->get() : Branch::where('id', BranchScope::currentBranchId())->get();
        $lockedBranchId = $isSuperAdmin ? null : BranchScope::currentBranchId();
        return view('masters.holidays.create', compact('branches', 'lockedBranchId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name'      => ['required', 'string', 'max:100'],
            'date'      => ['required', 'date'],
            'type'      => ['required', 'in:national,regional,optional'],
            'is_active' => ['boolean'],
        ]);

        // Only the Super Admin may leave branch_id blank (a global/national
        // holiday) — a branch-scoped user always gets their own branch forced.
        $data = BranchScope::stampBranchId($data);
        if (! empty($data['branch_id'])) {
            BranchScope::assertBranchAccess($data['branch_id']);
        }

        $data['year'] = \Carbon\Carbon::parse($data['date'])->year;
        Holiday::create($data);

        return redirect()->route('masters.holidays.index')
            ->with('success', 'Holiday created successfully.');
    }

    public function edit(Holiday $holiday)
    {
        BranchScope::assertBranchAccess($holiday->branch_id);
        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $branches = $isSuperAdmin ? Branch::active()->orderBy('name')->get() : Branch::where('id', $holiday->branch_id)->get();
        $lockedBranchId = $isSuperAdmin ? null : $holiday->branch_id;
        return view('masters.holidays.edit', compact('holiday', 'branches', 'lockedBranchId'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        BranchScope::assertBranchAccess($holiday->branch_id);

        $data = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name'      => ['required', 'string', 'max:100'],
            'date'      => ['required', 'date'],
            'type'      => ['required', 'in:national,regional,optional'],
            'is_active' => ['boolean'],
        ]);

        $data = BranchScope::stampBranchId($data);
        if (! empty($data['branch_id'])) {
            BranchScope::assertBranchAccess($data['branch_id']);
        }

        $data['year'] = \Carbon\Carbon::parse($data['date'])->year;
        $holiday->update($data);

        return redirect()->route('masters.holidays.index')
            ->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        BranchScope::assertBranchAccess($holiday->branch_id);

        $holiday->delete();
        return redirect()->route('masters.holidays.index')
            ->with('success', 'Holiday deleted successfully.');
    }
}
