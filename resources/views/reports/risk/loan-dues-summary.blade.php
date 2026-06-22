@extends('layouts.app')
@section('title', 'Loan Dues Summary - Reports')
@section('page-title', 'Loan Dues Summary')

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.categories.show', 'risk') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Risk Reports
    </a>
    <span style="font-size:12px; color:var(--text-secondary);">Expected collections by due date</span>
</div>

{{-- Totals --}}
<div class="grid-2" style="margin-bottom:20px;">
    <div class="card" style="border-left:4px solid var(--primary); text-align:center; padding:20px;">
        <div style="font-size:26px; font-weight:700; color:var(--primary);">{{ number_format($totalDue->count) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Total Installments Due</div>
    </div>
    <div class="card" style="border-left:4px solid var(--success); text-align:center; padding:20px;">
        <div style="font-size:22px; font-weight:700; color:var(--success);">KSH {{ number_format($totalDue->amount, 0) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Total Amount Due</div>
    </div>
</div>

{{-- Filters --}}
@include('reports._partials.filters', [
    'action' => $reportAction ?? route('reports.risk.loan-dues-summary'),
    'showDate' => true,
    'showBranch' => true,
    'branches' => $branches,
])

{{-- Table --}}
<div class="card">
    <div class="card-header" style="margin-bottom:14px;"><span class="card-title">Dues by Date</span></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>#</th><th>Due Date</th><th>No. of Installments</th><th>Amount Due</th></tr></thead>
            <tbody>
                @forelse($byDay as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->due_date?->format('d M Y') }}</td>
                    <td>{{ number_format($row->count) }}</td>
                    <td style="font-weight:700; color:var(--primary);">KSH {{ number_format($row->amount, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; padding:50px;">No dues found for selected period</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
