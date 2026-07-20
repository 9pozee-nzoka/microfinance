<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size:11px; color:#2C3E50; }

        .header { padding:16px 20px 12px; border-bottom:3px solid #4CAF50; margin-bottom:16px; }
        .company { font-size:18px; font-weight:bold; color:#4CAF50; }
        .report-title { font-size:14px; font-weight:bold; color:#2C3E50; margin-top:4px; }
        .meta { font-size:10px; color:#7F8C8D; margin-top:3px; }

        table { width:100%; border-collapse:collapse; }
        thead tr { background:#4CAF50; color:#fff; }
        thead th { padding:8px 10px; text-align:left; font-size:10px; font-weight:600; letter-spacing:0.3px; }
        tbody tr:nth-child(even) { background:#F5F7FA; }
        tbody tr { border-bottom:1px solid #E8ECF1; }
        tbody td { padding:7px 10px; font-size:10px; vertical-align:middle; }

        .loan-no { font-family:monospace; font-weight:600; color:#2E7D32; }
        .amount { font-weight:600; color:#2C3E50; }
        .highlight { font-weight:700; color:#4CAF50; }

        .summary { margin-bottom:14px; padding:10px 14px; background:#E8F5E9;
                   border-radius:6px; display:flex; gap:30px; }
        .summary-item { font-size:11px; }
        .summary-item .val { font-size:14px; font-weight:700; color:#2E7D32; }

        .info-box { margin-bottom:12px; padding:8px 12px; background:#FFF3E0;
                    border-left:3px solid #FF9800; border-radius:4px; font-size:10px; color:#E65100; }

        .footer { margin-top:16px; padding-top:8px; border-top:1px solid #E8ECF1;
                  font-size:9px; color:#95A5A6; display:flex; justify-content:space-between; }
        .empty { text-align:center; padding:30px; color:#7F8C8D; font-size:12px; }
    </style>
</head>
<body>
<div class="header">
    <div class="company">Mweela Cash Capital Ltd</div>
    <div class="report-title">{{ $title }}</div>
    <div class="meta">Exported: {{ $exportedAt }} &nbsp;|&nbsp; Total: {{ $loans->count() }} loans &nbsp;|&nbsp; Installments: KSH {{ number_format($loans->sum('weekly_installment'), 0) }}</div>
</div>

<div class="info-box">
    <strong>Prepay Collection:</strong> These loans are due tomorrow. Customers who pay today will be marked as early payers. Contact them now to collect before the due date.
</div>

<div class="summary">
    <div class="summary-item">
        <div class="val">{{ $loans->count() }}</div>
        <div>Loans Due Tomorrow</div>
    </div>
    <div class="summary-item">
        <div class="val">KSH {{ number_format($loans->sum('weekly_installment'), 0) }}</div>
        <div>Total to Collect</div>
    </div>
    <div class="summary-item">
        <div class="val">KSH {{ number_format($loans->sum('outstanding_balance'), 0) }}</div>
        <div>Total Outstanding</div>
    </div>
</div>

@if($loans->count())
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Customer Name</th>
            <th>Phone</th>
            <th>Loan No.</th>
            <th>Tomorrow's Installment (KSH)</th>
            <th>Outstanding (KSH)</th>
            <th>Remaining Weeks</th>
            @if($canFilter)
            <th>Officer</th>
            <th>Branch</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($loans as $i => $loan)
        @php
            $weeksLeft = $loan->maturity_date
                ? (int) ceil(\Carbon\Carbon::today()->diffInWeeks($loan->maturity_date, false))
                : '—';
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td style="font-weight:600;">{{ $loan->customer?->full_name ?? '—' }}</td>
            <td>{{ $loan->customer?->phone_number ?? '—' }}</td>
            <td class="loan-no">{{ $loan->loan_number }}</td>
            <td class="highlight">{{ number_format($loan->weekly_installment, 0) }}</td>
            <td class="amount">{{ number_format($loan->outstanding_balance, 0) }}</td>
            <td style="text-align:center; color:#7F8C8D;">{{ $weeksLeft }}</td>
            @if($canFilter)
            <td style="font-size:9px;">{{ $loan->relationshipOfficer?->name ?? '—' }}</td>
            <td style="font-size:9px;">{{ $loan->branch?->name ?? '—' }}</td>
            @endif
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="empty">No loans due tomorrow.</div>
@endif

<div class="footer">
    <span>Mweela Cash Capital Ltd &nbsp;·&nbsp; Mutomo, Kitui, Kenya &nbsp;·&nbsp; mweelacredit.co.ke</span>
    <span>Generated: {{ $exportedAt }}</span>
</div>
</body>
</html>
