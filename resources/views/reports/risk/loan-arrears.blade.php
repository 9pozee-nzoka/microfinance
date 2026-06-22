@extends('layouts.app')
@section('title', 'Loan Arrears - Reports')
@section('page-title', 'Loan Arrears')

@section('content')
<div class="page-actions">
    <a href="{{ route('reports.categories.show', 'risk') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Risk Reports
    </a>
    <span style="font-size:12px; color:var(--text-secondary);">As at {{ $asAt->format('d M Y, h:i A') }}</span>
</div>

{{-- Summary --}}
<div class="grid-4" style="margin-bottom:20px;">
    <div class="card" style="border-left:4px solid var(--danger); text-align:center; padding:20px;">
        <div style="font-size:26px; font-weight:700; color:var(--danger);">{{ number_format($totals->count) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Loans in Arrears</div>
    </div>
    <div class="card" style="border-left:4px solid var(--primary); text-align:center; padding:20px;">
        <div style="font-size:22px; font-weight:700; color:var(--primary);">KSH {{ number_format($totals->total_olb, 0) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Outstanding Balance</div>
    </div>
    <div class="card" style="border-left:4px solid var(--warning); text-align:center; padding:20px;">
        <div style="font-size:22px; font-weight:700; color:var(--warning);">KSH {{ number_format($totals->total_arrears, 0) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Total Arrears</div>
    </div>
    <div class="card" style="border-left:4px solid #9C27B0; text-align:center; padding:20px;">
        <div style="font-size:22px; font-weight:700; color:#9C27B0;">KSH {{ number_format($totals->total_principal, 0) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Total Principal</div>
    </div>
</div>

{{-- Filters --}}
@include('reports._partials.filters', [
    'action' => $reportAction ?? route('reports.risk.loan-arrears'),
    'showDate' => true,
    'showBranch' => true,
    'showProduct' => true,
    'showRisk' => true,
    'showSearch' => true,
    'branches' => $branches,
    'products' => $products,
])

{{-- Table --}}
<div class="card">
    <div class="card-header" style="margin-bottom:14px;">
        <span style="font-size:14px; font-weight:600;">Loan Arrears — {{ $loans->total() }} records</span>
    </div>
    <div class="table-wrap">
        <table class="data-table" style="min-width:1400px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Phone Number</th>
                    <th>Branch</th>
                    <th>Principal</th>
                    <th>Interest</th>
                    <th>OLB</th>
                    <th>Arrears</th>
                    <th>Overdue Days</th>
                    <th>Borrow Date</th>
                    <th>New/Repeat</th>
                    <th>Business Type</th>
                    <th>Guarantor</th>
                    <th>Guarantor Phone</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $i => $loan)
                @php $guarantor = $loan->guarantors->first(); @endphp
                <tr>
                    <td style="color:var(--text-secondary); font-size:12px;">{{ ($loans->currentPage()-1)*$loans->perPage()+$i+1 }}</td>
                    <td>
                        <div style="font-weight:600; font-size:13px;">{{ $loan->customer?->full_name }}</div>
                    </td>
                    <td>{{ $loan->customer?->phone_number }}</td>
                    <td>{{ $loan->branch?->name }}</td>
                    <td style="font-weight:600;">KSH {{ number_format($loan->principal_amount, 0) }}</td>
                    <td>KSH {{ number_format($loan->interest_amount, 0) }}</td>
                    <td style="font-weight:700; color:var(--primary);">KSH {{ number_format($loan->outstanding_balance, 0) }}</td>
                    <td style="color:var(--danger); font-weight:700;">KSH {{ number_format($loan->arrears_amount, 0) }}</td>
                    <td style="color:var(--danger);">{{ $loan->days_in_arrears }} days</td>
                    <td>{{ $loan->disbursement_date?->format('d/m/Y') }}</td>
                    <td>{{ $loan->customer?->loans?->count() > 1 ? 'Repeat Loan' : 'New Loan' }}</td>
                    <td>{{ $loan->customer?->business_type ?? '—' }}</td>
                    <td>{{ $guarantor?->name ?? '—' }}</td>
                    <td>{{ $guarantor?->phone_number ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="14" style="text-align:center; padding:50px; color:var(--text-secondary);">No loans in arrears</td></tr>
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
