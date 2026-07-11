@extends('layouts.app')

@section('title', 'Branches')
@section('page-title', 'Branches')
@section('page-subtitle', 'Manage company branches (Super Admin only)')

@section('page-actions')
    <a href="{{ route('masters.branches.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Add Branch
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Search by name, code, city, district..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Search</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('masters.branches.index') }}" class="btn btn-sm btn-secondary w-100"><i
                            class="bi bi-x-circle"></i> Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>City / District</th>
                            <th>State</th>
                            <th>Branch Head</th>
                            <th>Start Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                            <tr>
                                <td>{{ $branches->firstItem() + $loop->index }}</td>
                                <td><span class="badge bg-secondary">{{ $branch->code }}</span></td>
                                <td>{{ $branch->name }}</td>
                                <td>{{ $branch->city ?? '—' }}{{ $branch->district ? ' / ' . $branch->district : '' }}</td>
                                <td>{{ $branch->state ?? '—' }}</td>
                                <td>{{ $branch->branchHead->name ?? '—' }}</td>
                                <td>{{ $branch->start_date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    <span
                                        class="badge bg-{{ $branch->is_active ? 'success' : 'danger' }}-subtle text-{{ $branch->is_active ? 'success' : 'danger' }}">
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('masters.branches.edit', $branch) }}"
                                        class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if ($branch->is_active)
                                        <form action="{{ route('masters.branches.deactivate', $branch) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Deactivate this branch? Historical data stays available; new transactions will be blocked.')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Deactivate"><i
                                                    class="bi bi-slash-circle"></i></button>
                                        </form>
                                    @else
                                        <form action="{{ route('masters.branches.activate', $branch) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Activate"><i
                                                    class="bi bi-check-circle"></i></button>
                                        </form>
                                    @endif
                                    <form action="{{ route('masters.branches.destroy', $branch) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Delete this branch?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i
                                                class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No branches found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                {{ $branches->links() }}
            </div>
        </div>
    </div>
@endsection
