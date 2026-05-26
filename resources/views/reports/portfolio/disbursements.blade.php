@extends('layouts.app')
@section('title', 'Loan Disbursements - Reports')
@section('page-title', 'Loan Disbursements')

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Reports</a>
    <span style="font-size:12px; color:var(--text-secondary);">{{ $dateFrom->format('d M Y') }} — {{ $dateTo->format('d M Y') }}</span>
</div>

<div class="grid-3" style="margin-bottom:20px; gap:20px;">
    <div class="card" style="border-left:4px solid var(--primary);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Loans Disbursed</div>
        <div style="font-size:28px; font-weight:700; color:var(--primary);">{{ number_format($totals->count) }}</div>
    </div>
    <div class="card" style="border-left:4px solid var(--success);">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Total Principal</div>
        <div style="font-size:22px; font-weight:700; color:var(--success);">KSH {{ number_format($totals->total_principal, 0) }}</div>
    </div>
    <div class="card" style="border-left:4px solid #9C27B0;">
        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:4px;">Total Repayable</div>
        <div style="font-size:22px; font-weight:700; color:#9C27B0;">KSH {{ number_format($totals->total_repayable, 0) }}</div>
    </div>
</div>

{{-- By Method --}}
<div class="card" style="margin-bottom:20px;">
    <div style="font-size:13px; font-weight:600; margin-bottom:14px;">By Disbursement Method</div>
    <div style="display:flex; gap:20px; flex-wrap:wrap;">
        @foreach($byMethod as $m)
        @php
            $mc = match($m->disbursement_method) { 'mpesa' => ['#E8F5E9','#2E7D32','fa-mobile-alt'], 'bank_transfer' => ['#E3F2FD','#1565C0','fa-university'], default => ['#FFF3E0','#E65100','fa-money-bill'] };
        @endphp
        <div style="flex:1; min-width:160px; background:{{ $mc[0] }}; border-radius:10px; padding:16px 20px; border:1px solid {{ $mc[1] }}30;">
            <div style="font-size:12px; color:{{ $mc[1] }}; font-weight:600; margin-bottom:6px;">
                <i class="fas {{ $mc[2] }}"></i> {{ ucfirst(str_replace('_',' ',$m->disbursement_method ?? 'N/A')) }}
            </div>
            <div style="font-size:20px; font-weight:700; color:{{ $mc[1] }};">KSH {{ number_format($m->total, 0) }}</div>
            <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">{{ $m->cnt }} loans</div>
        </div>
        @endforeach
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('reports.portfolio.disbursements') }}">
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
            <div>
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->toDateString()) }}" class="filter-select">
            </div>
            <div>
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to', $dateTo->toDateString()) }}" class="filter-select">
            </div>
            <div>
                <label class="form-label">Branch</label>
                <select name="branch" class="filter-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Product</label>
                <select name="product" class="filter-select">
                    <option value="">All Products</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ request('product') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex; gap:8px; padding-bottom:1px;">
                <button type="submit" class="btn btn-primary" style="height:38px; padding:0 18px;"><i class="fas fa-search"></i> Filter</button>
                <a href="{{ route('reports.portfolio.disbursements') }}" class="btn btn-outline" style="height:38px; padding:0 14px;"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header" style="margin-bottom:14px;">
        <span style="font-size:14px; font-weight:600;">Disbursements — {{ $loans->total() }} records</span>
    </div>
    <div class="table-wrap">
        <div class="table-wrap">
        <table class="data-table" style="min-width:1000px;">
            <thead>
                <tr><th>#</th><th>Loan No.</th><th>Customer</th><th>Product</th><th>Principal</th><th>Total Repayable</th><th>Term</th><th>Method</th><th>Disbursed</th><th>Officer</th></tr>
            </thead>
            <tbody>
                @forelse($loans as $i => $loan)
                <tr>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ ($loans->currentPage()-1)*$loans->perPage()+$i+1 }}</td>
                    <td><a href="{{ route('loans.show', $loan) }}" style="font-family:monospace; font-size:12px; color:var(--primary); font-weight:600;">{{ $loan->loan_number }}</a></td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $loan->customer->full_name }}</div>
                        <div style="font-size:11px; color:var(--text-secondary);">{{ $loan->customer->phone_number }}</div>
                    </td>
                    <td style="font-size:12px;">{{ $loan->product->name ?? '—' }}</td>
                    <td style="font-weight:700; color:var(--success);">KSH {{ number_format($loan->principal_amount, 0) }}</td>
                    <td>KSH {{ number_format($loan->total_repayable, 0) }}</td>
                    <td>{{ $loan->term_weeks }}w</td>
                    <td><span class="badge badge-primary">{{ ucfirst(str_replace('_',' ',$loan->disbursement_method ?? 'N/A')) }}</span></td>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $loan->disbursement_date?->format('d M Y') ?? '—' }}</td>
                    <td style="font-size:12px;">{{ $loan->relationshipOfficer->name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center; padding:50px; color:var(--text-secondary);">No disbursements in this period</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($loans->hasPages())
    <div class="pagination-wrap">
        <span style="font-size:12px; color:var(--text-secondary);">Showing {{ $loans->firstItem() }}–{{ $loans->lastItem() }} of {{ $loans->total() }}</span>
        {{ $loans->links() }}
    </div>
    @endif
</div>
@endsection
