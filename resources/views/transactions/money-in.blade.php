{{-- resources/views/transactions/money-in.blade.php --}}
@extends('layouts.app')

@section('title', "Today's Money In - GetCash Capital")
@section('page-title', 'Money In')

@section('content')

{{-- Summary Cards --}}
<div class="grid-4" style="margin-bottom: 20px;">
    <div class="card" style="border-left: 4px solid var(--primary);">
        <div class="metric-label">Total Received Today</div>
        <div class="metric-value" style="font-size: 22px; color: var(--primary);">KSH {{ number_format($totalToday, 0) }}</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--success);">
        <div class="metric-label">Loan Repayments</div>
        <div class="metric-value" style="font-size: 22px; color: var(--success);">KSH {{ number_format($repaymentTotal, 0) }}</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--warning);">
        <div class="metric-label">Savings Deposits</div>
        <div class="metric-value" style="font-size: 22px; color: var(--warning);">KSH {{ number_format($savingsTotal, 0) }}</div>
    </div>
    <div class="card" style="border-left: 4px solid #9C27B0;">
        <div class="metric-label">Share Capital</div>
        <div class="metric-value" style="font-size: 22px; color: #9C27B0;">KSH {{ number_format($shareCapitalTotal, 0) }}</div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('transactions.money-in') }}">
        <div class="filter-row">
            <input type="date" name="date_from" value="{{ request('date_from', today()->toDateString()) }}" class="filter-select" style="flex:1 1 130px;">
            <input type="date" name="date_to"   value="{{ request('date_to',   today()->toDateString()) }}" class="filter-select" style="flex:1 1 130px;">
            <select name="type" class="filter-select" style="flex:1 1 150px;">
                <option value="">All Types</option>
                <option value="loan_repayment"  {{ request('type') === 'loan_repayment'  ? 'selected':'' }}>Loan Repayment</option>
                <option value="savings_deposit" {{ request('type') === 'savings_deposit' ? 'selected':'' }}>Savings Deposit</option>
                <option value="share_capital"   {{ request('type') === 'share_capital'   ? 'selected':'' }}>Share Capital</option>
            </select>
            <select name="source" class="filter-select" style="flex:1 1 120px;">
                <option value="">All Sources</option>
                <option value="mpesa" {{ request('source') === 'mpesa' ? 'selected':'' }}>M-Pesa</option>
                <option value="bank"  {{ request('source') === 'bank'  ? 'selected':'' }}>Bank</option>
                <option value="cash"  {{ request('source') === 'cash'  ? 'selected':'' }}>Cash</option>
            </select>
            <div style="flex:1 1 180px;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name / Phone / Ref">
                </div>
            </div>
            <div style="display:flex; gap:8px; flex-shrink:0;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> <span class="btn-label">Filter</span></button>
                <a href="{{ route('transactions.money-in') }}" class="btn btn-outline"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>
</div>

{{-- Record Payment Button --}}
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:8px;">
    <span style="font-size:13px; color:var(--text-secondary);">
        <strong>{{ $transactions->total() }}</strong> transactions
    </span>
    <button class="btn btn-primary" onclick="openPaymentModal()">
        <i class="fas fa-plus"></i> Record Payment
    </button>
</div>

