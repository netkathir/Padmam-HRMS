@extends('layouts.app')
@section('title','Holidays')
@section('page-title','Holidays')
@section('page-subtitle','Manage public and optional holidays')
@section('page-actions')
    <a href="{{ route('masters.holidays.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Holiday</a>
    <a href="{{ route('masters.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Masters</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-2">
                <select name="year" class="form-select form-select-sm">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="national" {{ request('type') == 'national' ? 'selected' : '' }}>National</option>
                    <option value="regional" {{ request('type') == 'regional' ? 'selected' : '' }}>Regional</option>
                    <option value="optional" {{ request('type') == 'optional' ? 'selected' : '' }}>Optional</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Date</th><th>Holiday</th><th>Type</th><th>Branch</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($holidays as $holiday)
                    <tr>
                        <td>{{ $holidays->firstItem() + $loop->index }}</td>
                        <td>{{ $holiday->date->format('d M Y') }} <small class="text-muted">({{ $holiday->date->format('l') }})</small></td>
                        <td>{{ $holiday->name }}</td>
                        <td><span class="badge bg-{{ $holiday->type == 'national' ? 'primary' : ($holiday->type == 'regional' ? 'info' : 'warning') }}-subtle text-{{ $holiday->type == 'national' ? 'primary' : ($holiday->type == 'regional' ? 'info' : 'warning') }}">{{ ucfirst($holiday->type) }}</span></td>
                        <td>{{ $holiday->branch->name ?? 'All Branches' }}</td>
                        <td><span class="badge bg-{{ $holiday->is_active ? 'success' : 'danger' }}-subtle text-{{ $holiday->is_active ? 'success' : 'danger' }}">{{ $holiday->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.holidays.edit', $holiday) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.holidays.destroy', $holiday) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this holiday?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No holidays found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $holidays->links() }}</div>
    </div>
</div>
@endsection
