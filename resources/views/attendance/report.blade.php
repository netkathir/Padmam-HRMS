@extends('layouts.app')
@section('title','Attendance Report')
@section('page-title','Attendance Report')
@section('page-subtitle','Monthly attendance summary')
@section('content')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Month</label>
                <input type="month" name="month" class="form-control" value="{{ request('month', now()->format('Y-m')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Employee</label>
                <select name="employee_id" class="form-select">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name ?? $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Results for <strong>{{ \Carbon\Carbon::parse(request('month', now()->format('Y-m')))->format('F Y') }}</strong></span>
        <a href="#" class="btn btn-sm btn-outline-success disabled"><i class="bi bi-file-earmark-excel"></i> Export</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th class="text-center text-success">Present</th>
                        <th class="text-center text-danger">Absent</th>
                        <th class="text-center text-warning">Late</th>
                        <th class="text-center text-info">Half Day</th>
                        <th class="text-center text-secondary">Leave</th>
                        <th class="text-center text-primary">WFH</th>
                        <th class="text-center">Total Days</th>
                        <th class="text-center">%</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $row)
                    <tr>
                        <td>
                            <strong>{{ $row['employee']->employee_code }}</strong><br>
                            <small class="text-muted">{{ $row['employee']->full_name ?? '—' }}</small>
                        </td>
                        <td><small>{{ $row['employee']->department->name ?? '—' }}</small></td>
                        <td class="text-center text-success fw-bold">{{ $row['present'] }}</td>
                        <td class="text-center text-danger fw-bold">{{ $row['absent'] }}</td>
                        <td class="text-center text-warning fw-bold">{{ $row['late'] }}</td>
                        <td class="text-center text-info fw-bold">{{ $row['half_day'] }}</td>
                        <td class="text-center text-secondary fw-bold">{{ $row['leave'] }}</td>
                        <td class="text-center text-primary fw-bold">{{ $row['wfh'] }}</td>
                        <td class="text-center">{{ $row['total'] }}</td>
                        <td class="text-center">
                            @php $pct = $row['total'] > 0 ? round(($row['present'] / $row['total']) * 100) : 0; @endphp
                            <span class="badge bg-{{ $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning' : 'danger') }}-subtle text-{{ $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning' : 'danger') }}">{{ $pct }}%</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No attendance data for the selected period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
