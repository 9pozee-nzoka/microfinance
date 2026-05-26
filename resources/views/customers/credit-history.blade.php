{{-- resources/views/customers/credit-history.blade.php --}}
@extends('layouts.app')

@section('title', 'Credit Score History - Mweela Cash Capital')
@section('page-title', 'Credit Score History')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div class="flash-success">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">
        <span class="card-title">Credit Score History</span>
        <button class="btn btn-outline" style="font-size: 12px;">
            <i class="fas fa-download"></i> Export Report
        </button>
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('customers.credit-history') }}" style="margin-bottom:20px;">
        <div class="filter-row">
            <div style="flex:1 1 200px;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or phone">
                </div>
            </div>
            <select name="rating" class="filter-select" style="flex:1 1 160px;">
                <option value="">All Score Ranges</option>
                <option value="excellent" {{ request('rating') === 'excellent' ? 'selected' : '' }}>Excellent (800+)</option>
                <option value="good"      {{ request('rating') === 'good'      ? 'selected' : '' }}>Good (650–799)</option>
                <option value="fair"      {{ request('rating') === 'fair'      ? 'selected' : '' }}>Fair (500–649)</option>
                <option value="poor"      {{ request('rating') === 'poor'      ? 'selected' : '' }}>Poor (350–499)</option>
                <option value="bad"       {{ request('rating') === 'bad'       ? 'selected' : '' }}>Bad (&lt;350)</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> <span class="btn-label">Search</span></button>
            <a href="{{ route('customers.credit-history') }}" class="btn btn-outline"><i class="fas fa-undo"></i></a>
        </div>
    </form>

    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Current Score</th>
                <th>Rating</th>
                <th>Savings History</th>
                <th>Repayment History</th>
                <th>Income Stability</th>
                <th>Last Updated</th>
                <th>Trend</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers ?? [] as $index => $customer)
            @php
                $latestScore = $customer->latestCreditScore();
                $score = $latestScore?->total_score ?? $customer->credit_score ?? 0;
                $rating = match(true) {
                    $score >= 800 => ['excellent', '#4CAF50'],
                    $score >= 650 => ['good', '#8BC34A'],
                    $score >= 500 => ['fair', '#FF9800'],
                    $score >= 350 => ['poor', '#FF5722'],
                    default => ['bad', '#F44336']
                };
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="font-weight: 600;">{{ $customer->full_name }}</td>
                <td>{{ $customer->phone_number }}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; border: 3px solid {{ $rating[1] }}; display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 14px; font-weight: 700; color: {{ $rating[1] }};">{{ $score }}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="status" style="background: {{ $rating[1] }}20; color: {{ $rating[1] }};">
                        {{ ucfirst($rating[0]) }}
                    </span>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 60px; height: 6px; background: #E8ECF1; border-radius: 3px;">
                            <div style="width: {{ ($latestScore?->savings_history_score ?? 0) / 3 }}%; height: 100%; background: var(--primary); border-radius: 3px;"></div>
                        </div>
                        <span style="font-size: 11px;">{{ $latestScore?->savings_history_score ?? 0 }}/300</span>
                    </div>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 60px; height: 6px; background: #E8ECF1; border-radius: 3px;">
                            <div style="width: {{ ($latestScore?->repayment_history_score ?? 0) / 4 }}%; height: 100%; background: var(--success); border-radius: 3px;"></div>
                        </div>
                        <span style="font-size: 11px;">{{ $latestScore?->repayment_history_score ?? 0 }}/400</span>
                    </div>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 60px; height: 6px; background: #E8ECF1; border-radius: 3px;">
                            <div style="width: {{ ($latestScore?->income_stability_score ?? 0) / 1.5 }}%; height: 100%; background: var(--warning); border-radius: 3px;"></div>
                        </div>
                        <span style="font-size: 11px;">{{ $latestScore?->income_stability_score ?? 0 }}/150</span>
                    </div>
                </td>
                <td style="font-size: 12px; color: var(--text-secondary);">
                    {{ $latestScore?->calculated_at?->format('d-M-Y') ?? 'N/A' }}
                </td>
                <td>
                    @if($score >= 700)
                        <i class="fas fa-arrow-up" style="color: var(--success);"></i>
                    @elseif($score >= 500)
                        <i class="fas fa-minus" style="color: var(--warning);"></i>
                    @else
                        <i class="fas fa-arrow-down" style="color: var(--danger);"></i>
                    @endif
                </td>
                <td>
                    <form method="POST" action="{{ route('customers.recalculate-score', $customer) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="padding: 5px 12px; font-size: 12px;">
                            <i class="fas fa-sync"></i> Recalculate
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-chart-bar" style="font-size: 48px; margin-bottom: 15px; display: block; opacity: 0.3;"></i>
                    <p>No credit score data available</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection