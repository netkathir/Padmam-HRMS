@extends('layouts.app')

@section('title', 'Employee Slab')
@section('page-title', 'Employee Slab')
@section('page-subtitle', 'Employment, Bank, and Designation & Salary details')

@section('page-actions')
    <div class="dropdown">
        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-plus-lg"></i> Add
        </button>
        <div class="dropdown-menu dropdown-menu-end p-3" style="min-width:320px;">
            <form action="{{ route('employee-slab.create') }}" method="GET">
                <label class="form-label small">Select Employee (without a slab yet)</label>
                <select name="employee" class="form-select form-select-sm" data-searchable required>
                    <option value="">Select</option>
                    @forelse ($pickerEmployees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }} @if($emp->employee_code) ({{ $emp->employee_code }}) @endif</option>
                    @empty
                        <option value="" disabled>All employees already have a slab</option>
                    @endforelse
                </select>
                <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">Continue</button>
            </form>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Search by name or code..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Employees</option>
                        <option value="not_created" {{ $notCreated ? 'selected' : '' }}>Not Created Slab</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Search</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Branch</th>
                            @unless($notCreated)
                                <th>Gross Salary</th>
                            @endunless
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            <tr>
                                <td>{{ $employees->firstItem() + $loop->index }}</td>
                                <td><span class="badge bg-secondary">{{ $emp->employee_code ?? '—' }}</span></td>
                                <td class="fw-semibold">{{ $emp->full_name }}</td>
                                <td>{{ $emp->department->name ?? '—' }}</td>
                                <td>{{ $emp->designation->name ?? '—' }}</td>
                                <td>{{ $emp->branch->name ?? '—' }}</td>
                                @unless($notCreated)
                                    <td>{{ $emp->currentSalary ? '₹' . number_format($emp->currentSalary->gross_salary, 2) : '—' }}</td>
                                @endunless
                                <td>
                                    @if($notCreated)
                                        <a href="{{ route('employee-slab.edit', $emp) }}" class="btn btn-sm btn-primary" title="Add"><i class="bi bi-plus-lg"></i> Add</a>
                                    @else
                                        <a href="{{ route('employee-slab.show', $emp) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('employee-slab.edit', $emp) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        @can('employees.full')
                                        <form action="{{ route('employees.destroy', $emp) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this employee?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    {{ $notCreated ? 'Every employee already has an Employee Slab.' : 'No employees found.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end">{{ $employees->links() }}</div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('[data-searchable]').forEach(function (select) {
            if (select.dataset.searchableInit) return;
            select.dataset.searchableInit = '1';
            const wrapper = document.createElement('div');
            wrapper.className = 'position-relative';
            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(select);
            select.classList.add('d-none');
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-select form-select-sm';
            input.placeholder = 'Search…';
            input.autocomplete = 'off';
            wrapper.appendChild(input);
            const list = document.createElement('div');
            list.className = 'list-group position-absolute w-100 shadow-sm';
            list.style.cssText = 'z-index:1000;max-height:220px;overflow-y:auto;display:none;';
            wrapper.appendChild(list);
            function optionsData() {
                return Array.from(select.options).filter(o => o.value !== '').map(o => ({ value: o.value, label: o.textContent }));
            }
            function renderList(filter) {
                const q = (filter || '').toLowerCase();
                const matches = optionsData().filter(o => o.label.toLowerCase().includes(q));
                list.innerHTML = matches.length
                    ? matches.map(o => `<button type="button" class="list-group-item list-group-item-action" data-value="${o.value}">${o.label}</button>`).join('')
                    : '<div class="list-group-item text-muted">No matches</div>';
                list.style.display = 'block';
                list.querySelectorAll('[data-value]').forEach(function (btn) {
                    btn.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        select.value = btn.dataset.value;
                        input.value = btn.textContent.trim();
                        list.style.display = 'none';
                    });
                });
            }
            input.addEventListener('focus', function () { renderList(''); });
            input.addEventListener('input', function () { renderList(input.value); });
            input.addEventListener('blur', function () { setTimeout(function () { list.style.display = 'none'; }, 150); });
        });
    })();
</script>
@endpush
