<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BranchDashboardController;
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
use App\Http\Controllers\Masters\BankController;
use App\Http\Controllers\RuleEngine\RuleController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\BranchAdmin\BranchHeadAssignmentController;
use App\Http\Controllers\BranchAdmin\BranchSwitcherController;
use App\Http\Controllers\BranchAdmin\AuditLogController as BranchAdminAuditLogController;
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
// Every feature area below is gated on the module.access_level ability
// enforced by the Gate and shown/hidden in the sidebar
// (resources/views/partials/_sidebar.blade.php) and managed on the Role
// Permissions page. A role only reaches a route once it has been explicitly
// granted the matching level on that module; anyone else gets a 403 via
// App\Http\Middleware\CheckPermission. super_admin bypasses all of this
// through Gate::before in AppServiceProvider.
//
// Access-level hierarchy (per Permission::LEVEL_DESCRIPTIONS): read < create
// < full — read/create implies viewing; store (adding a new record) requires
// create; update/destroy/approve/cancel/activate-deactivate-type actions
// require full. Every resource route below is split per-action to match this
// (previously the entire CRUD verb set for a resource was gated by a single
// blanket `.read` check, meaning a role granted only "read" could still
// reach store/update/destroy directly by URL — this has been corrected
// throughout).
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard — Overall Dashboard (FSD 5.2) and Branch Dashboard (FSD 5.3).
    Route::middleware('permission:dashboard.read')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
    Route::middleware('permission:branch_dashboard.read')->group(function () {
        Route::get('/dashboard/branch', [BranchDashboardController::class, 'index'])->name('dashboard.branch');
    });

    // Profile — always accessible to the account owner.
    Route::get('/profile',         [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',       [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Employees
    Route::resource('employees', EmployeeController::class)
        ->middlewareFor(['index', 'create', 'edit', 'show'], 'permission:employees.read')
        ->middlewareFor(['store'], 'permission:employees.create')
        ->middlewareFor(['update', 'destroy'], 'permission:employees.full');
    Route::get('/employees/{employee}/documents',  [EmployeeController::class, 'documents'])->name('employees.documents')->middleware('permission:employees.read');
    Route::post('/employees/{employee}/documents', [EmployeeController::class, 'uploadDocument'])->name('employees.documents.upload')->middleware('permission:employees.create');
    Route::delete('/employees/{employee}/documents/{document}', [EmployeeController::class, 'deleteDocument'])->name('employees.documents.destroy')->middleware('permission:employees.full');
    Route::post('/employees/{employee}/bank-details',              [EmployeeController::class, 'storeBankDetail'])->name('employees.bank-details.store')->middleware('permission:employees.create');
    Route::put('/employees/{employee}/bank-details/{bankDetail}',  [EmployeeController::class, 'updateBankDetail'])->name('employees.bank-details.update')->middleware('permission:employees.full');
    Route::delete('/employees/{employee}/bank-details/{bankDetail}', [EmployeeController::class, 'destroyBankDetail'])->name('employees.bank-details.destroy')->middleware('permission:employees.full');
    Route::get('/employees/{employee}/salary',     [EmployeeController::class, 'salary'])->name('employees.salary')->middleware('permission:employees.read');
    Route::post('/employees/{employee}/salary',    [EmployeeController::class, 'storeSalary'])->name('employees.salary.store')->middleware('permission:employees.full');
    Route::get('/employees/{employee}/exit',       [EmployeeController::class, 'exit'])->name('employees.exit')->middleware('permission:employees.read');
    Route::post('/employees/{employee}/exit',      [EmployeeController::class, 'processExit'])->name('employees.exit.store')->middleware('permission:employees.full');

    // Attendance (Contract Attendance shares the same module, per the sidebar)
    // Static routes MUST come before the {attendance} wildcard show route below.
    Route::get('/attendance',                    [AttendanceController::class, 'index'])->name('attendance.index')->middleware('permission:attendance.read');
    Route::get('/attendance/mark',               [AttendanceController::class, 'markForm'])->name('attendance.mark')->middleware('permission:attendance.read');
    Route::post('/attendance/mark',              [AttendanceController::class, 'mark'])->name('attendance.mark.post')->middleware('permission:attendance.create');
    Route::get('/attendance/manual',             [AttendanceController::class, 'manualForm'])->name('attendance.manual')->middleware('permission:attendance.read');
    Route::post('/attendance/manual',            [AttendanceController::class, 'manual'])->name('attendance.manual.post')->middleware('permission:attendance.create');
    Route::get('/attendance/pending',            [AttendanceController::class, 'pending'])->name('attendance.pending')->middleware('permission:attendance.read');
    Route::get('/attendance/report',             [AttendanceController::class, 'report'])->name('attendance.report')->middleware('permission:attendance.read');

    // 11.2 Biometric Excel Upload
    Route::get('/attendance/upload',                     [AttendanceController::class, 'uploadForm'])->name('attendance.upload.form')->middleware('permission:attendance.read');
    Route::post('/attendance/upload',                    [AttendanceController::class, 'upload'])->name('attendance.upload.post')->middleware('permission:attendance.create');
    Route::get('/attendance/upload/{upload}/mapping',    [AttendanceController::class, 'mappingForm'])->name('attendance.upload.mapping')->middleware('permission:attendance.read');
    Route::post('/attendance/upload/{upload}/mapping',   [AttendanceController::class, 'confirmMapping'])->name('attendance.upload.confirm')->middleware('permission:attendance.create');
    Route::get('/attendance/upload/{upload}/summary',    [AttendanceController::class, 'uploadSummary'])->name('attendance.upload.summary')->middleware('permission:attendance.read');
    Route::get('/attendance/upload/{upload}/errors',     [AttendanceController::class, 'downloadUploadErrors'])->name('attendance.upload.errors')->middleware('permission:attendance.read');

    // 11.3 Attendance Processing
    Route::get('/attendance/process',            [AttendanceController::class, 'processForm'])->name('attendance.process.form')->middleware('permission:attendance.read');
    Route::post('/attendance/process',           [AttendanceController::class, 'process'])->name('attendance.process.post')->middleware('permission:attendance.create');

    // 11.4 Register actions
    Route::post('/attendance/recalculate-selected', [AttendanceController::class, 'recalculateSelected'])->name('attendance.recalculate-selected')->middleware('permission:attendance.full');
    Route::get('/attendance/export',             [AttendanceController::class, 'export'])->name('attendance.export')->middleware('permission:attendance.read');
    Route::get('/attendance/export-pdf',         [AttendanceController::class, 'exportPdf'])->name('attendance.export.pdf')->middleware('permission:attendance.read');

    // 11.5 Attendance Correction
    Route::get('/attendance/correction',          [AttendanceController::class, 'correctionForm'])->name('attendance.correction.form')->middleware('permission:attendance.read');
    Route::post('/attendance/correction',         [AttendanceController::class, 'correction'])->name('attendance.correction.post')->middleware('permission:attendance.create');

    Route::post('/attendance/{attendance}/approve',    [AttendanceController::class, 'approve'])->name('attendance.approve')->middleware('permission:attendance.full');
    Route::post('/attendance/{attendance}/ot-approve', [AttendanceController::class, 'approveOvertime'])->name('attendance.ot.approve')->middleware('permission:attendance.full');
    Route::get('/attendance/{attendance}',        [AttendanceController::class, 'show'])->name('attendance.show')->middleware('permission:attendance.read');

    Route::get('/contract-attendance',           [ContractAttendanceController::class, 'index'])->name('contract-attendance.index')->middleware('permission:attendance.read');
    Route::get('/contract-attendance/mark',      [ContractAttendanceController::class, 'markForm'])->name('contract-attendance.mark')->middleware('permission:attendance.read');
    Route::post('/contract-attendance/mark',     [ContractAttendanceController::class, 'mark'])->name('contract-attendance.mark.post')->middleware('permission:attendance.create');
    Route::get('/contract-attendance/report',    [ContractAttendanceController::class, 'report'])->name('contract-attendance.report')->middleware('permission:attendance.read');

    // Leave — static routes MUST come before {leave} wildcard
    Route::get('/leaves',                        [LeaveController::class, 'index'])->name('leaves.index')->middleware('permission:leaves.read');
    Route::get('/leaves/create',                 [LeaveController::class, 'create'])->name('leaves.create')->middleware('permission:leaves.read');
    Route::post('/leaves',                       [LeaveController::class, 'store'])->name('leaves.store')->middleware('permission:leaves.create');
    Route::get('/leaves/balance',                [LeaveController::class, 'balance'])->name('leaves.balance')->middleware('permission:leaves.read');
    Route::post('/leaves/balance/{balance}/adjust', [LeaveController::class, 'adjustBalance'])->name('leaves.balance.adjust')->middleware('permission:leaves.full');
    Route::get('/leaves/permissions',            [LeaveController::class, 'permissions'])->name('leaves.permissions')->middleware('permission:leaves.read');
    Route::post('/leaves/permissions',           [LeaveController::class, 'storePermission'])->name('leaves.permissions.store')->middleware('permission:leaves.create');
    Route::post('/leaves/permissions/{permission}/approve', [LeaveController::class, 'approvePermission'])->name('leaves.permissions.approve')->middleware('permission:leaves.full');
    Route::get('/leaves/{leave}',                [LeaveController::class, 'show'])->name('leaves.show')->middleware('permission:leaves.read');
    Route::post('/leaves/{leave}/approve',       [LeaveController::class, 'approve'])->name('leaves.approve')->middleware('permission:leaves.full');
    // leaves.create (not .full) so a self-service employee can reach the
    // controller to cancel their own request; ownership is enforced inside
    // LeaveController::cancel() itself (leaves.full still sees/cancels any).
    Route::post('/leaves/{leave}/cancel',        [LeaveController::class, 'cancel'])->name('leaves.cancel')->middleware('permission:leaves.create');

    // Payroll (Contract Payroll shares the same module, per the sidebar)
    Route::get('/payroll',                       [PayrollController::class, 'index'])->name('payroll.index')->middleware('permission:payroll.read');
    Route::get('/payroll/generate',              [PayrollController::class, 'generateForm'])->name('payroll.generate')->middleware('permission:payroll.read');
    Route::post('/payroll/generate',             [PayrollController::class, 'generate'])->name('payroll.generate.post')->middleware('permission:payroll.full');
    Route::get('/payroll/{payroll}/payslip',     [PayrollController::class, 'payslip'])->name('payroll.payslip')->middleware('permission:payroll.read');
    Route::get('/payroll/{payroll}/payslip/pdf', [PayrollController::class, 'payslipPdf'])->name('payroll.payslip.pdf')->middleware('permission:payroll.read');
    Route::post('/payroll/{payroll}/payslip/email', [PayrollController::class, 'emailPayslip'])->name('payroll.payslip.email')->middleware('permission:payroll.read');
    Route::get('/payroll/payslip/bulk',          [PayrollController::class, 'payslipBulk'])->name('payroll.payslip.bulk')->middleware('permission:payroll.read');
    Route::get('/payroll/{payroll}/payment',     [PayrollController::class, 'paymentForm'])->name('payroll.payment')->middleware('permission:payroll.read');
    Route::post('/payroll/{payroll}/payment',    [PayrollController::class, 'storePayment'])->name('payroll.payment.store')->middleware('permission:payroll.full');
    Route::post('/payroll/{payroll}/adjustment', [PayrollController::class, 'manualAdjustment'])->name('payroll.adjustment.store')->middleware('permission:payroll.full');
    Route::delete('/payroll/{payroll}/adjustment/{type}/{id}', [PayrollController::class, 'deleteManualAdjustment'])->name('payroll.adjustment.destroy')->middleware('permission:payroll.full');
    Route::get('/payroll/lop-review',            [PayrollController::class, 'lopReview'])->name('payroll.lop-review')->middleware('permission:payroll.read');
    Route::post('/payroll/{payroll}/lop',        [PayrollController::class, 'updateLop'])->name('payroll.lop.update')->middleware('permission:payroll.full');
    Route::post('/payroll/lop-review/confirm',   [PayrollController::class, 'confirmLop'])->name('payroll.lop.confirm')->middleware('permission:payroll.full');
    Route::post('/payroll/lop-review/bulk',      [PayrollController::class, 'bulkLopAction'])->name('payroll.lop.bulk')->middleware('permission:payroll.full');
    Route::post('/payroll/confirm',              [PayrollController::class, 'confirmPayroll'])->name('payroll.confirm')->middleware('permission:payroll.full');
    Route::post('/payroll/close',                [PayrollController::class, 'closePayroll'])->name('payroll.close')->middleware('permission:payroll.full');
    Route::post('/payroll/{payroll}/reopen',     [PayrollController::class, 'reopenPayroll'])->name('payroll.reopen')->middleware('permission:payroll.full');
    Route::get('/payroll/export/register',       [PayrollController::class, 'exportRegister'])->name('payroll.export.register')->middleware('permission:payroll.read');
    Route::get('/payroll/export/bank-transfer',  [PayrollController::class, 'exportBankTransfer'])->name('payroll.export.bank-transfer')->middleware('permission:payroll.full');
    Route::get('/payroll/export/statutory',      [PayrollController::class, 'exportStatutory'])->name('payroll.export.statutory')->middleware('permission:payroll.read');

    Route::get('/contract-payroll',              [ContractPayrollController::class, 'index'])->name('contract-payroll.index')->middleware('permission:payroll.read');
    Route::get('/contract-payroll/calculate',    [ContractPayrollController::class, 'calculateForm'])->name('contract-payroll.calculate')->middleware('permission:payroll.read');
    Route::post('/contract-payroll/calculate',   [ContractPayrollController::class, 'calculate'])->name('contract-payroll.calculate.post')->middleware('permission:payroll.full');
    Route::get('/contract-payroll/{id}',         [ContractPayrollController::class, 'show'])->name('contract-payroll.show')->middleware('permission:payroll.read');
    Route::post('/contract-payroll/{id}/pay',    [ContractPayrollController::class, 'markPaid'])->name('contract-payroll.pay')->middleware('permission:payroll.full');

    // Reports — read-only throughout, no mutating actions.
    Route::middleware('permission:reports.read')->group(function () {
        Route::get('/reports',                       [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/attendance',            [ReportController::class, 'attendance'])->name('reports.attendance');
        Route::get('/reports/employees',             [ReportController::class, 'employees'])->name('reports.employees');
        Route::get('/reports/leave',                 [ReportController::class, 'leave'])->name('reports.leave');
        Route::get('/reports/payroll',               [ReportController::class, 'payroll'])->name('reports.payroll');
        Route::get('/reports/contractor',            [ReportController::class, 'contractor'])->name('reports.contractor');
        Route::get('/reports/contract-labour',       [ReportController::class, 'contractLabour'])->name('reports.contract-labour');
        Route::get('/reports/pf-esi',                [ReportController::class, 'pfEsi'])->name('reports.pf-esi');
        Route::get('/reports/overtime',              [ReportController::class, 'overtime'])->name('reports.overtime');
        Route::get('/reports/lop',                   [ReportController::class, 'lop'])->name('reports.lop');
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

    // Branch Management is Super-Admin-only by design — gated on role, not on
    // the permission system, so it can never be unlocked for any other role
    // even if masters_branches is accidentally granted via Role Permissions.
    // BranchController::ensureSuperAdmin() enforces this again at the
    // controller layer as defense in depth.
    Route::middleware('role:super_admin')->group(function () {
        Route::resource('masters/branches', BranchController::class, ['as' => 'masters'])->except('show');
        Route::post('masters/branches/{branch}/activate',   [BranchController::class, 'activate'])->name('masters.branches.activate');
        Route::post('masters/branches/{branch}/deactivate', [BranchController::class, 'deactivate'])->name('masters.branches.deactivate');
    });

    Route::resource('masters/departments', DepartmentController::class, ['as' => 'masters'])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_departments.read')
        ->middlewareFor(['store'], 'permission:masters_departments.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_departments.full');

    Route::resource('masters/designations', DesignationController::class, ['as' => 'masters'])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_designations.read')
        ->middlewareFor(['store'], 'permission:masters_designations.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_designations.full');

    Route::resource('masters/employee-types', EmployeeTypeController::class, ['as' => 'masters'])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_employee_types.read')
        ->middlewareFor(['store'], 'permission:masters_employee_types.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_employee_types.full');

    Route::resource('masters/shifts', ShiftController::class, ['as' => 'masters'])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_shifts.read')
        ->middlewareFor(['store'], 'permission:masters_shifts.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_shifts.full');

    Route::resource('masters/holidays', HolidayController::class, ['as' => 'masters'])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_holidays.read')
        ->middlewareFor(['store'], 'permission:masters_holidays.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_holidays.full');
    Route::post('masters/holidays/sunday-policy', [HolidayController::class, 'updateSundayPolicy'])->name('masters.holidays.sunday-policy')->middleware('permission:masters_holidays.full');

    Route::resource('masters/leave-types', LeaveTypeController::class, ['as' => 'masters'])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_leave_types.read')
        ->middlewareFor(['store'], 'permission:masters_leave_types.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_leave_types.full');

    Route::resource('masters/salary-slabs', SalarySlabController::class, ['as' => 'masters'])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_salary_slabs.read')
        ->middlewareFor(['store'], 'permission:masters_salary_slabs.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_salary_slabs.full');

    Route::resource('masters/earnings', EarningsComponentController::class, ['as' => 'masters', 'parameters' => ['earnings' => 'earningsComponent']])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_earnings.read')
        ->middlewareFor(['store'], 'permission:masters_earnings.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_earnings.full');

    Route::resource('masters/deductions', DeductionsComponentController::class, ['as' => 'masters', 'parameters' => ['deductions' => 'deductionsComponent']])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_deductions.read')
        ->middlewareFor(['store'], 'permission:masters_deductions.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_deductions.full');

    Route::resource('masters/ot-rates', OtRateController::class, ['as' => 'masters'])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_ot_rates.read')
        ->middlewareFor(['store'], 'permission:masters_ot_rates.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_ot_rates.full');

    Route::resource('masters/pf-esi', PfEsiConfigController::class, ['as' => 'masters', 'parameters' => ['pf-esi' => 'pfEsiConfig']])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_pf_esi.read')
        ->middlewareFor(['store'], 'permission:masters_pf_esi.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_pf_esi.full');

    Route::resource('masters/contractors', ContractorController::class, ['as' => 'masters'])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_contractors.read')
        ->middlewareFor(['store'], 'permission:masters_contractors.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_contractors.full');
    Route::post('masters/contractors/{contractor}/documents',                     [ContractorController::class, 'uploadDocument'])->name('masters.contractors.documents.store')->middleware('permission:masters_contractors.full');
    Route::delete('masters/contractors/{contractor}/documents/{document}',        [ContractorController::class, 'deleteDocument'])->name('masters.contractors.documents.destroy')->middleware('permission:masters_contractors.full');
    Route::get('masters/contractors/{contractor}/labour',              [ContractorController::class, 'labour'])->name('masters.contractors.labour')->middleware('permission:masters_contractors.read');
    Route::post('masters/contractors/{contractor}/labour/assign',      [ContractorController::class, 'assignLabour'])->name('masters.contractors.labour.assign')->middleware('permission:masters_contractors.create');
    Route::delete('masters/contractors/{contractor}/labour/{employee}', [ContractorController::class, 'removeLabour'])->name('masters.contractors.labour.remove')->middleware('permission:masters_contractors.full');
    Route::get('masters/contractors/{contractor}/attendance',                                [ContractorController::class, 'attendance'])->name('masters.contractors.attendance')->middleware('permission:masters_contractors.read');
    Route::get('masters/contractors/{contractor}/payroll',                                   [ContractorController::class, 'payroll'])->name('masters.contractors.payroll')->middleware('permission:masters_contractors.read');
    Route::get('masters/contractors/{contractor}/workers',                                   [ContractWorkerController::class, 'index'])->name('masters.contractors.workers.index')->middleware('permission:masters_contractors.read');
    Route::get('masters/contractors/{contractor}/workers/create',                            [ContractWorkerController::class, 'create'])->name('masters.contractors.workers.create')->middleware('permission:masters_contractors.read');
    Route::post('masters/contractors/{contractor}/workers',                                  [ContractWorkerController::class, 'store'])->name('masters.contractors.workers.store')->middleware('permission:masters_contractors.create');
    Route::get('masters/contractors/{contractor}/workers/{contractWorker}/edit',             [ContractWorkerController::class, 'edit'])->name('masters.contractors.workers.edit')->middleware('permission:masters_contractors.read');
    Route::put('masters/contractors/{contractor}/workers/{contractWorker}',                  [ContractWorkerController::class, 'update'])->name('masters.contractors.workers.update')->middleware('permission:masters_contractors.full');
    Route::delete('masters/contractors/{contractor}/workers/{contractWorker}',               [ContractWorkerController::class, 'destroy'])->name('masters.contractors.workers.destroy')->middleware('permission:masters_contractors.full');

    // Contract Labour Assignment shares the Contractors module
    Route::get('/contract-labour', [ContractorController::class, 'contractLabourIndex'])->name('contract-labour.index')->middleware('permission:masters_contractors.read');

    Route::resource('masters/banks', BankController::class, ['as' => 'masters'])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:masters_banks.read')
        ->middlewareFor(['store'], 'permission:masters_banks.create')
        ->middlewareFor(['update', 'destroy'], 'permission:masters_banks.full');

    // Module 4 — Rule Engine (single screen, category-driven; Employee Number
    // Configuration is not a separate form per the FSD).
    Route::resource('rule-engine', RuleController::class, ['parameters' => ['rule-engine' => 'rule']])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:rule_engine.read')
        ->middlewareFor(['store'], 'permission:rule_engine.create')
        ->middlewareFor(['update', 'destroy'], 'permission:rule_engine.full');
    Route::post('rule-engine/preview-employee-number', [RuleController::class, 'previewEmployeeNumber'])
        ->name('rule-engine.preview-employee-number')->middleware('permission:rule_engine.read');

    // Users
    Route::resource('users', UserController::class)->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:users.read')
        ->middlewareFor(['store'], 'permission:users.create')
        ->middlewareFor(['update', 'destroy'], 'permission:users.full');
    Route::get('/users/{user}/permissions',       [UserController::class, 'permissions'])->name('users.permissions')->middleware('permission:users.read');
    Route::put('/users/{user}/permissions',       [UserController::class, 'updatePermissions'])->name('users.permissions.update')->middleware('permission:users.full');
    Route::post('/users/{user}/activate',          [UserController::class, 'activate'])->name('users.activate')->middleware('permission:users.full');
    Route::post('/users/{user}/deactivate',        [UserController::class, 'deactivate'])->name('users.deactivate')->middleware('permission:users.full');
    Route::post('/users/{user}/lock',              [UserController::class, 'lock'])->name('users.lock')->middleware('permission:users.full');
    Route::post('/users/{user}/unlock',            [UserController::class, 'unlock'])->name('users.unlock')->middleware('permission:users.full');

    // Roles
    Route::resource('admin/roles', RoleController::class, ['as' => 'admin'])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:roles.read')
        ->middlewareFor(['store'], 'permission:roles.create')
        ->middlewareFor(['update', 'destroy'], 'permission:roles.full');

    // Role Permissions — assigning permissions is a highly sensitive action;
    // even loading the assignment form for editing requires full access.
    Route::middleware('permission:role_permissions.read')->group(function () {
        Route::get('/admin/role-permissions',                    [RolePermissionController::class, 'index'])->name('admin.role-permissions.index');
    });
    Route::middleware('permission:role_permissions.full')->group(function () {
        Route::get('/admin/role-permissions/{role}/assign',      [RolePermissionController::class, 'assign'])->name('admin.role-permissions.assign');
        Route::post('/admin/role-permissions/{role}/assign',     [RolePermissionController::class, 'update'])->name('admin.role-permissions.update');
    });

    // Settings
    Route::get('/settings',                  [SettingsController::class, 'index'])->name('settings.index')->middleware('permission:settings.read');
    Route::get('/settings/company',          [SettingsController::class, 'company'])->name('settings.company')->middleware('permission:settings.read');
    Route::post('/settings/company',         [SettingsController::class, 'updateCompany'])->name('settings.company.update')->middleware('permission:settings.full');
    Route::get('/settings/general',          [SettingsController::class, 'general'])->name('settings.general')->middleware('permission:settings.read');
    Route::post('/settings/general',         [SettingsController::class, 'updateGeneral'])->name('settings.general.update')->middleware('permission:settings.full');

    // ── Branch Administration ───────────────────────────────────────────
    // Only genuinely new features live here — Branches/Users/Roles/Permissions
    // are managed entirely via the existing Masters > Branches, System Admin >
    // Users/Roles/Role Permissions screens above (single source of truth).
    Route::resource('branch-admin/head-assignments', BranchHeadAssignmentController::class, [
        'as' => 'branch-admin',
        'parameters' => ['head-assignments' => 'headAssignment'],
    ])->except('show')
        ->middlewareFor(['index', 'create', 'edit'], 'permission:branch_admin_head_assignments.read')
        ->middlewareFor(['store'], 'permission:branch_admin_head_assignments.create')
        ->middlewareFor(['update', 'destroy'], 'permission:branch_admin_head_assignments.full');
    Route::post('branch-admin/head-assignments/{headAssignment}/deactivate', [BranchHeadAssignmentController::class, 'deactivate'])->name('branch-admin.head-assignments.deactivate')->middleware('permission:branch_admin_head_assignments.full');

    // Branch Switcher — changes which branch's data the current session views;
    // not a CRUD action on protected data, so read-level access is sufficient.
    Route::middleware('permission:branch_admin_switcher.read')->group(function () {
        Route::get('branch-admin/branch-switcher',        [BranchSwitcherController::class, 'index'])->name('branch-admin.branch-switcher.index');
        Route::post('branch-admin/branch-switcher/switch', [BranchSwitcherController::class, 'switch'])->name('branch-admin.branch-switcher.switch');
    });

    Route::middleware('permission:branch_admin_audit_log.read')->group(function () {
        Route::get('branch-admin/audit-log', [BranchAdminAuditLogController::class, 'index'])->name('branch-admin.audit-log.index');
    });
});
