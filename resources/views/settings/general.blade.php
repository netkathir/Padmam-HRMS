@extends('layouts.app')
@section('title','General Settings')
@section('page-title','General Settings')
@section('page-subtitle','Application defaults and preferences')
@section('page-actions')
    <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Settings</a>
@endsection
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="card">
    <div class="card-body">
        <form action="{{ route('settings.general.update') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-12"><h6 class="text-primary border-bottom pb-1">Localisation</h6></div>
                <div class="col-md-4">
                    <label class="form-label">Timezone</label>
                    <select name="timezone" class="form-select">
                        @foreach(\DateTimeZone::listIdentifiers() as $tz)
                        <option value="{{ $tz }}" {{ ($settings['timezone'] ?? 'Asia/Kolkata') == $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date Format</label>
                    <select name="date_format" class="form-select">
                        @foreach(['d/m/Y'=>'DD/MM/YYYY','m/d/Y'=>'MM/DD/YYYY','Y-m-d'=>'YYYY-MM-DD','d M Y'=>'DD Mon YYYY'] as $val => $label)
                        <option value="{{ $val }}" {{ ($settings['date_format'] ?? 'd/m/Y') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Currency Symbol</label>
                    <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '₹') }}" maxlength="5">
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Attendance & Payroll Defaults</h6></div>
                <div class="col-md-3">
                    <label class="form-label">Default Working Days/Month</label>
                    <input type="number" name="default_working_days" min="20" max="31" class="form-control" value="{{ old('default_working_days', $settings['default_working_days'] ?? 26) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Working Hours/Day</label>
                    <input type="number" name="working_hours_per_day" min="4" max="12" step="0.5" class="form-control" value="{{ old('working_hours_per_day', $settings['working_hours_per_day'] ?? 8) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Week Start Day</label>
                    <select name="week_start_day" class="form-select">
                        @foreach(['monday'=>'Monday','sunday'=>'Sunday','saturday'=>'Saturday'] as $val => $label)
                        <option value="{{ $val }}" {{ ($settings['week_start_day'] ?? 'monday') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pay Day</label>
                    <input type="number" name="pay_day" min="1" max="31" class="form-control" value="{{ old('pay_day', $settings['pay_day'] ?? 1) }}" placeholder="e.g. 1 = 1st of month">
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Leave Policy</h6></div>
                <div class="col-md-4">
                    <label class="form-label">Leave Year Start Month</label>
                    <select name="leave_year_start_month" class="form-select">
                        @for($m=1; $m<=12; $m++)
                        <option value="{{ $m }}" {{ ($settings['leave_year_start_month'] ?? 1) == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Max Carry Forward Days</label>
                    <input type="number" name="max_carry_forward_days" min="0" max="365" class="form-control" value="{{ old('max_carry_forward_days', $settings['max_carry_forward_days'] ?? 15) }}">
                </div>
                <div class="col-md-4">
                    <div class="form-check mt-4">
                        <input type="hidden" name="allow_leave_encashment" value="0">
                        <input type="checkbox" name="allow_leave_encashment" class="form-check-input" value="1" {{ ($settings['allow_leave_encashment'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label">Allow Leave Encashment</label>
                    </div>
                </div>

                <div class="col-12"><h6 class="text-primary border-bottom pb-1 mt-2">Employee Code Generation</h6></div>
                <div class="col-md-4">
                    <label class="form-label">Prefix</label>
                    <input type="text" name="employee_code_prefix" class="form-control" value="{{ old('employee_code_prefix', $settings['employee_code_prefix'] ?? 'EMP') }}" maxlength="10">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Starting Number</label>
                    <input type="number" name="employee_code_start" min="1" class="form-control" value="{{ old('employee_code_start', $settings['employee_code_start'] ?? 1001) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Padding Digits</label>
                    <input type="number" name="employee_code_padding" min="3" max="8" class="form-control" value="{{ old('employee_code_padding', $settings['employee_code_padding'] ?? 4) }}">
                    <div class="form-text">e.g. EMP0001 with 4-digit padding</div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Settings</button>
                <a href="{{ route('settings.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
