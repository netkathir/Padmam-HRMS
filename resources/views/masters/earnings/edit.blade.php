@extends('layouts.app')
@section('title','Edit Earning Component')
@section('page-title','Edit Earning Component')
@section('page-subtitle',$earningsComponent->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.earnings.update', $earningsComponent) }}" method="POST">
            @csrf @method('PUT')
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
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $earningsComponent->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    @php $currentStatus = old('is_active', $earningsComponent->is_active ? '1' : '0'); @endphp
                    <select name="is_active" class="form-select @error('is_active') is-invalid @enderror" required>
                        <option value="1" {{ (string) $currentStatus === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ (string) $currentStatus === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.earnings.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
