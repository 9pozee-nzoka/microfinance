{{-- resources/views/loans/approve.blade.php --}}
@extends('layouts.app')

@section('title', 'Approve New Loans - Mweela Cash Capital')
@section('page-title', 'Approve New Loans')

@section('styles')
<style>
    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,0.5); z-index: 2000;
        align-items: center; justify-content: center;
    }
    .modal-overlay.show { display: flex; }
    .modal-box {
        background: white; border-radius: 12px;
        width: 500px; max-width: 95%; max-height: 90vh;
        overflow-y: auto; display: flex; flex-direction: column;
    }
    .modal-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 20px 24px 0; flex-shrink: 0;
    }
    .modal-title { font-size: 16px; font-weight: 600; }
    .modal-close {
        background: none; border: none; font-size: 22px;
        cursor: pointer; color: var(--text-secondary); line-height: 1;
    }
    .modal-body { padding: 20px 24px; flex: 1; }
    .modal-footer {
        padding: 0 24px 20px;
        display: flex; gap: 10px; justify-content: flex-end; flex-shrink: 0;
    }
    .form-label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px; }
    .form-control {
        width: 100%; padding: 10px 12px;
        border: 1px solid var(--border); border-radius: 8px;
        font-size: 13px; font-family: inherit; outline: none;
        transition: border-color 0.15s;
    }
    .form-control:focus { border-color: var(--primary); }
    .empty-state { text-align: center; padding: 50px 20px; color: var(--text-secondary); }
    .empty-state i { font-size: 48px; opacity: 0.2; display: block; margin-bottom: 14px; }
</style>
@endsection

@section('content')
<div class="card">
    <div class="card-header" style="margin-bottom:20px; flex-wrap:wrap; gap:10px;">
        <div style="flex:1 1 200px;">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search by any field" id="loanSearch">
            </div>
        </div>
        <div style="flex:1 1 160px;">
            <div class="search-box">
                <i class="fas fa-phone"></i>
                <input type="text" placeholder="Search by phone" id="phoneSearch">
            </div>
        </div>
    </div>

    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Amount (KSH)</th>
                <th>Product</th>
                <th>Interest</th>
                <th>Branch</th>
                <th>Relationship Officer</th>
                <th>Purpose</th>
                <th>Status</th>
                <th>Date Created</th>
                <th>Created By</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans as $index => $loan)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td style="font-weight: 600;">{{ $loan->customer->full_name }}</td>
                <td>{{ $loan->customer->phone_number }}</td>
                <td style="font-weight: 600; color: var(--danger);">{{ number_format($loan->principal_amount, 0) }}</td>
                <td>{{ $loan->product->name }}</td>
                <td>{{ number_format($loan->interest_amount, 0) }}</td>
                <td>{{ $loan->branch->name }}</td>
                <td>{{ $loan->relationshipOfficer->name }}</td>
                <td>{{ ucfirst($loan->purpose) }}</td>
                <td>
                    @php
                        $statusClass = match($loan->status) {
                            'partially_approved' => 'status-partially-approved',
                            'active', 'disbursed', 'approved' => 'status-active',
                            'rejected' => 'status-rejected',
                            default => 'status-pending'
                        };
                        $statusLabel = match($loan->status) {
                            'partially_approved' => 'Partially Approved',
                            'active' => 'Active',
                            'disbursed' => 'Disbursed',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            default => ucfirst($loan->status)
                        };
                    @endphp
                    <span class="status {{ $statusClass }}">{{ $statusLabel }}</span>
                </td>
                <td style="font-size: 12px; color: var(--text-secondary);">
                    {{ $loan->created_at->format('d-m-Y') }}<<br>
                    {{ $loan->created_at->format('h:i A') }}
                </td>
                <td style="font-size: 12px;">{{ $loan->relationshipOfficer->name }}</td>
                <td>
                    <div style="display: flex; gap: 5px;">
                        <button class="btn btn-primary" style="padding: 5px 12px; font-size: 12px;" onclick="approveLoan({{ $loan->id }})">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-outline" style="padding: 5px 12px; font-size: 12px; color: var(--danger); border-color: var(--danger);" onclick="rejectLoan({{ $loan->id }})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="13">
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        No loans pending approval
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    @if($loans->hasPages())
    <div style="margin-top: 16px;">
        {{ $loans->withQueryString()->links() }}
    </div>
    @endif
</div>

{{-- Approval Modal --}}
<div id="approveModal" class="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Approve Loan</div>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="loanDetails"></div>
            <div class="form-group">
                <label class="form-label">Approval Notes</label>
                <textarea id="approvalNotes" rows="3" class="form-control"></textarea>
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label class="form-label">Approval Date <span style="color:var(--danger)">*</span></label>
                <input type="date" id="approvalDate" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control">
                <div style="font-size:11px; color:var(--text-secondary); margin-top:3px;">Defaults to today. Select a past date to backdate.</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="confirmApproval()">Confirm Approval</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentLoanId = null;

    function approveLoan(id) {
        currentLoanId = id;
        document.getElementById('approveModal').classList.add('show');
    }

    function closeModal() {
        document.getElementById('approveModal').classList.remove('show');
        currentLoanId = null;
    }

    function confirmApproval() {
        if (!currentLoanId) return;

        const btn = document.querySelector('#approveModal .btn-primary');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving…';

        const approvedDate = document.getElementById('approvalDate').value;
        console.log('Approving loan:', currentLoanId, 'with date:', approvedDate);

        fetch(`/loans/${currentLoanId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                _method: 'PATCH',
                notes: document.getElementById('approvalNotes').value,
                approved_at_date: approvedDate
            })
        })
        .then(async r => {
            const text = await r.text();
            console.log('Response status:', r.status, 'body:', text.substring(0, 500));
            try {
                return JSON.parse(text);
            } catch (e) {
                // Server returned HTML error page
                console.error('Server returned non-JSON response:', text.substring(0, 500));
                throw new Error('Server error ' + r.status + ': ' + text.substring(0, 200));
            }
        })
        .then(data => {
            if (data.success) {
                closeModal();
                location.reload();
            } else {
                alert(data.message || 'Approval failed. Please try again.');
                btn.disabled = false;
                btn.innerHTML = 'Confirm Approval';
            }
        })
        .catch(err => {
            console.error('Approval error:', err);
            alert('Error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = 'Confirm Approval';
        });
    }

    function rejectLoan(id) {
        const reason = prompt('Enter rejection reason:');
        if (!reason || !reason.trim()) return;

        fetch(`/loans/${id}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ _method: 'PATCH', reason: reason.trim() })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert(data.message || 'Rejection failed.');
        })
        .catch(() => alert('Network error. Please try again.'));
    }
</script>
@endsection