{{--
    File: resources/views/dashboard/branch.blade.php
    Purpose: Branch Dashboard (Dashboard FSD 5.3) — single-branch KPIs/charts
             with a branch selector (auto-selected when only one is
             authorized), current-date default, and employee/labour
             type/contractor filters.
    Author: System
--}}
@extends('layouts.app')

@section('title', 'Branch Dashboard')
@section('page-title', 'Branch Dashboard')
@section('page-subtitle', $authorizedBranches->firstWhere('id', $filters['branch_id'])?->name . ' — ' . $filters['date']->format('d M Y'))

@push('styles')
    <style>
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 14px; margin-bottom: 20px; }
        .kpi-card { border-radius: 14px; padding: 16px 18px; border: 1px solid rgba(255,255,255,.15); box-shadow: 0 4px 16px rgba(0,0,0,.12); color: #fff; }
        .kpi-label { font-size: 10.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: rgba(255,255,255,.75); margin-bottom: 3px; }
        .kpi-value { font-size: 22px; font-weight: 800; font-variant-numeric: tabular-nums; }
        .kpi-green  { background: linear-gradient(135deg, #065f46 0%, #059669 45%, #10b981 100%); }
        .kpi-red    { background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 45%, #ef4444 100%); }
        .kpi-blue   { background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 45%, #3b82f6 100%); }
        .kpi-amber  { background: linear-gradient(135deg, #92400e 0%, #d97706 45%, #f59e0b 100%); }
        .kpi-purple { background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 45%, #a855f7 100%); }
        .kpi-teal   { background: linear-gradient(135deg, #115e59 0%, #0d9488 45%, #14b8a6 100%); }
        .kpi-cyan   { background: linear-gradient(135deg, #164e63 0%, #0891b2 45%, #22d3ee 100%); }
        .kpi-indigo { background: linear-gradient(135deg, #312e81 0%, #4338ca 45%, #6366f1 100%); }
        .filter-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); padding: 14px 16px; margin-bottom: 18px; }
        .filter-card label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; margin-bottom: 3px; }
        .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 16px; }
        .chart-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.06); padding: 16px 18px; }
        .chart-card h6 { font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 12px; }
        .chart-card canvas { max-height: 260px; }
    </style>
@endpush

@section('content')

    <form method="GET" class="filter-card row g-2 align-items-end">
        @if ($authorizedBranches->count() > 1)
            <div class="col-md-3 col-6">
                <label class="d-block">Branch</label>
                <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach ($authorizedBranches as $branch)
                        <option value="{{ $branch->id }}" {{ $filters['branch_id'] === $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        @else
            <input type="hidden" name="branch_id" value="{{ $filters['branch_id'] }}">
        @endif
        <div class="col-md-2 col-6">
            <label class="d-block">Date</label>
            <input type="date" name="date" class="form-control form-control-sm" value="{{ $filters['date']->toDateString() }}">
        </div>
        <div class="col-md-2 col-6">
            <label class="d-block">Employee Type</label>
            <select name="employee_type" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="staff" {{ $filters['employee_type'] === 'staff' ? 'selected' : '' }}>Staff</option>
                <option value="labour" {{ $filters['employee_type'] === 'labour' ? 'selected' : '' }}>Labour</option>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="d-block">Labour Type</label>
            <select name="labour_type" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="company_labour" {{ $filters['labour_type'] === 'company_labour' ? 'selected' : '' }}>Company Labour</option>
                <option value="contract_labour" {{ $filters['labour_type'] === 'contract_labour' ? 'selected' : '' }}>Contract Labour</option>
            </select>
        </div>
        <div class="col-md-2 col-6">
            <label class="d-block">Contractor</label>
            <select name="contractor_id" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach ($contractors as $contractor)
                    <option value="{{ $contractor->id }}" {{ (string) $filters['contractor_id'] === (string) $contractor->id ? 'selected' : '' }}>{{ $contractor->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1 col-6">
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i></button>
        </div>
    </form>

    <div class="stat-grid">
        <a href="{{ route('employees.index', ['branch_id' => $filters['branch_id']]) }}" class="kpi-card kpi-purple text-decoration-none">
            <div class="kpi-label">Active Employees</div><div class="kpi-value">{{ number_format($kpis['active_employees']) }}</div>
        </a>
        <a href="{{ route('attendance.index', ['date' => $filters['date']->toDateString(), 'status' => 'present']) }}" class="kpi-card kpi-green text-decoration-none">
            <div class="kpi-label">Present</div><div class="kpi-value">{{ number_format($kpis['present']) }}</div>
        </a>
        <a href="{{ route('attendance.index', ['date' => $filters['date']->toDateString(), 'status' => 'absent']) }}" class="kpi-card kpi-amber text-decoration-none">
            <div class="kpi-label">Absent</div><div class="kpi-value">{{ number_format($kpis['absent']) }}</div>
        </a>
        <a href="{{ route('leaves.index') }}?status=approved" class="kpi-card kpi-blue text-decoration-none">
            <div class="kpi-label">Leave</div><div class="kpi-value">{{ number_format($kpis['on_leave']) }}</div>
        </a>
        <a href="{{ route('reports.lop') }}" class="kpi-card kpi-red text-decoration-none">
            <div class="kpi-label">LOP</div><div class="kpi-value">{{ number_format($kpis['lop']) }}</div>
        </a>
        <div class="kpi-card kpi-teal"><div class="kpi-label">Late Entry</div><div class="kpi-value">{{ number_format($kpis['late_entry']) }}</div></div>
        <div class="kpi-card kpi-cyan"><div class="kpi-label">Early Exit</div><div class="kpi-value">{{ number_format($kpis['early_exit']) }}</div></div>
        <div class="kpi-card kpi-indigo"><div class="kpi-label">Overtime Hours</div><div class="kpi-value">{{ number_format($kpis['overtime_hours'], 1) }}</div></div>
        <a href="{{ route('payroll.index') }}" class="kpi-card kpi-green text-decoration-none">
            <div class="kpi-label">Payroll Amount</div><div class="kpi-value" style="font-size:17px;">₹{{ number_format($kpis['payroll_amount']) }}</div>
        </a>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h6><i class="bi bi-calendar-check text-success"></i> Attendance Trend (30 days)</h6>
            <canvas id="chartAttendanceTrend"></canvas>
        </div>
        <div class="chart-card">
            <h6><i class="bi bi-diagram-3-fill" style="color:#7c3aed;"></i> Department-wise Strength</h6>
            <canvas id="chartDepartmentWise"></canvas>
        </div>
        <div class="chart-card">
            <h6><i class="bi bi-person-workspace text-info"></i> Contractor-wise Strength</h6>
            <canvas id="chartContractorWise"></canvas>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    const trend = @json($charts['attendance_trend']);
    new Chart(document.getElementById('chartAttendanceTrend'), {
        type: 'line',
        data: {
            labels: trend.labels,
            datasets: [
                { label: 'Present', data: trend.present, borderColor: '#10b981', backgroundColor: '#10b98133', tension: .3 },
                { label: 'Absent', data: trend.absent, borderColor: '#ef4444', backgroundColor: '#ef444433', tension: .3 },
            ]
        },
        options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    const dept = @json($charts['department_wise']->pluck('value', 'label'));
    new Chart(document.getElementById('chartDepartmentWise'), {
        type: 'bar',
        data: { labels: Object.keys(dept), datasets: [{ label: 'Employees', data: Object.values(dept), backgroundColor: '#7c3aed' }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    const contractor = @json($charts['contractor_wise']->pluck('value', 'label'));
    new Chart(document.getElementById('chartContractorWise'), {
        type: 'bar',
        data: { labels: Object.keys(contractor), datasets: [{ label: 'Workers', data: Object.values(contractor), backgroundColor: '#0891b2' }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>
@endpush
