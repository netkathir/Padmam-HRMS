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
                    <div class="form-text">Contractor Code is generated automatically on save.</div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label">Status (Active) <span class="text-danger">*</span></label>
                    </div>
                    @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Contact Details</h6></div>
                <div class="col-md-4">
                    <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                    <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person') }}" required>
                    @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required inputmode="numeric" pattern="[0-9]{10}" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Alternate Number</label>
                    <input type="text" name="alternate_phone" class="form-control @error('alternate_phone') is-invalid @enderror" value="{{ old('alternate_phone') }}" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
                    @error('alternate_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">State <span class="text-muted">(required if address entered)</span></label>
                    <select name="state" class="form-select @error('state') is-invalid @enderror">
                        <option value="">Select State</option>
                        @foreach($states as $st)
                            <option value="{{ $st }}" {{ old('state') == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">District <span class="text-muted">(required if address entered)</span></label>
                    <input type="text" name="district" class="form-control @error('district') is-invalid @enderror" value="{{ old('district') }}">
                    @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">PIN Code <span class="text-muted">(required if address entered)</span></label>
                    <input type="text" name="pincode" class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode') }}" maxlength="6">
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
