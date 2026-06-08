{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard - Mweela Cash Capital')
@section('page-title', 'Dashboard')

@section('content')
{{-- Filter Bar --}}
<div class="filter-bar">
    <select class="filter-select" id="officerFilter">
        <option value="">Relationship Officer</option>
        @foreach($officers as $officer)
            <option value="{{ $officer->id }}">{{ $officer->name }}</option>
        @endforeach
    </select>

    <select class="filter-select" id="branchFilter">
        <option value="">Branch</option>
        @foreach($branches as $branch)
            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
        @endforeach
    </select>

    <button class="btn btn-primary">
        <i class="fas fa-search"></i> Search
    </button>
</div>

{{-- Pending Actions --}}
<div class="grid-2" style="margin-bottom: 20px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">Loans Pending Approvals</span>
            <span class="badge badge-warning">{{ $pendingApprovals }}</span>
        </div>
        <div class="metric-value" style="font-size: 36px;">{{ $pendingApprovals }}</div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <span class="card-title">Loans Pending Disbursement</span>
            <span class="badge badge-primary">{{ $pendingDisbursement }}</span>
        </div>
        <div class="metric-value" style="font-size: 36px;">{{ $pendingDisbursement }}</div>
    </div>
</div>

{{-- Overdue & Risk Summary --}}
<div class="grid-3" style="margin-bottom: 20px;">
    <div class="card" style="border-left: 4px solid var(--danger);">
        <div class="card-header">
            <span class="card-title">Overdue Loans</span>
            <span class="badge badge-danger">{{ $overdueLoansCount }}</span>
        </div>
        <div class="metric-value" style="font-size: 32px; color: var(--danger);">{{ $overdueLoansCount }}</div>
        <div class="metric-label">KSH {{ number_format($overdueAmount, 0) }} outstanding</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--warning);">
        <div class="card-header">
            <span class="card-title">Portfolio at Risk (PAR30)</span>
            <span class="badge badge-warning">{{ $parPercentage }}%</span>
        </div>
        <div class="metric-value" style="font-size: 32px; color: var(--warning);">{{ $parPercentage }}%</div>
        <div class="metric-label">KSH {{ number_format($portfolioAtRisk ?? 0, 0) }} at risk</div>
    </div>
    <div class="card" style="border-left: 4px solid #6A1B9A;">
        <div class="card-header">
            <span class="card-title">Non-Performing Loans</span>
            <span class="badge" style="background:#6A1B9A; color:white;">{{ $nplCount }}</span>
        </div>
        <div class="metric-value" style="font-size: 32px; color: #6A1B9A;">{{ $nplCount }}</div>
        <div class="metric-label">KSH {{ number_format($nplAmount, 0) }} NPL amount</div>
    </div>
</div>

