@extends('layouts.app')
@section('title','Add Holiday')
@section('page-title','Add Holiday')
@section('page-subtitle','Create a new holiday entry')
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.holidays.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Holiday Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date') }}" required>
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="national" {{ old('type') == 'national' ? 'selected' : '' }}>National</option>
                        <option value="regional" {{ old('type') == 'regional' ? 'selected' : '' }}>Regional</option>
                        <option value="optional" {{ old('type') == 'optional' ? 'selected' : '' }}>Optional</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Branch {{ $lockedBranchId ? '' : '(leave blank for all)' }}</label>
                    <select name="branch_id" class="form-select" {{ $lockedBranchId ? 'disabled' : '' }}>
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) old('branch_id', $lockedBranchId) === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    @if ($lockedBranchId)
                        <input type="hidden" name="branch_id" value="{{ $lockedBranchId }}">
                        <div class="form-text">Locked to your assigned branch.</div>
                    @endif
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                <a href="{{ route('masters.holidays.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
