@extends('layouts.app')
@section('title', 'Credit Score Distribution - Reports')
@section('page-title', 'Credit Score Distribution')

@section('content')
<div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
    <a href="{{ route('reports.index') }}" class="btn btn-outline" style="font-size:13px;"><i class="fas fa-arrow-left"></i> Reports</a>
    <span style="font-size:12px; color:var(--text-secondary);">As at {{ now()->format('d M Y, h:i A') }}</span>
</div>

{{-- Overview --}}
<div class="grid-3" style="margin-bottom:20px; gap:20px;">
    <div class="card" style="border-left:4px solid var(--primary); text-align:center; padding:20px;">
        <div style="font-size:32px; font-weight:800; color:var(--primary);">{{ number_format($total) }}</div>
        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Total Customers</div>
    </div>
    <div class="card" style="border-left:4px solid var(--success); text-align:center; padding:20px;">
        <div style="font-size:32px; font-weight:800; color:var(--success);">{{ number_format(round($avgScore)) }}</div>
        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Average Credit Score</div>
    </div>
    <div class="card" style="border-left:4px solid var(--warning); text-align:center; padding:20px;">
        @php $scored = $bands->sum('count'); @endphp
        <div style="font-size:32px; font-weight:800; color:var(--warning);">{{ $total > 0 ? round(($scored / $total) * 100) : 0 }}%</div>
        <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">Customers with Score</div>
    </div>
</div>

{{-- Score Bands --}}
<div class="card" style="margin-bottom:20px;">
    <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:20px;">Score Band Distribution</div>
    @foreach($bands as $band)
    @php $pct = $total > 0 ? round(($band['count'] / $total) * 100, 1) : 0; @endphp
    <div style="margin-bottom:18px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:12px; height:12px; border-radius:50%; background:{{ $band['color'] }};"></div>
                <span style="font-size:14px; font-weight:600;">{{ $band['label'] }}</span>
            </div>
            <div style="text-align:right;">
                <span style="font-size:16px; font-weight:700; color:{{ $band['color'] }};">{{ number_format($band['count']) }}</span>
                <span style="font-size:12px; color:var(--text-secondary); margin-left:6px;">customers ({{ $pct }}%)</span>
                <span style="font-size:12px; color:var(--text-secondary); margin-left:12px;">Avg Limit: KSH {{ number_format($band['avg_limit'], 0) }}</span>
            </div>
        </div>
        <div style="height:12px; background:#E8ECF1; border-radius:6px; overflow:hidden;">
            <div style="width:{{ $pct }}%; height:100%; background:{{ $band['color'] }}; border-radius:6px; transition:width 0.5s;"></div>
        </div>
    </div>
    @endforeach
</div>

{{-- Top Customers --}}
<div class="card">
    <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:16px;">
        <i class="fas fa-trophy" style="color:#FF9800;"></i> Top 20 by Credit Score
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>Customer</th><th>Phone</th><th>Branch</th><th>Credit Score</th><th>Credit Limit</th><th>Savings</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($topCustomers as $i => $c)
                @php
                    $scoreColor = match(true) {
                        $c->credit_score >= 800 => '#4CAF50', $c->credit_score >= 650 => '#8BC34A',
                        $c->credit_score >= 500 => '#FF9800', $c->credit_score >= 350 => '#FF5722', default => '#F44336'
                    };
                    $medal = match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '' };
                @endphp
                <tr>
                    <td style="font-size:14px;">{{ $medal ?: ($i + 1) }}</td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $c->full_name }}</div>
                        <div style="font-size:11px; color:var(--text-secondary); font-family:monospace;">{{ $c->customer_number }}</div>
                    </td>
                    <td style="font-size:12px;">{{ $c->phone_number }}</td>
                    <td style="font-size:12px;">{{ $c->branch->name ?? '—' }}</td>
                    <td>
                        <span style="font-size:18px; font-weight:800; color:{{ $scoreColor }};">{{ $c->credit_score }}</span>
                        <div style="width:60px; height:4px; background:#E8ECF1; border-radius:2px; margin-top:4px;">
                            <div style="width:{{ min(100, ($c->credit_score/1000)*100) }}%; height:100%; background:{{ $scoreColor }}; border-radius:2px;"></div>
                        </div>
                    </td>
                    <td style="font-weight:600; color:var(--primary);">KSH {{ number_format($c->credit_limit, 0) }}</td>
                    <td style="color:var(--success);">KSH {{ number_format($c->savings_balance, 0) }}</td>
                    <td><span class="status {{ $c->status === 'active' ? 'status-active' : 'status-pending' }}">{{ ucfirst($c->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
