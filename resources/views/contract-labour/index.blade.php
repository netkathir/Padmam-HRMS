@extends('layouts.app')
@section('title', 'Contract Labour Assignment')
@section('page-title', 'Contract Labour Assignment')
@section('page-subtitle', 'Assign and manage employees under each contractor')

@section('content')

    {{-- Contractor Selector --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold mb-1">Select Contractor</label>
                    <select name="contractor_id" class="form-select" onchange="this.form.submit()">
                        <option value="">— Choose a Contractor —</option>
                        @foreach($contractors as $c)
                            <option value="{{ $c->id }}" {{ optional($contractor)->id == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary"><i class="bi bi-search"></i> Load</button>
                </div>
                @if($contractor)
                    <div class="col-auto ms-auto">
                        <a href="{{ route('masters.contractors.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-building"></i> All Contractors
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if($contractor)

        {{-- Summary strip --}}
        <div class="row g-2 mb-3">
            <div class="col-auto">
                <div class="px-3 py-2 rounded bg-primary-subtle text-primary fw-semibold small">
                    <i class="bi bi-person-workspace me-1"></i>{{ $contractor->name }}
                    @if($contractor->company_name)
                        <span class="text-muted fw-normal"> — {{ $contractor->company_name }}</span>
                    @endif
                </div>
            </div>
            <div class="col-auto">
                <div class="px-3 py-2 rounded bg-success-subtle text-success fw-semibold small">
                    <i class="bi bi-people me-1"></i>{{ $employees->total() }} Assigned
                </div>
            </div>
            <div class="col-auto">
                <div class="px-3 py-2 rounded bg-secondary-subtle text-secondary fw-semibold small">
                    <i class="bi bi-person-dash me-1"></i>{{ $unassignedEmployees->count() }} Unassigned
                </div>
            </div>
        </div>

        <div class="row g-3">

            {{-- Assigned Employees --}}
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people text-primary"></i> Assigned Employees ({{ $employees->total() }})</span>
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
                                            <td class="fw-semibold">{{ $emp->full_name }}</td>
                                            <td><small>{{ $emp->department->name ?? '—' }}</small></td>
                                            <td><small>{{ $emp->designation->name ?? '—' }}</small></td>
                                            <td>
                                                <form action="{{ route('masters.contractors.labour.remove', [$contractor, $emp]) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Remove {{ $emp->full_name }} from this contractor?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" title="Remove">
                                                        <i class="bi bi-person-x"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
                                                No employees assigned to this contractor yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($employees->hasPages())
                        <div class="card-footer d-flex justify-content-end">
                            {{ $employees->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Assign Panel --}}
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header"><i class="bi bi-person-plus text-success"></i> Assign Employee</div>
                    <div class="card-body">
                        @if($unassignedEmployees->count() > 0)
                            <form action="{{ route('masters.contractors.labour.assign', $contractor) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Select Employee <span class="text-danger">*</span></label>
                                    <select name="employee_id" class="form-select" required>
                                        <option value="">— Select Employee —</option>
                                        @foreach($unassignedEmployees as $emp)
                                            <option value="{{ $emp->id }}">
                                                {{ $emp->employee_code }} — {{ $emp->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Only active employees not yet assigned to any contractor are listed.</div>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-plus-lg"></i> Assign to Contractor
                                </button>
                            </form>
                        @else
                            <div class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                All active employees are already assigned to a contractor.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

    @else

        {{-- Empty state --}}
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-person-workspace fs-1 d-block mb-3 opacity-25"></i>
                <div class="fw-semibold mb-1">Select a Contractor to Begin</div>
                <small>Choose a contractor from the dropdown above to view and manage assigned employees.</small>
            </div>
        </div>

    @endif

@endsection
