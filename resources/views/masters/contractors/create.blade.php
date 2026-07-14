@extends('layouts.app')
@section('title','Add Contractor')
@section('page-title','Add Contractor')
@section('page-subtitle','Register a new contractor')
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.contractors.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-12"><h6 class="text-primary border-bottom pb-1">Contractor Details</h6></div>
                <div class="col-md-6">
                    <label class="form-label">Contractor Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Contractor Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" maxlength="150">
                </div>
                @if ($branches->isNotEmpty())
                <div class="col-md-6">
                    <label class="form-label">Primary Branch</label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                        <option value="">— Unassigned (visible to Super Admin only) —</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                @endif

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Contact Details</h6></div>
                <div class="col-md-4">
                    <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                    <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person') }}" required>
                    @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Alternate Number</label>
                    <input type="text" name="alternate_phone" class="form-control @error('alternate_phone') is-invalid @enderror" value="{{ old('alternate_phone') }}">
                    @error('alternate_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Address <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address') }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">State <span class="text-danger">*</span></label>
                    <select name="state" class="form-select @error('state') is-invalid @enderror" required>
                        <option value="">Select State</option>
                        @foreach($states as $st)
                            <option value="{{ $st }}" {{ old('state') == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">District <span class="text-danger">*</span></label>
                    <input type="text" name="district" class="form-control @error('district') is-invalid @enderror" value="{{ old('district') }}" required>
                    @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">PIN Code <span class="text-danger">*</span></label>
                    <input type="text" name="pincode" class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode') }}" maxlength="6" required>
                    @error('pincode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Statutory & Registration</h6></div>
                <div class="col-md-3">
                    <label class="form-label">PAN Number</label>
                    <input type="text" name="pan_number" class="form-control @error('pan_number') is-invalid @enderror" value="{{ old('pan_number') }}" maxlength="10" placeholder="ABCDE1234F">
                    @error('pan_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">GST Number</label>
                    <input type="text" name="gst_number" class="form-control @error('gst_number') is-invalid @enderror" value="{{ old('gst_number') }}" maxlength="15">
                    @error('gst_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">PF Registration Number</label>
                    <input type="text" name="pf_registration_number" class="form-control" value="{{ old('pf_registration_number') }}" maxlength="50">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ESI Registration Number</label>
                    <input type="text" name="esi_registration_number" class="form-control" value="{{ old('esi_registration_number') }}" maxlength="50">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Labour Licence Number</label>
                    <input type="text" name="license_number" class="form-control" value="{{ old('license_number') }}" id="license_number">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Licence Expiry Date <span class="text-muted">(required if licence entered)</span></label>
                    <input type="date" name="license_expiry" class="form-control @error('license_expiry') is-invalid @enderror" value="{{ old('license_expiry') }}">
                    @error('license_expiry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Maximum Labour Count</label>
                    <input type="number" name="max_labour_count" class="form-control" min="0" value="{{ old('max_labour_count') }}">
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Agreement</h6></div>
                <div class="col-md-4">
                    <label class="form-label">Agreement Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="agreement_start_date" class="form-control @error('agreement_start_date') is-invalid @enderror" value="{{ old('agreement_start_date') }}" required>
                    @error('agreement_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Agreement End Date</label>
                    <input type="date" name="agreement_end_date" class="form-control @error('agreement_end_date') is-invalid @enderror" value="{{ old('agreement_end_date') }}">
                    @error('agreement_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Branch Applicability</h6></div>
                <div class="col-md-8">
                    <label class="form-label">Applicable Branches <span class="text-danger">*</span></label>
                    <div class="border rounded p-2 @error('branch_ids') is-invalid @enderror" style="max-height:160px;overflow-y:auto;">
                        @foreach($allBranches as $b)
                        <div class="form-check">
                            <input type="checkbox" name="branch_ids[]" class="form-check-input" id="cbranch_{{ $b->id }}" value="{{ $b->id }}" {{ in_array((string)$b->id, old('branch_ids', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="cbranch_{{ $b->id }}">{{ $b->name }}</label>
                        </div>
                        @endforeach
                    </div>
                    @error('branch_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                <a href="{{ route('masters.contractors.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        <div class="alert alert-info mt-3 mb-0 small">Document upload (agreement, licence, supporting documents) is available after the contractor is saved, from the Edit screen.</div>
    </div>
</div>
@endsection
