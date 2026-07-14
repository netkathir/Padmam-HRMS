@extends('layouts.app')

@section('title', 'Payroll')
@section('page-title', 'Payroll')
@section('page-subtitle', 'Monthly payroll processing')

@section('page-actions')
    <a href="{{ route('payroll.generate', ['month' => $month, 'year' => $year]) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Generate Payroll</a>
    <a href="{{ route('payroll.lop-review', ['month' => $month, 'year' => $year]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-clipboard-check"></i> LOP Review</a>
    <a href="{{ route('payroll.export.register', ['month' => $month, 'year' => $year]) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Register</a>
    <a href="{{ route('payroll.export.bank-transfer', ['month' => $month, 'year' => $year]) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-bank"></i> Bank Transfer</a>
    <a href="{{ route('payroll.export.statutory', ['month' => $month, 'year' => $year]) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-text"></i> Statutory</a>
    <a href="{{ route('payroll.payslip.bulk', ['month' => $month, 'year' => $year]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-zip"></i> Bulk Payslips</a>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <select name="month" class="form-select form-select-sm">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" {{ request('month', $month) == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="year" class="form-select form-select-sm">
                        @foreach (range(now()->year - 2, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ request('year', $year) == $y ? 'selected' : '' }}>
                                {{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="calculated" {{ request('status') == 'calculated' ? 'selected' : '' }}>Calculated</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft (legacy)</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> View</button>
                </div>
            </form>

            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="p-2 bg-light rounded text-center">
                        <small class="text-muted">Records</small>
                        <div class="fw-bold">{{ $summary->count ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 bg-light rounded text-center">
                        <small class="text-muted">Gross Payable</small>
                        <div class="fw-bold">₹{{ number_format($summary->gross ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 bg-light rounded text-center">
                        <small class="text-muted">Deductions</small>
                        <div class="fw-bold text-danger">₹{{ number_format($summary->deductions ?? 0, 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-2 bg-light rounded text-center">
                        <small class="text-muted">Net Payable</small>
                        <div class="fw-bold text-success">₹{{ number_format($summary->net ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>

            @if($canConfirm || $canClose)
            <div class="d-flex gap-2 mb-3">
                @if($canConfirm)
                <form action="{{ route('payroll.confirm') }}" method="POST" onsubmit="return confirm('Confirm payroll for every eligible employee in this period?');">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month }}"><input type="hidden" name="year" value="{{ $year }}">
                    <button class="btn btn-sm btn-info"><i class="bi bi-check2-square"></i> Confirm Payroll (Period)</button>
                </form>
                @endif
                @if($canClose)
                <form action="{{ route('payroll.close') }}" method="POST" onsubmit="return confirm('Close payroll for every confirmed employee in this period? Changes will require reopening.');">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month }}"><input type="hidden" name="year" value="{{ $year }}">
                    <button class="btn btn-sm btn-dark"><i class="bi bi-lock"></i> Close Payroll (Period)</button>
                </form>
                @endif
            </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Basic</th>
                            <th>Gross</th>
                            <th>Deductions</th>
                            <th>Net</th>
                            <th>Employer Cost</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $pr)
                            <tr>
                                <td>{{ $pr->employee->employee_code ?? '' }} — {{ $pr->employee->full_name ?? '—' }}</td>
                                <td>{{ $pr->employee->department->name ?? '—' }}</td>
                                <td>₹{{ number_format($pr->basic_salary, 0) }}</td>
                                <td>₹{{ number_format($pr->gross_earnings, 0) }}</td>
                                <td>₹{{ number_format($pr->total_deductions, 0) }}</td>
                                <td class="fw-bold">₹{{ number_format($pr->net_salary, 0) }}</td>
                                <td>₹{{ number_format($pr->employer_cost ?? 0, 0) }}</td>
                                <td>
                                    @php $c = ['draft'=>'secondary','calculated'=>'secondary', 'processed'=>'info', 'confirmed'=>'info', 'closed'=>'dark', 'paid'=>'success', 'hold'=>'warning']; @endphp
                                    <span class="badge bg-{{ $c[$pr->status] ?? 'secondary' }}-subtle text-{{ $c[$pr->status] ?? 'secondary' }}">{{ $pr->status_label }}</span>
                                    @if($pr->net_salary < 0)
                                        <span class="badge bg-danger-subtle text-danger" title="Negative net salary">!</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('payroll.payslip', $pr) }}" class="btn btn-sm btn-outline-info" title="View Payslip"><i class="bi bi-file-text"></i></a>
                                    <a href="{{ route('payroll.payment', $pr) }}" class="btn btn-sm btn-outline-success" title="Payment"><i class="bi bi-cash"></i></a>
                                    @if($canReopen && in_array($pr->status, ['confirmed', 'closed']))
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#reopenModal{{ $pr->id }}" title="Reopen"><i class="bi bi-unlock"></i></button>
                                    <div class="modal fade" id="reopenModal{{ $pr->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('payroll.reopen', $pr) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header"><h6 class="modal-title">Reopen Payroll — {{ $pr->employee->full_name }}</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <div class="modal-body">
                                                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                                                        <textarea name="reopen_reason" class="form-control" rows="2" required></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-warning">Reopen</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No payroll records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">{{ $records->links() }}</div>
        </div>
    </div>
@endsection
