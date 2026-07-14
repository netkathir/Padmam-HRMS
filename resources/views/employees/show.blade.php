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
                    @if ($employee->profile_photo)
                        <img src="{{ $employee->profile_photo_url }}" alt="" class="rounded-circle mb-3" style="width:80px;height:80px;object-fit:cover;">
                    @else
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3"
                            style="width:80px;height:80px;">
                            <span
                                class="text-white fw-bold fs-3">{{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name ?? '', 0, 1)) }}</span>
                        </div>
                    @endif
                    <h5 class="mb-1">{{ $employee->display_name_or_default }}</h5>
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
                        @can('rule_engine.full')
                        <div class="col-sm-6"><strong>Weekly Off Rule:</strong> {{ $employee->weeklyOffRuleOverride->name ?? 'Automatic' }}</div>
                        <div class="col-sm-6"><strong>Attendance Rule:</strong> {{ $employee->attendanceRuleOverride->name ?? 'Automatic' }}</div>
                        <div class="col-sm-6"><strong>Payroll Rule:</strong> {{ $employee->payrollRuleOverride->name ?? 'Automatic' }}</div>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Government IDs &amp; Statutory</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6"><strong>Aadhaar Number:</strong> {{ $employee->aadhaar_number ?? '—' }}</div>
                        <div class="col-sm-6"><strong>PAN Number:</strong> {{ $employee->pan_number ?? '—' }}</div>
                        <div class="col-sm-6"><strong>UAN Number:</strong> {{ $employee->uan_number ?? '—' }}</div>
                        <div class="col-sm-6"><strong>PF Applicable:</strong> {{ $employee->is_pf_applicable ? 'Yes' : 'No' }}{{ $employee->pf_number ? ' (' . $employee->pf_number . ')' : '' }}</div>
                        <div class="col-sm-6"><strong>ESI Applicable:</strong> {{ $employee->is_esi_applicable ? 'Yes' : 'No' }}{{ $employee->esi_number ? ' (' . $employee->esi_number . ')' : '' }}</div>
                        <div class="col-sm-6"><strong>TDS Applicable:</strong> {{ $employee->is_tds_applicable ? 'Yes' : 'No' }}</div>
                    </div>
                </div>
            </div>

            @if ($employee->labour_type === 'contract_labour')
            <div class="card mt-3">
                <div class="card-header">Contract Labour Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6"><strong>Contractor:</strong> {{ $employee->contractor->name ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Contractor Employee No.:</strong> {{ $employee->contractor_employee_number ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Work Order No.:</strong> {{ $employee->work_order_number ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Labour Category:</strong> {{ $employee->labour_category ?? '—' }}</div>
                        <div class="col-sm-6"><strong>Contract Period:</strong>
                            {{ $employee->contract_start_date?->format('d-m-Y') ?? '—' }} to {{ $employee->contract_end_date?->format('d-m-Y') ?? '—' }}
                            @if ($employee->isContractExpired())
                                <span class="badge bg-danger ms-1">Expired</span>
                            @elseif ($employee->isContractExpiringSoon())
                                <span class="badge bg-warning text-dark ms-1">Expiring Soon</span>
                            @endif
                        </div>
                        @can('employees.full')
                        <div class="col-sm-6"><strong>Contractor Rate:</strong> {{ $employee->contractor_rate ?? '—' }}</div>
                        @endcan
                        @if ($employee->contractor_remarks)
                        <div class="col-12"><strong>Remarks:</strong> {{ $employee->contractor_remarks }}</div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    Bank Details
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#add-bank-detail-form">
                        <i class="bi bi-plus"></i> Add
                    </button>
                </div>
                <div class="card-body">
                    @if ($employee->bankDetails->isEmpty())
                        <p class="text-muted mb-0">No bank details on record.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Mode</th><th>Bank</th><th>Account Holder</th><th>Account Number</th><th>IFSC</th><th>Primary</th><th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($employee->bankDetails as $bd)
                                        <tr>
                                            <td>{{ ucfirst(str_replace('_', ' ', $bd->payment_mode)) }}</td>
                                            <td>{{ $bd->bank->name ?? $bd->bank_name ?? '—' }}</td>
                                            <td>{{ $bd->account_holder_name ?? '—' }}</td>
                                            <td>{{ $canViewFullBankDetails ? $bd->account_number : $bd->masked_account_number }}</td>
                                            <td>{{ $bd->ifsc_code ?? '—' }}</td>
                                            <td>{{ $bd->is_primary ? 'Yes' : '' }}</td>
                                            <td>
                                                @can('employees.full')
                                                <form action="{{ route('employees.bank-details.destroy', [$employee, $bd]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove these bank details?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    <div class="collapse mt-3" id="add-bank-detail-form">
                        <form action="{{ route('employees.bank-details.store', $employee) }}" method="POST">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Payment Mode</label>
                                    <select name="payment_mode" class="form-select">
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="cash">Cash</option>
                                        <option value="cheque">Cheque</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Bank</label>
                                    <select name="bank_id" class="form-select">
                                        <option value="">Select</option>
                                        @foreach ($banks ?? [] as $bank)
                                            <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Bank Name (if not listed)</label>
                                    <input type="text" name="bank_name" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Account Holder Name</label>
                                    <input type="text" name="account_holder_name" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Account Number</label>
                                    <input type="text" name="account_number" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Confirm Account Number</label>
                                    <input type="text" name="account_number_confirmation" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">IFSC Code</label>
                                    <input type="text" name="ifsc_code" class="form-control" style="text-transform:uppercase">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Branch Name</label>
                                    <input type="text" name="branch_name" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Account Type</label>
                                    <select name="account_type" class="form-select">
                                        <option value="savings">Savings</option>
                                        <option value="current">Current</option>
                                        <option value="salary">Salary</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_primary" value="1" class="form-check-input">
                                        <label class="form-check-label">Set as Primary</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm mt-3">Save Bank Details</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
