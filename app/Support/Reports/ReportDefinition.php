<?php

namespace App\Support\Reports;

/**
 * Module 10 (FSD 14.1-14.7) — one row in the report registry. A definition
 * either drives the generic engine (query + columns) or is a pure alias
 * (aliasRoute set) pointing at an existing bespoke report — see
 * ReportRegistry for the full existing/alias/new mapping.
 *
 * filterMap keys are the fixed FSD 14.1 filter vocabulary (branch_id,
 * department_id, employee_type_id, labour_type, contractor_id, employee_id).
 * Each value is a column path: a bare column ('branch_id') applies directly
 * to the base query; a dotted path ('employee.department_id') applies via
 * whereHas() on that relation — see ReportFilterApplier.
 */
class ReportDefinition
{
    public function __construct(
        public string $key,
        public string $section,
        public string $title,
        public string $description,
        public ?\Closure $query = null,
        public array $columns = [],
        public array $filterMap = [],
        public ?string $dateColumn = null,
        public bool $periodFilter = false,
        public ?string $statusColumn = null,
        public array $statusOptions = [],
        public array $branchScope = ['type' => 'none'],
        public array $eagerLoads = [],
        public ?array $defaultSort = null,
        public array $exportFormats = ['csv', 'pdf'],
        public string $pdfOrientation = 'landscape',
        public ?array $pdfColumns = null,
        public ?\Closure $summary = null,
        public ?string $aliasRoute = null,
        public array $aliasParams = [],
    ) {
    }

    public function isAlias(): bool
    {
        return $this->aliasRoute !== null;
    }
}