{{-- Portfolio & Performance --}}
<div class="grid-2" style="margin-bottom: 20px;">
    {{-- Portfolio --}}
    <div class="card">
        <div class="card-header">
            <span class="badge badge-primary">Portfolio</span>
        </div>
        <div class="grid-2" style="gap: 15px; grid-template-columns: repeat(2,1fr);">
            <div>
                <div class="metric-value" style="font-size: 32px; color: var(--primary);">{{ $totalCustomers }}</div>
                <div class="metric-label">Total Customers</div>
            </div>
            <div>
                <div class="metric-value" style="font-size: 32px; color: var(--primary);">{{ $activeCustomers }}</div>
                <div class="metric-label">Active Customers</div>
            </div>
            <div>
                <div class="metric-value" style="font-size: 32px; color: var(--success);">{{ $inactiveCustomers }}</div>
                <div class="metric-label">Inactive Customers</div>
            </div>
            <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); border-radius: 8px; padding: 15px; color: white;">
                <div style="font-size: 11px; opacity: 0.9;">OLB KSH</div>
                <div style="font-size: 24px; font-weight: 700;">{{ number_format($olb, 0) }}</div>
            </div>
        </div>
    </div>

    {{-- Performance --}}
    <div class="card">
        <div class="card-header">
            <span class="badge badge-primary">Performance</span>
        </div>
        <div class="circle-card-inner">
            <div class="circle-progress">
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle class="circle-bg" cx="60" cy="60" r="52"/>
                    <circle class="circle-fill" cx="60" cy="60" r="52" 
                        stroke="#00BCD4" 
                        stroke-dasharray="326.73" 
                        stroke-dashoffset="0"/>
                </svg>
                <div class="circle-text">
                    <div class="circle-percent" style="color: var(--primary);">{{ $fundedPercentage }}%</div>
                    <div class="circle-label">Funded</div>
                </div>
            </div>
            <div style="flex: 1;">
                <div style="margin-bottom: 15px;">
                    <div style="font-size: 11px; color: var(--text-secondary);">Disbursed Loans</div>
                    <div style="font-size: 20px; font-weight: 600;">{{ $disbursedLoans }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary);">Disbursed Amount</div>
                    <div style="font-size: 20px; font-weight: 600;">KSH {{ number_format($disbursedAmount, 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Collection & Risk --}}
<div class="grid-2" style="margin-bottom: 20px;">
    {{-- Collection --}}
    <div class="card">
        <div class="card-header">
            <span class="badge badge-success">Collection</span>
        </div>
        <div style="padding: 16px;">
            <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items:center;">
                <span style="font-size: 12px; color: var(--text-secondary);">Loans Due Today</span>
                <span style="font-size: 16px; font-weight: 600;">{{ $loansDueToday }}</span>
            </div>
            <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items:center;">
                <span style="font-size: 12px; color: var(--text-secondary);">Collections Today</span>
                <span style="font-size: 16px; font-weight: 600; color: var(--success);">KSH {{ number_format($collectionsToday, 0) }}</span>
            </div>
            <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items:center;">
                <span style="font-size: 12px; color: var(--text-secondary);">Total Loans Due (incl. overdue)</span>
                <span style="font-size: 16px; font-weight: 600;">{{ $loansDueCount }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items:center;">
                <span style="font-size: 12px; color: var(--text-secondary);">Prepaid Loans</span>
                <span style="font-size: 16px; font-weight: 600;">{{ $prepaidLoans }}</span>
            </div>
        </div>
    </div>

    {{-- Risk --}}
    <div class="card">
        <div class="card-header">
            <span class="badge badge-danger">Risk</span>
        </div>
        <div style="padding: 16px;">
            <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items:center;">
                <span style="font-size: 12px; color: var(--text-secondary);">Portfolio at Risk (PAR30)</span>
                <span style="font-size: 18px; font-weight: 700; color: var(--danger);">{{ $parPercentage }}%</span>
            </div>
            <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items:center;">
                <span style="font-size: 12px; color: var(--text-secondary);">Amount at Risk</span>
                <span style="font-size: 14px; font-weight: 600;">KSH {{ number_format($portfolioAtRisk ?? 0, 0) }}</span>
            </div>
            <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items:center;">
                <span style="font-size: 12px; color: var(--text-secondary);">Total Arrears</span>
                <span style="font-size: 14px; font-weight: 600; color: var(--danger);">KSH {{ number_format($totalArrears, 0) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items:center;">
                <span style="font-size: 12px; color: var(--text-secondary);">Arrears Collected Today</span>
                <span style="font-size: 14px; font-weight: 600; color: var(--success);">KSH {{ number_format($arrearsCollectedToday, 0) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- NPL Breakdown --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <span class="card-title">Non-Performing Loan Breakdown</span>
        <span class="form-hint">Includes defaulted and written-off loans</span>
    </div>
    <div class="grid-4">
        <div>
            <div class="metric-label">NPL Count</div>
            <div class="metric-value" style="font-size: 24px;">{{ $nplCount }}</div>
        </div>
        <div>
            <div class="metric-label">NPL Principal</div>
            <div class="metric-value" style="font-size: 24px;">KSH {{ number_format($nplPrincipal, 0) }}</div>
        </div>
        <div>
            <div class="metric-label">NPL Outstanding</div>
            <div class="metric-value" style="font-size: 24px;">KSH {{ number_format($nplAmount, 0) }}</div>
        </div>
        <div>
            <div class="metric-label">NPL Ratio</div>
            <div class="metric-value" style="font-size: 24px;">{{ $totalPortfolio > 0 ? round(($nplAmount / $totalPortfolio) * 100, 1) : 0 }}%</div>
        </div>
    </div>
</div>

{{-- Recent Transactions --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Today's Transactions</span>
        <a href="{{ route('transactions.processed') }}" class="btn btn-outline" style="font-size: 12px;">View All</a>
    </div>
    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Names</th>
                <th>Transaction Type</th>
                <th>Transaction ID</th>
                <th>Amount Received</th>
                <th>Date Captured</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentTransactions as $index => $txn)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $txn->customer?->full_name ?? 'N/A' }}</td>
                <td>
                    <span class="badge badge-primary">{{ ucfirst(str_replace('_', ' ', $txn->transaction_type)) }}</span>
                </td>
                <td>{{ $txn->transaction_number }}</td>
                <td style="font-weight: 600;">{{ number_format($txn->amount, 0) }}</td>
                <td style="color: var(--text-secondary);">{{ $txn->created_at->format('d-M-y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="empty-state">No transactions today</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Animate circle progress on load
    document.addEventListener('DOMContentLoaded', function() {
        const circles = document.querySelectorAll('.circle-fill');
        circles.forEach(circle => {
            const offset = circle.getAttribute('stroke-dashoffset');
            circle.style.strokeDashoffset = offset;
            setTimeout(() => {
                circle.style.transition = 'stroke-dashoffset 1s ease';
            }, 100);
        });
    });
</script>
@endsection