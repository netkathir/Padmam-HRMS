@extends('layouts.app')
@section('title','Generate Payroll')
@section('page-title','Generate Payroll')
@section('page-subtitle','Payroll Processing — Preview, Calculate, Confirm, Close and Reopen from one screen')
@section('content')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Preconditions <small class="text-muted fw-normal">(System)</small></h6></div>
            <div class="card-body">
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-6">
                        <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                            @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-6">
                        <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}" onchange="this.form.submit()">
                    </div>
                    @if(! $currentBranchId)
                    <div class="col-12">
                        <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Select branch…</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (string) $branchId === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </form>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        Attendance Processed
                        <span class="badge bg-{{ $preconditions['attendance_processed'] ? 'success' : 'danger' }}-subtle text-{{ $preconditions['attendance_processed'] ? 'success' : 'danger' }}">
                            {{ $preconditions['attendance_processed'] ? 'Yes' : 'No' }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        Unresolved Attendance
                        <span class="badge bg-{{ $preconditions['unresolved_attendance_count'] > 0 ? 'warning' : 'success' }}-subtle text-{{ $preconditions['unresolved_attendance_count'] > 0 ? 'warning' : 'success' }}">
                            {{ $preconditions['unresolved_attendance_count'] }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        Pending Leave Requests
                        <span class="badge bg-{{ $preconditions['pending_leave_count'] > 0 ? 'warning' : 'success' }}-subtle text-{{ $preconditions['pending_leave_count'] > 0 ? 'warning' : 'success' }}">
                            {{ $preconditions['pending_leave_count'] }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        Employees Without Salary Structure
                        <span class="badge bg-{{ $preconditions['employees_without_salary_count'] > 0 ? 'danger' : 'success' }}-subtle text-{{ $preconditions['employees_without_salary_count'] > 0 ? 'danger' : 'success' }}">
                            {{ $preconditions['employees_without_salary_count'] }}
                        </span>
                    </li>
                </ul>
                <div class="form-text mt-2">These are informational — PF/ESI/TDS and earnings/deduction rules always resolve via configured fallbacks, so generation is not blocked by this panel.</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Payroll Parameters</h6></div>
            <div class="card-body">
                <form action="{{ route('payroll.generate.post') }}" method="POST"
                    data-confirm-delete="This will calculate payroll for the selected period, including LOP (Loss of Pay) derived from attendance and leave records. Proceed?">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Payroll Month <span class="text-danger">*</span></label>
                        <select name="month" class="form-select @error('month') is-invalid @enderror" required>
                            @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ old('month', $month) == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>
                            @endfor
                        </select>
                        @error('month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year <span class="text-danger">*</span></label>
                        <select name="year" class="form-select @error('year') is-invalid @enderror" required>
                            @for($y = now()->year + 1; $y >= now()->year - 2; $y--)
                            <option value="{{ $y }}" {{ old('year', $year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if(! $currentBranchId)
                    <div class="mb-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">Select…</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (string) old('branch_id', $branchId) === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <input type="hidden" name="branch_id" value="{{ $currentBranchId }}">
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Employee Type</label>
                        <select name="employee_type_id" class="form-select">
                            <option value="">All</option>
                            @foreach($employeeTypes as $et)
                            <option value="{{ $et->id }}" {{ old('employee_type_id') == $et->id ? 'selected' : '' }}>{{ $et->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Labour Type</label>
                        <select name="labour_type" class="form-select">
                            <option value="">All</option>
                            <option value="company_labour" {{ old('labour_type') == 'company_labour' ? 'selected' : '' }}>Company Labour</option>
                            <option value="contract_labour" {{ old('labour_type') == 'contract_labour' ? 'selected' : '' }}>Contract Labour</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contractor</label>
                        <select name="contractor_id" class="form-select">
                            <option value="">All</option>
                            @foreach($contractors as $c)
                            <option value="{{ $c->id }}" {{ old('contractor_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                    <div class="alert alert-light border py-2 small"><i class="bi bi-info-circle"></i> Employees who already have a payroll record for this period are skipped, not overwritten. Use "Recalculate Selected" from the register to update an existing record.</div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-calculator"></i> Calculate Payroll</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Recent Payroll Runs</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Period</th><th>Employees</th><th>Gross</th><th>Net</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse($recentPayrolls as $run)
                            <tr>
                                <td><strong>{{ \Carbon\Carbon::create($run->year, $run->month, 1)->format('F Y') }}</strong></td>
                                <td>{{ $run->employee_count ?? '—' }}</td>
                                <td>₹{{ number_format($run->total_gross ?? 0, 0) }}</td>
                                <td>₹{{ number_format($run->total_net ?? 0, 0) }}</td>
                                <td>
                                    @php $sc = match($run->status){ 'paid'=>'success','closed'=>'dark','confirmed'=>'info','draft'=>'secondary','calculated'=>'secondary',default=>'secondary' }; @endphp
                                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucwords(str_replace('_',' ',$run->status)) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('payroll.index', ['month' => $run->month, 'year' => $run->year]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No payroll runs yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
