@extends('layouts.app')
@section('title', 'Add Contract Worker')
@section('page-title', 'Add Contract Worker')
@section('page-subtitle', $contractor->name . ' (' . $contractor->code . ')')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.contractors.workers.store', $contractor) }}" method="POST">
            @csrf
            <div class="row g-3">

                {{-- Personal Details --}}
                <div class="col-12"><h6 class="text-muted fw-semibold border-bottom pb-1 mb-1">Personal Details</h6></div>

                <div class="col-md-5">
                    <label class="form-label">Worker Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                        <option value="">— Select —</option>
                        <option value="male"   {{ old('gender') == 'male'   ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other"  {{ old('gender') == 'other'  ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                </div>

                {{-- ID Proof --}}
                <div class="col-md-4">
                    <label class="form-label">ID Proof Type</label>
                    <select name="id_proof_type" class="form-select">
                        <option value="">— Select —</option>
                        <option value="aadhaar"         {{ old('id_proof_type') == 'aadhaar'         ? 'selected' : '' }}>Aadhaar</option>
                        <option value="passport"        {{ old('id_proof_type') == 'passport'        ? 'selected' : '' }}>Passport</option>
                        <option value="voter_id"        {{ old('id_proof_type') == 'voter_id'        ? 'selected' : '' }}>Voter ID</option>
                        <option value="driving_license" {{ old('id_proof_type') == 'driving_license' ? 'selected' : '' }}>Driving License</option>
                        <option value="other"           {{ old('id_proof_type') == 'other'           ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ID Proof Number</label>
                    <input type="text" name="id_proof_number" class="form-control" value="{{ old('id_proof_number') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Skill Type</label>
                    <input type="text" name="skill_type" class="form-control" placeholder="e.g. Mason, Welder, Helper"
                           value="{{ old('skill_type') }}">
                </div>

                {{-- Work & Wage --}}
                <div class="col-12"><h6 class="text-muted fw-semibold border-bottom pb-1 mb-1 mt-2">Wage & Work Details</h6></div>

                <div class="col-md-3">
                    <label class="form-label">Wage Type <span class="text-danger">*</span></label>
                    <select name="wage_type" class="form-select @error('wage_type') is-invalid @enderror" required>
                        <option value="daily"   {{ old('wage_type', 'daily') == 'daily'   ? 'selected' : '' }}>Daily Rate</option>
                        <option value="monthly" {{ old('wage_type')          == 'monthly' ? 'selected' : '' }}>Monthly Rate</option>
                    </select>
                    @error('wage_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Wage Amount (₹) <span class="text-danger">*</span></label>
                    <input type="number" name="wage_amount" step="0.01" min="0"
                           class="form-control @error('wage_amount') is-invalid @enderror"
                           value="{{ old('wage_amount', 0) }}" required>
                    @error('wage_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active"     {{ old('status', 'active') == 'active'     ? 'selected' : '' }}>Active</option>
                        <option value="inactive"   {{ old('status')           == 'inactive'   ? 'selected' : '' }}>Inactive</option>
                        <option value="terminated" {{ old('status')           == 'terminated' ? 'selected' : '' }}>Terminated</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Worker</button>
                <a href="{{ route('masters.contractors.workers.index', $contractor) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
