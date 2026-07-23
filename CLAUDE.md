# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Padmam HRMS — a Laravel 12 (PHP 8.2) HR/payroll management system for a multi-branch organization, covering employees, attendance (incl. biometric upload), leave, payroll (regular + contract labour), a generic report engine, and a custom role/permission + branch-scoping authorization layer. Server-rendered with Blade + Bootstrap 5 (not Tailwind components — Tailwind is wired via Vite but the UI convention is Bootstrap), Vite for asset building.

## Commands

```bash
# Install PHP deps, generate key, run migrations, install/build JS assets
composer run setup

# Local dev — runs php artisan serve + queue:listen + pail (log viewer) + vite, concurrently
composer run dev

# Run the full test suite (clears config cache first)
composer run test
# equivalent to:
php artisan config:clear && php artisan test

# Run a single test file / method
php artisan test tests/Feature/DashboardTest.php
php artisan test --filter=test_method_name

# Frontend asset build
npm run dev      # vite dev server
npm run build     # production build

# Code style (Laravel Pint)
vendor/bin/pint
vendor/bin/pint --test   # check only, no changes

# Migrations / seeders
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed --class=RoleSeeder
```

Tests run against an in-memory SQLite DB (`phpunit.xml`), not the dev DB — safe to run anytime.

## Architecture

### Permission model: module + access level, not granular actions

There is no traditional per-action permission list. Instead every feature area is a **module** (e.g. `employees`, `attendance`, `masters_departments`, `payroll`, `rule_engine`) with exactly three **access levels**, defined once in `App\Models\Permission::ACCESS_LEVELS`:

- `read` (1) — view/list
- `create` (2) — add new records (implies read)
- `full` (3) — edit/delete/approve/cancel/etc. (implies create)

`AppServiceProvider::boot()` registers a `Gate::before` that implements this hierarchy for every ability check app-wide: a role granted `attendance.full` automatically passes `attendance.read` and `attendance.create` checks too. `super_admin` bypasses all checks unconditionally in the same `Gate::before`.

Routes are gated per-action to match this hierarchy (see the long comment block at the top of the authenticated route group in [routes/web.php](routes/web.php)) — e.g. `index/create/edit/show` need `.read`, `store` needs `.create`, `update/destroy` need `.full`. When adding a new route, follow this same split; don't gate an entire resource behind one blanket `.read` check.

`config/menu_modules.php` is the **single source of truth for sidebar modules** — it drives the sidebar nav, the Permissions admin page, and the Role Permissions assignment grid simultaneously. Add a module there once and `Permission::syncModules()` self-heals the `permissions` table (idempotent, called per-request) — no manual reseed needed.

Users can now hold **multiple roles** (`role_user` pivot, `User::roles()`/`allRoles()`); permission checks always union across every assigned role, not just the legacy singular `role_id`/`role()`. `User::allRoles()` falls back to the singular role if the pivot is empty, so pre-migration single-role accounts behave unchanged.

Middleware: `App\Http\Middleware\CheckPermission` (`->middleware('permission:module.level')`, comma-separated = OR), `CheckRole` (`->middleware('role:super_admin')` — used only where gating must be un-overridable by the permission system, e.g. Branch Management), `EnsureFeatureEnabled` (`->middleware('feature:flag_name')`, backed by `config/features.php` — used to hide a whole module from UI *and* routes without touching its controller/model/schema).

### Branch scoping (multi-branch data isolation)

`App\Support\BranchScope` resolves "the effective branch" for the current request/user and is the mechanism every branch-aware controller uses to filter queries and stamp writes:

- Branch-scoped users (`user_type` = `branch_head`/`branch_user` with a `branch_id`) are always locked to their own branch.
- Super Admin is **never** branch-scoped for authorization purposes, but always has exactly one branch "in effect" for data entry (no "All Branches" mode) — selected via the Branch Switcher (`session('current_branch_id')`), falling back to the first active branch.
- Legacy accounts (`user_type` null, pre-dating this module) get `currentBranchId() === null` everywhere, making every `BranchScope` method a no-op — behavior for them is unchanged.

Key methods: `scopeQuery()` / `scopeQueryVia()` (filter a query by branch, directly or through a relation), `scopeQueryIncludingGlobal()` (branch match OR null — for records like national Holidays that apply everywhere), `stampBranchId()` (force `branch_id` on write to the current effective branch, ignoring client input), `assertBranchAccess()` / `assertBranchIsActive()` (403/422 guards). `authorizedBranchIds()` is a separate, broader concept used only by the Dashboard controllers (which branches a user may *view*, via the `user_branches` pivot) — don't conflate it with `currentBranchId()`.

### Rule Engine (Module 4)

`app/Http/Controllers/RuleEngine/RuleController.php` + `App\Support\RuleEngine` implement a single, category-driven configuration screen (not one form per rule type) covering PF/ESI/TDS rounding rules, LOP, overtime, weekly-off, and employee-number-format rules. `App\Support\RuleEngine` holds pure, stateless calculation helpers (statutory rounding, minute rounding, financial-year labeling) shared between `PayrollController` and `EmployeeNumberGenerator` — nothing there is persisted, so keep it as static helpers rather than a model.

### Generic report engine (Module 10)

Beyond the ~9 hand-built report pages in `ReportController`, ~60 additional reports are served through one generic engine:

