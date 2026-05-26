{{-- resources/views/transactions/suspense.blade.php --}}
@extends('layouts.app')

@section('title', 'Suspense Accounts - Mweela Cash Capital')
@section('page-title', 'Suspense Accounts')

@section('content')

{{-- Summary Cards --}}
<div class="grid-4" style="margin-bottom: 20px;">
    <div class="card" style="border-left: 4px solid var(--danger);">
        <div class="metric-label">Unmatched Payments</div>
        <div class="metric-value" style="font-size: 22px; color: var(--danger);">{{ $unmatchedCount }}</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--warning);">
        <div class="metric-label">Total Unmatched Amount</div>
        <div class="metric-value" style="font-size: 22px; color: var(--warning);">KSH {{ number_format($unmatchedAmount, 0) }}</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--success);">
        <div class="metric-label">Matched Today</div>
        <div class="metric-value" style="font-size: 22px; color: var(--success);">{{ $matchedToday }}</div>
    </div>
    <div class="card" style="border-left: 4px solid var(--primary);">
        <div class="metric-label">Escalated</div>
        <div class="metric-value" style="font-size: 22px; color: var(--primary);">{{ $escalatedCount }}</div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card" style="margin-bottom: 20px;">
    <form method="GET" action="{{ route('transactions.suspense') }}">
        <div class="filter-row">
            <div style="flex: 1 1 130px;">
                <label style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Status</label>
                <select name="status" class="filter-select" style="width:100%;">
                    <option value="">All Status</option>
                    <option value="unmatched"  {{ request('status') === 'unmatched'  ? 'selected':'' }}>Unmatched</option>
                    <option value="matched"    {{ request('status') === 'matched'    ? 'selected':'' }}>Matched</option>
                    <option value="escalated"  {{ request('status') === 'escalated'  ? 'selected':'' }}>Escalated</option>
                    <option value="refunded"   {{ request('status') === 'refunded'   ? 'selected':'' }}>Refunded</option>
                </select>
            </div>
            <div style="flex: 1 1 120px;">
                <label style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Source</label>
                <select name="source" class="filter-select" style="width:100%;">
                    <option value="">All</option>
                    <option value="mpesa" {{ request('source') === 'mpesa' ? 'selected':'' }}>M-Pesa</option>
                    <option value="bank"  {{ request('source') === 'bank'  ? 'selected':'' }}>Bank</option>
                    <option value="cash"  {{ request('source') === 'cash'  ? 'selected':'' }}>Cash</option>
                </select>
            </div>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-select" style="flex: 1 1 130px;">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   class="filter-select" style="flex: 1 1 130px;">
            <div style="flex: 1 1 180px;">
                <label style="font-size: 11px; color: var(--text-secondary); display: block; margin-bottom: 4px;">Search</label>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Phone / M-Pesa / ID">
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-shrink: 0; align-items: flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> <span class="btn-label">Filter</span></button>
                <a href="{{ route('transactions.suspense') }}" class="btn btn-outline"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">Suspense Entries</span>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge badge-danger">{{ $suspenseEntries->total() }} Records</span>
            <button class="btn btn-primary" style="font-size: 12px;" onclick="openAddSuspenseModal()">
                <i class="fas fa-plus"></i> Add Entry
            </button>
        </div>
    </div>

    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Reference No.</th>
                <th>Source</th>
                <th>Ext. Reference</th>
                <th>Phone</th>
                <th>Bill Ref (ID)</th>
                <th>Amount (KSH)</th>
                <th>Payment Date</th>
                <th>Status</th>
                <th>Matched To</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suspenseEntries as $index => $entry)
            <tr>
                <td>{{ ($suspenseEntries->currentPage() - 1) * $suspenseEntries->perPage() + $index + 1 }}</td>
                <td style="font-family: monospace; font-size: 11px; font-weight: 600;">{{ $entry->reference_number }}</td>
                <td>
                    @if($entry->source === 'mpesa')
                        <span class="badge badge-success"><i class="fas fa-mobile-alt"></i> M-Pesa</span>
                    @elseif($entry->source === 'bank')
                        <span class="badge badge-primary"><i class="fas fa-university"></i> Bank</span>
                    @else
                        <span class="badge badge-warning"><i class="fas fa-money-bill"></i> Cash</span>
                    @endif
                </td>
                <td style="font-family: monospace; font-size: 11px;">{{ $entry->external_reference }}</td>
                <td style="font-size: 12px;">{{ $entry->phone_number ?? '—' }}</td>
                <td style="font-size: 12px;">{{ $entry->bill_reference ?? '—' }}</td>
                <td style="font-weight: 700; color: var(--warning);">{{ number_format($entry->amount, 0) }}</td>
                <td style="font-size: 12px; color: var(--text-secondary);">{{ $entry->payment_date->format('d M Y') }}</td>
                <td>
                    @if($entry->status === 'unmatched')
                        <span class="status status-pending">Unmatched</span>
                    @elseif($entry->status === 'matched')
                        <span class="status status-active">Matched</span>
                    @elseif($entry->status === 'escalated')
                        <span class="status" style="background:#FFF3E0; color:#E65100;">Escalated</span>
                    @else
                        <span class="status status-rejected">Refunded</span>
                    @endif
                </td>
                <td style="font-size: 12px;">
                    @if($entry->matchedCustomer)
                        <span style="color: var(--success);">
                            <i class="fas fa-user-check"></i>
                            {{ $entry->matchedCustomer->full_name }}
                        </span>
                    @else
                        <span style="color: var(--text-secondary);">—</span>
                    @endif
                </td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        @if($entry->status === 'unmatched')
                        <button class="btn btn-primary" style="padding: 4px 10px; font-size: 11px;"
                                onclick="openMatchModal({{ $entry->id }}, '{{ $entry->external_reference }}', {{ $entry->amount }})">
                            <i class="fas fa-link"></i> Match
                        </button>
                        <button class="btn btn-outline" style="padding: 4px 10px; font-size: 11px; color: var(--warning); border-color: var(--warning);"
                                onclick="escalateEntry({{ $entry->id }})">
                            <i class="fas fa-exclamation-triangle"></i>
                        </button>
                        @elseif($entry->status === 'matched')
                        <button class="btn btn-outline" style="padding: 4px 10px; font-size: 11px;" onclick="viewMatch({{ $entry->id }})">
                            <i class="fas fa-eye"></i> View
                        </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align: center; padding: 60px; color: var(--text-secondary);">
                    <i class="fas fa-check-circle" style="font-size: 48px; display: block; margin-bottom: 15px; opacity: 0.3; color: var(--success);"></i>
                    <p style="font-size: 15px;">No suspense entries found</p>
                    <p style="font-size: 12px; opacity: 0.7;">All payments have been matched</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    @if($suspenseEntries->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $suspenseEntries->firstItem() ?? 0 }}–{{ $suspenseEntries->lastItem() ?? 0 }} of {{ $suspenseEntries->total() }}</span>
        {{ $suspenseEntries->appends(request()->query())->links() }}
    </div>
    @endif
