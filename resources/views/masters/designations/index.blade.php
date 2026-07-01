@extends('layouts.app')
@section('title','Designations')
@section('page-title','Designations')
@section('page-subtitle','Manage job designations')
@section('page-actions')
    <a href="{{ route('masters.designations.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Designation</a>
    <a href="{{ route('masters.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Masters</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, code…" value="{{ request('search') }}">
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
                <a href="{{ route('masters.designations.index') }}" class="btn btn-sm btn-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Code</th><th>Name</th><th>Department</th><th>Grade</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($designations as $desig)
                    <tr>
                        <td>{{ $designations->firstItem() + $loop->index }}</td>
                        <td><span class="badge bg-secondary">{{ $desig->code ?? '—' }}</span></td>
                        <td>{{ $desig->name }}</td>
                        <td>{{ $desig->department->name ?? '—' }}</td>
                        <td>{{ $desig->grade ?? '—' }}</td>
                        <td><span class="badge bg-{{ $desig->is_active ? 'success' : 'danger' }}-subtle text-{{ $desig->is_active ? 'success' : 'danger' }}">{{ $desig->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.designations.edit', $desig) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.designations.destroy', $desig) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this designation?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No designations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $designations->links() }}</div>
    </div>
</div>
@endsection
