@extends('portal.layouts.app')

@section('title', $loan->loan_number)
@section('page-title', 'Loan Details')

@section('content')

@if(session('success'))
<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

{{-- Back --}}
<div style="margin-bottom: 20px;">
    <a href="{{ route('portal.loans') }}" class="btn btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i> Back to Loans
    </a>
</div>

@php
    $progress = $loan->total_repayable > 0
        ? min(100, ($loan->total_paid / $loan->total_repayable) * 100)
        : 0;

    $badgeClass = match($loan->status) {
        'active', 'disbursed' => 'badge-success',
        'completed'           => 'badge-info',
        'pending', 'under_review', 'approved' => 'badge-warning',
        'rejected', 'defaulted' => 'badge-danger',
        default => 'badge-info',
    };
@endphp

{{-- Loan header card --}}
<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
        <div>
            <div style="font-size: 22px; font-weight: 700; font-family: monospace;">{{ $loan->loan_number }}</div>
            <div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">
                {{ $loan->product->name ?? '—' }} &nbsp;·&nbsp; {{ $loan->term_weeks }} weeks
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="badge {{ $badgeClass }}" style="font-size: 13px; padding: 6px 14px;">
                {{ ucfirst(str_replace('_', ' ', $loan->status)) }}
            </span>
            @if(in_array($loan->status, ['disbursed', 'active']))
            <a href="{{ route('portal.loan.pay', $loan) }}" class="btn btn-primary">
                <i class="fas fa-money-bill-wave"></i> Make Payment
            </a>
            @endif
        </div>
    </div>

    {{-- Progress --}}
    <div style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
            <span style="color: var(--text-secondary);">Repayment Progress</span>
            <span style="font-weight: 700;">{{ round($progress) }}% paid</span>
        </div>
        <div class="progress-bar-wrap" style="height: 12px;">
            <div class="progress-bar-fill" style="width: {{ $progress }}%; background: {{ $loan->days_in_arrears > 0 ? '#F44336' : '#4CAF50' }};"></div>
        </div>
        @if($loan->days_in_arrears > 0)
        <div style="font-size: 12px; color: var(--danger); margin-top: 6px;">
            <i class="fas fa-exclamation-triangle"></i>
            {{ $loan->days_in_arrears }} days in arrears — KSH {{ number_format($loan->arrears_amount, 0) }} overdue
        </div>
        @endif
    </div>

    {{-- Key figures --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px;">
        <div style="text-align: center; padding: 14px; background: #F0FBFD; border-radius: 10px; border: 1px solid #B3E5FC;">
            <div style="font-size: 18px; font-weight: 700; color: var(--primary);">KSH {{ number_format($loan->principal_amount, 0) }}</div>
            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">Principal</div>
        </div>
        <div style="text-align: center; padding: 14px; background: #FFF8E1; border-radius: 10px; border: 1px solid #FFE082;">
            <div style="font-size: 18px; font-weight: 700; color: #F57F17;">KSH {{ number_format($loan->interest_amount, 0) }}</div>
            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">Interest</div>
        </div>
        <div style="text-align: center; padding: 14px; background: #F3E5F5; border-radius: 10px; border: 1px solid #CE93D8;">
            <div style="font-size: 18px; font-weight: 700; color: #7B1FA2;">KSH {{ number_format($loan->total_repayable, 0) }}</div>
            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">Total Repayable</div>
        </div>
        <div style="text-align: center; padding: 14px; background: #E8F5E9; border-radius: 10px; border: 1px solid #A5D6A7;">
            <div style="font-size: 18px; font-weight: 700; color: #2E7D32;">KSH {{ number_format($loan->total_paid, 0) }}</div>
            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">Total Paid</div>
        </div>
        <div style="text-align: center; padding: 14px; background: #FFEBEE; border-radius: 10px; border: 1px solid #FFCDD2;">
            <div style="font-size: 18px; font-weight: 700; color: #C62828;">KSH {{ number_format($loan->outstanding_balance, 0) }}</div>
            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">Outstanding</div>
        </div>
        <div style="text-align: center; padding: 14px; background: #F8FAFC; border-radius: 10px; border: 1px solid var(--border);">
            <div style="font-size: 18px; font-weight: 700; color: var(--text-primary);">KSH {{ number_format($loan->weekly_installment, 0) }}</div>
            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 3px;">Weekly Installment</div>
        </div>
    </div>
</div>

{{-- Installment summary --}}
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;">
    <div class="card" style="text-align: center;">
        <div style="font-size: 28px; font-weight: 700; color: #4CAF50;">{{ $paidInstallments }}</div>
        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Paid Installments</div>
    </div>
    <div class="card" style="text-align: center;">
        <div style="font-size: 28px; font-weight: 700; color: var(--primary);">{{ $pendingInstallments }}</div>
        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Remaining</div>
    </div>
    <div class="card" style="text-align: center;">
        <div style="font-size: 28px; font-weight: 700; color: {{ $overdueInstallments > 0 ? '#F44336' : '#9AA5B4' }};">{{ $overdueInstallments }}</div>
        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Overdue</div>
    </div>
</div>

{{-- Repayment Schedule --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-title" style="margin-bottom: 16px;">Repayment Schedule</div>

    <div style="overflow-x: auto;">
        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Due Date</th>
                    <th>Principal</th>
                    <th>Interest</th>
                    <th>Total Due</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($loan->repaymentSchedules as $schedule)
                @php
                    $rowBg = match($schedule->status) {
                        'paid'    => '#F1FFF3',
                        'overdue' => '#FFF5F5',
                        'partial' => '#FFFBF0',
                        default   => 'transparent',
                    };
                    $statusBadge = match($schedule->status) {
                        'paid'    => 'badge-success',
                        'overdue' => 'badge-danger',
                        'partial' => 'badge-warning',
                        'waived'  => 'badge-purple',
                        default   => 'badge-info',
                    };
                @endphp
                <tr style="background: {{ $rowBg }};">
                    <td style="font-weight: 700; color: var(--text-secondary);">{{ $schedule->installment_number }}</td>
                    <td style="font-size: 13px;">
                        {{ $schedule->due_date->format('d M Y') }}
                        @if($schedule->status !== 'paid' && $schedule->due_date->isPast())
                            <span style="font-size: 10px; color: var(--danger); display: block;">
                                {{ $schedule->due_date->diffInDays(now()) }}d overdue
                            </span>
                        @endif
                    </td>
                    <td>KSH {{ number_format($schedule->principal_amount, 0) }}</td>
                    <td>KSH {{ number_format($schedule->interest_amount, 0) }}</td>
                    <td style="font-weight: 600;">KSH {{ number_format($schedule->total_amount, 0) }}</td>
                    <td style="color: #4CAF50; font-weight: 600;">KSH {{ number_format($schedule->total_paid, 0) }}</td>
                    <td style="font-weight: 600; color: {{ $schedule->balance > 0 ? 'var(--primary)' : '#4CAF50' }};">
                        KSH {{ number_format(max(0, $schedule->total_amount - $schedule->total_paid), 0) }}
                    </td>
                    <td><span class="badge {{ $statusBadge }}">{{ ucfirst($schedule->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>

{{-- Payment History --}}
<div class="card">
    <div class="card-title" style="margin-bottom: 16px;">Payment History</div>

    @if($loan->repayments->isEmpty())
    <div style="text-align: center; padding: 30px; color: var(--text-secondary);">
        <i class="fas fa-receipt" style="font-size: 32px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
        No payments recorded yet
    </div>
    @else
    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Principal</th>
                <th>Interest</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($loan->repayments->sortByDesc('created_at') as $repayment)
            <tr>
                <td style="font-size: 12px;">{{ $repayment->created_at->format('d M Y h:i A') }}</td>
                <td style="font-weight: 700; color: #4CAF50;">KSH {{ number_format($repayment->amount, 0) }}</td>
                <td>KSH {{ number_format($repayment->principal_portion, 0) }}</td>
                <td>KSH {{ number_format($repayment->interest_portion, 0) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $repayment->payment_method)) }}</td>
                <td style="font-family: monospace; font-size: 12px;">{{ $repayment->transaction_reference ?? '—' }}</td>
                <td>
                    <span class="badge {{ $repayment->status === 'confirmed' ? 'badge-success' : ($repayment->status === 'reversed' ? 'badge-danger' : 'badge-warning') }}">
                        {{ ucfirst($repayment->status) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>

@endsection