</div>

{{-- Match Modal --}}
<div id="matchModal" class="modal-overlay" onclick="if(event.target===this)closeMatchModal()">
    <div class="modal-box wide">
        <div class="modal-header">
            <div class="modal-title">Match Suspense Payment</div>
            <button class="modal-close" onclick="closeMatchModal()">&times;</button>
        </div>

        <div id="matchSummary" style="padding:12px; background:#FFF3E0; border-radius:8px; margin-bottom:20px; font-size:13px;"></div>

        <form id="matchForm" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="suspense_id" id="matchSuspenseId">

            <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Find Customer <span class="req">*</span></label>
                <input type="text" id="matchCustomerSearch" placeholder="Search by name, phone or ID..." class="form-control" autocomplete="off" oninput="searchMatchCustomers(this.value)">
                <input type="hidden" name="customer_id" id="matchCustomerId">
                <div id="matchCustomerDropdown" style="display:none; position:absolute; background:white; border:1px solid var(--border); border-radius:6px; left:0; right:0; max-height:180px; overflow-y:auto; z-index:3000; box-shadow:0 4px 12px rgba(0,0,0,0.1);"></div>
                <div id="matchSelectedCustomer" style="display:none; margin-top:8px; padding:10px; background:#E8F5E9; border-radius:6px; font-size:12px;"></div>
            </div>

            <div id="matchLoanField" class="form-group" style="display:none;">
                <label class="form-label">Apply to Loan</label>
                <select name="loan_id" id="matchLoanSelect" class="form-control">
                    <option value="">-- Select customer first --</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Resolution Notes</label>
                <textarea name="resolution_notes" rows="2" placeholder="Notes about this match..." class="form-control"></textarea>
            </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeMatchModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-link"></i> Confirm Match</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Suspense Entry Modal --}}
