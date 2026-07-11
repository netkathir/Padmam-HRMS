{{--
    File: resources/views/admin/roles/create.blade.php
    Purpose: Create new role form — System Admin module
    Author: System
    Date: 2026-06-30
--}}
@extends('layouts.app')

@section('title', 'Create Role')
@section('page-title', 'Create Role')
@section('page-subtitle', 'Add a new access role to the system')

@section('page-actions')
    <x-back-button href="{{ route('admin.roles.index') }}" />
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between py-3">
                <span class="fw-semibold" style="font-size:14px;">
                    <i class="bi bi-shield-plus me-2 text-primary"></i>Role Details
                </span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.roles.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-medium">System Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. hr_manager (lowercase, underscores only)"
                               pattern="^[a-z][a-z_]*$">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Lowercase letters and underscores only. Used internally.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="display_name" value="{{ old('display_name') }}"
                               class="form-control @error('display_name') is-invalid @enderror"
                               placeholder="e.g. HR Manager">
                        @error('display_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Role Code</label>
                        <input type="text" name="role_code" value="{{ old('role_code') }}"
                               class="form-control @error('role_code') is-invalid @enderror"
                               placeholder="e.g. HR_MGR">
                        @error('role_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Applicable User Type</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="applicable_user_types[]" value="branch_head"
                                id="rtype_branch_head" {{ in_array('branch_head', old('applicable_user_types', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="rtype_branch_head">Branch Head</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="applicable_user_types[]" value="branch_user"
                                id="rtype_branch_user" {{ in_array('branch_user', old('applicable_user_types', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="rtype_branch_user">Branch User</label>
                        </div>
                        <div class="form-text">Optional — relevant only for branch-scoped roles.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Brief description of this role's purpose">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="isActive" value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="isActive">Active</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check-lg"></i> Create Role
                        </button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
