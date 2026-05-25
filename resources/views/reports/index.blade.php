@extends('layouts.app')

@section('title', 'Reports - GetCash Capital')
@section('page-title', 'Reports')

@section('styles')
<style>
    .report-category { margin-bottom: 32px; }
    .category-title {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.8px; color: var(--text-secondary);
        margin-bottom: 14px; padding-bottom: 8px;
        border-bottom: 2px solid var(--border);
        display: flex; align-items: center; gap: 8px;
    }
    .report-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
    .report-card {
        background: #fff; border-radius: 12px; border: 1px solid var(--border);
        padding: 20px; text-decoration: none; color: inherit;
        display: flex; align-items: flex-start; gap: 14px;
        transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }
    .report-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        border-color: var(--primary);
        color: inherit;
    }
    .report-icon {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; flex-shrink: 0;
    }
    .report-card-title { font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
    .report-card-desc  { font-size: 12px; color: var(--text-secondary); line-height: 1.5; }
    .report-card-badge {
        display: inline-block; margin-top: 8px; padding: 2px 8px;
        border-radius: 10px; font-size: 10px; font-weight: 600;
    }
</style>
@endsection

@section('content')

<div style="margin-bottom: 28px;">
    <p style="font-size: 14px; color: var(--text-secondary);">
        Generate and export reports across portfolio, operations, financials, and customers.
    </p>
</div>

{{-- ── Portfolio Reports ── --}}
<div class="report-category">
    <div class="category-title">
        <i class="fas fa-chart-pie" style="color: var(--primary);"></i> Portfolio Reports
    </div>
    <div class="report-grid">
        <a href="{{ route('reports.portfolio.loan-book') }}" class="report-card">
            <div class="report-icon" style="background: #E3F2FD; color: var(--primary);">
                <i class="fas fa-book-open"></i>
            </div>
            <div>
                <div class="report-card-title">Outstanding Loan Book</div>
                <div class="report-card-desc">All active loans with balances, product breakdown, and risk categories.</div>
                <span class="report-card-badge" style="background: #E3F2FD; color: var(--primary);">Live</span>
            </div>
        </a>
        <a href="{{ route('reports.portfolio.par') }}" class="report-card">
            <div class="report-icon" style="background: #FFEBEE; color: var(--danger);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <div class="report-card-title">Portfolio at Risk (PAR)</div>
                <div class="report-card-desc">Loans in arrears bucketed by PAR 1–30, 31–60, 61–90, and 90+ days.</div>
                <span class="report-card-badge" style="background: #FFEBEE; color: var(--danger);">Risk</span>
            </div>
        </a>
        <a href="{{ route('reports.portfolio.disbursements') }}" class="report-card">
            <div class="report-icon" style="background: #F3E5F5; color: #7B1FA2;">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div>
                <div class="report-card-title">Loan Disbursements</div>
                <div class="report-card-desc">Loans disbursed in a selected period by product, branch, and method.</div>
                <span class="report-card-badge" style="background: #F3E5F5; color: #7B1FA2;">Period</span>
            </div>
        </a>
        <a href="{{ route('reports.portfolio.collections') }}" class="report-card">
            <div class="report-icon" style="background: #E8F5E9; color: var(--success);">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div>
                <div class="report-card-title">Loan Collections</div>
                <div class="report-card-desc">Repayments received in a period with principal, interest, and penalty splits.</div>
                <span class="report-card-badge" style="background: #E8F5E9; color: var(--success);">Period</span>
            </div>
        </a>
    </div>
</div>

{{-- ── Operational Reports ── --}}
<div class="report-category">
    <div class="category-title">
        <i class="fas fa-cogs" style="color: var(--warning);"></i> Operational Reports
    </div>
    <div class="report-grid">
        <a href="{{ route('reports.operational.daily') }}" class="report-card">
            <div class="report-icon" style="background: #FFF3E0; color: var(--warning);">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div>
                <div class="report-card-title">Daily Activity Summary</div>
                <div class="report-card-desc">New customers, loans applied/approved/disbursed, and collections for any day.</div>
                <span class="report-card-badge" style="background: #FFF3E0; color: var(--warning);">Daily</span>
            </div>
        </a>
        <a href="{{ route('reports.operational.officers') }}" class="report-card">
            <div class="report-icon" style="background: #E8EAF6; color: #3F51B5;">
                <i class="fas fa-user-tie"></i>
            </div>
            <div>
                <div class="report-card-title">Officer Performance</div>
                <div class="report-card-desc">Loans originated, disbursed amounts, and collections per relationship officer.</div>
                <span class="report-card-badge" style="background: #E8EAF6; color: #3F51B5;">Period</span>
            </div>
        </a>
        <a href="{{ route('reports.operational.branches') }}" class="report-card">
            <div class="report-icon" style="background: #E0F2F1; color: #00796B;">
                <i class="fas fa-building"></i>
            </div>
            <div>
                <div class="report-card-title">Branch Performance</div>
                <div class="report-card-desc">Customer counts, active portfolio, disbursements, and collections per branch.</div>
                <span class="report-card-badge" style="background: #E0F2F1; color: #00796B;">Period</span>
            </div>
        </a>
    </div>
</div>

{{-- ── Financial Reports ── --}}
<div class="report-category">
    <div class="category-title">
        <i class="fas fa-coins" style="color: var(--success);"></i> Financial Reports
    </div>
    <div class="report-grid">
        <a href="{{ route('reports.financial.income') }}" class="report-card">
            <div class="report-icon" style="background: #E8F5E9; color: var(--success);">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <div class="report-card-title">Income Statement</div>
                <div class="report-card-desc">Interest income, processing fees, insurance fees, and penalties with 6-month trend.</div>
                <span class="report-card-badge" style="background: #E8F5E9; color: var(--success);">Period</span>
            </div>
        </a>
        <a href="{{ route('reports.financial.ledger') }}" class="report-card">
            <div class="report-icon" style="background: #ECEFF1; color: #546E7A;">
                <i class="fas fa-list-alt"></i>
            </div>
            <div>
                <div class="report-card-title">Transaction Ledger</div>
                <div class="report-card-desc">Full transaction history with type, source, direction filters and CSV export.</div>
                <span class="report-card-badge" style="background: #ECEFF1; color: #546E7A;">Export</span>
            </div>
        </a>
    </div>
</div>

{{-- ── Customer Reports ── --}}
<div class="report-category">
    <div class="category-title">
        <i class="fas fa-users" style="color: #9C27B0;"></i> Customer Reports
    </div>
    <div class="report-grid">
        <a href="{{ route('reports.customers.register') }}" class="report-card">
            <div class="report-icon" style="background: #F3E5F5; color: #7B1FA2;">
                <i class="fas fa-address-book"></i>
            </div>
            <div>
                <div class="report-card-title">Customer Register</div>
                <div class="report-card-desc">Full customer directory with status, branch, employment, and savings filters. CSV export.</div>
                <span class="report-card-badge" style="background: #F3E5F5; color: #7B1FA2;">Export</span>
            </div>
        </a>
        <a href="{{ route('reports.customers.credit-scores') }}" class="report-card">
            <div class="report-icon" style="background: #FFF8E1; color: #F57F17;">
                <i class="fas fa-star"></i>
            </div>
            <div>
                <div class="report-card-title">Credit Score Distribution</div>
                <div class="report-card-desc">Score band breakdown (Excellent → Bad) with top-ranked customers.</div>
                <span class="report-card-badge" style="background: #FFF8E1; color: #F57F17;">Live</span>
            </div>
        </a>
    </div>
</div>

@endsection
