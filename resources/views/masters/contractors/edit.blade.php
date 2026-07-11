@extends('layouts.app')
@section('title','Edit Contractor')
@section('page-title','Edit Contractor')
@section('page-subtitle',$contractor->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.contractors.update', $contractor) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Contractor Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $contractor->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $contractor->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $contractor->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                @if ($branches->isNotEmpty())
                <div class="col-md-6">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                        <option value="">— Unassigned (visible to Super Admin only) —</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) old('branch_id', $contractor->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $contractor->contact_person) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $contractor->phone) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $contractor->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">License Number</label>
                    <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $contractor->license_number) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">License Expiry</label>
                    <input type="date" name="license_expiry" class="form-control" value="{{ old('license_expiry', optional($contractor->license_expiry)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $contractor->company_name) }}" maxlength="150">
                </div>
                <div class="col-md-3">
                    <label class="form-label">GST Number</label>
                    <input type="text" name="gst_number" class="form-control" value="{{ old('gst_number', $contractor->gst_number) }}" maxlength="20">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $contractor->address) }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.contractors.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
