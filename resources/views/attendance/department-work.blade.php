@extends('layouts.app')
@section('title', 'Department Work Assignment')
@section('page-title', 'Department Work Assignment')
@section('page-subtitle', 'Assign an employee to a department for specific dates, at that department\'s Value Per Day')
@section('page-actions')
    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Register</a>
@endsection
@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-6">
                <label class="form-label">Employee <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-select" data-searchable required>
                    <option value="">Select…</option>
                    @foreach ($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->employee_code }} — {{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Load</button>
            </div>
        </form>

        <form action="{{ route('attendance.department-work.post') }}" method="POST">
            @csrf
            <input type="hidden" name="employee_id" value="{{ request('employee_id') }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Department <span class="text-danger">*</span></label>
                    <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror" data-searchable required>
                        <option value="">Select…</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" data-value-per-day="{{ $dept->value_per_day !== null ? number_format($dept->value_per_day, 2) : '' }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Value Per Day (₹)</label>
                    <input type="text" id="value_per_day_display" class="form-control" value="" placeholder="Select a department" disabled>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Dates <span class="text-danger">*</span></label>
                    <input type="text" id="dates_picker" class="form-control @error('dates') is-invalid @enderror" placeholder="Select one or more dates…" autocomplete="off">
                    <div id="dates_hidden_inputs"></div>
                    @error('dates')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" id="submit_btn" class="btn btn-primary" {{ request('employee_id') ? '' : 'disabled' }}><i class="bi bi-save"></i> Save Assignment</button>
                @unless(request('employee_id'))
                    <div class="form-text">Select and load an employee above first.</div>
                @endunless
            </div>
        </form>
    </div>
</div>

@if (request('employee_id'))
<div class="card">
    <div class="card-body">
        <h6 class="mb-3">Recent Assignments</h6>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead><tr><th>Date</th><th>Department</th><th class="text-end">Value Per Day (₹)</th></tr></thead>
                <tbody>
                    @forelse ($recent as $r)
                    <tr>
                        <td>{{ $r->work_date->format('d-M-Y') }}</td>
                        <td>{{ $r->department->name ?? '—' }}</td>
                        <td class="text-end">{{ number_format($r->value_per_day, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted py-3">No assignments recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
    (function () {
        const departmentSelect = document.getElementById('department_id');
        const valueDisplay = document.getElementById('value_per_day_display');
        function refreshValuePerDay() {
            const opt = departmentSelect.options[departmentSelect.selectedIndex];
            valueDisplay.value = opt && opt.dataset.valuePerDay ? opt.dataset.valuePerDay : '';
        }
        departmentSelect.addEventListener('change', refreshValuePerDay);
        refreshValuePerDay();

        const datesInput = document.getElementById('dates_picker');
        const hiddenContainer = document.getElementById('dates_hidden_inputs');
        flatpickr(datesInput, {
            mode: 'multiple',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd-M-Y',
            static: true,
            disableMobile: true,
            onChange: function (selectedDates, dateStr, instance) {
                hiddenContainer.innerHTML = '';
                selectedDates.forEach(function (d) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'dates[]';
                    input.value = instance.formatDate(d, 'Y-m-d');
                    hiddenContainer.appendChild(input);
                });
            },
        });
    })();
</script>
@endpush
