@extends('layouts.app')
@section('title', 'Outstanding Loan Book - Reports')
@section('page-title', 'Outstanding Loan Book')

@section('styles')
<style>
    .stat-card { background:#fff; border-radius:12px; padding:18px 20px; border:1px solid var(--border); box-shadow:0 2px 8px rgba(0,0,0,0.05); }
    .risk-bar { height:8px; border-radius:4px; margin-top:6px; }
</style>
@endsection

@section('content')
<div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Reports
    </a>
    <span style="font-size:12px; color:var(--text-secondary);">As at {{ now()->format('d M Y, h:i A') }}</span>
</div>

{{-- Summary Cards --}}
<div class="grid-4" style="margin-bottom:20px;">
    <div class="stat-card" style="border-left:4px solid var(--primary);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Active Loans</div>
        <div style="font-size:26px; font-weight:700; color:var(--primary);">{{ number_format($totals->count) }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--success);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Outstanding Balance</div>
        <div style="font-size:22px; font-weight:700; color:var(--success);">KSH {{ number_format($totals->total_outstanding, 0) }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--warning);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Total Arrears</div>
        <div style="font-size:22px; font-weight:700; color:var(--warning);">KSH {{ number_format($totals->total_arrears, 0) }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid #9C27B0;">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Total Collected</div>
        <div style="font-size:22px; font-weight:700; color:#9C27B0;">KSH {{ number_format($totals->total_collected, 0) }}</div>
    </div>
</div>

{{-- Breakdown row --}}
<div class="grid-2" style="margin-bottom:20px; gap:20px;">
    <div class="card">
        <div style="font-size:13px; font-weight:600; margin-bottom:14px; color:var(--text-primary);">By Product</div>
        @foreach($byProduct as $row)
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <span style="font-size:13px;">{{ $row->product }}</span>
            <div style="text-align:right;">
                <div style="font-size:13px; font-weight:600;">KSH {{ number_format($row->olb, 0) }}</div>
                <div style="font-size:11px; color:var(--text-secondary);">{{ $row->cnt }} loans</div>
            </div>
        </div>
        @php $pct = $totals->total_outstanding > 0 ? ($row->olb / $totals->total_outstanding) * 100 : 0; @endphp
        <div style="height:6px; background:#E8ECF1; border-radius:3px; margin-bottom:12px;">
            <div style="width:{{ $pct }}%; height:100%; background:var(--primary); border-radius:3px;"></div>
        </div>
        @endforeach
    </div>
    <div class="card">
        <div style="font-size:13px; font-weight:600; margin-bottom:14px; color:var(--text-primary);">By Risk Category</div>
        @php
            $riskColors = ['low' => '#4CAF50', 'medium' => '#FF9800', 'high' => '#FF5722', 'watch' => '#9C27B0', 'default' => '#F44336'];
        @endphp
        @foreach($riskColors as $risk => $color)
        @php $row = $byRisk[$risk] ?? null; @endphp
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="width:10px; height:10px; border-radius:50%; background:{{ $color }};"></div>
                <span style="font-size:13px; text-transform:capitalize;">{{ $risk }}</span>
            </div>
            <div style="text-align:right;">
                <span style="font-size:13px; font-weight:600; color:{{ $color }};">{{ $row ? number_format($row->cnt) : 0 }}</span>
                <span style="font-size:11px; color:var(--text-secondary); margin-left:6px;">loans</span>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('reports.portfolio.loan-book') }}">
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Branch</label>
                <select name="branch" class="filter-select" style="width:160px;">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Product</label>
                <select name="product" class="filter-select" style="width:180px;">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ request('product') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Risk</label>
                <select name="risk" class="filter-select" style="width:140px;">
                    <option value="">All Risk</option>
                    @foreach(['low','medium','high','watch','default'] as $r)
                        <option value="{{ $r }}" {{ request('risk') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Search</label>
                <div class="search-box" style="width:200px;">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name / Loan No…">
                </div>
            </div>
            <div style="display:flex; gap:8px; padding-bottom:1px;">
                <button type="submit" class="btn btn-primary" style="height:38px; padding:0 18px;"><i class="fas fa-search"></i> Filter</button>
                <a href="{{ route('reports.portfolio.loan-book') }}" class="btn btn-outline" style="height:38px; padding:0 14px;"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header" style="margin-bottom:14px;">
        <span style="font-size:14px; font-weight:600;">Loan Book — {{ $loans->total() }} loans</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table" style="min-width:1100px;">
            <thead>
                <tr>
                    <th>#</th><th>Loan No.</th><th>Customer</th><th>Product</th>
                    <th>Principal</th><th>Outstanding</th><th>Total Paid</th>
                    <th>Arrears</th><th>Days Arrears</th><th>Risk</th>
                    <th>Next Due</th><th>Branch</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $i => $loan)
                @php
                    $riskColor = match($loan->risk_category) {
                        'low' => ['#E8F5E9','#2E7D32'], 'medium' => ['#FFF3E0','#E65100'],
                        'high' => ['#FBE9E7','#BF360C'], 'watch' => ['#F3E5F5','#6A1B9A'],
                        default => ['#FFEBEE','#C62828']
                    };
                @endphp
                <tr>
                    <td style="color:var(--text-secondary); font-size:12px;">{{ ($loans->currentPage()-1)*$loans->perPage()+$i+1 }}</td>
                    <td><a href="{{ route('loans.show', $loan) }}" style="font-family:monospace; font-size:12px; color:var(--primary); font-weight:600;">{{ $loan->loan_number }}</a></td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $loan->customer->full_name }}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">{{ $loan->customer->phone_number }}</div>
                    </td>
                    <td style="font-size:12px;">{{ $loan->product->name ?? '—' }}</td>
                    <td style="font-weight:600;">KSH {{ number_format($loan->principal_amount, 0) }}</td>
                    <td style="font-weight:700; color:var(--primary);">KSH {{ number_format($loan->outstanding_balance, 0) }}</td>
                    <td style="color:var(--success);">KSH {{ number_format($loan->total_paid, 0) }}</td>
                    <td style="color:{{ $loan->arrears_amount > 0 ? 'var(--danger)' : 'var(--text-secondary)' }}; font-weight:{{ $loan->arrears_amount > 0 ? '700' : '400' }};">
                        KSH {{ number_format($loan->arrears_amount, 0) }}
                    </td>
                    <td style="color:{{ $loan->days_in_arrears > 0 ? 'var(--danger)' : 'var(--text-secondary)' }};">
                        {{ $loan->days_in_arrears }}d
                    </td>
                    <td>
                        <span class="badge" style="background:{{ $riskColor[0] }}; color:{{ $riskColor[1] }};">
                            {{ ucfirst($loan->risk_category) }}
                        </span>
                    </td>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $loan->next_due_date?->format('d M Y') ?? '—' }}</td>
                    <td style="font-size:12px;">{{ $loan->branch->name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="12" style="text-align:center; padding:50px; color:var(--text-secondary);">No active loans found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($loans->hasPages())
    <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 4px 4px; border-top:1px solid var(--border); margin-top:8px;">
        <span style="font-size:12px; color:var(--text-secondary);">Showing {{ $loans->firstItem() }}–{{ $loans->lastItem() }} of {{ $loans->total() }}</span>
        {{ $loans->links() }}
    </div>
    @endif
</div>
@endsection
