@extends('layouts.app')
@section('title','Checkpoints')
@section('page-title','Checkpoints')
@section('page-subtitle','Manage biometric attendance checkpoints')
@section('page-actions')
    <a href="{{ route('masters.checkpoints.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Checkpoint</a>
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
                <a href="{{ route('masters.checkpoints.index') }}" class="btn btn-sm btn-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Code</th><th>Name</th><th>Branch</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($checkpoints as $checkpoint)
                    <tr>
                        <td>{{ $checkpoints->firstItem() + $loop->index }}</td>
                        <td><span class="badge bg-secondary">{{ $checkpoint->code }}</span></td>
                        <td>{{ $checkpoint->name }}</td>
                        <td>{{ $checkpoint->branch->name ?? '—' }}</td>
                        <td>{{ $checkpoint->description ?? '—' }}</td>
                        <td><span class="badge bg-{{ $checkpoint->is_active ? 'success' : 'danger' }}-subtle text-{{ $checkpoint->is_active ? 'success' : 'danger' }}">{{ $checkpoint->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.generic.show', ['module' => 'checkpoints', 'id' => $checkpoint->id]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('masters.checkpoints.edit', $checkpoint) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.checkpoints.destroy', $checkpoint) }}" method="POST" class="d-inline" data-confirm-delete="Delete this checkpoint?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No checkpoints found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $checkpoints->links() }}</div>
    </div>
</div>
@endsection
