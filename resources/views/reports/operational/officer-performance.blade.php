@extends('layouts.app')
@section('title', 'Officer Performance - Reports')
@section('page-title', 'Officer Performance')

@section('content')
@php
$officerSlot = '<div><label class="form-label">Officer</label><select name="officer" class="form-control"><option value="">All Officers</option>';
foreach($staffList as $staff) {
    $selected = (string) $selectedOfficer === (string) $staff->id ? 'selected' : '';
    $officerSlot .= '<option value="'.$staff->id.'" '.$selected.'>'.$staff->name.($staff->designation ? ' — '.$staff->designation : '').'</option>';
}
$officerSlot .= '</select></div>';
@endphp

<div class="page-actions">
    <a href="{{ route('reports.categories.show', 'operational') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Operational Reports</a>
    <span style="font-size:12px; color:var(--text-secondary);">{{ $dateFrom->format('d M Y') }} — {{ $dateTo->format('d M Y') }}</span>
</div>

@include('reports._partials.filters', [
    'action' => $reportAction ?? route('reports.operational.officers'),
    'showDate' => true,
    'slot' => $officerSlot,
])

<div style="font-size:13px; color:var(--text-secondary); margin-bottom:20px;">
    Period: <strong>{{ $dateFrom->format('d M Y') }}</strong> — <strong>{{ $dateTo->format('d M Y') }}</strong>
    @if($selectedOfficer)
        @php $officerName = $staffList->firstWhere('id', $selectedOfficer)?->name ?? 'Selected Officer'; @endphp
        &nbsp;·&nbsp; Officer: <strong>{{ $officerName }}</strong>
    @endif
</div>

{{-- Summary Metrics --}}
<div class="grid-4" style="margin-bottom:20px;">
    <div class="card card-primary">
        <div class="metric-label" style="margin-bottom:4px;">Total Officers</div>
        <div class="metric-value" style="font-size:22px;">{{ number_format($summary['total_officers']) }}</div>
    </div>
    <div class="card card-secondary">
        <div class="metric-label" style="margin-bottom:4px;">Loans Created</div>
        <div class="metric-value" style="font-size:22px;">{{ number_format($summary['total_loans_created']) }}</div>
        <div class="metric-label">avg {{ number_format($summary['avg_loans_per_officer'], 1) }} / officer</div>
    </div>
    <div class="card card-success">
        <div class="metric-label" style="margin-bottom:4px;">Amount Disbursed</div>
        <div class="metric-value" style="font-size:22px;">KSH {{ number_format($summary['total_disbursed'], 0) }}</div>
    </div>
    <div class="card card-info">
        <div class="metric-label" style="margin-bottom:4px;">Collections</div>
        <div class="metric-value" style="font-size:22px;">KSH {{ number_format($summary['total_collections_amount'], 0) }}</div>
        <div class="metric-label">{{ number_format($summary['total_collections_count']) }} payments · avg KSH {{ number_format($summary['avg_collections_per_officer'], 2) }}</div>
    </div>
</div>

@php
$collectionRate = $summary['total_disbursed'] > 0
    ? round(($summary['total_collections_amount'] / $summary['total_disbursed']) * 100, 1)
    : 0;
@endphp
<div class="grid-3" style="margin-bottom:20px; gap:20px;">
    <div class="card card-primary">
        <div class="metric-label" style="margin-bottom:4px;">Active Portfolio</div>
        <div class="metric-value" style="font-size:24px;">KSH {{ number_format($summary['total_active_portfolio'], 0) }}</div>
    </div>
    <div class="card card-danger">
        <div class="metric-label" style="margin-bottom:4px;">Active Arrears</div>
        <div class="metric-value" style="font-size:24px;">KSH {{ number_format($summary['total_active_arrears'], 0) }}</div>
    </div>
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
                <div class="circle-label">PAR</div>
            </div>
        </div>
        <div>
            <div class="metric-label" style="margin-bottom:4px;">Collection Rate</div>
            <div class="metric-value" style="font-size:24px;">{{ $collectionRate }}%</div>
            <div class="metric-label">of period disbursed</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <div class="table-wrap">
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
</div>
@endsection
