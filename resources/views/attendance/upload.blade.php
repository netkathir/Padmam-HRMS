@extends('layouts.app')
@section('title', 'Biometric Excel Upload')
@section('page-title', 'Biometric Excel Upload')
@section('page-subtitle', 'Upload punch data exported from the biometric device')
@section('page-actions')
    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Register</a>
@endsection
@section('content')
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> {{ session('warning') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('attendance.upload.post') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required {{ $currentBranchId ? 'disabled' : '' }}>
                            <option value="">Select</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ (string) old('branch_id', $currentBranchId) === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @if ($currentBranchId)
                            <input type="hidden" name="branch_id" value="{{ $currentBranchId }}">
                        @endif
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Attendance Period From <span class="text-danger">*</span></label>
                            <input type="date" name="period_from" class="form-control @error('period_from') is-invalid @enderror" value="{{ old('period_from') }}" required>
                            @error('period_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Attendance Period To <span class="text-danger">*</span></label>
                            <input type="date" name="period_to" class="form-control @error('period_to') is-invalid @enderror" value="{{ old('period_to') }}" required>
                            @error('period_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload File (XLS/XLSX) <span class="text-danger">*</span></label>
                        <input type="file" name="file" accept=".xls,.xlsx" class="form-control @error('file') is-invalid @enderror" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Remarks</label>
                        <textarea name="remarks" rows="2" class="form-control">{{ old('remarks') }}</textarea>
                    </div>
                    <div class="alert alert-light border small">
                        Uploaded By: <strong>{{ auth()->user()->name }}</strong> &middot;
                        Uploaded Date &amp; Time: <strong>{{ now()->format('d M Y H:i') }}</strong>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload &amp; Continue</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
