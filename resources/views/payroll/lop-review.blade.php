@extends('layouts.app')
@section('title','LOP Review')
@section('page-title','LOP Review')
@section('page-subtitle','Review LOP calculated from attendance and leave before payroll processing')
@section('page-actions')
    <a href="{{ route('payroll.index', ['month'=>$month,'year'=>$year]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Payroll</a>
@endsection
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">Payroll Month <span class="text-danger">*</span></label>
                <select name="month" class="form-select form-select-sm">
                    @for($m=1;$m<=12;$m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Year <span class="text-danger">*</span></label>
                <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Branch</label>
                <select name="branch_id" class="form-select form-select-sm" {{ $currentBranchId ? 'disabled' : '' }}>
                    <option value="">All</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ (string) request('branch_id', $currentBranchId) === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Employee Type</label>
                <select name="employee_type_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($employeeTypes as $et)
                        <option value="{{ $et->id }}" {{ request('employee_type_id') == $et->id ? 'selected' : '' }}>{{ $et->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Labour Type</label>
                <select name="labour_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="company_labour" {{ request('labour_type') == 'company_labour' ? 'selected' : '' }}>Company Labour</option>
                    <option value="contract_labour" {{ request('labour_type') == 'contract_labour' ? 'selected' : '' }}>Contract Labour</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Contractor</label>
                <select name="contractor_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($contractors as $c)
                        <option value="{{ $c->id }}" {{ request('contractor_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mt-2">
                <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <p class="text-muted small mb-0">Changing Approved LOP Days away from the Calculated value requires a reason. Employees with zero calculated LOP don't require any selection.</p>
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#confirmLopModal">
                <i class="bi bi-shield-check"></i> Confirm LOP for This Period
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle small">
                <thead>
                    <tr>
                        <th>Employee Number</th>
                        <th>Employee Name</th>
                        <th class="text-end">Absent<br>Days</th>
                        <th class="text-end">Unpaid Leave<br>Days</th>
                        <th class="text-end">Half-Day<br>LOP</th>
                        <th class="text-end">Late/Early<br>Exit LOP</th>
                        <th class="text-end">Calculated<br>LOP Days</th>
                        <th class="text-center">Apply<br>LOP</th>
                        <th>Approved<br>LOP Days</th>
                        <th class="text-end">Adjustment</th>
                        <th>Adjustment Reason</th>
                        <th>Final<br>LOP Days</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                    @php $zeroLop = (float) $record->calculated_lop_days <= 0; @endphp
                    @php $formId = 'lop-form-' . $record->id; @endphp
                    <tr class="lop-row">
                        <td>
                            <form id="{{ $formId }}" action="{{ route('payroll.lop.update', $record) }}" method="POST" class="d-none">
                                @csrf
                            </form>
                            {{ $record->employee->employee_code ?? '—' }}
                        </td>
                        <td>{{ $record->employee->full_name ?? '—' }}</td>
                        <td class="text-end">{{ number_format($record->absent_days ?? 0, 1) }}</td>
                        <td class="text-end">{{ number_format($record->unpaid_leave_days ?? 0, 1) }}</td>
                        <td class="text-end">{{ number_format($record->half_day_lop_days ?? 0, 1) }}</td>
                        <td class="text-end">{{ number_format($record->late_early_lop_days ?? 0, 1) }}</td>
                        <td class="text-end fw-bold">{{ number_format($record->calculated_lop_days ?? $record->lop_days, 2) }}</td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input apply-lop" name="lop_applied" value="1" form="{{ $formId }}"
                                {{ $record->lop_applied ? 'checked' : '' }} {{ $zeroLop ? 'disabled' : '' }}>
                            @if($zeroLop)<input type="hidden" name="lop_applied" value="1" form="{{ $formId }}">@endif
                        </td>
                        <td>
                            <input type="number" step="0.5" min="0" name="lop_days" class="form-control form-control-sm approved-lop" style="width:90px" form="{{ $formId }}"
                                value="{{ $record->lop_days }}" data-calculated="{{ $record->calculated_lop_days ?? $record->lop_days }}" {{ $zeroLop ? 'readonly' : '' }}>
                        </td>
                        <td class="text-end adjustment-cell">—</td>
                        <td>
                            <input type="text" name="lop_override_reason" class="form-control form-control-sm" placeholder="Reason if changed" form="{{ $formId }}" value="{{ $record->lop_override_reason }}">
                        </td>
                        <td class="fw-bold final-lop-cell">{{ $record->lop_applied ? number_format($record->lop_days, 2) : '0.00' }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" form="{{ $formId }}"><i class="bi bi-save"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="13" class="text-center text-muted py-4">No draft payroll records for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $records->links() }}</div>
    </div>
</div>

<div class="modal fade" id="confirmLopModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('payroll.lop.confirm') }}" method="POST">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <div class="modal-header">
                    <h6 class="modal-title">Confirm LOP Before Payroll Processing</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>This locks the Final LOP Days shown above for every employee in this period. Payment cannot be
                        recorded for an employee with LOP until this confirmation is done.</p>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="confirmLopCheck" required>
                        <label class="form-check-label" for="confirmLopCheck">
                            I confirm the LOP values above are correct and ready for payroll processing.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="confirmLopSubmit" disabled>Confirm LOP</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    document.getElementById('confirmLopCheck')?.addEventListener('change', function () {
        document.getElementById('confirmLopSubmit').disabled = !this.checked;
    });

    // FSD 12.4 — "Adjustment" is a live client-side delta (Approved - Calculated),
    // and "Final LOP Days" previews Apply-LOP ? Approved : 0, before saving.
    document.querySelectorAll('.lop-row-form').forEach(function (form) {
        const approvedInput = form.querySelector('.approved-lop');
        const applyCheckbox = form.querySelector('.apply-lop');
        const adjustmentCell = form.querySelector('.adjustment-cell');
        const finalCell = form.querySelector('.final-lop-cell');

        function recalc() {
            const calculated = parseFloat(approvedInput.dataset.calculated) || 0;
            const approved = parseFloat(approvedInput.value) || 0;
            const diff = approved - calculated;
            adjustmentCell.textContent = diff === 0 ? '—' : (diff > 0 ? '+' : '') + diff.toFixed(2);
            const applied = applyCheckbox.checked || applyCheckbox.disabled;
            finalCell.textContent = (applied ? approved : 0).toFixed(2);
        }
        approvedInput.addEventListener('input', recalc);
        applyCheckbox.addEventListener('change', recalc);
        recalc();
    });
</script>
@endpush
