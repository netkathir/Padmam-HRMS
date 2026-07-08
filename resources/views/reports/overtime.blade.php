@extends('layouts.app')
@section('title', 'OT Report')
@section('page-title', 'OT Report')
@section('page-subtitle', 'Employee overtime hours, wages, and date-wise details')

@section('page-actions')
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Reports
    </a>
@endsection

@push('styles')
    <style>
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        @media (max-width: 767px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            border-radius: 14px;
            padding: 16px 18px;
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

        .stat-cyan {
            background: linear-gradient(135deg, #164e63 0%, #0891b2 45%, #22d3ee 100%);
            box-shadow: 0 8px 28px rgba(8, 145, 178, .35);
        }

        .stat-cyan:hover {
            box-shadow: 0 16px 40px rgba(8, 145, 178, .5);
        }

        .stat-green {
            background: linear-gradient(135deg, #065f46 0%, #059669 45%, #10b981 100%);
            box-shadow: 0 8px 28px rgba(5, 150, 105, .35);
        }

        .stat-green:hover {
            box-shadow: 0 16px 40px rgba(5, 150, 105, .5);
        }

        .stat-blue {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 45%, #3b82f6 100%);
            box-shadow: 0 8px 28px rgba(29, 78, 216, .35);
        }

        .stat-blue:hover {
            box-shadow: 0 16px 40px rgba(29, 78, 216, .5);
        }

        .stat-inner {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            z-index: 1;
        }

        .stat-icon-wrap {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border-radius: 11px;
            background: rgba(255, 255, 255, .2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
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
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .7);
            margin-bottom: 3px;
            line-height: 1.3;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .stat-sub {
            font-size: 11px;
            color: rgba(255, 255, 255, .55);
            margin-top: 3px;
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
                        @for ($y = now()->year; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}"
                                {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter"></i> Filter</button>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-excel"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="stat-grid">
        <div class="stat-card stat-cyan">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-label">Total OT Hours</div>
                    <div class="stat-value">{{ number_format($totals->total_hours ?? 0, 1) }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-cash-coin"></i></div>
                <div>
                    <div class="stat-label">Total OT Wages</div>
                    <div class="stat-value">₹{{ number_format($totals->total_amount ?? 0, 0) }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-blue">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="stat-label">Employees with OT</div>
                    <div class="stat-value">{{ $totals->employee_count ?? 0 }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Employee-wise OT summary --}}
    <div class="card mb-3">
        <div class="card-body px-3 pt-3 pb-0">
            <h6 class="fw-semibold mb-0">Employee-wise Summary</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th class="text-end">OT Hours</th>
                            <th class="text-end">OT Wages (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary as $rec)
                            <tr>
                                <td><strong>{{ $rec->employee->employee_code ?? '—' }}</strong><br>
                                    <small>{{ $rec->employee->full_name ?? '—' }}</small>
                                </td>
                                <td><small>{{ $rec->employee->department->name ?? '—' }}</small></td>
                                <td class="text-end">{{ number_format($rec->ot_hours, 1) }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format($rec->ot_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No overtime records for the selected
                                    filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end">{{ $summary->withQueryString()->links() }}</div>
    </div>

    {{-- Date-wise OT details --}}
    <div class="card">
        <div class="card-body px-3 pt-3 pb-0">
            <h6 class="fw-semibold mb-0">Date-wise Details</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th class="text-end">OT Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyOt as $att)
                            <tr>
                                <td>{{ $att->date->format('d-m-Y') }}</td>
                                <td><small>{{ $att->employee->full_name ?? '—' }}</small></td>
                                <td><small>{{ $att->employee->department->name ?? '—' }}</small></td>
                                <td class="text-end">{{ $att->ot_hours }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No date-wise OT punches for the
                                    selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end">{{ $dailyOt->withQueryString()->links() }}</div>
    </div>
@endsection
