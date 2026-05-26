{{-- resources/views/transactions/processed.blade.php --}}
@extends('layouts.app')

@section('title', 'Processed Transactions - GetCash Capital')
@section('page-title', 'Processed Transactions')

@section('content')

{{-- Summary Cards --}}
<div class="grid-4" style="margin-bottom: 20px;">
    <div class="card" style="border-left: 4px solid var(--primary);">
        <div class="metric-label">Total Transactions</div>
        <div class="metric-value" style="font-size: 22px; color: var(--primary);">{{ number_format($totalCount) }}</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--success);">
        <div class="metric-label">Total Volume</div>
        <div class="metric-value" style="font-size: 22px; color: var(--success);">KSH {{ number_format($totalVolume, 0) }}</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--warning);">
        <div class="metric-label">M-Pesa Transactions</div>
        <div class="metric-value" style="font-size: 22px; color: var(--warning);">{{ number_format($mpesaCount) }}</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--danger);">
        <div class="metric-label">Reversed / Failed</div>
        <div class="metric-value" style="font-size: 22px; color: var(--danger);">{{ number_format($reversedCount) }}</div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('transactions.processed') }}">
        <div class="filter-row">
            <div style="flex: 1 1 140px;">
                <label style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Search By</label>
                <select name="search_by" class="filter-select" style="width:100%;">
                    <option value="any"        {{ request('search_by','any') === 'any'        ? 'selected':'' }}>Any Field</option>
                    <option value="mpesa"      {{ request('search_by') === 'mpesa'      ? 'selected':'' }}>M-Pesa Code</option>
                    <option value="phone"      {{ request('search_by') === 'phone'      ? 'selected':'' }}>Phone</option>
                    <option value="id_number"  {{ request('search_by') === 'id_number'  ? 'selected':'' }}>ID Number</option>
                    <option value="txn_number" {{ request('search_by') === 'txn_number' ? 'selected':'' }}>Txn No.</option>
                </select>
            </div>
            <div style="flex: 1 1 180px;">
                <label style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Search Value</label>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Enter value…">
                </div>
            </div>
            <div style="flex: 1 1 150px;">
                <label style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Type</label>
                <select name="type" class="filter-select" style="width:100%;">
                    <option value="">All Types</option>
                    <option value="loan_repayment"    {{ request('type') === 'loan_repayment'    ? 'selected':'' }}>Loan Repayment</option>
                    <option value="savings_deposit"   {{ request('type') === 'savings_deposit'   ? 'selected':'' }}>Savings Deposit</option>
                    <option value="share_capital"     {{ request('type') === 'share_capital'     ? 'selected':'' }}>Share Capital</option>
                    <option value="loan_disbursement" {{ request('type') === 'loan_disbursement' ? 'selected':'' }}>Disbursement</option>
                    <option value="penalty"           {{ request('type') === 'penalty'           ? 'selected':'' }}>Penalty</option>
                    <option value="refund"            {{ request('type') === 'refund'            ? 'selected':'' }}>Refund</option>
                </select>
            </div>
            <div style="flex: 1 1 120px;">
                <label style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Source</label>
                <select name="source" class="filter-select" style="width:100%;">
                    <option value="">All</option>
                    <option value="mpesa"    {{ request('source') === 'mpesa'    ? 'selected':'' }}>M-Pesa</option>
                    <option value="bank"     {{ request('source') === 'bank'     ? 'selected':'' }}>Bank</option>
                    <option value="cash"     {{ request('source') === 'cash'     ? 'selected':'' }}>Cash</option>
                    <option value="internal" {{ request('source') === 'internal' ? 'selected':'' }}>Internal</option>
                </select>
            </div>
            <div style="flex: 1 1 120px;">
                <label style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Status</label>
                <select name="status" class="filter-select" style="width:100%;">
                    <option value="">All</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected':'' }}>Completed</option>
                    <option value="pending"   {{ request('status') === 'pending'   ? 'selected':'' }}>Pending</option>
                    <option value="reversed"  {{ request('status') === 'reversed'  ? 'selected':'' }}>Reversed</option>
                    <option value="failed"    {{ request('status') === 'failed'    ? 'selected':'' }}>Failed</option>
                </select>
            </div>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-select" style="flex: 1 1 130px;">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   class="filter-select" style="flex: 1 1 130px;">
            <div style="display: flex; gap: 8px; flex-shrink: 0; align-items: flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> <span class="btn-label">Filter</span></button>
                <a href="{{ route('transactions.processed') }}" class="btn btn-outline"><i class="fas fa-undo"></i></a>
                <button type="button" class="btn btn-outline" onclick="exportTransactions()" title="Export CSV">
                    <i class="fas fa-download"></i>
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Transactions Table --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">All Transactions</span>
        <span class="badge badge-primary">{{ $transactions->total() }} Records</span>
    </div>

    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Transaction No.</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Type</th>
                <th>Source</th>
                <th>Ext. Reference</th>
                <th>Bill Ref</th>
                <th>Amount (KSH)</th>
                <th>Direction</th>
                <th>Status</th>
                <th>Reconciled</th>
                <th>Date</th>
                <th>Captured By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $txn)
            @php
                $typeColors = [
                    'loan_repayment'    => ['#4CAF50', '#E8F5E9'],
                    'savings_deposit'   => ['#FF9800', '#FFF3E0'],
                    'share_capital'     => ['#9C27B0', '#F3E5F5'],
                    'loan_disbursement' => ['#F44336', '#FFEBEE'],
                    'processing_fee'    => ['#00BCD4', '#E3F2FD'],
                    'insurance_fee'     => ['#607D8B', '#ECEFF1'],
                    'penalty'           => ['#E91E63', '#FCE4EC'],
                    'refund'            => ['#795548', '#EFEBE9'],
                    'adjustment'        => ['#FF5722', '#FBE9E7'],
                ];
                $tc = $typeColors[$txn->transaction_type] ?? ['#757575', '#F5F5F5'];
            @endphp
            <tr>
                <td>{{ ($transactions->currentPage() - 1) * $transactions->perPage() + $index + 1 }}</td>
                <td style="font-family: monospace; font-size: 11px; font-weight: 600;">{{ $txn->transaction_number }}</td>
                <td style="font-weight: 500;">{{ $txn->customer?->full_name ?? '—' }}</td>
                <td style="font-size: 12px;">{{ $txn->customer?->phone_number ?? $txn->phone_number ?? '—' }}</td>
                <td>
                    <span class="badge" style="background: {{ $tc[1] }}; color: {{ $tc[0] }}; font-size: 10px;">
                        {{ ucfirst(str_replace('_', ' ', $txn->transaction_type)) }}
                    </span>
                </td>
                <td>
                    @if($txn->source === 'mpesa')
                        <span style="font-size: 11px; font-weight: 600; color: #4CAF50;"><i class="fas fa-mobile-alt"></i> M-Pesa</span>
                    @elseif($txn->source === 'bank')
                        <span style="font-size: 11px; font-weight: 600; color: var(--primary);"><i class="fas fa-university"></i> Bank</span>
                    @elseif($txn->source === 'cash')
                        <span style="font-size: 11px; font-weight: 600; color: var(--warning);"><i class="fas fa-money-bill"></i> Cash</span>
                    @else
                        <span style="font-size: 11px; color: var(--text-secondary);">{{ ucfirst($txn->source ?? '—') }}</span>
                    @endif
                </td>
                <td style="font-family: monospace; font-size: 11px;">{{ $txn->external_reference ?? '—' }}</td>
                <td style="font-size: 12px;">{{ $txn->bill_reference ?? '—' }}</td>
                <td style="font-weight: 700; {{ $txn->direction === 'credit' ? 'color: var(--success)' : 'color: var(--danger)' }};">
                    {{ number_format($txn->amount, 0) }}
                </td>
                <td>
                    @if($txn->direction === 'credit')
                        <span style="font-size: 11px; font-weight: 600; color: var(--success);"><i class="fas fa-arrow-down"></i> In</span>
                    @else
                        <span style="font-size: 11px; font-weight: 600; color: var(--danger);"><i class="fas fa-arrow-up"></i> Out</span>
                    @endif
                </td>
                <td>
                    @if($txn->status === 'completed')
                        <span class="status status-active">Completed</span>
                    @elseif($txn->status === 'pending')
                        <span class="status status-pending">Pending</span>
                    @elseif($txn->status === 'reversed')
                        <span class="status status-rejected">Reversed</span>
                    @else
                        <span class="status status-rejected">Failed</span>
                    @endif
                </td>
                <td style="text-align: center;">
                    @if($txn->is_reconciled)
                        <i class="fas fa-check-circle" style="color: var(--success);" title="Reconciled on {{ $txn->reconciled_at?->format('d M Y') }}"></i>
                    @else
                        <i class="fas fa-clock" style="color: var(--warning);" title="Pending reconciliation"></i>
                    @endif
                </td>
                <td style="font-size: 12px; color: var(--text-secondary);">
                    {{ $txn->created_at->format('d M Y') }}<br>
                    <span style="font-size: 11px;">{{ $txn->created_at->format('h:i A') }}</span>
                </td>
                <td style="font-size: 12px;">{{ $txn->createdBy?->name ?? 'System' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="14" style="text-align: center; padding: 60px; color: var(--text-secondary);">
                    <i class="fas fa-receipt" style="font-size: 48px; display: block; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p style="font-size: 15px;">No transactions found</p>
                    <p style="font-size: 12px; opacity: 0.7;">Try adjusting your search filters</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    @if($transactions->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $transactions->firstItem() ?? 0 }}–{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }}</span>
        {{ $transactions->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
function exportTransactions() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.location.href = '{{ route("transactions.processed") }}?' + params.toString();
}
</script>
@endsection
