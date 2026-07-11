<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-2 sticky-top">
    <div class="d-flex align-items-center">
        <button class="btn btn-link p-0 me-3 text-secondary" id="sidebarToggle" title="Toggle sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>

        {{-- Breadcrumbs --}}
        @if(isset($breadcrumbs))
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Home</a></li>
                @foreach($breadcrumbs as $label => $url)
                    @if($loop->last)
                        <li class="breadcrumb-item active">{{ $label }}</li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ $url }}" class="text-decoration-none">{{ $label }}</a></li>
                    @endif
                @endforeach
            </ol>
        </nav>
        @endif
    </div>

    <div class="ms-auto d-flex align-items-center gap-3">
        {{-- Quick date --}}
        <span class="text-muted d-none d-md-inline" style="font-size:13px;">
            <i class="bi bi-calendar3 me-1"></i>{{ now()->format('D, d M Y') }}
        </span>

        {{-- Branch Switcher — Super Admin only. Renders nothing for anyone
             else, so it has zero visual/behavioral effect on other accounts. --}}
        @if (auth()->user()->isSuperAdmin())
            @php
                $navbarBranches = \App\Models\Branch::active()->orderBy('name')->get();
                $navbarCurrentBranchId = session('current_branch_id');
            @endphp
            <form action="{{ route('branch-admin.branch-switcher.switch') }}" method="POST" class="d-none d-md-flex align-items-center gap-1">
                @csrf
                <i class="bi bi-arrow-left-right text-muted" style="font-size:13px;"></i>
                <select name="branch_id" class="form-select form-select-sm" style="width:auto;font-size:12.5px;" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach ($navbarBranches as $navbarBranch)
                        <option value="{{ $navbarBranch->id }}" {{ (string) $navbarCurrentBranchId === (string) $navbarBranch->id ? 'selected' : '' }}>
                            {{ $navbarBranch->name }} ({{ $navbarBranch->code }})
                        </option>
                    @endforeach
                </select>
            </form>
        @endif

        {{-- User dropdown --}}
        <div class="dropdown">
            <button class="btn btn-link p-0 d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                    <span class="text-white fw-bold" style="font-size:12px;">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                </div>
                <span class="ms-2 d-none d-md-inline text-dark">{{ auth()->user()->name }}</span>
                <i class="bi bi-chevron-down ms-1 text-secondary" style="font-size:11px;"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->email }}</span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-left me-2"></i>Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
