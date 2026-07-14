@extends('layouts.app')
@section('title', 'Employee Report')
@section('page-title', 'Employee Report')
@section('page-subtitle', 'Complete employee directory and statistics')

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

        .stat-orange {
            background: linear-gradient(135deg, #92400e 0%, #d97706 45%, #f59e0b 100%);
            box-shadow: 0 8px 28px rgba(217, 119, 6, .35);
        }

        .stat-orange:hover {
            box-shadow: 0 16px 40px rgba(217, 119, 6, .5);
        }

        .stat-purple {
            background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 45%, #a855f7 100%);
            box-shadow: 0 8px 28px rgba(124, 58, 237, .35);
        }

        .stat-purple:hover {
            box-shadow: 0 16px 40px rgba(124, 58, 237, .5);
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
                    <label class="form-label">Designation</label>
                    <select name="designation_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($designations as $desig)
                            <option value="{{ $desig->id }}"
                                {{ request('designation_id') == $desig->id ? 'selected' : '' }}>{{ $desig->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated
                        </option>
                        <option value="probation" {{ request('status') == 'probation' ? 'selected' : '' }}>Probation</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Employment Type</label>
                    <select name="employee_type_id" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach ($employeeTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ request('employee_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter"></i> Filter</button>
                    <a href="{{ route('reports.employees', array_merge(request()->query(), ['export' => 1])) }}" class="btn btn-sm btn-outline-success"><i
                            class="bi bi-file-earmark-excel"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="stat-grid">
        <div class="stat-card stat-blue">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="stat-label">Total Employees</div>
                    <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    <div class="stat-sub">All employees</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-green">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-person-check-fill"></i></div>
                <div>
                    <div class="stat-label">Active</div>
                    <div class="stat-value">{{ $stats['active'] ?? 0 }}</div>
                    <div class="stat-sub">Currently working</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-orange">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-calendar-x-fill"></i></div>
                <div>
                    <div class="stat-label">On Leave Today</div>
                    <div class="stat-value">{{ $stats['on_leave'] ?? 0 }}</div>
                    <div class="stat-sub">{{ now()->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="stat-card stat-purple">
            <div class="stat-inner">
                <div class="stat-icon-wrap"><i class="bi bi-person-plus-fill"></i></div>
                <div>
                    <div class="stat-label">Joined This Month</div>
                    <div class="stat-value">{{ $stats['new_this_month'] ?? 0 }}</div>
                    <div class="stat-sub">{{ now()->format('F Y') }}</div>
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
                            <th>Code</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Type</th>
                            <th>Joining Date</th>
                            <th>Basic Salary</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            @php
                                $statusColors = [
                                    'active' => 'success',
                                    'inactive' => 'danger',
                                    'terminated' => 'dark',
                                    'probation' => 'warning',
                                    'on_leave' => 'info',
                                ];
                                $sc = $statusColors[$emp->status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td>{{ $emp->employee_code }}</td>
                                <td>{{ $emp->full_name ?? $emp->name }}</td>
                                <td>{{ $emp->department->name ?? '—' }}</td>
                                <td>{{ $emp->designation->name ?? '—' }}</td>
                                <td>{{ $emp->employeeType->name ?? '—' }}</td>
                                <td>{{ $emp->date_of_joining ? \Carbon\Carbon::parse($emp->date_of_joining)->format('d M Y') : '—' }}
                                </td>
                                <td>₹{{ number_format($emp->currentSalary->basic_salary ?? 0, 0) }}</td>
                                <td><span
                                        class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst(str_replace('_', ' ', $emp->status)) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No employees match the selected
                                    filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-2">{{ $employees->withQueryString()->links() }}</div>
@endsection
