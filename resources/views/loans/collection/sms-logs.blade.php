@extends('layouts.app')
@section('title', 'SMS Logs - Mweela Cash Capital')
@section('page-title', 'SMS Logs')

@section('styles')
<style>
    .filter-input { padding:8px 14px;border:1px solid var(--border);border-radius:8px;font-size:13px;background:#fff;outline:none;height:38px; }
    .filter-input:focus { border-color:var(--primary); }
    .msg-preview { max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:12px;color:var(--text-secondary); }
</style>
@endsection

@section('content')

@if(session('success'))
<div class="flash-success">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

{{-- Stats --}}
<div class="grid-5" style="margin-bottom:20px;display:grid;grid-template-columns:repeat(5,1fr);gap:16px;">
    <div class="card" style="border-left:4px solid var(--success);padding:16px 20px;">
        <div style="font-size:11px;color:var(--text-secondary);margin-bottom:4px;">Sent</div>
        <div style="font-size:26px;font-weight:700;color:var(--success);">{{ number_format($sentCount) }}</div>
    </div>
    <div class="card" style="border-left:4px solid var(--warning);padding:16px 20px;">
        <div style="font-size:11px;color:var(--text-secondary);margin-bottom:4px;">Pending</div>
        <div style="font-size:26px;font-weight:700;color:var(--warning);">{{ number_format($pendingCount) }}</div>
    </div>
    <div class="card" style="border-left:4px solid var(--danger);padding:16px 20px;">
        <div style="font-size:11px;color:var(--text-secondary);margin-bottom:4px;">Failed</div>
        <div style="font-size:26px;font-weight:700;color:var(--danger);">{{ number_format($failedCount) }}</div>
    </div>
    <div class="card" style="border-left:4px solid #7B1FA2;padding:16px 20px;">
        <div style="font-size:11px;color:var(--text-secondary);margin-bottom:4px;">Blacklisted</div>
        <div style="font-size:26px;font-weight:700;color:#7B1FA2;">{{ number_format($blacklistedCount) }}</div>
    </div>
    <div class="card" style="border-left:4px solid #9C27B0;padding:16px 20px;">
        <div style="font-size:11px;color:var(--text-secondary);margin-bottom:4px;">Total Cost</div>
        <div style="font-size:22px;font-weight:700;color:#9C27B0;">KES {{ number_format($totalCost, 2) }}</div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('collection.sms-logs') }}">
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
            <div style="position:relative;flex:1;min-width:180px;max-width:240px;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-secondary);font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, phone, AT ID…"
                       class="filter-input" style="width:100%;padding-left:36px;">
            </div>
            <select name="status" class="filter-input" style="min-width:130px;">
                <option value="">All Status</option>
                @foreach(['pending','sent','failed','blacklisted','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="message_type" class="filter-input" style="min-width:180px;">
                <option value="">All Types</option>
                @foreach(['payment_reminder','overdue_notice','payment_received','loan_approved','loan_disbursed','custom'] as $t)
                    <option value="{{ $t }}" {{ request('message_type') === $t ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="filter-input" style="width:150px;">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   class="filter-input" style="width:150px;">
            <button type="submit" class="btn btn-primary" style="height:38px;padding:0 18px;"><i class="fas fa-search"></i> Filter</button>
            <a href="{{ route('collection.sms-logs') }}" class="btn btn-outline" style="height:38px;padding:0 14px;"><i class="fas fa-undo"></i></a>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <span style="font-size:14px;font-weight:600;">{{ $logs->total() }} SMS records</span>
    </div>
    <div style="overflow-x:auto;">
        <div class="table-wrap">
        <table class="data-table" style="min-width:1000px;">
            <thead>
                <tr>
                    <th>#</th><th>Customer</th><th>Phone</th><th>Type</th>
                    <th>Message</th><th>Status</th><th>AT Message ID</th>
                    <th>Cost</th><th>Scheduled</th><th>Sent At</th><th>By</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $i => $log)
                @php
                    $sc = match($log->status) {
                        'sent'        => ['status-active',   'Sent'],
                        'failed'      => ['status-rejected', 'Failed'],
                        'pending'     => ['status-pending',  'Pending'],
                        'blacklisted' => ['status-blacklisted', 'Blacklisted'],
                        'cancelled'   => ['status-partially-approved', 'Cancelled'],
                        default       => ['status-pending',  ucfirst($log->status)],
                    };
                @endphp
                <tr>
                    <td style="font-size:12px;color:var(--text-secondary);">{{ ($logs->currentPage()-1)*$logs->perPage()+$i+1 }}</td>
                    <td>
                        <div style="font-weight:600;font-size:13px;">{{ $log->customer->full_name ?? '—' }}</div>
                        @if($log->loan)
                        <div style="font-size:11px;color:var(--primary);font-family:monospace;">{{ $log->loan->loan_number }}</div>
                        @endif
                    </td>
                    <td style="font-size:12px;">{{ $log->phone_number }}</td>
                    <td>
                        <span class="badge badge-primary" style="font-size:10px;">
                            {{ ucfirst(str_replace('_',' ',$log->message_type)) }}
                        </span>
                        @if($log->is_bulk)
                        <span class="badge" style="background:#F3E5F5;color:#7B1FA2;font-size:10px;margin-left:3px;">Bulk</span>
                        @endif
                    </td>
                    <td><div class="msg-preview" title="{{ $log->message }}">{{ $log->message }}</div></td>
                    <td><span class="status {{ $sc[0] }}">{{ $sc[1] }}</span></td>
                    <td style="font-family:monospace;font-size:11px;color:var(--text-secondary);">{{ $log->at_message_id ?? '—' }}</td>
                    <td style="font-size:12px;">{{ $log->at_cost ? 'KES '.$log->at_cost : '—' }}</td>
                    <td style="font-size:12px;color:var(--text-secondary);">
                        {{ $log->scheduled_at ? $log->scheduled_at->format('d M Y H:i') : 'Immediate' }}
                    </td>
                    <td style="font-size:12px;color:var(--text-secondary);">
                        {{ $log->sent_at ? $log->sent_at->format('d M Y H:i') : '—' }}
                    </td>
                    <td style="font-size:12px;">{{ $log->createdBy->name ?? 'System' }}</td>
                    <td>
                        @if($log->status === 'pending')
                        <button onclick="cancelSms({{ $log->id }}, this)"
                                style="background:none;border:1px solid var(--danger);color:var(--danger);border-radius:6px;padding:3px 8px;font-size:11px;cursor:pointer;">
                            Cancel
                        </button>
                        @endif
                        @if($log->failure_reason)
                        <button onclick="showError('{{ addslashes($log->failure_reason) }}')"
                                style="background:none;border:1px solid var(--warning);color:var(--warning);border-radius:6px;padding:3px 8px;font-size:11px;cursor:pointer;margin-left:3px;">
                            Error
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" style="text-align:center;padding:60px;color:var(--text-secondary);">
                        <i class="fas fa-sms" style="font-size:48px;display:block;margin-bottom:12px;opacity:0.2;"></i>
                        No SMS logs found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    @if($logs->hasPages())
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 4px 4px;border-top:1px solid var(--border);margin-top:8px;">
        <span style="font-size:12px;color:var(--text-secondary);">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}</span>
        {{ $logs->appends(request()->query())->links() }}
    </div>
    @endif
</div>

{{-- Error Modal --}}
<div id="errorModal" class="modal-overlay" onclick="if(event.target===this)closeModal('errorModal')">
    <div class="modal-box" style="max-width:440px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
            <h3 style="font-size:14px;font-weight:600;color:var(--danger);"><i class="fas fa-exclamation-circle"></i> Failure Reason</h3>
            <button onclick="document.getElementById('errorModal').classList.remove('show')" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <div id="errorText" style="font-size:13px;color:var(--text-primary);background:#FFEBEE;border-radius:8px;padding:12px;word-break:break-all;"></div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function cancelSms(id, btn) {
    if (!confirm('Cancel this pending SMS?')) return;
    fetch(`/loans/collection/sms/${id}/cancel`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ _method: 'PATCH' })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            btn.closest('tr').style.opacity = '0.4';
            btn.textContent = 'Cancelled';
            btn.disabled = true;
        } else {
            alert(d.error || 'Failed to cancel.');
        }
    });
}

function showError(msg) {
    document.getElementById('errorText').textContent = msg;
    document.getElementById('errorModal').classList.add('show');
}
</script>
@endsection
