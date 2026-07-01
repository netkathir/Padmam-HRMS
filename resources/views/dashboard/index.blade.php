{{--
    File: resources/views/dashboard/index.blade.php
    Purpose: Main dashboard — modern SaaS-style with gradient cards and animations
    Author: System
    Date: 2026-06-30
--}}
@extends('layouts.app')

@section('title', 'Dashboard')
{{-- No page-title section — the hero greeting block below replaces it --}}

@push('styles')
    <style>
        /* ═══════════════════════════════════════════
       DASHBOARD — Modern SaaS Design System
       ═══════════════════════════════════════════ */

        /* ── Animated ambient background ── */
        .dash-bg {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .dash-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .12;
            animation: blobDrift 18s ease-in-out infinite alternate;
        }

        .dash-blob-1 {
            width: 520px;
            height: 520px;
            background: #7c3aed;
            top: -180px;
            left: -120px;
            animation-delay: 0s;
        }

        .dash-blob-2 {
            width: 420px;
            height: 420px;
            background: #0ea5e9;
            top: 30%;
            right: -140px;
            animation-delay: -6s;
        }

        .dash-blob-3 {
            width: 340px;
            height: 340px;
            background: #10b981;
            bottom: -100px;
            left: 30%;
            animation-delay: -12s;
        }

        @keyframes blobDrift {
            0% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(30px, -20px) scale(1.05);
            }

            66% {
                transform: translate(-20px, 30px) scale(.97);
            }

            100% {
                transform: translate(10px, 10px) scale(1.03);
            }
        }

        /* ── Page wrapper (sits above blobs) ── */
        .dash-content {
            position: relative;
            z-index: 1;
        }

        /* ── Greeting hero ── */
        .dash-hero {
            background: linear-gradient(135deg, #0d1433 0%, #1a2248 60%, #16213e 100%);
            border-radius: 14px;
            padding: 14px 22px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .06);
            box-shadow: 0 4px 24px rgba(13, 20, 51, .35);
        }

        .dash-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%234f7ef8' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 1;
            pointer-events: none;
        }

        .dash-hero-glow {
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(79, 126, 248, .25) 0%, transparent 70%);
            top: -70px;
            right: -50px;
            pointer-events: none;
        }

        .dash-greeting {
            font-size: 17px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 2px;
        }

        .dash-greeting-sub {
            font-size: 12px;
            color: #8899bb;
            margin: 0;
        }

        .dash-hero-date {
            font-size: 11px;
            color: #6b7a99;
            text-align: right;
        }

        .dash-hero-day {
            font-size: 18px;
            font-weight: 700;
            color: #c8d0e7;
            line-height: 1;
        }

        /* ── Stat cards ── */
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

        .kpi-card {
            border-radius: 14px;
            padding: 16px 18px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, .15);
            box-shadow: 0 4px 16px rgba(0, 0, 0, .12);
            transition: transform .25s cubic-bezier(.34, 1.56, .64, 1),
                box-shadow .25s ease;
            animation: cardEntry .5s ease both;
            text-decoration: none;
            display: block;
            color: inherit;
        }

        .kpi-card:hover {
            transform: translateY(-4px) scale(1.02);
            text-decoration: none;
            color: inherit;
        }

        .kpi-card:nth-child(1) {
            animation-delay: .05s;
        }

        .kpi-card:nth-child(2) {
            animation-delay: .12s;
        }

        .kpi-card:nth-child(3) {
            animation-delay: .19s;
        }

        .kpi-card:nth-child(4) {
            animation-delay: .26s;
        }

        /* Horizontal inner layout */
        .kpi-inner {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            z-index: 1;
        }

        /* Gradient variants */
        .kpi-purple {
            background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 45%, #a855f7 100%);
            box-shadow: 0 8px 28px rgba(124, 58, 237, .35);
        }

        .kpi-purple:hover {
            box-shadow: 0 16px 40px rgba(124, 58, 237, .5);
        }

        .kpi-green {
            background: linear-gradient(135deg, #065f46 0%, #059669 45%, #10b981 100%);
            box-shadow: 0 8px 28px rgba(5, 150, 105, .35);
        }

        .kpi-green:hover {
            box-shadow: 0 16px 40px rgba(5, 150, 105, .5);
        }

        .kpi-blue {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 45%, #3b82f6 100%);
            box-shadow: 0 8px 28px rgba(29, 78, 216, .35);
        }

        .kpi-blue:hover {
            box-shadow: 0 16px 40px rgba(29, 78, 216, .5);
        }

        .kpi-amber {
            background: linear-gradient(135deg, #92400e 0%, #d97706 45%, #f59e0b 100%);
            box-shadow: 0 8px 28px rgba(217, 119, 6, .35);
        }

        .kpi-amber:hover {
            box-shadow: 0 16px 40px rgba(217, 119, 6, .5);
        }

        /* Glassmorphism inner shine */
        .kpi-card::before {
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

        /* Decorative circle */
        .kpi-card::after {
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

        .kpi-card:hover::after {
            transform: scale(1.35);
        }

        /* Icon — compact square */
        .kpi-icon-wrap {
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

        .kpi-card:nth-child(2) .kpi-icon-wrap {
            animation-delay: -.8s;
        }

        .kpi-card:nth-child(3) .kpi-icon-wrap {
            animation-delay: -1.6s;
        }

        .kpi-card:nth-child(4) .kpi-icon-wrap {
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

        .kpi-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .7);
            margin-bottom: 3px;
            line-height: 1.3;
        }

        .kpi-value {
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .kpi-sub {
            font-size: 11px;
            color: rgba(255, 255, 255, .55);
            margin-top: 3px;
            line-height: 1.3;
        }

        .kpi-arrow {
            position: absolute;
            top: 13px;
            right: 14px;
            font-size: 15px;
            color: rgba(255, 255, 255, .3);
            transition: transform .2s ease, color .2s ease;
            z-index: 1;
        }

        .kpi-card:hover .kpi-arrow {
            transform: translate(2px, -2px);
            color: rgba(255, 255, 255, .65);
        }

        /* ── Card entry animation ── */
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

        /* ── Secondary section cards ── */
        .info-card {
            background: #fff;
            border-radius: 16px;
            border: none;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
            overflow: hidden;
            animation: cardEntry .55s ease both;
            transition: box-shadow .25s ease;
            height: 100%;
        }

        .info-card:hover {
            box-shadow: 0 6px 24px rgba(0, 0, 0, .1);
        }

        .info-card:nth-child(1) {
            animation-delay: .3s;
        }

        .info-card:nth-child(2) {
            animation-delay: .38s;
        }

        .info-card:nth-child(3) {
            animation-delay: .46s;
        }

        .info-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-card-title {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-card-title i {
            font-size: 16px;
        }

        .info-card-badge {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* ── Attendance progress bars ── */
        .att-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            gap: 12px;
        }

        .att-row+.att-row {
            border-top: 1px solid #f9fafb;
        }

        .att-label {
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            min-width: 72px;
        }

        .att-bar-wrap {
            flex: 1;
            height: 6px;
            background: #f3f4f6;
            border-radius: 99px;
            overflow: hidden;
        }

        .att-bar {
            height: 100%;
            border-radius: 99px;
            transition: width 1s cubic-bezier(.4, 0, .2, 1);
            width: 0;
        }

        .att-count {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            min-width: 28px;
            text-align: right;
        }

        /* ── Payroll stats ── */
        .payroll-stat {
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .payroll-stat+.payroll-stat {
            border-top: 1px solid #f9fafb;
        }

        .payroll-stat-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }

        .payroll-stat-value {
            font-size: 18px;
            font-weight: 800;
            color: #111827;
            font-variant-numeric: tabular-nums;
        }

        /* ── Department list ── */
        .dept-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 20px;
            font-size: 13px;
        }

        .dept-row+.dept-row {
            border-top: 1px solid #f9fafb;
        }

        .dept-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dept-name {
            flex: 1;
            color: #374151;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dept-bar-wrap {
            width: 70px;
            height: 5px;
            background: #f3f4f6;
            border-radius: 99px;
            overflow: hidden;
        }

        .dept-bar {
            height: 100%;
            border-radius: 99px;
            background: #4f7ef8;
            transition: width 1.1s cubic-bezier(.4, 0, .2, 1);
            width: 0;
        }

        .dept-count {
            font-size: 12px;
            font-weight: 700;
            color: #4f7ef8;
            min-width: 24px;
            text-align: right;
        }

        /* ── Dept dot colours (cycling) ── */
        .dept-dot-0 {
            background: #4f7ef8;
        }

        .dept-dot-1 {
            background: #10b981;
        }

        .dept-dot-2 {
            background: #a855f7;
        }

        .dept-dot-3 {
            background: #f59e0b;
        }

        .dept-dot-4 {
            background: #ef4444;
        }

        .dept-dot-5 {
            background: #06b6d4;
        }

        .dept-dot-6 {
            background: #f97316;
        }

        .dept-dot-7 {
            background: #8b5cf6;
        }

        /* ── Bottom cards: leaves + events ── */
        .bottom-card {
            animation: cardEntry .55s ease both;
        }

        .bottom-card:nth-child(1) {
            animation-delay: .5s;
        }

        .bottom-card:nth-child(2) {
            animation-delay: .6s;
        }

        /* ── Leave status badges ── */
        .ls-pending {
            background: #fef9c3;
            color: #854d0e;
        }

        .ls-approved {
            background: #dcfce7;
            color: #166534;
        }

        .ls-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .ls-cancelled {
            background: #f3f4f6;
            color: #374151;
        }

        /* ── Event list ── */
        .event-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 20px;
        }

        .event-item+.event-item {
            border-top: 1px solid #f9fafb;
        }

        .event-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .event-icon-bday {
            background: #fee2e2;
            color: #dc2626;
        }

        .event-icon-anni {
            background: #ede9fe;
            color: #7c3aed;
        }

        .event-name {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }

        .event-meta {
            font-size: 12px;
            color: #9ca3af;
        }

        /* ── Tooltip ── */
        .kpi-card[data-tooltip] {
            position: relative;
        }

        /* ── Table in bottom cards ── */
        .dash-table {
            font-size: 13px;
            margin: 0;
        }

        .dash-table th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #9ca3af;
            background: #f9fafb;
            border-bottom: 1px solid #f3f4f6;
            padding: 10px 16px;
            font-weight: 600;
        }

        .dash-table td {
            padding: 10px 16px;
            border-bottom: 1px solid rgba(0, 0, 0, .04);
            vertical-align: middle;
            transition: background .15s ease;
        }

        .dash-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* ── Leave row colour coding ── */
        .lr-approved td {
            background: #f0fdf4;
        }

        .lr-approved td,
        .lr-approved .lr-name {
            color: #166534 !important;
        }

        .lr-approved td {
            border-bottom-color: #dcfce7;
        }

        .lr-pending td {
            background: #fffbeb;
        }

        .lr-pending td,
        .lr-pending .lr-name {
            color: #92400e !important;
        }

        .lr-pending td {
            border-bottom-color: #fef3c7;
        }

        .lr-rejected td {
            background: #fff1f2;
        }

        .lr-rejected td,
        .lr-rejected .lr-name {
            color: #9f1239 !important;
        }

        .lr-rejected td {
            border-bottom-color: #fecdd3;
        }

        .lr-cancelled td {
            background: #f8fafc;
        }

        .lr-cancelled td,
        .lr-cancelled .lr-name {
            color: #64748b !important;
        }

        .lr-cancelled td {
            border-bottom-color: #e2e8f0;
        }

        /* ── Row hover elevation ── */
        .dash-table tbody tr {
            transition: filter .15s ease, box-shadow .15s ease;
        }

        .dash-table tbody tr:hover td {
            filter: brightness(.96);
        }

        .dash-table tbody tr:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, .07);
            position: relative;
            z-index: 1;
        }
    </style>
@endpush

@section('content')
    <div class="dash-content">

        {{-- ── Ambient blobs ─────────────────────────────────────── --}}
        <div class="dash-bg">
            <div class="dash-blob dash-blob-1"></div>
            <div class="dash-blob dash-blob-2"></div>
            <div class="dash-blob dash-blob-3"></div>
        </div>

        {{-- ── Hero greeting ─────────────────────────────────────── --}}
        <div class="dash-hero">
            <div class="dash-hero-glow"></div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <p class="dash-greeting">
                        @php
                            $hour = now()->hour;
                            $greeting = match (true) {
                                $hour >= 5 && $hour < 12 => 'Good Morning',
                                $hour >= 12 && $hour < 17 => 'Good Afternoon',
                                $hour >= 17 && $hour < 22 => 'Good Evening',
                                default => 'Good Night',
                            };
                        @endphp
                        {{ $greeting }}, {{ auth()->user()->name }} 👋
                    </p>
                    <p class="dash-greeting-sub">Here's what's happening with your workforce today.</p>
                </div>
                <div class="text-end">
                    <div class="dash-hero-day">{{ now()->format('d') }}</div>
                    <div class="dash-hero-date">{{ now()->format('l, F Y') }}</div>
                </div>
            </div>
        </div>

        {{-- ── KPI Stat Cards ─────────────────────────────────────── --}}
        <div class="stat-grid">

            {{-- Total Employees — Purple --}}
            <a href="{{ route('employees.index') }}" class="kpi-card kpi-purple"
                title="Total active employees in the system">
                <i class="bi bi-arrow-up-right kpi-arrow"></i>
                <div class="kpi-inner">
                    <div class="kpi-icon-wrap"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="kpi-label">Total Employees</div>
                        <div class="kpi-value" data-counter="{{ $totalEmployees }}">{{ $totalEmployees }}</div>
                        <div class="kpi-sub">Active headcount</div>
                    </div>
                </div>
            </a>

            {{-- Present Today — Green --}}
            <a href="{{ route('attendance.index') }}" class="kpi-card kpi-green"
                title="Employees present or on half day today">
                <i class="bi bi-arrow-up-right kpi-arrow"></i>
                <div class="kpi-inner">
                    <div class="kpi-icon-wrap"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="kpi-label">Present Today</div>
                        <div class="kpi-value" data-counter="{{ $presentToday }}">{{ $presentToday }}</div>
                        <div class="kpi-sub">
                            @if ($totalEmployees > 0)
                                {{ round(($presentToday / $totalEmployees) * 100) }}% attendance rate
                            @else
                                No data
                            @endif
                        </div>
                    </div>
                </div>
            </a>

            {{-- Pending Leaves — Blue --}}
            <a href="{{ route('leaves.index') }}?status=pending" class="kpi-card kpi-blue"
                title="Leave requests awaiting approval">
                <i class="bi bi-arrow-up-right kpi-arrow"></i>
                <div class="kpi-inner">
                    <div class="kpi-icon-wrap"><i class="bi bi-calendar-x-fill"></i></div>
                    <div>
                        <div class="kpi-label">Pending Leaves</div>
                        <div class="kpi-value" data-counter="{{ $pendingLeaves }}">{{ $pendingLeaves }}</div>
                        <div class="kpi-sub">Awaiting approval</div>
                    </div>
                </div>
            </a>

            {{-- Absent Today — Amber --}}
            <a href="{{ route('attendance.index') }}?status=absent" class="kpi-card kpi-amber"
                title="Employees absent or not marked today">
                <i class="bi bi-arrow-up-right kpi-arrow"></i>
                <div class="kpi-inner">
                    <div class="kpi-icon-wrap"><i class="bi bi-person-x-fill"></i></div>
                    <div>
                        <div class="kpi-label">Absent Today</div>
                        <div class="kpi-value" data-counter="{{ max(0, $absentToday) }}">{{ max(0, $absentToday) }}</div>
                        <div class="kpi-sub">Not present today</div>
                    </div>
                </div>
            </a>

        </div>

        {{-- ── Secondary Row: Attendance · Payroll · Departments ─── --}}
        <div class="row g-4 mb-4">

            {{-- Today's Attendance Breakdown --}}
            <div class="col-lg-4 col-md-6">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-title">
                            <i class="bi bi-pie-chart-fill text-primary"></i>
                            Today's Attendance
                        </div>
                        <span class="info-card-badge bg-primary-subtle text-primary">
                            {{ \Carbon\Carbon::parse($today)->format('d M') }}
                        </span>
                    </div>
                    <div class="py-1">
                        @php
                            $attDefs = [
                                'present' => ['Present', '#10b981'],
                                'absent' => ['Absent', '#ef4444'],
                                'half_day' => ['Half Day', '#f59e0b'],
                                'late' => ['Late', '#06b6d4'],
                                'on_leave' => ['On Leave', '#8b5cf6'],
                            ];
                        @endphp
                        @foreach ($attDefs as $key => [$label, $color])
                            @php
                                $cnt = $attendanceBreakdown[$key] ?? 0;
                                $pct = $totalEmployees > 0 ? round(($cnt / $totalEmployees) * 100) : 0;
                            @endphp
                            <div class="att-row">
                                <span class="att-label">{{ $label }}</span>
                                <div class="att-bar-wrap">
                                    <div class="att-bar" style="background:{{ $color }};"
                                        data-width="{{ $pct }}"></div>
                                </div>
                                <span class="att-count">{{ $cnt }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="px-4 pb-4 pt-3">
                        <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-arrow-right me-1"></i> Full Attendance
                        </a>
                    </div>
                </div>
            </div>

            {{-- Payroll Summary --}}
            <div class="col-lg-4 col-md-6">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-title">
                            <i class="bi bi-cash-coin text-success"></i>
                            Payroll — {{ now()->format('M Y') }}
                        </div>
                        <span class="info-card-badge" style="background:#dcfce7;color:#166534;">
                            {{ now()->format('Y') }}
                        </span>
                    </div>
                    <div class="payroll-stat">
                        <div>
                            <div class="payroll-stat-label">Records Generated</div>
                            <div class="payroll-stat-value" data-counter="{{ $payrollMonth->count ?? 0 }}">
                                {{ $payrollMonth->count ?? 0 }}</div>
                        </div>
                        <div
                            style="width:44px;height:44px;border-radius:12px;background:#f0fdf4;
                            display:flex;align-items:center;justify-content:center;font-size:20px;color:#16a34a;">
                            <i class="bi bi-receipt"></i>
                        </div>
                    </div>
                    <div class="payroll-stat" style="background:#fafafa;">
                        <div>
                            <div class="payroll-stat-label">Gross Payable</div>
                            <div class="payroll-stat-value" style="font-size:15px;">
                                ₹{{ number_format($payrollMonth->gross ?? 0, 0) }}
                            </div>
                        </div>
                        <div
                            style="width:44px;height:44px;border-radius:12px;background:#eff6ff;
                            display:flex;align-items:center;justify-content:center;font-size:20px;color:#2563eb;">
                            <i class="bi bi-graph-up"></i>
                        </div>
                    </div>
                    <div class="payroll-stat">
                        <div>
                            <div class="payroll-stat-label">Net Payable</div>
                            <div class="payroll-stat-value" style="color:#16a34a;font-size:15px;">
                                ₹{{ number_format($payrollMonth->total ?? 0, 0) }}
                            </div>
                        </div>
                        <div
                            style="width:44px;height:44px;border-radius:12px;background:#f0fdf4;
                            display:flex;align-items:center;justify-content:center;font-size:20px;color:#16a34a;">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                    <div class="px-4 pb-4 pt-3">
                        <a href="{{ route('payroll.index') }}" class="btn btn-sm btn-success w-100">
                            <i class="bi bi-arrow-right me-1"></i> View Payroll
                        </a>
                    </div>
                </div>
            </div>

            {{-- Department Headcount --}}
            <div class="col-lg-4 col-md-12">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-title">
                            <i class="bi bi-diagram-3-fill text-purple" style="color:#7c3aed;"></i>
                            By Department
                        </div>
                        <span class="info-card-badge bg-secondary-subtle text-secondary">
                            {{ $deptWise->count() }} depts
                        </span>
                    </div>
                    <div class="py-1">
                        @forelse($deptWise as $i => $dept)
                            <div class="dept-row">
                                <div class="dept-dot dept-dot-{{ $i % 8 }}"></div>
                                <div class="dept-name" title="{{ $dept->dept }}">{{ $dept->dept }}</div>
                                <div class="dept-bar-wrap">
                                    <div class="dept-bar" style="background:var(--dc-{{ $i % 8 }},#4f7ef8);"
                                        data-width="{{ $totalEmployees > 0 ? round(($dept->cnt / $totalEmployees) * 100) : 0 }}">
                                    </div>
                                </div>
                                <div class="dept-count">{{ $dept->cnt }}</div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5" style="font-size:13px;">No department data</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Bottom Row: Leaves · Events ─────────────────────────── --}}
        <div class="row g-4">

            {{-- Recent Leave Requests --}}
            <div class="col-lg-7 bottom-card">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-title">
                            <i class="bi bi-calendar-x-fill" style="color:#2563eb;"></i>
                            Recent Leave Requests
                        </div>
                        <a href="{{ route('leaves.index') }}" class="btn btn-sm"
                            style="font-size:12px;background:#eff6ff;color:#2563eb;border:none;">
                            View All <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <table class="dash-table w-100">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th class="text-center">Days</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLeaves as $leave)
                                @php
                                    $rowClass = match ($leave->status) {
                                        'approved' => 'lr-approved',
                                        'rejected' => 'lr-rejected',
                                        'cancelled' => 'lr-cancelled',
                                        default => 'lr-pending',
                                    };
                                    $badgeClass = match ($leave->status) {
                                        'approved' => 'ls-approved',
                                        'rejected' => 'ls-rejected',
                                        'cancelled' => 'ls-cancelled',
                                        default => 'ls-pending',
                                    };
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td>
                                        <div class="fw-semibold lr-name">{{ $leave->employee->full_name ?? '—' }}</div>
                                    </td>
                                    <td>{{ $leave->leaveType->name ?? '—' }}</td>
                                    <td class="text-center fw-semibold">{{ $leave->total_days }}</td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}"
                                            style="border-radius:20px;font-size:11px;padding:4px 10px;font-weight:600;">
                                            {{ ucfirst($leave->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="bi bi-calendar-check d-block mb-2"
                                            style="font-size:28px;opacity:.3;"></i>
                                        No leave requests
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Upcoming Events --}}
            <div class="col-lg-5 bottom-card">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-title">
                            <i class="bi bi-gift-fill" style="color:#dc2626;"></i>
                            Upcoming Events
                        </div>
                        <span class="info-card-badge bg-danger-subtle text-danger">Next 7 days</span>
                    </div>
                    <div>
                        @foreach ($birthdays as $emp)
                            <div class="event-item">
                                <div class="event-icon event-icon-bday">🎂</div>
                                <div>
                                    <div class="event-name">{{ $emp->full_name }}</div>
                                    <div class="event-meta">Birthday · {{ $emp->date_of_birth->format('d M') }} ·
                                        {{ $emp->department->name ?? '' }}</div>
                                </div>
                            </div>
                        @endforeach
                        @foreach ($anniversaries as $emp)
                            <div class="event-item">
                                <div class="event-icon event-icon-anni">⭐</div>
                                <div>
                                    <div class="event-name">{{ $emp->full_name }}</div>
                                    <div class="event-meta">Work Anniversary · {{ $emp->date_of_joining->format('d M') }}
                                        · {{ $emp->department->name ?? '' }}</div>
                                </div>
                            </div>
                        @endforeach
                        @if ($birthdays->isEmpty() && $anniversaries->isEmpty())
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-emoji-smile d-block mb-2" style="font-size:28px;opacity:.3;"></i>
                                <div style="font-size:13px;">No upcoming events in the next 7 days</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>{{-- /.dash-content --}}
@endsection

@push('scripts')
    <script>
        /* ── Number counter animation ── */
        function animateCounter(el) {
            const target = parseInt(el.dataset.counter, 10);
            if (isNaN(target)) return;
            const duration = 1200;
            const start = performance.now();
            const format = n => n.toLocaleString('en-IN');

            function tick(now) {
                const elapsed = Math.min(now - start, duration);
                const progress = 1 - Math.pow(1 - elapsed / duration, 3); // ease-out cubic
                el.textContent = format(Math.round(progress * target));
                if (elapsed < duration) requestAnimationFrame(tick);
                else el.textContent = format(target);
            }
            requestAnimationFrame(tick);
        }

        /* ── Animate progress bars & counters when visible ── */
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el = entry.target;

                // Counter
                if (el.dataset.counter !== undefined) animateCounter(el);

                // Attendance / dept bars
                el.querySelectorAll('[data-width]').forEach(bar => {
                    bar.style.width = bar.dataset.width + '%';
                });

                observer.unobserve(el);
            });
        }, {
            threshold: 0.15
        });

        document.querySelectorAll('.kpi-card, .info-card').forEach(card => observer.observe(card));

        // Also fire bars inside info-cards directly
        document.querySelectorAll('.info-card').forEach(card => {
            card.querySelectorAll('[data-width]').forEach(bar => {
                // Trigger after card entry animation (~500ms)
                setTimeout(() => {
                    bar.style.width = bar.dataset.width + '%';
                }, 600);
            });
        });
    </script>
@endpush
