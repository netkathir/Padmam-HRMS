<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Masters\BranchController;
use App\Http\Controllers\Masters\DepartmentController;
use App\Http\Controllers\Masters\DesignationController;
use App\Http\Controllers\Masters\ShiftController;
use App\Http\Controllers\Masters\HolidayController;
use App\Http\Controllers\Masters\LeaveTypeController;
use App\Http\Controllers\Masters\EmployeeTypeController;
use App\Http\Controllers\Masters\ContractorController;
use App\Http\Controllers\Masters\ContractWorkerController;
use App\Http\Controllers\ContractAttendanceController;
use App\Http\Controllers\ContractPayrollController;
use App\Http\Controllers\Masters\EarningsComponentController;
use App\Http\Controllers\Masters\DeductionsComponentController;
use App\Http\Controllers\Masters\SalarySlabController;
use App\Http\Controllers\Masters\OtRateController;
use App\Http\Controllers\Masters\PfEsiConfigController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RolePermissionController;
use Illuminate\Support\Facades\Route;

// ── Redirect root to dashboard or login ──────────────────────────────────
Route::get('/', fn() => redirect()->route('dashboard'));

// ── Guest routes ─────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',                     [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login',                    [AuthController::class, 'login']);
    Route::get('/forgot-password',           [AuthController::class, 'forgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password',          [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::get('/reset-password/{token}',    [AuthController::class, 'resetPasswordForm'])->name('password.reset');
    Route::post('/reset-password',           [AuthController::class, 'resetPassword'])->name('password.store');
});

// ── Authenticated routes ──────────────────────────────────────────────────
// Every feature area below (other than Dashboard/Profile, which are always
// reachable once logged in) is wrapped in `permission:{module}.read` — the
// same module.access_level ability enforced by the Gate and shown/hidden in
// the sidebar (resources/views/partials/_sidebar.blade.php) and managed on
// the Role Permissions page. A role only reaches a route once it has been
// explicitly granted read (or higher) on that module; anyone else gets a 403
// via App\Http\Middleware\CheckPermission. super_admin bypasses all of this
// through Gate::before in AppServiceProvider.
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard — always accessible to any authenticated user, mirroring the
    // sidebar where Dashboard is never permission-gated.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile — always accessible to the account owner.
    Route::get('/profile',         [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',       [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Employees
    Route::middleware('permission:employees.read')->group(function () {
        Route::resource('employees', EmployeeController::class);
        Route::get('/employees/{employee}/documents',        [EmployeeController::class, 'documents'])->name('employees.documents');
        Route::post('/employees/{employee}/documents',       [EmployeeController::class, 'uploadDocument'])->name('employees.documents.upload');
        Route::get('/employees/{employee}/salary',           [EmployeeController::class, 'salary'])->name('employees.salary');
        Route::post('/employees/{employee}/salary',          [EmployeeController::class, 'storeSalary'])->name('employees.salary.store');
        Route::get('/employees/{employee}/exit',             [EmployeeController::class, 'exit'])->name('employees.exit');
        Route::post('/employees/{employee}/exit',            [EmployeeController::class, 'processExit'])->name('employees.exit.store');
    });

    // Attendance (Contract Attendance shares the same module, per the sidebar)
    Route::middleware('permission:attendance.read')->group(function () {
        Route::get('/attendance',                    [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/mark',               [AttendanceController::class, 'markForm'])->name('attendance.mark');
        Route::post('/attendance/mark',              [AttendanceController::class, 'mark'])->name('attendance.mark.post');
        Route::get('/attendance/manual',             [AttendanceController::class, 'manualForm'])->name('attendance.manual');
        Route::post('/attendance/manual',            [AttendanceController::class, 'manual'])->name('attendance.manual.post');
        Route::get('/attendance/pending',            [AttendanceController::class, 'pending'])->name('attendance.pending');
        Route::post('/attendance/{attendance}/approve', [AttendanceController::class, 'approve'])->name('attendance.approve');
        Route::get('/attendance/report',             [AttendanceController::class, 'report'])->name('attendance.report');

        Route::get('/contract-attendance',           [ContractAttendanceController::class, 'index'])->name('contract-attendance.index');
        Route::get('/contract-attendance/mark',      [ContractAttendanceController::class, 'markForm'])->name('contract-attendance.mark');
        Route::post('/contract-attendance/mark',     [ContractAttendanceController::class, 'mark'])->name('contract-attendance.mark.post');
        Route::get('/contract-attendance/report',    [ContractAttendanceController::class, 'report'])->name('contract-attendance.report');
    });

    // Leave — static routes MUST come before {leave} wildcard
    Route::middleware('permission:leaves.read')->group(function () {
        Route::get('/leaves',                        [LeaveController::class, 'index'])->name('leaves.index');
        Route::get('/leaves/create',                 [LeaveController::class, 'create'])->name('leaves.create');
        Route::post('/leaves',                       [LeaveController::class, 'store'])->name('leaves.store');
        Route::get('/leaves/balance',                [LeaveController::class, 'balance'])->name('leaves.balance');
        Route::get('/leaves/permissions',            [LeaveController::class, 'permissions'])->name('leaves.permissions');
        Route::post('/leaves/permissions',           [LeaveController::class, 'storePermission'])->name('leaves.permissions.store');
        Route::get('/leaves/{leave}',                [LeaveController::class, 'show'])->name('leaves.show');
        Route::post('/leaves/{leave}/approve',       [LeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('/leaves/{leave}/cancel',        [LeaveController::class, 'cancel'])->name('leaves.cancel');
    });

    // Payroll (Contract Payroll shares the same module, per the sidebar)
    Route::middleware('permission:payroll.read')->group(function () {
        Route::get('/payroll',                       [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/generate',              [PayrollController::class, 'generateForm'])->name('payroll.generate');
        Route::post('/payroll/generate',             [PayrollController::class, 'generate'])->name('payroll.generate.post');
        Route::get('/payroll/{payroll}/payslip',     [PayrollController::class, 'payslip'])->name('payroll.payslip');
        Route::get('/payroll/{payroll}/payment',     [PayrollController::class, 'paymentForm'])->name('payroll.payment');
        Route::post('/payroll/{payroll}/payment',    [PayrollController::class, 'storePayment'])->name('payroll.payment.store');

        Route::get('/contract-payroll',              [ContractPayrollController::class, 'index'])->name('contract-payroll.index');
        Route::get('/contract-payroll/calculate',    [ContractPayrollController::class, 'calculateForm'])->name('contract-payroll.calculate');
        Route::post('/contract-payroll/calculate',   [ContractPayrollController::class, 'calculate'])->name('contract-payroll.calculate.post');
        Route::get('/contract-payroll/{id}',         [ContractPayrollController::class, 'show'])->name('contract-payroll.show');
        Route::post('/contract-payroll/{id}/pay',    [ContractPayrollController::class, 'markPaid'])->name('contract-payroll.pay');
    });

    // Reports
    Route::middleware('permission:reports.read')->group(function () {
        Route::get('/reports',                       [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/attendance',            [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('/reports/employees',             [ReportController::class, 'employees'])->name('reports.employees');
        Route::get('/reports/leave',                 [ReportController::class, 'leave'])->name('reports.leave');
        Route::get('/reports/payroll',               [ReportController::class, 'payroll'])->name('reports.payroll');
        Route::get('/reports/contractor',            [ReportController::class, 'contractor'])->name('reports.contractor');
    });

    // Masters — hub page is reachable if any sub-module is readable; each
    // sub-screen below is gated by its own module (see config/menu_modules.php).
    $mastersReadAbilities = collect(config('menu_modules'))
        ->keys()
        ->filter(fn ($module) => str_starts_with($module, 'masters_'))
        ->map(fn ($module) => "$module.read")
        ->implode(',');

    Route::middleware("permission:{$mastersReadAbilities}")->group(function () {
        Route::get('/masters', [MasterController::class, 'index'])->name('masters.index');
    });

    Route::middleware('permission:masters_branches.read')->group(function () {
        Route::resource('masters/branches', BranchController::class, ['as' => 'masters'])->except('show');
    });
    Route::middleware('permission:masters_departments.read')->group(function () {
        Route::resource('masters/departments', DepartmentController::class, ['as' => 'masters'])->except('show');
    });
    Route::middleware('permission:masters_designations.read')->group(function () {
        Route::resource('masters/designations', DesignationController::class, ['as' => 'masters'])->except('show');
    });
    Route::middleware('permission:masters_employee_types.read')->group(function () {
        Route::resource('masters/employee-types', EmployeeTypeController::class, ['as' => 'masters'])->except('show');
    });
    Route::middleware('permission:masters_shifts.read')->group(function () {
        Route::resource('masters/shifts', ShiftController::class, ['as' => 'masters'])->except('show');
    });
    Route::middleware('permission:masters_holidays.read')->group(function () {
        Route::resource('masters/holidays', HolidayController::class, ['as' => 'masters'])->except('show');
    });
    Route::middleware('permission:masters_leave_types.read')->group(function () {
        Route::resource('masters/leave-types', LeaveTypeController::class, ['as' => 'masters'])->except('show');
    });
    Route::middleware('permission:masters_salary_slabs.read')->group(function () {
        Route::resource('masters/salary-slabs', SalarySlabController::class, ['as' => 'masters'])->except('show');
    });
    Route::middleware('permission:masters_earnings.read')->group(function () {
        Route::resource('masters/earnings', EarningsComponentController::class, ['as' => 'masters', 'parameters' => ['earnings' => 'earningsComponent']])->except('show');
    });
    Route::middleware('permission:masters_deductions.read')->group(function () {
        Route::resource('masters/deductions', DeductionsComponentController::class, ['as' => 'masters', 'parameters' => ['deductions' => 'deductionsComponent']])->except('show');
    });
    Route::middleware('permission:masters_ot_rates.read')->group(function () {
        Route::resource('masters/ot-rates', OtRateController::class, ['as' => 'masters'])->except('show');
    });
    Route::middleware('permission:masters_pf_esi.read')->group(function () {
        Route::resource('masters/pf-esi', PfEsiConfigController::class, ['as' => 'masters', 'parameters' => ['pf-esi' => 'pfEsiConfig']])->except('show');
    });
    Route::middleware('permission:masters_contractors.read')->group(function () {
        Route::resource('masters/contractors', ContractorController::class, ['as' => 'masters'])->except('show');
        Route::get('masters/contractors/{contractor}/labour',              [ContractorController::class, 'labour'])->name('masters.contractors.labour');
        Route::post('masters/contractors/{contractor}/labour/assign',      [ContractorController::class, 'assignLabour'])->name('masters.contractors.labour.assign');
        Route::delete('masters/contractors/{contractor}/labour/{employee}', [ContractorController::class, 'removeLabour'])->name('masters.contractors.labour.remove');
        Route::get('masters/contractors/{contractor}/attendance',                                [ContractorController::class, 'attendance'])->name('masters.contractors.attendance');
        Route::get('masters/contractors/{contractor}/payroll',                                   [ContractorController::class, 'payroll'])->name('masters.contractors.payroll');
        Route::get('masters/contractors/{contractor}/workers',                                   [ContractWorkerController::class, 'index'])->name('masters.contractors.workers.index');
        Route::get('masters/contractors/{contractor}/workers/create',                            [ContractWorkerController::class, 'create'])->name('masters.contractors.workers.create');
        Route::post('masters/contractors/{contractor}/workers',                                  [ContractWorkerController::class, 'store'])->name('masters.contractors.workers.store');
        Route::get('masters/contractors/{contractor}/workers/{contractWorker}/edit',             [ContractWorkerController::class, 'edit'])->name('masters.contractors.workers.edit');
        Route::put('masters/contractors/{contractor}/workers/{contractWorker}',                  [ContractWorkerController::class, 'update'])->name('masters.contractors.workers.update');
        Route::delete('masters/contractors/{contractor}/workers/{contractWorker}',               [ContractWorkerController::class, 'destroy'])->name('masters.contractors.workers.destroy');

        // Contract Labour Assignment shares the Contractors module
        Route::get('/contract-labour', [ContractorController::class, 'contractLabourIndex'])->name('contract-labour.index');
    });

    // Users
    Route::middleware('permission:users.read')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::get('/users/{user}/permissions',       [UserController::class, 'permissions'])->name('users.permissions');
        Route::put('/users/{user}/permissions',       [UserController::class, 'updatePermissions'])->name('users.permissions.update');
    });

    // Roles
    Route::middleware('permission:roles.read')->group(function () {
        Route::resource('admin/roles', RoleController::class, ['as' => 'admin'])->except('show');
    });

    // Permissions
    Route::middleware('permission:permissions.read')->group(function () {
        Route::resource('admin/permissions', PermissionController::class, ['as' => 'admin'])->except('show');
        Route::get('admin/permissions/module/{module}/edit', [PermissionController::class, 'editModule'])
            ->name('admin.permissions.module.edit');
        Route::put('admin/permissions/module/{module}', [PermissionController::class, 'updateModule'])
            ->name('admin.permissions.module.update');
    });

    // Role Permissions
    Route::middleware('permission:role_permissions.read')->group(function () {
        Route::get('/admin/role-permissions',                    [RolePermissionController::class, 'index'])->name('admin.role-permissions.index');
        Route::get('/admin/role-permissions/{role}/assign',      [RolePermissionController::class, 'assign'])->name('admin.role-permissions.assign');
        Route::post('/admin/role-permissions/{role}/assign',     [RolePermissionController::class, 'update'])->name('admin.role-permissions.update');
    });

    // Settings
    Route::middleware('permission:settings.read')->group(function () {
        Route::get('/settings',                  [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/settings/company',          [SettingsController::class, 'company'])->name('settings.company');
        Route::post('/settings/company',         [SettingsController::class, 'updateCompany'])->name('settings.company.update');
        Route::get('/settings/general',          [SettingsController::class, 'general'])->name('settings.general');
        Route::post('/settings/general',         [SettingsController::class, 'updateGeneral'])->name('settings.general.update');
    });
});
