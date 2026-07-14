@extends('layouts.app')
@section('title', 'Attendance Processing')
@section('page-title', 'Attendance Processing')
@section('page-subtitle', 'Calculate attendance from uploaded biometric punches')
@section('page-actions')
    <a href="{{ route('attendance.upload.form') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-upload"></i> Upload Biometric Data</a>
@endsection
@section('content')
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('attendance.process.post') }}" method="POST"
                    onsubmit="return confirm('This will calculate attendance from biometric punches for the selected period and filters. Proceed?');">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required {{ $currentBranchId ? 'disabled' : '' }}>
                            <option value="">Select</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ (string) old('branch_id', $currentBranchId) === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @if ($currentBranchId)
                            <input type="hidden" name="branch_id" value="{{ $currentBranchId }}">
                        @endif
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Processing Period From <span class="text-danger">*</span></label>
                            <input type="date" name="period_from" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Processing Period To <span class="text-danger">*</span></label>
                            <input type="date" name="period_to" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Employee Type</label>
                            <select name="employee_type_id" class="form-select">
                                <option value="">All</option>
                                @foreach ($employeeTypes as $et)
                                    <option value="{{ $et->id }}">{{ $et->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Labour Type</label>
                            <select name="labour_type" class="form-select">
                                <option value="">All</option>
                                <option value="company_labour">Company Labour</option>
                                <option value="contract_labour">Contract Labour</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Contractor</label>
                            <select name="contractor_id" class="form-select">
                                <option value="">All</option>
                                @foreach ($contractors as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Shift</label>
                            <select name="shift_id" class="form-select">
                                <option value="">All</option>
                                @foreach ($shifts as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="recalculate_existing" value="1" class="form-check-input" id="recalc" {{ $canRecalculate ? '' : 'disabled' }}>
                        <label class="form-check-label" for="recalc">Recalculate Existing Attendance</label>
                        @if (! $canRecalculate)
                            <div class="form-text text-warning">You do not have permission to recalculate already-processed attendance.</div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Processing Remarks</label>
                        <textarea name="remarks" rows="2" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-gear"></i> Process Attendance</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