<div id="addSuspenseModal" class="modal-overlay" onclick="if(event.target===this)closeAddSuspenseModal()">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Add Suspense Entry</div>
            <button class="modal-close" onclick="closeAddSuspenseModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('transactions.suspense.store') }}">
            @csrf
            <div class="modal-body">
            <div class="grid-2" style="gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label class="form-label">Source <span class="req">*</span></label>
                    <select name="source" class="form-control" required>
                        <option value="">-- Select --</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">External Reference <span class="req">*</span></label>
                    <input type="text" name="external_reference" placeholder="M-Pesa code / Bank ref" class="form-control" style="text-transform:uppercase;" required>
                </div>
            </div>
            <div class="grid-2" style="gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone_number" placeholder="07XXXXXXXX" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Bill Reference (ID No.)</label>
                    <input type="text" name="bill_reference" placeholder="National ID number" class="form-control">
                </div>
            </div>
            <div class="grid-2" style="gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label class="form-label">Amount (KSH) <span class="req">*</span></label>
                    <input type="number" name="amount" min="1" step="0.01" placeholder="0.00" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Date <span class="req">*</span></label>
                    <input type="date" name="payment_date" value="{{ today()->toDateString() }}" class="form-control" required>
                </div>
            </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAddSuspenseModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Entry</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
function openAddSuspenseModal() {
    document.getElementById('addSuspenseModal').classList.add('show');
}
function closeAddSuspenseModal() {
    document.getElementById('addSuspenseModal').classList.remove('show');
}

function openMatchModal(id, ref, amount) {
    document.getElementById('matchSuspenseId').value = id;
    document.getElementById('matchSummary').innerHTML =
        `<i class="fas fa-info-circle" style="color:var(--warning);"></i>
         Matching payment <strong>${ref}</strong> of <strong>KSH ${Number(amount).toLocaleString()}</strong>`;
    document.getElementById('matchForm').action = `/transactions/suspense/${id}/match`;
    document.getElementById('matchModal').classList.add('show');
}
function closeMatchModal() {
    document.getElementById('matchModal').classList.remove('show');
    document.getElementById('matchCustomerSearch').value = '';
    document.getElementById('matchCustomerId').value = '';
    document.getElementById('matchSelectedCustomer').style.display = 'none';
    document.getElementById('matchLoanField').style.display = 'none';
}

let matchSearchTimeout;
function searchMatchCustomers(query) {
    clearTimeout(matchSearchTimeout);
    if (query.length < 2) { document.getElementById('matchCustomerDropdown').style.display = 'none'; return; }
    matchSearchTimeout = setTimeout(() => {
        fetch(`/api/customers/search?q=${encodeURIComponent(query)}`, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => {
            const dd = document.getElementById('matchCustomerDropdown');
            if (!data.length) { dd.style.display = 'none'; return; }
            dd.innerHTML = data.map(c => `
                <div onclick="selectMatchCustomer(${c.id}, '${c.full_name}', '${c.phone_number}')"
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

function selectMatchCustomer(id, name, phone) {
    document.getElementById('matchCustomerId').value = id;
    document.getElementById('matchCustomerSearch').value = name;
    document.getElementById('matchCustomerDropdown').style.display = 'none';
    document.getElementById('matchSelectedCustomer').style.display = 'block';
    document.getElementById('matchSelectedCustomer').innerHTML =
        `<i class="fas fa-user-check" style="color:var(--success);"></i> <strong>${name}</strong> &mdash; ${phone}`;
    document.getElementById('matchLoanField').style.display = 'block';

    fetch(`/api/customers/${id}/active-loans`, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(loans => {
        const sel = document.getElementById('matchLoanSelect');
        sel.innerHTML = '<option value="">-- Savings / No specific loan --</option>' +
            loans.map(l => `<option value="${l.id}">${l.loan_number} — KSH ${Number(l.outstanding_balance).toLocaleString()} outstanding</option>`).join('');
    });
}

function escalateEntry(id) {
    if (!confirm('Mark this entry as escalated?')) return;
    fetch(`/transactions/suspense/${id}/escalate`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}

document.addEventListener('click', e => {
    if (!e.target.closest('#matchCustomerSearch') && !e.target.closest('#matchCustomerDropdown')) {
        document.getElementById('matchCustomerDropdown').style.display = 'none';
    }
});
</script>
@endsection
