<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Branch;
use App\Models\Setting;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends Controller
{
    private const TYPES = ['public_holiday', 'festival_holiday', 'optional', 'company_holiday'];
    private const EMPLOYEE_TYPES = ['staff', 'company_labour', 'contract_labour'];

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
        $sundayPaidCompanyLabour  = Setting::get('holiday', 'sunday_paid_company_labour', true);
        $sundayPaidContractLabour = Setting::get('holiday', 'sunday_paid_contract_labour', true);
        return view('masters.holidays.index', compact('holidays', 'branches', 'years', 'sundayPaidCompanyLabour', 'sundayPaidContractLabour'));
    }

    public function updateSundayPolicy(Request $request)
    {
        $data = $request->validate([
            'sunday_paid_company_labour'  => ['boolean'],
            'sunday_paid_contract_labour' => ['boolean'],
        ]);

        Setting::set('holiday', 'sunday_paid_company_labour', $request->boolean('sunday_paid_company_labour') ? '1' : '0');
        Setting::set('holiday', 'sunday_paid_contract_labour', $request->boolean('sunday_paid_contract_labour') ? '1' : '0');

        return redirect()->route('masters.holidays.index')
            ->with('success', 'Sunday pay policy updated successfully.');
    }

    public function create()
    {
        $currentBranch = BranchScope::currentBranch();
        return view('masters.holidays.create', compact('currentBranch'));
    }

    private function rules(): array
    {
        return [
            'branch_id'     => ['nullable', 'exists:branches,id'],
            'calendar_name' => ['required', 'string', 'max:150'],
            'name'          => ['required', 'string', 'max:100'],
            'date'          => ['required', 'date'],
            'type'          => ['required', 'in:' . implode(',', self::TYPES)],
            'is_paid'       => ['boolean'],
            'description'   => ['nullable', 'string'],
            'applicable_employee_types'   => ['required', 'array', 'min:1'],
            'applicable_employee_types.*' => ['in:' . implode(',', self::EMPLOYEE_TYPES)],
            'is_active'     => ['boolean'],
        ];
    }

    /**
     * FSD 7.3: "Duplicate holidays for the same branch, date, and
     * applicability shall not be allowed." Employee-type applicability can
     * differ per row for the same date, so this checks for any overlap in
     * applicable_employee_types on an existing row for the same branch+date,
     * not a flat unique constraint.
     */
    private function assertNoDuplicate(array $data, ?int $ignoreId = null): void
    {
        $existing = Holiday::where('branch_id', $data['branch_id'] ?? null)
            ->where('date', $data['date'])
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        foreach ($existing as $row) {
            $existingTypes = $row->applicable_employee_types ?? self::EMPLOYEE_TYPES;
            if (array_intersect($existingTypes, $data['applicable_employee_types'])) {
                abort(422, "A holiday already exists for this branch and date covering one or more of the same employee types (\"{$row->name}\").");
            }
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        // Only the Super Admin may leave branch_id blank (a global/national
        // holiday) — a branch-scoped user always gets their own branch forced.
        $data = BranchScope::stampBranchId($data);
        if (! empty($data['branch_id'])) {
            BranchScope::assertBranchAccess($data['branch_id']);
        }

        $this->assertNoDuplicate($data);

        $data['year'] = \Carbon\Carbon::parse($data['date'])->year;
        Holiday::create($data);

        return redirect()->route('masters.holidays.index')
            ->with('success', 'Holiday created successfully.');
    }

    public function edit(Holiday $holiday)
    {
        BranchScope::assertBranchAccess($holiday->branch_id);
        $currentBranch = $holiday->branch ?? BranchScope::currentBranch();
        return view('masters.holidays.edit', compact('holiday', 'currentBranch'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        BranchScope::assertBranchAccess($holiday->branch_id);

        $data = $request->validate($this->rules());

        $data = BranchScope::stampBranchId($data);
        if (! empty($data['branch_id'])) {
            BranchScope::assertBranchAccess($data['branch_id']);
        }

        $this->assertNoDuplicate($data, $holiday->id);

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
