<?php

namespace App\Http\Controllers\RuleEngine;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BusinessRule;
use App\Models\Contractor;
use App\Models\DeductionsComponent;
use App\Models\EarningsComponent;
use App\Models\Shift;
use App\Services\EmployeeNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule as ValidationRule;

/**
 * Module 4 FSD 8.1/8.2 — the single Rule Engine screen. Employee Number
 * Configuration (and every other rule category) is deliberately NOT a
 * separate form per the FSD — one controller, one screen, a category
 * dropdown drives which detail fields render/validate.
 */
class RuleController extends Controller
{
    private const EMPLOYEE_TYPES = ['staff', 'labour'];
    private const LABOUR_TYPES = ['company_labour', 'contract_labour'];

    public function index(Request $request)
    {
        $query = BusinessRule::query()->orderBy('category')->orderBy('priority');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $rules = $query->paginate(20)->withQueryString();
        return view('rule-engine.index', [
            'rules' => $rules,
            'categories' => BusinessRule::CATEGORIES,
        ]);
    }

    private function formOptions(): array
    {
        return [
            'branches' => Branch::active()->orderBy('name')->get(),
            'contractors' => Contractor::where('is_active', true)->orderBy('name')->get(),
            'shifts' => Shift::where('is_active', true)->orderBy('name')->get(),
            'earningsComponents' => EarningsComponent::where('is_active', true)->orderBy('sort_order')->get(),
            'deductionsComponents' => DeductionsComponent::where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => BusinessRule::CATEGORIES,
        ];
    }

    public function create()
    {
        return view('rule-engine.create', $this->formOptions());
    }

    public function edit(BusinessRule $rule)
    {
        $rule->load(BusinessRule::DETAIL_RELATIONS[$rule->category] ?? []);
        return view('rule-engine.edit', array_merge(['rule' => $rule], $this->formOptions()));
    }

