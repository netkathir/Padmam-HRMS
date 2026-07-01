@extends('layouts.guest')

@section('title', 'Reset Password')
@section('auth-subtitle', 'Set a new password')

@section('content')
<form method="POST" action="{{ route('password.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" name="email" id="email" value="{{ old('email', $email ?? '') }}"
               class="form-control @error('email') is-invalid @enderror"
               placeholder="Your email" required>
        @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="password">New Password</label>
        <div class="input-group">
            <input type="password" name="password" id="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Min. 8 characters" required>
            <button type="button" class="btn" data-bs-toggle="password" data-bs-target="#password" tabindex="-1">
                <i class="bi bi-eye"></i>
            </button>
            @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label" for="password_confirmation">Confirm Password</label>
        <input type="password" name="password_confirmation" id="password_confirmation"
               class="form-control" placeholder="Repeat password" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-lock me-2"></i>Reset Password
    </button>
</form>
@endsection

@section('auth-footer')
    <a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to Login</a>
@endsection
