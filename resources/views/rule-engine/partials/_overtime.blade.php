@php $d = $rule->overtimeRule ?? null; @endphp
<div class="rule-category-fields" data-category="overtime" style="display:none">
    <div class="row g-3">
        <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Overtime Rule</h6></div>
        <div class="col-md-3">
            <div class="form-check mt-2">
                <input type="hidden" name="overtime_applicable" value="0">
                <input type="checkbox" name="overtime_applicable" class="form-check-input" value="1" {{ old('overtime_applicable', $d->overtime_applicable ?? true) ? 'checked' : '' }}>
                <label class="form-check-label">Overtime Applicable</label>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Minimum Overtime Minutes</label>
            <input type="number" name="minimum_overtime_minutes" class="form-control" min="0" value="{{ old('minimum_overtime_minutes', $d->minimum_overtime_minutes ?? 15) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Overtime Calculation</label>
            <select name="overtime_calculation" class="form-select">
                <option value="">Select</option>
                @foreach(['hourly_rate'=>'Hourly Rate','fixed_rate'=>'Fixed Rate','salary_formula'=>'Salary Formula'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('overtime_calculation', $d->overtime_calculation ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Overtime Rate</label>
            <input type="number" step="0.01" name="overtime_rate" class="form-control" value="{{ old('overtime_rate', $d->overtime_rate ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Overtime Rounding <span class="text-muted">(minutes, 0 = none)</span></label>
            <input type="number" name="overtime_rounding_minutes" class="form-control" min="0" max="60" value="{{ old('overtime_rounding_minutes', $d->overtime_rounding_minutes ?? 0) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Maximum Overtime/Day <span class="text-muted">(minutes)</span></label>
            <input type="number" name="maximum_overtime_per_day_minutes" class="form-control" min="0" value="{{ old('maximum_overtime_per_day_minutes', $d->maximum_overtime_per_day_minutes ?? '') }}">
        </div>
        <div class="col-md-3">
            <div class="form-check mt-4">
                <input type="hidden" name="approval_required" value="0">
                <input type="checkbox" name="approval_required" class="form-check-input" value="1" {{ old('approval_required', $d->approval_required ?? true) ? 'checked' : '' }}>
                <label class="form-check-label">Approval Required</label>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Weekly Off Overtime Rate</label>
            <input type="number" step="0.01" name="weekly_off_overtime_rate" class="form-control" value="{{ old('weekly_off_overtime_rate', $d->weekly_off_overtime_rate ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Holiday Overtime Rate</label>
            <input type="number" step="0.01" name="holiday_overtime_rate" class="form-control" value="{{ old('holiday_overtime_rate', $d->holiday_overtime_rate ?? '') }}">
        </div>
    </div>
</div>
