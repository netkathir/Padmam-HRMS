@extends('layouts.app')
@section('title','Add Earning Component')
@section('page-title','Add Earning Component')
@section('page-subtitle','Define a new salary earning head')
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.earnings.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                @if($currentBranch)
                    <input type="hidden" name="branch_id" value="{{ $currentBranch->id }}">
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" value="{{ $currentBranch->name }}" disabled>
                    </div>
                @endif
                <div class="col-md-6">
                    <label class="form-label">Earning Component Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit</button>
                <a href="{{ route('masters.earnings.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
