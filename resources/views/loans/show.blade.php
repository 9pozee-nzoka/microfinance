@extends('layouts.app')

@section('title', 'Loan ' . $loan->loan_number . ' - GetCash Capital')
@section('page-title', 'Loan Detail')

@section('styles')
<style>
    .detail-label { font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px; }
    .detail-value { font-size: 14px; font-weight: 500; color: var(--text-primary); }
    .section-title { font-size: 13px; font-weight: 600; color: var(--text-primary); margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid var(--border); }
    .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; }
    .schedule-row-paid { background: #F1F8E9; }
    .schedule-row-overdue { background: #FFF8E1; }
</style>
@endsection

@section('content')

{{-- Flash --}}
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

{{-- Back + Actions --}}
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <a href="{{ route('loans.index') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back to Loans
    </a>
    <div style="display:flex; gap:8px;">
        @if(in_array($loan->status, ['pending','under_review','partially_approved']))
        <button class="btn btn-primary" onclick="openApproveModal()">
            <i class="fas fa-check"></i> Approve
        </button>
        <button class="btn btn-outline" style="color:var(--danger); border-color:var(--danger);" onclick="openRejectModal()">
            <i class="fas fa-times"></i> Reject
        </button>
        @endif
        @if($loan->status === 'approved')
        <button class="btn btn-primary" onclick="openDisburseModal()">
            <i class="fas fa-paper-plane"></i> Disburse
        </button>
        @endif
        <button class="btn btn-outline" onclick="openSmsModal()" style="color:#7B1FA2; border-color:#CE93D8;">
            <i class="fas fa-sms"></i> Send SMS
        </button>
    </div>
</div>

{{-- Status Banner --}}
@php
    $bannerColor = match($loan->status) {
        'active','disbursed' => ['#E8F5E9','#2E7D32'],
        'approved'           => ['#E3F2FD','#1565C0'],
        'pending','under_review','partially_approved' => ['#FFF3E0','#E65100'],
        'completed'          => ['#F3E5F5','#6A1B9A'],
        'rejected','defaulted','written_off' => ['#FFEBEE','#C62828'],
        default              => ['#F5F5F5','#757575'],
    };
@endphp
<div style="background:{{ $bannerColor[0] }}; border-radius:10px; padding:14px 20px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">
    <div>
        <span style="font-size:18px; font-weight:700; color:{{ $bannerColor[1] }};">{{ $loan->loan_number }}</span>
        <span class="status" style="margin-left:12px; background:{{ $bannerColor[0] }}; color:{{ $bannerColor[1] }}; border:1px solid {{ $bannerColor[1] }}40;">
            {{ ucfirst(str_replace('_',' ',$loan->status)) }}
        </span>
    </div>
    <div style="text-align:right;">
        <div style="font-size:22px; font-weight:700; color:{{ $bannerColor[1] }};">KSH {{ number_format($loan->principal_amount, 0) }}</div>
        <div style="font-size:12px; color:var(--text-secondary);">Principal Amount</div>
    </div>
</div>

<div class="grid-2" style="gap:20px; margin-bottom:20px;">

    {{-- ── Loan Details ── --}}
    <div class="card">
        <div class="section-title"><i class="fas fa-file-alt" style="color:var(--primary); margin-right:6px;"></i>Loan Details</div>
        <div class="info-grid">
            <div><div class="detail-label">Product</div><div class="detail-value">{{ $loan->product->name ?? '—' }}</div></div>
            <div><div class="detail-label">Interest Rate</div><div class="detail-value">{{ $loan->product->interest_rate ?? '—' }}% ({{ $loan->product->interest_method ?? '' }})</div></div>
            <div><div class="detail-label">Term</div><div class="detail-value">{{ $loan->term_weeks }} weeks</div></div>
            <div><div class="detail-label">Weekly Installment</div><div class="detail-value">KSH {{ number_format($loan->weekly_installment, 0) }}</div></div>
            <div><div class="detail-label">Interest Amount</div><div class="detail-value">KSH {{ number_format($loan->interest_amount, 0) }}</div></div>
            <div><div class="detail-label">Processing Fee</div><div class="detail-value">KSH {{ number_format($loan->processing_fee, 0) }}</div></div>
            <div><div class="detail-label">Insurance Fee</div><div class="detail-value">KSH {{ number_format($loan->insurance_fee, 0) }}</div></div>
            <div><div class="detail-label">Total Repayable</div><div class="detail-value" style="color:var(--primary); font-weight:700;">KSH {{ number_format($loan->total_repayable, 0) }}</div></div>
            <div><div class="detail-label">Purpose</div><div class="detail-value">{{ ucfirst($loan->purpose ?? '—') }}</div></div>
            <div><div class="detail-label">Application Date</div><div class="detail-value">{{ $loan->application_date?->format('d M Y') ?? '—' }}</div></div>
            <div><div class="detail-label">Disbursement Date</div><div class="detail-value">{{ $loan->disbursement_date?->format('d M Y') ?? 'Not yet' }}</div></div>
            <div><div class="detail-label">Maturity Date</div><div class="detail-value">{{ $loan->maturity_date?->format('d M Y') ?? '—' }}</div></div>
        </div>
    </div>

    {{-- ── Customer Details ── --}}
    <div class="card">
        <div class="section-title"><i class="fas fa-user" style="color:var(--primary); margin-right:6px;"></i>Customer</div>
        <div class="info-grid">
            <div><div class="detail-label">Full Name</div><div class="detail-value">{{ $loan->customer->full_name }}</div></div>
            <div><div class="detail-label">Phone</div><div class="detail-value">{{ $loan->customer->phone_number }}</div></div>
            <div><div class="detail-label">ID Number</div><div class="detail-value">{{ $loan->customer->id_number }}</div></div>
            <div><div class="detail-label">Customer No.</div><div class="detail-value" style="font-family:monospace;">{{ $loan->customer->customer_number }}</div></div>
            <div><div class="detail-label">Branch</div><div class="detail-value">{{ $loan->branch->name ?? '—' }}</div></div>
            <div><div class="detail-label">Officer</div><div class="detail-value">{{ $loan->relationshipOfficer->name ?? '—' }}</div></div>
            <div><div class="detail-label">Credit Score</div>
                <div class="detail-value" style="color:{{ $loan->customer->credit_score >= 650 ? 'var(--success)' : ($loan->customer->credit_score >= 500 ? 'var(--warning)' : 'var(--danger)') }}; font-weight:700;">
                    {{ $loan->customer->credit_score }}
                </div>
            </div>
            <div><div class="detail-label">Savings Balance</div><div class="detail-value">KSH {{ number_format($loan->customer->savings_balance, 0) }}</div></div>
        </div>
    </div>
</div>

{{-- ── Repayment Progress ── --}}
@php
    $progress = $loan->total_repayable > 0 ? min(100, round(($loan->total_paid / $loan->total_repayable) * 100, 1)) : 0;
@endphp
<div class="card" style="margin-bottom:20px;">
    <div class="section-title"><i class="fas fa-chart-line" style="color:var(--primary); margin-right:6px;"></i>Repayment Progress</div>
    <div class="grid-4" style="margin-bottom:16px;">
        <div><div class="detail-label">Total Repayable</div><div class="detail-value" style="font-size:18px;">KSH {{ number_format($loan->total_repayable, 0) }}</div></div>
        <div><div class="detail-label">Total Paid</div><div class="detail-value" style="font-size:18px; color:var(--success);">KSH {{ number_format($loan->total_paid, 0) }}</div></div>
        <div><div class="detail-label">Outstanding Balance</div><div class="detail-value" style="font-size:18px; color:var(--primary);">KSH {{ number_format($loan->outstanding_balance, 0) }}</div></div>
        <div><div class="detail-label">Arrears</div><div class="detail-value" style="font-size:18px; color:{{ $loan->arrears_amount > 0 ? 'var(--danger)' : 'var(--success)' }};">KSH {{ number_format($loan->arrears_amount, 0) }}</div></div>
    </div>
    <div style="height:12px; background:#E8ECF1; border-radius:6px; overflow:hidden; margin-bottom:6px;">
        <div style="width:{{ $progress }}%; height:100%; background:{{ $progress >= 100 ? '#4CAF50' : ($progress >= 50 ? 'var(--primary)' : 'var(--warning)') }}; border-radius:6px; transition:width 0.5s;"></div>
    </div>
    <div style="font-size:12px; color:var(--text-secondary);">{{ $progress }}% repaid &nbsp;·&nbsp; Next due: {{ $loan->next_due_date?->format('d M Y') ?? '—' }} &nbsp;·&nbsp; Days in arrears: {{ $loan->days_in_arrears ?? 0 }}</div>
</div>

{{-- ── Repayment Schedule ── --}}
<div class="card" style="margin-bottom:20px;">
    <div class="section-title"><i class="fas fa-calendar-alt" style="color:var(--primary); margin-right:6px;"></i>Repayment Schedule</div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Due Date</th>
                    <th>Principal</th>
                    <th>Interest</th>
                    <th>Total Due</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loan->repaymentSchedules as $schedule)
                <tr class="{{ $schedule->status === 'paid' ? 'schedule-row-paid' : ($schedule->is_overdue ? 'schedule-row-overdue' : '') }}">
                    <td>{{ $schedule->installment_number }}</td>
                    <td style="font-size:12px;">{{ $schedule->due_date->format('d M Y') }}</td>
                    <td>{{ number_format($schedule->principal_amount, 0) }}</td>
                    <td>{{ number_format($schedule->interest_amount, 0) }}</td>
                    <td style="font-weight:600;">{{ number_format($schedule->total_amount, 0) }}</td>
                    <td style="color:var(--success);">{{ number_format($schedule->total_paid, 0) }}</td>
                    <td>{{ number_format($schedule->balance, 0) }}</td>
                    <td>
                        @if($schedule->status === 'paid')
                            <span class="status status-active">Paid</span>
                        @elseif($schedule->is_overdue)
                            <span class="status status-rejected">Overdue ({{ $schedule->days_overdue }}d)</span>
                        @elseif($schedule->status === 'partial')
                            <span class="status status-partially-approved">Partial</span>
                        @else
                            <span class="status status-pending">Pending</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center; padding:30px; color:var(--text-secondary);">No schedule generated yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Payment History ── --}}
<div class="card" style="margin-bottom:20px;">
    <div class="section-title"><i class="fas fa-history" style="color:var(--primary); margin-right:6px;"></i>Payment History</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Amount</th>
                <th>Principal</th>
                <th>Interest</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Received By</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loan->repayments as $repayment)
            <tr>
                <td style="font-size:12px;">{{ $repayment->created_at->format('d M Y h:i A') }}</td>
                <td style="font-weight:700; color:var(--success);">KSH {{ number_format($repayment->amount, 0) }}</td>
                <td>{{ number_format($repayment->principal_portion, 0) }}</td>
                <td>{{ number_format($repayment->interest_portion, 0) }}</td>
                <td><span class="badge badge-primary">{{ ucfirst(str_replace('_',' ',$repayment->payment_method)) }}</span></td>
                <td style="font-family:monospace; font-size:11px;">{{ $repayment->transaction_reference ?? '—' }}</td>
                <td style="font-size:12px;">{{ $repayment->receivedBy?->name ?? 'System' }}</td>
                <td>
                    @if($repayment->status === 'confirmed')
                        <span class="status status-active">Confirmed</span>
                    @elseif($repayment->status === 'reversed')
                        <span class="status status-rejected">Reversed</span>
                    @else
                        <span class="status status-pending">Pending</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center; padding:30px; color:var(--text-secondary);">No payments recorded yet</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── SMS History ── --}}
