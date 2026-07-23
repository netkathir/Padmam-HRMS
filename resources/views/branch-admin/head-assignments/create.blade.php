@extends('layouts.app')

@section('title', 'New Branch Head Assignment')
@section('page-title', 'New Branch Head Assignment')
@section('page-subtitle', 'Branch Administration')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('branch-admin.head-assignments.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    @include('partials._locked_branch_field', [
                        'currentBranch' => $currentBranch,
                        'branchFieldHelp' => 'New assignments are always created for the currently selected branch. Use the Branch Switcher to assign a head to a different branch.',
                    ])
                    <div class="col-md-6">
                        <label class="form-label">Branch Head <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">Select User</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}" {{ (string) old('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', now()->toDateString()) }}" required>
                        @error('effective_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to') }}">
                        @error('effective_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit</button>
                    <a href="{{ route('branch-admin.head-assignments.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
