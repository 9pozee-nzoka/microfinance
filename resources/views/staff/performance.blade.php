@extends('layouts.app')

@section('title', 'Staff Performance - Mweela Cash Capital')
@section('page-title', 'Staff Performance: ' . $user->name)

@section('content')

<div style="margin-bottom:20px;">
    <a href="{{ route('staff.index') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back to Staff
    </a>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-value">{{ $customersCount }}</div>
        <div class="stat-label">Total Customers</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $activeCustomers }}</div>
        <div class="stat-label">Active Customers</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $loansCount }}</div>
        <div class="stat-label">Total Loans</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $activeLoans }}</div>
        <div class="stat-label">Active Loans</div>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-value" style="color:var(--primary);">KSH {{ number_format($totalDisbursed, 0) }}</div>
        <div class="stat-label">Total Disbursed</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--success);">KSH {{ number_format($totalCollected, 0) }}</div>
        <div class="stat-label">Total Collected</div>
    </div>
</div>

{{-- Recent Loans --}}
<div class="form-section">
    <div class="section-heading"><i class="fas fa-list"></i> Recent Loans</div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Loan Number</th>
                    <th>Customer</th>
                    <th>Principal</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentLoans as $loan)
                <tr>
                    <td><a href="{{ route('loans.show', $loan) }}">{{ $loan->loan_number }}</a></td>
                    <td>{{ $loan->customer?->full_name ?? '-' }}</td>
                    <td>KSH {{ number_format($loan->principal_amount, 0) }}</td>
                    <td><span class="badge badge-{{ $loan->status==='active'||$loan->status==='disbursed'?'success':($loan->status==='pending'?'warning':'secondary') }}">{{ ucfirst($loan->status) }}</span></td>
                    <td>{{ $loan->created_at->format('d-M-Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center; padding:20px; color:var(--text-secondary);">No loans yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
