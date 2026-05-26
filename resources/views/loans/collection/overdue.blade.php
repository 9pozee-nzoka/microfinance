@extends('layouts.app')
@section('title', 'Overdue Loans - GetCash Capital')
@section('page-title', 'Overdue Loans')

@section('styles')
<style>
    .filter-input { padding:8px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;background:#fff;outline:none;transition:border-color 0.15s;height:38px; }
    .filter-input:focus { border-color:var(--primary); }
    .action-btn { width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px;cursor:pointer;transition:all 0.15s;text-decoration:none;color:var(--text-secondary); }
    .action-btn:hover { border-color:var(--primary);color:var(--primary);background:#E3F2FD; }
    .days-badge { display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700; }
</style>
@endsection

@section('content')

@if(session('success'))
<div class="flash-success">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

{{-- Stats --}}
<div class="grid-4" style="margin-bottom:20px;">
    <div class="card" style="border-left:4px solid var(--danger);padding:16px 20px;">
        <div style="font-size:11px;color:var(--text-secondary);margin-bottom:4px;">Total Overdue</div>
        <div style="font-size:24px;font-weight:700;color:var(--danger);">{{ number_format($loans->total()) }}</div>
    </div>
    <div class="card" style="border-left:4px solid var(--warning);padding:16px 20px;">
        <div style="font-size:11px;color:var(--text-secondary);margin-bottom:4px;">Total Arrears</div>
        <div style="font-size:20px;font-weight:700;color:var(--warning);">KSH {{ number_format($totalArrears, 0) }}</div>
    </div>
    <div class="card" style="border-left:4px solid #FF5722;padding:16px 20px;">
        <div style="font-size:11px;color:var(--text-secondary);margin-bottom:4px;">PAR 30+ days</div>
        <div style="font-size:24px;font-weight:700;color:#FF5722;">
            {{ $loans->getCollection()->where('days_in_arrears', '>', 30)->count() }}
        </div>
    </div>
    <div class="card" style="border-left:4px solid #B71C1C;padding:16px 20px;">
        <div style="font-size:11px;color:var(--text-secondary);margin-bottom:4px;">PAR 90+ days</div>
        <div style="font-size:24px;font-weight:700;color:#B71C1C;">
            {{ $loans->getCollection()->where('days_in_arrears', '>', 90)->count() }}
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('collection.overdue') }}">
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
            <div style="position:relative;flex:1;min-width:200px;max-width:260px;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-secondary);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, phone, loan no…"
                       class="filter-input" style="width:100%;padding-left:36px;">
            </div>
            <select name="branch" class="filter-input" style="min-width:150px;">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
            <select name="product" class="filter-input" style="min-width:170px;">
                <option value="">All Products</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
            <select name="min_days" class="filter-input" style="min-width:150px;">
                <option value="">All Overdue</option>
                <option value="1"  {{ request('min_days') == 1  ? 'selected' : '' }}>1+ days</option>
                <option value="30" {{ request('min_days') == 30 ? 'selected' : '' }}>30+ days</option>
                <option value="60" {{ request('min_days') == 60 ? 'selected' : '' }}>60+ days</option>
                <option value="90" {{ request('min_days') == 90 ? 'selected' : '' }}>90+ days</option>
            </select>
            <button type="submit" class="btn btn-primary" style="height:38px;padding:0 18px;"><i class="fas fa-search"></i> Filter</button>
            <a href="{{ route('collection.overdue') }}" class="btn btn-outline" style="height:38px;padding:0 14px;"><i class="fas fa-undo"></i></a>
            <button type="button" class="btn btn-primary" style="height:38px;padding:0 14px;background:#FF5722;border-color:#FF5722;" onclick="openBulkModal()">
                <i class="fas fa-paper-plane"></i> Bulk SMS
            </button>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <span style="font-size:14px;font-weight:600;">{{ $loans->total() }} overdue loans</span>
    </div>
    <div style="overflow-x:auto;">
        <div class="table-wrap">
        <table class="data-table" style="min-width:1100px;">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" style="accent-color:var(--primary);cursor:pointer;"></th>
                    <th>#</th><th>Customer</th><th>Loan No.</th><th>Product</th>
                    <th>Outstanding</th><th>Arrears</th><th>Days Overdue</th>
                    <th>Next Due</th><th>Branch</th><th>Officer</th><th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $i => $loan)
                @php
                    $d = $loan->days_in_arrears;
                    $dColor = $d > 90 ? ['#B71C1C','#FFEBEE'] : ($d > 60 ? ['#C62828','#FFEBEE'] : ($d > 30 ? ['#BF360C','#FBE9E7'] : ['#E65100','#FFF3E0']));
                @endphp
                <tr>
                    <td><input type="checkbox" class="row-check" data-loan="{{ $loan->id }}" data-phone="{{ $loan->customer->phone_number }}" style="accent-color:var(--primary);cursor:pointer;"></td>
                    <td style="font-size:12px;color:var(--text-secondary);">{{ ($loans->currentPage()-1)*$loans->perPage()+$i+1 }}</td>
                    <td>
                        <div style="font-weight:600;font-size:13px;">{{ $loan->customer->full_name }}</div>
                        <div style="font-size:11px;color:var(--text-secondary);">{{ $loan->customer->phone_number }}</div>
                    </td>
                    <td>
                        <a href="{{ route('loans.show', $loan) }}" style="font-family:monospace;font-size:12px;color:var(--primary);font-weight:600;">
                            {{ $loan->loan_number }}
                        </a>
                    </td>
                    <td style="font-size:12px;">{{ $loan->product->name ?? '—' }}</td>
                    <td style="font-weight:700;color:var(--primary);">KSH {{ number_format($loan->outstanding_balance, 0) }}</td>
                    <td style="font-weight:700;color:{{ $dColor[0] }};">KSH {{ number_format($loan->arrears_amount, 0) }}</td>
                    <td>
                        <span class="days-badge" style="background:{{ $dColor[1] }};color:{{ $dColor[0] }};">
                            {{ $d }} days
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--text-secondary);">{{ $loan->next_due_date?->format('d M Y') ?? '—' }}</td>
                    <td style="font-size:12px;">{{ $loan->branch->name ?? '—' }}</td>
                    <td style="font-size:12px;">{{ $loan->relationshipOfficer->name ?? '—' }}</td>
                    <td>
                        <div style="display:flex;gap:5px;justify-content:center;">
                            <a href="{{ route('loans.show', $loan) }}" class="action-btn" title="View Loan"><i class="fas fa-eye"></i></a>
                            <button class="action-btn" title="Send SMS"
                                    onclick="openLoanSmsModal({{ $loan->id }}, '{{ addslashes($loan->customer->full_name) }}', '{{ $loan->loan_number }}', {{ $loan->days_in_arrears }})">
                                <i class="fas fa-sms"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" style="text-align:center;padding:60px;color:var(--text-secondary);">
                        <i class="fas fa-check-circle" style="font-size:48px;color:var(--success);display:block;margin-bottom:12px;opacity:0.5;"></i>
                        No overdue loans found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    @if($loans->hasPages())
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 4px 4px;border-top:1px solid var(--border);margin-top:8px;">
        <span style="font-size:12px;color:var(--text-secondary);">Showing {{ $loans->firstItem() }}–{{ $loans->lastItem() }} of {{ $loans->total() }}</span>
        {{ $loans->links() }}
    </div>
    @endif
