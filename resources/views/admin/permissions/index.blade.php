{{--
    File: resources/views/admin/permissions/index.blade.php
    Purpose: Permissions listing — System Admin module
    Author: System
    Date: 2026-06-30
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
                <span class="text-muted" style="font-size:13px;">{{ $permissions->total() }} permissions</span>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
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
                    @php $prevModule = null; @endphp
                    @forelse($permissions as $perm)
                    @if($perm->module !== $prevModule)
                    <tr>
                        <td colspan="6" class="py-1 px-3"
                            style="background:#f8f9fa;font-size:11px;font-weight:700;
                                   text-transform:uppercase;letter-spacing:.08em;color:#6c757d;">
                            {{ $perm->module }}
                        </td>
                    </tr>
                    @php $prevModule = $perm->module; @endphp
                    @endif
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
                            No permissions found. <a href="{{ route('admin.permissions.create') }}">Add the first one</a>.
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
</div>
@endsection
