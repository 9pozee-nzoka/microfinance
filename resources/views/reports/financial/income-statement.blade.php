@extends('layouts.app')
@section('title', 'Income Statement - Reports')
@section('page-title', 'Income Statement')

@section('content')
<div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Reports</a>
    <form method="GET" action="{{ route('reports.financial.income') }}" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <div>
            <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:3px;">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->toDateString()) }}" class="filter-select" style="width:150px;">
        </div>
        <div>
            <label style="font-size:11px; color:var(--text-secondary); display:block; margin-bottom:3px;">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to', $dateTo->toDateString()) }}" class="filter-select" style="width:150px;">
        </div>
        <div style="padding-top:18px;">
            <button type="submit" class="btn btn-primary" style="height:38px; padding:0 16px;"><i class="fas fa-search"></i> Apply</button>
        </div>
    </form>
</div>

@php
    $totalIncome = $interestIncome + $processingFees + $insuranceFees + $penaltyIncome;
@endphp

<div style="font-size:13px; color:var(--text-secondary); margin-bottom:20px;">
    Period: <strong>{{ $dateFrom->format('d M Y') }}</strong> — <strong>{{ $dateTo->format('d M Y') }}</strong>
</div>

<div class="grid-2" style="gap:20px; margin-bottom:20px;">
    {{-- Income Breakdown --}}
    <div class="card">
        <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid var(--border);">
            <i class="fas fa-chart-line" style="color:var(--success);"></i> Income Summary
        </div>

        @php
            $lines = [
                ['Interest Income',    $interestIncome,   'var(--success)', 'fa-percentage'],
                ['Processing Fees',    $processingFees,   'var(--primary)', 'fa-cog'],
                ['Insurance Fees',     $insuranceFees,    '#9C27B0',        'fa-shield-alt'],
                ['Penalty Income',     $penaltyIncome,    'var(--danger)',  'fa-exclamation'],
            ];
        @endphp

        @foreach($lines as [$label, $amount, $color, $icon])
        @php $pct = $totalIncome > 0 ? round(($amount / $totalIncome) * 100, 1) : 0; @endphp
        <div style="margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <div style="display:flex; align-items:center; gap:8px; font-size:13px;">
                    <i class="fas {{ $icon }}" style="color:{{ $color }}; width:16px;"></i>
                    {{ $label }}
                </div>
                <div style="text-align:right;">
                    <span style="font-weight:700; color:{{ $color }};">KSH {{ number_format($amount, 0) }}</span>
                    <span style="font-size:11px; color:var(--text-secondary); margin-left:6px;">{{ $pct }}%</span>
                </div>
            </div>
            <div style="height:8px; background:#E8ECF1; border-radius:4px; overflow:hidden;">
                <div style="width:{{ $pct }}%; height:100%; background:{{ $color }}; border-radius:4px;"></div>
            </div>
        </div>
        @endforeach

        <div style="border-top:2px solid var(--border); padding-top:14px; margin-top:4px; display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:15px; font-weight:700;">Total Income</span>
            <span style="font-size:22px; font-weight:800; color:var(--success);">KSH {{ number_format($totalIncome, 0) }}</span>
        </div>
    </div>

    {{-- Fund Flow --}}
    <div class="card">
        <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid var(--border);">
            <i class="fas fa-exchange-alt" style="color:var(--primary);"></i> Fund Flow
        </div>
        <div style="display:flex; flex-direction:column; gap:16px;">
            <div style="background:#FFEBEE; border-radius:10px; padding:16px 20px; border:1px solid #FFCDD2;">
                <div style="font-size:12px; color:var(--danger); font-weight:600; margin-bottom:4px;"><i class="fas fa-arrow-up"></i> FUNDS OUT — Disbursements</div>
                <div style="font-size:24px; font-weight:800; color:var(--danger);">KSH {{ number_format($totalDisbursed, 0) }}</div>
            </div>
            <div style="background:#E8F5E9; border-radius:10px; padding:16px 20px; border:1px solid #A5D6A7;">
                <div style="font-size:12px; color:var(--success); font-weight:600; margin-bottom:4px;"><i class="fas fa-arrow-down"></i> FUNDS IN — Principal Collected</div>
                <div style="font-size:24px; font-weight:800; color:var(--success);">KSH {{ number_format($principalCollected, 0) }}</div>
            </div>
            @php $net = $principalCollected - $totalDisbursed; @endphp
            <div style="background:{{ $net >= 0 ? '#E8F5E9' : '#FFEBEE' }}; border-radius:10px; padding:16px 20px; border:1px solid {{ $net >= 0 ? '#A5D6A7' : '#FFCDD2' }};">
                <div style="font-size:12px; color:{{ $net >= 0 ? 'var(--success)' : 'var(--danger)' }}; font-weight:600; margin-bottom:4px;">NET FUND FLOW</div>
                <div style="font-size:24px; font-weight:800; color:{{ $net >= 0 ? 'var(--success)' : 'var(--danger)' }};">
                    {{ $net >= 0 ? '+' : '' }}KSH {{ number_format($net, 0) }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 6-Month Trend --}}
<div class="card">
    <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:16px;">
        <i class="fas fa-chart-bar" style="color:var(--primary);"></i> 6-Month Income Trend
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr><th>Month</th><th>Interest Income</th><th>Fees</th><th>Penalties</th><th>Total</th></tr>
            </thead>
            <tbody>
                @foreach($trend as $t)
                @php $rowTotal = $t['interest'] + $t['fees'] + $t['penalty']; @endphp
                <tr>
                    <td style="font-weight:600;">{{ $t['month'] }}</td>
                    <td style="color:var(--success);">KSH {{ number_format($t['interest'], 0) }}</td>
                    <td style="color:var(--primary);">KSH {{ number_format($t['fees'], 0) }}</td>
                    <td style="color:var(--danger);">KSH {{ number_format($t['penalty'], 0) }}</td>
                    <td style="font-weight:700;">KSH {{ number_format($rowTotal, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
