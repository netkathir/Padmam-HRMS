@extends('layouts.app')
@section('title', 'Mark Attendance')
@section('page-title', 'Mark Attendance')
@section('page-subtitle', 'Today: ' . now()->format('d M Y, l'))
@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i>
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Employee Attendance — {{ now()->format('d M Y') }}</h6>
            <form method="GET" class="d-flex gap-2">
                <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-body p-0">
            <form action="{{ route('attendance.mark.post') }}" method="POST">
                @csrf
                <input type="hidden" name="date" value="{{ now()->format('Y-m-d') }}">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Shift</th>
                                <th>Status</th>
                                <th>In Time</th>
                                <th>Out Time</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employees as $emp)
                                @php
                                    $today = $emp
                                        ->attendance()
                                        ->where('date', now()->toDateString())
                                        ->first();
                                @endphp
                                <tr>
                                    <td>
                                        <input type="hidden" name="attendance[{{ $loop->index }}][employee_id]"
                                            value="{{ $emp->id }}">
                                        <strong>{{ $emp->employee_code }}</strong><br>
                                        <small class="text-muted">{{ $emp->full_name ?? $emp->name }}</small>
                                    </td>
                                    <td><small>{{ $emp->department->name ?? '—' }}</small></td>
                                    <td><small>{{ $emp->shift->name ?? '—' }}</small></td>
                                    <td>
                                        <select name="attendance[{{ $loop->index }}][status]"
                                            class="form-select form-select-sm">
                                            @foreach (['present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half Day', 'late' => 'Late', 'holiday' => 'Holiday', 'leave' => 'Leave', 'work_from_home' => 'WFH'] as $val => $label)
                                                <option value="{{ $val }}"
                                                    {{ old("attendance.{$loop->index}.status", $today->status ?? 'present') == $val ? 'selected' : '' }}>
                                                    {{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="time" name="attendance[{{ $loop->index }}][in_time]"
                                            class="form-control form-control-sm"
                                            value="{{ old("attendance.{$loop->index}.in_time", $today->in_time ?? '') }}">
                                    </td>
                                    <td><input type="time" name="attendance[{{ $loop->index }}][out_time]"
                                            class="form-control form-control-sm"
                                            value="{{ old("attendance.{$loop->index}.out_time", $today->out_time ?? '') }}">
                                    </td>
                                    <td><input type="text" name="attendance[{{ $loop->index }}][remarks]"
                                            class="form-control form-control-sm"
                                            value="{{ old("attendance.{$loop->index}.remarks", $today->remarks ?? '') }}"
                                            placeholder="Optional"></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No employees found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($employees->count())
                    <div class="p-3 border-top">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-all"></i> Save
                            Attendance</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
@endsection
