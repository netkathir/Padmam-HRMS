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
    'masters' => [
        'label' => 'Masters',
        'icon' => 'bi-collection',
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
