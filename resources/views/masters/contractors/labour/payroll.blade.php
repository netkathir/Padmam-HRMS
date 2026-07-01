@extends('layouts.app')
@section('title', 'Contractor Payroll - ' . $contractor->name)
@section('page-title', 'Contractor Payroll')
@section('page-subtitle', $contractor->name . ' (' . $contractor->code . ')')

@section('page-actions')
    <a href="{{ route('masters.contractors.labour', $contractor) }}" class="btn btn-outline-primary btn-sm"><i
            class="bi bi-people"></i> Labours</a>
    <a href="{{ route('masters.contractors.index') }}" class="btn btn-outline-secondary btn-sm"><i
            class="bi bi-arrow-left"></i> Contractors</a>
@endsection

@section('content')
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
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>Processed
                        </option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="hold" {{ request('status') == 'hold' ? 'selected' : '' }}>Hold</option>
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
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $pr)
                            <tr>
                                <td>{{ $pr->employee->full_name ?? '—' }}</td>
                                <td>{{ $pr->employee->department->name ?? '—' }}</td>
                                <td>₹{{ number_format($pr->basic_salary, 0) }}</td>
                                <td>₹{{ number_format($pr->gross_earnings, 0) }}</td>
                                <td>₹{{ number_format($pr->total_deductions, 0) }}</td>
                                <td class="fw-bold">₹{{ number_format($pr->net_salary, 0) }}</td>
                                <td>
                                    @php $c = ['draft'=>'secondary', 'processed'=>'info', 'paid'=>'success', 'hold'=>'warning']; @endphp
                                    <span
                                        class="badge bg-{{ $c[$pr->status] }}-subtle text-{{ $c[$pr->status] }}">{{ ucfirst($pr->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('payroll.payslip', $pr) }}" class="btn btn-sm btn-outline-info"><i
                                            class="bi bi-file-text"></i></a>
                                    <a href="{{ route('payroll.payment', $pr) }}" class="btn btn-sm btn-outline-success"><i
                                            class="bi bi-cash"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No payroll records found for this
                                    contractor.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">{{ $records->links() }}</div>
        </div>
    </div>
@endsection
