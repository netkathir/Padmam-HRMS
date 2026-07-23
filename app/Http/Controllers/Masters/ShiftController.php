<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Shift;
use App\Support\BranchScope;
use App\Support\SequentialCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $query = BranchScope::scopeQuery(Shift::with('branch'))->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }
        // A branch is already forced above (BranchScope::scopeQuery) for a
        // Super Admin (currently selected branch) or a branch-scoped actor
        // (their own branch) — this ad-hoc filter only still applies for
        // unscoped legacy accounts, avoiding a redundant/conflicting AND.
        if (BranchScope::currentBranchId() === null && $request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $shifts   = $query->paginate(20)->withQueryString();
        $branches = BranchScope::currentBranchId() === null ? Branch::orderBy('name')->get() : collect();
        return view('masters.shifts.index', compact('shifts', 'branches'));
    }

    public function create()
    {
        $currentBranch = BranchScope::currentBranch();
        return view('masters.shifts.create', compact('currentBranch'));
    }

    private function rules(?int $shiftId = null): array
    {
        return [
            // `code` is auto-generated server-side (see createWithGeneratedCode())
            // and never accepted from the client, so it is intentionally not
            // part of this validated field set. `branch_id` is likewise
            // never accepted from the client — always stamped server-side
            // from the currently active branch (see BranchScope::stampBranchId()).
            'name'                      => ['required', 'string', 'max:100', Rule::unique('shifts', 'name')->ignore($shiftId)],
            'start_time'                => ['required', 'date_format:H:i'],
            'end_time'                  => ['required', 'date_format:H:i'],
            'grace_late_entry_minutes'  => ['nullable', 'integer', 'min:0'],
            'grace_early_exit_minutes'  => ['nullable', 'integer', 'min:0'],
            'work_hours'                => ['nullable', 'numeric', 'min:0', 'max:24'],
            'is_active'                 => ['required', 'boolean'],
            'applicable_employee_types'   => ['required', 'array', 'min:1'],
            'applicable_employee_types.*' => ['in:' . implode(',', array_keys(config('employee_types')))],
        ];
    }

    /**
     * FSD 7.2: shift end time must form a valid (positive) duration, and
     * combined grace periods shall not exceed that total shift duration.
     * No explicit overnight flag: an End Time strictly earlier than Start
     * Time is inferred to cross midnight (e.g. 22:00 -> 06:00 is an 8-hour
     * shift), purely from the 24-hour values themselves — mirrors
     * Shift::getDurationMinutesAttribute(). An End Time EQUAL to Start Time
     * is NOT wrapped — that's a same-time data-entry mistake, not a 24-hour
     * shift, and must still be rejected below.
     */
    private function assertGraceWithinDuration(array $data): void
    {
        $start = \Carbon\Carbon::createFromFormat('H:i', $data['start_time'])->hour * 60
            + \Carbon\Carbon::createFromFormat('H:i', $data['start_time'])->minute;
        $end = \Carbon\Carbon::createFromFormat('H:i', $data['end_time'])->hour * 60
            + \Carbon\Carbon::createFromFormat('H:i', $data['end_time'])->minute;

        if ($end < $start) {
            $end += 24 * 60;
        }
        $duration = $end - $start;

        if ($duration <= 0) {
            abort(422, 'Shift End Time must result in a valid shift duration.');
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
        // Two near-simultaneous submissions (double-click, slow reload +
        // resubmit, two admins at once) can both read the same "last code"
        // and race for the same next value — one wins, the other must
        // retry with the now-updated last code. 10 attempts with a short
        // random backoff between them gives real concurrent contention
        // enough room to clear before giving up, rather than surfacing a
        // raw 500 to the user.
        //
        // withTrashed() here is load-bearing, not optional: the database's
        // unique index on `code` has no concept of "soft deleted" — a
        // deleted Shift's code is still permanently reserved. Looking only
        // at non-deleted rows (or worse, "whichever row has the highest
        // id") can compute a code that a deleted row already holds,
        // guaranteeing a duplicate-key failure every single time (the
        // retry loop can't help — it would recompute the exact same wrong
        // answer on every attempt, since nothing about that flaw changes).
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $allCodes = Shift::withTrashed()->lockForUpdate()->pluck('code');
                    $lastCode = SequentialCodeGenerator::highestCode($allCodes);
                    $data['code'] = SequentialCodeGenerator::next($lastCode, 'SH0001');

                    return Shift::create($data);
                });
            } catch (QueryException $e) {
                $isDuplicate = (string) $e->getCode() === '23000';
                if (! $isDuplicate || $attempt === 10) {
                    throw $e;
                }
                usleep(random_int(20_000, 80_000)); // 20-80ms jittered backoff
            }
        }

        throw new \RuntimeException('Unable to generate a unique Shift Code after several attempts.');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $this->assertGraceWithinDuration($data);

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchAccess($data['branch_id'] ?? null);

        $this->createWithGeneratedCode($data);

        return redirect()->route('masters.shifts.index')
            ->with('success', 'Shift created successfully.');
    }

    public function edit(Shift $shift)
    {
        BranchScope::assertBranchAccess($shift->branch_id);
        $currentBranch = $shift->branch;
        return view('masters.shifts.edit', compact('shift', 'currentBranch'));
    }

    public function update(Request $request, Shift $shift)
    {
        BranchScope::assertBranchAccess($shift->branch_id);

        $data = $request->validate($this->rules($shift->id));
        $this->assertGraceWithinDuration($data);

        $shift->update($data);

        return redirect()->route('masters.shifts.index')
            ->with('success', 'Shift updated successfully.');
    }

    public function destroy(Shift $shift)
    {
        BranchScope::assertBranchAccess($shift->branch_id);

        if ($shift->employeeShiftAssignments()->exists()) {
            return back()->with('error', 'Cannot delete shift with active assignments.');
        }
        $shift->delete();
        return redirect()->route('masters.shifts.index')
            ->with('success', 'Shift deleted successfully.');
    }
}
