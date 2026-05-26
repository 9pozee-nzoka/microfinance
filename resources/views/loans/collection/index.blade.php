@extends('layouts.app')
@section('title', 'Loan Collection - GetCash Capital')
@section('page-title', 'Loan Collection')

@section('styles')
<style>
    .stat-card {
        background:#fff; border-radius:12px; padding:18px 20px;
        border:1px solid var(--border); box-shadow:0 2px 8px rgba(0,0,0,0.05);
        display:flex; align-items:center; gap:16px;
    }
    .stat-icon {
        width:48px; height:48px; border-radius:12px;
        display:flex; align-items:center; justify-content:center;
        font-size:20px; flex-shrink:0;
    }
    .par-badge {
        display:inline-flex; align-items:center; justify-content:center;
        border-radius:8px; padding:10px 16px; font-size:13px; font-weight:700;
        flex-direction:column; gap:2px; min-width:80px;
    }
    .sms-bubble {
        padding:8px 12px; border-radius:8px; font-size:12px;
        border:1px solid var(--border); background:#FAFBFC;
        display:flex; align-items:flex-start; gap:10px;
    }
    .action-btn {
        width:30px; height:30px; border-radius:7px; border:1px solid var(--border);
        background:#fff; display:inline-flex; align-items:center; justify-content:center;
        font-size:12px; cursor:pointer; transition:all 0.15s; text-decoration:none;
        color:var(--text-secondary);
    }
    .action-btn:hover { border-color:var(--primary); color:var(--primary); background:#E3F2FD; }
</style>
@endsection

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

{{-- ── Top Stats ── --}}
<div class="grid-4" style="margin-bottom:24px;">
    <div class="stat-card" style="border-left:4px solid var(--danger);">
        <div class="stat-icon" style="background:#FFEBEE;color:var(--danger);"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div style="font-size:26px;font-weight:700;color:var(--text-primary);line-height:1;">{{ number_format($totalOverdue) }}</div>
            <div style="font-size:12px;color:var(--text-secondary);margin-top:3px;">Overdue Loans</div>
            <div style="font-size:11px;color:var(--danger);margin-top:2px;">KSH {{ number_format($totalArrearsAmt, 0) }}</div>
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--warning);">
        <div class="stat-icon" style="background:#FFF3E0;color:var(--warning);"><i class="fas fa-calendar-day"></i></div>
        <div>
            <div style="font-size:26px;font-weight:700;color:var(--text-primary);line-height:1;">{{ $dueToday->count() }}</div>
            <div style="font-size:12px;color:var(--text-secondary);margin-top:3px;">Due Today</div>
            <div style="font-size:11px;color:var(--warning);margin-top:2px;">KSH {{ number_format($totalDueToday, 0) }}</div>
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--primary);">
        <div class="stat-icon" style="background:#E3F2FD;color:var(--primary);"><i class="fas fa-sms"></i></div>
        <div>
            <div style="font-size:26px;font-weight:700;color:var(--text-primary);line-height:1;">{{ number_format($smsSentToday) }}</div>
            <div style="font-size:12px;color:var(--text-secondary);margin-top:3px;">SMS Sent Today</div>
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--success);">
        <div class="stat-icon" style="background:#E8F5E9;color:var(--success);"><i class="fas fa-calendar-alt"></i></div>
        <div>
            <div style="font-size:26px;font-weight:700;color:var(--text-primary);line-height:1;">{{ number_format($schedulesActive) }}</div>
            <div style="font-size:12px;color:var(--text-secondary);margin-top:3px;">Active Schedules</div>
        </div>
    </div>
</div>

