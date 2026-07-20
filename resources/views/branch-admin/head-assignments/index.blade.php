@extends('layouts.app')

@section('title', 'Branch Head Assignment')
@section('page-title', 'Branch Head Assignment')
@section('page-subtitle', 'Branch Administration — assign a Branch Head to each branch (Super Admin only)')

@section('page-actions')
    <a href="{{ route('branch-admin.head-assignments.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> New Assignment
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('branch-admin.head-assignments.index') }}" class="btn btn-sm btn-secondary w-100">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Branch</th>
                            <th>Branch Head</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th>Status</th>
                            <th>Assigned By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr>
                                <td>{{ $assignments->firstItem() + $loop->index }}</td>
                                <td>{{ $assignment->branch->name ?? '—' }}</td>
                                <td>{{ $assignment->user->name ?? '—' }}</td>
                                <td>{{ $assignment->effective_from?->format('d M Y') }}</td>
                                <td>{{ $assignment->effective_to?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $assignment->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $assignment->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($assignment->status) }}
                                    </span>
                                </td>
                                <td>{{ $assignment->assignedBy->name ?? '—' }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('branch-admin.head-assignments.edit', $assignment) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if ($assignment->status === 'active')
                                        <form action="{{ route('branch-admin.head-assignments.deactivate', $assignment) }}" method="POST" class="d-inline"
                                            data-confirm-delete="Deactivate this Branch Head assignment?">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Deactivate"><i class="bi bi-slash-circle"></i></button>
                                        </form>
                                    @else
                                        <form action="{{ route('branch-admin.head-assignments.destroy', $assignment) }}" method="POST" class="d-inline"
                                            data-confirm-delete="Delete this assignment record?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No assignments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                {{ $assignments->links() }}
            </div>
        </div>
    </div>
@endsection
