@extends('layouts.app')

@section('title', $customer->full_name . ' - GetCash Capital')
@section('page-title', 'Customer Profile')

@section('styles')
<style>
    .detail-label { font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px; }
    .detail-value { font-size: 14px; font-weight: 500; color: var(--text-primary); }
    .section-title {
        font-size: 13px; font-weight: 600; color: var(--text-primary);
        margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid var(--border);
        display: flex; align-items: center; gap: 8px;
    }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; }
    .avatar-lg {
        width: 72px; height: 72px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 26px; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .tab-nav { display: flex; gap: 0; border-bottom: 2px solid var(--border); margin-bottom: 20px; }
    .tab-btn {
        padding: 10px 20px; font-size: 13px; font-weight: 500;
        border: none; background: none; cursor: pointer; color: var(--text-secondary);
        border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.15s;
    }
    .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); font-weight: 600; }
    .tab-pane { display: none; }
    .tab-pane.active { display: block; }
</style>
@endsection

@section('content')

@if(session('success'))
<div style="background:#E8F5E9; border:1px solid #A5D6A7; border-radius:8px; padding:12px 16px; margin-bottom:16px; color:#2E7D32; display:flex; align-items:center; gap:10px;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#FFEBEE; border:1px solid #FFCDD2; border-radius:8px; padding:12px 16px; margin-bottom:16px; color:#C62828; display:flex; align-items:center; gap:10px;">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
</div>
@endif

{{-- Portal credentials flash (shown once after activation) --}}
@if(session('portal_credentials'))
@php $creds = session('portal_credentials'); @endphp
<div style="background:#E3F2FD; border:1px solid #90CAF9; border-radius:10px; padding:16px 20px; margin-bottom:16px;">
    <div style="font-size:14px; font-weight:700; color:#1565C0; margin-bottom:10px;">
        <i class="fas fa-user-check"></i> Customer Portal Account Created
    </div>
    <p style="font-size:13px; color:#1976D2; margin-bottom:10px;">
        Share these credentials with the customer. They can log in at
        <strong>{{ url('/portal/login') }}</strong>
    </p>
    <div style="background:white; border-radius:8px; padding:12px 16px; font-family:monospace; font-size:13px; border:1px solid #BBDEFB;">
        <div><strong>Email:</strong> {{ $creds['email'] }}</div>
        <div style="margin-top:6px;"><strong>Password:</strong> {{ $creds['password'] }}</div>
    </div>
    <p style="font-size:11px; color:#1976D2; margin-top:8px;">
        <i class="fas fa-exclamation-triangle"></i>
        This is shown only once. Please note it down before leaving this page.
    </p>
</div>
@endif

{{-- Back + Actions --}}
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <a href="{{ route('customers.index') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    <div style="display:flex; gap:8px;">
        @if($customer->status === 'active')
        <a href="{{ route('loans.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary">
            <i class="fas fa-hand-holding-usd"></i> Apply for Loan
        </a>
        @endif
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline">
            <i class="fas fa-pen"></i> Edit
        </a>
    </div>
</div>

