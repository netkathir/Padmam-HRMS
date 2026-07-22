@extends('layouts.app')

@section('title', 'Employee Document')
@section('page-title', 'Employee Document')
@section('page-subtitle', 'Upload and manage employee documents')

@section('page-actions')
    <a href="{{ route('employee-document.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Search by name or code..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Employees</option>
                        <option value="not_uploaded" {{ $notUploaded ? 'selected' : '' }}>Not Uploaded</option>
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
                            <th>Branch</th>
                            @unless($notUploaded)
                                <th>Documents</th>
                            @endunless
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            <tr>
                                <td>{{ $employees->firstItem() + $loop->index }}</td>
                                <td><span class="badge bg-secondary">{{ $emp->employee_code ?? '—' }}</span></td>
                                <td class="fw-semibold">{{ $emp->full_name }}</td>
                                <td>{{ $emp->department->name ?? '—' }}</td>
                                <td>{{ $emp->branch->name ?? '—' }}</td>
                                @unless($notUploaded)
                                    <td>{{ $emp->documents_count ?? $emp->documents()->count() }}</td>
                                @endunless
                                <td>
                                    @if($notUploaded)
                                        <a href="{{ route('employees.documents', $emp) }}" class="btn btn-sm btn-primary" title="Add"><i class="bi bi-plus-lg"></i> Add</a>
                                    @else
                                        <a href="{{ route('employee-document.show', $emp) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('employees.documents', $emp) }}" class="btn btn-sm btn-outline-primary" title="Manage"><i class="bi bi-pencil"></i></a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    {{ $notUploaded ? 'Every employee already has at least one document uploaded.' : 'No employees found.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">{{ $employees->links() }}</div>
        </div>
    </div>
@endsection
