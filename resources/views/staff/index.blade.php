@extends('layouts.app')

@section('title', 'Staff Overview - Mweela Cash Capital')
@section('page-title', 'Staff Overview')

@section('content')

<div style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
    <h2 style="margin:0;">Staff Overview</h2>
    <a href="{{ route('staff.create') }}" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Add New Staff
    </a>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-value">{{ $totalStaff }}</div>
        <div class="stat-label">Total Staff</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--success);">{{ $activeStaff }}</div>
        <div class="stat-label">Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--danger);">{{ $inactiveStaff }}</div>
        <div class="stat-label">Inactive</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap;">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, ID..." class="form-control" style="max-width:280px;">
    <select name="status" class="form-control" style="max-width:160px;">
        <option value="">All Statuses</option>
        <option value="active"    {{ request('status')==='active'   ?'selected':'' }}>Active</option>
        <option value="inactive"  {{ request('status')==='inactive' ?'selected':'' }}>Inactive</option>
        <option value="suspended" {{ request('status')==='suspended'?'selected':'' }}>Suspended</option>
    </select>
    <select name="branch" class="form-control" style="max-width:180px;">
        <option value="">All Branches</option>
        @foreach($branches as $branch)
            <option value="{{ $branch->id }}" {{ request('branch')==$branch->id?'selected':'' }}>{{ $branch->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
    <a href="{{ route('staff.index') }}" class="btn btn-outline">Clear</a>
</form>

{{-- Table --}}
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Employee ID</th>
                <th>Designation</th>
                <th>Branch</th>
                <th>Status</th>
                <th>Temp Password</th>
                <th style="text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($staff as $user)
            <tr>
                <td><strong>{{ $user->name }}</strong></td>
                <td style="font-size:12px;">{{ $user->email }}</td>
                <td style="font-family:monospace; font-size:12px;">{{ $user->phone_number }}</td>
                <td>{{ $user->employee_id ?? '-' }}</td>
                <td>{{ $user->designation }}</td>
                <td>{{ $user->branch?->name ?? '-' }}</td>
                <td>
                    <span class="badge badge-{{ $user->status==='active'?'success':($user->status==='inactive'?'secondary':'danger') }}">
                        {{ ucfirst($user->status) }}
                    </span>
                </td>
                <td>
                    @if($user->temp_password)
                        <code style="background:#f5f5f5;padding:2px 6px;border-radius:4px;font-size:11px;cursor:pointer;"
                              onclick="navigator.clipboard.writeText('{{ $user->temp_password }}');this.style.background='#e8f5e9';"
                              title="Click to copy">{{ $user->temp_password }}</code>
                    @else
                        <span style="color:var(--text-secondary);font-size:12px;">—</span>
                    @endif
                </td>
                <td style="text-align:right; white-space:nowrap;">
                    {{-- Contact --}}
                    <button type="button"
                            class="btn btn-sm btn-outline"
                            style="color:#1565C0; border-color:#90CAF9;"
                            title="Contact {{ $user->name }}"
                            onclick="openContactModal(
                                {{ $user->id }},
                                '{{ addslashes($user->name) }}',
                                '{{ $user->email }}',
                                '{{ $user->phone_number }}'
                            )">
                        <i class="fas fa-envelope"></i> Contact
                    </button>

                    {{-- Performance --}}
                    <a href="{{ route('staff.performance', $user) }}"
                       class="btn btn-sm btn-outline" style="margin-left:4px;">
                        <i class="fas fa-chart-line"></i>
                    </a>

                    @if($user->id !== auth()->id() && !$user->hasRole('super_admin'))
                    {{-- Reset Password --}}
                    <form method="POST" action="{{ route('staff.reset-password', $user) }}" style="display:inline;"
                          onsubmit="return confirm('Reset password for {{ $user->name }}?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline"
                                style="color:var(--warning);border-color:var(--warning);margin-left:4px;"
                                title="Reset Password">
                            <i class="fas fa-key"></i>
                        </button>
                    </form>

                    {{-- Deactivate / Reactivate --}}
                    @if($user->status === 'active')
                    <form method="POST" action="{{ route('staff.deactivate', $user) }}" style="display:inline;"
                          onsubmit="return confirm('Deactivate {{ $user->name }}?');">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline"
                                style="color:var(--danger);border-color:var(--danger);margin-left:4px;"
                                title="Deactivate">
                            <i class="fas fa-user-slash"></i>
                        </button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('staff.reactivate', $user) }}" style="display:inline;">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm btn-outline"
                                style="color:var(--success);border-color:var(--success);margin-left:4px;"
                                title="Reactivate">
                            <i class="fas fa-user-check"></i>
                        </button>
                    </form>
                    @endif
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center; padding:24px; color:var(--text-secondary);">No staff found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $staff->links() }}

