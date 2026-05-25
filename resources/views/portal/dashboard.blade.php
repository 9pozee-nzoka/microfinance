@extends('portal.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

@if(session('success'))
<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

{{-- Welcome banner --}}
<div style="background: linear-gradient(135deg, #00BCD4 0%, #0097A7 100%); border-radius: 14px; padding: 24px 28px; margin-bottom: 24px; color: white; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="font-size: 20px; font-weight: 700; margin-bottom: 4px;">
            Hello, {{ explode(' ', $customer->full_name)[0] }} 👋
        </div>
        <div style="font-size: 13px; opacity: 0.85;">
            Member since {{ $customer->created_at->format('F Y') }} &nbsp;·&nbsp;
            {{ $customer->customer_number }}
        </div>
    </div>
    @if($customer->status === 'active')
        <span style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
            <i class="fas fa-check-circle"></i> Active Account
        </span>
    @endif
</div>

{{-- Stat cards --}}
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #E0F7FA; color: #00BCD4;">
            <i class="fas fa-wallet"></i>
        </div>
        <div>
            <div class="stat-value">KSH {{ number_format($totalOutstanding, 0) }}</div>
            <div class="stat-label">Total Outstanding</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #E8F5E9; color: #4CAF50;">
            <i class="fas fa-check-double"></i>
        </div>
        <div>
            <div class="stat-value">KSH {{ number_format($totalPaid, 0) }}</div>
            <div class="stat-label">Total Paid</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: #F3E5F5; color: #9C27B0;">
            <i class="fas fa-piggy-bank"></i>
        </div>
        <div>
            <div class="stat-value">KSH {{ number_format($customer->savings_balance, 0) }}</div>
            <div class="stat-label">Savings Balance</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: {{ $overdueCount > 0 ? '#FFEBEE' : '#FFF8E1' }}; color: {{ $overdueCount > 0 ? '#F44336' : '#FF9800' }};">
            <i class="fas fa-{{ $overdueCount > 0 ? 'exclamation-triangle' : 'star' }}"></i>
        </div>
        <div>
            <div class="stat-value" style="color: {{ $overdueCount > 0 ? '#F44336' : 'var(--text-primary)' }};">
                {{ $customer->credit_score }}
            </div>
            <div class="stat-label">Credit Score{{ $overdueCount > 0 ? ' ⚠ Overdue' : '' }}</div>
        </div>
    </div>
</div>

{{-- Next payment alert --}}
@if($nextDue)
@php
    $daysUntil = now()->startOfDay()->diffInDays($nextDue->due_date, false);
    $isOverdue = $daysUntil < 0;
    $isDueSoon = $daysUntil >= 0 && $daysUntil <= 3;
@endphp
<div class="alert {{ $isOverdue ? 'alert-danger' : ($isDueSoon ? 'alert-warning' : 'alert-info') }}" style="margin-bottom: 24px;">
    <i class="fas fa-{{ $isOverdue ? 'exclamation-triangle' : 'calendar-alt' }}"></i>
    <div>
        @if($isOverdue)
            <strong>Payment Overdue!</strong> Installment #{{ $nextDue->installment_number }} was due on {{ $nextDue->due_date->format('d M Y') }} ({{ abs($daysUntil) }} days ago).
        @elseif($isDueSoon)
            <strong>Payment Due Soon!</strong> Installment #{{ $nextDue->installment_number }} is due on {{ $nextDue->due_date->format('d M Y') }} (in {{ $daysUntil }} day{{ $daysUntil != 1 ? 's' : '' }}).
        @else
            Next payment of <strong>KSH {{ number_format($nextDue->total_amount - $nextDue->total_paid, 0) }}</strong> is due on {{ $nextDue->due_date->format('d M Y') }}.
        @endif
        &nbsp;
        <a href="{{ route('portal.loan.pay', $nextDue->loan_id) }}" style="font-weight: 700; text-decoration: underline;">Pay Now →</a>
    </div>
</div>
@endif

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

    {{-- Active Loans --}}
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div class="card-title">Active Loans</div>
            <a href="{{ route('portal.loans') }}" class="btn btn-outline btn-sm">View All</a>
        </div>

        @forelse($activeLoans as $loan)
        @php
            $progress = $loan->total_repayable > 0
                ? min(100, ($loan->total_paid / $loan->total_repayable) * 100)
                : 0;
        @endphp
        <div style="padding: 14px; background: #F8FAFC; border-radius: 10px; margin-bottom: 12px; border: 1px solid var(--border);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                <div>
                    <div style="font-size: 13px; font-weight: 700; color: var(--text-primary);">{{ $loan->loan_number }}</div>
                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">{{ $loan->product->name ?? '—' }}</div>
                </div>
                <span class="badge {{ $loan->days_in_arrears > 0 ? 'badge-danger' : 'badge-success' }}">
                    {{ $loan->days_in_arrears > 0 ? 'Overdue' : 'On Track' }}
                </span>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-secondary); margin-bottom: 8px;">
                <span>Outstanding: <strong style="color: var(--text-primary);">KSH {{ number_format($loan->outstanding_balance, 0) }}</strong></span>
                <span>{{ round($progress) }}% paid</span>
            </div>

            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" style="width: {{ $progress }}%; background: {{ $loan->days_in_arrears > 0 ? '#F44336' : '#4CAF50' }};"></div>
            </div>

            <div style="margin-top: 10px; display: flex; gap: 8px;">
                <a href="{{ route('portal.loan.detail', $loan) }}" class="btn btn-outline btn-sm" style="flex: 1; justify-content: center;">
                    <i class="fas fa-eye"></i> Details
                </a>
                <a href="{{ route('portal.loan.pay', $loan) }}" class="btn btn-primary btn-sm" style="flex: 1; justify-content: center;">
                    <i class="fas fa-money-bill-wave"></i> Pay
                </a>
            </div>
        </div>
        @empty
        <div style="text-align: center; padding: 30px; color: var(--text-secondary);">
            <i class="fas fa-hand-holding-usd" style="font-size: 32px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
            No active loans
        </div>
        @endforelse
    </div>

    {{-- Recent Transactions --}}
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div class="card-title">Recent Transactions</div>
            <a href="{{ route('portal.transactions') }}" class="btn btn-outline btn-sm">View All</a>
        </div>

        @forelse($recentTransactions as $txn)
        <div style="display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border);">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: {{ $txn->direction === 'credit' ? '#E8F5E9' : '#FFEBEE' }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-{{ $txn->direction === 'credit' ? 'arrow-down' : 'arrow-up' }}"
                   style="color: {{ $txn->direction === 'credit' ? '#4CAF50' : '#F44336' }}; font-size: 13px;"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ ucfirst(str_replace('_', ' ', $txn->transaction_type)) }}
                </div>
                <div style="font-size: 11px; color: var(--text-secondary);">
                    {{ $txn->created_at->format('d M Y') }}
                </div>
            </div>
            <div style="font-size: 14px; font-weight: 700; color: {{ $txn->direction === 'credit' ? '#4CAF50' : '#F44336' }}; white-space: nowrap;">
                {{ $txn->direction === 'credit' ? '+' : '-' }} KSH {{ number_format($txn->amount, 0) }}
            </div>
        </div>
        @empty
        <div style="text-align: center; padding: 30px; color: var(--text-secondary);">
            <i class="fas fa-exchange-alt" style="font-size: 32px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
            No transactions yet
        </div>
        @endforelse
    </div>

</div>

@endsection
