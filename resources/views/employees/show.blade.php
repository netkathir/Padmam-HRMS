@extends('layouts.app')

@section('title', $employee->full_name)
@section('page-title', $employee->full_name)
@section('page-subtitle', 'Employee #' . $employee->employee_code)

@section('page-actions')
    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
    <a href="{{ route('employees.salary', $employee) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-cash"></i>
        Salary</a>
    <a href="{{ route('employees.documents', $employee) }}" class="btn btn-outline-info btn-sm"><i class="bi bi-file-text"></i>
        Documents</a>
    <a href="{{ route('employees.exit', $employee) }}" class="btn btn-outline-danger btn-sm"><i
            class="bi bi-box-arrow-right"></i> Exit</a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3"
                        style="width:80px;height:80px;">
                        <span
                            class="text-white fw-bold fs-3">{{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}</span>
                    </div>
                    <h5 class="mb-1">{{ $employee->full_name }}</h5>
                    <p class="text-muted mb-1">{{ $employee->designation->name ?? '—' }}</p>
                    <span
                        class="badge bg-{{ $employee->status == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($employee->status) }}</span>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">Quick Links</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('employees.salary', $employee) }}" class="list-group-item list-group-item-action"><i
                            class="bi bi-cash me-2"></i>Salary Structure</a>
                    <a href="{{ route('employees.documents', $employee) }}"
                        class="list-group-item list-group-item-action"><i class="bi bi-file-text me-2"></i>Documents</a>
                    <a href="{{ route('employees.exit', $employee) }}" class="list-group-item list-group-item-action"><i
                            class="bi bi-box-arrow-right me-2"></i>Exit Management</a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Personal Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6"><strong>Name:</strong> {{ $employee->full_name }}</div>
                        <div class="col-sm-6"><strong>Employee Code:</strong> {{ $employee->employee_code }}</div>
                        <div class="col-sm-6"><strong>Date of Birth:</strong>
                            {{ $employee->date_of_birth?->format('d-m-Y') ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Gender:</strong> {{ ucfirst($employee->gender) }}</div>
                        <div class="col-sm-6"><strong>Marital Status:</strong>
                            {{ ucfirst($employee->marital_status ?? '—') }}</div>
                        <div class="col-sm-6"><strong>Blood Group:</strong> {{ $employee->blood_group ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Personal Email:</strong> {{ $employee->personal_email ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Official Email:</strong> {{ $employee->official_email ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Phone:</strong> {{ $employee->phone ?? '—' }}</div>
                        <div class="col-12"><strong>Address:</strong>
                            {{ $employee->address_line1 ?? '—' }}{{ $employee->city ? ', ' . $employee->city : '' }}</div>
                    </div>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">Job Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6"><strong>Branch:</strong> {{ $employee->branch->name ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Department:</strong> {{ $employee->department->name ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Designation:</strong> {{ $employee->designation->name ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Employee Type:</strong> {{ $employee->employeeType->name ?? '—' }}
                        </div>
                        <div class="col-sm-6"><strong>Date of Joining:</strong>
                            {{ $employee->date_of_joining?->format('d-m-Y') ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Shift:</strong> {{ $employee->shift->name ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Reporting To:</strong> {{ $employee->reportingTo->full_name ?? '—' }}
                        </div>
                        <div class="col-sm-6"><strong>Status:</strong> {{ ucfirst($employee->status) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
