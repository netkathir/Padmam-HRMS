@php $d = $rule->weeklyOffRule ?? null; $selectedDays = old('weekly_off_days', $d->weekly_off_days ?? ['sunday']); @endphp
<div class="rule-category-fields" data-category="weekly_off" style="display:none">
    <div class="row g-3">
        <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Weekly Off and Sunday Rule</h6></div>
        <div class="col-md-8">
            <label class="form-label">Weekly Off Day <span class="text-danger">*</span></label>
            <div class="border rounded p-2 d-flex flex-wrap gap-3">
                @foreach(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                <div class="form-check">
                    <input type="checkbox" name="weekly_off_days[]" class="form-check-input" id="wd_{{ $day }}" value="{{ $day }}" {{ in_array($day, $selectedDays) ? 'checked' : '' }}>
                    <label class="form-check-label" for="wd_{{ $day }}">{{ ucfirst($day) }}</label>
                </div>
                @endforeach
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-check mt-4">
                <input type="hidden" name="is_paid" value="0">
                <input type="checkbox" name="is_paid" class="form-check-input" value="1" {{ old('is_paid', $d->is_paid ?? true) ? 'checked' : '' }}>
                <label class="form-check-label">Paid Weekly Off</label>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label">Min. Attendance Condition <span class="text-muted">(days, optional)</span></label>
            <input type="number" name="min_attendance_condition" class="form-control" min="0" value="{{ old('min_attendance_condition', $d->min_attendance_condition ?? '') }}">
        </div>
    </div>
</div>
