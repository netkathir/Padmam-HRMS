@extends('layouts.app')
@section('title','Add OT Rate')
@section('page-title','Add Overtime Rate')
@section('page-subtitle','Define a new overtime multiplier')
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.ot-rates.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Double OT, Holiday OT" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Multiplier <span class="text-danger">*</span></label>
                    <input type="number" name="multiplier" step="0.25" min="1" class="form-control @error('multiplier') is-invalid @enderror" value="{{ old('multiplier', 1.5) }}" required>
                    @error('multiplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">e.g. 1.5 = 1.5x normal rate</div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active','1')=='1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                <a href="{{ route('masters.ot-rates.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
