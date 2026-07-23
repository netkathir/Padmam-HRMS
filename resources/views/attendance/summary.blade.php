@extends('layouts.app')
@section('title','Attendance Summary')
@section('page-title','Attendance Summary')
@section('page-subtitle','Per-employee period summary — attendance, OT, and leave-type breakdown')
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
        <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Export</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Shift</th>
                        <th class="text-center">OT Hrs</th>
                        <th class="text-center">Total Working Days</th>
                        <th class="text-center text-success">Present</th>
                        <th class="text-center text-danger">Absent</th>
                        @foreach($leaveTypes as $lt)
                        <th class="text-center">{{ $lt->name }}</th>
                        @endforeach
                        <th class="text-center">Weekend</th>
                        <th class="text-center">Public Holiday</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary as $row)
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
                        @foreach($leaveTypes as $lt)
                        <td class="text-center">{{ $row['leave_counts'][$lt->id] ?? 0 }}</td>
                        @endforeach
                        <td class="text-center">{{ $row['weekend'] }}</td>
                        <td class="text-center">{{ $row['public_holiday'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ 8 + count($leaveTypes) }}" class="text-center text-muted py-4">No attendance data for the selected period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
