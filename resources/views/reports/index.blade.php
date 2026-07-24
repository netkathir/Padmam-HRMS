@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')
@section('page-subtitle', 'HRMS reports and analytics')

@push('styles')
    <style>
        /* ── Report Cards ── */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 991px) {
            .report-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 575px) {
            .report-grid {
                grid-template-columns: 1fr;
            }
        }

        .report-card {
            border-radius: 14px;
            padding: 28px 20px;
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
            text-align: center;
        }

        .report-card:hover {
            transform: translateY(-4px) scale(1.02);
            text-decoration: none;
            color: inherit;
            box-shadow: 0 12px 32px rgba(0, 0, 0, .2);
        }

        .report-card:nth-child(1) {
            animation-delay: .05s;
        }

        .report-card:nth-child(2) {
            animation-delay: .12s;
        }

        .report-card:nth-child(3) {
            animation-delay: .19s;
        }

        .report-card:nth-child(4) {
            animation-delay: .26s;
        }

        .report-card:nth-child(5) {
            animation-delay: .33s;
        }

        .report-card:nth-child(6) {
            animation-delay: .40s;
        }

        .report-card:nth-child(7) {
            animation-delay: .47s;
        }

        .report-card:nth-child(8) {
            animation-delay: .54s;
        }

        .report-card:nth-child(9) {
            animation-delay: .61s;
        }

        /* Glassmorphism inner shine */
        .report-card::before {
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
        .report-card::after {
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

        .report-card:hover::after {
            transform: scale(1.35);
        }

        /* Gradient variants */
        .report-blue {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 45%, #3b82f6 100%);
            box-shadow: 0 8px 28px rgba(29, 78, 216, .35);
        }

        .report-blue:hover {
            box-shadow: 0 16px 40px rgba(29, 78, 216, .5);
        }

        .report-green {
            background: linear-gradient(135deg, #065f46 0%, #059669 45%, #10b981 100%);
            box-shadow: 0 8px 28px rgba(5, 150, 105, .35);
        }

        .report-green:hover {
            box-shadow: 0 16px 40px rgba(5, 150, 105, .5);
        }

        .report-orange {
            background: linear-gradient(135deg, #92400e 0%, #d97706 45%, #f59e0b 100%);
            box-shadow: 0 8px 28px rgba(217, 119, 6, .35);
        }

        .report-orange:hover {
            box-shadow: 0 16px 40px rgba(217, 119, 6, .5);
        }

        .report-purple {
            background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 45%, #a855f7 100%);
            box-shadow: 0 8px 28px rgba(124, 58, 237, .35);
        }

        .report-purple:hover {
            box-shadow: 0 16px 40px rgba(124, 58, 237, .5);
        }

        .report-teal {
            background: linear-gradient(135deg, #134e4a 0%, #0d9488 45%, #2dd4bf 100%);
            box-shadow: 0 8px 28px rgba(13, 148, 136, .35);
        }

        .report-teal:hover {
            box-shadow: 0 16px 40px rgba(13, 148, 136, .5);
        }

        .report-indigo {
            background: linear-gradient(135deg, #312e81 0%, #4f46e5 45%, #818cf8 100%);
            box-shadow: 0 8px 28px rgba(79, 70, 229, .35);
        }

        .report-indigo:hover {
            box-shadow: 0 16px 40px rgba(79, 70, 229, .5);
        }

        .report-red {
            background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 45%, #f87171 100%);
            box-shadow: 0 8px 28px rgba(220, 38, 38, .35);
        }

        .report-red:hover {
            box-shadow: 0 16px 40px rgba(220, 38, 38, .5);
        }

        .report-cyan {
            background: linear-gradient(135deg, #164e63 0%, #0891b2 45%, #22d3ee 100%);
            box-shadow: 0 8px 28px rgba(8, 145, 178, .35);
        }

        .report-cyan:hover {
            box-shadow: 0 16px 40px rgba(8, 145, 178, .5);
        }

        .report-slate {
            background: linear-gradient(135deg, #1e293b 0%, #475569 45%, #94a3b8 100%);
            box-shadow: 0 8px 28px rgba(71, 85, 105, .35);
        }

        .report-slate:hover {
            box-shadow: 0 16px 40px rgba(71, 85, 105, .5);
        }

        .report-pink {
            background: linear-gradient(135deg, #831843 0%, #db2777 45%, #f472b6 100%);
            box-shadow: 0 8px 28px rgba(219, 39, 119, .35);
        }

        .report-pink:hover {
            box-shadow: 0 16px 40px rgba(219, 39, 119, .5);
        }

        /* Icon */
        .report-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 14px;
            border-radius: 14px;
            background: rgba(255, 255, 255, .2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
            backdrop-filter: blur(4px);
            animation: iconFloat 3.5s ease-in-out infinite;
        }

        .report-card:nth-child(2) .report-icon {
            animation-delay: -.8s;
        }

        .report-card:nth-child(3) .report-icon {
            animation-delay: -1.6s;
        }

        .report-card:nth-child(4) .report-icon {
            animation-delay: -2.4s;
        }

        .report-card:nth-child(5) .report-icon {
            animation-delay: -3.2s;
        }

        .report-card:nth-child(6) .report-icon {
            animation-delay: -.4s;
        }

        .report-card:nth-child(7) .report-icon {
            animation-delay: -1.2s;
        }

        .report-card:nth-child(8) .report-icon {
            animation-delay: -2s;
        }

        .report-card:nth-child(9) .report-icon {
            animation-delay: -2.8s;
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

        .report-title {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 6px;
            letter-spacing: .01em;
        }

        .report-desc {
            font-size: 12px;
            color: rgba(255, 255, 255, .7);
            line-height: 1.4;
            margin: 0;
        }

        .report-arrow {
            position: absolute;
            top: 14px;
            right: 14px;
            font-size: 16px;
            color: rgba(255, 255, 255, .3);
            transition: transform .2s ease, color .2s ease;
            z-index: 1;
        }

        .report-card:hover .report-arrow {
            transform: translate(2px, -2px);
            color: rgba(255, 255, 255, .65);
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
    <h5 class="mb-3">Quick Reports</h5>
    <div class="report-grid">
        {{-- Attendance Report — Blue --}}
        <a href="{{ route('reports.attendance') }}" class="report-card report-blue">
            <i class="bi bi-arrow-up-right report-arrow"></i>
            <div class="report-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="report-title">Attendance Report</div>
            <p class="report-desc">Daily/monthly attendance summary</p>
        </a>

        {{-- Employee Report — Green --}}
        <a href="{{ route('reports.employees') }}" class="report-card report-green">
            <i class="bi bi-arrow-up-right report-arrow"></i>
            <div class="report-icon"><i class="bi bi-people"></i></div>
            <div class="report-title">Employee Report</div>
            <p class="report-desc">Employee list with details</p>
        </a>

        {{-- Leave Report — Orange --}}
        <a href="{{ route('reports.leave') }}" class="report-card report-orange">
            <i class="bi bi-arrow-up-right report-arrow"></i>
            <div class="report-icon"><i class="bi bi-calendar-x"></i></div>
            <div class="report-title">Leave Report</div>
            <p class="report-desc">Leave requests summary</p>
        </a>

        {{-- Payroll Report — Purple --}}
        <a href="{{ route('reports.payroll') }}" class="report-card report-purple">
            <i class="bi bi-arrow-up-right report-arrow"></i>
            <div class="report-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="report-title">Payroll Report</div>
            <p class="report-desc">Payroll summary by period</p>
        </a>

        {{-- Contractor Report — Teal --}}
        <a href="{{ route('reports.contractor') }}" class="report-card report-teal">
            <i class="bi bi-arrow-up-right report-arrow"></i>
            <div class="report-icon"><i class="bi bi-person-workspace"></i></div>
            <div class="report-title">Contractor Report</div>
            <p class="report-desc">Contract worker payment summary</p>
        </a>

        {{-- Contract Labour Report — Indigo --}}
        <a href="{{ route('reports.contract-labour') }}" class="report-card report-indigo">
            <i class="bi bi-arrow-up-right report-arrow"></i>
            <div class="report-icon"><i class="bi bi-person-check"></i></div>
            <div class="report-title">Contract Labour Report</div>
            <p class="report-desc">Summary of contract labour details, attendance, wages, and payments</p>
        </a>

        {{-- PF / ESI Report — Red --}}
        <a href="{{ route('reports.pf-esi') }}" class="report-card report-red">
            <i class="bi bi-arrow-up-right report-arrow"></i>
            <div class="report-icon"><i class="bi bi-shield-plus"></i></div>
            <div class="report-title">PF / ESI Report</div>
            <p class="report-desc">PF &amp; ESI contribution details, deductions, and employer contributions</p>
        </a>

        {{-- OT Report — Cyan --}}
        <a href="{{ route('reports.overtime') }}" class="report-card report-cyan">
            <i class="bi bi-arrow-up-right report-arrow"></i>
            <div class="report-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="report-title">OT Report</div>
            <p class="report-desc">Employee overtime summary with OT hours, OT wages, and date-wise details</p>
        </a>

        {{-- LOP Report — Slate --}}
        <a href="{{ route('reports.lop') }}" class="report-card report-slate">
            <i class="bi bi-arrow-up-right report-arrow"></i>
            <div class="report-icon"><i class="bi bi-graph-down-arrow"></i></div>
            <div class="report-title">LOP Report</div>
            <p class="report-desc">Loss of Pay report showing LOP days, deduction amount, and employee-wise summary</p>
        </a>

        {{-- Salary Structure — Pink --}}
        <a href="{{ route('reports.salary-structure.index') }}" class="report-card report-pink">
            <i class="bi bi-arrow-up-right report-arrow"></i>
            <div class="report-icon"><i class="bi bi-diagram-3"></i></div>
            <div class="report-title">Salary Structure</div>
            <p class="report-desc">Employee-wise gross salary, earnings, and deductions breakdown</p>
        </a>
    </div>

    {{-- Module 10 (FSD 14.1-14.7) — the ~80 additional named reports, grouped by
         FSD subsection and collapsed by default so this page doesn't become a
         wall of links. Each entry links either to the generic report engine
         (reports.view) or straight at an existing bespoke report (aliasRoute). --}}
    <h5 class="mt-4 mb-3">All Reports</h5>
    <div class="accordion" id="allReportsAccordion">
        @foreach(\App\Support\Reports\ReportRegistry::sections() as $sectionKey => $sectionLabel)
            @php $sectionReports = \App\Support\Reports\ReportRegistry::bySection($sectionKey); @endphp
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section-{{ $sectionKey }}">
                        {{ $sectionLabel }} <span class="badge bg-secondary-subtle text-secondary ms-2">{{ count($sectionReports) }}</span>
                    </button>
                </h2>
                <div id="section-{{ $sectionKey }}" class="accordion-collapse collapse" data-bs-parent="#allReportsAccordion">
                    <div class="accordion-body">
                        <div class="list-group">
                            @foreach($sectionReports as $def)
                                <a href="{{ $def->isAlias() ? route($def->aliasRoute, $def->aliasParams) : route('reports.view', $def->key) }}" class="list-group-item list-group-item-action">
                                    <div class="fw-semibold">{{ $def->title }}</div>
                                    <small class="text-muted">{{ $def->description }}</small>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
