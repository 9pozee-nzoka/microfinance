{{--
    Reusable record-payment modal.
    Include once per page where openRecordPaymentModal() is needed.
--}}
<div id="recordPaymentModal" class="modal-overlay" onclick="if(event.target===this)closeModal('recordPaymentModal')">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-money-bill-wave" style="color:var(--primary);"></i> Record Payment</div>
            <button type="button" class="modal-close" onclick="closeModal('recordPaymentModal')">&times;</button>
        </div>

        <div id="recordPaymentInfo" style="background:#F8FAFC; border-radius:8px; padding:12px 14px; margin-bottom:18px; font-size:13px; border:1px solid var(--border);">
            <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                <span style="color:var(--text-secondary);">Customer</span>
                <strong id="rpCustomerName">-</strong>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--text-secondary);">Suggested Amount</span>
                <strong style="color:var(--primary);" id="rpSuggestedAmount">-</strong>
            </div>
        </div>

        <form id="recordPaymentForm" method="POST" action="{{ route('transactions.store') }}">
            @csrf
            <input type="hidden" name="transaction_type" value="loan_repayment">
            <input type="hidden" name="customer_id" id="rpCustomerId">
            <input type="hidden" name="loan_id" id="rpLoanId">

            <div class="form-group">
                <label class="form-label">Payment Source <span style="color:var(--danger)">*</span></label>
                <select class="form-control" name="source" id="rpSource" onchange="togglePaymentFields()" required>
                    <option value="cash" selected>Cash</option>
                    <option value="mpesa">M-Pesa</option>
                    <option value="bank">Bank</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Amount (KSH) <span style="color:var(--danger)">*</span></label>
                <input type="number" class="form-control" name="amount" id="rpAmount" min="1" step="0.01" required>
            </div>

            <div class="form-group">
                <label class="form-label">Payment Date <span style="color:var(--danger)">*</span></label>
                <input type="date" class="form-control" name="payment_date" id="rpPaymentDate" value="{{ today()->format('Y-m-d') }}" required>
            </div>

            <div class="form-group" id="rpMpesaRefGroup" style="display:none;">
                <label class="form-label">M-Pesa Receipt <span style="color:var(--danger)">*</span></label>
                <input type="text" class="form-control" name="mpesa_receipt" id="rpMpesaReceipt">
            </div>

            <div class="form-group" id="rpBankRefGroup" style="display:none;">
                <label class="form-label">Bank Reference <span style="color:var(--danger)">*</span></label>
                <input type="text" class="form-control" name="bank_reference" id="rpBankReference">
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" id="rpNotes" rows="2"></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('recordPaymentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Record Payment
                </button>
            </div>
        </form>
    </div>
</div>

@once
<script>
    function openModal(id) {
        document.getElementById(id).classList.add('show');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
    }

    function openRecordPaymentModal(loanId, customerId, customerName, amount, phone) {
        document.getElementById('rpCustomerId').value = customerId;
        document.getElementById('rpLoanId').value = loanId;
        document.getElementById('rpCustomerName').textContent = customerName || 'N/A';
        document.getElementById('rpSuggestedAmount').textContent = amount ? 'KSH ' + Number(amount).toLocaleString('en-KE', {minimumFractionDigits: 0, maximumFractionDigits: 2}) : '-';
        document.getElementById('rpAmount').value = amount ? Number(amount).toFixed(2) : '';
        document.getElementById('rpSource').value = 'cash';
        document.getElementById('rpMpesaReceipt').value = '';
        document.getElementById('rpBankReference').value = '';
        document.getElementById('rpNotes').value = '';
        togglePaymentFields();
        openModal('recordPaymentModal');
    }

    function togglePaymentFields() {
        const source = document.getElementById('rpSource').value;
        document.getElementById('rpMpesaRefGroup').style.display = source === 'mpesa' ? 'block' : 'none';
        document.getElementById('rpBankRefGroup').style.display = source === 'bank' ? 'block' : 'none';
        document.getElementById('rpMpesaReceipt').required = source === 'mpesa';
        document.getElementById('rpBankReference').required = source === 'bank';
    }
</script>
@endonce
