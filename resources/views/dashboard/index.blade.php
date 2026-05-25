{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard - GetCash Capital')
@section('page-title', 'Dashboard')

@section('content')
{{-- Filter Bar --}}
<div class="filter-bar">
    <select class="filter-select" id="officerFilter">
        <option value="">Relationship Officer</option>
        <option value="joshua">Joshua Kyalo</option>
        <option value="samuel">Samuel Muimi</option>
    </select>
    
    <select class="filter-select" id="branchFilter">
        <option value="">Branch</option>
        <option value="sombe">Sombe</option>
        <option value="nairobi">Nairobi</option>
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

{{-- Portfolio & Performance --}}
<div class="grid-2" style="margin-bottom: 20px;">
    {{-- Portfolio --}}
    <div class="card">
        <div class="card-header">
            <span class="badge badge-primary">Portfolio</span>
        </div>
        <div class="grid-2" style="gap: 15px;">
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
        <div style="display: flex; align-items: center; gap: 30px;">
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
        <div style="display: flex; align-items: center; gap: 30px;">
            <div class="circle-progress">
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle class="circle-bg" cx="60" cy="60" r="52"/>
                    <circle class="circle-fill" cx="60" cy="60" r="52" 
                        stroke="#4CAF50" 
                        stroke-dasharray="326.73" 
                        stroke-dashoffset="326.73"/>
                </svg>
                <div class="circle-text">
                    <div class="circle-percent" style="color: var(--success);">0%</div>
                    <div class="circle-label">Collection Rate</div>
                </div>
            </div>
            <div style="flex: 1;">
                <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                    <span style="font-size: 12px; color: var(--text-secondary);">Loans Due Today</span>
                    <span style="font-size: 13px; font-weight: 600;">KSH {{ number_format($loansDueToday, 0) }}</span>
                </div>
                <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                    <span style="font-size: 12px; color: var(--text-secondary);">Collections</span>
                    <span style="font-size: 13px; font-weight: 600;">KSH {{ number_format($collectionsToday, 1) }}</span>
                </div>
                <div style="margin-bottom: 10px; display: flex; justify-content: space-between;">
                    <span style="font-size: 12px; color: var(--text-secondary);">Loans Due Count</span>
                    <span style="font-size: 13px; font-weight: 600;">{{ $loansDueCount }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-size: 12px; color: var(--text-secondary);">Prepaid Loans</span>
                    <span style="font-size: 13px; font-weight: 600;">KSH {{ number_format($prepaidLoans, 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Risk --}}
    <div class="card">
        <div class="card-header">
            <span class="badge badge-danger">Risk</span>
        </div>
        <div style="display: flex; align-items: center; gap: 30px;">
            <div class="circle-progress">
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle class="circle-bg" cx="60" cy="60" r="52"/>
                    <circle class="circle-fill" cx="60" cy="60" r="52" 
                        stroke="#F44336" 
                        stroke-dasharray="326.73" 
                        stroke-dashoffset="{{ 326.73 - (326.73 * $parPercentage / 100) }}"/>
                </svg>
                <div class="circle-text">
                    <div class="circle-percent" style="color: var(--danger);">{{ $parPercentage }}%</div>
                    <div class="circle-label">Portfolio at Risk</div>
                </div>
            </div>
            <div style="flex: 1;">
                <div style="margin-bottom: 15px;">
                    <div style="font-size: 11px; color: var(--text-secondary);">Total Arrears</div>
                    <div style="font-size: 20px; font-weight: 600; color: var(--danger);">KSH {{ number_format($totalArrears, 0) }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary);">Arrears Collected Today</div>
                    <div style="font-size: 20px; font-weight: 600; color: var(--success);">KSH {{ number_format($arrearsCollectedToday, 0) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- NPL Breakdown --}}
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <span class="card-title">Non-Performing Loan Breakdown</span>
    </div>
    <div class="grid-4">
        <div>
            <div class="metric-label">Principal Amount in NPL</div>
            <div class="metric-value" style="font-size: 24px;">{{ number_format($nplPrincipal, 0) }}</div>
        </div>
        <div>
            <div class="metric-label">NPL Amount</div>
            <div class="metric-value" style="font-size: 24px;">{{ number_format($nplAmount, 0) }}</div>
        </div>
        <div>
            <div class="metric-label">Number of NPLs</div>
            <div class="metric-value" style="font-size: 24px;">{{ $nplCount }}</div>
        </div>
        <div>
            <div class="metric-label">Percentage Turnover</div>
            <div class="metric-value" style="font-size: 24px;">% 0.0</div>
        </div>
    </div>
</div>

{{-- Recent Transactions --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Today's Transactions</span>
        <a href="{{ route('transactions.processed') }}" class="btn btn-outline" style="font-size: 12px;">View All</a>
    </div>
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
                <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-secondary);">
                    No transactions today
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
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