@extends('layouts.app')
@section('title','Leave Types')
@section('page-title','Leave Types')
@section('page-subtitle','Manage leave categories')
@section('page-actions')
    <a href="{{ route('masters.leave-types.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Leave Type</a>
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
                <a href="{{ route('masters.leave-types.index') }}" class="btn btn-sm btn-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Code</th><th>Name</th><th>Branch</th><th>Days/Year</th><th>Employee Type Applicability</th><th>Paid</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @php $typeLabels = config('employee_types'); @endphp
                    @forelse($leaveTypes as $lt)
                    <tr>
                        <td>{{ $leaveTypes->firstItem() + $loop->index }}</td>
                        <td><span class="badge bg-secondary">{{ $lt->code }}</span></td>
                        <td>{{ $lt->name }}</td>
                        <td>{{ $lt->branch->name ?? '—' }}</td>
                        <td>{{ $lt->days_per_year }}</td>
                        <td>{{ collect($lt->applicable_employee_types ?? [])->map(fn($t) => $typeLabels[$t] ?? $t)->join(', ') ?: '—' }}</td>
                        <td class="text-center"><i class="bi bi-{{ $lt->is_paid ? 'check-circle-fill text-success' : 'x-circle text-danger' }}"></i></td>
                        <td><span class="badge bg-{{ $lt->is_active ? 'success' : 'danger' }}-subtle text-{{ $lt->is_active ? 'success' : 'danger' }}">{{ $lt->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.generic.show', ['module' => 'leave-types', 'id' => $lt->id]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('masters.leave-types.edit', $lt) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.leave-types.destroy', $lt) }}" method="POST" class="d-inline" data-confirm-delete="Delete this leave type?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No leave types found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $leaveTypes->links() }}</div>
    </div>
</div>
@endsection
