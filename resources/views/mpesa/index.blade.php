@extends('layouts.app')

@section('title', 'M-Pesa Transactions')
@section('page-title', 'M-Pesa Transactions')

@section('content')

@if(session('success'))
<div class="flash-success">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

{{-- Stats --}}
<div class="grid-4" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-title">STK Push (Repayments)</div>
        <div class="metric-value" style="color:var(--primary);">{{ number_format($totalStkPush) }}</div>
        <div class="metric-label">Total initiated</div>
    </div>
    <div class="card">
        <div class="card-title">B2C (Disbursements)</div>
        <div class="metric-value" style="color:#7B1FA2;">{{ number_format($totalB2c) }}</div>
        <div class="metric-label">Total initiated</div>
    </div>
    <div class="card">
        <div class="card-title">Total Disbursed</div>
        <div class="metric-value" style="color:var(--danger);">KSH {{ number_format($totalDisbursed, 0) }}</div>
        <div class="metric-label">Completed B2C</div>
    </div>
    <div class="card">
        <div class="card-title">Total Collected</div>
        <div class="metric-value" style="color:var(--success);">KSH {{ number_format($totalCollected, 0) }}</div>
        <div class="metric-label">Completed STK</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('mpesa.index') }}" style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; align-items:flex-end;">
    <div class="search-box" style="width:260px;">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Phone, receipt, loan no…" value="{{ request('search') }}">
    </div>
    <select name="type" class="filter-select">
        <option value="">All Types</option>
        <option value="stk_push" {{ request('type') === 'stk_push' ? 'selected' : '' }}>STK Push</option>
        <option value="b2c"      {{ request('type') === 'b2c'      ? 'selected' : '' }}>B2C Disbursement</option>
    </select>
    <select name="status" class="filter-select">
        <option value="">All Statuses</option>
        <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
        <option value="failed"    {{ request('status') === 'failed'    ? 'selected' : '' }}>Failed</option>
    </select>
    <input type="date" name="date_from" class="filter-select" value="{{ request('date_from') }}" placeholder="From">
    <input type="date" name="date_to"   class="filter-select" value="{{ request('date_to') }}"   placeholder="To">
    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    <a href="{{ route('mpesa.index') }}" class="btn btn-outline">Clear</a>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Loan</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Amount</th>
                    <th>Receipt</th>
                    <th>Status</th>
                    <th>Initiated By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                @php
                    $statusColor = match($txn->status) {
                        'completed' => ['status-active', 'Completed'],
                        'failed'    => ['status-rejected', 'Failed'],
                        'cancelled' => ['status-rejected', 'Cancelled'],
                        default     => ['status-pending', 'Pending'],
                    };
                    $typeColor = $txn->type === 'b2c' ? '#7B1FA2' : 'var(--primary)';
                    $typeLabel = $txn->type === 'b2c' ? 'B2C Disburse' : 'STK Push';
                @endphp
                <tr>
                    <td style="font-size:12px; white-space:nowrap;">{{ $txn->created_at->format('d M Y H:i') }}</td>
                    <td>
                        <span class="badge" style="background:{{ $txn->type === 'b2c' ? '#F3E5F5' : '#E3F2FD' }}; color:{{ $typeColor }};">
                            <i class="fas fa-{{ $txn->type === 'b2c' ? 'paper-plane' : 'mobile-alt' }}"></i>
                            {{ $typeLabel }}
                        </span>
                    </td>
                    <td>
                        @if($txn->loan)
                        <a href="{{ route('loans.show', $txn->loan) }}" style="font-family:monospace; font-size:12px; color:var(--primary);">
                            {{ $txn->loan->loan_number }}
                        </a>
                        @else
                        <span style="color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                    <td style="font-size:13px;">{{ $txn->customer?->full_name ?? '—' }}</td>
                    <td style="font-family:monospace; font-size:12px;">{{ $txn->phone_number }}</td>
                    <td style="font-weight:700; color:{{ $txn->type === 'b2c' ? 'var(--danger)' : 'var(--success)' }};">
                        {{ $txn->type === 'b2c' ? '-' : '+' }} KSH {{ number_format($txn->amount, 0) }}
                    </td>
                    <td style="font-family:monospace; font-size:11px; color:var(--text-secondary);">
                        {{ $txn->mpesa_receipt_number ?? '—' }}
                    </td>
                    <td>
                        <span class="status {{ $statusColor[0] }}">{{ $statusColor[1] }}</span>
                        @if($txn->result_desc && $txn->status === 'failed')
                        <div style="font-size:10px; color:var(--danger); margin-top:2px; max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $txn->result_desc }}">
                            {{ $txn->result_desc }}
                        </div>
                        @endif
                    </td>
                    <td style="font-size:12px;">{{ $txn->initiatedBy?->name ?? 'System' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">No M-Pesa transactions found</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
    <div style="margin-top:16px; display:flex; justify-content:center;">
        {{ $transactions->links() }}
    </div>
    @endif
</div>

@endsection
