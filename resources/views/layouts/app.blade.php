<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'HRMS') }}</title>

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        * { box-sizing: border-box; }

        html, body {
            height: 100%;
        }
        body {
            background-color: #f5f6fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 14px;
            /* The page itself never scrolls — the sidebar's own nav (.sb-nav)
               and .page-content below scroll independently instead, so the
               sidebar stays fixed in place while either one scrolls. */
            overflow: hidden;
        }

        /* ── Layout wrapper ── */
        .layout-wrapper { display: flex; height: 100vh; overflow: hidden; }

        /* ── Main content ── */
        .main-content { flex: 1; min-width: 0; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        .page-content  { flex: 1; min-height: 0; overflow-y: auto; padding: 20px 24px; }

        /* ── Sidebar backdrop (mobile) ── */
        .sidebar-backdrop {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 1039;
            display: none;
        }
        .sidebar-backdrop.show { display: block; }

        /* ── Cards & common ── */
        .card { border: none; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        .card-header { background: #fff; border-bottom: 1px solid #f0f0f0; font-weight: 600; }

        /* stat-card styles removed — dashboard uses kpi-card */

        /* ── Tables ── */
        .table th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; background: #f8f9fa; }
        .table td { vertical-align: middle; }

        /* ── Badges ── */
        .badge { font-weight: 500; font-size: 11px; }

        /* ── Page title ── */
        .page-title    { font-size: 18px; font-weight: 600; color: #1a1a2e; margin-bottom: 4px; }
        .page-subtitle { font-size: 13px; color: #6c757d; margin-bottom: 0; }

        /* ── Back button (layout-level) ── */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 5px 13px 5px 7px;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            color: #4b5563;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
            white-space: nowrap;
            flex-shrink: 0;
            text-decoration: none;
            line-height: 1.4;
            box-shadow: 0 1px 2px rgba(0,0,0,.05);
        }
        .btn-back:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            color: #1f2937;
            transform: translateX(-2px);
        }
        .btn-back:active { transform: translateX(0); }
        .btn-back .bi { font-size: 17px; line-height: 1; }

        /* ── Alerts ── */
        .alert { border: none; border-radius: 8px; }

        /* ── Pagination ── */
        .pagination { margin-bottom: 0; flex-wrap: wrap; }
        .pagination .page-link { font-size: 13px; padding: 4px 10px; border-color: #e5e7eb; color: #374151; }
        .pagination .page-item.active .page-link { background-color: #4f7ef8; border-color: #4f7ef8; }
        .pagination .page-item.disabled .page-link { color: #9ca3af; }
        .card-footer .pagination { margin-bottom: 0; }
    </style>

    @stack('styles')
</head>
<body>

<div class="layout-wrapper">
    {{-- Sidebar --}}
    @include('partials._sidebar')

    {{-- Backdrop for mobile --}}
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    {{-- Main --}}
    <div class="main-content">
        {{-- Navbar --}}
        @include('partials._navbar')

        {{-- Page content --}}
        <div class="page-content">
            {{-- Alerts --}}
            @include('partials._alerts')
            @include('partials._confirm-modal')

            {{-- Page header --}}
            @hasSection('page-title')
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-3">
                    @unless(Route::is('dashboard'))
                    @hasSection('back-url')
                    <a href="@yield('back-url')" class="btn-back" title="Go back">
                        <i class="bi bi-arrow-left-short"></i> Back
                    </a>
                    @else
                    <button type="button" class="btn-back" onclick="history.back()" title="Go back">
                        <i class="bi bi-arrow-left-short"></i> Back
                    </button>
                    @endif
                    @endunless
                    <div>
                        <h1 class="page-title">@yield('page-title')</h1>
                        @hasSection('page-subtitle')
                        <p class="page-subtitle">@yield('page-subtitle')</p>
                        @endif
                    </div>
                </div>
                @hasSection('page-actions')
                <div class="d-flex gap-2">@yield('page-actions')</div>
                @endif
            </div>
            @endif

            @yield('content')
        </div>

        {{-- Footer --}}
        <footer class="text-center text-muted py-3 border-top" style="font-size:12px;background:#fff;">
            &copy; {{ date('Y') }} {{ config('app.name', 'HRMS') }}. All rights reserved.
        </footer>
    </div>
</div>

{{-- Bootstrap JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- Chart.js — Dashboard FSD charts (Overall Dashboard, Branch Dashboard) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<script>
// Sidebar toggle
const sidebar  = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebarBackdrop');
const toggle   = document.getElementById('sidebarToggle');

if (toggle) {
    toggle.addEventListener('click', () => {
        if (window.innerWidth < 992) {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        } else {
            // On desktop: collapse width
            if (sidebar.style.width === '0px' || sidebar.style.width === '') {
                sidebar.style.width = '260px';
                sidebar.style.minWidth = '260px';
            } else {
                sidebar.style.width = '0px';
                sidebar.style.minWidth = '0px';
            }
        }
    });
}

if (backdrop) {
    backdrop.addEventListener('click', () => {
        sidebar.classList.remove('show');
        backdrop.classList.remove('show');
    });
}

// Toast notifications — top-right, auto-shown on page load.
document.querySelectorAll('.toast').forEach(el => {
    bootstrap.Toast.getOrCreateInstance(el).show();
});

// System-themed delete/destructive-action confirmation — replaces the
// browser's native confirm() everywhere. Any form with a
// data-confirm-delete="Message?" attribute is intercepted: the form is not
// submitted until the shared modal is confirmed, then it's submitted
// programmatically.
(function () {
    const modalEl = document.getElementById('confirmActionModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const messageEl = modalEl.querySelector('[data-confirm-message]');
    const confirmBtn = modalEl.querySelector('[data-confirm-proceed]');
    let pendingForm = null;

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm-delete')) return;
        if (form.dataset.confirmed === '1') return; // already confirmed, let it submit

        e.preventDefault();
        pendingForm = form;
        messageEl.textContent = form.getAttribute('data-confirm-delete') || 'Are you sure?';
        modal.show();
    });

    confirmBtn.addEventListener('click', function () {
        modal.hide();
        if (pendingForm) {
            pendingForm.dataset.confirmed = '1';
            pendingForm.requestSubmit();
            pendingForm = null;
        }
    });
})();
</script>

@stack('scripts')
</body>
</html>
