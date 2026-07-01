@extends('layouts.app')
@section('title', 'Calculate Contract Payroll')
@section('page-title', 'Calculate Contract Payroll')
@section('page-subtitle', 'Generate wages from attendance records')

@section('page-actions')
    <a href="{{ route('contract-payroll.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Payroll List
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-calculator"></i> Payroll Calculation Settings</div>
            <div class="card-body">

                <div class="alert alert-info border-0 mb-4" style="background:#eff6ff;">
                    <i class="bi bi-info-circle-fill text-info me-2"></i>
                    Payroll is calculated from daily attendance records.
                    If a record exists for the month it will be <strong>updated</strong>.
                    <ul class="mb-0 mt-2 small">
                        <li>Daily rate: wage × effective days (+ 1.5× OT rate)</li>
                        <li>Monthly rate: pro-rated by attendance / working days (Mon–Sat)</li>
                    </ul>
                </div>

                <form action="{{ route('contract-payroll.calculate.post') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Contractor <span class="text-danger">*</span></label>
                        <select name="contractor_id" class="form-select @error('contractor_id') is-invalid @enderror" required>
                            <option value="">— Select Contractor —</option>
                            @foreach($contractors as $c)
                                <option value="{{ $c->id }}" {{ old('contractor_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('contractor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Month <span class="text-danger">*</span></label>
                            <select name="month" class="form-select" required>
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ old('month', now()->month) == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Year <span class="text-danger">*</span></label>
                            <select name="year" class="form-select" required>
                                @foreach(range(now()->year - 1, now()->year + 1) as $y)
                                    <option value="{{ $y }}" {{ old('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-play-circle"></i> Generate Payroll
                        </button>
                        <a href="{{ route('contract-payroll.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection
