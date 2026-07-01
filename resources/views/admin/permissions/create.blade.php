{{--
    File: resources/views/admin/permissions/create.blade.php
    Purpose: Add new permission form — module + access level
    Author: System
    Date: 2026-06-30
--}}
@extends('layouts.app')

@section('title', 'Add Permission')
@section('page-title', 'Add Permission')
@section('page-subtitle', 'Define a new module-level access permission')

@section('page-actions')
    <x-back-button href="{{ route('admin.permissions.index') }}" />
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center py-3">
                <i class="bi bi-key me-2 text-primary"></i>
                <span class="fw-semibold" style="font-size:14px;">New Permission</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.permissions.store') }}" method="POST">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Module <span class="text-danger">*</span></label>
                            <input type="text" name="module" value="{{ old('module') }}"
                                   list="moduleList"
                                   class="form-control @error('module') is-invalid @enderror"
                                   placeholder="e.g. employees">
                            <datalist id="moduleList">
                                @foreach($modules as $mod)
                                <option value="{{ $mod }}">
                                @endforeach
                            </datalist>
                            @error('module')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Lowercase, no spaces (underscores ok).</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Access Level <span class="text-danger">*</span></label>
                            <select name="access_level"
                                    class="form-select @error('access_level') is-invalid @enderror">
                                <option value="">— Select level —</option>
                                @foreach($levels as $lvl)
                                <option value="{{ $lvl }}" {{ old('access_level') === $lvl ? 'selected' : '' }}>
                                    {{ strtoupper($lvl) }}
                                </option>
                                @endforeach
                            </select>
                            @error('access_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Higher levels grant all lower-level access.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" rows="2"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="What does this permission allow?">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check-lg"></i> Add Permission
                        </button>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