{{-- Transactions Table --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Inflow Transactions</span>
        <span class="badge badge-success">{{ $transactions->total() }} Records</span>
    </div>

    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Transaction ID</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Type</th>
                <th>Source</th>
                <th>Ext. Reference</th>
                <th>Amount (KSH)</th>
                <th>Status</th>
                <th>Date</th>
                <th>Captured By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $txn)
            @php
                $typeColors = [
                    'loan_repayment'  => ['#4CAF50', '#E8F5E9'],
                    'savings_deposit' => ['#FF9800', '#FFF3E0'],
                    'share_capital'   => ['#9C27B0', '#F3E5F5'],
                    'processing_fee'  => ['#00BCD4', '#E3F2FD'],
                    'insurance_fee'   => ['#607D8B', '#ECEFF1'],
                    'penalty'         => ['#F44336', '#FFEBEE'],
                ];
                $tc = $typeColors[$txn->transaction_type] ?? ['#757575', '#F5F5F5'];
            @endphp
            <tr>
                <td>{{ ($transactions->currentPage() - 1) * $transactions->perPage() + $index + 1 }}</td>
                <td style="font-family: monospace; font-size: 11px; font-weight: 600;">{{ $txn->transaction_number }}</td>
                <td style="font-weight: 500;">{{ $txn->customer?->full_name ?? '—' }}</td>
                <td style="font-size: 12px;">{{ $txn->customer?->phone_number ?? $txn->phone_number ?? '—' }}</td>
                <td>
                    <span class="badge" style="background: {{ $tc[1] }}; color: {{ $tc[0] }};">
                        {{ ucfirst(str_replace('_', ' ', $txn->transaction_type)) }}
                    </span>
                </td>
                <td>
                    @if($txn->source === 'mpesa')
                        <span class="badge badge-success"><i class="fas fa-mobile-alt"></i> M-Pesa</span>
                    @elseif($txn->source === 'bank')
                        <span class="badge badge-primary"><i class="fas fa-university"></i> Bank</span>
                    @else
                        <span class="badge badge-warning"><i class="fas fa-money-bill"></i> Cash</span>
                    @endif
                </td>
                <td style="font-family: monospace; font-size: 11px;">{{ $txn->external_reference ?? '—' }}</td>
                <td style="font-weight: 700; color: var(--success);">{{ number_format($txn->amount, 0) }}</td>
                <td>
                    @if($txn->status === 'completed')
                        <span class="status status-active">Completed</span>
                    @elseif($txn->status === 'pending')
                        <span class="status status-pending">Pending</span>
                    @elseif($txn->status === 'reversed')
                        <span class="status status-rejected">Reversed</span>
                    @else
                        <span class="status status-rejected">Failed</span>
                    @endif
                </td>
                <td style="font-size: 12px; color: var(--text-secondary);">
                    {{ $txn->created_at->format('d M Y') }}<br>
                    <span style="font-size: 11px;">{{ $txn->created_at->format('h:i A') }}</span>
                </td>
                <td style="font-size: 12px;">{{ $txn->createdBy?->name ?? 'System' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align: center; padding: 60px; color: var(--text-secondary);">
                    <i class="fas fa-money-bill-wave" style="font-size: 48px; display: block; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p style="font-size: 15px;">No inflow transactions found</p>
                    <p style="font-size: 12px; opacity: 0.7;">Try adjusting the date range or filters</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    @if($transactions->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $transactions->firstItem() ?? 0 }}–{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }}</span>
        {{ $transactions->appends(request()->query())->links() }}
    </div>
    @endif
</div>

{{-- Record Payment Modal --}}
<div id="paymentModal" class="modal-overlay" onclick="if(event.target===this)closePaymentModal()">
    <div class="modal-box wide">
        <div class="modal-header">
            <div class="modal-title">Record Payment</div>
            <button class="modal-close" onclick="closePaymentModal()">&times;</button>
        </div>

        <form id="paymentForm" method="POST" action="{{ route('transactions.store') }}">
            @csrf
            <div class="modal-body">
            <div class="grid-2" style="gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label class="form-label">Transaction Type <span class="req">*</span></label>
                    <select name="transaction_type" id="txnType" class="form-control" onchange="toggleFields()" required>
                        <option value="">-- Select --</option>
                        <option value="loan_repayment">Loan Repayment</option>
                        <option value="savings_deposit">Savings Deposit</option>
                        <option value="share_capital">Share Capital</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Source <span class="req">*</span></label>
                    <select name="source" id="paySource" class="form-control" onchange="toggleSourceFields()" required>
                        <option value="">-- Select --</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Customer <span class="req">*</span></label>
                <input type="text" id="customerSearch" placeholder="Search by name, phone or ID..." class="form-control" autocomplete="off" oninput="searchCustomers(this.value)">
                <input type="hidden" name="customer_id" id="customerId">
                <div id="customerDropdown" style="display:none; position:absolute; background:white; border:1px solid var(--border); border-radius:6px; left:0; right:0; max-height:200px; overflow-y:auto; z-index:3000; box-shadow:0 4px 12px rgba(0,0,0,0.1);"></div>
                <div id="selectedCustomer" style="display:none; margin-top:8px; padding:10px; background:#E3F2FD; border-radius:6px; font-size:12px;"></div>
            </div>

            {{-- Loan field (shown for repayments) --}}
            <div id="loanField" class="form-group" style="display:none;">
                <label class="form-label">Loan <span class="req">*</span></label>
                <select name="loan_id" id="loanSelect" class="form-control">
                    <option value="">-- Select customer first --</option>
                </select>
            </div>

            <div class="grid-2" style="gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label class="form-label">Amount (KSH) <span class="req">*</span></label>
                    <input type="number" name="amount" id="payAmount" min="1" step="0.01" placeholder="0.00" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Date <span class="req">*</span></label>
                    <input type="date" name="payment_date" value="{{ today()->toDateString() }}" class="form-control" required>
                </div>
            </div>

            {{-- M-Pesa fields --}}
            <div id="mpesaFields" style="display:none; margin-bottom:15px;">
                <div class="grid-2" style="gap:15px;">
                    <div class="form-group">
                        <label class="form-label">M-Pesa Receipt No. <span class="req">*</span></label>
                        <input type="text" name="mpesa_receipt" placeholder="e.g. QHX1234ABC" class="form-control" style="text-transform:uppercase;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" placeholder="07XXXXXXXX" class="form-control">
                    </div>
                </div>
            </div>

            {{-- Bank fields --}}
            <div id="bankFields" style="display:none; margin-bottom:15px;">
                <div class="form-group">
                    <label class="form-label">Bank Reference <span class="req">*</span></label>
                    <input type="text" name="bank_reference" placeholder="Bank transaction reference" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="2" placeholder="Optional notes..." class="form-control"></textarea>
            </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closePaymentModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Record Payment</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
function openPaymentModal() {
    document.getElementById('paymentModal').classList.add('show');
}
function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('show');
    document.getElementById('paymentForm').reset();
    document.getElementById('selectedCustomer').style.display = 'none';
    document.getElementById('loanField').style.display = 'none';
    document.getElementById('mpesaFields').style.display = 'none';
    document.getElementById('bankFields').style.display = 'none';
    document.getElementById('customerId').value = '';
    document.getElementById('customerSearch').value = '';
}