{{-- ── Contact Staff Modal ── --}}
<div id="contactModal" class="modal-overlay" onclick="if(event.target===this)closeModal('contactModal')">
    <div class="modal-box" style="width:480px; max-width:95vw;">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-envelope" style="color:#1565C0;"></i>
                Contact Staff — <span id="contactName"></span>
            </div>
            <button class="modal-close" onclick="closeModal('contactModal')">&times;</button>
        </div>

        {{-- Contact info row --}}
        <div style="background:#F0F4F8; border-radius:8px; padding:10px 14px; margin-bottom:18px;
                    display:flex; gap:20px; flex-wrap:wrap; font-size:13px;">
            <span><i class="fas fa-envelope" style="color:var(--primary);margin-right:5px;"></i>
                  <a id="contactEmailLink" href="#" style="color:var(--primary);"></a></span>
            <span><i class="fas fa-phone" style="color:var(--success);margin-right:5px;"></i>
                  <a id="contactPhoneLink" href="#" style="color:var(--success);"></a></span>
        </div>

        {{-- Channel tabs --}}
        <div style="display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:18px;">
            <button type="button" onclick="switchContactTab('email')" id="ctab-email"
                    style="padding:9px 18px; font-size:13px; font-weight:600; border:none; background:none;
                           cursor:pointer; color:var(--primary); border-bottom:2px solid var(--primary); margin-bottom:-2px;">
                <i class="fas fa-envelope"></i> Email
            </button>
            <button type="button" onclick="switchContactTab('sms')" id="ctab-sms"
                    style="padding:9px 18px; font-size:13px; font-weight:500; border:none; background:none;
                           cursor:pointer; color:var(--text-secondary); border-bottom:2px solid transparent; margin-bottom:-2px;">
                <i class="fas fa-sms"></i> SMS
            </button>
            <button type="button" onclick="switchContactTab('whatsapp')" id="ctab-whatsapp"
                    style="padding:9px 18px; font-size:13px; font-weight:500; border:none; background:none;
                           cursor:pointer; color:var(--text-secondary); border-bottom:2px solid transparent; margin-bottom:-2px;">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </button>
        </div>

        {{-- Email panel --}}
        <div id="cpanel-email">
            <form method="POST" action="{{ route('staff.contact.email') }}" id="contactEmailForm">
                @csrf
                <input type="hidden" name="user_id" id="contactUserId">
                <div class="form-group">
                    <label class="form-label">Subject <span class="req">*</span></label>
                    <input type="text" name="subject" class="form-control"
                           placeholder="e.g. Follow-up on overdue accounts" required>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Message <span class="req">*</span>
                        <span id="emailCharCount" style="float:right; font-size:11px; color:var(--text-secondary); font-weight:400;"></span>
                    </label>
                    <textarea name="message" rows="5" class="form-control"
                              oninput="document.getElementById('emailCharCount').textContent=this.value.length+' chars'"
                              placeholder="Type your message to the staff member…" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('contactModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Email
                    </button>
                </div>
            </form>
        </div>

        {{-- SMS panel --}}
        <div id="cpanel-sms" style="display:none;">
            <form method="POST" action="{{ route('staff.contact.sms') }}" id="contactSmsForm">
                @csrf
                <input type="hidden" name="user_id" id="contactUserIdSms">
                <div class="form-group">
                    <label class="form-label">
                        Message <span class="req">*</span>
                        <span id="smsCharCount" style="float:right; font-size:11px; color:var(--text-secondary); font-weight:400;">0 / 160</span>
                    </label>
                    <textarea name="message" rows="4" class="form-control"
                              oninput="updateSmsCount(this)"
                              placeholder="Type your SMS message (max 160 chars for single SMS)…"
                              maxlength="459" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('contactModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sms"></i> Send SMS
                    </button>
                </div>
            </form>
        </div>

        {{-- WhatsApp panel --}}
        <div id="cpanel-whatsapp" style="display:none;">
            <div class="form-group">
                <label class="form-label">Message</label>
                <textarea id="waMessage" rows="4" class="form-control"
                          placeholder="Type your WhatsApp message…"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('contactModal')">Cancel</button>
                <button type="button" class="btn" style="background:#25D366; color:#fff; border-color:#25D366;"
                        onclick="openWhatsApp()">
                    <i class="fab fa-whatsapp"></i> Open WhatsApp
                </button>
            </div>
        </div>

    </div>
</div>

@endsection

@section('scripts')
<script>
let contactPhone = '';

function openContactModal(id, name, email, phone) {
    contactPhone = phone;
    document.getElementById('contactName').textContent     = name;
    document.getElementById('contactUserId').value         = id;
    document.getElementById('contactUserIdSms').value      = id;
    document.getElementById('contactEmailLink').textContent = email;
    document.getElementById('contactEmailLink').href        = 'mailto:' + email;
    document.getElementById('contactPhoneLink').textContent = phone;
    document.getElementById('contactPhoneLink').href        = 'tel:' + phone;
    switchContactTab('email');
    document.getElementById('contactModal').classList.add('show');
}

function switchContactTab(tab) {
    ['email','sms','whatsapp'].forEach(t => {
        document.getElementById('cpanel-' + t).style.display = t === tab ? 'block' : 'none';
        const btn = document.getElementById('ctab-' + t);
        btn.style.color = t === tab ? 'var(--primary)' : 'var(--text-secondary)';
        btn.style.borderBottomColor = t === tab ? 'var(--primary)' : 'transparent';
        btn.style.fontWeight = t === tab ? '600' : '500';
    });
}

function updateSmsCount(el) {
    document.getElementById('smsCharCount').textContent = el.value.length + ' / 160';
}

function openWhatsApp() {
    const msg  = encodeURIComponent(document.getElementById('waMessage').value);
    const num  = contactPhone.replace(/\D/g,'');
    const wa   = num.startsWith('0') ? '254' + num.slice(1) : num;
    window.open('https://wa.me/' + wa + (msg ? '?text=' + msg : ''), '_blank');
}
</script>
@endsection
