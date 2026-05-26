@extends('layouts.app')
@section('title', 'SMS Schedules - Mweela Cash Capital')
@section('page-title', 'SMS Schedules')

@section('styles')
<style>
    .schedule-card {
        background:#fff; border-radius:12px; border:1px solid var(--border);
        padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.05);
        transition:box-shadow 0.15s;
    }
    .schedule-card:hover { box-shadow:0 6px 20px rgba(0,0,0,0.09); }
    .schedule-status-dot { width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:5px; }
    .placeholder-tag {
        display:inline-block; padding:2px 8px; border-radius:4px;
        background:#E3F2FD; color:var(--primary); font-size:11px;
        font-family:monospace; cursor:pointer; margin:2px;
    }
    .placeholder-tag:hover { background:var(--primary); color:#fff; }
    .form-label { display:block;font-size:12px;font-weight:600;color:var(--text-primary);margin-bottom:5px; }
    .form-control { width:100%;padding:9px 13px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;background:#fff;outline:none;transition:border-color 0.15s; }
    .form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px rgba(0,188,212,0.12); }
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

{{-- Header --}}
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <p style="font-size:13px;color:var(--text-secondary);flex:1;min-width:200px;">
        Automated SMS rules that run against your loan portfolio. Active schedules run daily via the scheduler.
    </p>
    <button class="btn btn-primary" onclick="openCreateModal()" style="flex-shrink:0;">
        <i class="fas fa-plus"></i> New Schedule
    </button>
</div>

{{-- Placeholder reference --}}
<div class="card" style="margin-bottom:20px;background:#F0FBFD;border-color:#B3E5FC;">
    <div style="font-size:12px;font-weight:600;color:var(--primary);margin-bottom:8px;"><i class="fas fa-info-circle"></i> Available Message Placeholders</div>
    <div style="display:flex;flex-wrap:wrap;gap:4px;">
        @foreach(['{name}' => 'Customer full name', '{loan_number}' => 'Loan number', '{amount_due}' => 'Weekly installment', '{due_date}' => 'Next due date', '{outstanding}' => 'Outstanding balance', '{days_overdue}' => 'Days in arrears'] as $tag => $desc)
        <span class="placeholder-tag" title="{{ $desc }}" onclick="copyTag('{{ $tag }}')">{{ $tag }}</span>
        @endforeach
    </div>
    <div style="font-size:11px;color:var(--text-secondary);margin-top:6px;">Click a tag to copy it to clipboard</div>
</div>

{{-- Schedules Grid --}}
@if($schedules->isEmpty())
<div class="card" style="text-align:center;padding:60px;">
    <i class="fas fa-calendar-alt" style="font-size:48px;color:var(--text-secondary);opacity:0.2;display:block;margin-bottom:16px;"></i>
    <p style="font-size:15px;color:var(--text-secondary);">No SMS schedules yet</p>
    <p style="font-size:13px;color:var(--text-secondary);margin-bottom:20px;">Create your first schedule to automate collection reminders</p>
    <button class="btn btn-primary" onclick="openCreateModal()"><i class="fas fa-plus"></i> Create Schedule</button>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;margin-bottom:24px;">
    @foreach($schedules as $schedule)
    @php
        $statusColor = match($schedule->status) { 'active' => '#4CAF50', 'paused' => '#FF9800', default => '#9E9E9E' };
    @endphp
    <div class="schedule-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
            <div style="flex:1;min-width:0;">
                <div style="font-size:15px;font-weight:700;color:var(--text-primary);margin-bottom:3px;">{{ $schedule->name }}</div>
                @if($schedule->description)
                <div style="font-size:12px;color:var(--text-secondary);">{{ $schedule->description }}</div>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;margin-left:10px;">
                <span style="font-size:12px;font-weight:600;color:{{ $statusColor }};">
                    <span class="schedule-status-dot" style="background:{{ $statusColor }};"></span>
                    {{ ucfirst($schedule->status) }}
                </span>
            </div>
        </div>

        {{-- Trigger & Target --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
            <div style="background:#FAFBFC;border-radius:8px;padding:10px;">
                <div style="font-size:10px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;margin-bottom:4px;">Trigger</div>
                <div style="font-size:12px;font-weight:600;">
                    @if($schedule->trigger_type === 'days_before_due')
                        {{ $schedule->trigger_days }} days before due
                    @elseif($schedule->trigger_type === 'days_after_due')
                        {{ $schedule->trigger_days }} days after due
                    @elseif($schedule->trigger_type === 'on_due_date')
                        On due date
                    @else
                        Manual only
                    @endif
                </div>
            </div>
            <div style="background:#FAFBFC;border-radius:8px;padding:10px;">
                <div style="font-size:10px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;margin-bottom:4px;">Target</div>
                <div style="font-size:12px;font-weight:600;">
                    @if($schedule->target === 'specific_product')
                        {{ $schedule->targetProduct->name ?? 'Product' }}
                    @elseif($schedule->target === 'specific_branch')
                        {{ $schedule->targetBranch->name ?? 'Branch' }}
                    @else
                        {{ ucfirst(str_replace('_',' ',$schedule->target)) }}
                    @endif
                </div>
            </div>
        </div>

        {{-- Message preview --}}
        <div style="background:#F0FBFD;border-radius:8px;padding:10px;margin-bottom:14px;font-size:12px;color:var(--text-secondary);border:1px solid #B3E5FC;line-height:1.5;">
            {{ Str::limit($schedule->message_template, 120) }}
        </div>

        {{-- Stats --}}
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-secondary);margin-bottom:14px;">
            <span><i class="fas fa-paper-plane" style="color:var(--primary);"></i> {{ number_format($schedule->total_sent) }} sent total</span>
            <span>
                @if($schedule->last_run_at)
                    Last run: {{ $schedule->last_run_at->diffForHumans() }}
                @else
                    Never run
                @endif
            </span>
        </div>

        {{-- Actions --}}
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            {{-- Run now --}}
            <form method="POST" action="{{ route('collection.schedules.run', $schedule) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-primary" style="font-size:12px;padding:6px 12px;"
                        onclick="return confirm('Run this schedule now?')">
                    <i class="fas fa-play"></i> Run Now
                </button>
            </form>

            {{-- Toggle active/paused --}}
            <form method="POST" action="{{ route('collection.schedules.toggle', $schedule) }}" style="display:inline;">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-outline" style="font-size:12px;padding:6px 12px;color:{{ $schedule->status === 'active' ? 'var(--warning)' : 'var(--success)' }};border-color:{{ $schedule->status === 'active' ? 'var(--warning)' : 'var(--success)' }};">
                    <i class="fas fa-{{ $schedule->status === 'active' ? 'pause' : 'play' }}"></i>
                    {{ $schedule->status === 'active' ? 'Pause' : 'Activate' }}
                </button>
            </form>

            {{-- Edit --}}
            <button class="btn btn-outline" style="font-size:12px;padding:6px 12px;"
                    onclick="openEditModal({{ $schedule->id }}, {{ json_encode($schedule) }})">
                <i class="fas fa-pen"></i>
            </button>

            {{-- Delete --}}
            <form method="POST" action="{{ route('collection.schedules.destroy', $schedule) }}" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline" style="font-size:12px;padding:6px 12px;color:var(--danger);border-color:var(--danger);"
                        onclick="return confirm('Delete this schedule?')">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ── Create / Edit Modal ── --}}