@php $smsLogs = \App\Models\SmsLog::where('loan_id', $loan->id)->latest()->limit(10)->get(); @endphp
@if($smsLogs->count())
<div class="card" style="margin-bottom:20px;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div class="section-title" style="margin-bottom:0;"><i class="fas fa-sms" style="color:#7B1FA2; margin-right:6px;"></i>SMS History</div>
        <a href="{{ route('collection.sms-logs') }}?search={{ $loan->loan_number }}" style="font-size:12px; color:var(--primary);">View all →</a>
    </div>
    <div style="margin-top:14px; display:flex; flex-direction:column; gap:8px;">
        @foreach($smsLogs as $sms)
        @php
            $sc = match($sms->status) {
                'sent'      => ['#E8F5E9','#2E7D32','fa-check-circle'],
                'failed'    => ['#FFEBEE','#C62828','fa-times-circle'],
                'pending'   => ['#E3F2FD','#1565C0','fa-clock'],
                default     => ['#F5F5F5','#757575','fa-ban'],
            };
        @endphp
        <div style="display:flex; align-items:flex-start; gap:10px; padding:10px 12px; background:#FAFBFC; border-radius:8px; border:1px solid var(--border);">
            <i class="fas {{ $sc[2] }}" style="color:{{ $sc[1] }}; margin-top:2px; font-size:14px; flex-shrink:0;"></i>
            <div style="flex:1; min-width:0;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:4px;">
                    <span style="font-size:12px; font-weight:600;">{{ $sms->phone_number }}</span>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="badge" style="background:{{ $sc[0] }}; color:{{ $sc[1] }}; font-size:10px;">{{ ucfirst($sms->status) }}</span>
                        <span style="font-size:11px; color:var(--text-secondary);">{{ $sms->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div style="font-size:12px; color:var(--text-secondary); margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    {{ $sms->message }}
                </div>
                <div style="font-size:11px; color:var(--text-secondary); margin-top:2px;">
                    {{ ucfirst(str_replace('_',' ',$sms->message_type)) }}
                    @if($sms->at_cost) &nbsp;·&nbsp; KES {{ $sms->at_cost }} @endif
                    @if($sms->sent_at) &nbsp;·&nbsp; Sent {{ $sms->sent_at->format('d M Y H:i') }} @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ── Guarantors ── --}}
