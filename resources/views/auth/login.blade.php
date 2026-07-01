@extends('layouts.guest')

@section('title', 'Login')
@section('auth-subtitle', 'Sign in to your account')

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="mb-3">
        <label class="form-label" for="login">Email or Username</label>
        <input type="text" name="login" id="login" value="{{ old('login') }}"
               class="form-control @error('login') is-invalid @enderror"
               placeholder="email@company.com or username" autofocus autocomplete="username" required>
        @error('login')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <label class="form-label mb-0" for="password">Password</label>
            <a href="{{ route('password.request') }}" class="text-decoration-none text-primary" style="font-size:13px;">Forgot password?</a>
        </div>
        <div class="input-group mt-1">
            <input type="password" name="password" id="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="btn" data-bs-toggle="password" data-bs-target="#password" tabindex="-1">
                <i class="bi bi-eye"></i>
            </button>
            @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-4 d-flex align-items-center">
        <input type="checkbox" name="remember" id="remember" class="form-check-input me-2" value="1">
        <label class="form-check-label" for="remember">Keep me signed in</label>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
    </button>
</form>
@endsection
