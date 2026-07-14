@extends('layouts.app')
@section('title','LOP Review')
@section('page-title','LOP Review')
@section('page-subtitle','Review and adjust calculated Loss of Pay before finalizing payroll')
@section('page-actions')
    <a href="{{ route('payroll.index', ['month'=>$month,'year'=>$year]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Payroll</a>
@endsection
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-2">
                <select name="month" class="form-select form-select-sm">
                    @for($m=1;$m<=12;$m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <p class="text-muted small">FSD 8.6 — "Payroll shall use approved payroll LOP." Editing LOP Days below and saving requires a reason, and recomputes the LOP deduction and net salary for that employee.</p>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Employee</th><th>Working Days</th><th>Present</th><th>Calculated LOP</th><th>Approved LOP Days</th><th>Reason</th><th></th></tr></thead>
                <tbody>
                    @forelse($records as $record)
                    <tr>
                        <td>{{ $record->employee->full_name ?? '—' }} <span class="text-muted small">({{ $record->employee->employee_code ?? '' }})</span></td>
                        <td>{{ $record->working_days }}</td>
                        <td>{{ $record->present_days }}</td>
                        <td>{{ $record->calculated_lop_days ?? $record->lop_days }}</td>
                        <td>
                            <form action="{{ route('payroll.lop.update', $record) }}" method="POST" class="d-flex gap-2 align-items-center">
                                @csrf
                                <input type="number" step="0.5" min="0" name="lop_days" class="form-control form-control-sm" style="width:90px" value="{{ $record->lop_days }}">
                        </td>
                        <td>
                                <input type="text" name="lop_override_reason" class="form-control form-control-sm" placeholder="Reason if changed" value="{{ $record->lop_override_reason }}">
                        </td>
                        <td>
                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-save"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No draft payroll records for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $records->links() }}</div>
    </div>
</div>
@endsection
