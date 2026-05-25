@extends('layouts.app')
@section('title', 'Portfolio at Risk - Reports')
@section('page-title', 'Portfolio at Risk (PAR)')

@section('content')
<div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Reports</a>
    <span style="font-size:12px; color:var(--text-secondary);">As at {{ now()->format('d M Y, h:i A') }}</span>
</div>

{{-- PAR Summary --}}
<div class="grid-3" style="margin-bottom:20px; gap:20px;">
    <div class="card" style="border-left:4px solid var(--danger); text-align:center; padding:24px;">
        <div style="font-size:36px; font-weight:800; color:var(--danger);">{{ $parRate }}%</div>
        <div style="font-size:13px; color:var(--text-secondary); margin-top:4px;">PAR Rate (≥1 day)</div>
    </div>
    <div class="card" style="border-left:4px solid var(--warning); text-align:center; padding:24px;">
        <div style="font-size:26px; font-weight:700; color:var(--warning);">KSH {{ number_format($parAmount, 0) }}</div>
        <div style="font-size:13px; color:var(--text-secondary); margin-top:4px;">At-Risk Portfolio</div>
    </div>
    <div class="card" style="border-left:4px solid var(--primary); text-align:center; padding:24px;">
        <div style="font-size:26px; font-weight:700; color:var(--primary);">KSH {{ number_format($totalPortfolio, 0) }}</div>
        <div style="font-size:13px; color:var(--text-secondary); margin-top:4px;">Total Portfolio (OLB)</div>
    </div>
</div>

{{-- PAR Buckets --}}
<div class="card" style="margin-bottom:20px;">
    <div style="font-size:14px; font-weight:600; margin-bottom:16px;">PAR Aging Buckets</div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr><th>Bucket</th><th>No. of Loans</th><th>Outstanding Balance</th><th>Arrears Amount</th><th>% of Portfolio</th></tr>
            </thead>
            <tbody>
                @foreach($buckets as $b)
                @php $pct = $totalPortfolio > 0 ? round(($b['olb'] / $totalPortfolio) * 100, 2) : 0; @endphp
                <tr>
                    <td style="font-weight:600;">{{ $b['label'] }}</td>
                    <td>{{ number_format($b['count']) }}</td>
                    <td style="font-weight:600; color:var(--danger);">KSH {{ number_format($b['olb'], 0) }}</td>
                    <td style="color:var(--warning);">KSH {{ number_format($b['arrears'], 0) }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="flex:1; height:8px; background:#E8ECF1; border-radius:4px; overflow:hidden;">
                                <div style="width:{{ $pct }}%; height:100%; background:var(--danger); border-radius:4px;"></div>
                            </div>
                            <span style="font-size:12px; font-weight:600; min-width:40px;">{{ $pct }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('reports.portfolio.par') }}">
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
            <div>
                <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:4px;">Min Days in Arrears</label>
                <select name="par_days" class="filter-select" style="width:160px;">
                    @foreach([1 => 'PAR 1+', 30 => 'PAR 30+', 60 => 'PAR 60+', 90 => 'PAR 90+'] as $d => $label)
                        <option value="{{ $d }}" {{ request('par_days', 1) == $d ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
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
            <div style="display:flex; gap:8px; padding-bottom:1px;">
                <button type="submit" class="btn btn-primary" style="height:38px; padding:0 18px;"><i class="fas fa-search"></i> Filter</button>
                <a href="{{ route('reports.portfolio.par') }}" class="btn btn-outline" style="height:38px; padding:0 14px;"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>
</div>

{{-- Loans Table --}}
<div class="card">
    <div class="card-header" style="margin-bottom:14px;">
        <span style="font-size:14px; font-weight:600;">Loans in Arrears — {{ $loans->total() }} records</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table" style="min-width:1000px;">
            <thead>
                <tr><th>#</th><th>Loan No.</th><th>Customer</th><th>Product</th><th>Outstanding</th><th>Arrears</th><th>Days in Arrears</th><th>Risk</th><th>Branch</th><th>Officer</th></tr>
            </thead>
            <tbody>
                @forelse($loans as $i => $loan)
                @php
                    $dColor = $loan->days_in_arrears > 90 ? 'var(--danger)' : ($loan->days_in_arrears > 30 ? 'var(--warning)' : '#FF9800');
                @endphp
                <tr>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ ($loans->currentPage()-1)*$loans->perPage()+$i+1 }}</td>
                    <td><a href="{{ route('loans.show', $loan) }}" style="font-family:monospace; font-size:12px; color:var(--primary); font-weight:600;">{{ $loan->loan_number }}</a></td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $loan->customer->full_name }}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">{{ $loan->customer->phone_number }}</div>
                    </td>
                    <td style="font-size:12px;">{{ $loan->product->name ?? '—' }}</td>
                    <td style="font-weight:700; color:var(--primary);">KSH {{ number_format($loan->outstanding_balance, 0) }}</td>
                    <td style="font-weight:700; color:var(--danger);">KSH {{ number_format($loan->arrears_amount, 0) }}</td>
                    <td>
                        <span style="font-size:14px; font-weight:800; color:{{ $dColor }};">{{ $loan->days_in_arrears }}</span>
                        <span style="font-size:11px; color:var(--text-secondary);"> days</span>
                    </td>
                    <td><span class="badge badge-danger">{{ ucfirst($loan->risk_category) }}</span></td>
                    <td style="font-size:12px;">{{ $loan->branch->name ?? '—' }}</td>
                    <td style="font-size:12px;">{{ $loan->relationshipOfficer->name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center; padding:50px; color:var(--text-secondary);">No loans in arrears</td></tr>
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
