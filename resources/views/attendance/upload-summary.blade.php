@extends('layouts.app')
@section('title', 'Upload Summary')
@section('page-title', 'Upload Validation Summary')
@section('page-subtitle', $upload->original_filename)
@section('page-actions')
    <a href="{{ route('attendance.process.form') }}" class="btn btn-primary btn-sm"><i class="bi bi-gear"></i> Process Attendance</a>
    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Register</a>
@endsection
@section('content')
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="row g-3">
    @php
        $tiles = [
            ['label' => 'Total Rows', 'value' => $upload->total_rows, 'color' => 'primary'],
            ['label' => 'Valid Rows', 'value' => $upload->valid_rows, 'color' => 'success'],
            ['label' => 'Invalid Rows', 'value' => $upload->invalid_rows, 'color' => 'danger'],
            ['label' => 'Duplicate Rows', 'value' => $upload->duplicate_rows, 'color' => 'warning'],
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
    </div>
</div>
@endsection
