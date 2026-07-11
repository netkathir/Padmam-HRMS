@extends('layouts.app')
@section('title','Edit User')
@section('page-title','Edit User')
@section('page-subtitle',$user->name)
@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">User Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Login ID <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $user->username) }}" required>
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile', $user->mobile) }}">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if ($isSuperAdmin || $isBranchScoped)
                            <div class="col-md-4">
                                <label class="form-label">User Type</label>
                                <select name="user_type" class="form-select @error('user_type') is-invalid @enderror" {{ count($userTypeOptions) === 1 ? 'disabled' : '' }}>
                                    @foreach ($userTypeOptions as $value => $label)
                                        <option value="{{ $value }}" {{ old('user_type', $user->user_type ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if (count($userTypeOptions) === 1)
                                    <input type="hidden" name="user_type" value="{{ array_key_first($userTypeOptions) }}">
                                @endif
                                @error('user_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch</label>
                                <select name="branch_id" id="branchSelect" class="form-select @error('branch_id') is-invalid @enderror" {{ $lockedBranchId ? 'disabled' : '' }}>
                                    <option value="">— Not Applicable —</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ (string) old('branch_id', $lockedBranchId ?? $user->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                @if ($lockedBranchId)
                                    <input type="hidden" name="branch_id" value="{{ $lockedBranchId }}">
                                @endif
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                                <option value="">Select role…</option>
                                @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$role->name)) }}</option>
                                @endforeach
                            </select>
                            @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">New Password <small class="text-muted">(leave blank to keep)</small></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Employee Mapping</label>
                            <select name="employee_id" id="employeeSelect" class="form-select">
                                <option value="">— None —</option>
                                @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" data-branch="{{ $emp->branch_id }}" {{ old('employee_id', $user->employee_id) == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->employee_code }} — {{ $emp->first_name }} {{ $emp->last_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Avatar</label>
                            @if($user->avatar)
                            <div class="mb-1"><img src="{{ Storage::url($user->avatar) }}" class="rounded-circle" width="40" height="40" style="object-fit:cover"></div>
                            @endif
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Account Expiry Date</label>
                            <input type="date" name="account_expiry_date" class="form-control @error('account_expiry_date') is-invalid @enderror" value="{{ old('account_expiry_date', $user->account_expiry_date?->format('Y-m-d')) }}">
                            @error('account_expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            @php $currentStatus = $user->is_locked ? 'locked' : ($user->is_active ? 'active' : 'inactive'); @endphp
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', $currentStatus) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $currentStatus) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="locked" {{ old('status', $currentStatus) === 'locked' ? 'selected' : '' }}>Locked</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="force_password_change" value="1" id="forcePwd" {{ old('force_password_change', $user->force_password_change) ? 'checked' : '' }}>
                                <label class="form-check-label" for="forcePwd">Force password change on next login</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $user->remarks) }}</textarea>
                        </div>

                        <div class="col-12">
                            <hr>
                            <div class="row text-muted small">
                                <div class="col-md-6">
                                    <strong>Created By:</strong> {{ $user->createdBy->name ?? '—' }}
                                    <span class="ms-1">{{ $user->created_at?->format('d M Y, h:i A') }}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Modified By:</strong> {{ $user->updatedBy->name ?? '—' }}
                                    <span class="ms-1">{{ $user->updated_at?->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update User</button>
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
    if (!branchSelect || !employeeSelect) return;

    function filterEmployees() {
        var branchId = branchSelect.value;
        Array.from(employeeSelect.options).forEach(function (opt) {
            if (!opt.value) return;
            opt.hidden = branchId && opt.dataset.branch !== branchId;
        });
    }

    branchSelect.addEventListener('change', filterEmployees);
    filterEmployees();
})();
</script>
@endpush
