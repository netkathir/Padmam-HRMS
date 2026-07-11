@extends('layouts.app')

@section('title', 'Branch Switcher')
@section('page-title', 'Branch Switcher')
@section('page-subtitle', 'Branch Administration — move between branches without logging out (Super Admin only)')

@section('content')
    <div class="card">
        <div class="card-body">
            <p class="text-muted">
                Current branch:
                <strong>
                    @if ($currentBranchId)
                        {{ $branches->firstWhere('id', $currentBranchId)?->name ?? 'Unknown' }}
                    @else
                        All Branches
                    @endif
                </strong>
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
                        <tr class="{{ ! $currentBranchId ? 'table-primary' : '' }}" data-search="all branches">
                            <td colspan="2"><strong>All Branches</strong></td>
                            <td>—</td>
                            <td>
                                <form action="{{ route('branch-admin.branch-switcher.clear') }}" method="POST" class="branch-switch-form">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary" {{ ! $currentBranchId ? 'disabled' : '' }}>
                                        <i class="bi bi-check-circle"></i> {{ ! $currentBranchId ? 'Current' : 'Switch' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @foreach ($branches as $i => $branch)
                            <tr class="{{ (string) $currentBranchId === (string) $branch->id ? 'table-primary' : '' }}"
                                data-search="{{ strtolower($branch->name . ' ' . $branch->code) }}">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $branch->name }}</td>
                                <td><span class="badge bg-secondary">{{ $branch->code }}</span></td>
                                <td>
                                    <form action="{{ route('branch-admin.branch-switcher.switch') }}" method="POST" class="branch-switch-form">
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

    document.querySelectorAll('.branch-switch-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Switching branches will clear any unsaved changes on branch-specific screens. Continue?')) {
                e.preventDefault();
            }
        });
    });
})();
</script>
@endpush