{{-- ── PAR Buckets + Quick Actions ── --}}
<div class="grid-2" style="margin-bottom:24px;gap:20px;">

    {{-- PAR Buckets --}}
    <div class="card">
        <div style="font-size:13px;font-weight:600;color:var(--text-primary);margin-bottom:16px;">
            <i class="fas fa-chart-bar" style="color:var(--danger);margin-right:6px;"></i>Portfolio at Risk
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <div class="par-badge" style="background:#FFF3E0;color:#E65100;">
                <span style="font-size:22px;font-weight:800;">{{ $par30 }}</span>
                <span style="font-size:10px;font-weight:600;">PAR 1–30</span>
            </div>
            <div class="par-badge" style="background:#FBE9E7;color:#BF360C;">
                <span style="font-size:22px;font-weight:800;">{{ $par60 }}</span>
                <span style="font-size:10px;font-weight:600;">PAR 31–60</span>
            </div>
            <div class="par-badge" style="background:#FFEBEE;color:#C62828;">
                <span style="font-size:22px;font-weight:800;">{{ $par90 }}</span>
                <span style="font-size:10px;font-weight:600;">PAR 61–90</span>
            </div>
            <div class="par-badge" style="background:#B71C1C;color:#fff;">
                <span style="font-size:22px;font-weight:800;">{{ $par90p }}</span>
                <span style="font-size:10px;font-weight:600;">PAR 90+</span>
            </div>
        </div>
        <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('collection.overdue') }}" class="btn btn-outline" style="font-size:12px;padding:6px 14px;">
                <i class="fas fa-list"></i> View All Overdue
            </a>
            <button class="btn btn-primary" style="font-size:12px;padding:6px 14px;" onclick="openBulkModal()">
                <i class="fas fa-paper-plane"></i> Send Bulk SMS
            </button>
        </div>
    </div>

    {{-- Quick SMS ── --}}
    <div class="card">
        <div style="font-size:13px;font-weight:600;color:var(--text-primary);margin-bottom:16px;">
            <i class="fas fa-bolt" style="color:var(--warning);margin-right:6px;"></i>Quick SMS
        </div>
        <form method="POST" action="{{ route('collection.sms.send') }}">
            @csrf
            <input type="hidden" name="recipient_type" value="custom">
            <div style="margin-bottom:12px;">
                <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px;">Phone Number</label>
                <input type="text" name="phone_number" class="filter-select" style="width:100%;" placeholder="07XXXXXXXX" required>
            </div>
            <div style="margin-bottom:12px;">
                <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px;">Message Type</label>
                <select name="message_type" class="filter-select" style="width:100%;">
                    <option value="payment_reminder">Payment Reminder</option>
                    <option value="overdue_notice">Overdue Notice</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div style="margin-bottom:12px;">
                <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px;">Message <span id="charCount" style="color:var(--text-secondary);font-weight:400;">(0/160)</span></label>
                <textarea name="message" rows="3" id="quickMsg" oninput="countChars(this)"
                          style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;resize:vertical;"
                          placeholder="Type your message…" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">
                <i class="fas fa-paper-plane"></i> Send Now
            </button>
        </form>
    </div>
</div>