<div id="scheduleModal" class="modal-overlay" onclick="if(event.target===this)closeModal('scheduleModal')">
    <div class="modal-box" style="max-width:600px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h3 id="modalTitle" style="font-size:15px;font-weight:600;">New SMS Schedule</h3>
            <button onclick="closeModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-secondary);">&times;</button>
        </div>

        <form id="scheduleForm" method="POST" action="{{ route('collection.schedules.store') }}">
            @csrf
            <span id="methodField"></span>

            <div class="grid-2" style="gap:14px;margin-bottom:14px;">
                <div style="grid-column:span 2;">
                    <label class="form-label">Schedule Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="name" id="fName" class="form-control" placeholder="e.g. 3-Day Before Due Reminder" required>
                </div>
                <div style="grid-column:span 2;">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" id="fDesc" class="form-control" placeholder="Optional description">
                </div>
            </div>

            <div class="grid-2" style="gap:14px;margin-bottom:14px;">
                <div>
                    <label class="form-label">Trigger Type <span style="color:var(--danger)">*</span></label>
                    <select name="trigger_type" id="fTriggerType" class="form-control" onchange="toggleTriggerDays()" required>
                        <option value="days_before_due">X Days Before Due Date</option>
                        <option value="on_due_date">On Due Date</option>
                        <option value="days_after_due">X Days After Due (Overdue)</option>
                        <option value="manual">Manual Only</option>
                    </select>
                </div>
                <div id="triggerDaysWrap">
                    <label class="form-label">Number of Days <span style="color:var(--danger)">*</span></label>
                    <input type="number" name="trigger_days" id="fTriggerDays" class="form-control" min="0" max="365" value="3" placeholder="e.g. 3">
                </div>
            </div>

            <div class="grid-2" style="gap:14px;margin-bottom:14px;">
                <div>
                    <label class="form-label">Target Audience <span style="color:var(--danger)">*</span></label>
                    <select name="target" id="fTarget" class="form-control" onchange="toggleTargetFields()" required>
                        <option value="all_active">All Active Loans</option>
                        <option value="overdue">Overdue Loans</option>
                        <option value="due_today">Due Today</option>
                        <option value="specific_product">Specific Product</option>
                        <option value="specific_branch">Specific Branch</option>
                    </select>
                </div>
                <div id="productWrap" style="display:none;">
                    <label class="form-label">Loan Product</label>
                    <select name="target_product_id" id="fProduct" class="form-control">
                        <option value="">-- Select --</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="branchWrap" style="display:none;">
                    <label class="form-label">Branch</label>
                    <select name="target_branch_id" id="fBranch" class="form-control">
                        <option value="">-- Select --</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom:14px;">
                <label class="form-label">
                    Message Template <span style="color:var(--danger)">*</span>
                    <span id="tplCharCount" style="color:var(--text-secondary);font-weight:400;">(0/459)</span>
                </label>
                <textarea name="message_template" id="fTemplate" rows="4" oninput="countTplChars(this)"
                          class="form-control" style="resize:vertical;"
                          placeholder="Dear {name}, your loan {loan_number} payment of KSH {amount_due} is due on {due_date}. Mweela Cash Capital." required></textarea>
                <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:3px;">
                    @foreach(['{name}','{loan_number}','{amount_due}','{due_date}','{outstanding}','{days_overdue}'] as $tag)
                    <span class="placeholder-tag" onclick="insertTag('{{ $tag }}')">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label class="form-label">Status</label>
                <select name="status" id="fStatus" class="form-control">
                    <option value="draft">Draft (inactive)</option>
                    <option value="active">Active (runs daily)</option>
                    <option value="paused">Paused</option>
                </select>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Save Schedule
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
let editMode = false;

