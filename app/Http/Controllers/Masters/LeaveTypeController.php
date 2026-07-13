<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    private const EMPLOYEE_TYPES = ['staff', 'company_labour', 'contract_labour'];

    public function index(Request $request)
    {
        $query = LeaveType::orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }

        $leaveTypes = $query->paginate(20)->withQueryString();
        return view('masters.leave-types.index', compact('leaveTypes'));
    }

    public function create()
    {
        return view('masters.leave-types.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'code'                => ['required', 'string', 'max:20', 'unique:leave_types,code'],
            'days_per_year'       => ['required', 'numeric', 'min:0', 'max:365'],
            'max_carry_forward'   => ['nullable', 'numeric', 'min:0', 'max:365'],
            'is_carry_forward'    => ['boolean'],
            'is_paid'             => ['boolean'],
            'is_half_day_allowed' => ['boolean'],
            'gender_specific'     => ['required', 'in:all,male,female'],
            'requires_document'   => ['boolean'],
            'min_notice_days'     => ['nullable', 'integer', 'min:0'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:0'],
            'applicable_employee_types'   => ['required', 'array', 'min:1'],
            'applicable_employee_types.*' => ['in:' . implode(',', self::EMPLOYEE_TYPES)],
            'is_active'           => ['boolean'],
        ]);

        LeaveType::create($data);

        return redirect()->route('masters.leave-types.index')
            ->with('success', 'Leave type created successfully.');
    }

    public function edit(LeaveType $leaveType)
    {
        return view('masters.leave-types.edit', compact('leaveType'));
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'code'                => ['required', 'string', 'max:20', 'unique:leave_types,code,' . $leaveType->id],
            'days_per_year'       => ['required', 'numeric', 'min:0', 'max:365'],
            'max_carry_forward'   => ['nullable', 'numeric', 'min:0', 'max:365'],
            'is_carry_forward'    => ['boolean'],
            'is_paid'             => ['boolean'],
            'is_half_day_allowed' => ['boolean'],
            'gender_specific'     => ['required', 'in:all,male,female'],
            'requires_document'   => ['boolean'],
            'min_notice_days'     => ['nullable', 'integer', 'min:0'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:0'],
            'applicable_employee_types'   => ['required', 'array', 'min:1'],
            'applicable_employee_types.*' => ['in:' . implode(',', self::EMPLOYEE_TYPES)],
            'is_active'           => ['boolean'],
        ]);

        $leaveType->update($data);

        return redirect()->route('masters.leave-types.index')
            ->with('success', 'Leave type updated successfully.');
    }

    public function destroy(LeaveType $leaveType)
    {
        if ($leaveType->leaveRequests()->exists()) {
            return back()->with('error', 'Cannot delete leave type with associated leave requests.');
        }
        if ($leaveType->balances()->exists()) {
            return back()->with('error', 'Cannot delete leave type with associated leave balances.');
        }
        $leaveType->delete();
        return redirect()->route('masters.leave-types.index')
            ->with('success', 'Leave type deleted successfully.');
    }
}
