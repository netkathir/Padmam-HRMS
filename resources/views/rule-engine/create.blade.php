@extends('layouts.app')
@section('title','Add Rule')
@section('page-title','Add Rule')
@section('page-subtitle','Configure a new Rule Engine rule')
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('rule-engine.store') }}" method="POST" id="rule-form">
            @csrf
            <div class="row g-3">
                @include('rule-engine.partials._common', ['rule' => null])
                @include('rule-engine.partials._employee_number', ['rule' => null])
                @include('rule-engine.partials._attendance', ['rule' => null])
                @include('rule-engine.partials._weekly_off', ['rule' => null])
                @include('rule-engine.partials._lop', ['rule' => null])
                @include('rule-engine.partials._pf', ['rule' => null])
                @include('rule-engine.partials._esi', ['rule' => null])
                @include('rule-engine.partials._tds', ['rule' => null])
                @include('rule-engine.partials._overtime', ['rule' => null])
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit</button>
                <a href="{{ route('rule-engine.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@include('rule-engine.partials._script')
@endsection
