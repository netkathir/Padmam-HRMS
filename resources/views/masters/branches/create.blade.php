@extends('layouts.app')

@section('title', 'Create Branch')
@section('page-title', 'Create Branch')
@section('page-subtitle', 'Add a new branch (Super Admin only)')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('masters.branches.store') }}" method="POST" id="createBranchForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- Branch Code is auto-generated server-side (one higher than the
                         latest existing code) — hidden here, not user-entered. --}}
                    <div class="col-md-3 d-none">
                        <label class="form-label">Branch Code</label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                            value="{{ old('code') }}">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="is_active" class="form-select" required>
                            <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-12"><h6 class="fw-bold border-bottom pb-2 mt-2">Address</h6></div>

                    <div id="addressSection" class="row g-3">
                        {{-- Address is stored server-side as a single `address` column/field
                             (now unconditionally required). These two line inputs are a UI
                             convenience only — combined into the hidden `address` field just
                             before submit. --}}
                        <div class="col-md-6">
                            <label class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                            <input type="text" name="address_line1" id="address_line1" class="form-control @error('address') is-invalid @enderror"
                                value="{{ old('address_line1') }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" name="address_line2" id="address_line2" class="form-control"
                                value="{{ old('address_line2') }}">
                        </div>
                        <input type="hidden" name="address" id="address_combined" value="{{ old('address') }}">

                        <div class="col-md-3">
                            <label class="form-label">State <span class="text-danger">*</span></label>
                            <select name="state" class="form-select @error('state') is-invalid @enderror" required>
                                <option value="">Select State</option>
                                @foreach ($states as $state)
                                    <option value="{{ $state }}" {{ old('state') === $state ? 'selected' : '' }}>{{ $state }}</option>
                                @endforeach
                            </select>
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">District <span class="text-danger">*</span></label>
                            <input type="text" name="district" class="form-control @error('district') is-invalid @enderror"
                                value="{{ old('district') }}" required>
                            @error('district')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                                value="{{ old('city') }}" required>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">PIN Code <span class="text-danger">*</span></label>
                            <input type="text" name="pincode" inputmode="numeric" maxlength="6"
                                class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode') }}" required>
                            @error('pincode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person"
                            class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person') }}">
                        @error('contact_person')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Branch Head is assigned after the branch and its user exist,
                         via the existing Branch Head Assignment functionality — not
                         during branch creation. --}}
                    <div class="col-md-4 d-none">
                        <label class="form-label">Branch Head</label>
                        <select name="branch_head_user_id" class="form-select @error('branch_head_user_id') is-invalid @enderror">
                            <option value="">— None —</option>
                            @foreach ($branchHeads as $u)
                                <option value="{{ $u->id }}" {{ (string) old('branch_head_user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_head_user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch Start Date</label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                            value="{{ old('start_date') }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">PF Establishment Number</label>
                        <input type="text" name="pf_establishment_number" class="form-control @error('pf_establishment_number') is-invalid @enderror" value="{{ old('pf_establishment_number') }}">
                        @error('pf_establishment_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ESI Employer Code</label>
                        <input type="text" name="esi_employer_code" class="form-control @error('esi_employer_code') is-invalid @enderror" value="{{ old('esi_employer_code') }}">
                        @error('esi_employer_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                    <a href="{{ route('masters.branches.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    // Address Line 1/2 are UI-only inputs; the server still expects a single
    // `address` field, so combine them into the hidden field before submit.
    var form = document.getElementById('createBranchForm');
    var addressLine1 = document.getElementById('address_line1');
    var addressLine2 = document.getElementById('address_line2');
    var addressCombined = document.getElementById('address_combined');

    function combineAddressLines() {
        if (!addressCombined) return;
        var parts = [
            addressLine1 ? addressLine1.value.trim() : '',
            addressLine2 ? addressLine2.value.trim() : ''
        ].filter(Boolean);
        addressCombined.value = parts.join(', ');
    }

    if (form) {
        form.addEventListener('submit', combineAddressLines);
    }
})();
</script>
@endpush