{{-- ── Profile Header ── --}}
@php
    $initials  = collect(explode(' ', $customer->full_name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->implode('');
    $colors    = ['#00BCD4','#4CAF50','#FF9800','#9C27B0','#F44336','#3F51B5','#009688','#795548'];
    $avatarBg  = $colors[abs(crc32($customer->customer_number)) % count($colors)];
    $statusMap = ['active' => ['status-active','Active'], 'pending' => ['status-pending','Pending'],
                  'suspended' => ['status-partially-approved','Suspended'], 'rejected' => ['status-rejected','Rejected'],
                  'dormant' => ['status-partially-approved','Dormant']];
    [$statusClass, $statusLabel] = $statusMap[$customer->status] ?? ['status-pending', ucfirst($customer->status)];
    $score = $customer->credit_score ?? 0;
    $scoreColor = match(true) {
        $score >= 800 => '#4CAF50', $score >= 650 => '#8BC34A',
        $score >= 500 => '#FF9800', $score >= 350 => '#FF5722', default => '#F44336'
    };
@endphp

<div class="card" style="margin-bottom:20px;">
    <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
        <div class="avatar-lg" style="background:{{ $avatarBg }};">{{ $initials }}</div>
        <div style="flex:1; min-width:200px;">
            <div style="font-size:22px; font-weight:700; color:var(--text-primary);">{{ $customer->full_name }}</div>
            <div style="font-size:13px; color:var(--text-secondary); margin-top:4px; display:flex; flex-wrap:wrap; gap:16px;">
                <span><i class="fas fa-hashtag" style="color:var(--primary);"></i> {{ $customer->customer_number }}</span>
                <span><i class="fas fa-phone" style="color:var(--primary);"></i> {{ $customer->phone_number }}</span>
                <span><i class="fas fa-id-card" style="color:var(--primary);"></i> {{ $customer->id_number }}</span>
                @if($customer->email)
                <span><i class="fas fa-envelope" style="color:var(--primary);"></i> {{ $customer->email }}</span>
                @endif
            </div>
            <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <span class="status {{ $statusClass }}">{{ $statusLabel }}</span>
                @if($customer->kyc_verified_at)
                    <span style="font-size:12px; color:var(--success);"><i class="fas fa-shield-alt"></i> KYC Verified</span>
                @else
                    <span style="font-size:12px; color:var(--warning);"><i class="fas fa-exclamation-circle"></i> KYC Pending</span>
                @endif
                <span style="font-size:12px; color:var(--text-secondary);">
                    Joined {{ $customer->created_at->format('d M Y') }}
                </span>
            </div>
        </div>
        {{-- Quick stats --}}
        <div style="display:flex; gap:20px; flex-wrap:wrap;">
            <div style="text-align:center; padding:12px 20px; background:#F0FBFD; border-radius:10px; border:1px solid #B3E5FC;">
                <div style="font-size:20px; font-weight:700; color:var(--primary);">KSH {{ number_format($customer->savings_balance, 0) }}</div>
                <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">Savings Balance</div>
            </div>
            <div style="text-align:center; padding:12px 20px; background:#F3E5F5; border-radius:10px; border:1px solid #CE93D8;">
                <div style="font-size:20px; font-weight:700; color:#7B1FA2;">KSH {{ number_format($customer->share_capital, 0) }}</div>
                <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">Share Capital</div>
            </div>
            <div style="text-align:center; padding:12px 20px; background:#FFF8E1; border-radius:10px; border:1px solid #FFE082;">
                <div style="font-size:20px; font-weight:700; color:{{ $scoreColor }};">{{ $score }}</div>
                <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">Credit Score</div>
            </div>
            <div style="text-align:center; padding:12px 20px; background:#E8F5E9; border-radius:10px; border:1px solid #A5D6A7;">
                <div style="font-size:20px; font-weight:700; color:var(--success);">{{ $customer->loans->count() }}</div>
                <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">Total Loans</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Tabs ── --}}
<div class="tab-nav">
    <button class="tab-btn active" onclick="switchTab('personal', this)"><i class="fas fa-user"></i> Personal</button>
    <button class="tab-btn" onclick="switchTab('loans', this)"><i class="fas fa-hand-holding-usd"></i> Loans ({{ $customer->loans->count() }})</button>
    <button class="tab-btn" onclick="switchTab('transactions', this)"><i class="fas fa-exchange-alt"></i> Transactions</button>
    <button class="tab-btn" onclick="switchTab('credit', this)"><i class="fas fa-chart-line"></i> Credit History</button>
</div>

{{-- ── Tab: Personal ── --}}
<div class="tab-pane active" id="tab-personal">
    <div class="grid-2" style="gap:20px;">
        <div class="card">
            <div class="section-title"><i class="fas fa-user" style="color:var(--primary);"></i> Personal Details</div>
            <div class="info-grid">
                <div><div class="detail-label">Full Name</div><div class="detail-value">{{ $customer->full_name }}</div></div>
                <div><div class="detail-label">Date of Birth</div><div class="detail-value">{{ $customer->date_of_birth?->format('d M Y') ?? '—' }}</div></div>
                <div><div class="detail-label">Gender</div><div class="detail-value">{{ ucfirst($customer->gender ?? '—') }}</div></div>
                <div><div class="detail-label">Nationality</div><div class="detail-value">{{ $customer->nationality ?? '—' }}</div></div>
                <div><div class="detail-label">County</div><div class="detail-value">{{ $customer->county ?? '—' }}</div></div>
                <div><div class="detail-label">Sub-County</div><div class="detail-value">{{ $customer->sub_county ?? '—' }}</div></div>
                <div><div class="detail-label">Ward</div><div class="detail-value">{{ $customer->ward ?? '—' }}</div></div>
                <div style="grid-column:span 2;"><div class="detail-label">Address</div><div class="detail-value">{{ $customer->address ?? '—' }}</div></div>
            </div>
        </div>
        <div class="card">
            <div class="section-title"><i class="fas fa-briefcase" style="color:var(--primary);"></i> Employment</div>
            <div class="info-grid">
                <div><div class="detail-label">Employment Type</div><div class="detail-value">{{ ucfirst(str_replace('_',' ',$customer->employment_type ?? '—')) }}</div></div>
                <div><div class="detail-label">Monthly Income</div><div class="detail-value">KSH {{ number_format($customer->monthly_income ?? 0, 0) }}</div></div>
                <div><div class="detail-label">Employer</div><div class="detail-value">{{ $customer->employer_name ?? '—' }}</div></div>
                <div><div class="detail-label">Business Name</div><div class="detail-value">{{ $customer->business_name ?? '—' }}</div></div>
                <div><div class="detail-label">Business Type</div><div class="detail-value">{{ $customer->business_type ?? '—' }}</div></div>
            </div>
            <div class="section-title" style="margin-top:20px;"><i class="fas fa-users" style="color:var(--primary);"></i> Next of Kin</div>
            <div class="info-grid">
                <div><div class="detail-label">Name</div><div class="detail-value">{{ $customer->next_of_kin_name }}</div></div>
                <div><div class="detail-label">Phone</div><div class="detail-value">{{ $customer->next_of_kin_phone }}</div></div>
                <div><div class="detail-label">Relationship</div><div class="detail-value">{{ $customer->next_of_kin_relationship }}</div></div>
                <div><div class="detail-label">Address</div><div class="detail-value">{{ $customer->next_of_kin_address ?? '—' }}</div></div>
            </div>
        </div>
    </div>
    <div class="card" style="margin-top:20px;">
        <div class="section-title"><i class="fas fa-building" style="color:var(--primary);"></i> SACCO Membership</div>
        <div class="info-grid">
            <div><div class="detail-label">Branch</div><div class="detail-value">{{ $customer->branch->name ?? '—' }}</div></div>
            <div><div class="detail-label">Relationship Officer</div><div class="detail-value">{{ $customer->relationshipOfficer->name ?? '—' }}</div></div>
            <div><div class="detail-label">Credit Limit</div><div class="detail-value">KSH {{ number_format($customer->credit_limit, 0) }}</div></div>
            <div><div class="detail-label">KYC Verified</div>
                <div class="detail-value">
                    @if($customer->kyc_verified_at)
                        <span style="color:var(--success);">{{ $customer->kyc_verified_at->format('d M Y') }}</span>
                    @else
                        <span style="color:var(--warning);">Not verified</span>
                    @endif
                </div>
            </div>
            <div><div class="detail-label">Activated</div><div class="detail-value">{{ $customer->activated_at?->format('d M Y') ?? '—' }}</div></div>
            <div><div class="detail-label">Last Transaction</div><div class="detail-value">{{ $customer->last_transaction_at?->format('d M Y') ?? 'Never' }}</div></div>
        </div>
    </div>
</div>

{{-- ── Tab: Loans ── --}}
<div class="tab-pane" id="tab-loans">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <span style="font-size:13px; color:var(--text-secondary);">{{ $customer->loans->count() }} loan(s) on record</span>
        @if($customer->status === 'active')
        <a href="{{ route('loans.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary" style="font-size:13px;">
            <i class="fas fa-plus"></i> New Loan Application
        </a>
        @endif
    </div>
    <div class="card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Loan No.</th>
                    <th>Product</th>
                    <th>Principal</th>
                    <th>Total Repayable</th>
                    <th>Outstanding</th>
                    <th>Term</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->loans()->with('product')->latest()->get() as $loan)
                @php
                    $lsc = match($loan->status) {
                        'active','disbursed' => 'status-active', 'approved' => 'status-active',
                        'pending','under_review' => 'status-pending', 'rejected','defaulted' => 'status-rejected',
                        'completed' => 'status-active', default => 'status-pending'
                    };
                @endphp
                <tr>
                    <td style="font-family:monospace; font-size:12px; font-weight:600;">{{ $loan->loan_number }}</td>
                    <td>{{ $loan->product->name ?? '—' }}</td>
                    <td style="font-weight:600;">KSH {{ number_format($loan->principal_amount, 0) }}</td>
                    <td>KSH {{ number_format($loan->total_repayable, 0) }}</td>
                    <td style="color:var(--primary); font-weight:600;">KSH {{ number_format($loan->outstanding_balance, 0) }}</td>
                    <td>{{ $loan->term_weeks }}w</td>
                    <td><span class="status {{ $lsc }}">{{ ucfirst(str_replace('_',' ',$loan->status)) }}</span></td>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $loan->created_at->format('d M Y') }}</td>
                    <td><a href="{{ route('loans.show', $loan) }}" class="btn btn-outline" style="padding:4px 10px; font-size:12px;"><i class="fas fa-eye"></i></a></td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center; padding:40px; color:var(--text-secondary);">No loans found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Tab: Transactions ── --}}
