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
    </div>
@endsection
