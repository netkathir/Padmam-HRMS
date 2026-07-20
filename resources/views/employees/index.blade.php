@extends('layouts.app')

@section('title', 'Create Employee')
@section('page-title', 'Create Employee')
@section('page-subtitle', 'Manage all employees')

@section('page-actions')
    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Employee
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Search by name, code, email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}"
                                {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($branches->isNotEmpty())
                <div class="col-md-2">
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="probation" {{ request('status') == 'probation' ? 'selected' : '' }}>Probation
                        </option>
                        <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Search</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Category / Type</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            <tr>
                                <td>{{ $employees->firstItem() + $loop->index }}</td>
                                <td><span class="badge bg-secondary">{{ $emp->employee_code }}</span></td>
                                <td>
                                    <a href="{{ route('employees.show', $emp) }}" class="text-decoration-none fw-semibold">
                                        {{ $emp->full_name }}
                                    </a>
                                </td>
                                <td>{{ $emp->department->name ?? '—' }}</td>
                                <td>{{ $emp->designation->name ?? '—' }}</td>
                                <td>
                                    @if ($emp->designation_employee_category)
                                        {{ ucfirst($emp->designation_employee_category) }} · {{ $emp->designation_employee_type_label ?? '—' }}
                                        @if ($emp->designationContractor)
                                            <div class="text-muted small">{{ $emp->designationContractor->name }}</div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $emp->branch->name ?? '—' }}</td>
                                <td>{{ $emp->employeeType->name ?? '—' }}</td>
                                <td>
                                    @php $colors = ['active'=>'success', 'inactive'=>'danger', 'probation'=>'warning', 'terminated'=>'secondary']; @endphp
                                    <span
                                        class="badge bg-{{ $colors[$emp->status] ?? 'secondary' }}-subtle text-{{ $colors[$emp->status] ?? 'secondary' }}">
                                        {{ ucfirst($emp->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('employees.show', $emp) }}" class="btn btn-sm btn-outline-info"
                                        title="View"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('employees.edit', $emp) }}" class="btn btn-sm btn-outline-primary"
                                        title="Edit"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No employees found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">{{ $employees->links() }}</div>
        </div>
    </div>
@endsection
