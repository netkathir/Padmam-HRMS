<?php
// File: config/menu_modules.php
// Purpose: Single source of truth for sidebar modules — drives the sidebar nav gates,
//          the Permissions admin page, and the Role Permissions assignment grid.
//          Add a module here once and it automatically appears everywhere permissions
//          are listed/assigned; nothing else needs to be edited to make it show up.
// Author: System
// Date: 2026-07-08

return [
    'dashboard' => [
        'label' => 'Dashboard',
        'icon' => 'bi-speedometer2',
        'section' => 'Main',
    ],
    'branch_dashboard' => [
        'label' => 'Branch Dashboard',
        'icon' => 'bi-diagram-3',
        'section' => 'Main',
    ],
    'users' => [
        'label' => 'Users',
        'icon' => 'bi-people',
        'section' => 'System Admin',
    ],
    'roles' => [
        'label' => 'Roles',
        'icon' => 'bi-shield-check',
        'section' => 'System Admin',
    ],
    'role_permissions' => [
        'label' => 'Role Permissions',
        'icon' => 'bi-person-lock',
        'section' => 'System Admin',
    ],
    'settings' => [
        'label' => 'Settings',
        'icon' => 'bi-sliders',
        'section' => 'System Admin',
    ],
    // Masters has no permission of its own — each sub-module underneath it
    // (mirroring resources/views/partials/_sidebar.blade.php) is a distinct,
    // independently assignable module so access can be granted per sub-screen
    // instead of via one all-or-nothing "masters" switch.
    'masters_branches' => [
        'label' => 'Branches',
        'icon' => 'bi-building',
        'section' => 'Masters',
    ],
    'masters_departments' => [
        'label' => 'Departments',
        'icon' => 'bi-diagram-3',
        'section' => 'Masters',
    ],
    'masters_designations' => [
        'label' => 'Designations',
        'icon' => 'bi-person-badge',
        'section' => 'Masters',
    ],
    'masters_employee_types' => [
        'label' => 'Employee Types',
        'icon' => 'bi-person-gear',
        'section' => 'Masters',
    ],
    'masters_shifts' => [
        'label' => 'Shifts',
        'icon' => 'bi-clock',
        'section' => 'Masters',
    ],
    'masters_holidays' => [
        'label' => 'Holidays',
        'icon' => 'bi-calendar-heart',
        'section' => 'Masters',
    ],
    'masters_leave_types' => [
        'label' => 'Leave Types',
        'icon' => 'bi-calendar-minus',
        'section' => 'Masters',
    ],
    'masters_salary_slabs' => [
        'label' => 'Salary Slabs',
        'icon' => 'bi-layers',
        'section' => 'Masters',
    ],
    'masters_earnings' => [
        'label' => 'Earnings',
        'icon' => 'bi-graph-up-arrow',
        'section' => 'Masters',
    ],
    'masters_deductions' => [
        'label' => 'Deductions',
        'icon' => 'bi-graph-down-arrow',
        'section' => 'Masters',
    ],
    'masters_ot_rates' => [
        'label' => 'OT Rates',
        'icon' => 'bi-hourglass-split',
        'section' => 'Masters',
    ],
    'masters_pf_esi' => [
        'label' => 'PF & ESI',
        'icon' => 'bi-shield-plus',
        'section' => 'Masters',
    ],
    'masters_contractors' => [
        'label' => 'Contractors',
        'icon' => 'bi-person-workspace',
        'section' => 'Masters',
    ],
    'masters_banks' => [
        'label' => 'Banks',
        'icon' => 'bi-bank',
        'section' => 'Masters',
    ],
    'masters_checkpoints' => [
        'label' => 'Checkpoints',
        'icon' => 'bi-geo-alt',
        'section' => 'Masters',
    ],
    'masters_employee_checkpoints' => [
        'label' => 'Employee Checkpoint Mapping',
        'icon' => 'bi-person-lines-fill',
        'section' => 'Masters',
    ],
    'rule_engine' => [
        'label' => 'Rule Engine',
        'icon' => 'bi-diagram-3-fill',
        'section' => 'System Admin',
    ],
    'employees' => [
        'label' => 'Employees',
        'icon' => 'bi-person-vcard',
        'section' => 'People',
    ],
    'attendance' => [
        'label' => 'Attendance',
        'icon' => 'bi-calendar-check',
        'section' => 'Time & Leave',
    ],
    'leaves' => [
        'label' => 'Leaves',
        'icon' => 'bi-calendar-x',
        'section' => 'Time & Leave',
    ],
    'payroll' => [
        'label' => 'Payroll',
        'icon' => 'bi-cash-stack',
        'section' => 'Payroll',
    ],
    // Module 11 (FSD 15.2) — LOP and Payslip are listed as their own
    // distinct permission-controlled modules; both used to reuse the
    // `payroll` module key. Their routes now gate on these instead —
    // no controller logic changed, only which permission applies.
    'lop' => [
        'label' => 'LOP',
        'icon' => 'bi-graph-down-arrow',
        'section' => 'Payroll',
    ],
    'payslip' => [
        'label' => 'Payslip',
        'icon' => 'bi-file-earmark-text',
        'section' => 'Payroll',
    ],
    'reports' => [
        'label' => 'Reports',
        'icon' => 'bi-bar-chart-line',
        'section' => 'Insights',
    ],
    // Branch Administration — only genuinely new features get a module here.
    // Branches/Users/Roles/Permissions are governed by the existing
    // masters_branches/users/roles/permissions/role_permissions modules
    // above; there is no separate Branch Administration copy of any of them.
    'branch_admin_head_assignments' => [
        'label' => 'Branch Head Assignment',
        'icon' => 'bi-person-badge',
        'section' => 'Branch Administration',
    ],
    'branch_admin_switcher' => [
        'label' => 'Branch Switcher',
        'icon' => 'bi-arrow-left-right',
        'section' => 'Branch Administration',
    ],
    'branch_admin_audit_log' => [
        'label' => 'Audit Log',
        'icon' => 'bi-journal-text',
        'section' => 'Branch Administration',
    ],
];
