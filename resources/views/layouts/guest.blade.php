<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') — {{ config('app.name', 'HRMS') }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 14px;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 16px;
        }

        .auth-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
            overflow: hidden;
        }

        .auth-header {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            padding: 32px 32px 28px;
            text-align: center;
            color: #fff;
        }

        .auth-header .brand-icon {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 30px;
        }

        .auth-header h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .auth-header p  { font-size: 13px; opacity: .8; margin: 0; }

        .auth-body { padding: 32px; }

        .form-label { font-weight: 500; color: #374151; margin-bottom: 4px; }

        .form-control, .form-select {
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            padding: 9px 13px;
            font-size: 14px;
            transition: border-color .15s, box-shadow .15s;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,.15);
        }

        .input-group .form-control { border-right: none; }
        .input-group .btn { border: 1.5px solid #e5e7eb; border-left: none; background: #fff; color: #6c757d; border-radius: 0 8px 8px 0; }
        .input-group .btn:hover { background: #f3f4f6; }
        .input-group:focus-within .btn { border-color: #0d6efd; }

        .btn-primary {
            border-radius: 8px;
            padding: 10px;
            font-weight: 600;
            font-size: 14px;
            background: #0d6efd;
            border: none;
            transition: background .15s, transform .1s;
        }

        .btn-primary:hover { background: #0b5ed7; transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }

        .alert { border: none; border-radius: 8px; font-size: 13px; }

        .auth-footer { text-align: center; padding: 0 32px 24px; font-size: 13px; color: #6c757d; }
        .auth-footer a { color: #0d6efd; text-decoration: none; }
        .auth-footer a:hover { text-decoration: underline; }
    </style>

    @stack('styles')
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="brand-icon">
                <i class="bi bi-building"></i>
            </div>
            <h1>{{ config('app.name', 'HRMS') }}</h1>
            <p>@yield('auth-subtitle', 'Human Resource Management System')</p>
        </div>

        <div class="auth-body">
            @include('partials._alerts')
            @yield('content')
        </div>

        @hasSection('auth-footer')
        <div class="auth-footer">@yield('auth-footer')</div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle password visibility
document.querySelectorAll('[data-bs-toggle="password"]').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.querySelector(btn.dataset.bsTarget);
        if (!input) return;
        const isText = input.type === 'text';
        input.type = isText ? 'password' : 'text';
        btn.querySelector('i').classList.toggle('bi-eye', isText);
        btn.querySelector('i').classList.toggle('bi-eye-slash', !isText);
    });
});
</script>

@stack('scripts')
</body>
</html>