    private function headerRules(?int $ruleId, ?string $category): array
    {
        return [
            'name' => ['required', 'string', 'max:150', ValidationRule::unique('rules', 'name')->where('category', $category)->ignore($ruleId)],
            'category' => ['required', ValidationRule::in(BusinessRule::CATEGORIES)],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['exists:branches,id'],
            'employee_types' => ['required', 'array', 'min:1'],
            'employee_types.*' => [ValidationRule::in(self::EMPLOYEE_TYPES)],
            'labour_types' => ['nullable', 'array'],
            'labour_types.*' => [ValidationRule::in(self::LABOUR_TYPES)],
            'contractor_ids' => ['nullable', 'array'],
            'contractor_ids.*' => ['exists:contractors,id'],
            'priority' => ['required', 'integer', 'min:1'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
            'status' => ['required', ValidationRule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string'],
        ];
    }

    /** Category-specific validation rules, keyed by category. */
    private function detailRules(string $category): array
    {
        return match ($category) {
            'employee_number' => [
                'employee_category' => ['required', ValidationRule::in(['staff', 'company_labour', 'contract_labour'])],
                'branch_id' => ['nullable', 'exists:branches,id'],
                'contractor_id' => ['nullable', 'exists:contractors,id'],
                'prefix' => ['nullable', 'string', 'max:20'],
                'include_branch_code' => ['boolean'],
                'include_contractor_code' => ['boolean'],
                'separator' => ['nullable', 'string', 'max:5'],
                'sequence_start' => ['required', 'integer', 'min:1'],
                'sequence_length' => ['required', 'integer', 'min:1', 'max:10'],
                'include_financial_year' => ['boolean'],
                'include_calendar_year' => ['boolean'],
                'reset_frequency' => ['required', ValidationRule::in(['never', 'yearly', 'financial_yearly', 'branch_wise'])],
                'allow_manual_override' => ['boolean'],
            ],
            'attendance' => [
                'shift_ids' => ['nullable', 'array'],
                'shift_ids.*' => ['exists:shifts,id'],
                'min_full_day_hours' => ['required', 'numeric', 'min:0', 'max:24'],
                'min_half_day_hours' => ['required', 'numeric', 'min:0', 'lt:min_full_day_hours'],
                'late_grace_minutes' => ['nullable', 'integer', 'min:0'],
                'early_exit_grace_minutes' => ['nullable', 'integer', 'min:0'],
                'missing_punch_treatment' => ['required', ValidationRule::in(['absent', 'half_day', 'pending_review'])],
                'single_punch_treatment' => ['required', ValidationRule::in(['absent', 'half_day', 'pending_review'])],
                'multiple_punch_handling' => ['required', 'string', 'max:30'],
                'weekly_off_treatment' => ['required', ValidationRule::in(['paid', 'unpaid', 'conditional'])],
                'holiday_treatment' => ['required', ValidationRule::in(['paid', 'unpaid', 'conditional'])],
                'work_on_holiday_treatment' => ['required', ValidationRule::in(['overtime', 'compensatory_off', 'normal_day'])],
                'work_on_weekly_off_treatment' => ['required', ValidationRule::in(['overtime', 'compensatory_off', 'normal_day'])],
                'consecutive_absence_rule' => ['nullable', 'integer', 'min:0'],
                'rounding_minutes' => ['nullable', 'integer', 'min:0', 'max:60'],
            ],
            'weekly_off' => [
                'weekly_off_days' => ['required', 'array', 'min:1'],
                'weekly_off_days.*' => [ValidationRule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
                'is_paid' => ['boolean'],
                'min_attendance_condition' => ['nullable', 'integer', 'min:0'],
            ],
            'lop' => [
                'calculation_basis' => ['required', ValidationRule::in(['calendar_days', 'working_days', 'fixed_days'])],
                'fixed_payroll_days' => ['required_if:calculation_basis,fixed_days', 'nullable', 'integer', 'min:1', 'max:31'],
                'half_day_lop_value' => ['required', 'numeric', 'min:0', 'max:1'],
                'full_day_lop_value' => ['required', 'numeric', 'min:0', 'max:1'],
                'unpaid_leave_as_lop' => ['boolean'],
                'absent_day_as_lop' => ['boolean'],
                'missing_punch_as_lop' => ['boolean'],
                'late_count_conversion' => ['nullable', 'integer', 'min:0'],
                'early_exit_conversion' => ['nullable', 'integer', 'min:0'],
                'holiday_between_absences' => ['boolean'],
                'weekly_off_between_absences' => ['boolean'],
                'manual_lop_adjustment_allowed' => ['boolean'],
            ],
            'pf' => [
                'pf_applicable' => ['boolean'],
                'salary_slab_from' => ['required', 'numeric', 'min:0'],
                'salary_slab_to' => ['nullable', 'numeric', 'gt:salary_slab_from'],
                'pf_wage_components' => ['required', 'array', 'min:1'],
                'pf_wage_components.*' => ['exists:earnings_components,id'],
                'employee_pf_percentage' => ['required', 'numeric', 'between:0,100'],
                'employer_pf_percentage' => ['required', 'numeric', 'between:0,100'],
                'pf_wage_ceiling' => ['nullable', 'numeric', 'min:0'],
                'restrict_to_wage_ceiling' => ['boolean'],
                'voluntary_pf_allowed' => ['boolean'],
                'rounding_method' => ['required', ValidationRule::in(['nearest', 'up', 'down'])],
            ],
            'esi' => [
                'esi_applicable' => ['boolean'],
                'salary_slab_from' => ['required', 'numeric', 'min:0'],
                'salary_slab_to' => ['nullable', 'numeric', 'gt:salary_slab_from'],
                'esi_wage_components' => ['required', 'array', 'min:1'],
                'esi_wage_components.*' => ['exists:earnings_components,id'],
                'employee_esi_percentage' => ['required', 'numeric', 'between:0,100'],
                'employer_esi_percentage' => ['required', 'numeric', 'between:0,100'],
                'rounding_method' => ['required', ValidationRule::in(['nearest', 'up', 'down'])],
            ],
            'tds' => [
                'tds_applicable' => ['boolean'],
                'salary_slab_from' => ['required', 'numeric', 'min:0'],
                'salary_slab_to' => ['nullable', 'numeric', 'gt:salary_slab_from'],
                'tds_percentage' => ['required', 'numeric', 'between:0,100'],
                'calculation_basis' => ['required', ValidationRule::in(['monthly_gross', 'annual_estimated_income', 'taxable_income'])],
                'taxable_components' => ['required', 'array', 'min:1'],
                'taxable_components.*' => ['exists:earnings_components,id'],
                'exempt_components' => ['nullable', 'array'],
                'exempt_components.*' => ['exists:earnings_components,id'],
                'fixed_tds_amount_allowed' => ['boolean'],
                'rounding_method' => ['required', ValidationRule::in(['nearest', 'up', 'down'])],
            ],
            'overtime' => [
                'overtime_applicable' => ['boolean'],
                'minimum_overtime_minutes' => ['required_if:overtime_applicable,1', 'nullable', 'integer', 'min:0'],
                'overtime_calculation' => ['required_if:overtime_applicable,1', 'nullable', ValidationRule::in(['hourly_rate', 'fixed_rate', 'salary_formula'])],
                'overtime_rate' => ['required_if:overtime_applicable,1', 'nullable', 'numeric', 'min:0'],
                'overtime_rounding_minutes' => ['nullable', 'integer', 'min:0', 'max:60'],
                'maximum_overtime_per_day_minutes' => ['nullable', 'integer', 'min:0'],
                'approval_required' => ['boolean'],
                'weekly_off_overtime_rate' => ['nullable', 'numeric', 'min:0'],
                'holiday_overtime_rate' => ['nullable', 'numeric', 'min:0'],
            ],
            default => [],
        };
    }

    /**
     * FSD 8.2: "Conflicting active rules with the same priority and
     * applicability shall not be allowed." Two rules conflict when they
     * share category + priority + status=active and their branch/employee-type/
     * labour-type/contractor applicability AND effective-date windows overlap.
     */
    private function assertNoConflict(array $header, ?int $ignoreId): void
    {
        if ($header['status'] !== 'active') {
            return;
        }

        $candidates = BusinessRule::where('category', $header['category'])
            ->where('status', 'active')
            ->where('priority', $header['priority'])
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        $newFrom = $header['effective_from'];
        $newTo = $header['effective_to'] ?? null;
        $newBranches = $header['branch_ids'] ?? [];
        $newTypes = $header['employee_types'] ?? [];
        $newLabourTypes = $header['labour_types'] ?? [];
        $newContractors = $header['contractor_ids'] ?? [];

        foreach ($candidates as $existing) {
            $existingTo = $existing->effective_to?->toDateString();
            $dateOverlaps = ($existingTo === null || $existingTo >= $newFrom)
                && ($newTo === null || $existing->effective_from->toDateString() <= $newTo);
            if (! $dateOverlaps) {
                continue;
            }

            $branchOverlap = empty($newBranches) || empty($existing->branch_ids) || array_intersect($newBranches, $existing->branch_ids);
            $typeOverlap = empty($newTypes) || empty($existing->employee_types) || array_intersect($newTypes, $existing->employee_types);
            $labourOverlap = empty($newLabourTypes) || empty($existing->labour_types) || array_intersect($newLabourTypes, $existing->labour_types);
            $contractorOverlap = empty($newContractors) || empty($existing->contractor_ids) || array_intersect($newContractors, $existing->contractor_ids);

            if ($branchOverlap && $typeOverlap && $labourOverlap && $contractorOverlap) {
                abort(422, "This rule conflicts with existing active rule \"{$existing->name}\" — same priority and overlapping applicability/effective period.");
            }
        }
    }

    public function store(Request $request)
    {
        $category = $request->input('category');
        $header = $request->validate($this->headerRules(null, $category));
        $detail = $request->validate($this->detailRules($category));

        $this->assertNoConflict($header, null);

        $rule = DB::transaction(function () use ($header, $detail, $category) {
            $header['created_by'] = auth()->id();
            $rule = BusinessRule::create($header);

            $relation = BusinessRule::DETAIL_RELATIONS[$category] ?? null;
            if ($relation) {
                $rule->{$relation}()->create($detail);
            }

            return $rule;
        });

        return redirect()->route('rule-engine.index')->with('success', "Rule \"{$rule->name}\" created successfully.");
    }

    public function update(Request $request, BusinessRule $rule)
    {
        // Category is fixed after creation — changing it would orphan the
        // detail row and its category-specific validation.
        $category = $rule->category;
        $header = $request->validate($this->headerRules($rule->id, $category));
        unset($header['category']);
        $detail = $request->validate($this->detailRules($category));

        $this->assertNoConflict(array_merge($header, ['category' => $category]), $rule->id);

        DB::transaction(function () use ($rule, $header, $detail, $category) {
            $header['updated_by'] = auth()->id();
            $rule->update($header);

            $relation = BusinessRule::DETAIL_RELATIONS[$category] ?? null;
            if ($relation) {
                $rule->{$relation}()->updateOrCreate(['rule_id' => $rule->id], $detail);
            }
        });

        return redirect()->route('rule-engine.index')->with('success', "Rule \"{$rule->name}\" updated successfully.");
    }

    public function destroy(BusinessRule $rule)
    {
        $rule->delete();
        return redirect()->route('rule-engine.index')->with('success', 'Rule deleted successfully.');
    }

    /**
     * AJAX endpoint backing the Employee Number Rule's "Sample Employee
     * Number" preview field.
     */
    public function previewEmployeeNumber(Request $request, EmployeeNumberGenerator $generator)
    {
        $data = $request->validate([
            'employee_category' => ['required', ValidationRule::in(['staff', 'company_labour', 'contract_labour'])],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'contractor_id' => ['nullable', 'exists:contractors,id'],
            'prefix' => ['nullable', 'string', 'max:20'],
            'include_branch_code' => ['boolean'],
            'include_contractor_code' => ['boolean'],
            'separator' => ['nullable', 'string', 'max:5'],
            'sequence_start' => ['required', 'integer', 'min:1'],
            'sequence_length' => ['required', 'integer', 'min:1', 'max:10'],
            'include_financial_year' => ['boolean'],
            'include_calendar_year' => ['boolean'],
            'reset_frequency' => ['required', ValidationRule::in(['never', 'yearly', 'financial_yearly', 'branch_wise'])],
        ]);

        $rule = new BusinessRule();
        $detail = new \App\Models\EmployeeNumberRule($data);
        $rule->setRelation('employeeNumberRule', $detail);

        return response()->json(['sample' => $generator->preview($rule, $data['branch_id'] ?? null, $data['contractor_id'] ?? null)]);
    }
}
