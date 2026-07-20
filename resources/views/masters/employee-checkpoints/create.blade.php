@extends('layouts.app')
@section('title','Add Employee Checkpoint Mapping')
@section('page-title','Add Employee Checkpoint Mapping')
@section('page-subtitle','Map an employee to a checkpoint-specific ID')
@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('masters.employee-checkpoints.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" data-searchable required>
                        <option value="">Select</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }} @if($emp->employee_code) ({{ $emp->employee_code }}) @endif</option>
                        @endforeach
                    </select>
                    @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Checkpoint <span class="text-danger">*</span></label>
                    <select name="checkpoint_id" id="checkpoint_id" class="form-select @error('checkpoint_id') is-invalid @enderror" data-searchable required>
                        <option value="">Select</option>
                        @foreach ($checkpoints as $cp)
                            <option value="{{ $cp->id }}" data-code="{{ $cp->code }}" {{ old('checkpoint_id') == $cp->id ? 'selected' : '' }}>{{ $cp->name }}</option>
                        @endforeach
                    </select>
                    @error('checkpoint_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Employee Checkpoint ID <span class="text-danger">*</span></label>
                    {{--
                        Numeric-only, no letters — the checkpoint's own code
                        is shown as a fixed, non-editable prefix badge (never
                        typed, never stored as part of the value) so every
                        row stores just the bare number consistently. This is
                        what makes the (checkpoint, ID) uniqueness check
                        reliable — mixing "SPI500" and "500" for the same
                        checkpoint+number used to slip past it as two
                        different strings.
                    --}}
                    <div class="input-group">
                        <span class="input-group-text" id="checkpoint-code-prefix">—</span>
                        <input type="text" inputmode="numeric" pattern="[0-9]+" maxlength="20"
                            oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                            name="emp_checkpoint_id" class="form-control @error('emp_checkpoint_id') is-invalid @enderror"
                            value="{{ old('emp_checkpoint_id') }}" required placeholder="e.g. 001">
                    </div>
                    @error('emp_checkpoint_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="form-text">Numbers only — the checkpoint code shown on the left is attached automatically, don't type it.</div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
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
            input.addEventListener('focus', function () { renderList(input.value); });
            // preventDefault() on the option's mousedown (above) keeps the
            // input focused after a selection, so a second click never
            // fires another `focus` event and the list never reopens.
            // Reopening on plain `click` too fixes that.
            input.addEventListener('click', function () { renderList(input.value); });
            input.addEventListener('input', function () { renderList(input.value); });
            input.addEventListener('blur', function () { setTimeout(function () { list.style.display = 'none'; syncInputFromSelect(); }, 150); });
            syncInputFromSelect();
        }
        document.querySelectorAll('[data-searchable]').forEach(makeSearchable);

        // ── Employee Checkpoint ID: show the selected checkpoint's own code as a fixed prefix badge. ──
        const checkpointSelect = document.getElementById('checkpoint_id');
        const checkpointCodePrefix = document.getElementById('checkpoint-code-prefix');
        function refreshCheckpointCodePrefix() {
            if (!checkpointSelect || !checkpointCodePrefix) return;
            const opt = checkpointSelect.options[checkpointSelect.selectedIndex];
            checkpointCodePrefix.textContent = (opt && opt.value) ? (opt.dataset.code || '—') : '—';
        }
        if (checkpointSelect) {
            checkpointSelect.addEventListener('change', refreshCheckpointCodePrefix);
            refreshCheckpointCodePrefix();
        }
    })();
</script>
@endpush
