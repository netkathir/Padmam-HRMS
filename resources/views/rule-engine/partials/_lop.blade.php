@php $d = $rule->lopRule ?? null; @endphp
<div class="rule-category-fields" data-category="lop" style="display:none">
    <div class="row g-3">
        <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">LOP Rule</h6></div>
        <div class="col-md-4">
            <label class="form-label">LOP Calculation Basis <span class="text-danger">*</span></label>
            <select name="calculation_basis" class="form-select" id="lop-calculation-basis">
                @foreach(['calendar_days'=>'Calendar Days','working_days'=>'Working Days','fixed_days'=>'Fixed Days'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('calculation_basis', $d->calculation_basis ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4" id="fixed-payroll-days-wrapper">
            <label class="form-label">Fixed Payroll Days <span class="text-muted">(when Fixed Days)</span></label>
            <input type="number" name="fixed_payroll_days" class="form-control" min="1" max="31" value="{{ old('fixed_payroll_days', $d->fixed_payroll_days ?? 30) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Half-Day LOP Value <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="half_day_lop_value" class="form-control" value="{{ old('half_day_lop_value', $d->half_day_lop_value ?? 0.5) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Full-Day LOP Value <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="full_day_lop_value" class="form-control" value="{{ old('full_day_lop_value', $d->full_day_lop_value ?? 1) }}">
        </div>
        <div class="col-12">
            <div class="d-flex flex-wrap gap-4">
                @foreach(['unpaid_leave_as_lop'=>'Unpaid Leave as LOP','absent_day_as_lop'=>'Absent Day as LOP','missing_punch_as_lop'=>'Missing Punch as LOP','holiday_between_absences'=>'Holiday Between Absences Counts','weekly_off_between_absences'=>'Weekly Off Between Absences Counts','manual_lop_adjustment_allowed'=>'Manual LOP Adjustment Allowed'] as $field=>$label)
                <div class="form-check">
                    <input type="hidden" name="{{ $field }}" value="0">
                    <input type="checkbox" name="{{ $field }}" class="form-check-input" value="1" {{ old($field, $d->{$field} ?? in_array($field, ['unpaid_leave_as_lop','absent_day_as_lop','missing_punch_as_lop','manual_lop_adjustment_allowed'])) ? 'checked' : '' }}>
                    <label class="form-check-label">{{ $label }}</label>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Late Count Conversion <span class="text-muted">(e.g. 3 lates = 0.5 day LOP)</span></label>
            <input type="number" name="late_count_conversion" class="form-control" min="0" value="{{ old('late_count_conversion', $d->late_count_conversion ?? '') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Early Exit Conversion</label>
            <input type="number" name="early_exit_conversion" class="form-control" min="0" value="{{ old('early_exit_conversion', $d->early_exit_conversion ?? '') }}">
        </div>
    </div>
</div>
