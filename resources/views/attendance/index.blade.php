@extends('layouts.app')

@section('title', 'Attendance Register')
@section('page-title', 'Attendance Register')
@section('page-subtitle', 'Biometric, manual and corrected attendance records')

@section('page-actions')
    <a href="{{ route('attendance.upload.form') }}" class="btn btn-primary btn-sm"><i class="bi bi-upload"></i> Biometric Upload</a>
    <a href="{{ route('attendance.correction.form') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil-square"></i> Correction</a>
    <a href="{{ route('attendance.mark') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus-lg"></i> Mark Attendance</a>
    <a href="{{ route('attendance.manual') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i> Manual Entry</a>
    <a href="{{ route('attendance.report') }}" class="btn btn-outline-info btn-sm"><i class="bi bi-file-text"></i> Report</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <label class="form-label small mb-1">From <span class="text-danger">*</span></label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $from }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">To <span class="text-danger">*</span></label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $to }}" required>
                </div>
                @if ($currentBranchId === null)
                <div class="col-md-2">
                    <label class="form-label small mb-1">Branch</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label small mb-1">Employee Number</label>
                    <input type="text" name="employee_number" class="form-control form-control-sm" value="{{ request('employee_number') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Employee Name</label>
                    <input type="text" name="employee_name" class="form-control form-control-sm" value="{{ request('employee_name') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Employee Type</label>
                    <select name="employee_type_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($employeeTypes as $et)
                            <option value="{{ $et->id }}" {{ request('employee_type_id') == $et->id ? 'selected' : '' }}>{{ $et->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Labour Type</label>
                    <select name="labour_type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="company_labour" {{ request('labour_type') == 'company_labour' ? 'selected' : '' }}>Company Labour</option>
                        <option value="contract_labour" {{ request('labour_type') == 'contract_labour' ? 'selected' : '' }}>Contract Labour</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Contractor</label>
                    <select name="contractor_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($contractors as $c)
                            <option value="{{ $c->id }}" {{ request('contractor_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Shift</label>
                    <select name="shift_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($shifts as $s)
                            <option value="{{ $s->id }}" {{ request('shift_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach (['present','absent','half_day','paid_leave','unpaid_leave','weekly_off','paid_holiday','unpaid_holiday','on_duty','missing_punch','pending_review','on_leave','holiday','weekend'] as $st)
                            <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$st)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                </div>
            </form>

            <div class="row g-2 mb-3">
                @foreach (['present', 'absent', 'half_day', 'missing_punch', 'weekly_off', 'paid_holiday'] as $st)
                    @php $cnt = $summary[$st] ?? 0; @endphp
                    <div class="col">
                        <div class="text-center p-2 rounded bg-{{ $st == 'present' ? 'success' : ($st == 'absent' ? 'danger' : ($st == 'half_day' ? 'warning' : ($st == 'missing_punch' ? 'danger' : 'secondary'))) }}-subtle">
                            <div class="fw-bold">{{ $cnt }}</div>
                            <small>{{ ucwords(str_replace('_', ' ', $st)) }}</small>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-end gap-2 mb-2">
                <a href="{{ route('attendance.export', request()->query()) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel"></i> Export Excel</a>
                <a href="{{ route('attendance.export.pdf', request()->query()) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
            </div>

            <form action="{{ route('attendance.recalculate-selected') }}" method="POST" id="recalcForm" data-confirm-delete="Recalculate the selected attendance records?">
                @csrf
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th><input type="checkbox" onclick="document.querySelectorAll('.row-check').forEach(c=>c.checked=this.checked)"></th>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Shift</th>
                                <th>In Time</th>
                                <th>Out Time</th>
                                <th>Total Hrs</th>
                                <th>Late Min</th>
                                <th>Early Min</th>
                                <th>OT Hrs</th>
                                <th>Status</th>
                                <th>Leave Type</th>
                                <th>LOP Days</th>
                                <th>Source</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendance as $att)
                                <tr>
                                    <td><input type="checkbox" class="row-check" name="attendance_ids[]" value="{{ $att->id }}"></td>
                                    <td>{{ $att->date->format('d-m-Y') }}</td>
                                    <td>{{ $att->employee->employee_code ?? '' }} — {{ $att->employee->full_name ?? '—' }}</td>
                                    <td>{{ $att->shift->name ?? '—' }}</td>
                                    <td>{{ optional($att->in_time)->format('H:i') ?? '—' }}</td>
                                    <td>{{ optional($att->out_time)->format('H:i') ?? '—' }}</td>
                                    <td>{{ $att->work_hours }}</td>
                                    <td>{{ $att->late_minutes }}</td>
                                    <td>{{ $att->early_exit_minutes }}</td>
                                    <td>
                                        {{ $att->ot_hours }}
                                        @if ($att->ot_minutes > 0)
                                            @php $otColor = ['approved'=>'success','rejected'=>'danger'][$att->ot_approval_status] ?? 'warning'; @endphp
                                            <span class="badge bg-{{ $otColor }}-subtle text-{{ $otColor }}">{{ ucfirst($att->ot_approval_status ?? 'pending') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php $colors = ['present'=>'success','absent'=>'danger','half_day'=>'warning','missing_punch'=>'danger','pending_review'=>'warning','on_duty'=>'info','paid_leave'=>'secondary','unpaid_leave'=>'secondary','weekly_off'=>'dark','paid_holiday'=>'primary','unpaid_holiday'=>'primary']; @endphp
                                        <span class="badge bg-{{ $colors[$att->status] ?? 'secondary' }}-subtle text-{{ $colors[$att->status] ?? 'secondary' }}">{{ $att->status_label }}</span>
                                    </td>
                                    <td>{{ $att->leaveType->name ?? '—' }}</td>
                                    <td>{{ $att->lop_days ?? '—' }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $att->source_label }}</span></td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('attendance.show', $att) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('attendance.correction.form', ['employee_id' => $att->employee_id, 'date' => $att->date->toDateString()]) }}" class="btn btn-sm btn-outline-primary" title="Correct"><i class="bi bi-pencil-square"></i></a>
                                        @if ($att->ot_minutes > 0 && $att->ot_approval_status === 'pending')
                                        <form action="{{ route('attendance.ot.approve', $att) }}" method="POST" class="d-inline">
                                            @csrf<input type="hidden" name="action" value="approve">
                                            <button class="btn btn-sm btn-outline-success" title="Approve Overtime"><i class="bi bi-check-lg"></i></button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="text-center text-muted py-4">No attendance records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat"></i> Recalculate Selected</button>
            </form>
            <div class="d-flex justify-content-end mt-2">{{ $attendance->links() }}</div>
        </div>
    </div>
@endsection
