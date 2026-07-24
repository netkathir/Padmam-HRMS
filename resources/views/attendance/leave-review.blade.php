@extends('layouts.app')
@section('title', 'Paid Leave Review')
@section('page-title', 'Paid Leave Review')
@section('page-subtitle', $upload->original_filename . ' — ' . $upload->period_from->format('d M Y') . ' to ' . $upload->period_to->format('d M Y'))
@section('page-actions')
    <a href="{{ route('attendance.upload.summary', $upload) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Upload Summary</a>
@endsection
@section('content')

@if ($staffGaps->isEmpty())
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-check-circle fs-2 d-block mb-2"></i>
        No company staff have any missing-attendance dates to review for this period.
    </div>
</div>
@else
<div class="alert alert-info">
    Check a date to mark it as <strong>paid leave</strong> — it will be excluded from LOP. Leave a date unchecked to keep it flowing into LOP as before.
</div>

<form action="{{ route('attendance.upload.leave-review.post', $upload) }}" method="POST">
    @csrf
    <div class="accordion" id="leaveReviewAccordion">
        @foreach ($staffGaps as $i => $row)
        @php $employee = $row['employee']; @endphp
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#staffGap{{ $i }}">
                    {{ $employee->employee_code }} — {{ $employee->full_name }}
                    <span class="badge bg-warning-subtle text-warning ms-2">{{ count($row['gaps']) }} date(s)</span>
                </button>
            </h2>
            <div id="staffGap{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#leaveReviewAccordion">
                <div class="accordion-body">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th style="width:60px;">Paid</th><th>Date</th><th>Leave Type</th></tr></thead>
                        <tbody>
                            @foreach ($row['gaps'] as $g)
                            @php $rowKey = $employee->id . '_' . $loop->parent->index . '_' . $loop->index; @endphp
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input type="hidden" name="paid_leave[{{ $rowKey }}][checked]" value="0">
                                        <input type="checkbox" class="form-check-input" name="paid_leave[{{ $rowKey }}][checked]" value="1">
                                    </div>
                                    <input type="hidden" name="paid_leave[{{ $rowKey }}][employee_id]" value="{{ $employee->id }}">
                                    <input type="hidden" name="paid_leave[{{ $rowKey }}][date]" value="{{ $g['date'] }}">
                                    <input type="hidden" name="paid_leave[{{ $rowKey }}][leave_type_id]" value="{{ $g['leave_type_id'] }}">
                                </td>
                                <td>{{ \Carbon\Carbon::parse($g['date'])->format('d M Y, l') }}</td>
                                <td>
                                    @if ($g['leave_type_name'])
                                        <span class="badge bg-success-subtle text-success">{{ $g['leave_type_name'] }}</span>
                                    @else
                                        <span class="text-muted fst-italic">Non-Created Leave</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Paid Leave Confirmations</button>
    </div>
</form>
@endif
@endsection
