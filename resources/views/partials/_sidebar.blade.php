{{--
    File: resources/views/partials/_sidebar.blade.php
    Purpose: Main sidebar navigation with dark navy gradient design and collapsible sections
    Author: System
    Date: 2026-06-30
--}}

<style>
    /* ═══════════════════════════════════════════════════════════
   SIDEBAR — Dark Navy Enterprise Design
   ═══════════════════════════════════════════════════════════ */

    :root {
        --sb-bg-start: #0d1433;
        --sb-bg-end: #1a2248;
        --sb-accent: #4f7ef8;
        --sb-accent-glow: rgba(79, 126, 248, 0.18);
        --sb-text: #c8d0e7;
        --sb-text-muted: #6b7a99;
        --sb-hover-bg: rgba(255, 255, 255, 0.06);
        --sb-active-bg: rgba(79, 126, 248, 0.18);
        --sb-active-border: #4f7ef8;
        --sb-divider: rgba(255, 255, 255, 0.08);
        --sb-section-cap: #4a5578;
        --sb-radius: 8px;
        --sb-width: 260px;
    }

    /* ── Wrapper ── */
    #sidebar {
        width: var(--sb-width);
        min-width: var(--sb-width);
        height: 100vh;
        background: linear-gradient(180deg, var(--sb-bg-start) 0%, var(--sb-bg-end) 100%);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 1040;
        flex-shrink: 0;
        border-right: 1px solid rgba(255, 255, 255, .04);
    }

    /* ── Brand ── */
    .sb-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 20px 18px 16px;
        text-decoration: none;
    }

    .sb-brand-icon {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #4f7ef8, #6c4ff8);
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(79, 126, 248, 0.35);
    }

    .sb-brand-name {
        font-size: 15px;
        font-weight: 700;
        color: #fff;
        letter-spacing: .02em;
    }

    .sb-brand-sub {
        font-size: 10px;
        color: var(--sb-text-muted);
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    /* ── User card ── */
    .sb-user {
        margin: 0 12px 4px;
        padding: 10px 12px;
        background: rgba(255, 255, 255, .05);
        border-radius: var(--sb-radius);
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid rgba(255, 255, 255, .06);
    }

    .sb-user-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4f7ef8, #6c4ff8);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
    }

    .sb-user-name {
        font-size: 13px;
        font-weight: 600;
        color: #e8ecf4;
        line-height: 1.3;
    }

    .sb-user-role {
        font-size: 11px;
        color: var(--sb-text-muted);
        text-transform: capitalize;
    }

    /* ── Divider ── */
    .sb-divider {
        height: 1px;
        background: var(--sb-divider);
        margin: 10px 14px;
    }

    /* ── Nav body (scrollable) ── */
    .sb-nav {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 4px 10px;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, .08) transparent;
    }

    .sb-nav::-webkit-scrollbar {
        width: 4px;
    }

    .sb-nav::-webkit-scrollbar-track {
        background: transparent;
    }

    .sb-nav::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, .12);
        border-radius: 4px;
    }

    .sb-nav::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, .2);
    }

    /* ── Section label ── */
    .sb-section-label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--sb-section-cap);
        padding: 14px 10px 4px;
        user-select: none;
    }

    .sb-section-label::before {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--sb-divider);
        display: none;
        /* kept minimal — label only */
    }

    /* ── Nav link base ── */
    .sb-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        border-radius: var(--sb-radius);
        color: var(--sb-text);
        text-decoration: none;
        font-size: 13.5px;
        font-weight: 500;
        transition: background .15s ease, color .15s ease, border-color .15s ease;
        position: relative;
        margin-bottom: 2px;
        border-left: 3px solid transparent;
    }

    .sb-link i {
        font-size: 15px;
        width: 18px;
        text-align: center;
        flex-shrink: 0;
        opacity: .75;
        transition: opacity .15s;
    }

    .sb-link:hover {
        background: var(--sb-hover-bg);
        color: #fff;
    }

    .sb-link:hover i {
        opacity: 1;
    }

    .sb-link.active {
        background: var(--sb-active-bg);
        color: #fff;
        border-left-color: var(--sb-active-border);
        font-weight: 600;
    }

    .sb-link.active i {
        opacity: 1;
        color: var(--sb-accent);
    }

    /* ── Sub-links (inside collapse) ── */
    .sb-sub-nav {
        padding-left: 10px;
    }

    .sb-sub-link {
        font-size: 13px;
        padding: 7px 12px 7px 14px;
    }

    .sb-sub-link i {
        font-size: 14px;
    }

    /* ── Sub-group label inside Masters ── */
    .sb-sub-group-label {
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--sb-section-cap);
        padding: 10px 14px 3px;
        opacity: .7;
    }

    /* ── Collapse trigger (SYSTEM ADMIN style) ── */
    .sb-collapse-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 9px 12px;
        border-radius: var(--sb-radius);
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, .08);
        color: var(--sb-text);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .09em;
        text-transform: uppercase;
        cursor: pointer;
        transition: background .15s, color .15s;
        margin-bottom: 4px;
    }

    .sb-collapse-btn:hover {
        background: rgba(255, 255, 255, .09);
        color: #fff;
    }

    .sb-collapse-btn.collapsed {
        background: transparent;
        border-color: transparent;
    }

    .sb-collapse-btn.collapsed:hover {
        background: var(--sb-hover-bg);
        border-color: rgba(255, 255, 255, .06);
    }

    .sb-collapse-btn .sb-section-icon {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .sb-collapse-btn .sb-section-icon i {
        font-size: 15px;
        opacity: .8;
    }

    .sb-collapse-chevron {
        font-size: 11px;
        transition: transform .25s ease;
        opacity: .6;
    }

    .sb-collapse-btn.collapsed .sb-collapse-chevron {
        transform: rotate(-90deg);
    }

    /* ── Footer / logout (sticky bottom) ── */
    .sb-footer {
        padding: 10px 10px 16px;
        flex-shrink: 0;
    }

    .sb-logout {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 9px 14px;
        border-radius: var(--sb-radius);
        background: rgba(255, 255, 255, .04);
        border: 1px solid rgba(255, 255, 255, .08);
        color: #9daac5;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background .15s, color .15s, border-color .15s;
    }

    .sb-logout:hover {
        background: rgba(229, 62, 62, 0.14);
        border-color: rgba(229, 62, 62, 0.3);
        color: #fc8181;
    }

    .sb-logout i {
        font-size: 15px;
    }

    /* ── Mobile ── */
    @media (max-width: 991.98px) {
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            transform: translateX(-100%);
            transition: transform .25s ease;
            height: 100dvh;
        }

        #sidebar.show {
            transform: translateX(0);
        }
    }
