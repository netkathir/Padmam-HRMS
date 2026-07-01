@extends('layouts.app')
@section('title','Company Settings')
@section('page-title','Company Settings')
@section('page-subtitle','Organisation profile and statutory details')
@section('page-actions')
    <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Settings</a>
@endsection
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="card">
    <div class="card-body">
        <form action="{{ route('settings.company.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-12"><h6 class="text-primary border-bottom pb-1">Basic Information</h6></div>
                <div class="col-md-6">
                    <label class="form-label">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name', $settings['company_name'] ?? '') }}" required>
                    @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Short Name</label>
                    <input type="text" name="company_short_name" class="form-control" value="{{ old('company_short_name', $settings['company_short_name'] ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Logo</label>
                    @if(!empty($settings['company_logo']))
                    <div class="mb-1"><img src="{{ Storage::url($settings['company_logo']) }}" height="40" alt="Logo"></div>
                    @endif
                    <input type="file" name="company_logo" class="form-control" accept="image/*">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Address</label>
                    <textarea name="company_address" class="form-control" rows="2">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="company_city" class="form-control" value="{{ old('company_city', $settings['company_city'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <input type="text" name="company_state" class="form-control" value="{{ old('company_state', $settings['company_state'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="company_pincode" class="form-control" value="{{ old('company_pincode', $settings['company_pincode'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="company_phone" class="form-control" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="company_email" class="form-control" value="{{ old('company_email', $settings['company_email'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Website</label>
                    <input type="url" name="company_website" class="form-control" value="{{ old('company_website', $settings['company_website'] ?? '') }}">
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Statutory Information</h6></div>
                <div class="col-md-4">
                    <label class="form-label">PAN Number</label>
                    <input type="text" name="company_pan" class="form-control" value="{{ old('company_pan', $settings['company_pan'] ?? '') }}" maxlength="10">
                </div>
                <div class="col-md-4">
                    <label class="form-label">TAN Number</label>
                    <input type="text" name="company_tan" class="form-control" value="{{ old('company_tan', $settings['company_tan'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">GSTIN</label>
                    <input type="text" name="company_gstin" class="form-control" value="{{ old('company_gstin', $settings['company_gstin'] ?? '') }}" maxlength="15">
                </div>
                <div class="col-md-4">
                    <label class="form-label">PF Registration No.</label>
                    <input type="text" name="pf_registration_no" class="form-control" value="{{ old('pf_registration_no', $settings['pf_registration_no'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ESI Registration No.</label>
                    <input type="text" name="esi_registration_no" class="form-control" value="{{ old('esi_registration_no', $settings['esi_registration_no'] ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">PT Registration No.</label>
                    <input type="text" name="pt_registration_no" class="form-control" value="{{ old('pt_registration_no', $settings['pt_registration_no'] ?? '') }}">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                <a href="{{ route('settings.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
