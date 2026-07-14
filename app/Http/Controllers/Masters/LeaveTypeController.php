<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    private function rules(?int $leaveTypeId = null): array
    {
        return [
            'name'                         => ['required', 'string', 'max:100', Rule::unique('leave_types', 'name')->ignore($leaveTypeId)],
            'code'                         => ['required', 'string', 'max:20', Rule::unique('leave_types', 'code')->ignore($leaveTypeId)],
            'applicable_employee_types'    => ['required', 'array', 'min:1'],
            'applicable_employee_types.*'  => ['in:' . implode(',', self::EMPLOYEE_TYPES)],
            'is_paid'                      => ['required', 'boolean'],
            'is_active'                    => ['required', 'boolean'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

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
        $data = $request->validate($this->rules($leaveType->id));

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
