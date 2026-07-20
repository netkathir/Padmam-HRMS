@extends('layouts.app')
@section('title', 'Upload Summary')
@section('page-title', 'Upload Validation Summary')
@section('page-subtitle', $upload->original_filename)
@section('back-url', route('attendance.index', ['from_date' => $upload->period_from->toDateString(), 'to_date' => $upload->period_to->toDateString()]))
@section('page-actions')
    <a href="{{ route('attendance.index', ['from_date' => $upload->period_from->toDateString(), 'to_date' => $upload->period_to->toDateString()]) }}" class="btn btn-primary btn-sm"><i class="bi bi-calendar-check"></i> View Attendance Register</a>
@endsection
@section('content')
<div class="row g-3">
    @php
        $tiles = [
            ['label' => 'Total Rows', 'value' => $upload->total_rows, 'color' => 'primary'],
            ['label' => 'New Rows Saved', 'value' => $upload->valid_rows, 'color' => 'success'],
            ['label' => 'Rows Updated', 'value' => $upload->updated_rows, 'color' => 'info'],
            ['label' => 'Invalid Rows', 'value' => $upload->invalid_rows, 'color' => 'danger'],
            ['label' => 'Repeated Punches (within 3 min)', 'value' => $upload->duplicate_rows, 'color' => 'warning'],
            ['label' => 'Unknown Employees', 'value' => $upload->unknown_employee_rows, 'color' => 'secondary'],
            ['label' => 'Invalid Dates', 'value' => $upload->invalid_date_rows, 'color' => 'secondary'],
            ['label' => 'Invalid Times', 'value' => $upload->invalid_time_rows, 'color' => 'secondary'],
        ];
    @endphp
    @foreach ($tiles as $tile)
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="fs-3 fw-bold text-{{ $tile['color'] }}">{{ $tile['value'] }}</div>
                <div class="text-muted small">{{ $tile['label'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>
<div class="card mt-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Branch</dt><dd class="col-sm-9">{{ $upload->branch->name ?? '—' }}</dd>
            <dt class="col-sm-3">Period</dt><dd class="col-sm-9">{{ $upload->period_from->format('d M Y') }} to {{ $upload->period_to->format('d M Y') }}</dd>
            <dt class="col-sm-3">Sheet</dt><dd class="col-sm-9">{{ $upload->sheet_name ?? '—' }}</dd>
            <dt class="col-sm-3">Uploaded By</dt><dd class="col-sm-9">{{ $upload->uploader->name ?? '—' }} on {{ $upload->created_at->format('d M Y H:i') }}</dd>
            <dt class="col-sm-3">Remarks</dt><dd class="col-sm-9">{{ $upload->remarks ?? '—' }}</dd>
        </dl>
        @if ($upload->error_file_path)
            <a href="{{ route('attendance.upload.errors', $upload) }}" class="btn btn-outline-danger btn-sm mt-2">
                <i class="bi bi-file-earmark-excel"></i> Download Error File
            </a>
        @endif
        <p class="text-muted small mt-3 mb-0">Attendance has already been computed for the employees and dates found in this file — no separate processing step is needed.</p>
    </div>
</div>
@endsection
