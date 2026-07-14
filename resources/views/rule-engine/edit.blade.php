@extends('layouts.app')
@section('title','Edit Rule')
@section('page-title','Edit Rule')
@section('page-subtitle',$rule->name)
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('rule-engine.update', $rule) }}" method="POST" id="rule-form">
            @csrf @method('PUT')
            <div class="row g-3">
                @include('rule-engine.partials._common', ['rule' => $rule])
                @include('rule-engine.partials._employee_number', ['rule' => $rule])
                @include('rule-engine.partials._attendance', ['rule' => $rule])
                @include('rule-engine.partials._weekly_off', ['rule' => $rule])
                @include('rule-engine.partials._lop', ['rule' => $rule])
                @include('rule-engine.partials._pf', ['rule' => $rule])
                @include('rule-engine.partials._esi', ['rule' => $rule])
                @include('rule-engine.partials._tds', ['rule' => $rule])
                @include('rule-engine.partials._overtime', ['rule' => $rule])
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('rule-engine.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@include('rule-engine.partials._script')
@endsection
