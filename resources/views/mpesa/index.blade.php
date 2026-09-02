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
                                   style="width:120px; padding:5px 8px; font-size:12px; border:1px solid var(--border); border-radius:6px; font-family:monospace;">
                            {{-- Auto reprocess --}}
                            <button type="button"
                                    onclick="reprocessCallback({{ $cb->id }}, this)"
                                    class="btn btn-primary"
                                    style="font-size:12px; padding:5px 10px; white-space:nowrap;"
                                    title="Auto-match by phone or loan number">
                                <i class="fas fa-redo"></i> Reprocess
                            </button>
                            {{-- Manual match --}}
                            <button type="button"
                                    onclick="openMatchModal({{ $cb->id }}, '{{ $cb->transaction_id }}', {{ $cb->amount }}, '{{ $cb->account_reference }}')"
                                    class="btn btn-outline"
                                    style="font-size:12px; padding:5px 10px; white-space:nowrap; color:#7B1FA2; border-color:#CE93D8;"
                                    title="Manually match to a specific loan">
                                <i class="fas fa-link"></i> Match
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

{{-- ── Match Payment Modal ── --}}
<div id="matchModal" class="modal-overlay" onclick="if(event.target===this)closeModal('matchModal')">
    <div class="modal-box" style="width:540px; max-width:96vw;">
        <div class="modal-header">
            <div class="modal-title" style="color:#7B1FA2;">
                <i class="fas fa-link"></i> Match Payment to Loan
            </div>
            <button class="modal-close" onclick="closeModal('matchModal')">&times;</button>
        </div>
        {{-- Payment summary --}}
        <div style="background:#F3E5F5; border:1px solid #CE93D8; border-radius:8px; padding:12px 14px; margin-bottom:18px; font-size:13px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span style="color:#6A1B9A;">Transaction ID</span>
                <strong id="matchTxnId" style="font-family:monospace;"></strong>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span style="color:#6A1B9A;">Account Reference</span>
                <strong id="matchAcctRef"></strong>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:#6A1B9A;">Amount</span>
                <strong id="matchAmount" style="color:var(--success); font-size:16px;"></strong>
            </div>
        </div>
        {{-- Customer search --}}
        <div class="form-group">
            <label class="form-label">Search Customer <span class="req">*</span></label>
            <div style="position:relative;">
                <input type="text" id="matchCustomerSearch" class="form-control"
                       autocomplete="off" placeholder="Name, phone, or ID number…"
                       oninput="searchMatchCustomer(this.value)">
                <div id="matchCustomerDropdown" class="customer-dropdown"></div>
            </div>
        </div>
        {{-- Active loans --}}
        <div id="matchLoanSection" style="display:none;">
            <div class="form-group">
                <label class="form-label">Transaction Type <span class="req">*</span></label>
                <select id="matchTransactionType" class="form-control">
                    <option value="loan_repayment">Loan Repayment</option>
                    <option value="processing_fee">Processing Fee</option>
                </select>
                <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                    Select "Processing Fee" if this is for loan processing fee payment
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Select Active Loan <span class="req">*</span></label>
                <select id="matchLoanSelect" class="form-control">
                    <option value="">-- Select Loan --</option>
                </select>
                <div id="matchLoanInfo" style="font-size:12px; color:var(--text-secondary); margin-top:4px;"></div>
            </div>
        </div>
        <div id="matchResult" style="display:none; font-size:13px; margin-bottom:12px; padding:10px 14px; border-radius:6px;"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('matchModal')">Cancel</button>
            <button type="button" id="matchConfirmBtn" class="btn"
                    style="background:#7B1FA2; color:#fff; border-color:#7B1FA2;"
                    onclick="confirmMatch()" disabled>
                <i class="fas fa-link"></i> Apply Payment
            </button>
        </div>
    </div>
</div>

@section('scripts')
<script>
// ── Modal helpers (local to this page) ──────────────────────────
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('show');
}
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('show');
}

// ── Match Payment Modal ──────────────────────────────────────────
let matchCallbackId = null;

function openMatchModal(cbId, txnId, amount, acctRef) {
    matchCallbackId = cbId;
    document.getElementById('matchTxnId').textContent    = txnId;
    document.getElementById('matchAcctRef').textContent  = acctRef || '—';
    document.getElementById('matchAmount').textContent   = 'KSH ' + Number(amount).toLocaleString('en-KE');
    document.getElementById('matchCustomerSearch').value = acctRef || '';
    document.getElementById('matchLoanSection').style.display = 'none';
    document.getElementById('matchLoanSelect').innerHTML = '<option value="">-- Select Loan --</option>';
    document.getElementById('matchResult').style.display = 'none';
    document.getElementById('matchConfirmBtn').disabled  = true;
    document.getElementById('matchModal').classList.add('show');
    if (acctRef && acctRef.length >= 7) searchMatchCustomer(acctRef);
}

let matchTimer;
function searchMatchCustomer(q) {
    clearTimeout(matchTimer);
    const dd = document.getElementById('matchCustomerDropdown');
    if (q.length < 2) { dd.style.display = 'none'; return; }
    matchTimer = setTimeout(() => {
        fetch('/api/customers/search?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.length) { dd.style.display = 'none'; return; }
                dd.innerHTML = data.map(c => `
                    <div class="customer-option"
                         onclick="selectMatchCustomer(${c.id},'${(c.full_name||'').replace(/'/g,'&#39;')}','${c.phone_number||''}')">
                        <strong>${c.full_name}</strong>
                        <span style="color:var(--text-secondary); margin-left:8px;">${c.phone_number}</span>
                        <span style="float:right; font-size:11px; color:var(--primary);">${c.customer_number}</span>
                    </div>`).join('');
                dd.style.display = 'block';
            });
    }, 280);
}

