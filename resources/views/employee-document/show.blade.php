@extends('layouts.app')

@section('title', 'Employee Document')
@section('page-title', $employee->full_name)
@section('page-subtitle', 'Employee Document #' . ($employee->employee_code ?? '—'))
@section('back-url', route('employee-document.index'))

@section('page-actions')
    <a href="{{ route('employees.documents', $employee) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add / Manage</a>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    @if ($employee->profile_photo)
                        <img src="{{ $employee->profile_photo_url }}" alt="" class="rounded-circle mb-3" style="width:80px;height:80px;object-fit:cover;">
                    @else
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3"
                            style="width:80px;height:80px;">
                            <span class="text-white fw-bold fs-3">{{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name ?? '', 0, 1)) }}</span>
                        </div>
                    @endif
                    <h5 class="mb-1">{{ $employee->display_name_or_default }}</h5>
                    <p class="text-muted mb-1">{{ $employee->designation->name ?? '—' }}</p>
                    <span class="badge bg-{{ $employee->status == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($employee->status) }}</span>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">Quick Links</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('employees.show', $employee) }}" class="list-group-item list-group-item-action"><i class="bi bi-person-vcard me-2"></i>Create Employee</a>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Uploaded Documents</div>
                <div class="card-body">
                    @if ($documents->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Type</th><th>Number</th><th>Issued</th><th>Expiry</th><th>Remarks</th><th>Uploaded</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($documents as $doc)
                                        <tr>
                                            <td>{{ ucfirst(str_replace('_', ' ', $doc->document_type)) }}</td>
                                            <td>{{ $doc->document_number ?? '—' }}</td>
                                            <td>{{ $doc->issue_date?->format('d M Y') ?? '—' }}</td>
                                            <td>
                                                @if($doc->expiry_date)
                                                    @php $exp = \Carbon\Carbon::parse($doc->expiry_date); @endphp
                                                    <span class="{{ $exp->isPast() ? 'text-danger' : ($exp->between(now(), now()->addDays(30)) ? 'text-warning' : '') }}">{{ $exp->format('d M Y') }}</span>
                                                @else — @endif
                                            </td>
                                            <td>{{ $doc->remarks ?? '—' }}</td>
                                            <td>{{ $doc->created_at->format('d M Y') }}</td>
                                            <td><a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Open"><i class="bi bi-eye"></i></a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No documents uploaded yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