</style>

@php
    $user = auth()->user();
    $initials = strtoupper(substr($user->name, 0, 2));
    $roleName = $user->role->name ?? 'user';

    /* ── Route active helpers ────────────────────────────────────── */
    $isDashboard = request()->routeIs('dashboard');
    $isBranchDashboard = request()->routeIs('dashboard.branch');
    $isEmployees = request()->routeIs('employees.*');
    $isEmployeeDocument = request()->routeIs('employee-document.*');
    $isAttendance = request()->routeIs('attendance.*');
    $isLeaves = request()->routeIs('leaves.*');
    $isPayroll = request()->routeIs('payroll.*');
    $isReports = request()->routeIs('reports.*');
    $isMasters = request()->routeIs('masters.*');
    $isUsers = request()->routeIs('users.*');
    $isRoles = request()->routeIs('admin.roles.*');
    $isRolePerms = request()->routeIs('admin.role-permissions.*');
    $isSettings = request()->routeIs('settings.*');
    $isRuleEngine = request()->routeIs('rule-engine.*');
    $isProfile = request()->routeIs('profile.*');
    $isContractAttendance = request()->routeIs('contract-attendance.*');
    $isContractPayroll = request()->routeIs('contract-payroll.*');
    $isContractLabour = request()->routeIs('contract-labour.*');

    /* ── Permission map — module.read is the sidebar gate ──────────
     *  Hierarchy in Gate: delete > full > create > read
     *  So having 'employees.full' also passes 'employees.read' check.
     *  Module keys come from config/menu_modules.php (the same registry
     *  that drives the Role Permissions admin page) so this list can
     *  never drift out of sync with what's assignable. Dashboard/Branch
     *  Dashboard now participate in this same map (previously Dashboard
     *  was hard-excluded and rendered unconditionally, back when its route
     *  had no permission gate at all — both are gated now).
 *  Masters has no permission of its own — any one masters_* sub-screen
 *  permission unlocks the whole Masters section, and every sub-screen
 *  is shown unconditionally underneath, matching the original design.
 * ─────────────────────────────────────────────────────────── */
