@extends('layouts.app')
@section('title', 'Loan Collections - Reports')
@section('page-title', 'Loan Collections')

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.categories.show', 'operational') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Operational Reports</a>
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
            <div class="table-wrap">
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
</div>

{{-- Filters --}}
@include('reports._partials.filters', [
    'action' => $reportAction ?? route('reports.portfolio.collections'),
    'showDate' => true,
    'showBranch' => true,
    'showMethod' => true,
    'branches' => $branches,
])

<div class="card">
    <div class="card-header" style="margin-bottom:14px;">
        <span style="font-size:14px; font-weight:600;">Repayments — {{ $repayments->total() }} records</span>
    </div>
    <div class="table-wrap">
        <div class="table-wrap">
        <table class="data-table" style="min-width:1000px;">
            <thead>
                <tr><th>#</th><th>Customer</th><th>Loan No.</th><th>Amount</th><th>Principal</th><th>Interest</th><th>Penalty</th><th>Method</th><th>Reference</th><th>Received By</th><th>Status</th><th>Date</th></tr>
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
                    <td style="font-size:12px;">{{ $r->receivedBy->name ?? 'Portal' }}</td>
                    <td>
                        @if($r->status === 'confirmed')
                            <span class="badge badge-success" style="font-size:10px;">Confirmed</span>
                        @elseif($r->status === 'reversed')
                            <span class="badge badge-danger" style="font-size:10px;">Reversed</span>
                        @else
                            <span class="badge badge-warning" style="font-size:10px;">Pending</span>
                        @endif
                    </td>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $r->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="12" style="text-align:center; padding:50px; color:var(--text-secondary);">No collections in this period</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($repayments->hasPages())
    <div class="pagination-wrap">
        <span style="font-size:12px; color:var(--text-secondary);">Showing {{ $repayments->firstItem() }}–{{ $repayments->lastItem() }} of {{ $repayments->total() }}</span>
        {{ $repayments->links() }}
    </div>
    @endif
</div>
@endsection
