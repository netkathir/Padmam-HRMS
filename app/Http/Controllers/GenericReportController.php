<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Support\BranchAdminPermissions;
use App\Support\BranchScope;
use App\Support\Reports\ReportColumnRenderer;
use App\Support\Reports\ReportFilterApplier;
use App\Support\Reports\ReportMasking;
use App\Support\Reports\ReportRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Module 10 (FSD 14.1-14.7) — one generic show/exportCsv/exportPdf triplet
 * that serves every report driven by the ReportRegistry, instead of a
 * bespoke controller method per report. Aliased reports (ReportDefinition::
 * isAlias()) are never routed here — the reports index links straight at
 * their existing named route.
 */
class GenericReportController extends Controller
{
    public function show(string $key, Request $request)
    {
        $definition = ReportRegistry::find($key);
        abort_if(! $definition || $definition->isAlias(), 404);

        $query = ReportFilterApplier::apply(($definition->query)(), $definition, $request);
        if ($definition->eagerLoads) {
            $query->with($definition->eagerLoads);
        }
        if ($definition->defaultSort) {
            $query->orderBy($definition->defaultSort[0], $definition->defaultSort[1]);
        }

        $records = $query->paginate(30)->withQueryString();

        $summary = $definition->summary
            ? ($definition->summary)(ReportFilterApplier::apply(($definition->query)(), $definition, $request))
            : null;

        return view('reports.generic.show', [
            'definition'       => $definition,
            'records'          => $records,
            'summary'          => $summary,
            'canViewSensitive' => ReportMasking::canViewSensitive(),
            'filterOptions'    => $this->filterOptions($definition),
        ]);
    }

    public function exportCsv(string $key, Request $request)
    {
        $definition = ReportRegistry::find($key);
        abort_if(! $definition || $definition->isAlias() || ! in_array('csv', $definition->exportFormats, true), 404);

        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'reports', 'export_excel')) {
            abort(403, 'You do not have the "Export Excel" permission for Reports in Branch Administration.');
        }

        $records          = $this->recordsFor($definition, $request);
        $canViewSensitive = ReportMasking::canViewSensitive();
        $filename         = $definition->key . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($records, $definition, $canViewSensitive) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_column($definition->columns, 'label'));

            foreach ($records as $record) {
                fputcsv($handle, array_map(
                    fn ($col) => ReportColumnRenderer::render($record, $col, $canViewSensitive),
                    $definition->columns
                ));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(string $key, Request $request)
    {
        $definition = ReportRegistry::find($key);
        abort_if(! $definition || $definition->isAlias() || ! in_array('pdf', $definition->exportFormats, true), 404);

        if (BranchScope::isBranchScopedUser() && ! BranchAdminPermissions::can(auth()->user(), 'reports', 'export_pdf')) {
            abort(403, 'You do not have the "Export PDF" permission for Reports in Branch Administration.');
        }

        $records = $this->recordsFor($definition, $request);

        $pdf = Pdf::loadView('reports.generic.pdf', [
            'definition'       => $definition,
            'records'          => $records,
            'columns'          => $definition->pdfColumns ?? $definition->columns,
            'canViewSensitive' => ReportMasking::canViewSensitive(),
        ])->setPaper('a4', $definition->pdfOrientation);

        return $pdf->download($definition->key . '-' . now()->format('Y-m-d') . '.pdf');
    }

    private function recordsFor(\App\Support\Reports\ReportDefinition $definition, Request $request)
    {
        $query = ReportFilterApplier::apply(($definition->query)(), $definition, $request);
        if ($definition->eagerLoads) {
            $query->with($definition->eagerLoads);
        }
        if ($definition->defaultSort) {
            $query->orderBy($definition->defaultSort[0], $definition->defaultSort[1]);
        }

        return $query->get();
    }

    /** Only builds the dropdown sources a given definition actually declares a filter for. */
    private function filterOptions(\App\Support\Reports\ReportDefinition $definition): array
    {
        $options = [];
        $map     = $definition->filterMap;

        if (isset($map['branch_id'])) {
            $options['branches'] = BranchScope::scopeQuery(Branch::active(), 'id')->orderBy('name')->get();
        }
        if (isset($map['department_id'])) {
            $options['departments'] = BranchScope::scopeQuery(Department::query())->orderBy('name')->get();
        }
        if (isset($map['employee_type_id'])) {
            $options['employeeTypes'] = EmployeeType::where('is_active', true)->orderBy('name')->get();
        }
        if (isset($map['contractor_id'])) {
            $options['contractors'] = BranchScope::scopeQuery(Contractor::query())->orderBy('name')->get();
        }
        if (isset($map['employee_id'])) {
            $options['employees'] = BranchScope::scopeQuery(Employee::active())->orderBy('first_name')->get();
        }

        return $options;
    }
}