function toggleFields() {
    const type = document.getElementById('txnType').value;
    document.getElementById('loanField').style.display = type === 'loan_repayment' ? 'block' : 'none';
}

function toggleSourceFields() {
    const src = document.getElementById('paySource').value;
    document.getElementById('mpesaFields').style.display = src === 'mpesa' ? 'block' : 'none';
    document.getElementById('bankFields').style.display = src === 'bank' ? 'block' : 'none';
}

let searchTimeout;
function searchCustomers(query) {
    clearTimeout(searchTimeout);
    if (query.length < 2) {
        document.getElementById('customerDropdown').style.display = 'none';
        return;
    }
    searchTimeout = setTimeout(() => {
        fetch(`/api/customers/search?q=${encodeURIComponent(query)}`, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => {
            const dd = document.getElementById('customerDropdown');
            if (!data.length) { dd.style.display = 'none'; return; }
            dd.innerHTML = data.map(c => `
                <div onclick="selectCustomer(${c.id}, '${c.full_name}', '${c.phone_number}')"
                     style="padding:10px 15px; cursor:pointer; border-bottom:1px solid var(--border); font-size:13px;"
                     onmouseover="this.style.background='#F0F4F8'" onmouseout="this.style.background='white'">
                    <strong>${c.full_name}</strong>
                    <span style="color:var(--text-secondary); margin-left:8px;">${c.phone_number}</span>
                    <span style="float:right; font-size:11px; color:var(--primary);">${c.customer_number}</span>
                </div>
            `).join('');
            dd.style.display = 'block';
        });
    }, 300);
}

function selectCustomer(id, name, phone) {
    document.getElementById('customerId').value = id;
    document.getElementById('customerSearch').value = name;
    document.getElementById('customerDropdown').style.display = 'none';
    document.getElementById('selectedCustomer').style.display = 'block';
    document.getElementById('selectedCustomer').innerHTML = `<i class="fas fa-user-check" style="color:var(--success);"></i> <strong>${name}</strong> &mdash; ${phone}`;

    // Load loans if repayment type selected
    if (document.getElementById('txnType').value === 'loan_repayment') {
        loadCustomerLoans(id);
    }
}

function loadCustomerLoans(customerId) {
    fetch(`/api/customers/${customerId}/active-loans`, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(loans => {
        const sel = document.getElementById('loanSelect');
        sel.innerHTML = loans.length
            ? loans.map(l => `<option value="${l.id}">${l.loan_number} — KSH ${Number(l.outstanding_balance).toLocaleString()} outstanding</option>`).join('')
            : '<option value="">No active loans found</option>';
    });
}

// Close dropdown on outside click
document.addEventListener('click', e => {
    if (!e.target.closest('#customerSearch') && !e.target.closest('#customerDropdown')) {
        document.getElementById('customerDropdown').style.display = 'none';
    }
});
</script>
@endsection
