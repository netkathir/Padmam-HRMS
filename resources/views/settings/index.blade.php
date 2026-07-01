@extends('layouts.app')
@section('title','Settings')
@section('page-title','Settings')
@section('page-subtitle','Application configuration')
@section('content')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-building fs-1 text-primary d-block mb-2"></i>
                <h6>Company Settings</h6>
                <p class="text-muted small mb-3">Update company name, logo, address, and statutory information.</p>
                <a href="{{ route('settings.company') }}" class="btn btn-primary btn-sm"><i class="bi bi-arrow-right"></i> Configure</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-gear fs-1 text-success d-block mb-2"></i>
                <h6>General Settings</h6>
                <p class="text-muted small mb-3">Configure time zone, date format, currency, and application defaults.</p>
                <a href="{{ route('settings.general') }}" class="btn btn-success btn-sm"><i class="bi bi-arrow-right"></i> Configure</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-people fs-1 text-info d-block mb-2"></i>
                <h6>User Management</h6>
                <p class="text-muted small mb-3">Manage system users, roles, and permissions.</p>
                <a href="{{ route('users.index') }}" class="btn btn-info btn-sm"><i class="bi bi-arrow-right"></i> Manage</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-grid fs-1 text-warning d-block mb-2"></i>
                <h6>Masters</h6>
                <p class="text-muted small mb-3">Configure departments, designations, shifts, leave types, and payroll components.</p>
                <a href="{{ route('masters.index') }}" class="btn btn-warning btn-sm"><i class="bi bi-arrow-right"></i> Open Masters</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-envelope fs-1 text-secondary d-block mb-2"></i>
                <h6>Email / Notifications</h6>
                <p class="text-muted small mb-3">Configure mail server settings and notification preferences.</p>
                <span class="badge bg-secondary-subtle text-secondary">Coming Soon</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-shield-check fs-1 text-danger d-block mb-2"></i>
                <h6>Security</h6>
                <p class="text-muted small mb-3">Password policy, session management, and audit logs.</p>
                <span class="badge bg-secondary-subtle text-secondary">Coming Soon</span>
            </div>
        </div>
    </div>
</div>
@endsection
