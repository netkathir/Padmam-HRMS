<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Shift;
use App\Support\SequentialCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            // `code` is auto-generated server-side (see createWithGeneratedCode())
            // and never accepted from the client, so it is intentionally not
            // part of this validated field set.
            'name'                      => ['required', 'string', 'max:100', Rule::unique('shifts', 'name')->ignore($shiftId)],
            'start_time'                => ['required', 'date_format:H:i'],
            'end_time'                  => ['required', 'date_format:H:i'],
            'break_minutes'             => ['nullable', 'integer', 'min:0', 'max:480'],
            'grace_late_entry_minutes'  => ['nullable', 'integer', 'min:0'],
            'grace_early_exit_minutes'  => ['nullable', 'integer', 'min:0'],
            'work_hours'                => ['nullable', 'numeric', 'min:0', 'max:24'],
            'is_overnight'              => ['boolean'],
            'is_active'                 => ['required', 'boolean'],
            'branch_ids'                => ['required', 'array', 'min:1'],
            'branch_ids.*'              => ['exists:branches,id'],
            'applicable_employee_types'   => ['required', 'array', 'min:1'],
            'applicable_employee_types.*' => ['in:' . implode(',', self::EMPLOYEE_TYPES)],
        ];
    }

    /**
     * FSD 7.2: shift end time must form a valid (positive) duration, and
     * combined grace periods shall not exceed that total shift duration.
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

        if ($duration <= 0) {
            abort(422, 'Shift End Time must result in a valid shift duration (enable Overnight Shift if it crosses midnight).');
        }

        $grace = (int) ($data['grace_late_entry_minutes'] ?? 0) + (int) ($data['grace_early_exit_minutes'] ?? 0);

        if ($grace > $duration) {
            abort(422, 'Combined grace periods cannot exceed the total shift duration.');
        }
    }

    /**
     * Generates the next Shift Code (one higher than the latest existing
     * code, preserving its prefix/padding) and creates the shift with it —
     * mirrors BranchController::createWithGeneratedCode() exactly. A row
     * lock on the latest shift serializes concurrent creations, and the
     * retry loop is a defensive fallback against the rare duplicate-key
     * race the lock doesn't cover — the unique index on `code` is the
     * actual guarantee against ever storing a collision.
     */
    private function createWithGeneratedCode(array $data): Shift
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $lastCode = Shift::orderByDesc('id')->lockForUpdate()->value('code');
                    $data['code'] = SequentialCodeGenerator::next($lastCode, 'SH0001');

                    return Shift::create($data);
                });
            } catch (QueryException $e) {
                $isDuplicate = (string) $e->getCode() === '23000';
                if (! $isDuplicate || $attempt === 5) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Unable to generate a unique Shift Code after several attempts.');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $this->assertGraceWithinDuration($data);

        $shift = $this->createWithGeneratedCode($data);
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
