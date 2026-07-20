@extends('layouts.app')

@section('title', 'M-Pesa Transactions')
@section('page-title', 'M-Pesa Transactions')

@section('content')

@if(session('success'))
<div class="flash-success">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="flash-error">
    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
</div>
@endif

{{-- C2B Registration Banner --}}
<div style="background:#E3F2FD; border:1px solid #90CAF9; border-radius:10px;
            padding:14px 18px; margin-bottom:20px; display:flex; align-items:center;
            gap:14px; flex-wrap:wrap;">
    <i class="fas fa-plug" style="color:#1565C0; font-size:20px; flex-shrink:0;"></i>
    <div style="flex:1; min-width:200px;">
        <div style="font-weight:700; font-size:13px; color:#1565C0;">C2B Paybill Registration</div>
        <div style="font-size:12px; color:#1976D2; margin-top:2px;">
            For payments made directly to paybill <strong>{{ config('services.mpesa.shortcode') }}</strong> to reflect automatically,
            the confirmation URL must be registered with Safaricom. Click the button to register.
            <br>Confirmation URL: <code style="background:#BBDEFB; padding:1px 5px; border-radius:3px; font-size:11px;">{{ config('services.mpesa.c2b_confirmation_url', url('/mpesa/c2b/confirmation')) }}</code>
        </div>
    </div>
    <button type="button" onclick="registerC2bUrls(this)"
            class="btn" style="background:#1565C0; color:#fff; white-space:nowrap; flex-shrink:0;">
        <i class="fas fa-satellite-dish"></i> Register C2B URLs
    </button>
</div>

{{-- Stats --}}
<div class="grid-4" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-title">STK Push (Repayments)</div>
        <div class="metric-value" style="color:var(--primary);">{{ number_format($totalStkPush) }}</div>
        <div class="metric-label">Total initiated</div>
    </div>
    <div class="card">
        <div class="card-title">B2C (Disbursements)</div>
        <div class="metric-value" style="color:#7B1FA2;">{{ number_format($totalB2c) }}</div>
        <div class="metric-label">Total initiated</div>
    </div>
    <div class="card">
        <div class="card-title">Total Disbursed</div>
        <div class="metric-value" style="color:var(--danger);">KSH {{ number_format($totalDisbursed, 0) }}</div>
        <div class="metric-label">Completed B2C</div>
    </div>
    <div class="card">
        <div class="card-title">Total Collected</div>
        <div class="metric-value" style="color:var(--success);">KSH {{ number_format($totalCollected, 0) }}</div>
        <div class="metric-label">Completed STK</div>
    </div>
</div>

