@extends('layouts.app')
@section('title','Holidays')
@section('page-title','Holidays')
@section('page-subtitle','Manage public and optional holidays')
@section('page-actions')
    <a href="{{ route('masters.holidays.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Holiday</a>
    <a href="{{ route('masters.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Masters</a>
@endsection
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="card mb-3">
    <div class="card-body">
        <h6 class="text-primary mb-2">Sunday Pay Policy</h6>
        <p class="text-muted small mb-2">Sunday is always a paid weekly holiday for Staff. Configure whether it is paid for Company Labour and Contract Labour.</p>
        <form action="{{ route('masters.holidays.sunday-policy') }}" method="POST" class="d-flex gap-4 align-items-center flex-wrap">
            @csrf
            <div class="form-check">
                <input type="hidden" name="sunday_paid_company_labour" value="0">
                <input type="checkbox" name="sunday_paid_company_labour" class="form-check-input" value="1" {{ $sundayPaidCompanyLabour ? 'checked' : '' }}>
                <label class="form-check-label">Sunday Paid — Company Labour</label>
            </div>
            <div class="form-check">
                <input type="hidden" name="sunday_paid_contract_labour" value="0">
                <input type="checkbox" name="sunday_paid_contract_labour" class="form-check-input" value="1" {{ $sundayPaidContractLabour ? 'checked' : '' }}>
                <label class="form-check-label">Sunday Paid — Contract Labour</label>
            </div>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save Policy</button>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-2">
                <select name="year" class="form-select form-select-sm">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="public_holiday" {{ request('type') == 'public_holiday' ? 'selected' : '' }}>Public Holiday</option>
                    <option value="festival_holiday" {{ request('type') == 'festival_holiday' ? 'selected' : '' }}>Festival Holiday</option>
                    <option value="optional" {{ request('type') == 'optional' ? 'selected' : '' }}>Optional Holiday</option>
                    <option value="company_holiday" {{ request('type') == 'company_holiday' ? 'selected' : '' }}>Company Holiday</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Calendar</th><th>Date</th><th>Holiday</th><th>Type</th><th>Branch</th><th>Paid</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @php $typeColors = ['public_holiday'=>'primary','festival_holiday'=>'info','optional'=>'warning','company_holiday'=>'secondary']; $typeLabels = ['public_holiday'=>'Public','festival_holiday'=>'Festival','optional'=>'Optional','company_holiday'=>'Company']; @endphp
                    @forelse($holidays as $holiday)
                    <tr>
                        <td>{{ $holidays->firstItem() + $loop->index }}</td>
                        <td>{{ $holiday->calendar_name }}</td>
                        <td>{{ $holiday->date->format('d M Y') }} <small class="text-muted">({{ $holiday->date->format('l') }})</small></td>
                        <td>{{ $holiday->name }}</td>
                        <td><span class="badge bg-{{ $typeColors[$holiday->type] ?? 'secondary' }}-subtle text-{{ $typeColors[$holiday->type] ?? 'secondary' }}">{{ $typeLabels[$holiday->type] ?? ucfirst($holiday->type) }}</span></td>
                        <td>{{ $holiday->branch->name ?? 'All Branches' }}</td>
                        <td><span class="badge bg-{{ $holiday->is_paid ? 'success' : 'secondary' }}-subtle text-{{ $holiday->is_paid ? 'success' : 'secondary' }}">{{ $holiday->is_paid ? 'Paid' : 'Unpaid' }}</span></td>
                        <td><span class="badge bg-{{ $holiday->is_active ? 'success' : 'danger' }}-subtle text-{{ $holiday->is_active ? 'success' : 'danger' }}">{{ $holiday->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.holidays.edit', $holiday) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.holidays.destroy', $holiday) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this holiday?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No holidays found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $holidays->links() }}</div>
    </div>
</div>
@endsection
