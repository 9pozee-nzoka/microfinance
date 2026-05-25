@extends('layouts.app')
@section('title', 'Officer Performance - Reports')
@section('page-title', 'Officer Performance')

@section('content')
<div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Reports</a>
    <form method="GET" action="{{ route('reports.operational.officers') }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <div>
            <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:3px;">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->toDateString()) }}" class="filter-select" style="width:150px;">
        </div>
        <div>
            <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:3px;">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to', $dateTo->toDateString()) }}" class="filter-select" style="width:150px;">
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
    <div style="overflow-x:auto;">
        <table class="data-table" style="min-width:900px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Officer</th>
                    <th>Designation</th>
                    <th>Loans Created</th>
                    <th>Amount Disbursed</th>
                    <th>Collections (Count)</th>
                    <th>Collections (Amount)</th>
                    <th>Active Portfolio</th>
                    <th>Active Arrears</th>
                </tr>
            </thead>
            <tbody>
                @forelse($officers as $i => $officer)
                @php $portfolio = $activePortfolio[$officer->id] ?? null; @endphp
                <tr>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $i + 1 }}</td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $officer->name }}</div>
                    </td>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $officer->designation ?? '—' }}</td>
                    <td style="font-weight:700; color:var(--primary); font-size:16px;">{{ number_format($officer->loans_created) }}</td>
                    <td style="font-weight:600; color:var(--success);">KSH {{ number_format($officer->total_disbursed ?? 0, 0) }}</td>
                    <td>{{ number_format($officer->collections_count) }}</td>
                    <td style="font-weight:600; color:var(--primary);">KSH {{ number_format($officer->collections_amount ?? 0, 0) }}</td>
                    <td>
                        @if($portfolio)
                            <div style="font-weight:600;">KSH {{ number_format($portfolio->olb, 0) }}</div>
                            <div style="font-size:11px; color:var(--text-secondary);">{{ $portfolio->active_loans }} loans</div>
                        @else
                            <span style="color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                    <td style="color:{{ ($portfolio->arrears ?? 0) > 0 ? 'var(--danger)' : 'var(--text-secondary)' }}; font-weight:{{ ($portfolio->arrears ?? 0) > 0 ? '700' : '400' }};">
                        KSH {{ number_format($portfolio->arrears ?? 0, 0) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center; padding:50px; color:var(--text-secondary);">No officers found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
