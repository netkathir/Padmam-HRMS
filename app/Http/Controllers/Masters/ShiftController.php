<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $query = Shift::orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }

        $shifts = $query->paginate(20)->withQueryString();
        return view('masters.shifts.index', compact('shifts'));
    }

    public function create()
    {
        return view('masters.shifts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'code'          => ['required', 'string', 'max:20', 'unique:shifts,code'],
            'start_time'    => ['required', 'date_format:H:i'],
            'end_time'      => ['required', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'work_hours'    => ['nullable', 'numeric', 'min:0', 'max:24'],
            'is_overnight'  => ['boolean'],
            'is_active'     => ['boolean'],
        ]);

        Shift::create($data);

        return redirect()->route('masters.shifts.index')
            ->with('success', 'Shift created successfully.');
    }

    public function edit(Shift $shift)
    {
        return view('masters.shifts.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'code'          => ['required', 'string', 'max:20', 'unique:shifts,code,' . $shift->id],
            'start_time'    => ['required', 'date_format:H:i'],
            'end_time'      => ['required', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:480'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'work_hours'    => ['nullable', 'numeric', 'min:0', 'max:24'],
            'is_overnight'  => ['boolean'],
            'is_active'     => ['boolean'],
        ]);

        $shift->update($data);

        return redirect()->route('masters.shifts.index')
            ->with('success', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        if ($shift->employeeShiftAssignments()->exists()) {
            return back()->with('error', 'Cannot delete shift with active assignments.');
        }
        $shift->delete();
        return redirect()->route('masters.shifts.index')
            ->with('success', 'Shift deleted successfully.');
    }
}
