@extends('layouts.app')
@section('title', 'Contract Attendance')
@section('page-title', 'Contract Attendance')
@section('page-subtitle', 'View daily attendance of employees under contractors')

@section('page-actions')
    <a href="{{ route('contract-attendance.mark') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil-square"></i> Mark Attendance
    </a>
    <a href="{{ route('contract-attendance.report') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-file-earmark-bar-graph"></i> Monthly Report
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">

        {{-- Filters --}}
        <form method="GET" class="row g-2 mb-3">
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
                <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="present"  {{ request('status') == 'present'  ? 'selected' : '' }}>Present</option>
                    <option value="absent"   {{ request('status') == 'absent'   ? 'selected' : '' }}>Absent</option>
                    <option value="half_day" {{ request('status') == 'half_day' ? 'selected' : '' }}>Half Day</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> View</button>
            </div>
        </form>

        @if($contractor)
            {{-- Summary Badges --}}
            <div class="row g-2 mb-3">
                @foreach(['present' => ['success','Present'], 'absent' => ['danger','Absent'], 'half_day' => ['warning','Half Day']] as $st => [$color, $label])
                    <div class="col-auto">
                        <div class="px-3 py-2 rounded bg-{{ $color }}-subtle text-center" style="min-width:90px">
                            <div class="fw-bold fs-5 text-{{ $color }}">{{ $summary[$st] ?? 0 }}</div>
                            <small class="text-muted">{{ $label }}</small>
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
                            <th>OT</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendance as $att)
                            <tr>
                                <td>{{ $attendance->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">{{ optional($att->employee)->full_name ?? '—' }}</td>
                                <td><small class="text-muted">{{ optional($att->employee->department)->name ?? '—' }}</small></td>
                                <td>{{ $att->in_time ? \Carbon\Carbon::parse($att->in_time)->format('H:i') : '—' }}</td>
                                <td>{{ $att->out_time ? \Carbon\Carbon::parse($att->out_time)->format('H:i') : '—' }}</td>
                                <td>{{ $att->ot_minutes > 0 ? number_format($att->ot_minutes / 60, 1) . 'h' : '—' }}</td>
                                <td>
                                    @php $c = ['present'=>'success','absent'=>'danger','half_day'=>'warning']; @endphp
                                    <span class="badge bg-{{ $c[$att->status] ?? 'secondary' }}-subtle text-{{ $c[$att->status] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                                    </span>
                                </td>
                                <td><small class="text-muted">{{ $att->remarks ?? '—' }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No attendance records for {{ $contractor->name }} on {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">{{ $attendance->links() }}</div>
        @else
            <div class="text-center text-muted py-5">
                <i class="bi bi-calendar-check fs-1 d-block mb-2 opacity-50"></i>
                Select a contractor and date to view attendance.
            </div>
        @endif
    </div>
</div>
@endsection
