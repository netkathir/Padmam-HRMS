@extends('layouts.app')
@section('title','Organization Profile')
@section('page-title','Organization Profile')
@section('page-subtitle','Organization identity and statutory details — used on reports, payslips, and statutory documents')
@section('page-actions')
    <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Settings</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('settings.company.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-12"><h6 class="text-primary border-bottom pb-1">Basic Information</h6></div>
                <div class="col-md-5">
                    <label class="form-label">Organization Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $company->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Organization Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $company->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Short Name</label>
                    <input type="text" name="short_name" class="form-control" value="{{ old('short_name', $company->short_name) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="is_active" class="form-select">
                        <option value="1" {{ old('is_active', $company->is_active ?? true) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', $company->is_active ?? true) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Logo</label>
                    @if($company->logo_path)
                        <div class="mb-1"><img src="{{ Storage::url($company->logo_path) }}" height="40" alt="Logo"></div>
                    @endif
                    <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                    @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-12">
                    <label class="form-label">Registered Address <span class="text-danger">*</span></label>
                    <textarea id="registeredAddress" name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address', $company->address) }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="form-label mb-0">Communication Address</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sameAsRegistered">
                            <label class="form-check-label small" for="sameAsRegistered">Same as Registered Address</label>
                        </div>
                    </div>
                    <textarea id="communicationAddress" name="communication_address" class="form-control" rows="2">{{ old('communication_address', $company->communication_address) }}</textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">State <span class="text-danger">*</span></label>
                    <select name="state" class="form-select @error('state') is-invalid @enderror" required>
                        <option value="">Select State</option>
                        @foreach ($states as $state)
                            <option value="{{ $state }}" {{ old('state', $company->state) === $state ? 'selected' : '' }}>{{ $state }}</option>
                        @endforeach
                    </select>
                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">District <span class="text-danger">*</span></label>
                    <input type="text" name="district" class="form-control @error('district') is-invalid @enderror" value="{{ old('district', $company->district) }}" required>
                    @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $company->city) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">PIN Code <span class="text-danger">*</span></label>
                    <input type="text" name="pincode" inputmode="numeric" maxlength="6" class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode', $company->pincode) }}" required>
                    @error('pincode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $company->phone) }}">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $company->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control" value="{{ old('website', $company->website) }}">
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Statutory Information</h6></div>
                <div class="col-md-4">
                    <label class="form-label">PAN Number</label>
                    <input type="text" name="pan" class="form-control @error('pan') is-invalid @enderror" value="{{ old('pan', $company->pan) }}" maxlength="10" style="text-transform:uppercase;">
                    @error('pan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">TAN Number</label>
                    <input type="text" name="tan" class="form-control @error('tan') is-invalid @enderror" value="{{ old('tan', $company->tan) }}" maxlength="10" style="text-transform:uppercase;">
                    @error('tan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">GSTIN</label>
                    <input type="text" name="gstin" class="form-control @error('gstin') is-invalid @enderror" value="{{ old('gstin', $company->gstin) }}" maxlength="15" style="text-transform:uppercase;">
                    @error('gstin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">CIN</label>
                    <input type="text" name="cin" class="form-control" value="{{ old('cin', $company->cin) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">PF Establishment Number</label>
                    <input type="text" name="pf_registration" class="form-control" value="{{ old('pf_registration', $company->pf_registration) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ESI Employer Code</label>
                    <input type="text" name="esi_registration" class="form-control" value="{{ old('esi_registration', $company->esi_registration) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">PT Registration No.</label>
                    <input type="text" name="pt_registration" class="form-control" value="{{ old('pt_registration', $company->pt_registration) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Industry Type</label>
                    <input type="text" name="industry_type" class="form-control" value="{{ old('industry_type', $company->industry_type) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Financial Year Start Month</label>
                    <select name="financial_year_start" class="form-select">
                        @foreach (['1'=>'January','2'=>'February','3'=>'March','4'=>'April','5'=>'May','6'=>'June','7'=>'July','8'=>'August','9'=>'September','10'=>'October','11'=>'November','12'=>'December'] as $val => $label)
                            <option value="{{ $val }}" {{ (string) old('financial_year_start', $company->financial_year_start ?? 4) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
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

@push('scripts')
<script>
(function () {
    var checkbox = document.getElementById('sameAsRegistered');
    var registered = document.getElementById('registeredAddress');
    var communication = document.getElementById('communicationAddress');
    if (!checkbox || !registered || !communication) return;

    function syncAddress() {
        if (checkbox.checked) {
            communication.value = registered.value;
            communication.readOnly = true;
        } else {
            communication.readOnly = false;
        }
    }

    checkbox.addEventListener('change', syncAddress);
    registered.addEventListener('input', function () { if (checkbox.checked) communication.value = registered.value; });
})();
</script>
@endpush
