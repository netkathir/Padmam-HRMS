@extends('layouts.guest')

@section('title', 'Change Password')
@section('auth-subtitle', 'Your administrator requires you to set a new password before continuing')

@section('content')
<form method="POST" action="{{ route('password.force-change.update') }}">
    @csrf

    <div class="mb-3">
        <label class="form-label" for="password">New Password</label>
        <div class="input-group">
            <input type="password" name="password" id="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Min. 8 characters" required autofocus>
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
        <i class="bi bi-lock me-2"></i>Set New Password
    </button>
</form>
@endsection

@section('auth-footer')
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-link p-0"><i class="bi bi-arrow-left me-1"></i>Log out instead</button>
    </form>
@endsection
