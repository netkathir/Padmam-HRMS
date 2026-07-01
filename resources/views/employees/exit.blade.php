@extends('layouts.app')

@section('title', 'Exit Management')
@section('page-title', 'Exit Management')
@section('page-subtitle', $employee->full_name . ' — ' . $employee->employee_code)

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('employees.exit.store', $employee) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Exit Type <span class="text-danger">*</span></label>
                        <select name="exit_type" class="form-select" required>
                            <option value="resignation">Resignation</option>
                            <option value="termination">Termination</option>
                            <option value="retirement">Retirement</option>
                            <option value="absconding">Absconding</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Resignation Date <span class="text-danger">*</span></label>
                        <input type="date" name="resignation_date" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last Working Date <span class="text-danger">*</span></label>
                        <input type="date" name="last_working_date" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Exit Reason <span class="text-danger">*</span></label>
                        <textarea name="exit_reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-danger"><i class="bi bi-box-arrow-right"></i> Process
                        Exit</button>
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
