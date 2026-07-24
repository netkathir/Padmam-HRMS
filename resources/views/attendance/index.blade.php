@extends('layouts.app')

@section('title', 'Attendance Register')
@section('page-title', 'Attendance Register')
@section('page-subtitle', 'Per-employee attendance summary for the selected date range')

@section('page-actions')
    <a href="{{ route('attendance.upload.form') }}" class="btn btn-primary btn-sm"><i class="bi bi-upload"></i> Biometric Upload</a>
    <a href="{{ route('attendance.correction.form') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil-square"></i> Correction</a>
    <a href="{{ route('attendance.department-work.form') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-diagram-3"></i> Department Work</a>
    <a href="{{ route('attendance.mark') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus-lg"></i> Mark Attendance</a>
    <a href="{{ route('attendance.report') }}" class="btn btn-outline-info btn-sm"><i class="bi bi-file-text"></i> Report</a>
    <a href="{{ route('attendance.summary') }}" class="btn btn-outline-info btn-sm"><i class="bi bi-bar-chart-line"></i> Summary</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <label class="form-label small mb-1">From <span class="text-danger">*</span></label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $from }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">To <span class="text-danger">*</span></label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $to }}" required>
                </div>
                @if ($currentBranchId === null)
                <div class="col-md-2">
                    <label class="form-label small mb-1">Branch</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label small mb-1">Employee Number</label>
                    <input type="text" name="employee_number" class="form-control form-control-sm" value="{{ request('employee_number') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Employee Name</label>
                    <input type="text" name="employee_name" class="form-control form-control-sm" value="{{ request('employee_name') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Employee Type</label>
                    <select name="employee_type_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($employeeTypes as $et)
                            <option value="{{ $et->id }}" {{ request('employee_type_id') == $et->id ? 'selected' : '' }}>{{ $et->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Labour Type</label>
                    <select name="labour_type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="company_labour" {{ request('labour_type') == 'company_labour' ? 'selected' : '' }}>Company Labour</option>
                        <option value="contract_labour" {{ request('labour_type') == 'contract_labour' ? 'selected' : '' }}>Contract Labour</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Contractor</label>
                    <select name="contractor_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($contractors as $c)
                            <option value="{{ $c->id }}" {{ request('contractor_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Shift</label>
                    <select name="shift_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($shifts as $s)
                            <option value="{{ $s->id }}" {{ request('shift_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                </div>
            </form>

            <div class="d-flex justify-content-end gap-2 mb-2">
                <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Shift</th>
                            <th class="text-center">OT Hrs</th>
                            <th class="text-center">Total Working Days</th>
                            <th class="text-center text-success">Total Days Present</th>
                            <th class="text-center text-danger">Total Days Absent</th>
                            <th class="text-center text-danger">Absent Leave</th>
                            @foreach($leaveTypeNames as $name)
                            <th class="text-center">{{ $name }}</th>
                            @endforeach
                            <th class="text-center">Weekend Leave</th>
                            <th class="text-center">Public Leave</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendance as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['employee']->employee_code }}</strong><br>
                                    <small class="text-muted">{{ $row['employee']->full_name ?? '—' }}</small>
                                </td>
                                <td><small>{{ $row['shift']->name ?? '—' }}</small></td>
                                <td class="text-center">{{ $row['ot_hours'] }}</td>
                                <td class="text-center">{{ $row['working_days'] }}</td>
                                <td class="text-center text-success fw-bold">{{ $row['present'] }}</td>
                                <td class="text-center text-danger fw-bold">{{ $row['absent'] }}</td>
                                <td class="text-center text-danger fw-bold">{{ $row['absent_leave'] }}</td>
                                @foreach($leaveTypeNames as $name)
                                <td class="text-center">{{ $row['leave_counts'][$name] ?? 0 }}</td>
                                @endforeach
                                <td class="text-center">{{ $row['weekend'] }}</td>
                                <td class="text-center">{{ $row['public_holiday'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 9 + count($leaveTypeNames) }}" class="text-center text-muted py-4">No attendance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
