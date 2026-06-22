@extends('layouts.app')
@section('title', 'Loans Due - Reports')
@section('page-title', 'Loans Due')

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.categories.show', 'operational') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Operational Reports
    </a>
    <span style="font-size:12px; color:var(--text-secondary);">Installments due in selected period</span>
</div>

<div class="grid-2" style="margin-bottom:20px;">
    <div class="card" style="border-left:4px solid var(--primary); text-align:center; padding:20px;">
        <div style="font-size:26px; font-weight:700; color:var(--primary);">{{ number_format($totalDue->count) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Installments Due</div>
    </div>
    <div class="card" style="border-left:4px solid var(--success); text-align:center; padding:20px;">
        <div style="font-size:22px; font-weight:700; color:var(--success);">KSH {{ number_format($totalDue->amount, 0) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Total Amount Due</div>
    </div>
</div>

@include('reports._partials.filters', [
    'action' => $reportAction ?? route('reports.operational.loans-due'),
    'showDate' => true,
    'showBranch' => true,
    'branches' => $branches,
])

<div class="card">
    <div class="card-header" style="margin-bottom:14px;">
        <span style="font-size:14px; font-weight:600;">Loans Due — {{ $schedules->total() }} records</span>
    </div>
    <div class="table-wrap">
        <table class="data-table" style="min-width:1000px;">
            <thead>
                <tr><th>#</th><th>Due Date</th><th>Customer</th><th>Phone</th><th>Loan No.</th><th>Installment</th><th>Amount Due</th><th>Branch</th></tr>
            </thead>
            <tbody>
                @forelse($schedules as $i => $s)
                <tr>
                    <td style="color:var(--text-secondary); font-size:12px;">{{ ($schedules->currentPage()-1)*$schedules->perPage()+$i+1 }}</td>
                    <td>{{ $s->due_date?->format('d M Y') }}</td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $s->loan?->customer?->full_name }}</div>
                    </td>
                    <td>{{ $s->loan?->customer?->phone_number }}</td>
                    <td><a href="{{ route('loans.show', $s->loan) }}" style="font-family:monospace; font-size:12px; color:var(--primary); font-weight:600;">{{ $s->loan?->loan_number }}</a></td>
                    <td>{{ $s->installment_number }}</td>
                    <td style="font-weight:700; color:var(--primary);">KSH {{ number_format(max(0, $s->total_amount - $s->total_paid), 0) }}</td>
                    <td>{{ $s->loan?->branch?->name }}</td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center; padding:50px;">No dues found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($schedules->hasPages())
    <div class="pagination-wrap">
        <span style="font-size:12px; color:var(--text-secondary);">Showing {{ $schedules->firstItem() }}–{{ $schedules->lastItem() }} of {{ $schedules->total() }}</span>
        {{ $schedules->links() }}
    </div>
    @endif
</div>
@endsection
