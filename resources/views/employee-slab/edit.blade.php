@extends('layouts.app')

@section('title', 'Employee Slab')
@section('page-title', 'Employee Slab')
@section('page-subtitle', $employee->full_name)
@section('back-url', route('employee-slab.index'))

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- ── Section 1: Employment Information ───────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0">Employment Information</h6></div>
        <div class="card-body">
            <form action="{{ route('employees.update', $employee) }}" method="POST">
                @csrf @method('PUT')

                {{-- Steps 1-3 fields (Personal/Contact/Address) are already
                     saved — carry them forward unchanged so this form's own
                     `required` validation still passes when only Employment
                     Information is being filled in here. --}}
                <input type="hidden" name="first_name" value="{{ $employee->first_name }}">
                <input type="hidden" name="last_name" value="{{ $employee->last_name }}">
                <input type="hidden" name="date_of_birth" value="{{ $employee->date_of_birth?->format('Y-m-d') }}">
                <input type="hidden" name="gender" value="{{ $employee->gender }}">
                <input type="hidden" name="official_email" value="{{ $employee->official_email }}">
                <input type="hidden" name="phone" value="{{ $employee->phone }}">
                <input type="hidden" name="address_line1" value="{{ $employee->address_line1 }}">
                <input type="hidden" name="district" value="{{ $employee->district }}">
                <input type="hidden" name="state" value="{{ $employee->state }}">
                <input type="hidden" name="pincode" value="{{ $employee->pincode }}">
                <input type="hidden" name="permanent_address_line1" value="{{ $employee->permanent_address_line1 }}">
                <input type="hidden" name="biometric_id" value="{{ $employee->biometric_id }}">
                <input type="hidden" name="branch_id" value="{{ $currentBranch->id ?? $employee->branch_id }}">

                @php $currentCategory = old('employee_category', $employee->primary_employee_type === 'staff' ? 'staff' : $employee->labour_type); @endphp

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Employee Number</label>
                        <input type="text" class="form-control" value="{{ $employee->employee_code ?? 'Generated on save' }}" disabled>
                        <div class="form-text">Auto-generated and read-only.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Employee Category <span class="text-danger">*</span></label>
                        <select name="employee_category" id="employee_category" class="form-select @error('employee_category') is-invalid @enderror" required>
                            <option value="">Select</option>
                            <option value="staff" {{ $currentCategory == 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="company_labour" {{ $currentCategory == 'company_labour' ? 'selected' : '' }}>Company Labour</option>
                            <option value="contract_labour" {{ $currentCategory == 'contract_labour' ? 'selected' : '' }}>Contract Labour</option>
                        </select>
                        @error('employee_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Employee Type <span class="text-danger">*</span></label>
                        <select name="employee_type_id" class="form-select @error('employee_type_id') is-invalid @enderror" data-searchable required>
                            <option value="">Select</option>
                            @foreach ($employeeTypes as $et)
                                <option value="{{ $et->id }}" {{ old('employee_type_id', $employee->employee_type_id) == $et->id ? 'selected' : '' }}>{{ $et->name }}</option>
                            @endforeach
                        </select>
                        @error('employee_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        @php $currentStatus = old('status', $employee->status ?? 'active'); @endphp
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ $currentStatus == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="probation" {{ $currentStatus == 'probation' ? 'selected' : '' }}>Probation</option>
                            <option value="inactive" {{ $currentStatus == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="terminated" {{ $currentStatus == 'terminated' ? 'selected' : '' }}>Terminated</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if (! $employee->user)
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="create_user" id="create_user" class="form-check-input" value="1">
                                <label class="form-check-label" for="create_user">Create Login User</label>
                            </div>
                        </div>
                        <div class="col-md-3" id="create-user-role-field" style="display:none;">
                            <label class="form-label">Role</label>
                            <select name="role_id" class="form-select" data-searchable>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ $role->name === 'employee' ? 'selected' : '' }}>{{ $role->display_name ?? $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" data-searchable required>
                            <option value="">Select</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" {{ old('department_id', $employee->department_id) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Designation <span class="text-danger">*</span></label>
                        <input type="text" name="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation', $employee->designation->name ?? '') }}" required>
                        @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Joining <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_joining" class="form-control @error('date_of_joining') is-invalid @enderror" value="{{ old('date_of_joining', $employee->date_of_joining?->format('Y-m-d')) }}" required>
                        @error('date_of_joining')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Shift</label>
                        <select name="shift_id" class="form-select @error('shift_id') is-invalid @enderror" data-searchable>
                            <option value="">Select</option>
                            @foreach ($shifts as $s)
                                <option value="{{ $s->id }}" {{ old('shift_id', $employee->shift_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('shift_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Reporting To</label>
                        <select name="reporting_to" class="form-select @error('reporting_to') is-invalid @enderror" data-searchable>
                            <option value="">Select</option>
                            @foreach ($managers as $m)
                                @if ($m->id !== $employee->id)
                                    <option value="{{ $m->id }}" {{ old('reporting_to', $employee->reporting_to) == $m->id ? 'selected' : '' }}>{{ $m->full_name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('reporting_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Probation End Date</label>
                        <input type="date" name="probation_end_date" class="form-control @error('probation_end_date') is-invalid @enderror" value="{{ old('probation_end_date', $employee->probation_end_date?->format('Y-m-d')) }}">
                        @error('probation_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Confirmation Date</label>
                        <input type="date" name="date_of_confirmation" class="form-control @error('date_of_confirmation') is-invalid @enderror" value="{{ old('date_of_confirmation', $employee->date_of_confirmation?->format('Y-m-d')) }}">
                        @error('date_of_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Contract Labour Information — shown only when Employee Category = Contract Labour. --}}
                <div id="contract-labour-fields" class="row g-3 mt-1" style="display:none;">
                    <div class="col-12"><h6 class="fw-bold border-bottom pb-2 mt-2">Contract Labour Information</h6></div>
                    <div class="col-md-4">
                        <label class="form-label">Contractor <span class="text-danger">*</span></label>
                        <select name="contractor_id" class="form-select @error('contractor_id') is-invalid @enderror" data-searchable>
                            <option value="">Select</option>
                            @foreach ($contractors as $c)
                                <option value="{{ $c->id }}" {{ old('contractor_id', $employee->contractor_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('contractor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Contractor Employee Number</label>
                        <input type="text" name="contractor_employee_number" class="form-control" value="{{ old('contractor_employee_number', $employee->contractor_employee_number) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Work Order Number</label>
                        <input type="text" name="work_order_number" class="form-control" value="{{ old('work_order_number', $employee->work_order_number) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Contract Start Date</label>
                        <input type="date" name="contract_start_date" class="form-control @error('contract_start_date') is-invalid @enderror" value="{{ old('contract_start_date', $employee->contract_start_date?->format('Y-m-d')) }}">
                        @error('contract_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Contract End Date</label>
                        <input type="date" name="contract_end_date" class="form-control @error('contract_end_date') is-invalid @enderror" value="{{ old('contract_end_date', $employee->contract_end_date?->format('Y-m-d')) }}">
                        @error('contract_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Labour Category</label>
                        <input type="text" name="labour_category" class="form-control" value="{{ old('labour_category', $employee->labour_category) }}">
                    </div>
                    @if($canSetContractorRate)
                    <div class="col-md-3">
                        <label class="form-label">Contractor Rate</label>
                        <input type="number" step="0.01" name="contractor_rate" class="form-control" value="{{ old('contractor_rate', $employee->contractor_rate) }}">
                    </div>
                    @endif
                    <div class="col-12">
                        <label class="form-label">Contractor Remarks</label>
                        <textarea name="contractor_remarks" class="form-control" rows="2">{{ old('contractor_remarks', $employee->contractor_remarks) }}</textarea>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save Employment Information</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Section 2: Bank Information ──────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0">Bank Information</h6></div>
        <div class="card-body">
            @if ($employee->bankDetails->isNotEmpty())
                <div class="table-responsive mb-3">
                    <table class="table table-sm">
                        <thead><tr><th>Bank</th><th>Account Holder</th><th>Account Number</th><th>IFSC</th><th>Primary</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($employee->bankDetails as $bd)
                                <tr>
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
            @else
                <p class="text-muted">No bank details on record yet.</p>
            @endif

            <form action="{{ route('employees.bank-details.store', $employee) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Bank Name</label>
                        <select name="bank_id" class="form-select" data-searchable>
                            <option value="">Select</option>
                            @foreach ($banks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Not listed? Type it in "Other Bank Name" instead.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Other Bank Name (if not listed above)</label>
                        <input type="text" name="bank_name" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Account Holder Name</label>
                        <input type="text" name="account_holder_name" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account Number</label>
                        <div class="input-group">
                            <input type="password" name="account_number" class="form-control masked-toggle-field" autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary masked-toggle-btn" tabindex="-1"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Confirm Account Number</label>
                        <div class="input-group">
                            <input type="password" name="account_number_confirmation" class="form-control masked-toggle-field" autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary masked-toggle-btn" tabindex="-1"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">IFSC Code</label>
                        <input type="text" name="ifsc_code" class="form-control" style="text-transform:uppercase">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bank Branch</label>
                        <input type="text" name="branch_name" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account Type</label>
                        <select name="account_type" class="form-select">
                            <option value="">Select</option>
                            <option value="savings">Savings</option>
                            <option value="current">Current</option>
                            <option value="salary">Salary</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_primary" value="1" class="form-check-input">
                            <label class="form-check-label">Set as Primary</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save Bank Information</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Section 3: Designation & Salary ──────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0">Designation & Salary</h6></div>
        <div class="card-body">
            @if($employee->currentSalary)
                @php $salary = $employee->currentSalary; @endphp
                <div class="alert alert-light border mb-3">
                    <div class="row">
                        <div class="col-sm-3"><strong>Basic:</strong> ₹{{ number_format($salary->basic_salary, 2) }}</div>
                        <div class="col-sm-3"><strong>Gross:</strong> ₹{{ number_format($salary->gross_salary, 2) }}</div>
                        <div class="col-sm-3"><strong>CTC:</strong> ₹{{ number_format($salary->ctc, 2) }}</div>
                        <div class="col-sm-3"><strong>Net Salary:</strong> ₹{{ number_format($salary->net_salary, 2) }}</div>
                    </div>
                </div>
            @endif

            <form action="{{ route('employees.salary.store', $employee) }}" method="POST" id="salaryForm">
                @csrf
                <div class="row g-3">
                    <div class="col-12"><h6 class="fw-bold border-bottom pb-2">Designation</h6></div>
                    <div class="col-md-3">
                        <label class="form-label">Employee Category <span class="text-danger">*</span></label>
                        @php $desigCategory = old('designation_employee_category', $employee->designation_employee_category); @endphp
                        <select name="designation_employee_category" id="designation_employee_category" class="form-select @error('designation_employee_category') is-invalid @enderror" required>
                            <option value="">Select</option>
                            <option value="company" {{ $desigCategory == 'company' ? 'selected' : '' }}>Company</option>
                            <option value="contractor" {{ $desigCategory == 'contractor' ? 'selected' : '' }}>Contractor</option>
                        </select>
                        @error('designation_employee_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    {{--
                        Condition — Employee Category = Contractor hides the
                        Type field entirely (it's implied); Company shows it.
                        The field stays in the DOM (required toggled via JS)
                        so old()-repopulation after a validation error still
                        works, it's just visually hidden when not applicable.
                    --}}
                    <div class="col-md-3" id="designation-type-field">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="designation_employee_type" id="designation_employee_type" class="form-select @error('designation_employee_type') is-invalid @enderror" data-selected="{{ old('designation_employee_type', $employee->designation_employee_type) }}">
                            <option value="">Select Category first</option>
                        </select>
                        @error('designation_employee_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3" id="designation-contractor-field">
                        <label class="form-label">Contractor Name <span class="text-danger">*</span></label>
                        <select name="designation_contractor_id" id="designation_contractor_id" class="form-select @error('designation_contractor_id') is-invalid @enderror" data-searchable>
                            <option value="">Select</option>
                            @foreach ($contractors as $c)
                                <option value="{{ $c->id }}" {{ old('designation_contractor_id', $employee->designation_contractor_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('designation_contractor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" data-searchable>
                            <option value="">Select</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" {{ old('department_id', $employee->department_id) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Designation</label>
                        <input type="text" name="designation" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation', $employee->designation->name ?? '') }}">
                        @error('designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 mt-2"><h6 class="fw-bold border-bottom pb-2">Salary</h6></div>
                    <div class="col-md-6">
                        <label class="form-label">Salary Slab <span class="text-danger">*</span></label>
                        <select name="salary_slab_id" id="salary_slab_id" class="form-select @error('salary_slab_id') is-invalid @enderror" data-searchable required>
                            <option value="">Select</option>
                            @foreach($salarySlabs as $slab)
                            <option value="{{ $slab->id }}" {{ old('salary_slab_id', $employee->currentSalary?->salary_slab_id) == $slab->id ? 'selected' : '' }}>
                                {{ $slab->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('salary_slab_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">The employee's entire salary structure is inherited from the selected slab — nothing below is manually entered.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from', now()->format('Y-m-d')) }}" required>
                        @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to') }}">
                        @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Leave blank for an open-ended (current) structure.</div>
                    </div>

                    <div class="col-12 mt-2"><h6 class="fw-bold border-bottom pb-2">Overtime Applicability</h6></div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="hidden" name="is_ot_applicable" value="0">
                            <input type="checkbox" name="is_ot_applicable" id="is_ot_applicable" class="form-check-input" value="1" {{ old('is_ot_applicable', $employee->is_ot_applicable) ? 'checked' : '' }}>
                            <label class="form-check-label">OT Applicable</label>
                        </div>
                    </div>

                    <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">TDS / PF / ESI Percentages <small class="fw-normal text-muted">(read-only — from the selected Salary Slab)</small></h6></div>
                    <div class="col-md-2"><label class="form-label">TDS %</label><input type="text" id="inherited_tds_percentage" class="form-control" value="" disabled></div>
                    <div class="col-md-2"><label class="form-label">PF Employee %</label><input type="text" id="inherited_pf_employee_percentage" class="form-control" value="" disabled></div>
                    <div class="col-md-2"><label class="form-label">PF Employer %</label><input type="text" id="inherited_pf_employer_percentage" class="form-control" value="" disabled></div>
                    <div class="col-md-2"><label class="form-label">ESI Employee %</label><input type="text" id="inherited_esi_employee_percentage" class="form-control" value="" disabled></div>
                    <div class="col-md-2"><label class="form-label">ESI Employer %</label><input type="text" id="inherited_esi_employer_percentage" class="form-control" value="" disabled></div>

                    <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Salary Structure <small class="fw-normal text-muted">(read-only — from the selected Salary Slab)</small></h6></div>
                    <div class="col-md-3"><label class="form-label">Basic Salary (₹)</label><input type="text" id="inherited_basic_salary" class="form-control" value="" disabled></div>
                    <div class="col-md-3"><label class="form-label">Gross Salary (₹)</label><input type="text" id="inherited_gross_salary" class="form-control" value="" disabled></div>
                    <div class="col-md-3"><label class="form-label">CTC (₹)</label><input type="text" id="inherited_ctc" class="form-control" value="" disabled></div>
                    <div class="col-md-3"><label class="form-label">Employer Contribution (₹)</label><input type="text" id="inherited_employer_contributions" class="form-control" value="" disabled></div>
                    <div class="col-md-3"><label class="form-label">Total Deductions (₹)</label><input type="text" id="inherited_total_deductions" class="form-control" value="" disabled></div>
                    <div class="col-md-3"><label class="form-label">Net Salary (₹)</label><input type="text" id="inherited_net_salary" class="form-control" value="" disabled></div>

                    <div class="col-12 mt-2">
                        <h6 class="fw-bold border-bottom pb-2">Salary Components <small class="fw-normal text-muted">(read-only — from the selected Salary Slab)</small></h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6 class="small text-muted">Earnings Components</h6>
                                <table class="table table-sm">
                                    <thead><tr><th>Component</th><th>Calc. Type</th><th>Calc. Base</th><th>Amount</th></tr></thead>
                                    <tbody id="inherited_earning_components"><tr><td colspan="4" class="text-muted">Select a Salary Slab above</td></tr></tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="small text-muted">Deduction Components</h6>
                                <table class="table table-sm">
                                    <thead><tr><th>Component</th><th>Calc. Type</th><th>Calc. Base</th><th>Amount</th></tr></thead>
                                    <tbody id="inherited_deduction_components"><tr><td colspan="4" class="text-muted">Select a Salary Slab above</td></tr></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-success">Save Designation & Salary</button>
                </div>
            </form>
        </div>
    </div>

    <a href="{{ route('employee-slab.index') }}" class="btn btn-secondary">Back to Employee Slab List</a>
@endsection

@push('scripts')
<script>
    window.__employeeSlab = {
        salarySlabBreakdownUrl: @json(route('employees.salary-slab-breakdown', ['salarySlab' => 'SLAB_ID'])),
        currentSalaryBreakdown: @json($currentSalaryBreakdown),
    };
</script>
<script>
    (function () {
        function makeSearchable(select) {
            if (!select || select.dataset.searchableInit) return;
            select.dataset.searchableInit = '1';
            const wrapper = document.createElement('div');
            wrapper.className = 'position-relative';
            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(select);
            select.classList.add('d-none');
            const input = document.createElement('input');
            input.type = 'text';
            input.className = (select.className || 'form-select').replace('d-none', '').trim();
            input.placeholder = 'Search…';
            input.autocomplete = 'off';
            wrapper.appendChild(input);
            const list = document.createElement('div');
            list.className = 'list-group position-absolute w-100 shadow-sm';
            list.style.cssText = 'z-index:1000;max-height:220px;overflow-y:auto;display:none;';
            wrapper.appendChild(list);
            function optionsData() {
                return Array.from(select.options).filter(o => o.value !== '').map(o => ({ value: o.value, label: o.textContent }));
            }
            function syncInputFromSelect() {
                const opt = select.options[select.selectedIndex];
                input.value = (opt && opt.value !== '') ? opt.textContent.trim() : '';
            }
            function renderList(filter) {
                const q = (filter || '').toLowerCase();
                const matches = optionsData().filter(o => o.label.toLowerCase().includes(q));
                list.innerHTML = matches.length
                    ? matches.map(o => `<button type="button" class="list-group-item list-group-item-action" data-value="${o.value}">${o.label}</button>`).join('')
                    : '<div class="list-group-item text-muted">No matches</div>';
                list.style.display = 'block';
                list.querySelectorAll('[data-value]').forEach(function (btn) {
                    btn.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        select.value = btn.dataset.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        syncInputFromSelect();
                        list.style.display = 'none';
                    });
                });
            }
            input.addEventListener('focus', function () { renderList(''); });
            input.addEventListener('input', function () { renderList(input.value); });
            input.addEventListener('blur', function () { setTimeout(function () { list.style.display = 'none'; syncInputFromSelect(); }, 150); });
            syncInputFromSelect();
        }
        document.querySelectorAll('[data-searchable]').forEach(makeSearchable);

        document.querySelectorAll('.masked-toggle-btn').forEach(function (btn) {
            const field = btn.closest('.input-group').querySelector('.masked-toggle-field');
            btn.addEventListener('click', function () {
                const showing = field.type === 'text';
                field.type = showing ? 'password' : 'text';
                btn.querySelector('i').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        });

        // ── Employment Information: Create Login User reveals the Role select. ──
        const createUserCheckbox = document.getElementById('create_user');
        const createUserRoleField = document.getElementById('create-user-role-field');
        function refreshCreateUserRole() {
            if (!createUserCheckbox || !createUserRoleField) return;
            createUserRoleField.style.display = createUserCheckbox.checked ? '' : 'none';
        }
        if (createUserCheckbox) {
            createUserCheckbox.addEventListener('change', refreshCreateUserRole);
            refreshCreateUserRole();
        }

        // ── Employment Information: Contract Labour fields only for Contract Labour category ──
        const categorySelect = document.getElementById('employee_category');
        const contractFields = document.getElementById('contract-labour-fields');
        function refreshContractFields() {
            if (!categorySelect || !contractFields) return;
            contractFields.style.display = categorySelect.value === 'contract_labour' ? '' : 'none';
        }
        if (categorySelect) {
            categorySelect.addEventListener('change', refreshContractFields);
            refreshContractFields();
        }

        // ── Designation & Salary: Employee Category drives Type's options/visibility. ──
        const desigCategorySelect = document.getElementById('designation_employee_category');
        const desigTypeSelect = document.getElementById('designation_employee_type');
        const desigTypeField = document.getElementById('designation-type-field');
        const desigContractorField = document.getElementById('designation-contractor-field');
        const desigContractorSelect = document.getElementById('designation_contractor_id');
        const DESIGNATION_TYPE_OPTIONS = {
            company: [['staff', 'Staff'], ['labor', 'Labor']],
            contractor: [['contractor_staff', 'Contractor Staff'], ['contractor_labor', 'Contractor Labor']],
        };
        function refreshDesignationType(restorePrevious) {
            if (!desigCategorySelect || !desigTypeSelect) return;
            const category = desigCategorySelect.value;
            const previous = restorePrevious ? desigTypeSelect.dataset.selected : desigTypeSelect.value;
            const options = DESIGNATION_TYPE_OPTIONS[category] || [];
            desigTypeSelect.innerHTML = options.length
                ? options.map(([v, label]) => `<option value="${v}">${label}</option>`).join('')
                : '<option value="">Select Category first</option>';
            if (previous && options.some(([v]) => v === previous)) {
                desigTypeSelect.value = previous;
            }
        }
        // Condition — Employee Category = Contractor hides Type entirely
        // (Company shows it). Contractor Name is shown only for Contractor.
        function refreshDesignationVisibility() {
            if (!desigCategorySelect) return;
            const category = desigCategorySelect.value;
            const isContractor = category === 'contractor';
            const isCompany = category === 'company';
            if (desigTypeField) desigTypeField.style.display = isCompany ? '' : 'none';
            if (desigTypeSelect) desigTypeSelect.toggleAttribute('required', isCompany);
            if (desigContractorField) desigContractorField.style.display = isContractor ? '' : 'none';
            if (desigContractorSelect) desigContractorSelect.toggleAttribute('required', isContractor);
        }
        if (desigCategorySelect) {
            refreshDesignationType(true);
            refreshDesignationVisibility();
            desigCategorySelect.addEventListener('change', function () {
                refreshDesignationType(false);
                refreshDesignationVisibility();
            });
        }

        // ── Designation & Salary: selected Salary Slab drives the read-only breakdown. ──
        const salarySlabSelect = document.getElementById('salary_slab_id');
        const inheritedFields = {
            tds_percentage: document.getElementById('inherited_tds_percentage'),
            pf_employee_percentage: document.getElementById('inherited_pf_employee_percentage'),
            pf_employer_percentage: document.getElementById('inherited_pf_employer_percentage'),
            esi_employee_percentage: document.getElementById('inherited_esi_employee_percentage'),
            esi_employer_percentage: document.getElementById('inherited_esi_employer_percentage'),
            basic_salary: document.getElementById('inherited_basic_salary'),
            gross_salary: document.getElementById('inherited_gross_salary'),
            ctc: document.getElementById('inherited_ctc'),
            employer_contributions: document.getElementById('inherited_employer_contributions'),
            total_deductions: document.getElementById('inherited_total_deductions'),
            net_salary: document.getElementById('inherited_net_salary'),
        };
        const earningComponentsBody = document.getElementById('inherited_earning_components');
        const deductionComponentsBody = document.getElementById('inherited_deduction_components');

        function formatNumber(value) {
            return (typeof value === 'number' ? value : parseFloat(value || 0)).toFixed(2);
        }
        function componentRows(components) {
            if (!components || !components.length) return '<tr><td colspan="4" class="text-muted">None</td></tr>';
            return components.map(function (c) {
                return '<tr><td>' + c.name + '</td><td>' + (c.calculation_type ? c.calculation_type.charAt(0).toUpperCase() + c.calculation_type.slice(1) : '—')
                    + '</td><td>' + (c.calculation_base || '—') + '</td><td>₹' + formatNumber(c.amount) + '</td></tr>';
            }).join('');
        }
        function renderSalarySlabBreakdown(data) {
            Object.keys(inheritedFields).forEach(function (key) {
                var field = inheritedFields[key];
                if (field) field.value = data ? formatNumber(data[key]) : '';
            });
            var placeholder = '<tr><td colspan="4" class="text-muted">' + (data ? 'None' : 'Select a Salary Slab above') + '</td></tr>';
            if (earningComponentsBody) earningComponentsBody.innerHTML = data ? componentRows(data.earning_components) : placeholder;
            if (deductionComponentsBody) deductionComponentsBody.innerHTML = data ? componentRows(data.deduction_components) : placeholder;
        }
        function fetchAndRenderSlabBreakdown(slabId) {
            if (!slabId) { renderSalarySlabBreakdown(null); return; }
            var url = window.__employeeSlab.salarySlabBreakdownUrl.replace('SLAB_ID', encodeURIComponent(slabId));
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(renderSalarySlabBreakdown)
                .catch(function () { renderSalarySlabBreakdown(null); });
        }
        if (salarySlabSelect) {
            salarySlabSelect.addEventListener('change', function () { fetchAndRenderSlabBreakdown(salarySlabSelect.value); });
        }
        if (window.__employeeSlab.currentSalaryBreakdown) {
            renderSalarySlabBreakdown(window.__employeeSlab.currentSalaryBreakdown);
        }
        if (salarySlabSelect && salarySlabSelect.value) {
            fetchAndRenderSlabBreakdown(salarySlabSelect.value);
        }
    })();
</script>
@endpush
