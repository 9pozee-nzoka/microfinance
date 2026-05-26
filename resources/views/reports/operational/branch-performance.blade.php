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
