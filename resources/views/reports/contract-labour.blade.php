@extends('layouts.app')
@section('title', 'Contract Labour Report')
@section('page-title', 'Contract Labour Report')
@section('page-subtitle', 'Contract labour attendance, wages, and payment summary')

@section('page-actions')
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Reports
    </a>
@endsection

@push('styles')
    <style>
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        @media (max-width: 991px) {
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

        .stat-card:nth-child(4) {
            animation-delay: .26s;
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

        .stat-indigo {
            background: linear-gradient(135deg, #312e81 0%, #4f46e5 45%, #818cf8 100%);
            box-shadow: 0 8px 28px rgba(79, 70, 229, .35);
        }

        .stat-indigo:hover {
            box-shadow: 0 16px 40px rgba(79, 70, 229, .5);
        }

        .stat-green {
            background: linear-gradient(135deg, #065f46 0%, #059669 45%, #10b981 100%);
            box-shadow: 0 8px 28px rgba(5, 150, 105, .35);
        }

        .stat-green:hover {
            box-shadow: 0 16px 40px rgba(5, 150, 105, .5);
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

        .stat-card:nth-child(4) .stat-icon-wrap {
            animation-delay: -2.4s;
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
                    <label class="form-label">Contractor</label>
                    <select name="contractor_id" class="form-select form-select-sm">
                        <option value="">All Contractors</option>
                        @foreach ($contractors as $c)
                            <option value="{{ $c->id }}"
                                {{ request('contractor_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="cancelled" {{ request('payment_status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
        <div class="stat-card stat-indigo">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-person-check"></i></div>
                <div>
                    <div class="stat-label">Contract Workers</div>
                    <div class="stat-value">{{ $totals->worker_count ?? 0 }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <div class="stat-label">Total Wages</div>
                    <div class="stat-value">₹{{ number_format($totals->total_wages ?? 0, 0) }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-orange">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <div class="stat-label">Total OT Amount</div>
                    <div class="stat-value">₹{{ number_format($totals->total_ot ?? 0, 0) }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-purple">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="stat-label">Paid / Net Wages</div>
                    <div class="stat-value">{{ $paidCount ?? 0 }}</div>
                    <div class="stat-sub">₹{{ number_format($totals->total_net ?? 0, 0) }} net</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Contractor</th>
                            <th class="text-end">Present Days</th>
                            <th class="text-end">Absent Days</th>
                            <th class="text-end">OT Hours</th>
                            <th class="text-end">Gross Wages (₹)</th>
                            <th class="text-end">Deductions (₹)</th>
                            <th class="text-end">Net Wages (₹)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $rec)
                            @php
                                $sc = match ($rec->payment_status) {
                                    'paid' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'warning',
                                };
                            @endphp
                            <tr>
                                <td><strong>{{ $rec->worker->name ?? '—' }}</strong></td>
                                <td><small>{{ $rec->contractor->name ?? '—' }}</small></td>
                                <td class="text-end">{{ number_format($rec->present_days, 1) }}</td>
                                <td class="text-end">{{ number_format($rec->absent_days, 1) }}</td>
                                <td class="text-end">{{ number_format($rec->ot_hours, 1) }}</td>
                                <td class="text-end">{{ number_format($rec->gross_wages, 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($rec->deductions, 2) }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format($rec->net_wages, 2) }}</td>
                                <td><span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($rec->payment_status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No contract labour records for the
                                    selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-2">{{ $records->withQueryString()->links() }}</div>
@endsection
