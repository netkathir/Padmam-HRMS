@extends('layouts.app')
@section('title', 'Biometric Upload')
@section('page-title', 'Biometric Upload')
@section('page-subtitle', 'Upload punch data exported from the biometric device')
@section('page-actions')
    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Register</a>
@endsection
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('attendance.upload.post') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Upload File (XLS/XLSX/CSV) <span class="text-danger">*</span></label>
                        <input type="file" name="file" accept=".xls,.xlsx,.csv" class="form-control @error('file') is-invalid @enderror" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Expected columns: Person ID, Name, Department, Time, Attendance Status, Attendance Check Point, Custom Name, Data Source, Handling Type, Temperature, Abnormal.</div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload</button>
                </form>
            </div>
        </div>
    </div>
</div>

@if ($upload ?? null)
<div class="modal fade" id="uploadPreviewModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Upload — {{ $upload->original_filename }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if (count($sheetNames) > 1)
                <form method="GET" action="{{ route('attendance.upload.mapping', $upload) }}" class="mb-3 row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Sheet Name <span class="text-danger">*</span></label>
                        <select name="sheet_name" class="form-select" onchange="this.form.submit()">
                            @foreach ($sheetNames as $name)
                                <option value="{{ $name }}" {{ $selectedSheet === $name ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
                @endif

                <p class="text-muted small mb-3">
                    Columns are read in the device's standard export format — Person ID, Name, Department, Time,
                    Attendance Status, Attendance Check Point, Custom Name, Data Source, Handling Type, Temperature, Abnormal.
                    Review the parsed rows below, then confirm to actually save them.
                </p>

                @if ($preview)
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-2">
                            <div class="card text-center"><div class="card-body py-2">
                                <div class="fs-5 fw-bold">{{ $preview['counts']['total_rows'] }}</div>
                                <div class="text-muted small">Total Rows</div>
                            </div></div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card text-center"><div class="card-body py-2">
                                <div class="fs-5 fw-bold text-success">{{ $preview['counts']['valid_rows'] }}</div>
                                <div class="text-muted small">New — Will Save</div>
                            </div></div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card text-center"><div class="card-body py-2">
                                <div class="fs-5 fw-bold text-info">{{ $preview['counts']['updated_rows'] }}</div>
                                <div class="text-muted small">Already Exist — Will Update</div>
                            </div></div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card text-center"><div class="card-body py-2">
                                <div class="fs-5 fw-bold text-danger">{{ $preview['counts']['invalid_rows'] }}</div>
                                <div class="text-muted small">Invalid</div>
                            </div></div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card text-center"><div class="card-body py-2">
                                <div class="fs-5 fw-bold text-warning">{{ $preview['counts']['duplicate_rows'] }}</div>
                                <div class="text-muted small">Repeated Punch (within 3 min)</div>
                            </div></div>
                        </div>
                    </div>

                    @if ($preview['truncated'])
                        <div class="alert alert-info small">Showing the first rows of each type below — the full file will still be processed in full when you confirm.</div>
                    @endif

                    @if (count($preview['valid']))
                        <h6 class="fw-semibold mb-2">Rows That Will Be Saved</h6>
                        <div class="table-responsive mb-4" style="max-height:300px;">
                            <table class="table table-sm table-hover">
                                <thead><tr><th>Row</th><th>Person ID</th><th>Name</th><th>Department</th><th>Employee</th><th>Punch Time</th><th>Action</th></tr></thead>
                                <tbody>
                                    @foreach ($preview['valid'] as $p)
                                    <tr>
                                        <td>{{ $p['row'] }}</td>
                                        <td>{{ $p['person_id'] ?? '—' }}</td>
                                        <td>{{ $p['name'] ?? '—' }}</td>
                                        <td>{{ $p['department'] ?? '—' }}</td>
                                        <td>{{ $p['employee']->full_name ?? '—' }} @if($p['employee']->employee_code) ({{ $p['employee']->employee_code }}) @endif</td>
                                        <td>{{ $p['punch_time']->format('d-M-Y H:i') }}</td>
                                        <td>
                                            @if($p['will_update'] ?? false)
                                                <span class="badge bg-info-subtle text-info">Update</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success">New</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (count($preview['errors']))
                        <h6 class="fw-semibold mb-2">Rows With Errors <small class="text-muted fw-normal">(will not be saved)</small></h6>
                        <div class="table-responsive mb-3" style="max-height:300px;">
                            <table class="table table-sm table-hover">
                                <thead><tr><th>Row</th><th>Person ID</th><th>Attendance Check Point</th><th>Time</th><th>Error</th></tr></thead>
                                <tbody>
                                    @foreach ($preview['errors'] as $e)
                                    <tr>
                                        <td>{{ $e['row'] }}</td>
                                        <td>{{ $e['person_id'] ?? '—' }}</td>
                                        <td>{{ $e['checkpoint'] ?? '—' }}</td>
                                        <td>{{ $e['time'] ?? '—' }}</td>
                                        <td class="text-danger">{{ $e['errors'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (! count($preview['valid']) && ! count($preview['errors']))
                        <div class="alert alert-warning">No data rows were found in this sheet.</div>
                    @endif
                @endif
            </div>
            <div class="modal-footer">
                <form action="{{ route('attendance.upload.confirm', $upload) }}" method="POST">
                    @csrf
                    <input type="hidden" name="sheet_name" value="{{ $selectedSheet }}">
                    <button type="submit" class="btn btn-primary" {{ $selectedSheet ? '' : 'disabled' }}>
                        <i class="bi bi-check-lg"></i> Confirm &amp; Save
                    </button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    new bootstrap.Modal(document.getElementById('uploadPreviewModal')).show();
</script>
@endpush
@endif
@endsection