</div>

{{-- Loan SMS Modal --}}
<div id="loanSmsModal" class="modal-overlay" onclick="if(event.target===this)closeModal('loanSmsModal')">
    <div style="background:white;border-radius:12px;padding:28px;width:500px;max-width:95%;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:600;">Send SMS</h3>
            <button onclick="closeLoanSmsModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-secondary);">&times;</button>
        </div>
        <div id="loanSmsInfo" style="background:#FFEBEE;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;color:#C62828;"></div>
        <form method="POST" action="{{ route('collection.sms.send') }}">
            @csrf
            <input type="hidden" name="recipient_type" value="loan">
            <input type="hidden" name="loan_id" id="modalLoanId">
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Message Type</label>
                <select name="message_type" id="modalMsgType" class="filter-select" style="width:100%;" onchange="loadTemplate()">
                    <option value="overdue_notice">Overdue Notice</option>
                    <option value="payment_reminder">Payment Reminder</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">
                    Message <span id="modalCharCount" style="color:var(--text-secondary);font-weight:400;">(0/459)</span>
                </label>
                <textarea name="message" id="modalMessage" rows="4" oninput="countChars(this,'modalCharCount')"
                          style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:13px;resize:vertical;" required></textarea>
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Schedule (optional)</label>
                <input type="datetime-local" name="scheduled_at" class="filter-select" style="width:100%;">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeLoanSmsModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
            </div>
        </form>
    </div>