<div class="tab-pane" id="tab-transactions">
    <div class="card">
        <table class="data-table">
            <thead>
                <tr><th>Txn No.</th><th>Type</th><th>Source</th><th>Amount</th><th>Direction</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                @forelse($customer->transactions()->latest()->limit(50)->get() as $txn)
                <tr>
                    <td style="font-family:monospace; font-size:11px;">{{ $txn->transaction_number }}</td>
                    <td><span class="badge badge-primary">{{ ucfirst(str_replace('_',' ',$txn->transaction_type)) }}</span></td>
                    <td>{{ ucfirst($txn->source ?? '—') }}</td>
                    <td style="font-weight:700; color:{{ $txn->direction === 'credit' ? 'var(--success)' : 'var(--danger)' }};">
                        {{ $txn->direction === 'credit' ? '+' : '-' }} KSH {{ number_format($txn->amount, 0) }}
                    </td>
                    <td>{{ ucfirst($txn->direction) }}</td>
                    <td><span class="status {{ $txn->status === 'completed' ? 'status-active' : 'status-pending' }}">{{ ucfirst($txn->status) }}</span></td>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $txn->created_at->format('d M Y h:i A') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center; padding:40px; color:var(--text-secondary);">No transactions found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Tab: Credit History ── --}}
<div class="tab-pane" id="tab-credit">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <span style="font-size:13px; color:var(--text-secondary);">Credit score history</span>
        <form method="POST" action="{{ route('customers.recalculate-score', $customer) }}">
            @csrf
            <button type="submit" class="btn btn-primary" style="font-size:13px;">
                <i class="fas fa-sync-alt"></i> Recalculate Score
            </button>
        </form>
    </div>
    <div class="card">
        <table class="data-table">
            <thead>
                <tr><th>Date</th><th>Savings</th><th>Repayment</th><th>Income</th><th>Guarantor</th><th>Collateral</th><th>Total</th><th>Rating</th><th>Recommendation</th></tr>
            </thead>
            <tbody>
                @forelse($customer->creditScores()->latest()->get() as $cs)
                @php
                    $ratingColor = match($cs->rating) {
                        'excellent' => 'var(--success)', 'good' => '#8BC34A',
                        'fair' => 'var(--warning)', 'poor' => '#FF5722', default => 'var(--danger)'
                    };
                @endphp
                <tr>
                    <td style="font-size:12px;">{{ $cs->calculated_at?->format('d M Y') ?? $cs->created_at->format('d M Y') }}</td>
                    <td>{{ $cs->savings_history_score }}</td>
                    <td>{{ $cs->repayment_history_score }}</td>
                    <td>{{ $cs->income_stability_score }}</td>
                    <td>{{ $cs->guarantor_strength_score }}</td>
                    <td>{{ $cs->collateral_value_score }}</td>
                    <td style="font-weight:700; color:{{ $ratingColor }};">{{ $cs->total_score }}</td>
                    <td><span class="badge" style="background:{{ $ratingColor }}20; color:{{ $ratingColor }};">{{ ucfirst($cs->rating) }}</span></td>
                    <td style="font-size:12px; color:var(--text-secondary);">{{ $cs->recommendation }}</td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center; padding:40px; color:var(--text-secondary);">No credit score history</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('scripts')
<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}
</script>
@endsection
