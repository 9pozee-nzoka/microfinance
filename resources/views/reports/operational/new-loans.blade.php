@extends('layouts.app')
@section('title', 'New Loans - Reports')
@section('page-title', 'New Loans')

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.categories.show', 'operational') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Operational Reports
    </a>
    <span style="font-size:12px; color:var(--text-secondary);">Loan applications created in selected period</span>
</div>

<div class="grid-2" style="margin-bottom:20px;">
    <div class="card" style="border-left:4px solid var(--primary); text-align:center; padding:20px;">
        <div style="font-size:26px; font-weight:700; color:var(--primary);">{{ number_format($totals->count) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">New Loans</div>
    </div>
    <div class="card" style="border-left:4px solid var(--success); text-align:center; padding:20px;">
        <div style="font-size:22px; font-weight:700; color:var(--success);">KSH {{ number_format($totals->total_principal, 0) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Total Principal</div>
    </div>
</div>

@include('reports._partials.filters', [
    'action' => $reportAction ?? route('reports.operational.new-loans'),
    'showDate' => true,
    'showBranch' => true,
    'showProduct' => true,
    'showStatus' => true,
    'showSearch' => true,
    'branches' => $branches,
    'products' => $products,
    'statusOptions' => ['pending' => 'Pending', 'under_review' => 'Under Review', 'partially_approved' => 'Partially Approved', 'approved' => 'Approved', 'rejected' => 'Rejected', 'disbursed' => 'Disbursed', 'active' => 'Active', 'completed' => 'Completed'],
])

<div class="card">
    <div class="card-header" style="margin-bottom:14px;">
        <span style="font-size:14px; font-weight:600;">New Loans — {{ $loans->total() }} records</span>
    </div>
    <div class="table-wrap">
        <table class="data-table" style="min-width:1100px;">
            <thead>
                <tr><th>#</th><th>Loan No.</th><th>Customer</th><th>Phone</th><th>Principal</th><th>Product</th><th>Status</th><th>Applied Date</th><th>Branch</th><th>Officer</th></tr>
            </thead>
            <tbody>
                @forelse($loans as $i => $loan)
                <tr>
                    <td style="color:var(--text-secondary); font-size:12px;">{{ ($loans->currentPage()-1)*$loans->perPage()+$i+1 }}</td>
                    <td><a href="{{ route('loans.show', $loan) }}" style="font-family:monospace; font-size:12px; color:var(--primary); font-weight:600;">{{ $loan->loan_number }}</a></td>
                    <td><div style="font-weight:600; font-size:13px;">{{ $loan->customer?->full_name }}</div></td>
                    <td>{{ $loan->customer?->phone_number }}</td>
                    <td style="font-weight:600;">KSH {{ number_format($loan->principal_amount, 0) }}</td>
                    <td>{{ $loan->product?->name }}</td>
                    <td><span class="badge">{{ ucwords(str_replace('_', ' ', $loan->status)) }}</span></td>
                    <td>{{ $loan->created_at?->format('d M Y') }}</td>
                    <td>{{ $loan->branch?->name }}</td>
                    <td>{{ $loan->relationshipOfficer?->name }}</td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center; padding:50px;">No new loans found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($loans->hasPages())
    <div class="pagination-wrap">
        <span style="font-size:12px; color:var(--text-secondary);">Showing {{ $loans->firstItem() }}–{{ $loans->lastItem() }} of {{ $loans->total() }}</span>
        {{ $loans->links() }}
    </div>
    @endif
</div>
@endsection
