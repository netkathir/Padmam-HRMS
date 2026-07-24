@extends('layouts.app')
@section('title','Earnings Components')
@section('page-title','Earnings Components')
@section('page-subtitle','Manage salary earning heads')
@section('page-actions')
    <a href="{{ route('masters.earnings.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Component</a>
    <a href="{{ route('masters.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Masters</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, code…" value="{{ request('search') }}">
            </div>
            @if ($branches->isNotEmpty())
            <div class="col-md-3">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('masters.earnings.index') }}" class="btn btn-sm btn-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Code</th><th>Name</th><th>Branch</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($components as $comp)
                    <tr>
                        <td><span class="badge bg-success-subtle text-success">{{ $comp->code }}</span></td>
                        <td>{{ $comp->name }}</td>
                        <td>{{ $comp->branch->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $comp->is_active ? 'success' : 'danger' }}-subtle text-{{ $comp->is_active ? 'success' : 'danger' }}">{{ $comp->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.generic.show', ['module' => 'earnings', 'id' => $comp->id]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('masters.earnings.edit', $comp) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.earnings.destroy', $comp) }}" method="POST" class="d-inline" data-confirm-delete="Delete this component?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No earning components found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $components->links() }}</div>
    </div>
</div>
@endsection
