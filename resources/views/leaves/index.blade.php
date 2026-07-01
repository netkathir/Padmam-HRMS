@extends('layouts.app')

@section('title', 'Leave Requests')
@section('page-title', 'Leave Requests')
@section('page-subtitle', 'Manage employee leave requests')

@section('page-actions')
    <a href="{{ route('leaves.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Apply Leave</a>
    <a href="{{ route('leaves.balance') }}" class="btn btn-outline-info btn-sm"><i class="bi bi-wallet2"></i> Balance</a>
    <a href="{{ route('leaves.permissions') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-clock"></i>
        Permissions</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="leave_type_id" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach ($leaveTypes as $lt)
                            <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>
                                {{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaves as $leave)
                            <tr>
                                <td>{{ $leaves->firstItem() + $loop->index }}</td>
                                <td>{{ $leave->employee->full_name ?? '—' }}</td>
                                <td>{{ $leave->leaveType->name ?? '—' }}</td>
                                <td>{{ $leave->from_date->format('d-m-Y') }}</td>
                                <td>{{ $leave->to_date->format('d-m-Y') }}</td>
                                <td>{{ $leave->total_days }}</td>
                                <td>
                                    @php $cols = ['pending'=>'warning', 'approved'=>'success', 'rejected'=>'danger', 'cancelled'=>'secondary']; @endphp
                                    <span
                                        class="badge bg-{{ $cols[$leave->status] }}-subtle text-{{ $cols[$leave->status] }}">{{ ucfirst($leave->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('leaves.show', $leave) }}" class="btn btn-sm btn-outline-info"><i
                                            class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No leave requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">{{ $leaves->links() }}</div>
        </div>
    </div>
@endsection
