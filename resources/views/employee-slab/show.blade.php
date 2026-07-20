@extends('layouts.app')

@section('title', 'Employee Slab')
@section('page-title', $employee->full_name)
@section('page-subtitle', 'Employee Slab #' . ($employee->employee_code ?? '—'))
@section('back-url', route('employee-slab.index'))

@section('page-actions')
    <a href="{{ route('employee-slab.edit', $employee) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    @if ($employee->profile_photo)
                        <img src="{{ $employee->profile_photo_url }}" alt="" class="rounded-circle mb-3" style="width:80px;height:80px;object-fit:cover;">
                    @else
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3"
                            style="width:80px;height:80px;">
                            <span class="text-white fw-bold fs-3">{{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name ?? '', 0, 1)) }}</span>
                        </div>
                    @endif
                    <h5 class="mb-1">{{ $employee->display_name_or_default }}</h5>
                    <p class="text-muted mb-1">{{ $employee->designation->name ?? '—' }}</p>
                    <span class="badge bg-{{ $employee->status == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($employee->status) }}</span>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">Quick Links</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('employees.show', $employee) }}" class="list-group-item list-group-item-action"><i class="bi bi-person-vcard me-2"></i>Create Employee</a>
                    <a href="{{ route('employee-document.show', $employee) }}" class="list-group-item list-group-item-action"><i class="bi bi-file-text me-2"></i>Employee Document</a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Employment Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6"><strong>Employee Number:</strong> {{ $employee->employee_code ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Employee Category:</strong> {{ $employee->system_classification }}</div>
                        <div class="col-sm-6"><strong>Employee Type:</strong> {{ $employee->employeeType->name ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Status:</strong> {{ ucfirst($employee->status) }}</div>
                        <div class="col-sm-6"><strong>Department:</strong> {{ $employee->department->name ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Designation:</strong> {{ $employee->designation->name ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Date of Joining:</strong> {{ $employee->date_of_joining?->format('d-M-Y') ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Shift:</strong> {{ $employee->shift->name ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Reporting To:</strong> {{ $employee->reportingTo->full_name ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Probation End Date:</strong> {{ $employee->probation_end_date?->format('d-M-Y') ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Confirmation Date:</strong> {{ $employee->date_of_confirmation?->format('d-M-Y') ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Login User:</strong> {{ $employee->user->email ?? 'Not created' }}</div>
                    </div>

                    @if ($employee->labour_type === 'contract_labour')
                        <h6 class="fw-bold border-bottom pb-2 mt-4">Contract Labour Information</h6>
                        <div class="row g-3">
                            <div class="col-sm-6"><strong>Contractor:</strong> {{ $employee->contractor->name ?? '—' }}</div>
                            <div class="col-sm-6"><strong>Contractor Employee No.:</strong> {{ $employee->contractor_employee_number ?? '—' }}</div>
                            <div class="col-sm-6"><strong>Work Order No.:</strong> {{ $employee->work_order_number ?? '—' }}</div>
                            <div class="col-sm-6"><strong>Labour Category:</strong> {{ $employee->labour_category ?? '—' }}</div>
                            <div class="col-sm-6"><strong>Contract Period:</strong> {{ $employee->contract_start_date?->format('d-M-Y') ?? '—' }} to {{ $employee->contract_end_date?->format('d-M-Y') ?? '—' }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Bank Information</div>
                <div class="card-body">
                    @if ($employee->bankDetails->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Bank</th><th>Account Holder</th><th>Account Number</th><th>IFSC</th><th>Account Type</th><th>Primary</th></tr></thead>
                                <tbody>
                                    @foreach ($employee->bankDetails as $bd)
                                        <tr>
                                            <td>{{ $bd->bank->name ?? $bd->bank_name ?? '—' }}</td>
                                            <td>{{ $bd->account_holder_name ?? '—' }}</td>
                                            <td>{{ $canViewFullBankDetails ? $bd->account_number : $bd->masked_account_number }}</td>
                                            <td>{{ $bd->ifsc_code ?? '—' }}</td>
                                            <td>{{ ucfirst($bd->account_type ?? '—') }}</td>
                                            <td>{{ $bd->is_primary ? 'Yes' : '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No bank details on record yet.</p>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Designation & Salary</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6"><strong>Employee Category:</strong> {{ ucfirst($employee->designation_employee_category ?? '—') }}</div>
                        @if ($employee->designation_employee_category === 'company')
                            <div class="col-sm-6"><strong>Type:</strong> {{ $employee->designation_employee_type_label ?? '—' }}</div>
                        @endif
                        @if ($employee->designation_employee_category === 'contractor')
                            <div class="col-sm-6"><strong>Contractor Name:</strong> {{ $employee->designationContractor->name ?? '—' }}</div>
                        @endif
                    </div>

                    @if ($employee->currentSalary)
                        @php $salary = $employee->currentSalary; @endphp
                        <h6 class="fw-bold border-bottom pb-2 mt-3">Salary Structure</h6>
                        <div class="row g-3">
                            <div class="col-sm-6"><strong>Salary Slab:</strong> {{ $salary->slab->name ?? '—' }}</div>
                            <div class="col-sm-6"><strong>Effective From:</strong> {{ $salary->effective_from?->format('d-M-Y') }}</div>
                            <div class="col-sm-6"><strong>Effective To:</strong> {{ $salary->effective_to?->format('d-M-Y') ?? 'Current' }}</div>
                            <div class="col-sm-6"><strong>OT Applicable:</strong> {{ $employee->is_ot_applicable ? 'Yes' : 'No' }}</div>
                            <div class="col-sm-6"><strong>Basic Salary:</strong> ₹{{ number_format($salary->basic_salary, 2) }}</div>
                            <div class="col-sm-6"><strong>Gross Salary:</strong> ₹{{ number_format($salary->gross_salary, 2) }}</div>
                            <div class="col-sm-6"><strong>CTC:</strong> ₹{{ number_format($salary->ctc, 2) }}</div>
                            <div class="col-sm-6"><strong>Net Salary:</strong> ₹{{ number_format($salary->net_salary, 2) }}</div>
                        </div>

                        @if ($salary->components->isNotEmpty())
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <h6 class="small text-muted">Earnings Components</h6>
                                    <table class="table table-sm">
                                        <thead><tr><th>Component</th><th>Type</th><th>Amount</th></tr></thead>
                                        <tbody>
                                            @foreach ($salary->components->where('component_type', 'earning') as $c)
                                                <tr><td>{{ $c->component_name }}</td><td>{{ ucfirst($c->calculation_type) }}</td><td>₹{{ number_format($c->calculated_amount, 2) }}</td></tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="small text-muted">Deduction Components</h6>
                                    <table class="table table-sm">
                                        <thead><tr><th>Component</th><th>Type</th><th>Amount</th></tr></thead>
                                        <tbody>
                                            @foreach ($salary->components->where('component_type', 'deduction') as $c)
                                                <tr><td>{{ $c->component_name }}</td><td>{{ ucfirst($c->calculation_type) }}</td><td>₹{{ number_format($c->calculated_amount, 2) }}</td></tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @else
                        <p class="text-muted mb-0 mt-3">No salary structure recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
