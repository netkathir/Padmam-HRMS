<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    private const EMPLOYEE_TYPES = ['staff', 'company_labour', 'contract_labour'];

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
        $branches = Branch::active()->orderBy('name')->get();
        return view('masters.shifts.create', compact('branches'));
    }

    private function rules(?int $shiftId = null): array
    {
        return [
            'name'                      => ['required', 'string', 'max:100'],
            'code'                      => ['required', 'string', 'max:20', 'unique:shifts,code' . ($shiftId ? ",$shiftId" : '')],
            'start_time'                => ['required', 'date_format:H:i'],
            'end_time'                  => ['required', 'date_format:H:i'],
            'break_minutes'             => ['nullable', 'integer', 'min:0', 'max:480'],
            'grace_late_entry_minutes'  => ['nullable', 'integer', 'min:0'],
            'grace_early_exit_minutes'  => ['nullable', 'integer', 'min:0'],
            'work_hours'                => ['nullable', 'numeric', 'min:0', 'max:24'],
            'is_overnight'              => ['boolean'],
            'is_active'                 => ['boolean'],
            'branch_ids'                => ['required', 'array', 'min:1'],
            'branch_ids.*'              => ['exists:branches,id'],
            'applicable_employee_types'   => ['required', 'array', 'min:1'],
            'applicable_employee_types.*' => ['in:' . implode(',', self::EMPLOYEE_TYPES)],
        ];
    }

    /**
     * FSD 7.2: "Grace periods shall not exceed the total shift duration."
     */
    private function assertGraceWithinDuration(array $data): void
    {
        $start = \Carbon\Carbon::createFromFormat('H:i', $data['start_time'])->hour * 60
            + \Carbon\Carbon::createFromFormat('H:i', $data['start_time'])->minute;
        $end = \Carbon\Carbon::createFromFormat('H:i', $data['end_time'])->hour * 60
            + \Carbon\Carbon::createFromFormat('H:i', $data['end_time'])->minute;

        if (! empty($data['is_overnight']) && $end <= $start) {
            $end += 24 * 60;
        }
        $duration = $end - $start;

        $grace = (int) ($data['grace_late_entry_minutes'] ?? 0) + (int) ($data['grace_early_exit_minutes'] ?? 0);

        if ($grace > $duration) {
            abort(422, 'Combined grace periods cannot exceed the total shift duration.');
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $this->assertGraceWithinDuration($data);

        $shift = Shift::create($data);
        $shift->branches()->sync($data['branch_ids']);

        return redirect()->route('masters.shifts.index')
            ->with('success', 'Shift created successfully.');
    }

    public function edit(Shift $shift)
    {
        $branches = Branch::active()->orderBy('name')->get();
        $shift->load('branches');
        return view('masters.shifts.edit', compact('shift', 'branches'));
    }

    public function update(Request $request, Shift $shift)
    {
        $data = $request->validate($this->rules($shift->id));
        $this->assertGraceWithinDuration($data);

        $shift->update($data);
        $shift->branches()->sync($data['branch_ids']);

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
