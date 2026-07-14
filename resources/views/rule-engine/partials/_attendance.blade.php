@php $d = $rule->attendanceRule ?? null; $selectedShifts = old('shift_ids', $d->shift_ids ?? []); @endphp
<div class="rule-category-fields" data-category="attendance" style="display:none">
    <div class="row g-3">
        <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Attendance Rule</h6></div>
        <div class="col-md-6">
            <label class="form-label">Shift <span class="text-muted">(blank = all shifts)</span></label>
            <div class="border rounded p-2" style="max-height:120px;overflow-y:auto;">
                @foreach($shifts as $s)
                <div class="form-check">
                    <input type="checkbox" name="shift_ids[]" class="form-check-input" id="ar_shift_{{ $s->id }}" value="{{ $s->id }}" {{ in_array((string)$s->id, array_map('strval', $selectedShifts)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="ar_shift_{{ $s->id }}">{{ $s->name }}</label>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Minimum Full-Day Hours <span class="text-danger">*</span></label>
            <input type="number" step="0.25" name="min_full_day_hours" class="form-control" value="{{ old('min_full_day_hours', $d->min_full_day_hours ?? 8) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Minimum Half-Day Hours <span class="text-danger">*</span></label>
            <input type="number" step="0.25" name="min_half_day_hours" class="form-control" value="{{ old('min_half_day_hours', $d->min_half_day_hours ?? 4) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Late Grace Minutes</label>
            <input type="number" name="late_grace_minutes" class="form-control" min="0" value="{{ old('late_grace_minutes', $d->late_grace_minutes ?? 0) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Early Exit Grace Minutes</label>
            <input type="number" name="early_exit_grace_minutes" class="form-control" min="0" value="{{ old('early_exit_grace_minutes', $d->early_exit_grace_minutes ?? 0) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Missing Punch Treatment <span class="text-danger">*</span></label>
            <select name="missing_punch_treatment" class="form-select">
                @foreach(['absent'=>'Absent','half_day'=>'Half Day','pending_review'=>'Pending Review'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('missing_punch_treatment', $d->missing_punch_treatment ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Single Punch Treatment <span class="text-danger">*</span></label>
            <select name="single_punch_treatment" class="form-select">
                @foreach(['absent'=>'Absent','half_day'=>'Half Day','pending_review'=>'Pending Review'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('single_punch_treatment', $d->single_punch_treatment ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Multiple Punch Handling <span class="text-danger">*</span></label>
            <input type="text" name="multiple_punch_handling" class="form-control" value="{{ old('multiple_punch_handling', $d->multiple_punch_handling ?? 'first_in_last_out') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Attendance Rounding <span class="text-muted">(minutes, 0 = none)</span></label>
            <input type="number" name="rounding_minutes" class="form-control" min="0" max="60" value="{{ old('rounding_minutes', $d->rounding_minutes ?? 0) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Weekly Off Treatment <span class="text-danger">*</span></label>
            <select name="weekly_off_treatment" class="form-select">
                @foreach(['paid'=>'Paid','unpaid'=>'Unpaid','conditional'=>'Conditional'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('weekly_off_treatment', $d->weekly_off_treatment ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Holiday Treatment <span class="text-danger">*</span></label>
            <select name="holiday_treatment" class="form-select">
                @foreach(['paid'=>'Paid','unpaid'=>'Unpaid','conditional'=>'Conditional'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('holiday_treatment', $d->holiday_treatment ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Work on Holiday Treatment <span class="text-danger">*</span></label>
            <select name="work_on_holiday_treatment" class="form-select">
                @foreach(['overtime'=>'Overtime','compensatory_off'=>'Compensatory Off','normal_day'=>'Normal Day'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('work_on_holiday_treatment', $d->work_on_holiday_treatment ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Work on Weekly Off Treatment <span class="text-danger">*</span></label>
            <select name="work_on_weekly_off_treatment" class="form-select">
                @foreach(['overtime'=>'Overtime','compensatory_off'=>'Compensatory Off','normal_day'=>'Normal Day'] as $val=>$label)
                    <option value="{{ $val }}" {{ old('work_on_weekly_off_treatment', $d->work_on_weekly_off_treatment ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Consecutive Absence Rule <span class="text-muted">(optional, days)</span></label>
            <input type="number" name="consecutive_absence_rule" class="form-control" min="0" value="{{ old('consecutive_absence_rule', $d->consecutive_absence_rule ?? '') }}">
        </div>
    </div>
</div>
