@extends('layouts.app')

@section('title', 'Masters')
@section('page-title', 'Masters')
@section('page-subtitle', 'Manage system master data')

@section('content')
    <div class="row g-3">
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.branches.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-building fs-1 text-primary mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Branches</h6>
                    <small class="text-muted">{{ $branchCount ?? 0 }} records</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.departments.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-diagram-3 fs-1 text-success mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Departments</h6>
                    <small class="text-muted">{{ $deptCount ?? 0 }} records</small>
                </div>
            </a>
        </div>
        {{-- Designations is temporarily hidden from the Masters landing page
             (UI only — route/controller/data are untouched and this tile can
             be restored by uncommenting it). --}}
        {{--
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.designations.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-person-badge fs-1 text-info mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Designations</h6>
                    <small class="text-muted">{{ $desigCount ?? 0 }} records</small>
                </div>
            </a>
        </div>
        --}}
        @if (config('features.employee_types_enabled', false))
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.employee-types.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-person-check fs-1 text-warning mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Employee Types</h6>
                    <small class="text-muted">Manage types</small>
                </div>
            </a>
        </div>
        @endif
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.contractors.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-briefcase fs-1 text-secondary mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Contractors</h6>
                    <small class="text-muted">Manage contractors</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.shifts.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-clock-history fs-1 text-danger mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Shifts</h6>
                    <small class="text-muted">{{ $shiftCount ?? 0 }} records</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.holidays.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-calendar-heart fs-1 text-pink mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Holidays</h6>
                    <small class="text-muted">{{ $holidayCount ?? 0 }} this year</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.leave-types.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-calendar-x fs-1 text-purple mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Leave Types</h6>
                    <small class="text-muted">{{ $leaveTypeCount ?? 0 }} records</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.earnings.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-plus-circle fs-1 text-success mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Earnings</h6>
                    <small class="text-muted">{{ $earningsCount ?? 0 }} components</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.deductions.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-dash-circle fs-1 text-danger mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Deductions</h6>
                    <small class="text-muted">{{ $deductionsCount ?? 0 }} components</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.salary-slabs.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-layers fs-1 text-info mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Salary Slabs</h6>
                    <small class="text-muted">Manage slabs</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.ot-rates.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-clock fs-1 text-warning mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">OT Rates</h6>
                    <small class="text-muted">Manage rates</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.pf-esi.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-shield-check fs-1 text-primary mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">PF & ESI</h6>
                    <small class="text-muted">Configuration</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.banks.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-bank fs-1 text-secondary mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Banks</h6>
                    <small class="text-muted">{{ $bankCount ?? 0 }} records</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.checkpoints.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-geo-alt fs-1 text-info mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Checkpoints</h6>
                    <small class="text-muted">{{ $checkpointCount ?? 0 }} checkpoints</small>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('masters.employee-checkpoints.index') }}" class="card text-decoration-none h-100">
                <div class="card-body text-center py-4">
                    <i class="bi bi-person-lines-fill fs-1 text-primary mb-2 d-block"></i>
                    <h6 class="fw-semibold mb-1">Employee Checkpoint Mapping</h6>
                    <small class="text-muted">Map employees to checkpoints</small>
                </div>
            </a>
        </div>
    </div>
@endsection
