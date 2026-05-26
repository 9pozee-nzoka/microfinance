@extends('portal.layouts.app')

@section('title', 'Transactions')
@section('page-title', 'Transaction History')

@section('content')

{{-- Filters --}}
<form method="GET" action="{{ route('portal.transactions') }}" style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; align-items: flex-end;">
    <div>
        <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: 4px;">Type</label>
        <select name="type" class="form-control" style="min-width: 160px;">
            <option value="">All Types</option>
            <option value="loan_repayment"  {{ request('type') === 'loan_repayment'  ? 'selected' : '' }}>Loan Repayment</option>
            <option value="savings_deposit" {{ request('type') === 'savings_deposit' ? 'selected' : '' }}>Savings Deposit</option>
            <option value="share_capital"   {{ request('type') === 'share_capital'   ? 'selected' : '' }}>Share Capital</option>
        </select>
    </div>
    <div>
        <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: 4px;">From</label>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
    </div>
    <div>
        <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: 4px;">To</label>
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
    </div>
    <div style="display: flex; gap: 8px;">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter"></i> Filter
        </button>
        <a href="{{ route('portal.transactions') }}" class="btn btn-outline">Clear</a>
    </div>
</form>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Transaction No.</th>
                <th>Type</th>
                <th>Source</th>
                <th>Reference</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $txn)
            <tr>
                <td style="font-size: 12px; white-space: nowrap;">{{ $txn->created_at->format('d M Y h:i A') }}</td>
                <td style="font-family: monospace; font-size: 11px; color: var(--text-secondary);">{{ $txn->transaction_number }}</td>
                <td>
                    <span class="badge badge-info">
                        {{ ucfirst(str_replace('_', ' ', $txn->transaction_type)) }}
                    </span>
                </td>
                <td>{{ ucfirst($txn->source ?? '—') }}</td>
                <td style="font-family: monospace; font-size: 12px;">{{ $txn->external_reference ?? '—' }}</td>
                <td style="font-weight: 700; font-size: 14px; color: {{ $txn->direction === 'credit' ? '#4CAF50' : '#F44336' }}; white-space: nowrap;">
                    {{ $txn->direction === 'credit' ? '+' : '-' }} KSH {{ number_format($txn->amount, 0) }}
                </td>
                <td>
                    <span class="badge {{ $txn->status === 'completed' ? 'badge-success' : ($txn->status === 'failed' ? 'badge-danger' : 'badge-warning') }}">
                        {{ ucfirst($txn->status) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-exchange-alt" style="font-size: 36px; opacity: 0.3; display: block; margin-bottom: 12px;"></i>
                    No transactions found
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    @if($transactions->hasPages())
    <div style="margin-top: 16px; display: flex; justify-content: center;">
        {{ $transactions->links() }}
    </div>
    @endif
</div>

@endsection
