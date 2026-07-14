@extends('layouts.app')
@section('title','Edit Shift')
@section('page-title','Edit Shift')
@section('page-subtitle',$shift->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.shifts.update', $shift) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $shift->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Code</label>
                    <input type="text" class="form-control" value="{{ $shift->code }}" disabled>
                    <div class="form-text">Auto-generated — not editable.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Work Hours</label>
                    <input type="number" step="0.5" name="work_hours" class="form-control" value="{{ old('work_hours', $shift->work_hours) }}" min="0" max="24">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Time <span class="text-danger">*</span></label>
                    <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', $shift->start_time) }}" required>
                    @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Time <span class="text-danger">*</span></label>
                    <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time', $shift->end_time) }}" required>
                    @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Break (minutes)</label>
                    <input type="number" name="break_minutes" class="form-control" value="{{ old('break_minutes', $shift->break_minutes) }}" min="0" max="480">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grace – Late Entry (minutes)</label>
                    <input type="number" name="grace_late_entry_minutes" class="form-control @error('grace_late_entry_minutes') is-invalid @enderror" value="{{ old('grace_late_entry_minutes', $shift->grace_late_entry_minutes) }}" min="0">
                    @error('grace_late_entry_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Grace – Early Exit (minutes)</label>
                    <input type="number" name="grace_early_exit_minutes" class="form-control @error('grace_early_exit_minutes') is-invalid @enderror" value="{{ old('grace_early_exit_minutes', $shift->grace_early_exit_minutes) }}" min="0">
                    @error('grace_early_exit_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 d-flex gap-4">
                    <div class="form-check">
                        <input type="hidden" name="is_overnight" value="0">
                        <input type="checkbox" name="is_overnight" class="form-check-input" value="1" {{ old('is_overnight', $shift->is_overnight) ? 'checked' : '' }}>
                        <label class="form-check-label">Overnight Shift</label>
                    </div>
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $shift->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Active <span class="text-danger">*</span></label>
                    </div>
                    @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Branch Applicability <span class="text-danger">*</span></label>
                    @php $selectedBranches = old('branch_ids', $shift->branches->pluck('id')->map(fn($id)=>(string)$id)->all()); @endphp
                    <div class="border rounded p-2 @error('branch_ids') is-invalid @enderror" style="max-height:160px;overflow-y:auto;">
                        @foreach($branches as $b)
                        <div class="form-check">
                            <input type="checkbox" name="branch_ids[]" class="form-check-input" id="branch_{{ $b->id }}" value="{{ $b->id }}" {{ in_array((string)$b->id, $selectedBranches) ? 'checked' : '' }}>
                            <label class="form-check-label" for="branch_{{ $b->id }}">{{ $b->name }}</label>
                        </div>
                        @endforeach
                    </div>
                    @error('branch_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Employee Type Applicability <span class="text-danger">*</span></label>
                    @php $selectedTypes = old('applicable_employee_types', $shift->applicable_employee_types ?? []); @endphp
                    <div class="border rounded p-2 @error('applicable_employee_types') is-invalid @enderror">
                        @foreach(['staff'=>'Staff','company_labour'=>'Company Labour','contract_labour'=>'Contract Labour'] as $val=>$label)
                        <div class="form-check">
                            <input type="checkbox" name="applicable_employee_types[]" class="form-check-input" id="etype_{{ $val }}" value="{{ $val }}" {{ in_array($val, $selectedTypes) ? 'checked' : '' }}>
                            <label class="form-check-label" for="etype_{{ $val }}">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                    @error('applicable_employee_types')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.shifts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
