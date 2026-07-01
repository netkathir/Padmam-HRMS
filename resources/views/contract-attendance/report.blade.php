@extends('layouts.app')
@section('title', 'Contract Attendance Report')
@section('page-title', 'Contract Attendance Report')
@section('page-subtitle', 'Monthly attendance summary for contractor employees')

@section('page-actions')
    <a href="{{ route('contract-attendance.mark') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-pencil-square"></i> Mark Attendance
    </a>
    <a href="{{ route('contract-attendance.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-calendar-check"></i> Daily View
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-3">
                <select name="contractor_id" class="form-select form-select-sm">
                    <option value="">— Select Contractor —</option>
                    @foreach($contractors as $c)
                        <option value="{{ $c->id }}" {{ request('contractor_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="month" class="form-select form-select-sm">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="year" class="form-select form-select-sm">
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Generate</button>
            </div>
        </form>

        @if($contractor)
            <div class="mb-3">
                <h6 class="fw-semibold">{{ $contractor->name }} — {{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</h6>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th class="text-success">Present</th>
                            <th class="text-warning">Half Day</th>
                            <th class="text-danger">Absent</th>
                            <th>OT Hours</th>
                            <th>Effective Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $i = 1; @endphp
                        @forelse($workers as $wId => $worker)
                            @php
                                $row      = $workerRows->get($wId);
                                $present  = (int) optional($row)->present_count;
                                $halfDay  = (int) optional($row)->half_day_count;
                                $absent   = (int) optional($row)->absent_count;
                                $otHours  = (float) optional($row)->total_ot_hours;
                                $effective = $present + ($halfDay * 0.5);
                            @endphp
                            <tr>
                                <td>{{ $i++ }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $worker->full_name }}</div>
                                    <small class="text-muted">{{ $worker->employee_code }}</small>
                                </td>
                                <td><small class="text-muted">{{ optional($worker->department)->name ?? '—' }}</small></td>
                                <td><span class="badge bg-success-subtle text-success">{{ $present }}</span></td>
                                <td><span class="badge bg-warning-subtle text-warning">{{ $halfDay }}</span></td>
                                <td><span class="badge bg-danger-subtle text-danger">{{ $absent }}</span></td>
                                <td>{{ $otHours > 0 ? number_format($otHours, 1) . 'h' : '—' }}</td>
                                <td class="fw-semibold">{{ number_format($effective, 1) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No employees assigned to this contractor, or no attendance records for this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center text-muted py-5">
                <i class="bi bi-file-earmark-bar-graph fs-1 d-block mb-2 opacity-50"></i>
                Select a contractor, month, and year to generate the report.
            </div>
        @endif
    </div>
</div>
@endsection
