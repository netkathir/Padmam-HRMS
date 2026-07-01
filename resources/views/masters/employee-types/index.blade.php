@extends('layouts.app')
@section('title','Employee Types')
@section('page-title','Employee Types')
@section('page-subtitle','Manage employee categories')
@section('page-actions')
    <a href="{{ route('masters.employee-types.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Type</a>
    <a href="{{ route('masters.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Masters</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, code…" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('masters.employee-types.index') }}" class="btn btn-sm btn-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Code</th><th>Name</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($employeeTypes as $et)
                    <tr>
                        <td>{{ $employeeTypes->firstItem() + $loop->index }}</td>
                        <td><span class="badge bg-secondary">{{ $et->code }}</span></td>
                        <td>{{ $et->name }}</td>
                        <td>{{ $et->description ?? '—' }}</td>
                        <td><span class="badge bg-{{ $et->is_active ? 'success' : 'danger' }}-subtle text-{{ $et->is_active ? 'success' : 'danger' }}">{{ $et->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.employee-types.edit', $et) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.employee-types.destroy', $et) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this employee type?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No employee types found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $employeeTypes->links() }}</div>
    </div>
</div>
@endsection
