@extends('layouts.app')
@section('title','Pending Attendance')
@section('page-title','Pending Attendance Regularisation')
@section('page-subtitle','Review and approve employee regularisation requests')
@section('content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Requested Status</th>
                        <th>In / Out</th>
                        <th>Reason</th>
                        <th>Applied On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingRequests as $req)
                    <tr>
                        <td>
                            <strong>{{ $req->employee->employee_code }}</strong><br>
                            <small class="text-muted">{{ $req->employee->full_name ?? '—' }}</small>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($req->date)->format('d M Y') }}</td>
                        <td><span class="badge bg-primary-subtle text-primary">{{ ucfirst(str_replace('_',' ',$req->status)) }}</span></td>
                        <td><small>{{ $req->in_time ?? '—' }} / {{ $req->out_time ?? '—' }}</small></td>
                        <td><small>{{ Str::limit($req->remarks ?? '', 40) }}</small></td>
                        <td><small>{{ $req->created_at->format('d M Y') }}</small></td>
                        <td>
                            <form action="{{ route('attendance.approve', $req) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-success" title="Approve"><i class="bi bi-check-lg"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-check2-circle fs-3 d-block mb-2"></i>No pending regularisation requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $pendingRequests->links() }}</div>
    </div>
</div>
@endsection