- `App\Support\Reports\Definitions\*ReportDefinitions` — one file per FSD subsection (Employee, Attendance, Leave/LOP, Payroll, Statutory, Contractor), each returning an array of `ReportDefinition`s. Add a new report by adding an entry to the matching definitions file, not by writing a new controller.
- `App\Support\Reports\ReportRegistry` — merges all definition files into one flat, key-addressable registry (`find($key)`, `bySection()`).
- `ReportFilterApplier`, `ReportColumnRenderer`, `ReportMasking` — shared filter/render/PII-masking logic applied uniformly across every generic report.
- Routes: `GET /reports/view/{key}` + Excel/PDF export variants, handled by `GenericReportController`.

### Controller organization

- `app/Http/Controllers/Masters/*` — CRUD for master data (Branch, Department, Designation, Shift, Holiday, LeaveType, EmployeeType, Contractor, ContractWorker, Earnings/Deductions components, SalarySlab, OtRate, PfEsiConfig, Bank). All wired as `Route::resource(...)->middlewareFor(...)` with the read/create/full split described above.
- `app/Http/Controllers/Admin/*` — Role and RolePermission management.
- `app/Http/Controllers/BranchAdmin/*` — Branch Head Assignment, Branch Switcher, Branch audit log. Branches/Users/Roles/Permissions themselves are *not* duplicated here — they stay owned by the existing Masters/System Admin screens (single source of truth); only genuinely new branch-admin features live under this namespace.
- Top-level controllers (`EmployeeController`, `AttendanceController`, `ContractAttendanceController`, `LeaveController`, `PayrollController`, `ContractPayrollController`, `ReportController`) hold the main domain workflows.

### Route ordering gotcha

Several resources mix static routes with `{wildcard}` routes on the same prefix (e.g. `/attendance/mark` vs `/attendance/{attendance}`, `/leaves/balance` vs `/leaves/{leave}`). Static routes **must** stay declared before the wildcard route in `routes/web.php` or Laravel will greedily match the wildcard first.

### Auditing

`App\Models\AuditLog::write(...)` is called from sensitive support code (e.g. `BranchScope::assertBranchAccess()` on unauthorized branch access attempts) — follow this pattern when adding new sensitive/cross-branch operations rather than logging ad hoc.

### Migrations

Migrations are dated and layered incrementally (base schema from `2024_01_01_*`, then a long series of `2026_0X_XX_*` migrations adding/altering columns for specific features — Branch Administration, Rule Engine, FSD-driven field changes, etc.). When modifying a table already touched by a later migration, add a new migration rather than editing an old one.

### Staging deploy gotcha: stray renamed/deleted migration files

The `database/migrations` folder on the cPanel staging server (`~/public_html/padhmam_hrms`) can accumulate **stray files that were renamed or deleted in git history but never removed from the server's disk** — e.g. `2026_07_11_000001_seed_branch_head_and_branch_user_roles.php` was later renamed to `2026_07_11_000005_...` (content fixed to no longer insert roles directly), but the old `_000001` file kept existing on staging alongside the new one. Since Laravel runs *every* file in the folder whose name isn't yet in the `migrations` tracking table, it re-ran the old, buggy version and failed on columns that only get added by a later migration.

This is not a code bug — comparing file *contents* between local and staging isn't enough, since a rename in git can leave two files (old name + new name) sitting side by side on a server that was never `git clean`ed. When `php artisan migrate` fails with an error that doesn't match what the current file's content says, check for a same-content-different-name duplicate:

```bash
ls database/migrations | grep <topic-keyword>
git log --all --oneline -- "database/migrations/<suspected-old-filename>.php"
```

If git history shows that file was renamed or deleted in a later commit, it's safe to `rm` the stray copy on staging — it was never part of the local repo's current migration set, so a future `git pull` won't recreate it.


### Features it currently has
Login / Authentication — login, forgot password, forced password change
Dashboard — an overall dashboard and a per-branch dashboard
Employees — employee records, documents, bank details, salary structure, exit/offboarding
Attendance — manual marking, biometric Excel upload, processing, corrections, approvals, overtime approval
Leave — leave requests, approvals, leave balances, permission requests (short leave)
Payroll — payroll generation, payslips, payments, LOP (loss of pay) review, statutory exports, bank transfer files
Contract Labour — separate attendance & payroll flow for contractor-supplied workers
Masters (setup data) — Branches, Departments, Designations, Shifts, Holidays, Leave Types, Salary Slabs, Earnings/Deductions components, OT rates, PF/ESI config, Banks, Contractors
Rule Engine — one configurable screen for PF/ESI/TDS rounding rules, overtime, weekly-off, employee number formatting
Reports — ~9 built-in reports plus a generic report engine serving ~60 more (attendance, payroll, statutory, contractor, etc.), with Excel/PDF export
User & Role Management — users, roles, and a permission grid (read/create/full access per module)
Branch Administration — assigning branch heads, switching which branch a Super Admin is viewing, branch-level audit log, and data isolation so branch users only see their own branch's data
Settings — company profile and general app settings

-------------------------------
admin@hrms.com
Admin@123
------------------------------

when i run php artisan migrate:fresh --seed this run both migrations and seed both file

migration - this create db table structure only
seed - this give record to that table example role, permission, master sample data.

------------------------------

## ⚠️ BEFORE GOING LIVE — pending discussion: cron setup

The yearly leave balance reset (`app/Console/Commands/ResetLeaveBalancesForNewYear.php`, scheduled via `bootstrap/app.php`'s `withSchedule()` to run every Jan 1 at 00:00) **will not fire on its own** — Laravel's scheduler only runs when something calls `php artisan schedule:run` every minute, which on cPanel means adding a Cron Job:

```
* * * * * cd /home/<user>/public_html/padhmam_hrms && php artisan schedule:run >> /dev/null 2>&1
```

The user has a concern about this cron setup to discuss before it's finalized — **do not assume this is done or working on the live server until confirmed.** Revisit this before the production go-live date.