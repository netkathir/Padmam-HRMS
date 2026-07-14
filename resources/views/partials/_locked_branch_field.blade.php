{{--
    Read-only display of the currently active branch (from the Branch
    Switcher / the user's own branch) for Create/Edit forms that used to
    show a selectable Branch dropdown. Submits branch_id via a hidden input
    so the backend (BranchScope::stampBranchId()) still receives a value,
    though it forces this same value regardless of what's submitted.

    Expects: $currentBranch (Branch|null)
    Optional: $branchFieldLabel (string), $branchFieldHelp (string), $branchFieldCol (string)
--}}
<div class="{{ $branchFieldCol ?? 'col-md-6' }}">
    <label class="form-label">{{ $branchFieldLabel ?? 'Branch' }}</label>
    <input type="text" class="form-control" value="{{ $currentBranch->name ?? 'No active branch selected' }}" disabled>
    @if ($currentBranch)
        <input type="hidden" name="branch_id" value="{{ $currentBranch->id }}">
    @endif
    <div class="form-text">{{ $branchFieldHelp ?? 'Determined by the Branch Switcher — not selectable here.' }}</div>
</div>
