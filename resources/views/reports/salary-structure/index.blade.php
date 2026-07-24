@extends('layouts.app')
@section('title','Salary Structure')
@section('page-title','Salary Structure')
@section('page-subtitle','Employee-wise gross salary, earnings, and deductions')
@section('page-actions')
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Reports</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search employee code / name…" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('reports.salary-structure.index') }}" class="btn btn-sm btn-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee Code</th>
                        <th>Biometric Code</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Gross Salary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td>{{ $employees->firstItem() + $loop->index }}</td>
                        <td>{{ $employee->employee_code ?? '—' }}</td>
                        <td>{{ $employee->branch->code ?? '' }}{{ $employee->biometric_number }}</td>
                        <td>{{ $employee->department->name ?? '—' }}</td>
                        <td>{{ $employee->designation->name ?? '—' }}</td>
                        <td>{{ $employee->currentSalary ? number_format($employee->currentSalary->basic_salary, 2) : '—' }}</td>
                        <td>
                            <a href="{{ route('reports.salary-structure.show', $employee) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> View</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $employees->links() }}</div>
    </div>
</div>
@endsection
