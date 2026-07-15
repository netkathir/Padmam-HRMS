@extends('layouts.app')
@section('title','Settings')
@section('page-title','Settings')
@section('page-subtitle','Application configuration')
@section('content')
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-building fs-1 text-primary d-block mb-2"></i>
                <h6>Company Settings</h6>
                <p class="text-muted small mb-3">Update company name, logo, address, and statutory information.</p>
                <a href="{{ route('settings.company') }}" class="btn btn-primary btn-sm"><i class="bi bi-arrow-right"></i> Configure</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-gear fs-1 text-success d-block mb-2"></i>
                <h6>General Settings</h6>
                <p class="text-muted small mb-3">Configure time zone, date format, currency, and application defaults.</p>
                <a href="{{ route('settings.general') }}" class="btn btn-success btn-sm"><i class="bi bi-arrow-right"></i> Configure</a>
            </div>
        </div>
    </div>
</div>
@endsection
