@extends('layouts.app')

@section('title', 'Edit Employee')
@section('page-title', 'Edit Employee')
@section('page-subtitle', $employee->full_name)

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($employee->is_draft)
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> This employee is still a draft. Complete all tabs and click "Save Employee" on the final tab to finish registration.</div>
    @endif
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-4 flex-nowrap overflow-x-auto overflow-y-hidden" id="employeeWizardNav">
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="2" data-step-label="Personal Information">Personal Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="3" data-step-label="Contact Information">Contact Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="4" data-step-label="Address Information">Address Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="5" data-step-label="Employment Information">Employment Information</button></li>
                <li class="nav-item" id="nav-tab-6-item"><button type="button" class="nav-link" data-nav-tab="6" data-step-label="Contract Labour Information">Contract Labour Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="8" data-step-label="Bank Information">Bank Information</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="9" data-step-label="Designation &amp; Salary">Designation &amp; Salary</button></li>
                <li class="nav-item"><button type="button" class="nav-link" data-nav-tab="10" data-step-label="Employee Documents">Employee Documents</button></li>
            </ul>

            <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data" id="employeeWizardForm">
                @csrf @method('PUT')
                @include('employees._tabs_1_7', ['employee' => $employee])
            </form>

            {{-- ── Tab 8: Bank Information ─────────────────────────────── --}}
            <div class="tab-pane" data-tab-pane="8">
                @if ($employee->bankDetails->isNotEmpty())
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead>
                                <tr><th>Bank</th><th>Account Holder</th><th>Account Number</th><th>IFSC</th><th>Primary</th><th></th></tr>
                            </thead>
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

                <form action="{{ route('employees.bank-details.store', $employee) }}" method="POST" id="bankDetailForm">
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
                            <label class="form-label" id="account-holder-label">Account Holder Name</label>
                            <input type="text" name="account_holder_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" id="account-number-label">Account Number</label>
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
                            <label class="form-label" id="ifsc-label">IFSC Code</label>
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
                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
                        <button type="submit" name="next_tab" value="9" class="btn btn-primary">Save &amp; Next</button>
                        <button type="submit" class="btn btn-outline-primary">Save as Draft</button>
                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            {{-- ── Tab 9: Designation & Salary ─────────────────────────────── --}}
            <div class="tab-pane" data-tab-pane="9">
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header"><h6 class="mb-0">Current Salary Summary</h6></div>
                            <div class="card-body">
                                @if($employee->currentSalary)
                                    @php $salary = $employee->currentSalary; @endphp
                                    <dl class="row mb-0">
                                        <dt class="col-sm-3">Basic</dt><dd class="col-sm-3">₹{{ number_format($salary->basic_salary, 2) }}</dd>
                                        <dt class="col-sm-3">Gross</dt><dd class="col-sm-3 fw-bold">₹{{ number_format($salary->gross_salary, 2) }}</dd>
                                        <dt class="col-sm-3">CTC</dt><dd class="col-sm-3">₹{{ number_format($salary->ctc, 2) }}</dd>
                                        <dt class="col-sm-3">Net Salary</dt><dd class="col-sm-3 fw-bold">₹{{ number_format($salary->net_salary, 2) }}</dd>
                                    </dl>
                                @else
                                    <p class="text-muted mb-0">No salary structure recorded yet.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($salaryHistory->isNotEmpty())
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header"><h6 class="mb-0">Salary History</h6></div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>Effective From</th><th>Effective To</th><th>Basic</th><th>Gross</th></tr></thead>
                                        <tbody>
                                            @foreach($salaryHistory as $hist)
                                            <tr>
                                                <td>{{ $hist->effective_from?->format('d M Y') }}</td>
                                                <td>{{ $hist->effective_to?->format('d M Y') ?? ($hist->is_current ? 'Current' : '—') }}</td>
                                                <td>₹{{ number_format($hist->basic_salary, 0) }}</td>
                                                <td>₹{{ number_format($hist->gross_salary, 0) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

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
                        <div class="col-md-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            @php $desigType = old('designation_employee_type', $employee->designation_employee_type); @endphp
                            <select name="designation_employee_type" id="designation_employee_type" class="form-select @error('designation_employee_type') is-invalid @enderror" data-selected="{{ $desigType }}" required>
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
                        <div class="col-md-3">
                            <label class="form-label">Biometric ID <span class="text-danger">*</span></label>
                            <input type="text" name="biometric_id" class="form-control @error('biometric_id') is-invalid @enderror" value="{{ old('biometric_id', $employee->biometric_id) }}" required>
                            @error('biometric_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        <div class="col-md-2">
                            <label class="form-label">TDS %</label>
                            <input type="text" id="inherited_tds_percentage" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">PF Employee %</label>
                            <input type="text" id="inherited_pf_employee_percentage" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">PF Employer %</label>
                            <input type="text" id="inherited_pf_employer_percentage" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">ESI Employee %</label>
                            <input type="text" id="inherited_esi_employee_percentage" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">ESI Employer %</label>
                            <input type="text" id="inherited_esi_employer_percentage" class="form-control" value="" disabled>
                        </div>

                        <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Salary Structure <small class="fw-normal text-muted">(read-only — from the selected Salary Slab)</small></h6></div>
                        <div class="col-md-3">
                            <label class="form-label">Basic Salary (₹)</label>
                            <input type="text" id="inherited_basic_salary" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gross Salary (₹)</label>
                            <input type="text" id="inherited_gross_salary" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CTC (₹)</label>
                            <input type="text" id="inherited_ctc" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Employer Contribution (₹)</label>
                            <input type="text" id="inherited_employer_contributions" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total Deductions (₹)</label>
                            <input type="text" id="inherited_total_deductions" class="form-control" value="" disabled>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Net Salary (₹)</label>
                            <input type="text" id="inherited_net_salary" class="form-control" value="" disabled>
                        </div>

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
                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
                        <button type="submit" name="next_tab" value="10" class="btn btn-primary">Save &amp; Next</button>
                        <button type="submit" class="btn btn-outline-primary">Save as Draft</button>
                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            {{-- ── Tab 10: Employee Documents ──────────────────────────── --}}
            <div class="tab-pane" data-tab-pane="10">
                @if(!empty($missingMandatoryDocs))
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Missing mandatory document(s):
                        {{ collect($missingMandatoryDocs)->map(fn($t) => ucfirst(str_replace('_',' ',$t)))->implode(', ') }}
                    </div>
                @endif

                @if ($employee->documents->isNotEmpty())
                    <div class="table-responsive mb-3">
                        <table class="table table-hover">
                            <thead><tr><th>Type</th><th>Number</th><th>Issued</th><th>Expiry</th><th>Remarks</th><th>Uploaded</th><th>Actions</th></tr></thead>
                            <tbody>
                                @foreach($employee->documents as $doc)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</td>
                                    <td>{{ $doc->document_number ?? '—' }}</td>
                                    <td>{{ $doc->issue_date?->format('d M Y') ?? '—' }}</td>
                                    <td>
                                        @if($doc->expiry_date)
                                            @php $exp = \Carbon\Carbon::parse($doc->expiry_date); @endphp
                                            <span class="{{ $exp->isPast() ? 'text-danger' : ($exp->between(now(), now()->addDays(30)) ? 'text-warning' : '') }}">{{ $exp->format('d M Y') }}</span>
                                        @else — @endif
                                    </td>
                                    <td>{{ $doc->remarks ?? '—' }}</td>
                                    <td>{{ $doc->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                        @can('employees.full')
                                        <form action="{{ route('employees.documents.destroy', [$employee, $doc]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this document?');">
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

                <form action="{{ route('employees.documents.upload', $employee) }}" method="POST" enctype="multipart/form-data" id="documentsForm">
                    @csrf
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Add Document(s)</h6>
                        <button type="button" id="addDocumentRow" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus"></i> Add Document</button>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm" id="documentsTable">
                            <thead><tr><th>Document Type</th><th>Document Number</th><th>Issue Date</th><th>Expiry Date</th><th>File</th><th>Remarks</th><th></th></tr></thead>
                            <tbody id="documentsBody"></tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload</button>
                </form>

                <div class="mt-4 d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary wizard-prev">Previous</button>
                    <form action="{{ route('employees.finalize', $employee) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">Save Employee</button>
                    </form>
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    // @json() splits its argument on every top-level comma (to support an
    // optional second "encoding options" argument), so a multi-key array
    // literal breaks it — each payload is precomputed into its own variable
    // first and @json() is only ever given a single bare variable here.
    // FSD — Salary Slab is the single source of truth; $currentSalaryBreakdown
    // (built by the controller straight from the employee's assigned Salary
    // Slab, in the SAME shape the live slab-breakdown endpoint returns) is
    // embedded here so one JS renderer handles both "already saved" and
    // "just picked a different slab" without a separate Blade-rendered
    // version, and without ever showing a stale snapshot figure.
    $wizardCurrentSalary = $currentSalaryBreakdown;
@endphp
@push('scripts')
<script>
    window.__employeeWizard = {
        activeTab: {{ (int) $activeTab }},
        currentSalaryBreakdown: @json($wizardCurrentSalary),
        // Full base-path-aware URL template (the app may be deployed in a
        // subdirectory, e.g. /Padmam-HRMS/public, so a root-relative
        // '/salary-slab-breakdown/…' would resolve to the wrong host root).
        salarySlabBreakdownUrl: @json(route('employees.salary-slab-breakdown', ['salarySlab' => 'SLAB_ID'])),
        documentTypes: {
            aadhaar: 'Aadhaar', pan: 'PAN', bank_proof: 'Bank Proof', appointment_letter: 'Appointment Letter',
            employment_agreement: 'Employment Agreement', education_certificate: 'Education Certificate',
            experience_certificate: 'Experience Certificate', contractor_id: 'Contractor ID', passport: 'Passport', other: 'Other',
        },
    };
</script>
<script>
    (function () {
        const TOTAL_TABS = 10;
        const categorySelect = document.getElementById('employee_category');
        const navTab6Item = document.getElementById('nav-tab-6-item');
        const sameAsCurrent = document.getElementById('same_as_current_address');
        const permanentFields = document.getElementById('permanent-address-fields');
        const dobInput = document.getElementById('date_of_birth');
        const ageDisplay = document.getElementById('age_display');
        const displayNameInput = document.getElementById('display_name');
        let displayNameEdited = !!(displayNameInput && displayNameInput.value.trim() !== '');

        function tab6Visible() {
            return categorySelect && categorySelect.value === 'contract_labour';
        }

        // FSD — searchable dropdowns for the master-data pickers (Department,
        // Designation, Employee Type, Shift, Reporting To, Contractor, State,
        // Salary Slab, Bank). Progressively enhances the existing <select>
        // with a type-to-filter text box; the native <select> stays the
        // actual form field (just visually hidden) so submission/validation/
        // old-value repopulation all keep working unchanged.
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
            input.addEventListener('blur', function () {
                setTimeout(function () { list.style.display = 'none'; syncInputFromSelect(); }, 150);
            });
            syncInputFromSelect();
        }

        // Step numbers are computed, not hardcoded — Contract Labour
        // Information is only ever visible for the Contract Labour category,
        // so a static "6. Bank Information" would show a gap (5 skipped)
        // whenever it's hidden. Recomputed every time visibility changes.
        function renumberNavTabs() {
            Array.from(document.querySelectorAll('#employeeWizardNav .nav-item'))
                .filter(function (li) { return li.style.display !== 'none'; })
                .forEach(function (li, index) {
                    var btn = li.querySelector('button[data-step-label]');
                    if (btn) btn.textContent = (index + 1) + '. ' + btn.dataset.stepLabel;
                });
        }

        function refreshTab6Nav() {
            if (!navTab6Item) return;
            navTab6Item.style.display = tab6Visible() ? '' : 'none';
            renumberNavTabs();
        }

        function togglePermanentFields() {
            if (!sameAsCurrent || !permanentFields) return;
            permanentFields.style.display = sameAsCurrent.checked ? 'none' : '';
        }

        function copyCurrentToPermanentAddress() {
            if (!sameAsCurrent || !sameAsCurrent.checked) return;
            const map = {
                address_line1: 'permanent_address_line1', address_line2: 'permanent_address_line2',
                city: 'permanent_city', district: 'permanent_district', state: 'permanent_state', pincode: 'permanent_pincode',
            };
            Object.entries(map).forEach(([src, dest]) => {
                const s = document.getElementById(src);
                const d = document.querySelector(`[name="${dest}"]`);
                if (s && d) d.value = s.value;
            });
        }

        function refreshAge() {
            if (!dobInput || !ageDisplay) return;
            if (!dobInput.value) { ageDisplay.value = ''; return; }
            const dob = new Date(dobInput.value);
            if (isNaN(dob.getTime())) { ageDisplay.value = ''; return; }
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
            ageDisplay.value = age >= 0 ? age : '';
        }

        function refreshDisplayName() {
            if (!displayNameInput || displayNameEdited) return;
            const first = document.getElementById('first_name')?.value || '';
            const middle = document.getElementById('middle_name')?.value || '';
            const last = document.getElementById('last_name')?.value || '';
            displayNameInput.value = [first, middle, last].filter(Boolean).join(' ');
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', function () { refreshTab6Nav(); });
            refreshTab6Nav();
        }
        if (sameAsCurrent && permanentFields) {
            sameAsCurrent.addEventListener('change', function () { togglePermanentFields(); copyCurrentToPermanentAddress(); });
            togglePermanentFields();
            ['address_line1', 'address_line2', 'city', 'district', 'state', 'pincode'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) { el.addEventListener('input', copyCurrentToPermanentAddress); el.addEventListener('change', copyCurrentToPermanentAddress); }
            });
        }
        if (dobInput) { dobInput.addEventListener('change', refreshAge); refreshAge(); }
        if (displayNameInput) {
            displayNameInput.addEventListener('input', function () { displayNameEdited = displayNameInput.value.trim() !== ''; });
            ['first_name', 'middle_name', 'last_name'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', refreshDisplayName);
            });
        }

        // Any "Save"-while-staying button (already-complete employee, tabs
        // 1-6) submits next_tab equal to its own pane, set once at load time.
        document.querySelectorAll('.wizard-save-stay').forEach(function (btn) {
            const pane = btn.closest('[data-tab-pane]');
            if (pane) btn.value = pane.dataset.tabPane;
        });

        // ── Tab navigation across all 10 panes ──
        const navButtons = Array.from(document.querySelectorAll('[data-nav-tab]'));
        const panes = Array.from(document.querySelectorAll('[data-tab-pane]'));

        function isTabEnabled(n) {
            if (n === 1 || n === 7) return false; // removed: Employee Classification / Statutory Information
            if (n === 6) return tab6Visible();
            return n >= 1 && n <= TOTAL_TABS;
        }
        function nextEnabledTab(from, direction) {
            let n = from + direction;
            while (n >= 1 && n <= TOTAL_TABS && !isTabEnabled(n)) n += direction;
            return Math.min(Math.max(n, 1), TOTAL_TABS);
        }
        function showTab(n) {
            panes.forEach(function (p) { p.style.display = (parseInt(p.dataset.tabPane, 10) === n) ? '' : 'none'; });
            navButtons.forEach(function (b) { b.classList.toggle('active', parseInt(b.dataset.navTab, 10) === n); });
        }
        navButtons.forEach(function (b) {
            b.addEventListener('click', function () { showTab(parseInt(b.dataset.navTab, 10)); });
        });
        document.querySelectorAll('.wizard-next').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const pane = btn.closest('[data-tab-pane]');
                const current = parseInt(pane.dataset.tabPane, 10);
                const requiredFields = pane.querySelectorAll('[required]');
                for (const f of requiredFields) { if (!f.reportValidity()) return; }
                showTab(nextEnabledTab(current, 1));
            });
        });
        document.querySelectorAll('.wizard-prev').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const pane = btn.closest('[data-tab-pane]');
                const current = parseInt(pane.dataset.tabPane, 10);
                showTab(nextEnabledTab(current, -1));
            });
        });

        showTab(isTabEnabled(window.__employeeWizard.activeTab) ? window.__employeeWizard.activeTab : 2);

        document.querySelectorAll('[data-searchable]').forEach(makeSearchable);

        // Account Number / Confirm Account Number are masked (password-style)
        // by default; the eye-icon toggles plain-text visibility for the user
        // to proofread what they typed.
        document.querySelectorAll('.masked-toggle-btn').forEach(function (btn) {
            const field = btn.closest('.input-group').querySelector('.masked-toggle-field');
            btn.addEventListener('click', function () {
                const showing = field.type === 'text';
                field.type = showing ? 'password' : 'text';
                btn.querySelector('i').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        });

        // ── Tab 9: Designation & Salary — Employee Category drives Type's
        // options and whether Contractor Name is shown/required. ──
        const desigCategorySelect = document.getElementById('designation_employee_category');
        const desigTypeSelect = document.getElementById('designation_employee_type');
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
        function refreshDesignationContractor() {
            if (!desigCategorySelect || !desigContractorField) return;
            const isContractor = desigCategorySelect.value === 'contractor';
            desigContractorField.style.display = isContractor ? '' : 'none';
            if (desigContractorSelect) desigContractorSelect.toggleAttribute('required', isContractor);
        }
        if (desigCategorySelect) {
            refreshDesignationType(true);
            refreshDesignationContractor();
            desigCategorySelect.addEventListener('change', function () {
                refreshDesignationType(false);
                refreshDesignationContractor();
            });
        }

        // ── Tab 9: Designation & Salary — the selected Salary Slab is the
        // single source of truth; its whole breakdown is fetched read-only
        // (never computed/entered here). ──
        const salarySlabSelect = document.getElementById('salary_slab_id');

        // Same field-based layout as the Salary Slab Master's own
        // create/edit form (label + boxed input per value) — these stay
        // `disabled` so they're never submitted and never editable here;
        // the Salary Slab itself remains the only place these are entered.
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
            var url = window.__employeeWizard.salarySlabBreakdownUrl.replace('SLAB_ID', encodeURIComponent(slabId));
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) {
                    if (!r.ok) { console.error('Salary Slab breakdown request failed with status ' + r.status + ' for slab ' + slabId); return null; }
                    return r.json();
                })
                .then(function (data) {
                    renderSalarySlabBreakdown(data);
                })
                .catch(function (err) {
                    console.error('Salary Slab breakdown request errored for slab ' + slabId + ':', err);
                    renderSalarySlabBreakdown(null);
                });
        }

        if (salarySlabSelect) {
            salarySlabSelect.addEventListener('change', function () { fetchAndRenderSlabBreakdown(salarySlabSelect.value); });
        }
        // On page load, the select may already have a value — either the
        // employee's actually-saved Salary Slab, OR a slab old()-repopulated
        // after a validation failure elsewhere on this tab (e.g. Effective
        // To before Effective From) where no salary was ever saved, so
        // window.__employeeWizard.currentSalaryBreakdown is null. Relying
        // only on that embed left every field stuck blank in the second
        // case even though a slab was clearly selected — always live-fetch
        // whenever the select has a value so the displayed data is
        // guaranteed to match what's actually selected.
        if (window.__employeeWizard.currentSalaryBreakdown) {
            renderSalarySlabBreakdown(window.__employeeWizard.currentSalaryBreakdown);
        }
        if (salarySlabSelect && salarySlabSelect.value) {
            fetchAndRenderSlabBreakdown(salarySlabSelect.value);
        }

        // ── Tab 10: Documents add-row ──
        const documentsBody = document.getElementById('documentsBody');
        const addDocumentBtn = document.getElementById('addDocumentRow');
        let documentRowIndex = 0;

        function addDocumentRow() {
            const idx = documentRowIndex++;
            const typeOptions = Object.entries(window.__employeeWizard.documentTypes)
                .map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><select name="documents[${idx}][document_type]" class="form-select form-select-sm" required>${typeOptions}</select></td>
                <td><input type="text" name="documents[${idx}][document_number]" class="form-control form-control-sm"></td>
                <td><input type="date" name="documents[${idx}][issue_date]" class="form-control form-control-sm"></td>
                <td><input type="date" name="documents[${idx}][expiry_date]" class="form-control form-control-sm"></td>
                <td><input type="file" name="documents[${idx}][file]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required></td>
                <td><input type="text" name="documents[${idx}][remarks]" class="form-control form-control-sm"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-document-row"><i class="bi bi-x"></i></button></td>
            `;
            documentsBody.appendChild(tr);
            tr.querySelector('.remove-document-row').addEventListener('click', function () { tr.remove(); });
        }
        if (addDocumentBtn) addDocumentBtn.addEventListener('click', addDocumentRow);
        addDocumentRow();
    })();
</script>
@endpush