$can = collect(config('menu_modules'))
    ->keys()
    ->mapWithKeys(fn($module) => [$module => $user->can("$module.read")])
    ->all();

$mastersModuleKeys = collect(config('menu_modules'))
    ->keys()
    ->filter(fn($module) => str_starts_with($module, 'masters_'))
    ->all();

/* ── Section visibility ──────────────────────────────────────── */
$showSysAdmin = $can['users'] || $can['roles'] || $can['role_permissions'] || $can['settings'] || $can['rule_engine'];
$showMasters = collect($mastersModuleKeys)->contains(fn($module) => $can[$module]);
$showPeople = $can['employees'];
$showTimeLeave = $can['attendance'] || $can['leaves'];
$showPayroll = $can['payroll'];
$showInsights = $can['reports'];
$showContractMgmt = $can['masters_contractors'] || $can['attendance'] || $can['payroll'];

/* ── Collapse state ──────────────────────────────────────────── */
$sysAdminOpen = $showSysAdmin && ($isUsers || $isRoles || $isRolePerms || $isSettings || $isRuleEngine);
$mastersOpen = $showMasters && $isMasters;

/* ── Branch Administration — only the features with no existing-module
 *  equivalent live here. Branches/Users/Roles are reached via
 *  Masters ▸ Branches and System Admin ▸ Users/Roles/Role Permissions —
 *  no duplicate menu items for the same underlying data.
 * ─────────────────────────────────────────────────────────── */
$isBranchAdminHeadAssignments = request()->routeIs('branch-admin.head-assignments.*');
$isBranchAdminSwitcher = request()->routeIs('branch-admin.branch-switcher.*');
$isBranchAdminAuditLog = request()->routeIs('branch-admin.audit-log.*');
$showBranchAdmin =
    $can['branch_admin_head_assignments'] || $can['branch_admin_switcher'] || $can['branch_admin_audit_log'];
    $branchAdminOpen =
        $showBranchAdmin && ($isBranchAdminHeadAssignments || $isBranchAdminSwitcher || $isBranchAdminAuditLog);
@endphp

