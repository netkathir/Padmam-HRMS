@extends('layouts.app')
@section('title','OT Rates')
@section('page-title','Overtime Rates')
@section('page-subtitle','Manage overtime multiplier rates')
@section('page-actions')
    <a href="{{ route('masters.ot-rates.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add OT Rate</a>
    <a href="{{ route('masters.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Masters</a>
@endsection
@section('content')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Name</th><th>Multiplier</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($otRates as $rate)
                    <tr>
                        <td>{{ $otRates->firstItem() + $loop->index }}</td>
                        <td>{{ $rate->name }}</td>
                        <td><span class="badge bg-info-subtle text-info">{{ $rate->multiplier }}x</span></td>
                        <td>{{ $rate->description ?? '—' }}</td>
                        <td><span class="badge bg-{{ $rate->is_active ? 'success' : 'danger' }}-subtle text-{{ $rate->is_active ? 'success' : 'danger' }}">{{ $rate->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <a href="{{ route('masters.generic.show', ['module' => 'ot-rates', 'id' => $rate->id]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('masters.ot-rates.edit', $rate) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.ot-rates.destroy', $rate) }}" method="POST" class="d-inline" data-confirm-delete="Delete this OT rate?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No OT rates found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $otRates->links() }}</div>
    </div>
</div>
@endsection
