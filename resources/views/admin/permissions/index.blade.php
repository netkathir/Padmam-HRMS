{{--
    File: resources/views/admin/permissions/index.blade.php
    Purpose: Permissions listing — module-grouped summary, with a per-module
             detail drill-down for the underlying editable records
    Author: System
    Date: 2026-07-08
--}}
@extends('layouts.app')

@section('title', 'Permissions')
@section('page-title', 'Permissions')
@section('page-subtitle', 'Define module-level actions available in the system')

@section('page-actions')
    <a href="{{ route('admin.permissions.create') }}"
       class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
        <i class="bi bi-plus-lg"></i> Add Permission
    </a>
@endsection

@push('styles')
<style>
.perm-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 14px 24px;
    border-bottom: 1px solid #eef0f3;
}
.perm-summary-row:last-child { border-bottom: none; }
.perm-summary-row:hover { background: #fafbfc; }

.perm-summary-label { flex: 0 0 180px; font-weight: 600; font-size: 14px; color: #1f2937; }

.perm-summary-pills {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}

.perm-pill {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 40px;
    border-radius: 8px;
    border: 1px solid #dfe3e8;
    background: #fff;
    color: #9ca3af;
    font-size: 13px;
    font-weight: 600;
}
.perm-pill i { font-size: 14px; }
.perm-pill.is-defined {
    background: #eafaf1;
    border-color: #bdf0d3;
    color: #15803d;
}

.perm-summary-manage { flex: 0 0 auto; }

@media (max-width: 991.98px) {
    .perm-summary-pills { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 767.98px) {
    .perm-summary-row { flex-direction: column; align-items: stretch; }
    .perm-summary-label { flex-basis: auto; }
    .perm-summary-manage { align-self: flex-end; }
}
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header py-3">
        <form class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Filter by Module</label>
                <select name="module" class="form-select form-select-sm" onchange="this.form.submit()"
                        style="min-width:160px;">
                    <option value="">All Modules</option>
                    @foreach($modules as $mod)
                    <option value="{{ $mod }}" {{ request('module') === $mod ? 'selected' : '' }}>
                        {{ ucfirst($mod) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <span class="text-muted" style="font-size:13px;">
                    @if($mode === 'detail')
                        {{ $permissions->total() }} permissions in "{{ ucfirst(request('module')) }}"
                    @else
                        {{ $summary->count() }} modules
                    @endif
                </span>
            </div>
        </form>
    </div>

    @if($mode === 'summary')
    <div class="card-body p-0">
        @forelse($summary as $row)
        <div class="perm-summary-row">
            <div class="perm-summary-label">{{ $row['label'] }}</div>
            <div class="perm-summary-pills">
                @foreach(\App\Models\Permission::ACCESS_LEVELS as $level)
                @php $perm = $row['levelMap']->get($level); @endphp
                <span class="perm-pill {{ $perm ? 'is-defined' : '' }}"
                      title="{{ $perm ? $perm->name : 'Not defined' }}">
                    <i class="bi {{ ['read' => 'bi-eye', 'create' => 'bi-pencil-square', 'full' => 'bi-gear', 'delete' => 'bi-trash'][$level] }}"></i>
                    {{ ucfirst($level) }}
                </span>
                @endforeach
            </div>
            <div class="perm-summary-manage">
                <a href="{{ route('admin.permissions.index', ['module' => $row['module']]) }}"
                   class="btn btn-sm btn-outline-primary">
                    Manage <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-key d-block mb-2" style="font-size:32px;opacity:.3;"></i>
            No modules found.
        </div>
        @endforelse
    </div>
    @else
    <div class="card-body p-0">
        <div class="px-3 pt-3">
            <a href="{{ route('admin.permissions.index') }}" class="d-inline-flex align-items-center gap-1 text-decoration-none" style="font-size:13px;">
                <i class="bi bi-arrow-left"></i> All Modules
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Module</th>
                        <th>Access Level</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permissions as $perm)
                    <tr>
                        <td class="text-muted ps-3">{{ $perm->id }}</td>
                        <td><span class="badge bg-primary-subtle text-primary">{{ $perm->module }}</span></td>
                        <td>
                            @php
                                $lvlColors = ['read'=>'#1d4ed8','create'=>'#065f46','full'=>'#6d28d9','delete'=>'#991b1b'];
                                $lvlBgs    = ['read'=>'#dbeafe','create'=>'#d1fae5','full'=>'#ede9fe','delete'=>'#fee2e2'];
                                $lv = $perm->access_level;
                            @endphp
                            <span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;
                                         font-weight:700;background:{{ $lvlBgs[$lv] ?? '#f3f4f6' }};
                                         color:{{ $lvlColors[$lv] ?? '#374151' }};">
                                {{ strtoupper($lv) }}
                            </span>
                        </td>
                        <td class="fw-medium">{{ $perm->name }}</td>
                        <td class="text-muted"><small>{{ $perm->description ?? '—' }}</small></td>
                        <td class="text-end pe-3">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.permissions.edit', $perm) }}"
                                   class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.permissions.destroy', $perm) }}" method="POST"
                                      onsubmit="return confirm('Delete permission \'{{ $perm->name }}\'?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-key d-block mb-2" style="font-size:32px;opacity:.3;"></i>
                            No permissions found for this module. <a href="{{ route('admin.permissions.create') }}">Add one</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($permissions->total() > 0)
    <div class="card-footer bg-white border-top px-3 py-2">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <p class="mb-0 text-muted" style="font-size:13px;">
                Showing <strong>{{ $permissions->firstItem() }}</strong>–<strong>{{ $permissions->lastItem() }}</strong>
                of <strong>{{ $permissions->total() }}</strong> permissions
            </p>
            <div>{{ $permissions->links() }}</div>
        </div>
    </div>
    @endif
    @endif
</div>
@endsection
