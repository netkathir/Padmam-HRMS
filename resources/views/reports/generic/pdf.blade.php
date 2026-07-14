<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 9px; }
        h3 { margin-bottom: 2px; }
        p.subtitle { color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 3px 5px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h3>{{ $definition->title }}</h3>
    <p class="subtitle">{{ $definition->description }} &mdash; generated {{ now()->format('d M Y H:i') }}</p>
    <table>
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th>{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
            <tr>
                @foreach($columns as $col)
                    <td>{{ \App\Support\Reports\ReportColumnRenderer::render($record, $col, $canViewSensitive) }}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