{{-- ── Due Today + Overdue side by side ── --}}
<div class="grid-2" style="margin-bottom:24px;gap:20px;">

    {{-- Due Today --}}
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div style="font-size:13px;font-weight:600;color:var(--text-primary);">
                <i class="fas fa-calendar-day" style="color:var(--warning);margin-right:6px;"></i>Due Today ({{ $dueToday->count() }})
            </div>
            <a href="{{ route('collection.overdue') }}" style="font-size:12px;color:var(--primary);">View all →</a>
        </div>
        <div style="max-height:320px;overflow-y:auto;">
            @forelse($dueToday as $loan)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);">
                <div>
                    <div style="font-weight:600;font-size:13px;">{{ $loan->customer->full_name }}</div>
                    <div style="font-size:11px;color:var(--text-secondary);">
                        {{ $loan->loan_number }} · {{ $loan->customer->phone_number }}
                    </div>
                </div>
                <div style="text-align:right;display:flex;align-items:center;gap:8px;">
                    <div>
                        <div style="font-weight:700;font-size:13px;color:var(--warning);">KSH {{ number_format($loan->weekly_installment, 0) }}</div>
                        <div style="font-size:10px;color:var(--text-secondary);">due today</div>
                    </div>
                    <button class="action-btn" title="Send Reminder"
                            onclick="openLoanSmsModal({{ $loan->id }}, '{{ addslashes($loan->customer->full_name) }}', '{{ $loan->loan_number }}')">
                        <i class="fas fa-sms"></i>
                    </button>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:30px;color:var(--text-secondary);font-size:13px;">
                <i class="fas fa-check-circle" style="font-size:28px;color:var(--success);display:block;margin-bottom:8px;"></i>
                No loans due today
            </div>
            @endforelse
        </div>
    </div>

    {{-- Top Overdue --}}
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <div style="font-size:13px;font-weight:600;color:var(--text-primary);">
                <i class="fas fa-exclamation-triangle" style="color:var(--danger);margin-right:6px;"></i>Most Overdue
            </div>
            <a href="{{ route('collection.overdue') }}" style="font-size:12px;color:var(--primary);">View all →</a>
        </div>
        <div style="max-height:320px;overflow-y:auto;">
            @forelse($overdueLoans as $loan)
            @php $dColor = $loan->days_in_arrears > 90 ? 'var(--danger)' : ($loan->days_in_arrears > 30 ? '#FF5722' : 'var(--warning)'); @endphp
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);">
                <div>
                    <div style="font-weight:600;font-size:13px;">{{ $loan->customer->full_name }}</div>
                    <div style="font-size:11px;color:var(--text-secondary);">
                        {{ $loan->loan_number }} · {{ $loan->customer->phone_number }}
                    </div>
                </div>
                <div style="text-align:right;display:flex;align-items:center;gap:8px;">
                    <div>
                        <div style="font-weight:700;font-size:13px;color:{{ $dColor }};">{{ $loan->days_in_arrears }}d</div>
                        <div style="font-size:10px;color:var(--text-secondary);">KSH {{ number_format($loan->arrears_amount, 0) }}</div>
                    </div>
                    <button class="action-btn" title="Send Overdue Notice"
                            onclick="openLoanSmsModal({{ $loan->id }}, '{{ addslashes($loan->customer->full_name) }}', '{{ $loan->loan_number }}')">
                        <i class="fas fa-sms"></i>
                    </button>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:30px;color:var(--text-secondary);font-size:13px;">
                <i class="fas fa-check-circle" style="font-size:28px;color:var(--success);display:block;margin-bottom:8px;"></i>
                No overdue loans
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ── Recent SMS Activity ── --}}
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <div style="font-size:13px;font-weight:600;color:var(--text-primary);">
            <i class="fas fa-history" style="color:var(--primary);margin-right:6px;"></i>Recent SMS Activity
        </div>
        <a href="{{ route('collection.sms-logs') }}" style="font-size:12px;color:var(--primary);">View all logs →</a>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;">
        @forelse($recentSms as $sms)
        @php
            $sc = match($sms->status) { 'sent' => ['#E8F5E9','#2E7D32','fa-check-circle'], 'failed' => ['#FFEBEE','#C62828','fa-times-circle'], 'pending' => ['#E3F2FD','#1565C0','fa-clock'], default => ['#F5F5F5','#757575','fa-ban'] };
        @endphp
        <div class="sms-bubble">
            <i class="fas {{ $sc[2] }}" style="color:{{ $sc[1] }};margin-top:2px;font-size:14px;"></i>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:4px;">
                    <span style="font-weight:600;font-size:13px;">{{ $sms->customer->full_name ?? $sms->phone_number }}</span>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="badge" style="background:{{ $sc[0] }};color:{{ $sc[1] }};font-size:10px;">{{ ucfirst($sms->status) }}</span>
                        <span style="font-size:11px;color:var(--text-secondary);">{{ $sms->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div style="font-size:12px;color:var(--text-secondary);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $sms->message }}
                </div>
                <div style="font-size:11px;color:var(--text-secondary);margin-top:2px;">
                    {{ $sms->phone_number }}
                    @if($sms->at_cost) · KES {{ $sms->at_cost }} @endif
                    @if($sms->is_bulk) · <span style="color:var(--primary);">Bulk</span> @endif
                </div>
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:30px;color:var(--text-secondary);font-size:13px;">No SMS activity yet</div>
        @endforelse
    </div>
</div>

{{-- ── Loan SMS Modal ── --}}
<div id="loanSmsModal" class="modal-overlay" onclick="if(event.target===this)closeModal('loanSmsModal')">
    <div style="background:white;border-radius:12px;padding:28px;width:500px;max-width:95%;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:600;">Send SMS</h3>
            <button onclick="closeLoanSmsModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-secondary);">&times;</button>
        </div>
        <div id="loanSmsInfo" style="background:#E3F2FD;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:13px;"></div>
        <form method="POST" action="{{ route('collection.sms.send') }}">
            @csrf
            <input type="hidden" name="recipient_type" value="loan">
            <input type="hidden" name="loan_id" id="modalLoanId">
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Message Type</label>
                <select name="message_type" id="modalMsgType" class="filter-select" style="width:100%;" onchange="loadTemplate()">
                    <option value="payment_reminder">Payment Reminder</option>
                    <option value="overdue_notice">Overdue Notice</option>
                    <option value="payment_received">Payment Received</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">
                    Message <span id="modalCharCount" style="color:var(--text-secondary);font-weight:400;">(0/459)</span>
                </label>
                <textarea name="message" id="modalMessage" rows="4" oninput="countModalChars(this)"
                          style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:13px;resize:vertical;"
                          placeholder="Type your message…" required></textarea>
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Schedule (optional)</label>
                <input type="datetime-local" name="scheduled_at" class="filter-select" style="width:100%;">
                <div style="font-size:11px;color:var(--text-secondary);margin-top:3px;">Leave blank to send immediately</div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeLoanSmsModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send SMS</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Bulk SMS Modal ── --}}
