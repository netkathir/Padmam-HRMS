@extends('layouts.app')
@section('title','Edit Employee Checkpoint Mapping')
@section('page-title','Edit Employee Checkpoint Mapping')
@section('page-subtitle', $employeeCheckpoint->employee->full_name ?? '')
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.employee-checkpoints.update', $employeeCheckpoint) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Checkpoint <span class="text-danger">*</span></label>
                    <select name="checkpoint_id" class="form-select @error('checkpoint_id') is-invalid @enderror" data-searchable required>
                        <option value="">Select</option>
                        @foreach ($checkpoints as $cp)
                            <option value="{{ $cp->id }}" {{ old('checkpoint_id', $employeeCheckpoint->checkpoint_id) == $cp->id ? 'selected' : '' }}>{{ $cp->name }}</option>
                        @endforeach
                    </select>
                    @error('checkpoint_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" data-searchable required>
                        <option value="">Select</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id', $employeeCheckpoint->employee_id) == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }} @if($emp->employee_code) ({{ $emp->employee_code }}) @endif</option>
                        @endforeach
                    </select>
                    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Employee Checkpoint ID <span class="text-danger">*</span></label>
                    <input type="text" name="emp_checkpoint_id" class="form-control @error('emp_checkpoint_id') is-invalid @enderror" value="{{ old('emp_checkpoint_id', $employeeCheckpoint->emp_checkpoint_id) }}" required>
                    @error('emp_checkpoint_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
                <a href="{{ route('masters.employee-checkpoints.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        function makeSearchable(select) {
            if (!select || select.dataset.searchableInit) return;
            select.dataset.searchableInit = '1';
            const wrapper = document.createElement('div');
            wrapper.className = 'position-relative';
            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(select);
            select.classList.add('d-none');
            const input = document.createElement('input');
            input.type = 'text';
            input.className = (select.className || 'form-select').replace('d-none', '').trim();
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
            function syncInputFromSelect() {
                const opt = select.options[select.selectedIndex];
                input.value = (opt && opt.value !== '') ? opt.textContent.trim() : '';
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
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        syncInputFromSelect();
                        list.style.display = 'none';
                    });
                });
            }
            input.addEventListener('focus', function () { renderList(''); });
            input.addEventListener('input', function () { renderList(input.value); });
            input.addEventListener('blur', function () { setTimeout(function () { list.style.display = 'none'; syncInputFromSelect(); }, 150); });
            syncInputFromSelect();
        }
        document.querySelectorAll('[data-searchable]').forEach(makeSearchable);
    })();
</script>
@endpush