{{-- Failed / Suspended C2B Callbacks — Reprocess Panel --}}
@if(isset($failedC2b) && $failedC2b->count() > 0)
<div class="card" style="margin-bottom:24px; border-left:4px solid var(--danger);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
        <div>
            <div style="font-size:14px; font-weight:700; color:var(--danger);">
                <i class="fas fa-exclamation-triangle"></i>
                Failed / Suspended C2B Callbacks ({{ $failedC2b->count() }})
            </div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:3px;">
                These payments were received from Safaricom but could not be matched. Click Reprocess to retry.
            </div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Transaction ID</th>
                    <th>Account Ref</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($failedC2b as $cb)
                <tr id="cb-row-{{ $cb->id }}">
                    <td style="font-size:12px; white-space:nowrap;">{{ $cb->created_at->format('d M Y H:i') }}</td>
                    <td style="font-family:monospace; font-size:11px; font-weight:600; color:var(--primary);">
                        {{ $cb->transaction_id }}
                    </td>
                    <td>
                        <span style="font-family:monospace; font-size:13px; font-weight:600;">
                            {{ $cb->account_reference ?: '—' }}
                        </span>
                        @if($cb->customer)
                            <div style="font-size:11px; color:var(--success); margin-top:2px;">
                                <i class="fas fa-check-circle"></i> {{ $cb->customer->full_name }}
                            </div>
                        @endif
                    </td>
                    <td style="font-weight:700; color:var(--success);">
                        KSH {{ number_format($cb->amount, 0) }}
                    </td>
                    <td>
                        <span class="status {{ $cb->status === 'suspended' ? 'status-partially-approved' : 'status-pending' }}">
                            {{ ucfirst($cb->status) }}
                        </span>
                    </td>
                    <td style="font-size:11px; color:var(--text-secondary); max-width:220px;">
                        @php
                            $raw = $cb->raw_callback;
                            $suspenseNote = \App\Models\SuspenseAccount::where('external_reference', $cb->transaction_id)
                                ->value('resolution_notes');
                        @endphp
                        {{ $suspenseNote ?? 'Check account reference' }}
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center; align-items:center;" id="cb-actions-{{ $cb->id }}">
                            {{-- Edit account ref inline --}}
                            <input type="text" id="cb-ref-{{ $cb->id }}"
                                   value="{{ $cb->account_reference }}"
                                   placeholder="Phone or loan no."
                                   style="width:130px; padding:5px 8px; font-size:12px; border:1px solid var(--border); border-radius:6px; font-family:monospace;">
                            <button type="button"
                                    onclick="reprocessCallback({{ $cb->id }}, this)"
                                    class="btn btn-primary"
                                    style="font-size:12px; padding:5px 12px; white-space:nowrap;">
                                <i class="fas fa-redo"></i> Reprocess
                            </button>
                        </div>
                        <div id="cb-result-{{ $cb->id }}" style="font-size:11px; margin-top:4px; display:none;"></div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Filters --}}
