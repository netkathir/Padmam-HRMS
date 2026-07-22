@extends('layouts.app')

@section('title', 'Add Employee Document')
@section('page-title', 'Add Employee Document')
@section('page-subtitle', 'Search for an employee to upload documents for')
@section('back-url', route('employee-document.index'))

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-4">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" autofocus
                        placeholder="Search by name or employee code..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Search</button>
                </div>
            </form>

            @if (request()->filled('search'))
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Branch</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($employees as $emp)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $emp->employee_code ?? '—' }}</span></td>
                                    <td class="fw-semibold">{{ $emp->full_name }}</td>
                                    <td>{{ $emp->department->name ?? '—' }}</td>
                                    <td>{{ $emp->branch->name ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('employees.documents', $emp) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-upload"></i> Upload Documents
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No matching employees without documents were found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">Search for an employee above to begin uploading their documents.</p>
            @endif
        </div>
    </div>
@endsection