function selectMatchCustomer(id, name, phone) {
    document.getElementById('matchCustomerSearch').value = name;
    document.getElementById('matchCustomerDropdown').style.display = 'none';
    document.getElementById('matchLoanSection').style.display = 'none';
    document.getElementById('matchConfirmBtn').disabled = true;

    fetch('/api/customers/' + id + '/active-loans')
        .then(r => r.json())
        .then(loans => {
            const sel = document.getElementById('matchLoanSelect');
            if (!loans.length) {
                sel.innerHTML = '<option value="">No active loans for this customer</option>';
            } else {
                sel.innerHTML = '<option value="">-- Select Loan --</option>' +
                    loans.map(l => `<option value="${l.id}"
                        data-balance="${l.outstanding_balance}"
                        data-number="${l.loan_number}">
                        ${l.loan_number} — KSH ${Number(l.outstanding_balance).toLocaleString('en-KE')} outstanding
                    </option>`).join('');
                sel.onchange = function() {
                    const opt = this.options[this.selectedIndex];
                    document.getElementById('matchLoanInfo').textContent = opt.value
                        ? 'Outstanding: KSH ' + Number(opt.dataset.balance).toLocaleString('en-KE')
                        : '';
                    document.getElementById('matchConfirmBtn').disabled = !opt.value;
                };
            }
            document.getElementById('matchLoanSection').style.display = 'block';
        });
}

function confirmMatch() {
    const loanId = document.getElementById('matchLoanSelect').value;
    const transactionType = document.getElementById('matchTransactionType').value;
    const btn    = document.getElementById('matchConfirmBtn');
    const result = document.getElementById('matchResult');
    if (!loanId || !matchCallbackId) return;

    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying…';
    result.style.display = 'none';

    fetch('/mpesa/c2b/callbacks/' + matchCallbackId + '/match', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ 
            loan_id: loanId,
            transaction_type: transactionType 
        }),
    })
    .then(r => r.json())
    .then(data => {
        result.style.display = 'block';
        if (data.success) {
            result.style.background = '#E8F5E9'; result.style.color = '#2E7D32';
            result.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            btn.innerHTML    = '<i class="fas fa-check"></i> Done';
            btn.style.background = 'var(--success)';
            const row = document.getElementById('cb-row-' + matchCallbackId);
            if (row) { row.style.opacity = '0.3'; row.style.transition = 'opacity 0.5s'; }
            setTimeout(() => closeModal('matchModal'), 2500);
        } else {
            result.style.background = '#FFEBEE'; result.style.color = '#C62828';
            result.innerHTML = '<i class="fas fa-times-circle"></i> ' + data.message;
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-link"></i> Apply Payment';
        }
    })
    .catch(err => {
        result.style.display = 'block';
        result.style.background = '#FFEBEE'; result.style.color = '#C62828';
        result.textContent = 'Network error: ' + err.message;
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-link"></i> Apply Payment';
    });
}

document.addEventListener('click', e => {
    if (!e.target.closest('#matchCustomerSearch') && !e.target.closest('#matchCustomerDropdown')) {
        const dd = document.getElementById('matchCustomerDropdown');
        if (dd) dd.style.display = 'none';
    }
});

// ── Reprocess ────────────────────────────────────────────────────
function reprocessCallback(id, btn) {
    const refInput = document.getElementById('cb-ref-' + id);
    const result   = document.getElementById('cb-result-' + id);
    const ref      = refInput.value.trim();
    if (!ref) { result.style.display='block'; result.style.color='var(--danger)'; result.textContent='Enter the customer phone or loan number.'; return; }
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';
    result.style.display = 'none';
    fetch('/mpesa/c2b/callbacks/' + id + '/reprocess', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
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
            setTimeout(() => { const row = document.getElementById('cb-row-'+id); if(row){row.style.opacity='0.4';row.style.transition='opacity 0.5s';} }, 1500);
        } else {
            result.style.color = 'var(--danger)';
            result.innerHTML   = '<i class="fas fa-times-circle"></i> ' + data.message;
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-redo"></i> Reprocess';
        }
    })
    .catch(err => { result.style.display='block'; result.style.color='var(--danger)'; result.textContent='Network error: '+err.message; btn.disabled=false; btn.innerHTML='<i class="fas fa-redo"></i> Reprocess'; });
}

// ── Register C2B URLs ────────────────────────────────────────────
function registerC2bUrls(btn) {
    if (!confirm('Register C2B URLs with Safaricom?\n\nConfirmation URL:\n{{ config('services.mpesa.c2b_confirmation_url', url('/payment/c2b/confirmation')) }}\n\nProceed?')) return;
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering…';
    fetch('{{ route('mpesa.c2b.register') }}', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({}),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Registered!';
            btn.style.background = '#2E7D32';
            const b = document.createElement('div'); b.className='flash-success';
            b.innerHTML='<i class="fas fa-check-circle"></i> C2B URLs registered. Payments will now reflect automatically.';
            document.querySelector('.content-area').insertBefore(b, document.querySelector('.content-area').firstChild);
        } else {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-satellite-dish"></i> Register C2B URLs';
            alert('Registration failed: ' + (data.message||'Unknown error'));
        }
    })
    .catch(err => { btn.disabled=false; btn.innerHTML='<i class="fas fa-satellite-dish"></i> Register C2B URLs'; alert('Network error: '+err.message); });
}
</script>
@endsection
