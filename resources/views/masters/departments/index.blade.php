@extends('layouts.app')
@section('title','Departments')
@section('page-title','Departments')
@section('page-subtitle','Manage company departments')
@section('page-actions')
    <a href="{{ route('masters.departments.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Department</a>
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
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('masters.departments.index') }}" class="btn btn-sm btn-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Code</th><th>Name</th><th>Branch</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($departments as $dept)
                    <tr>
                        <td>{{ $departments->firstItem() + $loop->index }}</td>
                        <td><span class="badge bg-secondary">{{ $dept->code ?? '—' }}</span></td>
                        <td>{{ $dept->name }}</td>
                        <td>{{ $dept->branch->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $dept->is_active ? 'success' : 'danger' }}-subtle text-{{ $dept->is_active ? 'success' : 'danger' }}">{{ $dept->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.departments.edit', $dept) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.departments.destroy', $dept) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this department?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No departments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $departments->links() }}</div>
    </div>
</div>
@endsection
