@extends('layouts.app')
@section('title','Salary Structure')
@section('page-title','Salary Structure')
@section('page-subtitle', $employee->display_name_or_default)
@section('page-actions')
    <a href="{{ route('reports.salary-structure.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
@endsection
@section('content')
@php
    $salary = $employee->currentSalary;
    $components = $salary?->components ?? collect();
@endphp
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><strong>Code:</strong> {{ $employee->employee_code ?? '—' }}</div>
            <div class="col-md-4"><strong>Branch:</strong> {{ $employee->branch->name ?? '—' }}</div>
            <div class="col-md-4"><strong>Name:</strong> {{ $employee->display_name_or_default }}</div>
            <div class="col-md-4"><strong>Designation:</strong> {{ $employee->designation->name ?? '—' }}</div>
            <div class="col-md-4"><strong>Employee Type:</strong> {{ $employee->system_classification }}</div>
            <div class="col-md-4"><strong>Gross Salary:</strong> {{ $salary ? number_format($salary->basic_salary, 2) : '—' }}</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <table class="table table-bordered table-sm">
            <thead class="table-success"><tr><th>Earnings</th><th class="text-end">%</th><th class="text-end">Amount (₹)</th></tr></thead>
            <tbody>
                @forelse($components as $c)
                <tr>
                    <td>{{ $c->component_name }}</td>
                    <td class="text-end">{{ $c->rate !== null ? number_format($c->rate, 2) : '—' }}</td>
                    <td class="text-end">{{ number_format($c->calculated_amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center text-muted py-3">No earnings selected.</td></tr>
                @endforelse
                <tr class="table-light fw-bold">
                    <td>OT</td>
                    <td class="text-end">—</td>
                    <td class="text-end">{{ $latestPayroll ? number_format($latestPayroll->ot_amount, 2) : '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-bordered table-sm">
            <thead class="table-danger"><tr><th>Deductions</th><th class="text-end">%</th><th class="text-end">Amount (₹)</th></tr></thead>
            <tbody>
                <tr>
                    <td>PF (Employee)</td>
                    <td class="text-end">{{ $salary?->slab?->pf_employee_percentage !== null ? number_format($salary->slab->pf_employee_percentage, 2) : '—' }}</td>
                    <td class="text-end">{{ $salary ? number_format($salary->pf_employee, 2) : '—' }}</td>
                </tr>
                <tr>
                    <td>ESI (Employee)</td>
                    <td class="text-end">{{ $salary?->slab?->esi_employee_percentage !== null ? number_format($salary->slab->esi_employee_percentage, 2) : '—' }}</td>
                    <td class="text-end">{{ $salary ? number_format($salary->esi_employee, 2) : '—' }}</td>
                </tr>
                <tr>
                    <td>TDS</td>
                    <td class="text-end">{{ $salary?->slab?->tds_percentage !== null ? number_format($salary->slab->tds_percentage, 2) : '—' }}</td>
                    <td class="text-end">{{ $salary ? number_format($salary->tds, 2) : '—' }}</td>
                </tr>
                <tr class="table-light fw-bold">
                    <td>Gross</td>
                    <td class="text-end">—</td>
                    <td class="text-end">{{ $latestPayroll ? number_format($latestPayroll->gross_earnings, 2) : ($salary ? number_format($salary->gross_salary, 2) : '—') }}</td>
                </tr>
                <tr class="table-light fw-bold">
                    <td>NET</td>
                    <td class="text-end">—</td>
                    <td class="text-end">{{ $latestPayroll ? number_format($latestPayroll->net_salary, 2) : ($salary ? number_format($salary->net_salary, 2) : '—') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
