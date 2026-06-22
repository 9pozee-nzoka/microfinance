<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ ucwords(str_replace('_', ' ', $reportName)) }}</title>
    <style>
        @page {
            margin: 30px 25px;
            size: A4 landscape;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #333;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #26C6DA;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header td { vertical-align: top; }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #26C6DA;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .company-details {
            text-align: right;
            font-size: 9px;
            color: #555;
            line-height: 1.5;
        }
        .meta {
            margin-bottom: 12px;
            font-size: 9px;
            color: #555;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.data th {
            background: #26C6DA;
            color: #fff;
            padding: 7px 6px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #26C6DA;
        }
        table.data td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        table.data tr:nth-child(even) {
            background: #f9f9f9;
        }
        .text-right { text-align: right; }
        .footer {
            position: fixed;
            bottom: 10px;
            left: 25px;
            right: 25px;
            font-size: 8px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 6px;
        }
        .footer-left { float: left; }
        .footer-right { float: right; }
        .summary-row {
            font-weight: bold;
            background: #E0F7FA !important;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="company-name">{{ config('reports.export.company_name') }}</div>
                <div class="report-title">{{ ucwords(str_replace('_', ' ', $reportName)) }}</div>
            </td>
            <td class="company-details">
                <div>Tel: {{ config('reports.export.company_phone') }}</div>
                <div>{{ config('reports.export.company_address') }}</div>
            </td>
        </tr>
    </table>

    <div class="meta">
        @if(isset($dateFrom) && isset($dateTo))
            <strong>Period:</strong> {{ $dateFrom->format('d/m/Y') }} - {{ $dateTo->format('d/m/Y') }}
        @endif
        @if(isset($asAt))
            <strong>As At:</strong> {{ $asAt->format('d/m/Y H:i') }}
        @endif
        <span style="float:right;"><strong>Generated:</strong> {{ now()->format('d/m/Y H:i:s') }}</span>
    </div>

    <table class="data">
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
                        <td>{{ is_numeric($cell) && !is_int($cell) && is_float($cell + 0) ? number_format($cell, 2) : $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" style="text-align:center; padding:20px;">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span class="footer-left">{{ config('reports.export.company_name') }} &copy; {{ now()->year }}</span>
        <span class="footer-right">Page {PAGE_NUM} of {PAGE_COUNT}</span>
    </div>
</body>
</html>
