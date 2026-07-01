@extends('layouts.app')
@section('title', 'Mark Contract Attendance')
@section('page-title', 'Mark Contract Attendance')
@section('page-subtitle', 'Record daily attendance for employees under a contractor')

@section('page-actions')
    <a href="{{ route('contract-attendance.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> View Attendance
    </a>
@endsection

@section('content')

{{-- Select Contractor & Date --}}
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-funnel"></i> Select Contractor &amp; Date</div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Contractor <span class="text-danger">*</span></label>
                <select name="contractor_id" class="form-select" required>
                    <option value="">— Select Contractor —</option>
                    @foreach($contractors as $c)
                        <option value="{{ $c->id }}" {{ optional($contractor)->id == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control" value="{{ $date }}" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-people"></i> Load Workers
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Attendance Table --}}
@if($contractor)

    @if($workers->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-person-x fs-1 d-block mb-2 opacity-50"></i>
                <div class="fw-semibold mb-1">No active employees assigned to {{ $contractor->name }}</div>
                <small>Assign employees to this contractor first via
                    <a href="{{ route('contract-labour.index', ['contractor_id' => $contractor->id]) }}">Contract Labour Assignment</a>.
                </small>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-pencil-square"></i>
                    Attendance for <strong>{{ $contractor->name }}</strong>
                    on <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong>
                </span>
                <span class="badge bg-primary">{{ $workers->count() }} Employees</span>
            </div>
            <div class="card-body p-0">
                <form action="{{ route('contract-attendance.mark.post') }}" method="POST">
                    @csrf
                    <input type="hidden" name="contractor_id" value="{{ $contractor->id }}">
                    <input type="hidden" name="date" value="{{ $date }}">

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th style="width:220px">Status <span class="text-danger">*</span></th>
                                    <th style="width:120px">In Time</th>
                                    <th style="width:120px">Out Time</th>
                                    <th style="width:100px">OT Hrs</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workers as $worker)
                                    @php $existing = $existingMap->get($worker->id); @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $worker->full_name }}</div>
                                            <small class="text-muted">{{ $worker->employee_code }}</small>
                                        </td>
                                        <td><small class="text-muted">{{ optional($worker->department)->name ?? '—' }}</small></td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
                                                @foreach(['present' => ['success','P'], 'half_day' => ['warning','H'], 'absent' => ['danger','A']] as $val => [$color, $lbl])
                                                    <div class="form-check form-check-inline mb-0">
                                                        <input class="form-check-input" type="radio"
                                                            name="attendance[{{ $worker->id }}][status]"
                                                            id="st_{{ $worker->id }}_{{ $val }}"
                                                            value="{{ $val }}"
                                                            {{ old("attendance.{$worker->id}.status", optional($existing)->status ?? 'absent') == $val ? 'checked' : '' }}
                                                            required>
                                                        <label class="form-check-label text-{{ $color }} fw-semibold"
                                                               for="st_{{ $worker->id }}_{{ $val }}">{{ $lbl }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <input type="time" name="attendance[{{ $worker->id }}][in_time]"
                                                   class="form-control form-control-sm"
                                                   value="{{ old("attendance.{$worker->id}.in_time", $existing && $existing->in_time ? \Carbon\Carbon::parse($existing->in_time)->format('H:i') : '') }}">
                                        </td>
                                        <td>
                                            <input type="time" name="attendance[{{ $worker->id }}][out_time]"
                                                   class="form-control form-control-sm"
                                                   value="{{ old("attendance.{$worker->id}.out_time", $existing && $existing->out_time ? \Carbon\Carbon::parse($existing->out_time)->format('H:i') : '') }}">
                                        </td>
                                        <td>
                                            <input type="number" name="attendance[{{ $worker->id }}][ot_hours]"
                                                   class="form-control form-control-sm" step="0.5" min="0" max="24"
                                                   value="{{ old("attendance.{$worker->id}.ot_hours", $existing ? number_format($existing->ot_minutes / 60, 1) : 0) }}">
                                        </td>
                                        <td>
                                            <input type="text" name="attendance[{{ $worker->id }}][remarks]"
                                                   class="form-control form-control-sm"
                                                   value="{{ old("attendance.{$worker->id}.remarks", $existing->remarks ?? '') }}"
                                                   placeholder="Optional">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 border-top d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Attendance
                        </button>
                        <a href="{{ route('contract-attendance.index', ['contractor_id' => $contractor->id, 'date' => $date]) }}"
                           class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endif

@endsection
