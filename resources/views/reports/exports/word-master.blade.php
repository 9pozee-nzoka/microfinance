<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ ucwords(str_replace('_', ' ', $reportName)) }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #333; }
        h1 { font-size: 16pt; color: #26C6DA; text-transform: uppercase; }
        h2 { font-size: 12pt; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #26C6DA; color: #fff; padding: 6px; text-align: left; font-size: 9pt; }
        td { padding: 6px; border: 1px solid #ddd; font-size: 9pt; }
        tr:nth-child(even) { background: #f9f9f9; }
        .meta { margin-bottom: 12px; font-size: 9pt; color: #555; }
    </style>
</head>
<body>
    <h1>{{ config('reports.export.company_name') }}</h1>
    <h2>{{ ucwords(str_replace('_', ' ', $reportName)) }}</h2>

    <div class="meta">
        @if(isset($dateFrom) && isset($dateTo))
            <strong>Period:</strong> {{ $dateFrom->format('d/m/Y') }} - {{ $dateTo->format('d/m/Y') }}<br>
        @endif
        <strong>Generated:</strong> {{ now()->format('d/m/Y H:i:s') }}
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ is_numeric($cell) && !is_int($cell) ? number_format($cell, 2) : $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" style="text-align:center;">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
