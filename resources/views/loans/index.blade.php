@extends('layouts.app')

@section('title', 'All Loans - GetCash Capital')
@section('page-title', 'All Loans')

@section('content')
{{-- Summary Cards --}}
<div class="grid-4" style="margin-bottom: 20px;">
    <div class="card" style="border-left: 4px solid var(--primary);">
        <div class="metric-label">Total Loans</div>
        <div class="metric-value" style="font-size: 24px; color: var(--primary);">{{ $totalLoans ?? 0 }}</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--success);">
        <div class="metric-label">Active Loans</div>
        <div class="metric-value" style="font-size: 24px; color: var(--success);">{{ $activeLoansCount ?? 0 }}</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--warning);">
        <div class="metric-label">Pending Approval</div>
        <div class="metric-value" style="font-size: 24px; color: var(--warning);">{{ $pendingLoansCount ?? 0 }}</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--danger);">
        <div class="metric-label">In Arrears</div>
        <div class="metric-value" style="font-size: 24px; color: var(--danger);">{{ $arrearsLoansCount ?? 0 }}</div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('loans.index') }}">
        <div class="filter-row">
            <div style="flex: 1 1 220px;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Loan No. / Customer / Phone">
                </div>
            </div>
            <select name="status" class="filter-select" style="flex: 1 1 150px;">
                <option value="">All Status</option>
                @foreach(['pending'=>'Pending','under_review'=>'Under Review','partially_approved'=>'Partially Approved','approved'=>'Approved','disbursed'=>'Disbursed','active'=>'Active','completed'=>'Completed','defaulted'=>'Defaulted','rejected'=>'Rejected','written_off'=>'Written Off'] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="product" class="filter-select" style="flex: 1 1 150px;">
                <option value="">All Products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ request('product') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                @endforeach
            </select>
            <select name="branch" class="filter-select" style="flex: 1 1 140px;">
                <option value="">All Branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
            <select name="risk" class="filter-select" style="flex: 1 1 130px;">
                <option value="">All Risk</option>
                @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','watch'=>'Watch','default'=>'Default'] as $val => $label)
                    <option value="{{ $val }}" {{ request('risk') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-select" style="flex: 1 1 130px;">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   class="filter-select" style="flex: 1 1 130px;">
            <div style="display: flex; gap: 8px; flex-shrink: 0;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> <span class="btn-label">Filter</span></button>
                <a href="{{ route('loans.index') }}" class="btn btn-outline"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>
</div>

{{-- Loans Table --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Loan Portfolio</span>
        <div style="display: flex; gap: 10px;">
            <span class="badge badge-primary">{{ $loans->total() ?? 0 }} Records</span>
        </div>
    </div>

    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll" style="cursor: pointer;"></th>
                <th>#</th>
                <th>Loan No</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Amount (KSH)</th>
                <th>Product</th>
                <th>Interest</th>
                <th>Term</th>
                <th>Branch</th>
                <th>Officer</th>
                <th>Disbursed</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Risk</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans ?? [] as $index => $loan)
            @php
                $statusClass = match($loan->status) {
                    'pending' => 'status-pending',
                    'under_review' => 'status-pending',
                    'partially_approved' => 'status-partially-approved',
                    'approved' => 'status-active',
                    'disbursed' => 'status-disbursed',
                    'active' => 'status-active',
                    'completed' => 'status-active',
                    'rejected' => 'status-rejected',
                    'defaulted' => 'status-rejected',
                    'written_off' => 'status-rejected',
                    default => 'status-pending'
                };

                $riskClass = match($loan->risk_category) {
                    'low' => ['#4CAF50', '#E8F5E9'],
                    'medium' => ['#FF9800', '#FFF3E0'],
                    'high' => ['#FF5722', '#FBE9E7'],
                    'watch' => ['#9C27B0', '#F3E5F5'],
                    'default' => ['#F44336', '#FFEBEE'],
                    default => ['#757575', '#F5F5F5']
                };

                $progressPercent = $loan->total_repayable > 0 ? min(100, round(($loan->total_paid / $loan->total_repayable) * 100, 1)) : 0;
            @endphp
            <tr>
                <td><input type="checkbox" style="cursor: pointer;"></td>
                <td>{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-size: 11px; font-weight: 600;">{{ $loan->loan_number }}</td>
                <td style="font-weight: 600;">{{ $loan->customer->full_name ?? 'N/A' }}</td>
                <td style="font-size: 12px;">{{ $loan->customer->phone_number ?? 'N/A' }}</td>
                <td style="font-weight: 600; color: var(--primary);">{{ number_format($loan->principal_amount, 0) }}</td>
                <td style="font-size: 12px;">{{ $loan->product->name ?? 'N/A' }}</td>
                <td style="font-size: 12px;">{{ number_format($loan->interest_amount, 0) }}</td>
                <td style="font-size: 12px;">{{ $loan->term_weeks }} wks</td>
                <td style="font-size: 12px;">{{ $loan->branch->name ?? 'N/A' }}</td>
                <td style="font-size: 12px;">{{ $loan->relationshipOfficer->name ?? 'N/A' }}</td>
                <td style="font-size: 12px; color: var(--text-secondary);">
                    {{ $loan->disbursement_date ? $loan->disbursement_date->format('d-M-Y') : 'Not yet' }}
                </td>
                <td>
                    <span class="status {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $loan->status)) }}</span>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px; min-width: 120px;">
                        <div style="flex: 1; height: 8px; background: #E8ECF1; border-radius: 4px; overflow: hidden;">
                            <div style="width: {{ $progressPercent }}%; height: 100%; background: {{ $progressPercent >= 100 ? '#4CAF50' : ($progressPercent >= 50 ? '#00BCD4' : '#FF9800') }}; border-radius: 4px; transition: width 0.3s;"></div>
                        </div>
                        <span style="font-size: 11px; font-weight: 600; min-width: 35px;">{{ $progressPercent }}%</span>
                    </div>
                </td>
                <td>
                    <span class="status" style="background: {{ $riskClass[1] }}; color: {{ $riskClass[0] }}; font-size: 10px; text-transform: uppercase;">
                        {{ ucfirst($loan->risk_category) }}
                    </span>
                </td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <a href="{{ route('loans.show', $loan) }}" class="btn btn-primary" style="padding: 5px 10px; font-size: 11px;" title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if(in_array($loan->status, ['pending', 'under_review', 'partially_approved']))
                        <button class="btn btn-outline" style="padding: 5px 10px; font-size: 11px; color: var(--success); border-color: var(--success);" type="button" title="Approve">
                            <i class="fas fa-check"></i>
                        </button>
                        @endif
                        @if(in_array($loan->status, ['disbursed', 'active']))
                        <button class="btn btn-outline" style="padding: 5px 10px; font-size: 11px; color: var(--primary); border-color: var(--primary);" type="button" title="Record Payment">
                            <i class="fas fa-money-bill-wave"></i>
                        </button>
                        @endif
                        @if($loan->status === 'active' && $loan->days_in_arrears > 0)
                        <button class="btn btn-outline" style="padding: 5px 10px; font-size: 11px; color: var(--danger); border-color: var(--danger);" type="button" title="Send Reminder">
                            <i class="fas fa-bell"></i>
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="16">
                    <div class="empty-state">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>No loans found</p>
                        <p>Try adjusting your filters or add a new loan application</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    {{-- Pagination --}}
    @if(isset($loans) && $loans->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $loans->firstItem() ?? 0 }}–{{ $loans->lastItem() ?? 0 }} of {{ $loans->total() }}</span>
        {{ $loans->links() }}
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    // Select all checkbox
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
            cb.checked = this.checked;
        });
    });
</script>
@endsection