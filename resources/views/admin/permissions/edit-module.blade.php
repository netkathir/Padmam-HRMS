{{--
    File: resources/views/admin/permissions/edit-module.blade.php
    Purpose: Single Edit Permission screen for one module — an Access Level
             dropdown (No Access / Read Only / Create / Delete / Full Access)
             switches which level's description is shown; Update saves all of
             them in one request. No separate per-level records/pages.
    Author: System
    Date: 2026-07-08
--}}
@extends('layouts.app')

@section('title', 'Manage Permissions — ' . $label)
@section('page-title', 'Edit Permission')
@section('page-subtitle', $label)

@section('page-actions')
    <x-back-button href="{{ route('admin.permissions.index') }}" />
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center py-3">
                <i class="bi bi-key me-2 text-primary"></i>
                <span class="fw-semibold" style="font-size:14px;">{{ $label }}</span>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admin.permissions.module.update', $module) }}" method="POST" id="moduleForm">
                    @csrf @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Module</label>
                            <input type="text" value="{{ $module }}" class="form-control" disabled readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Access Level</label>
                            <select id="levelSelect" class="form-select">
                                <option value="" disabled>No Access</option>
                                <option value="read">Read Only</option>
                                <option value="create">Create</option>
                                <option value="delete">Delete</option>
                                <option value="full">Full Access</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium d-flex align-items-center gap-2">
                            Description
                            <code id="permSlug" class="text-muted" style="font-size:12px;"></code>
                        </label>
                        <textarea id="descriptionBox" rows="3" class="form-control"
                                  placeholder="What does this access level allow?"></textarea>
                        <div class="form-text">Switching Access Level keeps your edits for every level — Update saves them all together.</div>
                    </div>

                    {{-- Populated from the descriptions map just before submit --}}
                    @foreach($levels as $level)
                    <input type="hidden" name="descriptions[{{ $level }}]" id="hidden_{{ $level }}">
                    @endforeach

                    <div class="d-flex gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check-lg"></i> Update
                        </button>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const module = @json($module);
    const definedLevels = @json(collect($levels)->filter(fn ($lvl) => $permissionsByLevel->has($lvl))->values());
    const descriptions = @json(collect($levels)->mapWithKeys(
        fn ($lvl) => [$lvl => old("descriptions.$lvl", optional($permissionsByLevel->get($lvl))->description ?? '')]
    ));

    const select = document.getElementById('levelSelect');
    const box = document.getElementById('descriptionBox');
    const slug = document.getElementById('permSlug');

    const firstDefined = definedLevels[0] ?? '';
    select.value = firstDefined;
    let current = select.value;

    function load(level) {
        box.value = descriptions[level] ?? '';
        box.disabled = !definedLevels.includes(level);
        slug.textContent = level ? (module + '.' + level) : '';
    }
    load(current);

    select.addEventListener('change', function () {
        descriptions[current] = box.value;
        current = this.value;
        load(current);
    });

    document.getElementById('moduleForm').addEventListener('submit', function () {
        descriptions[current] = box.value;
        Object.keys(descriptions).forEach(function (level) {
            const hidden = document.getElementById('hidden_' + level);
            if (hidden) hidden.value = descriptions[level] ?? '';
        });
    });
})();
</script>
@endpush
