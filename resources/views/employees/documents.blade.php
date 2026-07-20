@extends('layouts.app')
@section('title','Employee Documents')
@section('page-title','Documents')
@section('page-subtitle',$employee->full_name ?? $employee->name)
@section('page-actions')
    <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Profile</a>
@endsection
@section('content')
@if(!empty($missingMandatory))
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Missing mandatory document(s):
        {{ collect($missingMandatory)->map(fn($t) => ucfirst(str_replace('_',' ',$t)))->implode(', ') }}
    </div>
@endif
<div class="row g-3">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Upload Document</h6></div>
            <div class="card-body">
                <form action="{{ route('employees.documents.upload', $employee) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Document Type <span class="text-danger">*</span></label>
                        <select name="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
                            <option value="">Select type…</option>
                            @foreach(['aadhaar' => 'Aadhaar Card', 'pan' => 'PAN Card', 'passport' => 'Passport', 'photo' => 'Photo', 'photo_id' => 'Photo ID Proof', 'bank_proof' => 'Bank Proof', 'offer_letter' => 'Offer Letter', 'resume' => 'Resume', 'relieving_letter' => 'Relieving Letter', 'experience_letter' => 'Experience Letter', 'education_certificate' => 'Education Certificate', 'other' => 'Other'] as $value => $label)
                            <option value="{{ $value }}" {{ old('document_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('document_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Document Number</label>
                        <input type="text" name="document_number" class="form-control" value="{{ old('document_number') }}" placeholder="Optional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">PDF/JPG/PNG, max 5MB</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload"></i> Upload</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Uploaded Documents</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Type</th><th>Number</th><th>Expiry</th><th>Uploaded</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse($documents as $doc)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</td>
                                <td>{{ $doc->document_number ?? '—' }}</td>
                                <td>
                                    @if($doc->expiry_date)
                                        @php $exp = \Carbon\Carbon::parse($doc->expiry_date); @endphp
                                        <span class="{{ $exp->isPast() ? 'text-danger' : ($exp->between(now(), now()->addDays(30)) ? 'text-warning' : '') }}">
                                            {{ $exp->format('d M Y') }}
                                        </span>
                                    @else —
                                    @endif
                                </td>
                                <td>{{ $doc->created_at->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                    @can('employees.full')
                                    <form action="{{ route('employees.documents.destroy', [$employee, $doc]) }}" method="POST" class="d-inline" data-confirm-delete="Remove this document?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No documents uploaded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
