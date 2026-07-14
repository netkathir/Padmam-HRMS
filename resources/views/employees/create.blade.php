@extends('layouts.app')

@section('title', 'Add Employee')
@section('page-title', 'Add Employee')
@section('page-subtitle', 'Create a new employee record')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('employees.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <h6 class="fw-bold border-bottom pb-2">Personal Information</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name"
                            class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}"
                            required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                            value="{{ old('last_name') }}" required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Employee Code</label>
                        <input type="text" name="employee_code"
                            class="form-control @error('employee_code') is-invalid @enderror"
                            value="{{ old('employee_code') }}" placeholder="Auto-generated if a numbering rule applies">
                        <div class="form-text">Leave blank to auto-generate from the Rule Engine's Employee Number Rule, if one is configured.</div>
                        @error('employee_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Employee Category <span class="text-danger">*</span></label>
                        <select name="employee_category" class="form-select @error('employee_category') is-invalid @enderror" required>
                            <option value="">Select</option>
                            <option value="staff" {{ old('employee_category') == 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="company_labour" {{ old('employee_category') == 'company_labour' ? 'selected' : '' }}>Company Labour</option>
                            <option value="contract_labour" {{ old('employee_category') == 'contract_labour' ? 'selected' : '' }}>Contract Labour</option>
                        </select>
                        @error('employee_category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth"
                            class="form-control @error('date_of_birth') is-invalid @enderror"
                            value="{{ old('date_of_birth') }}" required>
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                            <option value="">Select</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="">Select</option>
                            <option value="single" {{ old('marital_status') == 'single' ? 'selected' : '' }}>Single
                            </option>
                            <option value="married" {{ old('marital_status') == 'married' ? 'selected' : '' }}>Married
                            </option>
                            <option value="divorced" {{ old('marital_status') == 'divorced' ? 'selected' : '' }}>Divorced
                            </option>
                            <option value="widowed" {{ old('marital_status') == 'widowed' ? 'selected' : '' }}>Widowed
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Blood Group</label>
                        <input type="text" name="blood_group" class="form-control" value="{{ old('blood_group') }}">
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-bottom pb-2">Contact Information</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Official Email <span class="text-danger">*</span></label>
                        <input type="email" name="official_email"
                            class="form-control @error('official_email') is-invalid @enderror"
                            value="{{ old('official_email') }}" required>
                        @error('official_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Personal Email</label>
                        <input type="email" name="personal_email" class="form-control"
                            value="{{ old('personal_email') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone') }}" required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address_line1" class="form-control" rows="2">{{ old('address_line1') }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control" value="{{ old('state') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pincode</label>
                        <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}">
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-bottom pb-2">Job Information</h6>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required {{ $lockedBranchId ? 'disabled' : '' }}>
                            <option value="">Select</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ (string) old('branch_id', $lockedBranchId) === (string) $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}</option>
                            @endforeach
                        </select>
                        @if ($lockedBranchId)
                            <input type="hidden" name="branch_id" value="{{ $lockedBranchId }}">
                        @endif
                        @error('branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select @error('department_id') is-invalid @enderror"
                            required>
                            <option value="">Select</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}"
                                    {{ old('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                        <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror"
                            required>
                            <option value="">Select</option>
                            @foreach ($designations as $des)
                                <option value="{{ $des->id }}"
                                    {{ old('designation_id') == $des->id ? 'selected' : '' }}>{{ $des->name }}</option>
                            @endforeach
                        </select>
                        @error('designation_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Employee Type <span class="text-danger">*</span></label>
                        <select name="employee_type_id"
                            class="form-select @error('employee_type_id') is-invalid @enderror" required>
                            <option value="">Select</option>
                            @foreach ($employeeTypes as $et)
                                <option value="{{ $et->id }}"
                                    {{ old('employee_type_id') == $et->id ? 'selected' : '' }}>{{ $et->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Joining <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_joining"
                            class="form-control @error('date_of_joining') is-invalid @enderror"
                            value="{{ old('date_of_joining') }}" required>
                        @error('date_of_joining')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="probation" {{ old('status') == 'probation' ? 'selected' : '' }}>Probation
                            </option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Shift</label>
                        <select name="shift_id" class="form-select">
                            <option value="">Select</option>
                            @foreach ($shifts as $s)
                                <option value="{{ $s->id }}" {{ old('shift_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reporting To</label>
                        <select name="reporting_to" class="form-select">
                            <option value="">Select</option>
                            @foreach ($managers as $m)
                                <option value="{{ $m->id }}"
                                    {{ old('reporting_to') == $m->id ? 'selected' : '' }}>{{ $m->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-bottom pb-2">Government IDs</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Aadhaar Number</label>
                        <input type="text" name="aadhaar_number" class="form-control"
                            value="{{ old('aadhaar_number') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PAN Number</label>
                        <input type="text" name="pan_number" class="form-control" value="{{ old('pan_number') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">UAN Number</label>
                        <input type="text" name="uan_number" class="form-control" value="{{ old('uan_number') }}">
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-bottom pb-2">Payroll Settings</h6>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="is_pf_applicable" class="form-check-input" value="1"
                                {{ old('is_pf_applicable', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label">PF Applicable</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="is_esi_applicable" class="form-check-input" value="1"
                                {{ old('is_esi_applicable', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label">ESI Applicable</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="create_user" class="form-check-input" value="1">
                            <label class="form-check-label">Create Login User</label>
                        </div>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
