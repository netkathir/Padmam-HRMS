@extends('layouts.app')
@section('title', 'Attendance Report')
@section('page-title', 'Attendance Report')
@section('page-subtitle', 'Detailed attendance analysis')

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

        .stat-orange {
            background: linear-gradient(135deg, #92400e 0%, #d97706 45%, #f59e0b 100%);
            box-shadow: 0 8px 28px rgba(217, 119, 6, .35);
        }

        .stat-orange:hover {
            box-shadow: 0 16px 40px rgba(217, 119, 6, .5);
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
            font-size: 26px;
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
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm"
                        value="{{ request('from_date', $fromDate) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm"
                        value="{{ request('to_date', $toDate) }}">
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
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach (['present', 'absent', 'late', 'half_day', 'leave', 'work_from_home', 'holiday'] as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter"></i> Filter</button>
                    <a href="#" class="btn btn-sm btn-outline-success disabled"><i
                            class="bi bi-file-earmark-excel"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="stat-grid">
        <div class="stat-card stat-green">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-person-check-fill"></i></div>
                <div>
                    <div class="stat-label">Present</div>
                    <div class="stat-value">{{ $summary['present'] ?? 0 }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::parse($fromDate)->format('d M') }} -
                        {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-red">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-person-x-fill"></i></div>
                <div>
                    <div class="stat-label">Absent</div>
                    <div class="stat-value">{{ $summary['absent'] ?? 0 }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::parse($fromDate)->format('d M') }} -
                        {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-orange">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div>
                    <div class="stat-label">Late</div>
                    <div class="stat-value">{{ $summary['late'] ?? 0 }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::parse($fromDate)->format('d M') }} -
                        {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-blue">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-calendar-x-fill"></i></div>
                <div>
                    <div class="stat-label">On Leave</div>
                    <div class="stat-value">{{ $summary['leave'] ?? 0 }}</div>
                    <div class="stat-sub">{{ \Carbon\Carbon::parse($fromDate)->format('d M') }} -
                        {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>
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
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Hours</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $rec)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($rec->date)->format('d M Y') }}</td>
                                <td>
                                    <strong>{{ $rec->employee->employee_code }}</strong><br>
                                    <small>{{ $rec->employee->full_name ?? '—' }}</small>
                                </td>
                                <td><small>{{ $rec->employee->department->name ?? '—' }}</small></td>
                                <td>
                                    @php
                                        $sc = match ($rec->status) {
                                            'present' => 'success',
                                            'absent' => 'danger',
                                            'late' => 'warning',
                                            'half_day' => 'info',
                                            'leave' => 'primary',
                                            'work_from_home' => 'secondary',
                                            default => 'light',
                                        };
                                    @endphp
                                    <span
                                        class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst(str_replace('_', ' ', $rec->status)) }}</span>
                                </td>
                                <td>{{ $rec->in_time ? \Carbon\Carbon::parse($rec->in_time)->format('H:i') : '—' }}</td>
                                <td>{{ $rec->out_time ? \Carbon\Carbon::parse($rec->out_time)->format('H:i') : '—' }}</td>
                                <td>{{ $rec->work_minutes ? round($rec->work_minutes / 60, 1) . 'h' : '—' }}</td>
                                <td><small>{{ $rec->remarks ?? '' }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No records for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-2">{{ $records->withQueryString()->links() }}</div>
@endsection
