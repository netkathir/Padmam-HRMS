@extends('layouts.app')
@section('title', 'Edit Contract Worker')
@section('page-title', 'Edit Contract Worker')
@section('page-subtitle', $contractor->name . ' — ' . $contractWorker->name)

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.contractors.workers.update', [$contractor, $contractWorker]) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">

                <div class="col-12"><h6 class="text-muted fw-semibold border-bottom pb-1 mb-1">Personal Details</h6></div>

                <div class="col-md-5">
                    <label class="form-label">Worker Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $contractWorker->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">— Select —</option>
                        <option value="male"   {{ old('gender', $contractWorker->gender) == 'male'   ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $contractWorker->gender) == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other"  {{ old('gender', $contractWorker->gender) == 'other'  ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $contractWorker->phone) }}" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
                </div>

                <div class="col-md-4">
                    <label class="form-label">ID Proof Type</label>
                    <select name="id_proof_type" class="form-select">
                        <option value="">— Select —</option>
                        @foreach(['aadhaar' => 'Aadhaar', 'passport' => 'Passport', 'voter_id' => 'Voter ID', 'driving_license' => 'Driving License', 'other' => 'Other'] as $val => $label)
                            <option value="{{ $val }}" {{ old('id_proof_type', $contractWorker->id_proof_type) == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ID Proof Number</label>
                    <input type="text" name="id_proof_number" class="form-control"
                           value="{{ old('id_proof_number', $contractWorker->id_proof_number) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Skill Type</label>
                    <input type="text" name="skill_type" class="form-control"
                           value="{{ old('skill_type', $contractWorker->skill_type) }}">
                </div>

                <div class="col-12"><h6 class="text-muted fw-semibold border-bottom pb-1 mb-1 mt-2">Wage & Work Details</h6></div>

                <div class="col-md-3">
                    <label class="form-label">Wage Type <span class="text-danger">*</span></label>
                    <select name="wage_type" class="form-select" required>
                        <option value="daily"   {{ old('wage_type', $contractWorker->wage_type) == 'daily'   ? 'selected' : '' }}>Daily Rate</option>
                        <option value="monthly" {{ old('wage_type', $contractWorker->wage_type) == 'monthly' ? 'selected' : '' }}>Monthly Rate</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Wage Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="wage_amount" step="0.01" min="0" class="form-control"
                           value="{{ old('wage_amount', $contractWorker->wage_amount) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control"
                           value="{{ old('joining_date', optional($contractWorker->joining_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active"     {{ old('status', $contractWorker->status) == 'active'     ? 'selected' : '' }}>Active</option>
                        <option value="inactive"   {{ old('status', $contractWorker->status) == 'inactive'   ? 'selected' : '' }}>Inactive</option>
                        <option value="terminated" {{ old('status', $contractWorker->status) == 'terminated' ? 'selected' : '' }}>Terminated</option>
                    </select>
                </div>

            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Worker</button>
                <a href="{{ route('masters.contractors.workers.index', $contractor) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
