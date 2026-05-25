@extends('layouts.app')
@section('title', 'Daily Activity - Reports')
@section('page-title', 'Daily Activity Summary')

@section('content')
<div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Reports</a>
    <form method="GET" action="{{ route('reports.operational.daily') }}" style="display:flex; gap:10px; align-items:center;">
        <label style="font-size:13px; color:var(--text-secondary);">Date:</label>
        <input type="date" name="date" value="{{ $date->toDateString() }}" class="filter-select" style="width:160px;">
        <button type="submit" class="btn btn-primary" style="height:38px; padding:0 16px;"><i class="fas fa-search"></i> Go</button>
    </form>
</div>

<div style="font-size:18px; font-weight:700; color:var(--text-primary); margin-bottom:20px;">
    <i class="fas fa-calendar-day" style="color:var(--primary);"></i>
    {{ $date->format('l, d F Y') }}
    @if($date->isToday()) <span class="badge badge-success" style="font-size:12px; margin-left:8px;">Today</span> @endif
</div>

{{-- Activity Grid --}}
<div class="grid-4" style="margin-bottom:20px;">
    <div class="card" style="border-left:4px solid var(--primary); text-align:center; padding:20px;">
        <div style="font-size:32px; font-weight:800; color:var(--primary);">{{ $newCustomers }}</div>
        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;"><i class="fas fa-user-plus"></i> New Customers</div>
    </div>
    <div class="card" style="border-left:4px solid var(--success); text-align:center; padding:20px;">
        <div style="font-size:32px; font-weight:800; color:var(--success);">{{ $activatedToday }}</div>
        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;"><i class="fas fa-user-check"></i> Activated</div>
    </div>
    <div class="card" style="border-left:4px solid var(--warning); text-align:center; padding:20px;">
        <div style="font-size:32px; font-weight:800; color:var(--warning);">{{ $loansApplied }}</div>
        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;"><i class="fas fa-file-alt"></i> Loans Applied</div>
    </div>
    <div class="card" style="border-left:4px solid #9C27B0; text-align:center; padding:20px;">
        <div style="font-size:32px; font-weight:800; color:#9C27B0;">{{ $loansApproved }}</div>
        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;"><i class="fas fa-check-circle"></i> Loans Approved</div>
    </div>
</div>

<div class="grid-3" style="margin-bottom:20px; gap:20px;">
    <div class="card" style="border-left:4px solid var(--success); text-align:center; padding:20px;">
        <div style="font-size:26px; font-weight:700; color:var(--success);">{{ $loansDisbursed }}</div>
        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;"><i class="fas fa-paper-plane"></i> Loans Disbursed</div>
        <div style="font-size:14px; font-weight:600; color:var(--success); margin-top:6px;">KSH {{ number_format($disbursedAmount, 0) }}</div>
    </div>
    <div class="card" style="border-left:4px solid var(--primary); text-align:center; padding:20px;">
        <div style="font-size:26px; font-weight:700; color:var(--primary);">{{ $collectionCount }}</div>
        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;"><i class="fas fa-hand-holding-usd"></i> Repayments Received</div>
        <div style="font-size:14px; font-weight:600; color:var(--primary); margin-top:6px;">KSH {{ number_format($collections, 0) }}</div>
    </div>
    <div class="card" style="border-left:4px solid var(--warning); text-align:center; padding:20px;">
        <div style="font-size:26px; font-weight:700; color:var(--warning);">{{ $pendingApprovals }}</div>
        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;"><i class="fas fa-clock"></i> Pending Approvals</div>
        <div style="font-size:14px; font-weight:600; color:var(--warning); margin-top:6px;">{{ $pendingDisbursement }} awaiting disbursement</div>
    </div>
</div>

{{-- Transaction Breakdown --}}
<div class="grid-2" style="margin-bottom:20px; gap:20px;">
    <div class="card">
        <div style="font-size:13px; font-weight:600; margin-bottom:14px;">Transaction Breakdown</div>
        <table class="data-table">
            <thead><tr><th>Type</th><th>Direction</th><th>Count</th><th>Amount</th></tr></thead>
            <tbody>
                @forelse($txnByType as $t)
                <tr>
                    <td style="font-size:12px;">{{ ucfirst(str_replace('_',' ',$t->transaction_type)) }}</td>
                    <td>
                        <span class="badge {{ $t->direction === 'credit' ? 'badge-success' : 'badge-danger' }}">
                            {{ ucfirst($t->direction) }}
                        </span>
                    </td>
                    <td>{{ $t->cnt }}</td>
                    <td style="font-weight:600; color:{{ $t->direction === 'credit' ? 'var(--success)' : 'var(--danger)' }};">
                        KSH {{ number_format($t->total, 0) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; padding:20px; color:var(--text-secondary);">No transactions</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card">
        <div style="font-size:13px; font-weight:600; margin-bottom:14px;">Recent Transactions</div>
        <div style="max-height:280px; overflow-y:auto;">
            @forelse($transactions->take(15) as $txn)
            <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                <div>
                    <div style="font-size:13px; font-weight:500;">{{ $txn->customer?->full_name ?? 'N/A' }}</div>
                    <div style="font-size:11px; color:var(--text-secondary);">{{ ucfirst(str_replace('_',' ',$txn->transaction_type)) }} · {{ $txn->created_at->format('h:i A') }}</div>
                </div>
                <div style="font-weight:700; color:{{ $txn->direction === 'credit' ? 'var(--success)' : 'var(--danger)' }}; font-size:13px;">
                    {{ $txn->direction === 'credit' ? '+' : '-' }} KSH {{ number_format($txn->amount, 0) }}
                </div>
            </div>
            @empty
            <div style="text-align:center; padding:30px; color:var(--text-secondary); font-size:13px;">No transactions on this day</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
