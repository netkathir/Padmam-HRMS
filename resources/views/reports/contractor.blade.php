@extends('layouts.app')
@section('title', 'Contractor Report')
@section('page-title', 'Contractor Report')
@section('page-subtitle', 'Summary of contractor payment status by month')

@section('page-actions')
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Reports
    </a>
@endsection

@push('styles')
    <style>
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        @media (max-width: 1199px) {
            .stat-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 767px) {
            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 575px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            border-radius: 12px;
            padding: 13px 15px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .15);
            box-shadow: 0 4px 16px rgba(0, 0, 0, .12);
            transition: transform .25s cubic-bezier(.34, 1.56, .64, 1), box-shadow .25s ease;
            animation: cardEntry .5s ease both;
            text-decoration: none;
            display: block;
            color: inherit;
        }

        .stat-card:hover {
            transform: translateY(-4px) scale(1.02);
        }

        .stat-card:nth-child(1) {
            animation-delay: .05s;
        }

        .stat-card:nth-child(2) {
            animation-delay: .12s;
        }

        .stat-card:nth-child(3) {
            animation-delay: .19s;
        }

        .stat-card:nth-child(4) {
            animation-delay: .26s;
        }

        .stat-card:nth-child(5) {
            animation-delay: .33s;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(180deg, rgba(255, 255, 255, .18) 0%, transparent 100%);
            border-radius: 14px 14px 0 0;
            pointer-events: none;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
            bottom: -26px;
            right: -20px;
            pointer-events: none;
            transition: transform .28s ease;
        }

        .stat-card:hover::after {
            transform: scale(1.35);
        }

        .stat-blue {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 45%, #3b82f6 100%);
            box-shadow: 0 8px 28px rgba(29, 78, 216, .35);
        }

        .stat-blue:hover {
            box-shadow: 0 16px 40px rgba(29, 78, 216, .5);
        }

        .stat-green {
            background: linear-gradient(135deg, #065f46 0%, #059669 45%, #10b981 100%);
            box-shadow: 0 8px 28px rgba(5, 150, 105, .35);
        }

        .stat-green:hover {
            box-shadow: 0 16px 40px rgba(5, 150, 105, .5);
        }

        .stat-red {
            background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 45%, #ef4444 100%);
            box-shadow: 0 8px 28px rgba(220, 38, 38, .35);
        }

        .stat-red:hover {
            box-shadow: 0 16px 40px rgba(220, 38, 38, .5);
        }

        .stat-purple {
            background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 45%, #a855f7 100%);
            box-shadow: 0 8px 28px rgba(124, 58, 237, .35);
        }

        .stat-purple:hover {
            box-shadow: 0 16px 40px rgba(124, 58, 237, .5);
        }

        .stat-orange {
            background: linear-gradient(135deg, #92400e 0%, #d97706 45%, #f59e0b 100%);
            box-shadow: 0 8px 28px rgba(217, 119, 6, .35);
        }

        .stat-orange:hover {
            box-shadow: 0 16px 40px rgba(217, 119, 6, .5);
        }

        .stat-inner {
            display: flex;
            align-items: center;
            gap: 11px;
            position: relative;
            z-index: 1;
        }

        .stat-icon-wrap {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            border-radius: 10px;
            background: rgba(255, 255, 255, .2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            color: #fff;
            backdrop-filter: blur(4px);
            animation: iconFloat 3.5s ease-in-out infinite;
        }

        .stat-card:nth-child(2) .stat-icon-wrap {
            animation-delay: -.8s;
        }

        .stat-card:nth-child(3) .stat-icon-wrap {
            animation-delay: -1.6s;
        }

        .stat-card:nth-child(4) .stat-icon-wrap {
            animation-delay: -2.4s;
        }

        .stat-card:nth-child(5) .stat-icon-wrap {
            animation-delay: -3.2s;
        }

        @keyframes iconFloat {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-4px);
            }
        }

        .stat-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .7);
            margin-bottom: 2px;
            line-height: 1.3;
            white-space: nowrap;
        }

        .stat-value {
            font-size: 21px;
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .stat-sub {
            font-size: 10px;
            color: rgba(255, 255, 255, .55);
            margin-top: 2px;
            line-height: 1.3;
        }

        @keyframes cardEntry {
            from {
                opacity: 0;
                transform: translateY(24px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endpush

@section('content')
    @php
        $totalWorkers  = $contractors->sum('employees_count');
        $totalGross    = $payrollSummary->sum('total_gross');
        $totalNet      = $payrollSummary->sum('total_net');
        $totalPaid     = $payrollSummary->sum('paid_count');
        $totalPending  = $payrollSummary->sum(fn($s) => $s->worker_count - $s->paid_count);
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select form-select-sm">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-select form-select-sm">
                        @foreach (range(now()->year - 1, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> View</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="stat-grid">
        <div class="stat-card stat-orange">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-building"></i></div>
                <div>
                    <div class="stat-label">Total Contractors</div>
                    <div class="stat-value">{{ $contractors->count() }}</div>
                    <div class="stat-sub">Registered contractors</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-blue">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="stat-label">Total Workers</div>
                    <div class="stat-value">{{ $totalWorkers }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="stat-label">Total Gross</div>
                    <div class="stat-value">₹{{ number_format($totalGross, 0) }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-purple">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="stat-label">Total Net</div>
                    <div class="stat-value">₹{{ number_format($totalNet, 0) }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-red">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-label">Pending Payments</div>
                    <div class="stat-value">{{ $totalPending }}</div>
                    <div class="stat-sub">{{ $totalPaid }} paid</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="px-3 pt-3">
                <h6 class="fw-semibold mb-0">
                    {{ \Carbon\Carbon::create($year, $month)->format('F Y') }} — All Contractors
                </h6>
                @if ($contractors->count() > 0 && $totalWorkers === 0)
                    <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small">
                        <i class="bi bi-exclamation-circle me-1"></i> No Data Available — no contractor workers were
                        assigned during {{ \Carbon\Carbon::create($year, $month)->format('F Y') }}.
                    </div>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Contractor</th>
                            <th>Total Workers</th>
                            <th>Workers w/ Payroll</th>
                            <th class="text-end">Total Gross</th>
                            <th class="text-end">Total Net</th>
                            <th>Paid</th>
                            <th>Pending</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contractors as $c)
                            @php
                                $ps      = $payrollSummary->get($c->id);
                                $pwCount = optional($ps)->worker_count ?? 0;
                                $gross   = optional($ps)->total_gross ?? 0;
                                $net     = optional($ps)->total_net ?? 0;
                                $paid    = optional($ps)->paid_count ?? 0;
                                $pending = $pwCount - $paid;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $c->name }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        {{ $c->employees_count }}
                                    </span>
                                </td>
                                <td>{{ $pwCount ?: '—' }}</td>
                                <td class="text-end">{{ $gross > 0 ? '₹' . number_format($gross, 0) : '—' }}</td>
                                <td class="text-end fw-semibold {{ $net > 0 ? 'text-success' : '' }}">
                                    {{ $net > 0 ? '₹' . number_format($net, 0) : '—' }}
                                </td>
                                <td>
                                    @if ($paid > 0)
                                        <span class="badge bg-success-subtle text-success">{{ $paid }} paid</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($pending > 0)
                                        <span class="badge bg-warning-subtle text-warning">{{ $pending }} pending</span>
                                    @elseif ($pwCount > 0)
                                        <span class="badge bg-success-subtle text-success">All paid</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('reports.payroll', ['contractor_id' => $c->id, 'month' => $month, 'year' => $year]) }}"
                                       class="btn btn-sm btn-outline-info" title="View Payroll">
                                        <i class="bi bi-cash-stack"></i>
                                    </a>
                                    <a href="{{ route('reports.attendance', ['contractor_id' => $c->id, 'from_date' => \Carbon\Carbon::create($year, $month, 1)->toDateString(), 'to_date' => \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->toDateString()]) }}"
                                       class="btn btn-sm btn-outline-success" title="Attendance Report">
                                        <i class="bi bi-calendar-check"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No contractors found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($contractors->count() > 0 && $payrollSummary->count() > 0)
                        <tfoot>
                            <tr class="table-light fw-semibold">
                                <td colspan="4">Total</td>
                                <td class="text-end">₹{{ number_format($totalGross, 0) }}</td>
                                <td class="text-end text-success">₹{{ number_format($totalNet, 0) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
