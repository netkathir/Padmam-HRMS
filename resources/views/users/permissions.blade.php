@extends('layouts.app')
@section('title','User Permissions')
@section('page-title','User Permissions')
@section('page-subtitle',$user->name . ' — ' . ucfirst(str_replace('_',' ',$user->role?->name ?? 'no role')))
@section('page-actions')
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Users</a>
@endsection
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- User card --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
            @if($user->avatar)
                <img src="{{ Storage::url($user->avatar) }}" class="rounded-circle" width="52" height="52" style="object-fit:cover">
            @else
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0" style="width:52px;height:52px;font-size:20px">{{ strtoupper(substr($user->name,0,1)) }}</div>
            @endif
            <div>
                <div class="fw-bold fs-5">{{ $user->name }}</div>
                <div class="text-muted small">{{ $user->email }} &nbsp;|&nbsp; @<strong>{{ $user->username }}</strong></div>
            </div>
            <div class="ms-auto">
                <span class="badge bg-primary-subtle text-primary fs-6">{{ ucfirst(str_replace('_',' ',$user->role?->name ?? '—')) }}</span>
                <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}-subtle text-{{ $user->is_active ? 'success' : 'danger' }} ms-1">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Role-based access info --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-shield-check me-1"></i>Role Permissions — {{ ucfirst(str_replace('_',' ',$user->role?->name ?? '—')) }}</h6>
        <span class="badge bg-info-subtle text-info">{{ $user->role?->permissions->count() ?? 0 }} permissions</span>
    </div>
    <div class="card-body">
        @php
            $rolePermissions = $user->role?->permissions ?? collect();
            $grouped = $rolePermissions->groupBy('module');
        @endphp
        @if($grouped->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                No permissions assigned to the <strong>{{ $user->role?->display_name ?? 'assigned' }}</strong> role yet.
            </div>
        @else
            <div class="row g-3">
                @foreach($grouped as $module => $perms)
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="fw-semibold text-primary mb-2 text-capitalize">
                            <i class="bi bi-grid-3x3-gap me-1"></i>{{ str_replace('_',' ',$module) }}
                        </div>
                        @foreach($perms as $perm)
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-check-circle-fill text-success small"></i>
                            <small>{{ ucfirst(str_replace(['_','.'],' ',$perm->action)) }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@if($user->isSuperAdmin())
<div class="alert alert-warning">
    <i class="bi bi-shield-fill-exclamation me-2"></i>
    <strong>Super Admin:</strong> This user bypasses all permission checks and has unrestricted access to every module.
</div>
@endif
@endsection
