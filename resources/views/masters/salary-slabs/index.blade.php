@extends('layouts.app')
@section('title','Salary Slabs')
@section('page-title','Salary Slabs')
@section('page-subtitle','Manage CTC salary bands')
@section('page-actions')
    <a href="{{ route('masters.salary-slabs.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Slab</a>
    <a href="{{ route('masters.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Masters</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search slab name…" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('masters.salary-slabs.index') }}" class="btn btn-sm btn-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Name</th><th>Min Salary (₹)</th><th>Max Salary (₹)</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($slabs as $slab)
                    <tr>
                        <td>{{ $slabs->firstItem() + $loop->index }}</td>
                        <td>{{ $slab->name }}</td>
                        <td>₹{{ number_format($slab->min_salary, 0) }}</td>
                        <td>₹{{ number_format($slab->max_salary, 0) }}</td>
                        <td><span class="badge bg-{{ $slab->is_active ? 'success' : 'danger' }}-subtle text-{{ $slab->is_active ? 'success' : 'danger' }}">{{ $slab->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.salary-slabs.edit', $slab) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.salary-slabs.destroy', $slab) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this salary slab?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No salary slabs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $slabs->links() }}</div>
    </div>
</div>
@endsection
