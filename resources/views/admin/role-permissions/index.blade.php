{{--
    File: resources/views/admin/role-permissions/index.blade.php
    Purpose: List all roles with permission counts — assign role permissions
    Author: System
    Date: 2026-06-30
--}}
@extends('layouts.app')

@section('title', 'Role Permissions')
@section('page-title', 'Role Permissions')
@section('page-subtitle', 'Assign permissions to each role')

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center py-3">
        <i class="bi bi-person-lock me-2 text-primary"></i>
        <span class="fw-semibold" style="font-size:14px;">Roles — Permission Assignment</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Role</th>
                        <th>Display Name</th>
                        <th class="text-center">Permissions Assigned</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                    <tr>
                        <td class="ps-3 text-muted">{{ $role->id }}</td>
                        <td><code class="text-primary">{{ $role->name }}</code></td>
                        <td class="fw-medium">{{ $role->display_name }}</td>
                        <td class="text-center">
                            @if($role->permissions_count > 0)
                                <span class="badge bg-success-subtle text-success">
                                    {{ $role->permissions_count }} permissions
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">None assigned</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $role->is_active ? 'success' : 'danger' }}-subtle
                                         text-{{ $role->is_active ? 'success' : 'danger' }}">
                                {{ $role->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end pe-3">
                            <a href="{{ route('admin.role-permissions.assign', $role) }}"
                               class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                                <i class="bi bi-sliders"></i> Assign
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-person-lock d-block mb-2" style="font-size:32px;opacity:.3;"></i>
                            No roles found. <a href="{{ route('admin.roles.create') }}">Create a role first</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
