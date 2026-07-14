<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Shift;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\EarningsComponent;
use App\Models\DeductionsComponent;
use App\Models\PfEsiConfig;
use App\Models\Bank;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function index()
    {
        return view('masters.index', [
            'branchCount'      => Branch::count(),
            'deptCount'        => Department::count(),
            'desigCount'       => Designation::count(),
            'shiftCount'       => Shift::count(),
            'holidayCount'     => Holiday::where(fn($q) => $q->whereYear('start_date', now()->year)->orWhereYear('end_date', now()->year))->count(),
            'leaveTypeCount'   => LeaveType::count(),
            'earningsCount'    => EarningsComponent::count(),
            'deductionsCount'  => DeductionsComponent::count(),
            'bankCount'        => Bank::count(),
        ]);
    }
}
