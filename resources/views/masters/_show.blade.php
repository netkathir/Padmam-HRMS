@extends('layouts.app')
@section('title', $title)
@section('page-title', $title)
@section('page-subtitle', 'Record details')
@section('page-actions')
    @if($editRoute)
        <a href="{{ $editRoute }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
    @endif
    <a href="{{ $indexRoute }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-3">
            @foreach($fields as $label => $value)
                <div class="col-md-6">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fw-medium">{{ $value === '' || $value === null ? '—' : $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
