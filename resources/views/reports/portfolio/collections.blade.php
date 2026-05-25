@extends('layouts.app')
@section('title', 'Loan Collections - Reports')
@section('page-title', 'Loan Collections')

@section('content')
<div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Reports</a>
    <span style="font-size:12px; color:var(--text-secondary);">{{ $dateFrom->format('d M Y') }} — {{ $dateTo->format('d M Y') }}</span>
</div>

<div class="grid-4" style="margin-bottom:20px;">
    <div class="card" style="border-left:4px solid var(--success);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Total Collected</div>
        <div style="font-size:22px; font-weight:700; color:var(--success);">KSH {{ number_format($totals->total, 0) }}</div>
        <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">{{ number_format($totals->count) }} payments</div>
    </div>
    <div class="card" style="border-left:4px solid var(--primary);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Principal Collected</div>
        <div style="font-size:22px; font-weight:700; color:var(--primary);">KSH {{ number_format($totals->principal, 0) }}</div>
    </div>
    <div class="card" style="border-left:4px solid var(--warning);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Interest Collected</div>
        <div style="font-size:22px; font-weight:700; color:var(--warning);">KSH {{ number_format($totals->interest, 0) }}</div>
    </div>
    <div class="card" style="border-left:4px solid var(--danger);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Penalties Collected</div>
        <div style="font-size:22px; font-weight:700; color:var(--danger);">KSH {{ number_format($totals->penalty, 0) }}</div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:20px; gap:20px;">
    {{-- By Method --}}
    <div class="card">
        <div style="font-size:13px; font-weight:600; margin-bottom:14px;">By Payment Method</div>
        @foreach($byMethod as $m)
        @php $pct = $totals->total > 0 ? round(($m->total / $totals->total) * 100, 1) : 0; @endphp
        <div style="margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                <span>{{ ucfirst(str_replace('_',' ',$m->payment_method ?? 'N/A')) }}</span>
                <span style="font-weight:600;">KSH {{ number_format($m->total, 0) }} <span style="color:var(--text-secondary); font-weight:400;">({{ $pct }}%)</span></span>
            </div>
            <div style="height:6px; background:#E8ECF1; border-radius:3px;">
                <div style="width:{{ $pct }}%; height:100%; background:var(--success); border-radius:3px;"></div>
            </div>
        </div>
        @endforeach
    </div>
    {{-- Daily trend --}}
    <div class="card">
        <div style="font-size:13px; font-weight:600; margin-bottom:14px;">Daily Collections</div>
        <div style="max-height:200px; overflow-y:auto;">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Payments</th><th>Amount</th></tr></thead>
                <tbody>
                    @foreach($daily as $d)
                    <tr>
                        <td style="font-size:12px;">{{ \Carbon\Carbon::parse($d->day)->format('d M Y') }}</td>
                        <td>{{ $d->cnt }}</td>
                        <td style="font-weight:600; color:var(--success);">KSH {{ number_format($d->total, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('reports.portfolio.collections') }}">
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->toDateString()) }}" class="filter-select" style="width:150px;">
            </div>
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to', $dateTo->toDateString()) }}" class="filter-select" style="width:150px;">
            </div>
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Branch</label>
                <select name="branch" class="filter-select" style="width:160px;">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Method</label>
                <select name="method" class="filter-select" style="width:150px;">
                    <option value="">All Methods</option>
                    @foreach(['mpesa' => 'M-Pesa', 'bank_transfer' => 'Bank', 'cash' => 'Cash'] as $v => $l)
                        <option value="{{ $v }}" {{ request('method') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex; gap:8px; padding-bottom:1px;">
                <button type="submit" class="btn btn-primary" style="height:38px; padding:0 18px;"><i class="fas fa-search"></i> Filter</button>
                <a href="{{ route('reports.portfolio.collections') }}" class="btn btn-outline" style="height:38px; padding:0 14px;"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header" style="margin-bottom:14px;">
        <span style="font-size:14px; font-weight:600;">Repayments — {{ $repayments->total() }} records</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table" style="min-width:1000px;">
            <thead>
                <tr><th>#</th><th>Customer</th><th>Loan No.</th><th>Amount</th><th>Principal</th><th>Interest</th><th>Penalty</th><th>Method</th><th>Reference</th><th>Received By</th><th>Date</th></tr>
            </thead>
            <tbody>
                @forelse($repayments as $i => $r)
                <tr>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ ($repayments->currentPage()-1)*$repayments->perPage()+$i+1 }}</td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $r->customer->full_name ?? '—' }}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">{{ $r->customer->phone_number ?? '' }}</div>
                    </td>
                    <td style="font-family:monospace; font-size:12px;">{{ $r->loan->loan_number ?? '—' }}</td>
                    <td style="font-weight:700; color:var(--success);">KSH {{ number_format($r->amount, 0) }}</td>
                    <td>KSH {{ number_format($r->principal_portion, 0) }}</td>
                    <td>KSH {{ number_format($r->interest_portion, 0) }}</td>
                    <td>KSH {{ number_format($r->penalty_portion, 0) }}</td>
                    <td><span class="badge badge-primary">{{ ucfirst(str_replace('_',' ',$r->payment_method)) }}</span></td>
                    <td style="font-family:monospace; font-size:11px;">{{ $r->transaction_reference ?? '—' }}</td>
                    <td style="font-size:12px;">{{ $r->receivedBy->name ?? 'System' }}</td>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $r->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="11" style="text-align:center; padding:50px; color:var(--text-secondary);">No collections in this period</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($repayments->hasPages())
    <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 4px 4px; border-top:1px solid var(--border); margin-top:8px;">
        <span style="font-size:12px; color:var(--text-secondary);">Showing {{ $repayments->firstItem() }}–{{ $repayments->lastItem() }} of {{ $repayments->total() }}</span>
        {{ $repayments->links() }}
    </div>
    @endif
</div>
@endsection
