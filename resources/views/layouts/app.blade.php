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
    {{-- Flatpickr — system-wide replacement date picker, auto-applied to every native input[type=date] (see bottom of page). Material Blue theme for a modern look, matching this app's blue accent. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/themes/material_blue.css">
    <style>
        /* static: true (set at init below) keeps the calendar anchored
           directly under its input so it scrolls with the page instead of
           floating fixed over the viewport. Compact sizing on top of the
           Material Blue theme to match this app's density. */
        .flatpickr-calendar { font-size: 13px; border-radius: 8px; }
        .flatpickr-day { height: 32px; line-height: 32px; }
        .flatpickr-months .flatpickr-month { height: 34px; }
        .flatpickr-current-month { font-size: 13px; padding-top: 3px; }
        .flatpickr-weekdays { height: 24px; }
        span.flatpickr-weekday { font-size: 11px; }
        .numInputWrapper { height: 24px; }
    </style>

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
    @unless (View::hasSection('hide-sidebar'))
    {{-- Sidebar --}}
    @include('partials._sidebar')

    {{-- Backdrop for mobile --}}
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    @endunless

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
                    {{--
                        Back button disabled system-wide, per request — every
                        page used to get one from here (single shared
                        location, not per-view) via either an explicit
                        @section('back-url', ...) or a browser history.back()
                        fallback. Uncomment to restore.
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
                    --}}
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

{{-- Flatpickr — system-wide date picker replacement --}}
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
// Replaces every native input[type=date] on the page with Flatpickr — one
// shared init instead of touching all date fields individually. altInput
// shows a readable "d-M-Y" while the real input (submitted to the server)
// keeps the Y-m-d format every controller already expects, so no backend
// changes are needed. Re-run on Bootstrap modals when they're shown, since
// dynamically-inserted date inputs (e.g. the biometric upload preview modal)
// wouldn't exist yet at page load.
function initFlatpickrOn(root) {
    (root || document).querySelectorAll('input[type="date"]:not([data-fp-init])').forEach(function (el) {
        el.setAttribute('data-fp-init', '1');
        flatpickr(el, {
            altInput: true,
            altFormat: 'd-M-Y',
            dateFormat: 'Y-m-d',
            allowInput: true,
            // static: true renders the calendar as a sibling of the input
            // (anchored to it, scrolls with the page) instead of appending
            // it to <body> as a viewport-fixed element that visually
            // "floats"/detaches from the field when the page scrolls.
            static: true,
            disableMobile: true,
        });
    });
}
initFlatpickrOn(document);
document.addEventListener('shown.bs.modal', function (e) { initFlatpickrOn(e.target); });
</script>

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

/**
 * Client-side toast helper — for validation/feedback that happens before any
 * request is sent (e.g. a multi-step wizard's "Next" button), reusing the
 * same top-right .toast-container the server-rendered session flashes use
 * (see resources/views/partials/_alerts.blade.php), so both look identical.
 * type: 'success' | 'danger' | 'warning' | 'info'
 */
window.showToast = function (message, type = 'warning') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1080';
        document.body.appendChild(container);
    }
    const icon = { success: 'check-circle', danger: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' }[type] || 'info-circle';
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${type} border-0 shadow`;
    el.setAttribute('role', 'alert');
    el.setAttribute('data-bs-autohide', 'true');
    el.setAttribute('data-bs-delay', '5000');
    el.innerHTML = `<div class="d-flex">
        <div class="toast-body"><i class="bi bi-${icon} me-2"></i>${message}</div>
        <button type="button" class="btn-close ${type === 'warning' ? '' : 'btn-close-white'} me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
    container.appendChild(el);
    const toast = bootstrap.Toast.getOrCreateInstance(el);
    toast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
};

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
