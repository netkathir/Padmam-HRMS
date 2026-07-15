@extends('layouts.app')
@section('title','Edit Contractor')
@section('page-title','Edit Contractor')
@section('page-subtitle',$contractor->name)
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="card mb-3">
    <div class="card-body">
        <form action="{{ route('masters.contractors.update', $contractor) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-12"><h6 class="text-primary border-bottom pb-1">Contractor Details</h6></div>
                <div class="col-md-6">
                    <label class="form-label">Contractor Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $contractor->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Contractor Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $contractor->code) }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $contractor->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Status (Active) <span class="text-danger">*</span></label>
                    </div>
                    @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Contact Details</h6></div>
                <div class="col-md-4">
                    <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                    <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person', $contractor->contact_person) }}" required>
                    @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $contractor->phone) }}" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Alternate Number</label>
                    <input type="text" name="alternate_phone" class="form-control @error('alternate_phone') is-invalid @enderror" value="{{ old('alternate_phone', $contractor->alternate_phone) }}">
                    @error('alternate_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $contractor->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $contractor->address) }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">State <span class="text-muted">(required if address entered)</span></label>
                    <select name="state" class="form-select @error('state') is-invalid @enderror">
                        <option value="">Select State</option>
                        @foreach($states as $st)
                            <option value="{{ $st }}" {{ old('state', $contractor->state) == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">District <span class="text-muted">(required if address entered)</span></label>
                    <input type="text" name="district" class="form-control @error('district') is-invalid @enderror" value="{{ old('district', $contractor->district) }}">
                    @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">PIN Code <span class="text-muted">(required if address entered)</span></label>
                    <input type="text" name="pincode" class="form-control @error('pincode') is-invalid @enderror" value="{{ old('pincode', $contractor->pincode) }}" maxlength="6">
                    @error('pincode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Statutory & Registration</h6></div>
                <div class="col-md-3">
                    <label class="form-label">PAN Number</label>
                    <input type="text" name="pan_number" class="form-control @error('pan_number') is-invalid @enderror" value="{{ old('pan_number', $contractor->pan_number) }}" maxlength="10">
                    @error('pan_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">GST Number</label>
                    <input type="text" name="gst_number" class="form-control @error('gst_number') is-invalid @enderror" value="{{ old('gst_number', $contractor->gst_number) }}" maxlength="15">
                    @error('gst_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">PF Registration Number</label>
                    <input type="text" name="pf_registration_number" class="form-control" value="{{ old('pf_registration_number', $contractor->pf_registration_number) }}" maxlength="50">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ESI Registration Number</label>
                    <input type="text" name="esi_registration_number" class="form-control" value="{{ old('esi_registration_number', $contractor->esi_registration_number) }}" maxlength="50">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Labour Licence Number</label>
                    <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $contractor->license_number) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Licence Expiry Date <span class="text-muted">(required if licence entered)</span></label>
                    <input type="date" name="license_expiry" class="form-control @error('license_expiry') is-invalid @enderror" value="{{ old('license_expiry', optional($contractor->license_expiry)->format('Y-m-d')) }}">
                    @error('license_expiry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($contractor->isLicenseExpired())
                        <div class="form-text text-danger">Licence has expired.</div>
                    @elseif($contractor->isLicenseExpiringSoon())
                        <div class="form-text text-warning">Licence expiring within 30 days.</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label">Maximum Labour Count</label>
                    <input type="number" name="max_labour_count" class="form-control" min="0" value="{{ old('max_labour_count', $contractor->max_labour_count) }}">
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Agreement</h6></div>
                <div class="col-md-4">
                    <label class="form-label">Agreement Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="agreement_start_date" class="form-control @error('agreement_start_date') is-invalid @enderror" value="{{ old('agreement_start_date', optional($contractor->agreement_start_date)->format('Y-m-d')) }}" required>
                    @error('agreement_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Agreement End Date</label>
                    <input type="date" name="agreement_end_date" class="form-control @error('agreement_end_date') is-invalid @enderror" value="{{ old('agreement_end_date', optional($contractor->agreement_end_date)->format('Y-m-d')) }}">
                    @error('agreement_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @if($contractor->isAgreementExpired())
                        <div class="form-text text-danger">Agreement has expired.</div>
                    @elseif($contractor->isAgreementExpiringSoon())
                        <div class="form-text text-warning">Agreement expiring within 30 days.</div>
                    @endif
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.contractors.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="text-primary border-bottom pb-1">Documents</h6>
        <p class="text-muted small">Agreement, licence, and supporting documents (PDF/JPG/PNG, max 5MB).</p>
        <form action="{{ route('masters.contractors.documents.store', $contractor) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Document Type</label>
                <select name="document_type" class="form-select form-select-sm" required>
                    <option value="agreement">Agreement</option>
                    <option value="licence">Licence</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">File</label>
                <input type="file" name="file" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-upload"></i> Upload</button>
            </div>
            @error('file')<div class="col-12 text-danger small">{{ $message }}</div>@enderror
        </form>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Type</th><th>File</th><th>Uploaded</th><th></th></tr></thead>
                <tbody>
                    @forelse($contractor->documents as $doc)
                    <tr>
                        <td>{{ ucfirst($doc->document_type) }}</td>
                        <td><a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank">{{ $doc->original_name }}</a></td>
                        <td>{{ $doc->created_at->format('d M Y') }}</td>
                        <td>
                            <form action="{{ route('masters.contractors.documents.destroy', [$contractor, $doc]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this document?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No documents uploaded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
