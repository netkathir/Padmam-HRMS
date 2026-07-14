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
                    <div class="col-md-3">
                        <label class="form-label">Branch Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                            value="{{ old('code') }}" required>
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
                    <div class="col-md-2 d-none">
                        <label class="form-label">Unit Type</label>
                        <input type="text" name="unit_type" list="unitTypeOptions" class="form-control" value="{{ old('unit_type') }}" placeholder="Branch, Factory…">
                        <datalist id="unitTypeOptions">
                            @foreach ($unitTypes as $type)
                                <option value="{{ $type }}">
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            @php $hasAddressOld = old('has_address', old('address') ? '1' : '0'); @endphp
                            <input class="form-check-input" type="checkbox" name="has_address" value="1" id="hasAddress" {{ $hasAddressOld ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="hasAddress">Address Available</label>
                        </div>
                        <div class="form-text">Enable this to record the branch's address, State, District, City and PIN Code.</div>
                    </div>

                    <div id="addressSection" class="row g-3 {{ $hasAddressOld ? '' : 'd-none' }}">
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">State <span class="text-danger">*</span></label>
                            <select name="state" class="form-select @error('state') is-invalid @enderror">
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
                                value="{{ old('district') }}">
                            @error('district')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                                value="{{ old('city') }}">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">PIN Code <span class="text-danger">*</span></label>
                            <input type="text" name="pincode" inputmode="numeric" maxlength="6"
                                class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode') }}">
                            @error('pincode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4 d-none">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person"
                            class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person') }}">
                        @error('contact_person')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 d-none">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone') }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 d-none">
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
                    <div class="col-md-4 d-none">
                        <label class="form-label">Branch Start Date</label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                            value="{{ old('start_date') }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 d-none">
                        <label class="form-label">Branch Closure Date</label>
                        <input type="date" name="closure_date" class="form-control @error('closure_date') is-invalid @enderror"
                            value="{{ old('closure_date') }}">
                        @error('closure_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- PF Establishment Number / ESI Employer Code will be managed
                         later via the dedicated Branch Statutory Configuration
                         module — hidden here but the backend support is retained. --}}
                    <div class="col-md-4 d-none">
                        <label class="form-label">PF Establishment Number</label>
                        <input type="text" name="pf_establishment_number" class="form-control @error('pf_establishment_number') is-invalid @enderror" value="{{ old('pf_establishment_number') }}">
                        @error('pf_establishment_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 d-none">
                        <label class="form-label">ESI Employer Code</label>
                        <input type="text" name="esi_employer_code" class="form-control @error('esi_employer_code') is-invalid @enderror" value="{{ old('esi_employer_code') }}">
                        @error('esi_employer_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 d-none">
                        <label class="form-label">Weekly Off Days</label>
                        <div class="d-flex flex-wrap gap-2 pt-1">
                            @php $oldWeeklyOff = old('weekly_off_days', ['sunday']); @endphp
                            @foreach ($weekdays as $day)
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input" type="checkbox" name="weekly_off_days[]" value="{{ $day }}" id="wo_{{ $day }}" {{ in_array($day, $oldWeeklyOff) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="wo_{{ $day }}">{{ ucfirst(substr($day, 0, 3)) }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('weekly_off_days')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
    var hasAddressCheckbox = document.getElementById('hasAddress');
    var addressSection = document.getElementById('addressSection');
    var requiredWhenAddressed = addressSection
        ? addressSection.querySelectorAll('[name="state"], [name="district"], [name="city"], [name="pincode"]')
        : [];

    function toggleAddressSection() {
        if (!hasAddressCheckbox || !addressSection) return;
        var show = hasAddressCheckbox.checked;
        addressSection.classList.toggle('d-none', !show);
        requiredWhenAddressed.forEach(function (el) {
            el.required = show;
        });
    }

    if (hasAddressCheckbox) {
        hasAddressCheckbox.addEventListener('change', toggleAddressSection);
        toggleAddressSection();
    }
})();
</script>
@endpush
