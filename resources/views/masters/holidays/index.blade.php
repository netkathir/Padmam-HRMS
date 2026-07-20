@extends('layouts.app')
@section('title','Holidays')
@section('page-title','Holidays')
@section('page-subtitle','Manage public and optional holidays')
@section('page-actions')
    <a href="{{ route('masters.holidays.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Holiday</a>
    <a href="{{ route('masters.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Masters</a>
@endsection
@section('content')
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
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Holiday</th><th>Start Date</th><th>End Date</th><th>Applicable Employee Types</th><th>Paid</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @php $typeLabels = ['staff'=>'Staff','company_labour'=>'Company Labour','contract_labour'=>'Contract Labour']; @endphp
                    @forelse($holidays as $holiday)
                    <tr>
                        <td>{{ $holidays->firstItem() + $loop->index }}</td>
                        <td>{{ $holiday->name }}</td>
                        <td>{{ $holiday->start_date->format('d M Y') }} <small class="text-muted">({{ $holiday->start_date->format('l') }})</small></td>
                        <td>{{ $holiday->end_date->format('d M Y') }} <small class="text-muted">({{ $holiday->end_date->format('l') }})</small></td>
                        <td>{{ collect($holiday->applicable_employee_types ?? [])->map(fn($t) => $typeLabels[$t] ?? $t)->join(', ') ?: '—' }}</td>
                        <td><span class="badge bg-{{ $holiday->is_paid ? 'success' : 'secondary' }}-subtle text-{{ $holiday->is_paid ? 'success' : 'secondary' }}">{{ $holiday->is_paid ? 'Paid' : 'Unpaid' }}</span></td>
                        <td><span class="badge bg-{{ $holiday->is_active ? 'success' : 'danger' }}-subtle text-{{ $holiday->is_active ? 'success' : 'danger' }}">{{ $holiday->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.generic.show', ['module' => 'holidays', 'id' => $holiday->id]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('masters.holidays.edit', $holiday) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.holidays.destroy', $holiday) }}" method="POST" class="d-inline" data-confirm-delete="Delete this holiday?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No holidays found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $holidays->links() }}</div>
    </div>
</div>
@endsection