<div id="bulkSmsModal" class="modal-overlay" onclick="if(event.target===this)closeModal('bulkSmsModal')">
    <div style="background:white;border-radius:12px;padding:28px;width:520px;max-width:95%;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 style="font-size:15px;font-weight:600;"><i class="fas fa-paper-plane" style="color:var(--primary);"></i> Send Bulk SMS</h3>
            <button onclick="closeBulkModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-secondary);">&times;</button>
        </div>
        <form method="POST" action="{{ route('collection.sms.bulk') }}">
            @csrf
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Target Group <span style="color:var(--danger)">*</span></label>
                <select name="target" class="filter-select" style="width:100%;" required>
                    <option value="due_today">Due Today</option>
                    <option value="overdue">All Overdue</option>
                    <option value="par30">PAR 1–30 days</option>
                    <option value="par60">PAR 31–60 days</option>
                    <option value="par90plus">PAR 90+ days</option>
                    <option value="all_active">All Active Loans</option>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Message Type <span style="color:var(--danger)">*</span></label>
                <select name="message_type" class="filter-select" style="width:100%;" required>
                    <option value="payment_reminder">Payment Reminder</option>
                    <option value="overdue_notice">Overdue Notice</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">
                    Message <span style="color:var(--danger)">*</span>
                    <span id="bulkCharCount" style="color:var(--text-secondary);font-weight:400;">(0/459)</span>
                </label>
                <textarea name="message" rows="4" oninput="countBulkChars(this)"
                          style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-size:13px;resize:vertical;"
                          placeholder="Dear customer, your loan payment of KSH {amount} is due on {date}. Please pay to avoid penalties." required></textarea>
                <div style="font-size:11px;color:var(--text-secondary);margin-top:4px;">
                    <strong>Tip:</strong> This message goes to all matched customers. Keep it concise.
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:5px;">Schedule (optional)</label>
                <input type="datetime-local" name="scheduled_at" class="filter-select" style="width:100%;">
                <div style="font-size:11px;color:var(--text-secondary);margin-top:3px;">Leave blank to send immediately via queue</div>
            </div>
            <div style="background:#FFF3E0;border:1px solid #FFE0B2;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#E65100;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong> This will send SMS to all matched customers. Charges apply per message.
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeBulkModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Bulk SMS</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
const templates = {
    payment_reminder: 'Dear {name}, your loan {loan_number} payment of KSH {amount_due} is due on {due_date}. Please pay on time to avoid penalties. GetCash Capital.',
    overdue_notice:   'Dear {name}, your loan {loan_number} is {days_overdue} days overdue. Outstanding: KSH {outstanding}. Please pay immediately to avoid further charges. GetCash Capital.',
    payment_received: 'Dear {name}, we have received your payment for loan {loan_number}. Outstanding balance: KSH {outstanding}. Thank you. GetCash Capital.',
    custom: '',
};

function countChars(el) {
    document.getElementById('charCount').textContent = `(${el.value.length}/160)`;
}
function countModalChars(el) {
    document.getElementById('modalCharCount').textContent = `(${el.value.length}/459)`;
}
function countBulkChars(el) {
    document.getElementById('bulkCharCount').textContent = `(${el.value.length}/459)`;
}

function openLoanSmsModal(loanId, name, loanNo) {
    document.getElementById('modalLoanId').value = loanId;
    document.getElementById('loanSmsInfo').innerHTML =
        `<i class="fas fa-user" style="color:var(--primary);"></i> <strong>${name}</strong> &nbsp;·&nbsp; ${loanNo}`;
    loadTemplate();
    document.getElementById('loanSmsModal').classList.add('show');
}
function closeLoanSmsModal() {
    document.getElementById('loanSmsModal').classList.remove('show');
}
function loadTemplate() {
    const type = document.getElementById('modalMsgType').value;
    const msg  = document.getElementById('modalMessage');
    if (templates[type]) {
        msg.value = templates[type];
        countModalChars(msg);
    }
}

function openBulkModal()  { document.getElementById('bulkSmsModal').classList.add('show'); }
function closeBulkModal() { document.getElementById('bulkSmsModal').classList.remove('show'); }

// Close modals on backdrop click
['loanSmsModal','bulkSmsModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
@endsection
