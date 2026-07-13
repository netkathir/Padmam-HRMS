{{--
    File: resources/views/dashboard/index.blade.php
    Purpose: Overall Dashboard (Dashboard FSD 5.2) — cross-branch KPIs/charts
             with date range, branch multi-select, employee/labour type and
             contractor filters.
    Author: System
--}}
@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <style>
        .dash-bg { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
        .dash-blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: .12; animation: blobDrift 18s ease-in-out infinite alternate; }
        .dash-blob-1 { width: 520px; height: 520px; background: #7c3aed; top: -180px; left: -120px; }
        .dash-blob-2 { width: 420px; height: 420px; background: #0ea5e9; top: 30%; right: -140px; animation-delay: -6s; }
        .dash-blob-3 { width: 340px; height: 340px; background: #10b981; bottom: -100px; left: 30%; animation-delay: -12s; }
        @keyframes blobDrift { 0% { transform: translate(0,0) scale(1); } 33% { transform: translate(30px,-20px) scale(1.05); } 66% { transform: translate(-20px,30px) scale(.97); } 100% { transform: translate(10px,10px) scale(1.03); } }
        .dash-content { position: relative; z-index: 1; }

        .dash-hero { background: linear-gradient(135deg, #0d1433 0%, #1a2248 60%, #16213e 100%); border-radius: 14px; padding: 14px 22px; margin-bottom: 16px; border: 1px solid rgba(255,255,255,.06); box-shadow: 0 4px 24px rgba(13,20,51,.35); }
        .dash-greeting { font-size: 17px; font-weight: 700; color: #fff; margin: 0 0 2px; }
        .dash-greeting-sub { font-size: 12px; color: #8899bb; margin: 0; }

        .filter-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); padding: 14px 16px; margin-bottom: 18px; }
        .filter-card label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; margin-bottom: 3px; }

        .stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; margin-bottom: 20px; }
        .kpi-card { border-radius: 14px; padding: 16px 18px; position: relative; overflow: hidden; cursor: default; border: 1px solid rgba(255,255,255,.15); box-shadow: 0 4px 16px rgba(0,0,0,.12); transition: transform .2s ease; text-decoration: none; display: block; color: inherit; }
        a.kpi-card { cursor: pointer; }
        a.kpi-card:hover { transform: translateY(-3px); text-decoration: none; color: inherit; }
        .kpi-inner { display: flex; align-items: center; gap: 14px; position: relative; z-index: 1; }
        .kpi-icon-wrap { width: 42px; height: 42px; flex-shrink: 0; border-radius: 11px; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; }
        .kpi-label { font-size: 10.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: rgba(255,255,255,.75); margin-bottom: 3px; line-height: 1.3; }
        .kpi-value { font-size: 22px; font-weight: 800; color: #fff; line-height: 1.1; font-variant-numeric: tabular-nums; }
        .kpi-arrow { position: absolute; top: 13px; right: 14px; font-size: 14px; color: rgba(255,255,255,.35); }

        .kpi-purple { background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 45%, #a855f7 100%); }
        .kpi-green  { background: linear-gradient(135deg, #065f46 0%, #059669 45%, #10b981 100%); }
        .kpi-blue   { background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 45%, #3b82f6 100%); }
        .kpi-amber  { background: linear-gradient(135deg, #92400e 0%, #d97706 45%, #f59e0b 100%); }
        .kpi-teal   { background: linear-gradient(135deg, #115e59 0%, #0d9488 45%, #14b8a6 100%); }
        .kpi-red    { background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 45%, #ef4444 100%); }
        .kpi-indigo { background: linear-gradient(135deg, #312e81 0%, #4338ca 45%, #6366f1 100%); }
        .kpi-pink   { background: linear-gradient(135deg, #831843 0%, #be185d 45%, #ec4899 100%); }
        .kpi-cyan   { background: linear-gradient(135deg, #164e63 0%, #0891b2 45%, #22d3ee 100%); }
        .kpi-slate  { background: linear-gradient(135deg, #1e293b 0%, #334155 45%, #64748b 100%); }

        .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .chart-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.06); padding: 16px 18px; }
        .chart-card h6 { font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .chart-card canvas { max-height: 260px; }

        .info-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.06); overflow: hidden; height: 100%; }
        .info-card-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f3f4f6; }
        .info-card-title { font-size: 14px; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 8px; }
        .dash-table { font-size: 13px; margin: 0; }
        .dash-table th { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; background: #f9fafb; border-bottom: 1px solid #f3f4f6; padding: 10px 16px; font-weight: 600; }
        .dash-table td { padding: 10px 16px; border-bottom: 1px solid rgba(0,0,0,.04); vertical-align: middle; }
        .ls-pending { background: #fef9c3; color: #854d0e; } .ls-approved { background: #dcfce7; color: #166534; }
        .ls-rejected { background: #fee2e2; color: #991b1b; } .ls-cancelled { background: #f3f4f6; color: #374151; }
        .event-item { display: flex; align-items: center; gap: 14px; padding: 12px 20px; }
        .event-item + .event-item { border-top: 1px solid #f9fafb; }
        .event-icon { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .event-icon-bday { background: #fee2e2; color: #dc2626; } .event-icon-anni { background: #ede9fe; color: #7c3aed; }
        .event-name { font-size: 13px; font-weight: 600; color: #111827; } .event-meta { font-size: 12px; color: #9ca3af; }
    </style>
@endpush

@section('content')
<div class="dash-content">
    <div class="dash-bg">
        <div class="dash-blob dash-blob-1"></div>
        <div class="dash-blob dash-blob-2"></div>
        <div class="dash-blob dash-blob-3"></div>
    </div>

    <div class="dash-hero">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                @php
                    $hour = now()->hour;
                    $greeting = match (true) {
                        $hour >= 5 && $hour < 12 => 'Good Morning',
                        $hour >= 12 && $hour < 17 => 'Good Afternoon',
                        $hour >= 17 && $hour < 22 => 'Good Evening',
                        default => 'Good Night',
                    };
                @endphp
                <p class="dash-greeting">{{ $greeting }}, {{ auth()->user()->name }} 👋</p>
                <p class="dash-greeting-sub">Overall Dashboard — across every branch you're authorized for.</p>
            </div>
            <div class="text-end">
                <div style="font-size:18px;font-weight:700;color:#c8d0e7;">{{ now()->format('d') }}</div>
                <div style="font-size:11px;color:#6b7a99;">{{ now()->format('l, F Y') }}</div>
            </div>
        </div>
    </div>

    {{-- ── Filter bar ────────────────────────────────────────── --}}
    <form method="GET" class="filter-card row g-2 align-items-end">
        <div class="col-md-2 col-6">
            <label class="d-block">Date From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from']->toDateString() }}">
        </div>
        <div class="col-md-2 col-6">
            <label class="d-block">Date To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to']->toDateString() }}">
        </div>
        @if ($authorizedBranches->count() > 1)
            <div class="col-md-3 col-12">
                <label class="d-block">Branches</label>
                <select name="branch_ids[]" multiple class="form-select form-select-sm" size="1" title="Ctrl/Cmd+click to select multiple">
                    @foreach ($authorizedBranches as $branch)
                        <option value="{{ $branch->id }}" {{ $filters['branch_ids']->contains($branch->id) ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
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

    {{-- ── KPI cards ─────────────────────────────────────────── --}}
    <div class="stat-grid">
        <a href="{{ route('employees.index') }}" class="kpi-card kpi-purple" title="Active employees matching the current filters">
            <i class="bi bi-arrow-up-right kpi-arrow"></i>
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-people-fill"></i></div>
                <div><div class="kpi-label">Total Employees</div><div class="kpi-value">{{ number_format($kpis['total_employees']) }}</div></div></div>
        </a>
        <div class="kpi-card kpi-indigo" title="Employees classified as Staff">
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-person-badge-fill"></i></div>
                <div><div class="kpi-label">Staff Count</div><div class="kpi-value">{{ number_format($kpis['staff_count']) }}</div></div></div>
        </div>
        <div class="kpi-card kpi-teal" title="Company labour employees">
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-person-workspace"></i></div>
                <div><div class="kpi-label">Company Labour</div><div class="kpi-value">{{ number_format($kpis['company_labour_count']) }}</div></div></div>
        </div>
        <a href="{{ route('contract-labour.index') }}" class="kpi-card kpi-cyan" title="Contract labour (external workers)">
            <i class="bi bi-arrow-up-right kpi-arrow"></i>
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-person-check"></i></div>
                <div><div class="kpi-label">Contract Labour</div><div class="kpi-value">{{ number_format($kpis['contract_labour_count']) }}</div></div></div>
        </a>
        <a href="{{ route('masters.contractors.index') }}" class="kpi-card kpi-slate" title="Active contractors">
            <i class="bi bi-arrow-up-right kpi-arrow"></i>
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-diagram-3"></i></div>
                <div><div class="kpi-label">Contractor Count</div><div class="kpi-value">{{ number_format($kpis['contractor_count']) }}</div></div></div>
        </a>
        <a href="{{ route('attendance.index', ['date' => $filters['date_to']->toDateString(), 'status' => 'present']) }}" class="kpi-card kpi-green" title="Present on {{ $filters['date_to']->format('d M Y') }}">
            <i class="bi bi-arrow-up-right kpi-arrow"></i>
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-person-check-fill"></i></div>
                <div><div class="kpi-label">Present Employees</div><div class="kpi-value">{{ number_format($kpis['present_employees']) }}</div></div></div>
        </a>
        <a href="{{ route('attendance.index', ['date' => $filters['date_to']->toDateString(), 'status' => 'absent']) }}" class="kpi-card kpi-amber" title="Absent on {{ $filters['date_to']->format('d M Y') }}">
            <i class="bi bi-arrow-up-right kpi-arrow"></i>
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-person-x-fill"></i></div>
                <div><div class="kpi-label">Absent Employees</div><div class="kpi-value">{{ number_format($kpis['absent_employees']) }}</div></div></div>
        </a>
        <a href="{{ route('leaves.index') }}?status=approved" class="kpi-card kpi-blue" title="Approved leave covering {{ $filters['date_to']->format('d M Y') }}">
            <i class="bi bi-arrow-up-right kpi-arrow"></i>
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-calendar-x-fill"></i></div>
                <div><div class="kpi-label">Employees on Leave</div><div class="kpi-value">{{ number_format($kpis['on_leave_employees']) }}</div></div></div>
        </a>
        <a href="{{ route('reports.lop') }}" class="kpi-card kpi-red" title="Loss-of-pay employees (from processed payroll)">
            <i class="bi bi-arrow-up-right kpi-arrow"></i>
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-exclamation-diamond-fill"></i></div>
                <div><div class="kpi-label">LOP Employees</div><div class="kpi-value">{{ number_format($kpis['lop_employees']) }}</div></div></div>
        </a>
        <a href="{{ route('payroll.index') }}" class="kpi-card kpi-green" title="Gross salary for the selected period's month(s)">
            <i class="bi bi-arrow-up-right kpi-arrow"></i>
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-graph-up"></i></div>
                <div><div class="kpi-label">Monthly Gross Salary</div><div class="kpi-value" style="font-size:17px;">₹{{ number_format($kpis['monthly_gross']) }}</div></div></div>
        </a>
        <a href="{{ route('payroll.index') }}" class="kpi-card kpi-blue" title="Net salary for the selected period's month(s)">
            <i class="bi bi-arrow-up-right kpi-arrow"></i>
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-wallet2"></i></div>
                <div><div class="kpi-label">Monthly Net Salary</div><div class="kpi-value" style="font-size:17px;">₹{{ number_format($kpis['monthly_net']) }}</div></div></div>
        </a>
        <div class="kpi-card kpi-purple" title="Employer PF contribution">
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-shield-plus"></i></div>
                <div><div class="kpi-label">Employer PF</div><div class="kpi-value" style="font-size:17px;">₹{{ number_format($kpis['employer_pf']) }}</div></div></div>
        </div>
        <div class="kpi-card kpi-pink" title="Employer ESI contribution">
            <div class="kpi-inner"><div class="kpi-icon-wrap"><i class="bi bi-heart-pulse-fill"></i></div>
                <div><div class="kpi-label">Employer ESI</div><div class="kpi-value" style="font-size:17px;">₹{{ number_format($kpis['employer_esi']) }}</div></div></div>
        </div>
    </div>

    {{-- ── Charts ────────────────────────────────────────────── --}}
    <div class="chart-grid">
        <div class="chart-card">
            <h6><i class="bi bi-building text-primary"></i> Branch-wise Employees</h6>
            <canvas id="chartBranchWise"></canvas>
        </div>
        <div class="chart-card">
            <h6><i class="bi bi-calendar-check text-success"></i> Attendance Summary</h6>
            <canvas id="chartAttendanceSummary"></canvas>
        </div>
        <div class="chart-card">
            <h6><i class="bi bi-pie-chart-fill text-purple" style="color:#7c3aed;"></i> Employee Type</h6>
            <canvas id="chartEmployeeType"></canvas>
        </div>
        <div class="chart-card">
            <h6><i class="bi bi-cash-coin text-success"></i> Payroll Summary (6 months)</h6>
            <canvas id="chartPayrollSummary"></canvas>
        </div>
        <div class="chart-card">
            <h6><i class="bi bi-person-workspace text-info"></i> Contractor-wise Labour</h6>
            <canvas id="chartContractorLabour"></canvas>
        </div>
    </div>

    {{-- ── Recent Leaves + Upcoming Events (preserved from before) ─── --}}
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-title"><i class="bi bi-calendar-x-fill" style="color:#2563eb;"></i> Recent Leave Requests</div>
                    <a href="{{ route('leaves.index') }}" class="btn btn-sm" style="font-size:12px;background:#eff6ff;color:#2563eb;border:none;">View All <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
                <table class="dash-table w-100">
                    <thead><tr><th>Employee</th><th>Type</th><th class="text-center">Days</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recentLeaves as $leave)
                            @php $badgeClass = match ($leave->status) { 'approved' => 'ls-approved', 'rejected' => 'ls-rejected', 'cancelled' => 'ls-cancelled', default => 'ls-pending' }; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $leave->employee->full_name ?? '—' }}</td>
                                <td>{{ $leave->leaveType->name ?? '—' }}</td>
                                <td class="text-center fw-semibold">{{ $leave->total_days }}</td>
                                <td><span class="badge {{ $badgeClass }}" style="border-radius:20px;font-size:11px;padding:4px 10px;font-weight:600;">{{ ucfirst($leave->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-5">No leave requests</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-title"><i class="bi bi-gift-fill" style="color:#dc2626;"></i> Upcoming Events</div>
                    <span class="badge bg-danger-subtle text-danger">Next 7 days</span>
                </div>
                <div>
                    @foreach ($birthdays as $emp)
                        <div class="event-item"><div class="event-icon event-icon-bday">🎂</div>
                            <div><div class="event-name">{{ $emp->full_name }}</div><div class="event-meta">Birthday · {{ $emp->date_of_birth->format('d M') }} · {{ $emp->department->name ?? '' }}</div></div></div>
                    @endforeach
                    @foreach ($anniversaries as $emp)
                        <div class="event-item"><div class="event-icon event-icon-anni">⭐</div>
                            <div><div class="event-name">{{ $emp->full_name }}</div><div class="event-meta">Work Anniversary · {{ $emp->date_of_joining->format('d M') }} · {{ $emp->department->name ?? '' }}</div></div></div>
                    @endforeach
                    @if ($birthdays->isEmpty() && $anniversaries->isEmpty())
                        <div class="text-center text-muted py-5"><div style="font-size:13px;">No upcoming events in the next 7 days</div></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const branchWise = @json($charts['branch_wise_employees']->pluck('value', 'label'));
    new Chart(document.getElementById('chartBranchWise'), {
        type: 'bar',
        data: { labels: Object.keys(branchWise), datasets: [{ label: 'Employees', data: Object.values(branchWise), backgroundColor: '#4f7ef8' }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    const att = @json($charts['attendance_summary']);
    new Chart(document.getElementById('chartAttendanceSummary'), {
        type: 'line',
        data: {
            labels: att.labels,
            datasets: [
                { label: 'Present', data: att.present, borderColor: '#10b981', backgroundColor: '#10b98133', tension: .3 },
                { label: 'Absent', data: att.absent, borderColor: '#ef4444', backgroundColor: '#ef444433', tension: .3 },
                { label: 'On Leave', data: att.on_leave, borderColor: '#8b5cf6', backgroundColor: '#8b5cf633', tension: .3 },
            ]
        },
        options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    const empType = @json($charts['employee_type']);
    new Chart(document.getElementById('chartEmployeeType'), {
        type: 'doughnut',
        data: {
            labels: ['Staff', 'Company Labour', 'Contract Labour'],
            datasets: [{ data: [empType.staff, empType.company_labour, empType.contract_labour], backgroundColor: ['#6366f1', '#0d9488', '#22d3ee'] }]
        }
    });

    const payroll = @json($charts['payroll_summary']);
    new Chart(document.getElementById('chartPayrollSummary'), {
        type: 'bar',
        data: {
            labels: payroll.map(p => p.label),
            datasets: [
                { label: 'Gross', data: payroll.map(p => p.gross), backgroundColor: '#3b82f6' },
                { label: 'Net', data: payroll.map(p => p.net), backgroundColor: '#10b981' },
            ]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });

    const contractorLabour = @json($charts['contractor_wise_labour']->pluck('value', 'label'));
    new Chart(document.getElementById('chartContractorLabour'), {
        type: 'bar',
        data: { labels: Object.keys(contractorLabour), datasets: [{ label: 'Workers', data: Object.values(contractorLabour), backgroundColor: '#0891b2' }] },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>
@endpush
