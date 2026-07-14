@extends('layouts.app')
@section('title', 'Sheet & Column Mapping')
@section('page-title', 'Sheet & Column Mapping')
@section('page-subtitle', $upload->original_filename)
@section('content')
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card">
            <div class="card-body">
                @if (count($sheetNames) > 1)
                <form method="GET" class="mb-3 row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Sheet Name <span class="text-danger">*</span></label>
                        <select name="sheet_name" class="form-select" onchange="this.form.submit()">
                            @foreach ($sheetNames as $name)
                                <option value="{{ $name }}" {{ $selectedSheet === $name ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                @endif

                <form action="{{ route('attendance.upload.confirm', $upload) }}" method="POST">
                    @csrf
                    <input type="hidden" name="sheet_name" value="{{ $selectedSheet }}">

                    @if (empty($headerRow))
                        <div class="alert alert-warning">No header row could be detected in this sheet.</div>
                    @else
                        <p class="text-muted small">Map each expected field to the matching column detected in your file. Employee Number or Biometric ID must be mapped; Punch Date and Punch Time are mandatory.</p>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Expected Field</th><th>Excel Column</th></tr></thead>
                                <tbody>
                                    @foreach (\App\Services\BiometricUploadService::EXPECTED_FIELDS as $field => $label)
                                    <tr>
                                        <td>
                                            {{ $label }}
                                            @if (in_array($field, ['punch_date','punch_time']))
                                                <span class="text-danger">*</span>
                                            @endif
                                        </td>
                                        <td>
                                            <select name="mapping[{{ $field }}]" class="form-select form-select-sm" {{ in_array($field, ['punch_date','punch_time']) ? 'required' : '' }}>
                                                <option value="">— Not Mapped —</option>
                                                @foreach ($headerRow as $col => $text)
                                                    <option value="{{ $col }}" {{ ($guessedMapping[$field] ?? null) === $col ? 'selected' : '' }}>{{ $text }} (Column {{ $col }})</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Confirm Mapping &amp; Validate</button>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
