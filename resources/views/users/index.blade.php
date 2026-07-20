{{--
    File: resources/views/users/index.blade.php
    Purpose: User management listing with role/status badges and aligned actions
    Author: System
    Date: 2026-06-30
--}}
@extends('layouts.app')

@section('title', 'User Management')
@section('page-title', 'User Management')
@section('page-subtitle', 'System users and access control')

@section('page-actions')
    <a href="{{ route('users.create') }}"
       class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
        <i class="bi bi-person-plus"></i> Add User
    </a>
@endsection

@push('styles')
<style>
/* ── Table base ── */
.um-table { font-size: 13px; width: 100%; margin: 0; }

.um-table thead th {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #6b7280;
    background: #f8f9fa;
    border-top: none;
    border-bottom: 2px solid #e9ecef;
    padding: 10px 14px;
    white-space: nowrap;
    vertical-align: middle;
}

.um-table tbody td {
    padding: 10px 14px;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
    white-space: nowrap;
}

.um-table tbody tr:last-child td { border-bottom: none; }

.um-table tbody tr {
    transition: background .12s ease, box-shadow .12s ease;
}
.um-table tbody tr:hover {
    background: #f5f8ff;
    box-shadow: inset 3px 0 0 #4f7ef8;
}

/* ── Avatar initials ── */
.um-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f7ef8, #6c4ff8);
    display: inline-flex;
    align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: #fff;
    flex-shrink: 0;
}
.um-name { font-weight: 600; color: #111827; }

/* ── Role badge colours ── */
.um-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    letter-spacing: .02em;
}
.role-super_admin { background: #ede9fe; color: #5b21b6; }
.role-admin       { background: #e0e7ff; color: #3730a3; }
.role-hr          { background: #dbeafe; color: #1e40af; }
.role-manager     { background: #ccfbf1; color: #0f766e; }
.role-employee    { background: #f1f5f9; color: #475569; }
.role-default     { background: #f3f4f6; color: #6b7280; }

/* ── Status badge ── */
.status-active   { background: #dcfce7; color: #166534; }
.status-inactive { background: #fee2e2; color: #991b1b; }

/* ── Action buttons ── */
.um-actions { display: flex; align-items: center; gap: 4px; }
.um-btn {
    display: inline-flex;
    align-items: center; justify-content: center;
    width: 30px; height: 30px;
    border-radius: 7px;
    border: 1px solid transparent;
    font-size: 13px;
    cursor: pointer;
    transition: background .13s ease, transform .1s ease, border-color .13s ease;
    text-decoration: none;
    flex-shrink: 0;
    background: none;
    padding: 0;
    line-height: 1;
}
.um-btn:hover { transform: translateY(-1px); text-decoration: none; }

.um-btn-edit  { border-color: #bfdbfe; color: #2563eb; background: #eff6ff; }
.um-btn-edit:hover  { background: #dbeafe; color: #1d4ed8; }

.um-btn-perms { border-color: #a7f3d0; color: #059669; background: #ecfdf5; }
.um-btn-perms:hover { background: #d1fae5; color: #047857; }

.um-btn-del   { border-color: #fecaca; color: #dc2626; background: #fff5f5; }
.um-btn-del:hover   { background: #fee2e2; color: #b91c1c; }
</style>
@endpush

@section('content')
<div class="card">

    {{-- ── Search / Filter bar ──────────────────────────────────── --}}
    <div class="card-header py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4 col-sm-12">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search name, email, username…"
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-3 col-sm-6">
                <select name="role_id" class="form-select form-select-sm">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <select name="is_active" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            @if ($branches->isNotEmpty())
                <div class="col-md-2 col-sm-6">
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-auto d-flex gap-2">
                <button type="submit"
                        class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- ── Table ────────────────────────────────────────────────── --}}
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table um-table mb-0">
                <thead>
                    <tr>
                        <th style="width:46px;" class="ps-3">#</th>
                        <th style="min-width:190px;">Name</th>
                        <th style="min-width:120px;">Username</th>
                        <th style="min-width:195px;">Email</th>
                        <th style="min-width:120px;">Role</th>
                        <th style="min-width:150px;">Employee</th>
                        <th style="min-width:120px;">Branch</th>
                        <th style="min-width:120px;">Last Login</th>
                        <th class="text-center" style="min-width:95px;">Status</th>
                        <th class="text-end pe-3" style="min-width:160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    @php
                        $rn        = $user->role?->name ?? 'default';
                        $roleClass = 'role-' . (in_array($rn, ['super_admin','admin','hr','manager','employee'])
                                        ? $rn : 'default');
                        $roleLabel = ucfirst(str_replace('_', ' ', $rn === 'default' ? '—' : $rn));
                    @endphp
                    <tr>
                        {{-- # --}}
                        <td class="text-muted ps-3" style="font-size:12px;">
                            {{ $users->firstItem() + $loop->index }}
                        </td>

                        {{-- Name + Avatar --}}
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($user->avatar)
                                    <img src="{{ Storage::url($user->avatar) }}"
                                         class="rounded-circle" width="34" height="34"
                                         style="object-fit:cover;flex-shrink:0;" alt="">
                                @else
                                    <div class="um-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                @endif
                                <span class="um-name">{{ $user->name }}</span>
                            </div>
                        </td>

                        {{-- Username --}}
                        <td>
                            <code style="font-size:12px;color:#6366f1;background:#eef2ff;padding:2px 7px;border-radius:5px;">
                                {{ $user->username }}
                            </code>
                        </td>

                        {{-- Email --}}
                        <td class="text-muted" style="font-size:12.5px;">{{ $user->email }}</td>

                        {{-- Role --}}
                        <td>
                            <span class="um-badge {{ $roleClass }}">{{ $roleLabel }}</span>
                        </td>

                        {{-- Linked Employee --}}
                        <td class="text-muted" style="font-size:12.5px;">
                            {{ $user->employee?->full_name ?? '—' }}
                        </td>

                        {{-- Branch --}}
                        <td class="text-muted" style="font-size:12.5px;">
                            {{ $user->branch->name ?? '—' }}
                        </td>

                        {{-- Last Login --}}
                        <td class="text-muted" style="font-size:12px;">
                            @if($user->last_login_at)
                                <span title="{{ $user->last_login_at->format('d M Y, H:i') }}">
                                    {{ $user->last_login_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-muted">Never</span>
                            @endif
                        </td>

                        {{-- Status --}}
                        <td class="text-center">
                            @if ($user->is_locked)
                                <span class="um-badge status-inactive">
                                    <i class="bi bi-lock-fill" style="font-size:10px; margin-right:3px;"></i> Locked
                                </span>
                            @else
                                <span class="um-badge {{ $user->is_active ? 'status-active' : 'status-inactive' }}">
                                    <i class="bi bi-{{ $user->is_active ? 'check-circle-fill' : 'x-circle-fill' }}"
                                       style="font-size:10px; margin-right:3px;"></i>
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td>
                            <div class="um-actions justify-content-end pe-1">
                                <a href="{{ route('users.edit', $user) }}"
                                   class="um-btn um-btn-edit" title="Edit user">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ route('users.permissions', $user) }}"
                                   class="um-btn um-btn-perms" title="Permissions">
                                    <i class="bi bi-shield-check"></i>
                                </a>
                                @if($user->id !== auth()->id())
                                    @if ($user->is_locked)
                                        <form action="{{ route('users.unlock', $user) }}" method="POST" class="m-0 p-0">
                                            @csrf
                                            <button type="submit" class="um-btn um-btn-edit" title="Unlock"><i class="bi bi-unlock"></i></button>
                                        </form>
                                    @else
                                        <form action="{{ route('users.lock', $user) }}" method="POST" class="m-0 p-0">
                                            @csrf
                                            <button type="submit" class="um-btn um-btn-edit" title="Lock"><i class="bi bi-lock"></i></button>
                                        </form>
                                    @endif
                                    @if ($user->is_active)
                                        <form action="{{ route('users.deactivate', $user) }}" method="POST" class="m-0 p-0" data-confirm-delete="Deactivate this user?">
                                            @csrf
                                            <button type="submit" class="um-btn um-btn-edit" title="Deactivate"><i class="bi bi-slash-circle"></i></button>
                                        </form>
                                    @else
                                        <form action="{{ route('users.activate', $user) }}" method="POST" class="m-0 p-0">
                                            @csrf
                                            <button type="submit" class="um-btn um-btn-edit" title="Activate"><i class="bi bi-check-circle"></i></button>
                                        </form>
                                    @endif
                                @endif
                                @if($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user) }}" method="POST"
                                      data-confirm-delete="Delete user '{{ addslashes($user->name) }}'?"
                                      class="m-0 p-0">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="um-btn um-btn-del" title="Delete user">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="bi bi-people d-block mb-2" style="font-size:32px;opacity:.3;"></i>
                            No users found.
                            @if(request()->hasAny(['search','role_id','is_active']))
                                <a href="{{ route('users.index') }}" class="d-block mt-1" style="font-size:13px;">
                                    Clear filters
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Footer: record count + pagination ───────────────────── --}}
    @if($users->total() > 0)
    <div class="card-footer bg-white border-top px-3 py-2">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <p class="mb-0 text-muted" style="font-size:13px;">
                Showing <strong>{{ $users->firstItem() }}</strong>–<strong>{{ $users->lastItem() }}</strong>
                of <strong>{{ $users->total() }}</strong> users
            </p>
            <div>{{ $users->withQueryString()->links() }}</div>
        </div>
    </div>
    @endif

</div>
@endsection
