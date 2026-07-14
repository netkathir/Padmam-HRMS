@extends('layouts.app')
@section('title','Add User')
@section('page-title','Add User')
@section('page-subtitle','Create a new system user account')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">User Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Login ID <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" required>
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile') }}" placeholder="e.g. +91 98765 43210">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if ($isSuperAdmin || $isBranchScoped)
                            <div class="col-md-4">
                                <label class="form-label">User Type</label>
                                <select name="user_type" id="userType" class="form-select @error('user_type') is-invalid @enderror" {{ count($userTypeOptions) === 1 ? 'disabled' : '' }}>
                                    @foreach ($userTypeOptions as $value => $label)
                                        <option value="{{ $value }}" {{ old('user_type', array_key_first($userTypeOptions)) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if (count($userTypeOptions) === 1)
                                    <input type="hidden" name="user_type" value="{{ array_key_first($userTypeOptions) }}">
                                @endif
                                @error('user_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch <small class="text-muted">(currently active)</small></label>
                                <select name="branch_id" id="branchSelect" class="form-select @error('branch_id') is-invalid @enderror" {{ $lockedBranchId ? 'disabled' : '' }}>
                                    <option value="">— Not Applicable —</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ (string) old('branch_id', $lockedBranchId) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                @if ($lockedBranchId)
                                    <input type="hidden" name="branch_id" value="{{ $lockedBranchId }}">
                                @endif
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span> <small class="text-muted">(select one or more)</small></label>
                            <div class="border rounded p-2 @error('role_ids') is-invalid @enderror" style="max-height: 160px; overflow-y: auto;">
                                @forelse($roles as $role)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="role_ids[]" value="{{ $role->id }}" id="role{{ $role->id }}"
                                        {{ in_array($role->id, old('role_ids', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role{{ $role->id }}">{{ $role->display_name ?: ucfirst(str_replace('_',' ',$role->name)) }}</label>
                                </div>
                                @empty
                                <span class="text-muted">No assignable roles available.</span>
                                @endforelse
                            </div>
                            @error('role_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @error('role_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Branch Access <span class="text-danger">*</span> <small class="text-muted">(one or more branches)</small></label>
                            @if ($lockedBranchId)
                                <div class="border rounded p-2 bg-light">
                                    {{ $branches->firstWhere('id', $lockedBranchId)->name ?? '—' }}
                                    <small class="text-muted d-block">This account is restricted to a single branch.</small>
                                </div>
                                <input type="hidden" name="branch_ids[]" value="{{ $lockedBranchId }}">
                            @else
                                <div class="border rounded p-2 @error('branch_ids') is-invalid @enderror" style="max-height: 160px; overflow-y: auto;">
                                    @foreach($allBranches as $branch)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" id="branchAccess{{ $branch->id }}"
                                            {{ in_array($branch->id, old('branch_ids', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="branchAccess{{ $branch->id }}">{{ $branch->name }}</label>
                                    </div>
                                    @endforeach
                                </div>
                                @error('branch_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @error('branch_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Employee Mapping</label>
                            <input type="search" id="employeeSearch" class="form-control form-control-sm mb-1" placeholder="Search employee by name or code…">
                            <select name="employee_id" id="employeeSelect" class="form-select">
                                <option value="">— None —</option>
                                @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" data-branch="{{ $emp->branch_id }}"
                                    data-search="{{ strtolower($emp->employee_code.' '.$emp->first_name.' '.$emp->last_name) }}"
                                    {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->employee_code }} — {{ $emp->first_name }} {{ $emp->last_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Avatar</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Account Expiry Date</label>
                            <input type="date" name="account_expiry_date" class="form-control @error('account_expiry_date') is-invalid @enderror" value="{{ old('account_expiry_date') }}">
                            @error('account_expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="locked" {{ old('status') === 'locked' ? 'selected' : '' }}>Locked</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="force_password_change" value="1" id="forcePwd" {{ old('force_password_change') ? 'checked' : '' }}>
                                <label class="form-check-label" for="forcePwd">Force password change on first login</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus"></i> Create User</button>
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
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
    var branchSelect = document.getElementById('branchSelect');
    var employeeSelect = document.getElementById('employeeSelect');
    var employeeSearch = document.getElementById('employeeSearch');

    function filterEmployees() {
        var branchId = branchSelect ? branchSelect.value : '';
        var term = employeeSearch ? employeeSearch.value.trim().toLowerCase() : '';
        Array.from(employeeSelect.options).forEach(function (opt) {
            if (!opt.value) return;
            var matchesBranch = !branchId || opt.dataset.branch === branchId;
            var matchesSearch = !term || (opt.dataset.search || '').indexOf(term) !== -1;
            var matches = matchesBranch && matchesSearch;
            opt.hidden = !matches;
            if (!matches && opt.selected) opt.selected = false;
        });
    }

    if (employeeSelect) {
        if (branchSelect) branchSelect.addEventListener('change', filterEmployees);
        if (employeeSearch) employeeSearch.addEventListener('input', filterEmployees);
        filterEmployees();
    }
})();
</script>
@endpush
