{{--
    File: resources/views/admin/roles/index.blade.php
    Purpose: Roles listing — System Admin module
    Author: System
    Date: 2026-06-30
--}}
@extends('layouts.app')

@section('title', 'Roles')
@section('page-title', 'Roles')
@section('page-subtitle', 'Manage access roles for system users')

@section('page-actions')
    <a href="{{ route('admin.roles.create') }}"
       class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
        <i class="bi bi-plus-lg"></i> Create Role
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <span class="fw-semibold" style="font-size:14px;">
            <i class="bi bi-shield-check me-2 text-primary"></i>All Roles
        </span>
        <span class="badge bg-secondary-subtle text-secondary">{{ $roles->total() }} total</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>System Name</th>
                        <th>Display Name</th>
                        <th>Description</th>
                        <th class="text-center">Users</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                    <tr>
                        <td class="text-muted ps-3">{{ $role->id }}</td>
                        <td><code class="text-primary fw-medium">{{ $role->name }}</code></td>
                        <td class="fw-medium">{{ $role->display_name }}</td>
                        <td class="text-muted"><small>{{ $role->description ?? '—' }}</small></td>
                        <td class="text-center">
                            <span class="badge bg-info-subtle text-info">{{ $role->users_count }}</span>
                        </td>
                        <td class="text-center">
                            @if($role->is_active)
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.roles.edit', $role) }}"
                                   class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST"
                                      onsubmit="return confirm('Delete role \'{{ $role->display_name }}\'?')">
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
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-shield-x d-block mb-2" style="font-size:32px;opacity:.3;"></i>
                            No roles found. <a href="{{ route('admin.roles.create') }}">Create the first role</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($roles->total() > 0)
    <div class="card-footer bg-white border-top px-3 py-2">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <p class="mb-0 text-muted" style="font-size:13px;">
                Showing <strong>{{ $roles->firstItem() }}</strong>–<strong>{{ $roles->lastItem() }}</strong>
                of <strong>{{ $roles->total() }}</strong> roles
            </p>
            <div>{{ $roles->links() }}</div>
        </div>
    </div>
    @endif
</div>
@endsection
