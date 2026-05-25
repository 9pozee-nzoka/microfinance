{{-- resources/views/loans/approve.blade.php --}}
@extends('layouts.app')

@section('title', 'Approve New Loans - GetCash Capital')
@section('page-title', 'Approve New Loans')

@section('content')
<div class="card">
    <div class="card-header" style="margin-bottom: 20px;">
        <div class="search-box" style="width: 400px;">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search By any Field" id="loanSearch">
        </div>
        <div class="search-box" style="width: 250px;">
            <i class="fas fa-phone"></i>
            <input type="text" placeholder="Search By Phone NO." id="phoneSearch">
        </div>
    </div>

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
                <td colspan="13" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                    <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 10px; display: block;"></i>
                    No loans pending approval
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Approval Modal --}}
<div id="approveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 30px; width: 500px; max-width: 90%;">
        <h3 style="margin-bottom: 20px;">Approve Loan</h3>
        <div id="loanDetails" style="margin-bottom: 20px;"></div>
        <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 5px;">Approval Notes</label>
            <textarea id="approvalNotes" rows="3" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 6px; resize: vertical;"></textarea>
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="confirmApproval()">Confirm Approval</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentLoanId = null;

    function approveLoan(id) {
        currentLoanId = id;
        document.getElementById('approveModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('approveModal').style.display = 'none';
        currentLoanId = null;
    }

    function confirmApproval() {
        if (!currentLoanId) return;
        
        fetch(`/api/loans/${currentLoanId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                notes: document.getElementById('approvalNotes').value
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Approval failed');
            }
        });
    }

    function rejectLoan(id) {
        if (!confirm('Are you sure you want to reject this loan?')) return;
        
        fetch(`/api/loans/${id}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ reason: 'Rejected by officer' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
    }
</script>
@endsection