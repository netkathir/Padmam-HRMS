<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\Setting;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    private const EMPLOYEE_TYPES = ['staff', 'company_labour', 'contract_labour'];

    public function index(Request $request)
    {
        $query = Holiday::orderBy('start_date', 'desc');

        $year = $request->filled('year') ? $request->year : now()->year;
        $query->where(fn($q) => $q->whereYear('start_date', $year)->orWhereYear('end_date', $year));

        $holidays = $query->paginate(20)->withQueryString();
        $years    = range(now()->year - 2, now()->year + 2);
        $sundayPaidCompanyLabour  = Setting::get('holiday', 'sunday_paid_company_labour', true);
        $sundayPaidContractLabour = Setting::get('holiday', 'sunday_paid_contract_labour', true);
        return view('masters.holidays.index', compact('holidays', 'years', 'sundayPaidCompanyLabour', 'sundayPaidContractLabour'));
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
        return view('masters.holidays.create');
    }

    private function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'is_paid'       => ['boolean'],
            'applicable_employee_types'   => ['required', 'array', 'min:1'],
            'applicable_employee_types.*' => ['in:' . implode(',', self::EMPLOYEE_TYPES)],
            'is_active'     => ['required', 'boolean'],
        ];
    }

    /**
     * Duplicate holidays covering the same date range and applicability
     * shall not be allowed. Employee-type applicability can differ per row
     * for an overlapping range, so this checks for any overlap in
     * applicable_employee_types on an existing row whose [start_date,
     * end_date] overlaps the new one, not a flat unique constraint.
     */
    private function assertNoDuplicate(array $data, ?int $ignoreId = null): void
    {
        $existing = Holiday::where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        foreach ($existing as $row) {
            $existingTypes = $row->applicable_employee_types ?? self::EMPLOYEE_TYPES;
            if (array_intersect($existingTypes, $data['applicable_employee_types'])) {
                abort(422, "A holiday already exists overlapping this date range covering one or more of the same employee types (\"{$row->name}\").");
            }
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $this->assertNoDuplicate($data);

        Holiday::create($data);

        return redirect()->route('masters.holidays.index')
            ->with('success', 'Holiday created successfully.');
    }

    public function edit(Holiday $holiday)
    {
        return view('masters.holidays.edit', compact('holiday'));
    }

    public function update(Request $request, Holiday $holiday)
    {
        $data = $request->validate($this->rules());

        $this->assertNoDuplicate($data, $holiday->id);

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
