@extends('layouts.app')
@section('title', 'Contractors')
@section('page-title', 'Contractors')
@section('page-subtitle', 'Manage labour contractors')
@section('page-actions')
    <a href="{{ route('masters.contractors.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add
        Contractor</a>
    <a href="{{ route('masters.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i>
        Masters</a>
@endsection
@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($expiringSoonCount > 0)
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> {{ $expiringSoonCount }} contractor(s) have a licence or agreement expiring within 30 days.</div>
    @endif
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Search name, code, contact…" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Search</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('masters.contractors.index') }}" class="btn btn-sm btn-secondary w-100"><i
                            class="bi bi-x-circle"></i> Reset</a>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>License Expiry</th>
                            <th>Agreement End</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contractors as $c)
                            <tr>
                                <td>{{ $contractors->firstItem() + $loop->index }}</td>
                                <td><span class="badge bg-secondary">{{ $c->code }}</span></td>
                                <td>{{ $c->name }}</td>
                                <td>{{ $c->contact_person ?? '—' }}</td>
                                <td>{{ $c->phone ?? '—' }}</td>
                                <td>{{ $c->email ?? '—' }}</td>
                                <td>
                                    @if($c->license_expiry)
                                        <span class="{{ $c->isLicenseExpired() ? 'text-danger' : ($c->isLicenseExpiringSoon() ? 'text-warning fw-semibold' : '') }}">{{ $c->license_expiry->format('d-m-Y') }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($c->agreement_end_date)
                                        <span class="{{ $c->isAgreementExpired() ? 'text-danger' : ($c->isAgreementExpiringSoon() ? 'text-warning fw-semibold' : '') }}">{{ $c->agreement_end_date->format('d-m-Y') }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><span
                                        class="badge bg-{{ $c->is_active ? 'success' : 'danger' }}-subtle text-{{ $c->is_active ? 'success' : 'danger' }}">{{ $c->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('masters.contractors.edit', $c) }}"
                                        class="btn btn-sm btn-outline-primary" title="Edit"><i
                                            class="bi bi-pencil"></i></a>
                                    <form action="{{ route('masters.contractors.destroy', $c) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Delete this contractor?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Delete"><i
                                                class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No contractors found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">{{ $contractors->links() }}</div>
        </div>
    </div>
@endsection
