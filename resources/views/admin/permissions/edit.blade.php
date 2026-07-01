{{--
    File: resources/views/admin/permissions/edit.blade.php
    Purpose: Edit permission — module + access level
    Author: System
    Date: 2026-06-30
--}}
@extends('layouts.app')

@section('title', 'Edit Permission')
@section('page-title', 'Edit Permission')
@section('page-subtitle', $permission->name)

@section('page-actions')
    <x-back-button href="{{ route('admin.permissions.index') }}" />
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center py-3">
                <i class="bi bi-key me-2 text-warning"></i>
                <span class="fw-semibold" style="font-size:14px;">{{ $permission->name }}</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
                    @csrf @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Module <span class="text-danger">*</span></label>
                            <input type="text" name="module"
                                   value="{{ old('module', $permission->module) }}"
                                   list="moduleList"
                                   class="form-control @error('module') is-invalid @enderror">
                            <datalist id="moduleList">
                                @foreach($modules as $mod)
                                <option value="{{ $mod }}">
                                @endforeach
                            </datalist>
                            @error('module')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Access Level <span class="text-danger">*</span></label>
                            <select name="access_level"
                                    class="form-select @error('access_level') is-invalid @enderror">
                                @foreach($levels as $lvl)
                                <option value="{{ $lvl }}"
                                    {{ old('access_level', $permission->access_level) === $lvl ? 'selected' : '' }}>
                                    {{ strtoupper($lvl) }}
                                </option>
                                @endforeach
                            </select>
                            @error('access_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" rows="2"
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $permission->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-warning d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check-lg"></i> Update Permission
                        </button>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
