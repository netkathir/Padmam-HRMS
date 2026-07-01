@extends('layouts.app')
@section('title', 'Contract Workers — ' . $contractor->name)
@section('page-title', 'Contract Workers')
@section('page-subtitle', $contractor->name . ' (' . $contractor->code . ')')

@section('page-actions')
    <a href="{{ route('masters.contractors.workers.create', $contractor) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus"></i> Add Worker
    </a>
    <div class="btn-group">
        <a href="{{ route('masters.contractors.attendance', $contractor) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-calendar-check"></i> Attendance
        </a>
        <a href="{{ route('masters.contractors.payroll', $contractor) }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-cash-stack"></i> Payroll
        </a>
        <a href="{{ route('masters.contractors.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Contractors
        </a>
    </div>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search name, phone, skill…" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active"     {{ request('status') == 'active'     ? 'selected' : '' }}>Active</option>
                    <option value="inactive"   {{ request('status') == 'inactive'   ? 'selected' : '' }}>Inactive</option>
                    <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Search</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('masters.contractors.workers.index', $contractor) }}" class="btn btn-sm btn-secondary w-100">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Phone</th>
                        <th>Skill Type</th>
                        <th>ID Proof</th>
                        <th>Wage Type</th>
                        <th>Wage</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workers as $w)
                        <tr>
                            <td>{{ $workers->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">{{ $w->name }}</td>
                            <td>{{ $w->gender ? ucfirst($w->gender) : '—' }}</td>
                            <td>{{ $w->phone ?? '—' }}</td>
                            <td>{{ $w->skill_type ?? '—' }}</td>
                            <td>
                                @if($w->id_proof_type)
                                    <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $w->id_proof_type)) }}</small>
                                    @if($w->id_proof_number)
                                        <br><span class="badge bg-light text-dark">{{ $w->id_proof_number }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $w->wage_type === 'daily' ? 'info' : 'primary' }}-subtle text-{{ $w->wage_type === 'daily' ? 'info' : 'primary' }}">
                                    {{ ucfirst($w->wage_type) }}
                                </span>
                            </td>
                            <td class="fw-semibold">₹{{ number_format($w->wage_amount, 0) }}</td>
                            <td>{{ $w->joining_date ? $w->joining_date->format('d-m-Y') : '—' }}</td>
                            <td>
                                @php $sc = ['active'=>'success','inactive'=>'warning','terminated'=>'danger'][$w->status] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($w->status) }}</span>
                            </td>
                            <td>
                                <a href="{{ route('masters.contractors.workers.edit', [$contractor, $w]) }}"
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('masters.contractors.workers.destroy', [$contractor, $w]) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('Remove this worker?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">No contract workers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <small class="text-muted">Total: {{ $workers->total() }} workers</small>
            {{ $workers->links() }}
        </div>
    </div>
</div>
@endsection
