@extends('layouts.app')
@section('title', 'Branch Performance - Reports')
@section('page-title', 'Branch Performance')

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Reports</a>
    <form method="GET" action="{{ route('reports.operational.branches') }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <div>
            <label class="form-label">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->toDateString()) }}" class="filter-select">
        </div>
        <div>
            <label class="form-label">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to', $dateTo->toDateString()) }}" class="filter-select">
        </div>
        <div style="padding-top:18px;">
            <button type="submit" class="btn btn-primary" style="height:38px; padding:0 16px;"><i class="fas fa-search"></i> Filter</button>
        </div>
    </form>
</div>

<div style="font-size:13px; color:var(--text-secondary); margin-bottom:20px;">
    Period: <strong>{{ $dateFrom->format('d M Y') }}</strong> — <strong>{{ $dateTo->format('d M Y') }}</strong>
</div>

{{-- Summary Metrics --}}
<div class="grid-4" style="margin-bottom:20px;">
    <div class="card card-primary">
        <div class="metric-label" style="margin-bottom:4px;">Total Branches</div>
        <div class="metric-value" style="font-size:22px;">{{ number_format($summary['total_branches']) }}</div>
    </div>
    <div class="card card-secondary">
        <div class="metric-label" style="margin-bottom:4px;">Total Customers</div>
        <div class="metric-value" style="font-size:22px;">{{ number_format($summary['total_customers']) }}</div>
        <div class="metric-label">{{ number_format($summary['total_active_customers']) }} active</div>
    </div>
    <div class="card card-success">
        <div class="metric-label" style="margin-bottom:4px;">Active Loans</div>
        <div class="metric-value" style="font-size:22px;">{{ number_format($summary['total_active_loans']) }}</div>
    </div>
    <div class="card card-info">
        <div class="metric-label" style="margin-bottom:4px;">Avg OLB / Branch</div>
        <div class="metric-value" style="font-size:22px;">KSH {{ number_format($summary['avg_olb_per_branch'], 0) }}</div>
    </div>
</div>

<div class="grid-4" style="margin-bottom:20px;">
    <div class="card card-primary">
        <div class="metric-label" style="margin-bottom:4px;">Outstanding Balance</div>
        <div class="metric-value" style="font-size:22px;">KSH {{ number_format($summary['total_olb'], 0) }}</div>
    </div>
    <div class="card card-danger">
        <div class="metric-label" style="margin-bottom:4px;">Total Arrears</div>
        <div class="metric-value" style="font-size:22px;">KSH {{ number_format($summary['total_arrears'], 0) }}</div>
    </div>
    <div class="card card-success">
        <div class="metric-label" style="margin-bottom:4px;">Disbursed (Period)</div>
        <div class="metric-value" style="font-size:22px;">KSH {{ number_format($summary['total_disbursed_period'], 0) }}</div>
    </div>
    <div class="card card-info">
        <div class="metric-label" style="margin-bottom:4px;">Collected (Period)</div>
        <div class="metric-value" style="font-size:22px;">KSH {{ number_format($summary['total_collected_period'], 0) }}</div>
    </div>
</div>

@php
$collectionRate = $summary['total_disbursed_period'] > 0
    ? round(($summary['total_collected_period'] / $summary['total_disbursed_period']) * 100, 1)
    : 0;
@endphp
<div class="grid-2" style="margin-bottom:20px; gap:20px;">
    <div class="card card-dark" style="display:flex; align-items:center; gap:16px;">
        <div class="circle-progress" style="width:100px; height:100px;">
            <svg width="100" height="100" viewBox="0 0 120 120">
                <circle class="circle-bg" cx="60" cy="60" r="52"/>
                <circle class="circle-fill" cx="60" cy="60" r="52"
                    stroke-dasharray="326.73"
                    stroke-dashoffset="{{ 326.73 * (1 - min($summary['par_percentage'], 100) / 100) }}"/>
            </svg>
            <div class="circle-text">
                <div class="circle-percent">{{ $summary['par_percentage'] }}%</div>
                <div class="circle-label">Portfolio at Risk</div>
            </div>
        </div>
        <div>
            <div style="font-size:14px; font-weight:600; color:#fff;">PAR Rate</div>
            <div style="font-size:12px; color:rgba(255,255,255,0.80); margin-top:4px;">Arrears as a share of total OLB</div>
        </div>
    </div>
    <div class="card card-success">
        <div class="metric-label" style="margin-bottom:4px;">Collection Rate</div>
        <div class="metric-value" style="font-size:28px;">{{ $collectionRate }}%</div>
        <div class="progress-track" style="height:6px; border-radius:3px; margin-top:8px;">
            <div class="progress-fill" style="width:{{ min($collectionRate, 100) }}%; height:100%; border-radius:3px;"></div>
        </div>
        <div class="metric-label">collected vs disbursed in period</div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Total Customers</th>
                    <th>Active Customers</th>
                    <th>Active Loans</th>
                    <th>Outstanding Balance</th>
                    <th>Arrears</th>
                    <th>Disbursed (Period)</th>
                    <th>Collected (Period)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                @php $parRate = $branch->olb > 0 ? round(($branch->arrears / $branch->olb) * 100, 1) : 0; @endphp
                <tr>
                    <td>
                        <div style="font-weight:700; font-size:14px;">{{ $branch->name }}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">{{ $branch->code }}</div>
                    </td>
                    <td style="font-size:15px; font-weight:600;">{{ number_format($branch->customers_count) }}</td>
                    <td style="color:var(--success); font-weight:600;">{{ number_format($branch->active_customers_count) }}</td>
                    <td style="color:var(--primary); font-weight:600;">{{ number_format($branch->active_loans_count) }}</td>
                    <td style="font-weight:700; color:var(--primary);">KSH {{ number_format($branch->olb, 0) }}</td>
                    <td>
                        <div style="color:{{ $branch->arrears > 0 ? 'var(--danger)' : 'var(--text-secondary)' }}; font-weight:{{ $branch->arrears > 0 ? '700' : '400' }};">
                            KSH {{ number_format($branch->arrears, 0) }}
                        </div>
                        @if($parRate > 0)
                        <div style="font-size:11px; color:var(--danger);">PAR: {{ $parRate }}%</div>
                        @endif
                    </td>
                    <td style="color:var(--success); font-weight:600;">KSH {{ number_format($branch->disbursed_period, 0) }}</td>
                    <td style="color:var(--primary); font-weight:600;">KSH {{ number_format($branch->collected_period, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center; padding:50px; color:var(--text-secondary);">No branches found</td></tr>
                @endforelse
            </tbody>
            @if($branches->count() > 1)
            <tfoot style="background:#FAFBFC; font-weight:700;">
                <tr>
                    <td>TOTAL</td>
                    <td>{{ number_format($branches->sum('customers_count')) }}</td>
                    <td>{{ number_format($branches->sum('active_customers_count')) }}</td>
                    <td>{{ number_format($branches->sum('active_loans_count')) }}</td>
                    <td>KSH {{ number_format($branches->sum('olb'), 0) }}</td>
                    <td>KSH {{ number_format($branches->sum('arrears'), 0) }}</td>
                    <td>KSH {{ number_format($branches->sum('disbursed_period'), 0) }}</td>
                    <td>KSH {{ number_format($branches->sum('collected_period'), 0) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
        </div>
    </div>
</div>
@endsection
