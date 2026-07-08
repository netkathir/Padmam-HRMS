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
    'permissions' => [
        'label' => 'Permissions',
        'icon' => 'bi-key',
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
    'reports' => [
        'label' => 'Reports',
        'icon' => 'bi-bar-chart-line',
        'section' => 'Insights',
    ],
];
