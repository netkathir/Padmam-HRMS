@extends('layouts.app')
@section('title','Generate Payroll')
@section('page-title','Generate Payroll')
@section('page-subtitle','Process monthly salary computation')
@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Payroll Parameters</h6></div>
            <div class="card-body">
                <form action="{{ route('payroll.generate.post') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Month <span class="text-danger">*</span></label>
                        <select name="month" class="form-select @error('month') is-invalid @enderror" required>
                            @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ old('month', now()->month) == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>
                            @endfor
                        </select>
                        @error('month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Year <span class="text-danger">*</span></label>
                        <select name="year" class="form-select @error('year') is-invalid @enderror" required>
                            @for($y = now()->year + 1; $y >= now()->year - 2; $y--)
                            <option value="{{ $y }}" {{ old('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Working Days in Month</label>
                        <input type="number" name="working_days" min="1" max="31" class="form-control" value="{{ old('working_days', 26) }}">
                    </div>
                    <div class="alert alert-warning py-2 small"><i class="bi bi-exclamation-triangle"></i> This will overwrite any existing draft payroll for the selected period.</div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-calculator"></i> Generate Payroll</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Recent Payroll Runs</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Period</th><th>Employees</th><th>Gross</th><th>Net</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse($recentPayrolls as $run)
                            <tr>
                                <td><strong>{{ \Carbon\Carbon::create($run->year, $run->month)->format('F Y') }}</strong></td>
                                <td>{{ $run->employee_count ?? '—' }}</td>
                                <td>₹{{ number_format($run->total_gross ?? 0, 0) }}</td>
                                <td>₹{{ number_format($run->total_net ?? 0, 0) }}</td>
                                <td>
                                    @php $sc = match($run->status){ 'paid'=>'success','processed'=>'info','draft'=>'warning',default=>'secondary' }; @endphp
                                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($run->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('payroll.index') }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No payroll runs yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
