<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Branch;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $query = Holiday::with('branch')->orderBy('date', 'desc');

        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        } else {
            $query->whereYear('date', now()->year);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $holidays = $query->paginate(20)->withQueryString();
        $branches = Branch::orderBy('name')->get();
        $years    = range(now()->year - 2, now()->year + 2);
        return view('masters.holidays.index', compact('holidays', 'branches', 'years'));
    }

    public function create()
    {
        $branches = Branch::active()->orderBy('name')->get();
        return view('masters.holidays.create', compact('branches'));
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

        $data['year'] = \Carbon\Carbon::parse($data['date'])->year;
        Holiday::create($data);

        return redirect()->route('masters.holidays.index')
            ->with('success', 'Holiday created successfully.');
    }

    public function edit(Holiday $holiday)
    {
        $branches = Branch::active()->orderBy('name')->get();
        return view('masters.holidays.edit', compact('holiday', 'branches'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'name'      => ['required', 'string', 'max:100'],
            'date'      => ['required', 'date'],
            'type'      => ['required', 'in:national,regional,optional'],
            'is_active' => ['boolean'],
        ]);

        $data['year'] = \Carbon\Carbon::parse($data['date'])->year;
        $holiday->update($data);

        return redirect()->route('masters.holidays.index')
            ->with('success', 'Holiday updated successfully.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return redirect()->route('masters.holidays.index')
            ->with('success', 'Holiday deleted successfully.');
    }
}
