@extends('layouts.guest')

@section('title', 'Forgot Password')
@section('auth-subtitle', 'Reset your password')

@section('content')
<p class="text-muted mb-4" style="font-size:13px;">
    Enter your email address and we'll send you a link to reset your password.
</p>

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="mb-4">
        <label class="form-label" for="email">Email Address</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}"
               class="form-control @error('email') is-invalid @enderror"
               placeholder="Enter your email" autofocus required>
        @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-envelope me-2"></i>Send Reset Link
    </button>
</form>
@endsection

@section('auth-footer')
    <a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to Login</a>
@endsection
