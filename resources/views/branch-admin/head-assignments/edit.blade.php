@extends('layouts.app')

@section('title', 'Edit Branch Head Assignment')
@section('page-title', 'Edit Assignment')
@section('page-subtitle', 'Branch Administration — ' . ($assignment->branch->name ?? '') . ' / ' . ($assignment->user->name ?? ''))

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('branch-admin.head-assignments.update', $assignment) }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" value="{{ $assignment->branch->name ?? '' }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Branch Head</label>
                        <input type="text" class="form-control" value="{{ $assignment->user->name ?? '' }}" disabled>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', $assignment->effective_from?->format('Y-m-d')) }}" required>
                        @error('effective_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to', $assignment->effective_to?->format('Y-m-d')) }}">
                        @error('effective_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select">
                            <option value="active" {{ old('status', $assignment->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $assignment->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $assignment->remarks) }}</textarea>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                    <a href="{{ route('branch-admin.head-assignments.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
