@extends('layouts.app')

@section('title', 'Audit Log')
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Branch Administration — Administration and branch activity trail (Super Admin only)')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}" placeholder="To">
                </div>
                <div class="col-md-2">
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Users</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($branches->isNotEmpty())
                <div class="col-md-2">
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ (string) request('branch_id') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All Actions</option>
                        @foreach ($actions as $a)
                            <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $a)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-search"></i></button>
                    <a href="{{ route('branch-admin.audit-log.index') }}" class="btn btn-sm btn-secondary flex-fill"><i class="bi bi-x-circle"></i></a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>User</th>
                            <th>Branch</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Record</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->created_at?->format('d M Y, h:i:s A') }}</td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                                <td>{{ $log->branch->name ?? '—' }}</td>
                                <td>{{ str_replace('_', ' ', $log->table_name) }}</td>
                                <td>
                                    <span class="badge bg-info-subtle text-info">{{ str_replace('_', ' ', ucfirst($log->action)) }}</span>
                                </td>
                                <td>{{ $log->record_id ?: '—' }}</td>
                                <td class="text-muted small">{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No audit records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
@endsection
