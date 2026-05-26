@extends('layouts.app')
@section('title', 'Transaction Ledger - Reports')
@section('page-title', 'Transaction Ledger')

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Reports</a>
    <span style="font-size:12px; color:var(--text-secondary);">{{ $dateFrom->format('d M Y') }} — {{ $dateTo->format('d M Y') }}</span>
</div>

{{-- Summary --}}
<div class="grid-3" style="margin-bottom:20px; gap:20px;">
    <div class="card" style="border-left:4px solid var(--success);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Total Credits</div>
        <div style="font-size:22px; font-weight:700; color:var(--success);">KSH {{ number_format($summary['credit']->total ?? 0, 0) }}</div>
        <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">{{ number_format($summary['credit']->cnt ?? 0) }} transactions</div>
    </div>
    <div class="card" style="border-left:4px solid var(--danger);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Total Debits</div>
        <div style="font-size:22px; font-weight:700; color:var(--danger);">KSH {{ number_format($summary['debit']->total ?? 0, 0) }}</div>
        <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">{{ number_format($summary['debit']->cnt ?? 0) }} transactions</div>
    </div>
    <div class="card" style="border-left:4px solid var(--primary);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Net Flow</div>
        @php $net = ($summary['credit']->total ?? 0) - ($summary['debit']->total ?? 0); @endphp
        <div style="font-size:22px; font-weight:700; color:{{ $net >= 0 ? 'var(--success)' : 'var(--danger)' }};">
            {{ $net >= 0 ? '+' : '' }}KSH {{ number_format($net, 0) }}
        </div>
    </div>
</div>

{{-- By Type --}}
<div class="card" style="margin-bottom:20px;">
    <div style="font-size:13px; font-weight:600; margin-bottom:14px;">By Transaction Type</div>
    <div style="display:flex; flex-wrap:wrap; gap:10px;">
        @foreach($byType as $t)
        <div style="background:#FAFBFC; border:1px solid var(--border); border-radius:8px; padding:10px 16px; min-width:160px;">
            <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">{{ ucfirst(str_replace('_',' ',$t->transaction_type)) }}</div>
            <div style="font-size:16px; font-weight:700; color:var(--text-primary);">KSH {{ number_format($t->total, 0) }}</div>
            <div style="font-size:11px; color:var(--text-secondary);">{{ $t->cnt }} txns</div>
        </div>
        @endforeach
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('reports.financial.ledger') }}">
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
            <div>
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->toDateString()) }}" class="filter-select">
            </div>
            <div>
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to', $dateTo->toDateString()) }}" class="filter-select">
            </div>
            <div>
                <label class="form-label">Type</label>
                <select name="type" class="filter-select">
                    <option value="">All Types</option>
                    @foreach(['loan_disbursement','loan_repayment','savings_deposit','savings_withdrawal','share_capital','processing_fee','insurance_fee','penalty','refund','adjustment'] as $t)
                        <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Direction</label>
                <select name="direction" class="filter-select">
                    <option value="">Both</option>
                    <option value="credit" {{ request('direction') === 'credit' ? 'selected' : '' }}>Credit</option>
                    <option value="debit"  {{ request('direction') === 'debit'  ? 'selected' : '' }}>Debit</option>
                </select>
            </div>
            <div>
                <label class="form-label">Source</label>
                <select name="source" class="filter-select">
                    <option value="">All</option>
                    @foreach(['mpesa','bank','cash','internal','system'] as $s)
                        <option value="{{ $s }}" {{ request('source') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex; gap:8px; padding-bottom:1px;">
                <button type="submit" class="btn btn-primary" style="height:38px; padding:0 18px;"><i class="fas fa-search"></i> Filter</button>
                <a href="{{ route('reports.financial.ledger') }}" class="btn btn-outline" style="height:38px; padding:0 14px;"><i class="fas fa-undo"></i></a>
                <button type="submit" name="export" value="1" class="btn btn-outline" style="height:38px; padding:0 14px; color:var(--success); border-color:var(--success);">
                    <i class="fas fa-download"></i> CSV
                </button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header" style="margin-bottom:14px;">
        <span style="font-size:14px; font-weight:600;">Transactions — {{ $transactions->total() }} records</span>
    </div>
    <div class="table-wrap">
        <table class="data-table" style="min-width:1000px;">
            <thead>
                <tr><th>#</th><th>Txn No.</th><th>Customer</th><th>Type</th><th>Direction</th><th>Source</th><th>Ext. Ref</th><th>Amount</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                @forelse($transactions as $i => $txn)
                <tr>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ ($transactions->currentPage()-1)*$transactions->perPage()+$i+1 }}</td>
                    <td style="font-family:monospace; font-size:11px; font-weight:600;">{{ $txn->transaction_number }}</td>
                    <td>
                        <div style="font-weight:500; font-size:13px;">{{ $txn->customer?->full_name ?? '—' }}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">{{ $txn->customer?->phone_number ?? '' }}</div>
                    </td>
                    <td><span class="badge badge-primary" style="font-size:10px;">{{ ucfirst(str_replace('_',' ',$txn->transaction_type)) }}</span></td>
                    <td>
                        <span class="badge {{ $txn->direction === 'credit' ? 'badge-success' : 'badge-danger' }}">
                            {{ $txn->direction === 'credit' ? '↑ Credit' : '↓ Debit' }}
                        </span>
                    </td>
                    <td style="font-size:12px;">{{ ucfirst($txn->source ?? '—') }}</td>
                    <td style="font-family:monospace; font-size:11px;">{{ $txn->external_reference ?? '—' }}</td>
                    <td style="font-weight:700; color:{{ $txn->direction === 'credit' ? 'var(--success)' : 'var(--danger)' }};">
                        {{ $txn->direction === 'credit' ? '+' : '-' }} KSH {{ number_format($txn->amount, 0) }}
                    </td>
                    <td>
                        <span class="status {{ $txn->status === 'completed' ? 'status-active' : ($txn->status === 'reversed' ? 'status-rejected' : 'status-pending') }}">
                            {{ ucfirst($txn->status) }}
                        </span>
                    </td>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $txn->created_at->format('d M Y') }}<br><span style="font-size:11px;">{{ $txn->created_at->format('h:i A') }}</span></td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center; padding:50px; color:var(--text-secondary);">No transactions found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
    <div class="pagination-wrap">
        <span style="font-size:12px; color:var(--text-secondary);">Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} of {{ $transactions->total() }}</span>
        {{ $transactions->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