@if($loan->guarantors->count())
<div class="card">
    <div class="section-title"><i class="fas fa-handshake" style="color:var(--primary); margin-right:6px;"></i>Guarantors</div>
    <table class="data-table">
        <thead>
            <tr><th>#</th><th>Name</th><th>Phone</th><th>Guaranteed Amount</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($loan->guarantors as $i => $g)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td style="font-weight:600;">{{ $g->guarantorCustomer?->full_name ?? '—' }}</td>
                <td>{{ $g->guarantorCustomer?->phone_number ?? '—' }}</td>
                <td>KSH {{ number_format($g->guaranteed_amount, 0) }}</td>
                <td>
                    @if($g->status === 'accepted')
                        <span class="status status-active">Accepted</span>
                    @elseif($g->status === 'rejected')
                        <span class="status status-rejected">Rejected</span>
                    @else
                        <span class="status status-pending">Pending</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Approve Modal ── --}}
<div id="approveModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:30px; width:480px; max-width:95%;">
        <h3 style="font-size:16px; font-weight:600; margin-bottom:16px;">Approve Loan {{ $loan->loan_number }}</h3>
        <form method="POST" action="{{ route('loans.approve-action', $loan) }}">
            @csrf @method('PATCH')
            <div style="margin-bottom:16px;">
                <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">Approval Notes</label>
                <textarea name="notes" rows="3" placeholder="Optional notes…"
                          style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px; font-size:13px; resize:vertical;"></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal('approveModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Confirm Approval</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Reject Modal ── --}}
