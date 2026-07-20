@extends('layouts.app')
@section('title','PF & ESI Configuration')
@section('page-title','PF & ESI Configuration')
@section('page-subtitle','Statutory contribution rates and ceilings')
@section('page-actions')
    <a href="{{ route('masters.pf-esi.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Config</a>
    <a href="{{ route('masters.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Masters</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Effective From</th>
                        <th>PF Employee %</th>
                        <th>PF Employer %</th>
                        <th>PF Ceiling (₹)</th>
                        <th>ESI Employee %</th>
                        <th>ESI Employer %</th>
                        <th>ESI Ceiling (₹)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($configs as $config)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($config->effective_from)->format('d M Y') }}</td>
                        <td>{{ $config->pf_employee_percent }}%</td>
                        <td>{{ $config->pf_employer_percent }}%</td>
                        <td>₹{{ number_format($config->pf_ceiling, 0) }}</td>
                        <td>{{ $config->esi_employee_percent }}%</td>
                        <td>{{ $config->esi_employer_percent }}%</td>
                        <td>₹{{ number_format($config->esi_ceiling, 0) }}</td>
                        <td><span class="badge bg-{{ $config->is_active ? 'success' : 'danger' }}-subtle text-{{ $config->is_active ? 'success' : 'danger' }}">{{ $config->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.generic.show', ['module' => 'pf-esi', 'id' => $config->id]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('masters.pf-esi.edit', $config) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.pf-esi.destroy', $config) }}" method="POST" class="d-inline" data-confirm-delete="Delete this PF/ESI config?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No PF/ESI configurations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $configs->links() }}</div>
    </div>
</div>
@endsection