<form method="GET" action="{{ route('mpesa.index') }}" style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; align-items:flex-end;">
    <div class="search-box" style="width:260px;">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Phone, receipt, loan no…" value="{{ request('search') }}">
    </div>
    <select name="type" class="filter-select">
        <option value="">All Types</option>
        <option value="stk_push" {{ request('type') === 'stk_push' ? 'selected' : '' }}>STK Push</option>
        <option value="b2c"      {{ request('type') === 'b2c'      ? 'selected' : '' }}>B2C Disbursement</option>
    </select>
    <select name="status" class="filter-select">
        <option value="">All Statuses</option>
        <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
        <option value="failed"    {{ request('status') === 'failed'    ? 'selected' : '' }}>Failed</option>
    </select>
    <input type="date" name="date_from" class="filter-select" value="{{ request('date_from') }}" placeholder="From">
    <input type="date" name="date_to"   class="filter-select" value="{{ request('date_to') }}"   placeholder="To">
    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    <a href="{{ route('mpesa.index') }}" class="btn btn-outline">Clear</a>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Loan</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Amount</th>
                    <th>Receipt</th>
                    <th>Status</th>
                    <th>Initiated By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                @php
                    $statusColor = match($txn->status) {
                        'completed' => ['status-active', 'Completed'],
                        'failed'    => ['status-rejected', 'Failed'],
                        'cancelled' => ['status-rejected', 'Cancelled'],
                        default     => ['status-pending', 'Pending'],
                    };
                    $typeColor = $txn->type === 'b2c' ? '#7B1FA2' : 'var(--primary)';
                    $typeLabel = $txn->type === 'b2c' ? 'B2C Disburse' : 'STK Push';
                @endphp
                <tr>
                    <td style="font-size:12px; white-space:nowrap;">{{ $txn->created_at->format('d M Y H:i') }}</td>
                    <td>
                        <span class="badge" style="background:{{ $txn->type === 'b2c' ? '#F3E5F5' : '#E3F2FD' }}; color:{{ $typeColor }};">
                            <i class="fas fa-{{ $txn->type === 'b2c' ? 'paper-plane' : 'mobile-alt' }}"></i>
                            {{ $typeLabel }}
                        </span>
                    </td>
                    <td>
                        @if($txn->loan)
                        <a href="{{ route('loans.show', $txn->loan) }}" style="font-family:monospace; font-size:12px; color:var(--primary);">
                            {{ $txn->loan->loan_number }}
                        </a>
                        @else
                        <span style="color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                    <td style="font-size:13px;">{{ $txn->customer?->full_name ?? '—' }}</td>
                    <td style="font-family:monospace; font-size:12px;">{{ $txn->phone_number }}</td>
                    <td style="font-weight:700; color:{{ $txn->type === 'b2c' ? 'var(--danger)' : 'var(--success)' }};">
                        {{ $txn->type === 'b2c' ? '-' : '+' }} KSH {{ number_format($txn->amount, 0) }}
                    </td>
                    <td style="font-family:monospace; font-size:11px; color:var(--text-secondary);">
                        {{ $txn->mpesa_receipt_number ?? '—' }}
                    </td>
                    <td>
                        <span class="status {{ $statusColor[0] }}">{{ $statusColor[1] }}</span>
                        @if($txn->result_desc && $txn->status === 'failed')
                        <div style="font-size:10px; color:var(--danger); margin-top:2px; max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $txn->result_desc }}">
                            {{ $txn->result_desc }}
                        </div>
                        @endif
                    </td>
                    <td style="font-size:12px;">{{ $txn->initiatedBy?->name ?? 'System' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">No M-Pesa transactions found</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
    <div style="margin-top:16px; display:flex; justify-content:center;">
        {{ $transactions->links() }}
    </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
function reprocessCallback(id, btn) {
    const refInput = document.getElementById('cb-ref-' + id);
    const result   = document.getElementById('cb-result-' + id);
    const ref      = refInput.value.trim();

    if (!ref) {
        result.style.display = 'block';
        result.style.color   = 'var(--danger)';
        result.textContent   = 'Enter the customer phone or loan number.';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';
    result.style.display = 'none';

    // Update the account_reference first if changed
    fetch('{{ url('/mpesa/c2b/callbacks') }}/' + id + '/reprocess', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ account_reference: ref }),
    })
    .then(r => r.json())
    .then(data => {
        result.style.display = 'block';
        if (data.success) {
            result.style.color = 'var(--success)';
            result.innerHTML   = '<i class="fas fa-check-circle"></i> ' + data.message;
            btn.innerHTML      = '<i class="fas fa-check"></i> Done';
            btn.style.background = 'var(--success)';
            // Fade out the row after 2s
            setTimeout(() => {
                const row = document.getElementById('cb-row-' + id);
                if (row) { row.style.opacity = '0.4'; row.style.transition = 'opacity 0.5s'; }
            }, 1500);
        } else {
            result.style.color = 'var(--danger)';
            result.innerHTML   = '<i class="fas fa-times-circle"></i> ' + data.message;
            btn.disabled       = false;
            btn.innerHTML      = '<i class="fas fa-redo"></i> Reprocess';
        }
    })
    .catch(err => {
        result.style.display = 'block';
        result.style.color   = 'var(--danger)';
        result.textContent   = 'Network error: ' + err.message;
        btn.disabled         = false;
        btn.innerHTML        = '<i class="fas fa-redo"></i> Reprocess';
    });
}
    if (!confirm('Register C2B URLs with Safaricom? This will tell Safaricom to send payment notifications to:\n\n{{ config('services.mpesa.c2b_confirmation_url', url('/mpesa/c2b/confirmation')) }}\n\nProceed?')) {
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering…';

    fetch('{{ route('mpesa.c2b.register') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({}),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Registered!';
            btn.style.background = '#2E7D32';
            // Show success banner
            const banner = document.createElement('div');
            banner.className = 'flash-success';
            banner.innerHTML = '<i class="fas fa-check-circle"></i> C2B URLs registered with Safaricom successfully. Payments made to paybill will now reflect automatically.';
            document.querySelector('.content-area').insertBefore(banner, document.querySelector('.content-area').firstChild);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-satellite-dish"></i> Register C2B URLs';
            alert('Registration failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-satellite-dish"></i> Register C2B URLs';
        alert('Network error: ' + err.message);
    });
}
</script>
@endsection
