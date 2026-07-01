{{--
    File: resources/views/admin/role-permissions/assign.blade.php
    Purpose: Assign module-level permissions to a role (4-level grid layout)
    Author: System
    Date: 2026-06-30
--}}
@extends('layouts.app')

@section('title', 'Assign Permissions — ' . $role->display_name)
@section('page-title', 'Assign Permissions')
@section('page-subtitle', 'Role: ' . $role->display_name . ' (' . $role->name . ')')

@section('page-actions')
    <x-back-button href="{{ route('admin.role-permissions.index') }}" />
@endsection

@push('styles')
<style>
/* ── Permission grid table ── */
.perm-grid { font-size: 13.5px; }
.perm-grid th {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: #6b7280;
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    padding: 10px 16px;
    vertical-align: middle;
    white-space: nowrap;
}
.perm-grid td {
    padding: 10px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}
.perm-grid tbody tr:last-child td { border-bottom: none; }
.perm-grid tbody tr:hover { background: #f8faff; }

/* Module name */
.pm-label  { font-weight: 600; color: #111827; font-size: 13.5px; }
.pm-slug   { font-size: 11px; color: #9ca3af; margin-top: 2px; }

/* Level header badges */
.lvl-read   { background: #dbeafe; color: #1d4ed8; }
.lvl-create { background: #d1fae5; color: #065f46; }
.lvl-full   { background: #ede9fe; color: #6d28d9; }
.lvl-delete { background: #fee2e2; color: #991b1b; }
.lvl-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .05em;
}

/* Checkboxes */
.perm-check  { width: 17px; height: 17px; cursor: pointer; accent-color: #4f7ef8; }
.row-all-chk { width: 16px; height: 16px; cursor: pointer; accent-color: #6b7280; }
.col-all-chk { width: 16px; height: 16px; cursor: pointer; accent-color: #4f7ef8; }

/* Sticky header */
.perm-grid thead { position: sticky; top: 0; z-index: 5; }

/* Grant-all column row */
.col-all-row { background: #fafbfc; border-bottom: 1px solid #e9ecef; }
.col-all-row td { padding: 8px 16px; }
</style>
@endpush

@section('content')
<form action="{{ route('admin.role-permissions.update', $role) }}" method="POST">
@csrf

<div class="card">
    <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-lock text-primary" style="font-size:18px;"></i>
            <div>
                <div class="fw-semibold" style="font-size:14px;">{{ $role->display_name }}</div>
                <div class="text-muted" style="font-size:12px;">Select access levels for each module</div>
            </div>
            <span class="badge bg-primary-subtle text-primary ms-1">{{ count($assigned) }} assigned</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAll(true)">
                <i class="bi bi-check-all"></i> Grant All
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll(false)">
                <i class="bi bi-x-circle"></i> Clear All
            </button>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-save"></i> Save Changes
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        @if($grouped->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-key d-block mb-2" style="font-size:32px;opacity:.3;"></i>
            No permissions defined.
            <a href="{{ route('admin.permissions.create') }}">Add permissions first</a>.
        </div>
        @else
        <div class="table-responsive">
            <table class="table perm-grid mb-0">
                <thead>
                    {{-- Column headers ── --}}
                    <tr>
                        <th style="min-width:200px;">Module</th>
                        <th class="text-center" style="width:115px;">
                            <span class="lvl-badge lvl-read">READ</span>
                        </th>
                        <th class="text-center" style="width:115px;">
                            <span class="lvl-badge lvl-create">CREATE</span>
                        </th>
                        <th class="text-center" style="width:120px;">
                            <span class="lvl-badge lvl-full">FULL ACCESS</span>
                        </th>
                        <th class="text-center" style="width:115px;">
                            <span class="lvl-badge lvl-delete">DELETE</span>
                        </th>
                        <th class="text-center" style="width:85px;">
                            <span style="font-size:10px;color:#9ca3af;font-weight:700;letter-spacing:.05em;text-transform:uppercase;">All</span>
                        </th>
                    </tr>

                    {{-- Grant-all-per-column row ── --}}
                    <tr class="col-all-row">
                        <td class="text-muted ps-4"
                            style="font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">
                            Toggle entire column →
                        </td>
                        @foreach(['read','create','full','delete'] as $lvl)
                        <td class="text-center">
                            <input type="checkbox"
                                   class="col-all-chk js-col-all"
                                   data-level="{{ $lvl }}"
                                   onchange="toggleColumn('{{ $lvl }}', this.checked)"
                                   title="Toggle all {{ strtoupper($lvl) }}">
                        </td>
                        @endforeach
                        <td></td>
                    </tr>
                </thead>

                <tbody>
                    @php
                    $moduleLabels = [
                        'dashboard'        => ['Dashboard',        'bi-speedometer2'],
                        'employees'        => ['Employees',        'bi-person-vcard'],
                        'attendance'       => ['Attendance',       'bi-calendar-check'],
                        'leaves'           => ['Leaves',           'bi-calendar-x'],
                        'payroll'          => ['Payroll',          'bi-cash-stack'],
                        'reports'          => ['Reports',          'bi-bar-chart-line'],
                        'users'            => ['Users',            'bi-people'],
                        'roles'            => ['Roles',            'bi-shield-check'],
                        'permissions'      => ['Permissions',      'bi-key'],
                        'role_permissions' => ['Role Permissions', 'bi-person-lock'],
                        'settings'         => ['Settings',         'bi-sliders'],
                        'masters'          => ['Masters',          'bi-collection'],
                    ];
                    @endphp

                    @foreach($grouped as $moduleName => $perms)
                    @php
                        [$mlabel, $micon] = $moduleLabels[$moduleName] ?? [ucfirst(str_replace('_', ' ', $moduleName)), 'bi-circle'];
                        $levelMap   = $perms->keyBy('access_level');
                        $rowIdx     = $loop->index;
                        $allChecked = $perms->every(fn($p) => in_array($p->id, $assigned));
                    @endphp
                    <tr>
                        {{-- Module name ── --}}
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi {{ $micon }} text-primary" style="font-size:15px;opacity:.7;"></i>
                                <div>
                                    <div class="pm-label">{{ $mlabel }}</div>
                                    <div class="pm-slug">{{ $moduleName }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- 4 level checkboxes ── --}}
                        @foreach(['read','create','full','delete'] as $lvl)
                        @php $perm = $levelMap->get($lvl); @endphp
                        <td class="text-center">
                            @if($perm)
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $perm->id }}"
                                   class="perm-check js-perm js-row-{{ $rowIdx }} js-col-{{ $lvl }}"
                                   {{ in_array($perm->id, $assigned) ? 'checked' : '' }}>
                            @else
                            <span class="text-muted" style="font-size:12px;" title="Not defined">—</span>
                            @endif
                        </td>
                        @endforeach

                        {{-- Row "all 4 levels" toggle ── --}}
                        <td class="text-center">
                            <input type="checkbox"
                                   class="row-all-chk js-row-all"
                                   data-row="{{ $rowIdx }}"
                                   onchange="toggleRow({{ $rowIdx }}, this.checked)"
                                   title="Toggle all levels for {{ $mlabel }}"
                                   {{ $allChecked ? 'checked' : '' }}>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center px-4 py-3">
        <p class="mb-0 text-muted" style="font-size:12.5px;">
            <i class="bi bi-info-circle me-1 text-primary"></i>
            Higher levels include all lower ones:
            <strong>DELETE</strong> &rsaquo; Full Access &rsaquo; Create &rsaquo; <strong>READ</strong>
        </p>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.role-permissions.index') }}" class="btn btn-light btn-sm">Cancel</a>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-save me-1"></i> Save Permissions
            </button>
        </div>
    </div>
</div>

</form>
@endsection

@push('scripts')
<script>
function toggleAll(state) {
    document.querySelectorAll('.perm-check').forEach(cb => cb.checked = state);
    document.querySelectorAll('.row-all-chk').forEach(cb => cb.checked = state);
    document.querySelectorAll('.col-all-chk').forEach(cb => cb.checked = state);
}

function toggleRow(rowIdx, state) {
    document.querySelectorAll(`.js-row-${rowIdx}`).forEach(cb => cb.checked = state);
}

function toggleColumn(level, state) {
    document.querySelectorAll(`.js-col-${level}`).forEach(cb => cb.checked = state);
    // sync column-all header checkbox
    const colAll = document.querySelector(`.js-col-all[data-level="${level}"]`);
    if (colAll) colAll.checked = state;
}

// Keep row-all checkboxes in sync when individual cells are toggled
document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('perm-check')) return;

    const match   = [...e.target.classList].find(c => c.startsWith('js-row-'));
    if (!match) return;
    const rowIdx  = match.replace('js-row-', '');
    const rowBoxes = [...document.querySelectorAll(`.js-row-${rowIdx}`)];
    const rowAll   = document.querySelector(`.js-row-all[data-row="${rowIdx}"]`);
    if (rowAll) rowAll.checked = rowBoxes.every(c => c.checked);

    // Also sync column-all header
    const colMatch = [...e.target.classList].find(c => c.startsWith('js-col-') && !c.startsWith('js-col-all'));
    if (!colMatch) return;
    const level   = colMatch.replace('js-col-', '');
    const colBoxes = [...document.querySelectorAll(`.js-col-${level}.perm-check`)];
    const colAll   = document.querySelector(`.js-col-all[data-level="${level}"]`);
    if (colAll) colAll.checked = colBoxes.every(c => c.checked);
});
</script>
@endpush