function openCreateModal() {
    editMode = false;
    document.getElementById('modalTitle').textContent = 'New SMS Schedule';
    document.getElementById('scheduleForm').action = '{{ route('collection.schedules.store') }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Create Schedule';
    clearForm();
    document.getElementById('scheduleModal').classList.add('show');
}

function openEditModal(id, data) {
    editMode = true;
    document.getElementById('modalTitle').textContent = 'Edit Schedule';
    document.getElementById('scheduleForm').action = `/loans/collection/schedules/${id}`;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Save Changes';

    document.getElementById('fName').value         = data.name || '';
    document.getElementById('fDesc').value         = data.description || '';
    document.getElementById('fTriggerType').value  = data.trigger_type || 'days_before_due';
    document.getElementById('fTriggerDays').value  = data.trigger_days || 0;
    document.getElementById('fTarget').value       = data.target || 'all_active';
    document.getElementById('fProduct').value      = data.target_product_id || '';
    document.getElementById('fBranch').value       = data.target_branch_id || '';
    document.getElementById('fTemplate').value     = data.message_template || '';
    document.getElementById('fStatus').value       = data.status || 'draft';

    toggleTriggerDays();
    toggleTargetFields();
    countTplChars(document.getElementById('fTemplate'));
    document.getElementById('scheduleModal').classList.add('show');
}

function closeModal() { document.getElementById('scheduleModal').classList.remove('show'); }

function clearForm() {
    ['fName','fDesc','fTriggerDays','fTemplate'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('fTriggerType').value = 'days_before_due';
    document.getElementById('fTarget').value      = 'all_active';
    document.getElementById('fStatus').value      = 'draft';
    document.getElementById('fTriggerDays').value = '3';
    toggleTriggerDays();
    toggleTargetFields();
}

function toggleTriggerDays() {
    const type = document.getElementById('fTriggerType').value;
    const show = ['days_before_due','days_after_due'].includes(type);
    document.getElementById('triggerDaysWrap').style.display = show ? 'block' : 'none';
}

function toggleTargetFields() {
    const target = document.getElementById('fTarget').value;
    document.getElementById('productWrap').style.display = target === 'specific_product' ? 'block' : 'none';
    document.getElementById('branchWrap').style.display  = target === 'specific_branch'  ? 'block' : 'none';
}

function countTplChars(el) {
    document.getElementById('tplCharCount').textContent = `(${el.value.length}/459)`;
}

function insertTag(tag) {
    const ta = document.getElementById('fTemplate');
    const pos = ta.selectionStart;
    ta.value = ta.value.slice(0, pos) + tag + ta.value.slice(ta.selectionEnd);
    ta.selectionStart = ta.selectionEnd = pos + tag.length;
    ta.focus();
    countTplChars(ta);
}

function copyTag(tag) {
    navigator.clipboard?.writeText(tag).then(() => {
        // brief visual feedback
        event.target.style.background = 'var(--primary)';
        event.target.style.color = '#fff';
        setTimeout(() => {
            event.target.style.background = '';
            event.target.style.color = '';
        }, 800);
    });
}

document.getElementById('scheduleModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
@endsection
