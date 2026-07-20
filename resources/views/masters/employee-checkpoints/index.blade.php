@extends('layouts.app')
@section('title','Employee Checkpoint Mapping')
@section('page-title','Employee Checkpoint Mapping')
@section('page-subtitle','Map employees to their biometric checkpoint IDs')
@section('page-actions')
    <a href="{{ route('masters.employee-checkpoints.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Mapping</a>
    <a href="{{ route('masters.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Masters</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search employee, checkpoint ID…" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="checkpoint_id" class="form-select form-select-sm">
                    <option value="">All Checkpoints</option>
                    @foreach ($checkpoints as $cp)
                        <option value="{{ $cp->id }}" {{ request('checkpoint_id') == $cp->id ? 'selected' : '' }}>{{ $cp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('masters.employee-checkpoints.index') }}" class="btn btn-sm btn-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Checkpoint</th><th>Employee Checkpoint ID</th><th>Employee</th><th>Employee Code</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($mappings as $mapping)
                    <tr>
                        <td>{{ $mappings->firstItem() + $loop->index }}</td>
                        <td><span class="badge bg-secondary">{{ $mapping->checkpoint->name ?? '—' }}</span></td>
                        <td>{{ $mapping->emp_checkpoint_id }}</td>
                        <td>{{ $mapping->employee->full_name ?? '—' }}</td>
                        <td>{{ $mapping->employee->employee_code ?? '—' }}</td>
                        <td>
                            <a href="{{ route('masters.generic.show', ['module' => 'employee-checkpoints', 'id' => $mapping->id]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('masters.employee-checkpoints.edit', $mapping) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.employee-checkpoints.destroy', $mapping) }}" method="POST" class="d-inline" data-confirm-delete="Remove this employee checkpoint mapping?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No employee checkpoint mappings found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $mappings->links() }}</div>
    </div>
</div>
@endsection
