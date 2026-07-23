<?php
// File: config/employee_types.php
// Purpose: Single source of truth for the 3-value employee classification
//          scheme (raw code => full display label) used throughout the
//          system — Shifts, Leave Types, Rule Engine, dashboards, reports.
//          Raw codes 'staff'/'company_labour'/'contract_labour' come from
//          Employee.primary_employee_type + Employee.labour_type; never
//          duplicate this label map inline in a controller or Blade view.

return [
    'staff'           => 'Company Staff',
    'company_labour'  => 'Company Labour',
    'contract_labour' => 'Contract Labour',
];
