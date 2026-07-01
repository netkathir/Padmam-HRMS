@extends('layouts.app')
@section('title','Leave Balance')
@section('page-title','Leave Balance')
@section('page-subtitle','Available leave quota for ' . now()->year)
@section('content')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                    @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Employee</label>
                <input type="text" name="search" class="form-control" placeholder="Name or code…" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        @foreach($leaveTypes as $lt)
                        <th class="text-center" title="{{ $lt->name }}">{{ Str::limit($lt->name, 10) }}</th>
                        @endforeach
                        <th class="text-center">Total Used</th>
                        <th class="text-center">Total Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balances as $row)
                    <tr>
                        <td>
                            <strong>{{ $row['employee']->employee_code }}</strong><br>
                            <small class="text-muted">{{ $row['employee']->full_name ?? '—' }}</small>
                        </td>
                        <td><small>{{ $row['employee']->department->name ?? '—' }}</small></td>
                        @foreach($leaveTypes as $lt)
                        @php $bal = $row['balance'][$lt->id] ?? ['entitled'=>0,'taken'=>0,'remaining'=>0]; @endphp
                        <td class="text-center">
                            <span class="badge bg-{{ $bal['remaining'] > 0 ? 'success' : 'danger' }}-subtle text-{{ $bal['remaining'] > 0 ? 'success' : 'danger' }}">
                                {{ $bal['remaining'] }}/{{ $bal['entitled'] }}
                            </span>
                        </td>
                        @endforeach
                        <td class="text-center fw-bold text-danger">{{ $row['total_taken'] }}</td>
                        <td class="text-center fw-bold text-success">{{ $row['total_remaining'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ 4 + $leaveTypes->count() }}" class="text-center text-muted py-4">No data found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
