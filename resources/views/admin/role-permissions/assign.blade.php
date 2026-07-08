{{--
    File: resources/views/admin/role-permissions/assign.blade.php
    Purpose: Assign per-module access levels to a role — flat permission matrix
             (No Access / Read Only / Create / Delete / Full Access per module, radio behaviour)
    Author: System
    Date: 2026-07-08
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
.perm-list { display: flex; flex-direction: column; }

.perm-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 14px 24px;
    border-bottom: 1px solid #eef0f3;
}
.perm-row:last-child { border-bottom: none; }
.perm-row:hover { background: #fafbfc; }

.perm-module-label {
    flex: 0 0 180px;
    font-weight: 600;
    font-size: 14px;
    color: #1f2937;
}

.perm-options {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
}

.perm-opt { position: relative; display: block; }

.perm-opt-input {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
    pointer-events: none;
}

.perm-opt-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 42px;
    border-radius: 8px;
    border: 1px solid #dfe3e8;
    background: #fff;
    color: #4b5563;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
    user-select: none;
}
.perm-opt-btn i { font-size: 15px; }

.perm-opt-input:focus-visible + .perm-opt-btn {
    outline: 2px solid #4f7ef8;
    outline-offset: 1px;
}

.perm-opt-input:not(:checked) + .perm-opt-btn:hover {
    border-color: #b9c0cc;
    color: #1f2937;
}

/* Selected — "No Access" highlights red, every granted level highlights green */
.perm-opt-input:checked + .perm-opt-btn.opt-none {
    background: #e53e3e;
    border-color: #e53e3e;
    color: #fff;
}
.perm-opt-input:checked + .perm-opt-btn:not(.opt-none) {
    background: #22a55a;
    border-color: #22a55a;
    color: #fff;
}

@media (max-width: 991.98px) {
    .perm-options { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 767.98px) {
    .perm-row { flex-direction: column; align-items: stretch; }
    .perm-module-label { flex-basis: auto; }
    .perm-options { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
@endpush

@php
    // Column order requested for the matrix: No Access, Read Only, Create, Delete,
    // Full Access. Note this is a display-order choice only — the actual access
    // hierarchy enforced by the Gate (read < create < full < delete) is unchanged.
    $visibleLevels = [
        'read'   => ['label' => 'Read Only',   'icon' => 'bi-eye'],
        'create' => ['label' => 'Create',      'icon' => 'bi-pencil-square'],
        'delete' => ['label' => 'Delete',      'icon' => 'bi-trash'],
        'full'   => ['label' => 'Full Access', 'icon' => 'bi-gear'],
    ];

    $rows = $grouped->map(function ($perms, $moduleName) use ($assigned, $menuModules) {
        return [
            'label' => $menuModules[$moduleName]['label'] ?? ucfirst(str_replace('_', ' ', $moduleName)),
            'levelMap' => $perms->keyBy('access_level'),
            'selectedId' => $assigned[$moduleName] ?? null,
        ];
    });

    $visibleAssignedCount = $rows->filter(fn ($row) => !is_null($row['selectedId']))->count();
@endphp

@section('content')
<form action="{{ route('admin.role-permissions.update', $role) }}" method="POST">
@csrf

<div class="card">
    <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-lock text-primary" style="font-size:18px;"></i>
            <div>
                <div class="fw-semibold" style="font-size:14px;">{{ $role->display_name }}</div>
                <div class="text-muted" style="font-size:12px;">Select one access level per module</div>
            </div>
            <span class="badge bg-primary-subtle text-primary ms-1" id="assignedCountBadge">
                {{ $visibleAssignedCount }} assigned
            </span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectLevelForAll('delete')">
                <i class="bi bi-check-all"></i> Grant All
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectLevelForAll('')">
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
        <div class="perm-list">
            @foreach($rows as $moduleName => $row)
            <div class="perm-row">
                <div class="perm-module-label">{{ $row['label'] }}</div>
                <div class="perm-options">
                    <label class="perm-opt">
                        <input type="radio"
                               name="permissions[{{ $moduleName }}]"
                               value=""
                               data-level=""
                               class="perm-opt-input"
                               {{ is_null($row['selectedId']) ? 'checked' : '' }}>
                        <span class="perm-opt-btn opt-none">
                            <i class="bi bi-lock"></i> No Access
                        </span>
                    </label>

                    @foreach($visibleLevels as $level => $levelMeta)
                    @php $perm = $row['levelMap']->get($level); @endphp
                    <label class="perm-opt">
                        <input type="radio"
                               name="permissions[{{ $moduleName }}]"
                               value="{{ $perm->id ?? '' }}"
                               data-level="{{ $level }}"
                               class="perm-opt-input"
                               {{ !$perm ? 'disabled' : '' }}
                               {{ $perm && $row['selectedId'] === $perm->id ? 'checked' : '' }}>
                        <span class="perm-opt-btn opt-{{ $level }}" title="{{ $perm ? '' : 'Not defined' }}">
                            <i class="bi {{ $levelMeta['icon'] }}"></i> {{ $levelMeta['label'] }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center px-4 py-3 flex-wrap gap-2">
        <p class="mb-0 text-muted" style="font-size:12.5px;">
            <i class="bi bi-info-circle me-1 text-primary"></i>
            Higher levels include all lower ones:
            <strong>Delete</strong> &rsaquo; Full Access &rsaquo; Create &rsaquo; <strong>Read Only</strong>
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
function selectLevelForAll(level) {
    document.querySelectorAll(`.perm-opt-input[data-level="${level}"]`).forEach(input => {
        if (!input.disabled) {
            input.checked = true;
        }
    });
}
</script>
@endpush
