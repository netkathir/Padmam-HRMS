<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        h3 { margin-bottom: 2px; }
        p.subtitle { color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px 5px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h3>Attendance Register</h3>
    <p class="subtitle">{{ \Carbon\Carbon::parse($from)->format('d M Y') }} to {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
    <table>
        <thead>
            <tr>
                <th>Date</th><th>Employee No.</th><th>Employee</th><th>Shift</th><th>In</th><th>Out</th>
                <th>Hours</th><th>Late</th><th>Early</th><th>OT</th><th>Status</th><th>Leave Type</th><th>LOP</th><th>Source</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $a)
            <tr>
                <td>{{ $a->date->format('d-m-Y') }}</td>
                <td>{{ $a->employee->employee_code ?? '' }}</td>
                <td>{{ $a->employee->full_name ?? '' }}</td>
                <td>{{ $a->shift->name ?? '' }}</td>
                <td>{{ optional($a->in_time)->format('H:i') }}</td>
                <td>{{ optional($a->out_time)->format('H:i') }}</td>
                <td>{{ $a->work_hours }}</td>
                <td>{{ $a->late_minutes }}</td>
                <td>{{ $a->early_exit_minutes }}</td>
                <td>{{ $a->ot_hours }}</td>
                <td>{{ $a->status_label }}</td>
                <td>{{ $a->leaveType->name ?? '' }}</td>
                <td>{{ $a->lop_days ?? '' }}</td>
                <td>{{ $a->source_label }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
