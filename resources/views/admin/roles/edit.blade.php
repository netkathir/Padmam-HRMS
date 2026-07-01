{{--
    File: resources/views/admin/roles/edit.blade.php
    Purpose: Edit existing role — System Admin module
    Author: System
    Date: 2026-06-30
--}}
@extends('layouts.app')

@section('title', 'Edit Role')
@section('page-title', 'Edit Role')
@section('page-subtitle', 'Modify role: ' . $role->display_name)

@section('page-actions')
    <x-back-button href="{{ route('admin.roles.index') }}" />
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between py-3">
                <span class="fw-semibold" style="font-size:14px;">
                    <i class="bi bi-shield-check me-2 text-warning"></i>{{ $role->display_name }}
                </span>
                <span class="badge bg-{{ $role->is_active ? 'success' : 'danger' }}-subtle
                             text-{{ $role->is_active ? 'success' : 'danger' }}">
                    {{ $role->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-medium">System Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $role->name) }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. hr_manager"
                               pattern="^[a-z][a-z_]*$">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Lowercase letters and underscores only.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Display Name <span class="text-danger">*</span></label>
                        <input type="text" name="display_name" value="{{ old('display_name', $role->display_name) }}"
                               class="form-control @error('display_name') is-invalid @enderror">
                        @error('display_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $role->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="isActive" value="1"
                                   {{ old('is_active', $role->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="isActive">Active</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-warning d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check-lg"></i> Update Role
                        </button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
