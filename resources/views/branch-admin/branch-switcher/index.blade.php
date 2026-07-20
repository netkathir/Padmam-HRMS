@extends('layouts.app')

@section('title', 'Branch Switcher')
@section('page-title', 'Branch Switcher')
@section('page-subtitle', 'Branch Administration — move between branches without logging out (Super Admin only)')

@section('content')
    <div class="card">
        <div class="card-body">
            <p class="text-muted">
                Current branch:
                <strong>{{ $branches->firstWhere('id', $currentBranchId)?->name ?? 'Unknown' }}</strong>
            </p>

            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" id="branchSwitcherSearch" class="form-control form-control-sm"
                        placeholder="Search branches by name or code...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="branchSwitcherTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Branch</th>
                            <th>Code</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branches as $i => $branch)
                            <tr class="{{ (string) $currentBranchId === (string) $branch->id ? 'table-primary' : '' }}"
                                data-search="{{ strtolower($branch->name . ' ' . $branch->code) }}">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $branch->name }}</td>
                                <td><span class="badge bg-secondary">{{ $branch->code }}</span></td>
                                <td>
                                    <form action="{{ route('branch-admin.branch-switcher.switch') }}" method="POST" class="branch-switch-form" data-confirm-delete="Switching branches will clear any unsaved changes on branch-specific screens. Continue?">
                                        @csrf
                                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                        <button type="submit" class="btn btn-sm btn-outline-primary" {{ (string) $currentBranchId === (string) $branch->id ? 'disabled' : '' }}>
                                            <i class="bi bi-arrow-left-right"></i> {{ (string) $currentBranchId === (string) $branch->id ? 'Current' : 'Switch' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var search = document.getElementById('branchSwitcherSearch');
    var rows = document.querySelectorAll('#branchSwitcherTable tbody tr');

    search?.addEventListener('input', function () {
        var term = this.value.trim().toLowerCase();
        rows.forEach(function (row) {
            row.hidden = term !== '' && !row.dataset.search.includes(term);
        });
    });

})();
</script>
@endpush
