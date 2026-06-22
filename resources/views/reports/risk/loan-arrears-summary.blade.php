@extends('layouts.app')
@section('title', 'Loan Arrears Summary - Reports')
@section('page-title', 'Loan Arrears Summary')

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.categories.show', 'risk') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Risk Reports
    </a>
    <span style="font-size:12px; color:var(--text-secondary);">As at {{ now()->format('d M Y, h:i A') }}</span>
</div>

{{-- Totals --}}
<div class="grid-3" style="margin-bottom:20px;">
    <div class="card" style="border-left:4px solid var(--danger); text-align:center; padding:20px;">
        <div style="font-size:26px; font-weight:700; color:var(--danger);">{{ number_format($totals->count) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Loans in Arrears</div>
    </div>
    <div class="card" style="border-left:4px solid var(--primary); text-align:center; padding:20px;">
        <div style="font-size:22px; font-weight:700; color:var(--primary);">KSH {{ number_format($totals->olb, 0) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Outstanding Balance</div>
    </div>
    <div class="card" style="border-left:4px solid var(--warning); text-align:center; padding:20px;">
        <div style="font-size:22px; font-weight:700; color:var(--warning);">KSH {{ number_format($totals->arrears, 0) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Total Arrears</div>
    </div>
</div>

{{-- Filters --}}
@include('reports._partials.filters', [
    'action' => $reportAction ?? route('reports.risk.loan-arrears-summary'),
    'showDate' => true,
    'showBranch' => true,
    'showProduct' => true,
    'showOfficer' => true,
    'branches' => $branches,
    'products' => $products,
    'officers' => \App\Models\User::where('status','active')->whereDoesntHave('roles', fn($q)=>$q->where('name','customer'))->orderBy('name')->get(['id','name']),
])

{{-- By Branch --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-header" style="margin-bottom:14px;"><span class="card-title">By Branch</span></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Branch</th><th>No. of Loans</th><th>Outstanding Balance</th><th>Arrears</th></tr></thead>
            <tbody>
                @forelse($byBranch as $row)
                <tr>
                    <td>{{ $row->branch }}</td>
                    <td>{{ number_format($row->count) }}</td>
                    <td style="font-weight:600;">KSH {{ number_format($row->olb, 0) }}</td>
                    <td style="color:var(--danger);">KSH {{ number_format($row->arrears, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; padding:30px;">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- By Officer --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-header" style="margin-bottom:14px;"><span class="card-title">By Officer</span></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Officer</th><th>No. of Loans</th><th>Outstanding Balance</th><th>Arrears</th></tr></thead>
            <tbody>
                @forelse($byOfficer as $row)
                <tr>
                    <td>{{ $row->officer }}</td>
                    <td>{{ number_format($row->count) }}</td>
                    <td style="font-weight:600;">KSH {{ number_format($row->olb, 0) }}</td>
                    <td style="color:var(--danger);">KSH {{ number_format($row->arrears, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; padding:30px;">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- By Risk --}}
<div class="card">
    <div class="card-header" style="margin-bottom:14px;"><span class="card-title">By Risk Category</span></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Risk Category</th><th>No. of Loans</th><th>Outstanding Balance</th><th>Arrears</th></tr></thead>
            <tbody>
                @forelse($byRisk as $row)
                <tr>
                    <td>{{ ucfirst($row->risk_category) }}</td>
                    <td>{{ number_format($row->count) }}</td>
                    <td style="font-weight:600;">KSH {{ number_format($row->olb, 0) }}</td>
                    <td style="color:var(--danger);">KSH {{ number_format($row->arrears, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; padding:30px;">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
