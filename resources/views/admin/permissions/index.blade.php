{{--
    File: resources/views/admin/permissions/index.blade.php
    Purpose: Permissions listing — one row per module, showing which access
             levels are defined; "Manage" opens the consolidated edit screen
             for that module (all levels edited together, no per-record list)
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
    <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <label class="form-label mb-0" style="font-size:12px;font-weight:600;">Jump to module:</label>
            <select id="jumpToModule" class="form-select form-select-sm" style="min-width:180px;">
                <option value="">Select a module…</option>
                @foreach($modules as $mod)
                <option value="{{ route('admin.permissions.module.edit', $mod) }}">
                    {{ config("menu_modules.$mod.label", ucfirst(str_replace('_', ' ', $mod))) }}
                </option>
                @endforeach
            </select>
        </div>
        <span class="text-muted" style="font-size:13px;">{{ $summary->count() }} modules</span>
    </div>

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
                <a href="{{ route('admin.permissions.module.edit', $row['module']) }}"
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
</div>
@endsection

@push('scripts')
<script>
document.getElementById('jumpToModule').addEventListener('change', function () {
    if (this.value) {
        window.location.href = this.value;
    }
});
</script>
@endpush
