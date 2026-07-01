@extends('layouts.app')

@section('title', 'Attendance')
@section('page-title', 'Attendance')
@section('page-subtitle', 'Daily attendance records')

@section('page-actions')
    <a href="{{ route('attendance.mark') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Mark Attendance</a>
    <a href="{{ route('attendance.manual') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Manual
        Entry</a>
    <a href="{{ route('attendance.report') }}" class="btn btn-outline-info btn-sm"><i class="bi bi-file-text"></i> Report</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control form-control-sm"
                        value="{{ request('date', $date) }}">
                </div>
                <div class="col-md-3">
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}"
                                {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                        <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                        <option value="half_day" {{ request('status') == 'half_day' ? 'selected' : '' }}>Half Day</option>
                        <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                        <option value="on_leave" {{ request('status') == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                </div>
            </form>

            <div class="row g-2 mb-3">
                @foreach (['present', 'absent', 'half_day', 'late', 'on_leave'] as $st)
                    @php $cnt = $summary[$st] ?? 0; @endphp
                    <div class="col">
                        <div
                            class="text-center p-2 rounded bg-{{ $st == 'present' ? 'success' : ($st == 'absent' ? 'danger' : ($st == 'half_day' ? 'warning' : ($st == 'late' ? 'info' : 'secondary'))) }}-subtle">
                            <div class="fw-bold">{{ $cnt }}</div>
                            <small>{{ ucfirst(str_replace('_', ' ', $st)) }}</small>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Work Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendance as $att)
                            <tr>
                                <td>{{ $attendance->firstItem() + $loop->index }}</td>
                                <td>{{ $att->employee->full_name ?? '—' }}</td>
                                <td>{{ $att->employee->department->name ?? '—' }}</td>
                                <td>{{ $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('H:i') : '—' }}</td>
                                <td>{{ $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('H:i') : '—' }}</td>
                                <td>{{ $att->work_minutes ? round($att->work_minutes / 60, 1) . 'h' : '—' }}</td>
                                <td>
                                    @php $colors = ['present'=>'success','absent'=>'danger','half_day'=>'warning','late'=>'info','on_leave'=>'secondary','holiday'=>'primary','weekend'=>'dark']; @endphp
                                    <span
                                        class="badge bg-{{ $colors[$att->status] ?? 'secondary' }}-subtle text-{{ $colors[$att->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $att->status)) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No attendance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">{{ $attendance->links() }}</div>
        </div>
    </div>
@endsection