<div id="rejectModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:30px; width:480px; max-width:95%;">
        <h3 style="font-size:16px; font-weight:600; margin-bottom:16px;">Reject Loan {{ $loan->loan_number }}</h3>
        <form method="POST" action="{{ route('loans.reject', $loan) }}">
            @csrf @method('PATCH')
            <div style="margin-bottom:16px;">
                <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">Rejection Reason <span style="color:var(--danger)">*</span></label>
                <textarea name="reason" rows="3" required placeholder="State the reason for rejection…"
                          style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px; font-size:13px; resize:vertical;"></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal('rejectModal')">Cancel</button>
                <button type="submit" class="btn" style="background:var(--danger); color:white;"><i class="fas fa-times"></i> Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Disburse Modal ── --}}
<div id="disburseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:30px; width:500px; max-width:95%;">
        <h3 style="font-size:16px; font-weight:600; margin-bottom:16px;">Disburse Loan {{ $loan->loan_number }}</h3>
        <form method="POST" action="{{ route('loans.disburse', $loan) }}">
            @csrf @method('PATCH')
            <div style="margin-bottom:15px;">
                <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">Disbursement Method <span style="color:var(--danger)">*</span></label>
                <select name="disbursement_method" id="disburseMethod" class="filter-select" style="width:100%;" onchange="toggleDisburseFields()" required>
                    <option value="">-- Select --</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                </select>
            </div>
            <div id="mpesaReceiptField" style="display:none; margin-bottom:15px;">
                <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">M-Pesa Receipt No. <span style="color:var(--danger)">*</span></label>
                <input type="text" name="mpesa_receipt_number" placeholder="e.g. QHX1234ABC" class="filter-select" style="width:100%; text-transform:uppercase;">
            </div>
            <div style="margin-bottom:20px;">
                <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">Reference / Notes</label>
                <input type="text" name="disbursement_reference" placeholder="Optional reference" class="filter-select" style="width:100%;">
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal('disburseModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Confirm Disbursement</button>
            </div>
        </form>
    </div>
</div>

{{-- ── SMS Modal ── --}}
<div id="smsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:30px; width:500px; max-width:95%; max-height:92vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="font-size:16px; font-weight:600; color:var(--text-primary);">
                <i class="fas fa-sms" style="color:#7B1FA2;"></i> Send SMS
            </h3>
            <button onclick="closeModal('smsModal')" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--text-secondary); line-height:1;">&times;</button>
        </div>

        {{-- Recipient info --}}
        <div style="background:#F3E5F5; border:1px solid #CE93D8; border-radius:8px; padding:12px 14px; margin-bottom:18px; font-size:13px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <i class="fas fa-user-circle" style="color:#7B1FA2; font-size:18px;"></i>
                <div>
                    <div style="font-weight:700;">{{ $loan->customer->full_name }}</div>
                    <div style="font-size:12px; color:var(--text-secondary);">
                        {{ $loan->customer->phone_number }}
                        &nbsp;·&nbsp; {{ $loan->loan_number }}
                        @if($loan->days_in_arrears > 0)
                            &nbsp;·&nbsp; <span style="color:var(--danger); font-weight:600;">{{ $loan->days_in_arrears }} days overdue</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('collection.sms.send') }}">
            @csrf
            <input type="hidden" name="recipient_type" value="loan">
            <input type="hidden" name="loan_id" value="{{ $loan->id }}">

            <div style="margin-bottom:15px;">
                <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">Message Type <span style="color:var(--danger)">*</span></label>
                <select name="message_type" id="smsType" class="filter-select" style="width:100%;" onchange="loadSmsTemplate()" required>
                    <option value="payment_reminder">Payment Reminder</option>
                    <option value="overdue_notice" {{ $loan->days_in_arrears > 0 ? 'selected' : '' }}>Overdue Notice</option>
                    <option value="payment_received">Payment Received</option>
                    <option value="loan_approved">Loan Approved</option>
                    <option value="loan_disbursed">Loan Disbursed</option>
                    <option value="custom">Custom Message</option>
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">
                    Message <span style="color:var(--danger)">*</span>
                    <span id="smsCharCount" style="color:var(--text-secondary); font-weight:400; float:right;">(0 / 459 chars)</span>
                </label>
                <textarea name="message" id="smsMessage" rows="5" required
                          oninput="updateSmsCount(this)"
                          style="width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; resize:vertical; line-height:1.5;"
                          placeholder="Type your message…"></textarea>
                <div style="margin-top:6px; display:flex; flex-wrap:wrap; gap:3px;">
                    @foreach(['{name}' => 'Customer name', '{loan_number}' => 'Loan no.', '{amount_due}' => 'Installment', '{due_date}' => 'Due date', '{outstanding}' => 'Balance', '{days_overdue}' => 'Days overdue'] as $tag => $hint)
                    <span onclick="insertSmsTag('{{ $tag }}')"
                          title="{{ $hint }}"
                          style="display:inline-block; padding:2px 7px; border-radius:4px; background:#E3F2FD; color:var(--primary); font-size:11px; font-family:monospace; cursor:pointer; transition:background 0.1s;"
                          onmouseover="this.style.background='var(--primary)';this.style.color='#fff'"
                          onmouseout="this.style.background='#E3F2FD';this.style.color='var(--primary)'">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="font-size:12px; font-weight:600; display:block; margin-bottom:5px;">Schedule (optional)</label>
                <input type="datetime-local" name="scheduled_at" class="filter-select" style="width:100%;">
                <div style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Leave blank to send immediately via queue</div>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal('smsModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:#7B1FA2; border-color:#7B1FA2;">
                    <i class="fas fa-paper-plane"></i> Send SMS
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
function openApproveModal()  { document.getElementById('approveModal').style.display  = 'flex'; }
function openRejectModal()   { document.getElementById('rejectModal').style.display   = 'flex'; }
function openDisburseModal() { document.getElementById('disburseModal').style.display = 'flex'; }
function openSmsModal()      {
    loadSmsTemplate();
    document.getElementById('smsModal').style.display = 'flex';
}
function closeModal(id)      { document.getElementById(id).style.display = 'none'; }

function toggleDisburseFields() {
    const method = document.getElementById('disburseMethod').value;
    document.getElementById('mpesaReceiptField').style.display = method === 'mpesa' ? 'block' : 'none';
}

// ── SMS helpers ──────────────────────────────────────────────────
const smsTemplates = {
    payment_reminder: 'Dear {{ $loan->customer->full_name }}, your loan {{ $loan->loan_number }} payment of KSH {{ number_format($loan->weekly_installment, 0) }} is due on {{ $loan->next_due_date?->format("d M Y") ?? "N/A" }}. Please pay on time to avoid penalties. GetCash Capital.',
    overdue_notice:   'Dear {{ $loan->customer->full_name }}, your loan {{ $loan->loan_number }} is {{ $loan->days_in_arrears }} days overdue. Outstanding balance: KSH {{ number_format($loan->outstanding_balance, 0) }}. Please pay immediately to avoid further charges. GetCash Capital.',
    payment_received: 'Dear {{ $loan->customer->full_name }}, we have received your payment for loan {{ $loan->loan_number }}. Outstanding balance: KSH {{ number_format($loan->outstanding_balance, 0) }}. Thank you. GetCash Capital.',
    loan_approved:    'Dear {{ $loan->customer->full_name }}, your loan application {{ $loan->loan_number }} of KSH {{ number_format($loan->principal_amount, 0) }} has been approved. Disbursement will follow shortly. GetCash Capital.',
    loan_disbursed:   'Dear {{ $loan->customer->full_name }}, your loan {{ $loan->loan_number }} of KSH {{ number_format($loan->principal_amount, 0) }} has been disbursed. First repayment of KSH {{ number_format($loan->weekly_installment, 0) }} is due on {{ $loan->first_due_date?->format("d M Y") ?? "N/A" }}. GetCash Capital.',
    custom: '',
};

function loadSmsTemplate() {
    const type = document.getElementById('smsType').value;
    const ta   = document.getElementById('smsMessage');
    ta.value   = smsTemplates[type] || '';
    updateSmsCount(ta);
}

function updateSmsCount(ta) {
    const len  = ta.value.length;
    const msgs = Math.ceil(len / 160) || 1;
    document.getElementById('smsCharCount').textContent =
        `(${len} chars · ${msgs} SMS${msgs > 1 ? ' parts' : ''})`;
    ta.style.borderColor = len > 459 ? 'var(--danger)' : 'var(--border)';
}

function insertSmsTag(tag) {
    const ta  = document.getElementById('smsMessage');
    const pos = ta.selectionStart;
    ta.value  = ta.value.slice(0, pos) + tag + ta.value.slice(ta.selectionEnd);
    ta.selectionStart = ta.selectionEnd = pos + tag.length;
    ta.focus();
    updateSmsCount(ta);
}

// Close modals on backdrop click
['approveModal','rejectModal','disburseModal','smsModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});

// Pre-load template on page load if overdue
document.addEventListener('DOMContentLoaded', () => {
    @if($loan->days_in_arrears > 0)
    document.getElementById('smsType').value = 'overdue_notice';
    @endif
});
</script>
@endsection