</div>

{{-- Bulk SMS Modal --}}
<div id="bulkSmsModal" class="modal-overlay" onclick="if(event.target===this)closeModal('bulkSmsModal')">
    <div style="background:white;border-radius:12px;padding:28px;width:520px;max-width:95%;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:600;"><i class="fas fa-paper-plane" style="color:var(--danger);"></i> Bulk SMS — Overdue</h3>
            <button onclick="closeBulkModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-secondary);">&times;</button>
        </div>
        <form method="POST" action="{{ route('collection.sms.bulk') }}">
            @csrf
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Target Group</label>
                <select name="target" class="filter-select" style="width:100%;">
                    <option value="overdue">All Overdue</option>
                    <option value="par30">PAR 1–30 days</option>
                    <option value="par60">PAR 31–60 days</option>
                    <option value="par90plus">PAR 90+ days</option>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Message Type</label>
                <select name="message_type" class="filter-select" style="width:100%;">
                    <option value="overdue_notice">Overdue Notice</option>
                    <option value="payment_reminder">Payment Reminder</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">
                    Message <span id="bulkCharCount" style="color:var(--text-secondary);font-weight:400;">(0/459)</span>
                </label>
                <textarea name="message" rows="4" oninput="countChars(this,'bulkCharCount')"
                          style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:13px;resize:vertical;"
                          placeholder="Dear customer, your loan is overdue. Please pay immediately to avoid further penalties. GetCash Capital." required></textarea>
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Schedule (optional)</label>
                <input type="datetime-local" name="scheduled_at" class="filter-select" style="width:100%;">
            </div>
            <div style="background:#FFF3E0;border:1px solid #FFE0B2;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#E65100;">
                <i class="fas fa-exclamation-triangle"></i> SMS charges apply per recipient.
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeBulkModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:var(--danger);"><i class="fas fa-paper-plane"></i> Send Bulk</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
const templates = {
    overdue_notice:   'Dear {name}, your loan {loan_number} is {days_overdue} days overdue. Outstanding: KSH {outstanding}. Please pay immediately. GetCash Capital.',
    payment_reminder: 'Dear {name}, your loan {loan_number} payment of KSH {amount_due} is due on {due_date}. Please pay on time. GetCash Capital.',
    custom: '',
};

function countChars(el, counterId) {
    document.getElementById(counterId).textContent = `(${el.value.length}/459)`;
}

function openLoanSmsModal(loanId, name, loanNo, days) {
    document.getElementById('modalLoanId').value = loanId;
    document.getElementById('loanSmsInfo').innerHTML =
        `<i class="fas fa-exclamation-triangle"></i> <strong>${name}</strong> &nbsp;·&nbsp; ${loanNo} &nbsp;·&nbsp; <strong>${days} days overdue</strong>`;
    loadTemplate();
    document.getElementById('loanSmsModal').classList.add('show');
}
function closeLoanSmsModal() { document.getElementById('loanSmsModal').classList.remove('show'); }

function loadTemplate() {
    const type = document.getElementById('modalMsgType').value;
    const msg  = document.getElementById('modalMessage');
    msg.value  = templates[type] || '';
    countChars(msg, 'modalCharCount');
}

function openBulkModal()  { document.getElementById('bulkSmsModal').classList.add('show'); }
function closeBulkModal() { document.getElementById('bulkSmsModal').classList.remove('show'); }

// Select all
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
});
</script>
@endsection
