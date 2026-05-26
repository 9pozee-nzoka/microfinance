@extends('portal.layouts.app')

@section('title', 'My Loans')
@section('page-title', 'My Loans')

@section('content')

<div class="card">
    <div class="card-title" style="margin-bottom: 16px;">All Loan Applications</div>

    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Loan No.</th>
                <th>Product</th>
                <th>Principal</th>
                <th>Total Repayable</th>
                <th>Outstanding</th>
                <th>Progress</th>
                <th>Status</th>
                <th>Applied</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans as $loan)
            @php
                $progress = $loan->total_repayable > 0
                    ? min(100, ($loan->total_paid / $loan->total_repayable) * 100)
                    : 0;

                $badgeClass = match($loan->status) {
                    'active', 'disbursed' => 'badge-success',
                    'completed'           => 'badge-info',
                    'pending', 'under_review', 'approved' => 'badge-warning',
                    'rejected', 'defaulted', 'written_off' => 'badge-danger',
                    default => 'badge-info',
                };
            @endphp
            <tr>
                <td style="font-family: monospace; font-size: 12px; font-weight: 700;">{{ $loan->loan_number }}</td>
                <td>{{ $loan->product->name ?? '—' }}</td>
                <td style="font-weight: 600;">KSH {{ number_format($loan->principal_amount, 0) }}</td>
                <td>KSH {{ number_format($loan->total_repayable, 0) }}</td>
                <td style="font-weight: 700; color: var(--primary);">KSH {{ number_format($loan->outstanding_balance, 0) }}</td>
                <td style="min-width: 120px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div class="progress-bar-wrap" style="flex: 1;">
                            <div class="progress-bar-fill" style="width: {{ $progress }}%;"></div>
                        </div>
                        <span style="font-size: 11px; color: var(--text-secondary); white-space: nowrap;">{{ round($progress) }}%</span>
                    </div>
                </td>
                <td><span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $loan->status)) }}</span></td>
                <td style="font-size: 12px; color: var(--text-secondary);">{{ $loan->application_date?->format('d M Y') ?? $loan->created_at->format('d M Y') }}</td>
                <td>
                    <div style="display: flex; gap: 6px;">
                        <a href="{{ route('portal.loan.detail', $loan) }}" class="btn btn-outline btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if(in_array($loan->status, ['disbursed', 'active']))
                        <a href="{{ route('portal.loan.pay', $loan) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-money-bill-wave"></i> Pay
                        </a>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center; padding: 50px; color: var(--text-secondary);">
                    <i class="fas fa-hand-holding-usd" style="font-size: 36px; opacity: 0.3; display: block; margin-bottom: 12px;"></i>
                    No loans found. Contact your relationship officer to apply.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    @if($loans->hasPages())
    <div style="margin-top: 16px; display: flex; justify-content: center;">
        {{ $loans->links() }}
    </div>
    @endif
</div>

@endsection
