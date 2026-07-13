@extends('layouts.app')

@section('title', 'Edit Employee')
@section('page-title', 'Edit Employee')
@section('page-subtitle', $employee->full_name)

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('employees.update', $employee) }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-12">
                        <h6 class="fw-bold border-bottom pb-2">Personal Information</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name"
                            class="form-control @error('first_name') is-invalid @enderror"
                            value="{{ old('first_name', $employee->first_name) }}" required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                            value="{{ old('last_name', $employee->last_name) }}" required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Employee Code <span class="text-danger">*</span></label>
                        <input type="text" name="employee_code"
                            class="form-control @error('employee_code') is-invalid @enderror"
                            value="{{ old('employee_code', $employee->employee_code) }}" required>
                        @error('employee_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth"
                            class="form-control @error('date_of_birth') is-invalid @enderror"
                            value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}" required>
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                            <option value="male" {{ old('gender', $employee->gender) == 'male' ? 'selected' : '' }}>Male
                            </option>
                            <option value="female" {{ old('gender', $employee->gender) == 'female' ? 'selected' : '' }}>
                                Female</option>
                            <option value="other" {{ old('gender', $employee->gender) == 'other' ? 'selected' : '' }}>
                                Other</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="">Select</option>
                            <option value="single"
                                {{ old('marital_status', $employee->marital_status) == 'single' ? 'selected' : '' }}>Single
                            </option>
                            <option value="married"
                                {{ old('marital_status', $employee->marital_status) == 'married' ? 'selected' : '' }}>
                                Married</option>
                            <option value="divorced"
                                {{ old('marital_status', $employee->marital_status) == 'divorced' ? 'selected' : '' }}>
                                Divorced</option>
                            <option value="widowed"
                                {{ old('marital_status', $employee->marital_status) == 'widowed' ? 'selected' : '' }}>
                                Widowed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Blood Group</label>
                        <input type="text" name="blood_group" class="form-control"
                            value="{{ old('blood_group', $employee->blood_group) }}">
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-bottom pb-2">Contact & Job Information</h6>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Official Email <span class="text-danger">*</span></label>
                        <input type="email" name="official_email"
                            class="form-control @error('official_email') is-invalid @enderror"
                            value="{{ old('official_email', $employee->official_email) }}" required>
                        @error('official_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $employee->phone) }}" required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Joining <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_joining"
                            class="form-control @error('date_of_joining') is-invalid @enderror"
                            value="{{ old('date_of_joining', $employee->date_of_joining?->format('Y-m-d')) }}" required>
                        @error('date_of_joining')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required {{ $lockedBranchId ? 'disabled' : '' }}>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}"
                                    {{ (string) old('branch_id', $lockedBranchId ?? $employee->branch_id) === (string) $b->id ? 'selected' : '' }}>
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
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}"
                                    {{ old('department_id', $employee->department_id) == $d->id ? 'selected' : '' }}>
                                    {{ $d->name }}</option>
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
                            @foreach ($designations as $des)
                                <option value="{{ $des->id }}"
                                    {{ old('designation_id', $employee->designation_id) == $des->id ? 'selected' : '' }}>
                                    {{ $des->name }}</option>
                            @endforeach
                        </select>
                        @error('designation_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>
                                Active</option>
                            <option value="probation"
                                {{ old('status', $employee->status) == 'probation' ? 'selected' : '' }}>Probation</option>
                            <option value="inactive"
                                {{ old('status', $employee->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="terminated"
                                {{ old('status', $employee->status) == 'terminated' ? 'selected' : '' }}>Terminated
                            </option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
