@extends('layouts.app')

@section('title', $definition->title)
@section('page-title', $definition->title)
@section('page-subtitle', $definition->description)

@section('page-actions')
    @if(in_array('csv', $definition->exportFormats, true))
        <a href="{{ route('reports.view.export.excel', array_merge(['key' => $definition->key], request()->query())) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
    @endif
    @if(in_array('pdf', $definition->exportFormats, true))
        <a href="{{ route('reports.view.export.pdf', array_merge(['key' => $definition->key], request()->query())) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
    @endif
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Reports</a>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            @if(isset($filterOptions['branches']))
                <div class="col-md-2">
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($filterOptions['branches'] as $b)
                            <option value="{{ $b->id }}" {{ (string) request('branch_id') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if(isset($filterOptions['departments']))
                <div class="col-md-2">
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach($filterOptions['departments'] as $d)
                            <option value="{{ $d->id }}" {{ (string) request('department_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if(isset($filterOptions['employeeTypes']))
                <div class="col-md-2">
                    <select name="employee_type_id" class="form-select form-select-sm">
                        <option value="">All Employee Types</option>
                        @foreach($filterOptions['employeeTypes'] as $et)
                            <option value="{{ $et->id }}" {{ (string) request('employee_type_id') === (string) $et->id ? 'selected' : '' }}>{{ $et->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($definition->filterMap['labour_type'] ?? null)
                <div class="col-md-2">
                    <select name="labour_type" class="form-select form-select-sm">
                        <option value="">All Labour Types</option>
                        <option value="company_labour" {{ request('labour_type') == 'company_labour' ? 'selected' : '' }}>Company Labour</option>
                        <option value="contract_labour" {{ request('labour_type') == 'contract_labour' ? 'selected' : '' }}>Contract Labour</option>
                    </select>
                </div>
            @endif
            @if(isset($filterOptions['contractors']))
                <div class="col-md-2">
                    <select name="contractor_id" class="form-select form-select-sm">
                        <option value="">All Contractors</option>
                        @foreach($filterOptions['contractors'] as $c)
                            <option value="{{ $c->id }}" {{ (string) request('contractor_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if(isset($filterOptions['employees']))
                <div class="col-md-2">
                    <select name="employee_id" class="form-select form-select-sm">
                        <option value="">All Employees</option>
                        @foreach($filterOptions['employees'] as $e)
                            <option value="{{ $e->id }}" {{ (string) request('employee_id') === (string) $e->id ? 'selected' : '' }}>{{ $e->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($definition->dateColumn)
                <div class="col-md-2">
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
                </div>
            @endif
            @if($definition->periodFilter)
                <div class="col-md-2">
                    <select name="month" class="form-select form-select-sm">
                        <option value="">Month</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (string) request('month') === (string) $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="year" class="form-select form-select-sm">
                        <option value="">Year</option>
                        @foreach(range(now()->year - 2, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ (string) request('year') === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($definition->statusColumn)
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        @foreach($definition->statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

@if($summary)
<div class="row g-2 mb-3">
    @foreach($summary as $s)
    <div class="col-md-3">
        <div class="p-2 bg-light rounded text-center">
            <small class="text-muted">{{ $s['label'] }}</small>
            <div class="fw-bold">{{ $s['value'] }}</div>
        </div>
    </div>
    @endforeach
</div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        @foreach($definition->columns as $col)
                            <th>{{ $col['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            @foreach($definition->columns as $col)
                                <td>{{ \App\Support\Reports\ReportColumnRenderer::render($record, $col, $canViewSensitive) }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ max(1, count($definition->columns)) }}" class="text-center text-muted py-4">No records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $records->links() }}</div>
</div>
@endsection
