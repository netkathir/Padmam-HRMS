@extends('layouts.app')
@section('title','Rule Engine')
@section('page-title','Rule Engine')
@section('page-subtitle','Employee numbering, attendance, weekly off, LOP, PF, ESI, TDS & overtime rules')
@section('page-actions')
    <a href="{{ route('rule-engine.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Rule</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ', $cat)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search rule name…" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('rule-engine.index') }}" class="btn btn-sm btn-secondary w-100"><i class="bi bi-x-circle"></i> Reset</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Rule Name</th><th>Category</th><th>Applicability</th><th>Priority</th><th>Effective</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($rules as $rule)
                    <tr>
                        <td>{{ $rules->firstItem() + $loop->index }}</td>
                        <td>{{ $rule->name }}</td>
                        <td><span class="badge bg-info-subtle text-info">{{ ucwords(str_replace('_',' ', $rule->category)) }}</span></td>
                        <td class="small text-muted">
                            {{ empty($rule->branch_ids) ? 'All Branches' : count($rule->branch_ids).' branch(es)' }},
                            {{ implode('/', $rule->employee_types ?? []) }}
                            @if(!empty($rule->contractor_ids)) &middot; {{ count($rule->contractor_ids) }} contractor(s) @endif
                        </td>
                        <td>{{ $rule->priority }}</td>
                        <td class="small">{{ $rule->effective_from->format('d M Y') }}@if($rule->effective_to) – {{ $rule->effective_to->format('d M Y') }}@endif</td>
                        <td><span class="badge bg-{{ $rule->status == 'active' ? 'success' : 'danger' }}-subtle text-{{ $rule->status == 'active' ? 'success' : 'danger' }}">{{ ucfirst($rule->status) }}</span></td>
                        <td>
                            <a href="{{ route('rule-engine.edit', $rule) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('rule-engine.destroy', $rule) }}" method="POST" class="d-inline" data-confirm-delete="Delete this rule?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No rules configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $rules->links() }}</div>
    </div>
</div>
@endsection
