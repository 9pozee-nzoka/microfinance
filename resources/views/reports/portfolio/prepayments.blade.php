@extends('layouts.app')
@section('title', 'Prepayment Analytics - Reports')
@section('page-title', 'Prepayment Analytics')

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Reports</a>
    <form method="GET" action="{{ route('reports.portfolio.prepayments') }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <div>
            <label class="form-label">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->toDateString()) }}" class="filter-select">
        </div>
        <div>
            <label class="form-label">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to', $dateTo->toDateString()) }}" class="filter-select">
        </div>
        <div>
            <label class="form-label">Branch</label>
            <select name="branch" class="filter-select">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="padding-top:18px;">
            <button type="submit" class="btn btn-primary" style="height:38px; padding:0 16px;"><i class="fas fa-search"></i> Filter</button>
        </div>
    </form>
</div>

<div style="font-size:13px; color:var(--text-secondary); margin-bottom:20px;">
    Period: <strong>{{ $dateFrom->format('d M Y') }}</strong> — <strong>{{ $dateTo->format('d M Y') }}</strong>
</div>

{{-- Combined Summary Cards --}}
<div class="grid-4" style="margin-bottom:20px;">
    <div class="card" style="border-left:4px solid var(--success);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Early Installment Payments</div>
        <div style="font-size:22px; font-weight:700; color:var(--success);">{{ number_format($earlyPaymentsSummary->count ?? 0) }}</div>
        <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">KSH {{ number_format($earlyPaymentsSummary->total_amount ?? 0, 0) }}</div>
    </div>
    <div class="card" style="border-left:4px solid var(--primary);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Early Loan Closures</div>
        <div style="font-size:22px; font-weight:700; color:var(--primary);">{{ number_format($closureSummary['total_count']) }}</div>
        <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">
            KSH {{ number_format($closureSummary['prepayment_amount'] + $closureSummary['topup_amount'] + $closureSummary['settlement_amount'] + $closureSummary['other_amount'], 0) }}
        </div>
    </div>
    <div class="card" style="border-left:4px solid var(--warning);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Combined Prepayment Amount</div>
        <div style="font-size:22px; font-weight:700; color:var(--warning);">
            KSH {{ number_format(($earlyPaymentsSummary->total_amount ?? 0) + $closureSummary['prepayment_amount'] + $closureSummary['topup_amount'] + $closureSummary['settlement_amount'] + $closureSummary['other_amount'], 0) }}
        </div>
    </div>
    <div class="card" style="border-left:4px solid #00BCD4;">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Avg Days Early</div>
        <div style="font-size:22px; font-weight:700; color:#00BCD4;">{{ round($earlyPaymentsSummary->avg_days_early ?? 0, 1) }}</div>
        <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">installment payments</div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:20px; gap:20px;">
    {{-- Early Payments by Method --}}
    <div class="card">
        <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid var(--border);">
            <i class="fas fa-credit-card" style="color:var(--success);"></i> Early Payments by Method
        </div>
        @php $epTotal = $earlyPaymentsSummary->total_amount ?? 0; @endphp
        @forelse($earlyPaymentsByMethod as $m)
        @php $pct = $epTotal > 0 ? round(($m->total / $epTotal) * 100, 1) : 0; @endphp
        <div style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                <span>{{ ucfirst(str_replace('_',' ',$m->payment_method ?? 'N/A')) }}</span>
                <span style="font-weight:600;">KSH {{ number_format($m->total, 0) }} <span style="color:var(--text-secondary); font-weight:400;">({{ $pct }}%)</span></span>
            </div>
            <div style="height:6px; background:#E8ECF1; border-radius:3px;">
                <div style="width:{{ $pct }}%; height:100%; background:var(--success); border-radius:3px;"></div>
            </div>
        </div>
        @empty
        <div style="text-align:center; padding:20px; color:var(--text-secondary);">No early payments</div>
        @endforelse
    </div>

    {{-- Early Closures Breakdown --}}
    <div class="card">
        <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid var(--border);">
            <i class="fas fa-lock" style="color:var(--primary);"></i> Early Closures by Type
        </div>
        @php
            $closureBreakdown = [
                ['label' => 'Prepayment', 'count' => $closureSummary['prepayment_count'], 'amount' => $closureSummary['prepayment_amount'], 'color' => 'var(--success)', 'icon' => 'fa-money-bill-wave'],
                ['label' => 'Top-Up', 'count' => $closureSummary['topup_count'], 'amount' => $closureSummary['topup_amount'], 'color' => 'var(--warning)', 'icon' => 'fa-arrow-up'],
                ['label' => 'Full Early Settlement', 'count' => $closureSummary['settlement_count'], 'amount' => $closureSummary['settlement_amount'], 'color' => '#9C27B0', 'icon' => 'fa-check-circle'],
                ['label' => 'Other', 'count' => $closureSummary['other_count'], 'amount' => $closureSummary['other_amount'], 'color' => 'var(--text-secondary)', 'icon' => 'fa-ellipsis-h'],
            ];
            $closureTotal = $closureSummary['prepayment_amount'] + $closureSummary['topup_amount'] + $closureSummary['settlement_amount'] + $closureSummary['other_amount'];
        @endphp
        @foreach($closureBreakdown as $item)
        @php $pct = $closureSummary['total_count'] > 0 ? round(($item['count'] / $closureSummary['total_count']) * 100, 1) : 0; @endphp
        <div style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                <div style="display:flex; align-items:center; gap:8px; font-size:13px;">
                    <i class="fas {{ $item['icon'] }}" style="color:{{ $item['color'] }}; width:16px;"></i>
                    {{ $item['label'] }}
                </div>
                <div style="text-align:right;">
                    <span style="font-weight:700; color:{{ $item['color'] }};">{{ number_format($item['count']) }}</span>
                    <span style="font-size:11px; color:var(--text-secondary); margin-left:6px;">{{ $pct }}%</span>
                </div>
            </div>
            <div style="height:6px; background:#E8ECF1; border-radius:3px;">
                <div style="width:{{ $pct }}%; height:100%; background:{{ $item['color'] }}; border-radius:3px;"></div>
            </div>
            <div style="font-size:11px; color:var(--text-secondary); margin-top:2px; text-align:right;">
                KSH {{ number_format($item['amount'], 0) }}
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Monthly Trend --}}
<div class="card" style="margin-bottom:20px;">
    <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:16px;">
        <i class="fas fa-chart-bar" style="color:var(--primary);"></i> Monthly Trend — Combined Prepayments
    </div>
    <div class="table-wrap">
        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Early Payments</th>
                    <th>Early Pay Amount</th>
                    <th>Early Closures</th>
                    <th>Closure Amount</th>
                    <th>Total Count</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthlyTrend as $t)
                <tr>
                    <td style="font-weight:600; font-size:13px;">{{ $t['month'] }}</td>
                    <td style="color:var(--success);">{{ number_format($t['early_payment_count']) }}</td>
                    <td style="color:var(--success); font-weight:600;">KSH {{ number_format($t['early_payment_amount'], 0) }}</td>
                    <td style="color:var(--primary);">{{ number_format($t['closure_count']) }}</td>
                    <td style="color:var(--primary); font-weight:600;">KSH {{ number_format($t['closure_amount'], 0) }}</td>
                    <td style="font-weight:700;">{{ number_format($t['total_count']) }}</td>
                    <td style="font-weight:700;">KSH {{ number_format($t['total_amount'], 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>

{{-- Section A: Early Installment Payments --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-header" style="margin-bottom:14px; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-size:14px; font-weight:600;"><i class="fas fa-calendar-check" style="color:var(--success);"></i> Early Installment Payments — {{ $earlyPayments->total() }} records</span>
        <span style="font-size:11px; color:var(--text-secondary);">Paid before due date</span>
    </div>
    <div class="table-wrap">
        <div class="table-wrap">
        <table class="data-table" style="min-width:1100px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Loan No.</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Paid Date</th>
                    <th>Days Early</th>
                    <th>Method</th>
                    <th>Received By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($earlyPayments as $i => $r)
                <tr>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ ($earlyPayments->currentPage()-1)*$earlyPayments->perPage()+$i+1 }}</td>
                    <td style="font-family:monospace; font-size:12px; font-weight:600;">{{ $r->loan->loan_number ?? '—' }}</td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $r->customer->full_name ?? '—' }}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">{{ $r->customer->phone_number ?? '' }}</div>
                    </td>
                    <td style="font-weight:700; color:var(--success);">KSH {{ number_format($r->amount, 0) }}</td>
                    <td style="font-size:12px;">{{ $r->schedule?->due_date?->format('d M Y') ?? '—' }}</td>
                    <td style="font-size:12px;">{{ $r->created_at->format('d M Y') }}</td>
                    <td style="color:#00BCD4; font-weight:700;">{{ number_format($r->days_early) }} days</td>
                    <td><span class="badge badge-primary">{{ ucfirst(str_replace('_',' ',$r->payment_method)) }}</span></td>
                    <td style="font-size:12px;">{{ $r->receivedBy->name ?? 'System' }}</td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center; padding:50px; color:var(--text-secondary);">No early installment payments in this period</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($earlyPayments->hasPages())
    <div class="pagination-wrap">
        <span style="font-size:12px; color:var(--text-secondary);">Showing {{ $earlyPayments->firstItem() }}–{{ $earlyPayments->lastItem() }} of {{ $earlyPayments->total() }}</span>
        {{ $earlyPayments->appends(['closures_page' => request('closures_page')])->links() }}
    </div>
    @endif
</div>

{{-- Section B: Early Loan Closures --}}
<div class="card">
    <div class="card-header" style="margin-bottom:14px; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-size:14px; font-weight:600;"><i class="fas fa-lock" style="color:var(--primary);"></i> Early Loan Closures — {{ $closures->total() }} records</span>
        <span style="font-size:11px; color:var(--text-secondary);">Loans closed before maturity</span>
    </div>
    <div class="table-wrap">
        <div class="table-wrap">
        <table class="data-table" style="min-width:1000px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Loan No.</th>
                    <th>Closure Type</th>
                    <th>Principal</th>
                    <th>Total Paid</th>
                    <th>Closure Payment</th>
                    <th>Method</th>
                    <th>Officer</th>
                    <th>Closed Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($closures as $i => $loan)
                <tr>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ ($closures->currentPage()-1)*$closures->perPage()+$i+1 }}</td>
                    <td style="font-family:monospace; font-size:12px; font-weight:600;">{{ $loan->loan_number }}</td>
                    <td>
                        @php
                            $badgeColors = [
                                'prepayment' => ['bg' => '#E8F5E9', 'color' => 'var(--success)', 'label' => 'Prepayment'],
                                'topup' => ['bg' => '#FFF8E1', 'color' => 'var(--warning)', 'label' => 'Top-Up'],
                                'full_early_settlement' => ['bg' => '#F3E5F5', 'color' => '#9C27B0', 'label' => 'Early Settlement'],
                                'other' => ['bg' => '#ECEFF1', 'color' => 'var(--text-secondary)', 'label' => 'Other'],
                            ];
                            $badge = $badgeColors[$loan->closure_type] ?? $badgeColors['other'];
                        @endphp
                        <span style="display:inline-block; padding:3px 10px; border-radius:10px; font-size:11px; font-weight:600; background:{{ $badge['bg'] }}; color:{{ $badge['color'] }};">
                            {{ $badge['label'] }}
                        </span>
                    </td>
                    <td>KSH {{ number_format($loan->principal_amount, 0) }}</td>
                    <td>KSH {{ number_format($loan->total_paid, 0) }}</td>
                    <td style="font-weight:700; color:var(--primary);">KSH {{ number_format($loan->closure_payment_amount ?? 0, 0) }}</td>
                    <td style="font-size:12px;">{{ $loan->closure_payment_method ? ucfirst(str_replace('_', ' ', $loan->closure_payment_method)) : '—' }}</td>
                    <td style="font-size:12px;">{{ $loan->officer_name ?? '—' }}</td>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $loan->updated_at ? $loan->updated_at->format('d M Y') : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center; padding:50px; color:var(--text-secondary);">No early loan closures in this period</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($closures->hasPages())
    <div class="pagination-wrap">
        <span style="font-size:12px; color:var(--text-secondary);">Showing {{ $closures->firstItem() }}–{{ $closures->lastItem() }} of {{ $closures->total() }}</span>
        {{ $closures->appends(['payments_page' => request('payments_page')])->links() }}
    </div>
    @endif
</div>
@endsection
