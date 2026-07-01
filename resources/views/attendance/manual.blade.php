@extends('layouts.app')
@section('title', 'Manual Attendance Entry')
@section('page-title', 'Manual Attendance Entry')
@section('page-subtitle', 'Add or correct individual attendance records')
@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i>
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    <div class="row g-3">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Entry Form</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('attendance.manual.post') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror"
                                required>
                                <option value="">Select employee…</option>
                                @foreach ($employees as $emp)
                                    <option value="{{ $emp->id }}"
                                        {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->employee_code }} — {{ $emp->full_name ?? $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                value="{{ old('date', now()->format('Y-m-d')) }}" required>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                @foreach (['present' => 'Present', 'absent' => 'Absent', 'half_day' => 'Half Day', 'late' => 'Late', 'holiday' => 'Holiday', 'leave' => 'Leave', 'work_from_home' => 'Work From Home'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('status') == $val ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">In Time</label>
                                <input type="time" name="in_time" class="form-control" value="{{ old('in_time') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Out Time</label>
                                <input type="time" name="out_time" class="form-control" value="{{ old('out_time') }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <textarea name="manual_reason" class="form-control" rows="2" required>{{ old('manual_reason') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Save Entry</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Recent Manual Entries</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>In</th>
                                    <th>Out</th>
                                    <th>By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentEntries as $entry)
                                    <tr>
                                        <td>{{ $entry->employee->full_name ?? '—' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</td>
                                        <td><span
                                                class="badge bg-secondary-subtle text-secondary">{{ ucfirst(str_replace('_', ' ', $entry->status)) }}</span>
                                        </td>
                                        <td>{{ $entry->in_time ? \Carbon\Carbon::parse($entry->in_time)->format('H:i') : '—' }}
                                        </td>
                                        <td>{{ $entry->out_time ? \Carbon\Carbon::parse($entry->out_time)->format('H:i') : '—' }}
                                        </td>
                                        <td><small>{{ $entry->employee->full_name ?? '—' }}</small></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No recent manual entries.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
