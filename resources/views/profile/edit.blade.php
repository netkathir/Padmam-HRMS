@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')
@section('page-subtitle', 'Manage your account information')

@section('content')
<div class="row g-4">
    {{-- Profile Info --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-person me-2"></i>Profile Information</div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                               class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                               class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                               class="form-control" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Change Password --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-lock me-2"></i>Change Password</div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" name="current_password" id="current_password"
                               class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password" name="password" id="new_password"
                               class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password_confirmation">Confirm New Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-shield-lock me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>

        {{-- Account info --}}
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Account Info</div>
            <div class="card-body" style="font-size:13px;">
                <div class="row mb-2">
                    <div class="col-5 text-muted">Role</div>
                    <div class="col-7 fw-semibold">{{ $user->role->name ?? '—' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Last Login</div>
                    <div class="col-7">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</div>
                </div>
                <div class="row">
                    <div class="col-5 text-muted">Member Since</div>
                    <div class="col-7">{{ $user->created_at->format('d M Y') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
