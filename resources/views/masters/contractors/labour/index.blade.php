@extends('layouts.app')
@section('title', 'Contractor Labour - ' . $contractor->name)
@section('page-title', 'Contractor Labour')
@section('page-subtitle', $contractor->name . ' (' . $contractor->code . ')')

@section('page-actions')
    <a href="{{ route('masters.contractors.index') }}" class="btn btn-outline-secondary btn-sm"><i
            class="bi bi-arrow-left"></i> Back to Contractors</a>
@endsection

@section('content')
    <div class="row">
        {{-- Assigned Employees --}}
        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people"></i> Assigned Labours ({{ $employees->total() }})</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employees as $emp)
                                    <tr>
                                        <td>{{ $employees->firstItem() + $loop->index }}</td>
                                        <td><span class="badge bg-secondary">{{ $emp->employee_code }}</span></td>
                                        <td>{{ $emp->full_name }}</td>
                                        <td>{{ $emp->department->name ?? '—' }}</td>
                                        <td>{{ $emp->designation->name ?? '—' }}</td>
                                        <td>
                                            <form
                                                action="{{ route('masters.contractors.labour.remove', [$contractor, $emp]) }}"
                                                method="POST" class="d-inline"
                                                data-confirm-delete="Remove this employee from the contractor?">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"
                                                    title="Remove from contractor"><i class="bi bi-person-x"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No employees assigned to this
                                            contractor.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($employees->hasPages())
                    <div class="card-footer d-flex justify-content-end">{{ $employees->links() }}</div>
                @endif
            </div>
        </div>

        {{-- Assign New Employee --}}
        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><i class="bi bi-person-plus"></i> Assign Employee</div>
                <div class="card-body">
                    @if ($unassignedEmployees->count() > 0)
                        <form action="{{ route('masters.contractors.labour.assign', $contractor) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Select Employee <span class="text-danger">*</span></label>
                                <select name="employee_id" class="form-select" required>
                                    <option value="">— Select Employee —</option>
                                    @foreach ($unassignedEmployees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->employee_code }} -
                                            {{ $emp->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Assign to
                                Contractor</button>
                        </form>
                    @else
                        <p class="text-muted mb-0">All active employees are already assigned to a contractor.</p>
                    @endif
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="card mt-3">
                <div class="card-header"><i class="bi bi-info-circle"></i> Summary</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded text-center">
                                <small class="text-muted">Total Labours</small>
                                <div class="fw-bold fs-5">{{ $employees->total() }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded text-center">
                                <small class="text-muted">Unassigned</small>
                                <div class="fw-bold fs-5">{{ $unassignedEmployees->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