<nav id="sidebar">

    {{-- ── Brand ─────────────────────────────────────────────── --}}
    <a href="{{ route('dashboard') }}" class="sb-brand">
        <div class="sb-brand-icon"><i class="bi bi-building-fill"></i></div>
        <div>
            <div class="sb-brand-name">{{ config('app.name', 'HRMS') }}</div>
            <div class="sb-brand-sub">HR Management</div>
        </div>
    </a>

    {{-- ── User info ───────────────────────────────────────────── --}}
    <div class="sb-user">
        <div class="sb-user-avatar">{{ $initials }}</div>
        <div>
            <div class="sb-user-name">{{ $user->name }}</div>
            <div class="sb-user-role">{{ str_replace('_', ' ', $roleName) }}</div>
        </div>
    </div>

    <div class="sb-divider"></div>

    {{-- ── Navigation ──────────────────────────────────────────── --}}
    <div class="sb-nav">

        {{-- Overall Dashboard — gated like every other module now. --}}
        @if ($can['dashboard'])
            <a href="{{ route('dashboard') }}" class="sb-link {{ $isDashboard ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        @endif

        {{-- Branch Dashboard — single-branch KPIs/charts. --}}
        @if ($can['branch_dashboard'])
            <a href="{{ route('dashboard.branch') }}" class="sb-link {{ $isBranchDashboard ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i>
                <span>Branch Dashboard</span>
            </a>
        @endif

        {{-- Shared accordion parent — System Admin / Masters / Branch
             Administration are the only 3 collapsible sections in the
             sidebar; data-bs-parent (on each .collapse div below) makes
             Bootstrap auto-close whichever of the OTHER two is open
             whenever one is expanded, so at most one stays open at once. --}}
        <div id="sidebarAccordion">

        {{-- ── SYSTEM ADMIN (collapsible) ──────────────────────── --}}
        @if ($showSysAdmin)
            <div class="mt-2">
                <button class="sb-collapse-btn {{ $sysAdminOpen ? '' : 'collapsed' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#sysAdminMenu"
                    aria-expanded="{{ $sysAdminOpen ? 'true' : 'false' }}">
                    <span class="sb-section-icon">
                        <i class="bi bi-tools"></i>
                        <span>System Admin</span>
                    </span>
                    <i class="bi bi-chevron-up sb-collapse-chevron"></i>
                </button>

                <div class="collapse {{ $sysAdminOpen ? 'show' : '' }}" id="sysAdminMenu" data-bs-parent="#sidebarAccordion">
                    <div class="sb-sub-nav">
                        @if ($can['users'])
                            <a href="{{ route('users.index') }}"
                                class="sb-link sb-sub-link {{ $isUsers ? 'active' : '' }}">
                                <i class="bi bi-people"></i>
                                <span>Users</span>
                            </a>
                        @endif
                        @if ($can['roles'])
                            <a href="{{ route('admin.roles.index') }}"
                                class="sb-link sb-sub-link {{ $isRoles ? 'active' : '' }}">
                                <i class="bi bi-shield-check"></i>
                                <span>Roles</span>
                            </a>
                        @endif
                        @if ($can['role_permissions'])
                            <a href="{{ route('admin.role-permissions.index') }}"
                                class="sb-link sb-sub-link {{ $isRolePerms ? 'active' : '' }}">
                                <i class="bi bi-person-lock"></i>
                                <span>Role Permissions</span>
                            </a>
                        @endif
                        @if ($can['settings'])
                            <a href="{{ route('settings.index') }}"
                                class="sb-link sb-sub-link {{ $isSettings ? 'active' : '' }}">
                                <i class="bi bi-sliders"></i>
                                <span>Settings</span>
                            </a>
                        @endif
                        @if ($can['rule_engine'])
                            <a href="{{ route('rule-engine.index') }}"
                                class="sb-link sb-sub-link {{ $isRuleEngine ? 'active' : '' }}">
                                <i class="bi bi-diagram-3-fill"></i>
                                <span>Rule Engine</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- ── MASTERS (collapsible) ────────────────────────────── --}}
        @if ($showMasters)
            <div class="mt-2">
                <button class="sb-collapse-btn {{ $mastersOpen ? '' : 'collapsed' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#mastersMenu"
                    aria-expanded="{{ $mastersOpen ? 'true' : 'false' }}">
                    <span class="sb-section-icon">
                        <i class="bi bi-collection"></i>
                        <span>Masters</span>
                    </span>
                    <i class="bi bi-chevron-up sb-collapse-chevron"></i>
                </button>

                <div class="collapse {{ $mastersOpen ? 'show' : '' }}" id="mastersMenu" data-bs-parent="#sidebarAccordion">
                    <div class="sb-sub-nav">
                        {{-- Organisation — each sub-link is gated by its own module
                             permission (masters_branches, masters_departments, ...)
                             so a role that only has, say, masters_departments never
                             sees links to screens it cannot actually open. --}}
                        <div class="sb-sub-group-label">Organisation</div>
                        {{-- Branch Management is Super-Admin-only by design (never
                             permission-driven), so no accidental role-permission
                             grant can surface this link for anyone else. --}}
                        @if ($user->isSuperAdmin())
                            <a href="{{ route('masters.branches.index') }}"
                                class="sb-link sb-sub-link {{ request()->routeIs('masters.branches.*') ? 'active' : '' }}">
                                <i class="bi bi-building"></i><span>Branches</span>
                            </a>
                        @endif
                        @if ($can['masters_departments'])
                            <a href="{{ route('masters.departments.index') }}"
                                class="sb-link sb-sub-link {{ request()->routeIs('masters.departments.*') ? 'active' : '' }}">
                                <i class="bi bi-diagram-3"></i><span>Departments</span>
                            </a>
                        @endif
                        {{-- Designations is temporarily hidden from the Masters menu
                             (UI only — routes/controller/permissions/data are untouched
                             and this link can be restored by uncommenting it). --}}
                        {{--
                        @if ($can['masters_designations'])
                            <a href="{{ route('masters.designations.index') }}"
                                class="sb-link sb-sub-link {{ request()->routeIs('masters.designations.*') ? 'active' : '' }}">
                                <i class="bi bi-person-badge"></i><span>Designations</span>
                            </a>
                        @endif
                        --}}
                        {{-- Employee Types is temporarily hidden (config/features.php:
                             employee_types_enabled) — flip that flag back to true to
                             restore this nav link; the module itself is untouched. --}}
                        @if ($can['masters_employee_types'] && config('features.employee_types_enabled', false))
                            <a href="{{ route('masters.employee-types.index') }}"
                                class="sb-link sb-sub-link {{ request()->routeIs('masters.employee-types.*') ? 'active' : '' }}">
                                <i class="bi bi-person-gear"></i><span>Employee Types</span>
                            </a>
                        @endif

                        {{-- HR Policy --}}
                        @if ($can['masters_shifts'] || $can['masters_holidays'] || $can['masters_leave_types'])
                            <div class="sb-sub-group-label">HR Policy</div>
                        @endif
                        @if ($can['masters_shifts'])
                            <a href="{{ route('masters.shifts.index') }}"
                                class="sb-link sb-sub-link {{ request()->routeIs('masters.shifts.*') ? 'active' : '' }}">
                                <i class="bi bi-clock"></i><span>Shifts</span>
                            </a>
                        @endif
                        @if ($can['masters_holidays'])
                            <a href="{{ route('masters.holidays.index') }}"
                                class="sb-link sb-sub-link {{ request()->routeIs('masters.holidays.*') ? 'active' : '' }}">
                                <i class="bi bi-calendar-heart"></i><span>Holidays</span>
                            </a>
                        @endif
                        @if ($can['masters_leave_types'])
                            <a href="{{ route('masters.leave-types.index') }}"
                                class="sb-link sb-sub-link {{ request()->routeIs('masters.leave-types.*') ? 'active' : '' }}">
                                <i class="bi bi-calendar-minus"></i><span>Leave Types</span>
                            </a>
                        @endif
                        {{-- Payroll --}}
                        @if (
                            $can['masters_salary_slabs'] ||
                                $can['masters_earnings'] ||
                                $can['masters_banks']
                        )
                            <div class="sb-sub-group-label">Payroll</div>
                        @endif
                        @if ($can['masters_salary_slabs'])
                            <a href="{{ route('masters.salary-slabs.index') }}"
                                class="sb-link sb-sub-link {{ request()->routeIs('masters.salary-slabs.*') ? 'active' : '' }}">
                                <i class="bi bi-layers"></i><span>Salary Slabs</span>
                            </a>
                        @endif
                        @if ($can['masters_banks'])
                            <a href="{{ route('masters.banks.index') }}"
                                class="sb-link sb-sub-link {{ request()->routeIs('masters.banks.*') ? 'active' : '' }}">
                                <i class="bi bi-bank"></i><span>Banks</span>
                            </a>
                        @endif
                        @if ($can['masters_earnings'])
                            <a href="{{ route('masters.earnings.index') }}"
                                class="sb-link sb-sub-link {{ request()->routeIs('masters.earnings.*') ? 'active' : '' }}">
                                <i class="bi bi-graph-up-arrow"></i><span>Earnings</span>
                            </a>
                        @endif
                        {{-- Operations --}}
                        @if ($can['masters_contractors'])
                            <div class="sb-sub-group-label">Operations</div>
                            <a href="{{ route('masters.contractors.index') }}"
                                class="sb-link sb-sub-link {{ request()->routeIs('masters.contractors.*') ? 'active' : '' }}">
                                <i class="bi bi-person-workspace"></i><span>Contractors</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{--
            Branch Administration group disabled per request:
            - Branch Head Assignment: commented out below
            - Branch Switcher: commented out below
            - The "Branch Administration" section/label itself removed
            - Audit Log moved to its own standalone link after Reports
              (see the Insights section further down)
        @if ($showBranchAdmin)
            <div class="mt-2">
                <button class="sb-collapse-btn {{ $branchAdminOpen ? '' : 'collapsed' }}" type="button"
                    data-bs-toggle="collapse" data-bs-target="#branchAdminMenu"
                    aria-expanded="{{ $branchAdminOpen ? 'true' : 'false' }}">
                    <span class="sb-section-icon">
                        <i class="bi bi-diagram-2"></i>
                        <span>Branch Administration</span>
                    </span>
                    <i class="bi bi-chevron-up sb-collapse-chevron"></i>
                </button>

                <div class="collapse {{ $branchAdminOpen ? 'show' : '' }}" id="branchAdminMenu" data-bs-parent="#sidebarAccordion">
                    <div class="sb-sub-nav">
                        @if ($can['branch_admin_head_assignments'])
                            <a href="{{ route('branch-admin.head-assignments.index') }}"
                                class="sb-link sb-sub-link {{ $isBranchAdminHeadAssignments ? 'active' : '' }}">
                                <i class="bi bi-person-badge"></i><span>Branch Head Assignment</span>
                            </a>
                        @endif
                        @if ($can['branch_admin_switcher'])
                            <a href="{{ route('branch-admin.branch-switcher.index') }}"
                                class="sb-link sb-sub-link {{ $isBranchAdminSwitcher ? 'active' : '' }}">
                                <i class="bi bi-arrow-left-right"></i><span>Branch Switcher</span>
                            </a>
                        @endif
                        @if ($can['branch_admin_audit_log'])
                            <a href="{{ route('branch-admin.audit-log.index') }}"
                                class="sb-link sb-sub-link {{ $isBranchAdminAuditLog ? 'active' : '' }}">
                                <i class="bi bi-journal-text"></i><span>Audit Log</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        --}}

        </div>{{-- /#sidebarAccordion --}}

        {{-- ── People ───────────────────────────────────────────── --}}
        @if ($showPeople)
            <div class="sb-section-label"><i class="bi bi-people-fill"></i> People</div>
            <a href="{{ route('employees.index') }}" class="sb-link {{ $isEmployees ? 'active' : '' }}">
                <i class="bi bi-person-vcard"></i>
                <span>Create Employee</span>
            </a>
            <a href="{{ route('employee-document.index') }}" class="sb-link {{ $isEmployeeDocument ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text"></i>
                <span>Employee Document</span>
            </a>
        @endif

        {{-- ── Time & Leave ─────────────────────────────────────── --}}
        @if ($showTimeLeave)
            <div class="sb-section-label"><i class="bi bi-clock-history"></i> Time & Leave</div>
            @if ($can['attendance'])
                <a href="{{ route('attendance.index') }}" class="sb-link {{ $isAttendance ? 'active' : '' }}">
                    <i class="bi bi-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            @endif
            @if ($can['leaves'])
                <a href="{{ route('leaves.index') }}" class="sb-link {{ $isLeaves ? 'active' : '' }}">
                    <i class="bi bi-calendar-x"></i>
                    <span>Leave</span>
                </a>
            @endif
        @endif

        {{-- ── Contract Mgmt ───────────────────────────────────── --}}
        @if ($showContractMgmt)
            <div class="sb-section-label"><i class="bi bi-person-workspace"></i> Contract Mgmt</div>
            @if ($can['masters_contractors'])
                <a href="{{ route('contract-labour.index') }}"
                    class="sb-link {{ $isContractLabour ? 'active' : '' }}">
                    <i class="bi bi-person-check"></i>
                    <span>Contract Labour</span>
                </a>
            @endif
            @if ($can['attendance'])
                <a href="{{ route('contract-attendance.index') }}"
                    class="sb-link {{ $isContractAttendance ? 'active' : '' }}">
                    <i class="bi bi-calendar2-check"></i>
                    <span>Contract Attendance</span>
                </a>
            @endif
            @if ($can['payroll'])
                <a href="{{ route('contract-payroll.index') }}"
                    class="sb-link {{ $isContractPayroll ? 'active' : '' }}">
                    <i class="bi bi-cash-coin"></i>
                    <span>Contract Payroll</span>
                </a>
            @endif
        @endif

        {{-- ── Payroll ──────────────────────────────────────────── --}}
        @if ($showPayroll)
            <div class="sb-section-label"><i class="bi bi-cash-coin"></i> Payroll</div>
            <a href="{{ route('payroll.index') }}" class="sb-link {{ $isPayroll ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i>
                <span>Payroll</span>
            </a>
        @endif

        {{-- ── Insights ─────────────────────────────────────────── --}}
        @if ($showInsights || $can['branch_admin_audit_log'])
            <div class="sb-section-label"><i class="bi bi-graph-up"></i> Insights</div>
            @if ($showInsights)
            <a href="{{ route('reports.index') }}" class="sb-link {{ $isReports ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i>
                <span>Reports</span>
            </a>
            @endif
            @if ($can['branch_admin_audit_log'])
            <a href="{{ route('branch-admin.audit-log.index') }}" class="sb-link {{ $isBranchAdminAuditLog ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i>
                <span>Audit Log</span>
            </a>
            @endif
        @endif

        {{-- ── Profile — always visible ─────────────────────────── --}}
        <div class="sb-section-label"><i class="bi bi-gear"></i> Account</div>
        <a href="{{ route('profile.edit') }}" class="sb-link {{ $isProfile ? 'active' : '' }}">
            <i class="bi bi-person-circle"></i>
            <span>My Profile</span>
        </a>

    </div>

    <div class="sb-divider"></div>

    {{-- ── Logout ───────────────────────────────────────────────── --}}
    <div class="sb-footer">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="sb-logout">
                <i class="bi bi-box-arrow-left"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>

</nav>
