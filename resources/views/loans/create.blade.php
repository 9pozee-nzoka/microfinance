@extends('layouts.app')

@section('title', 'New Loan Application - Mweela Cash Capital')
@section('page-title', 'New Loan Application')

@section('content')

<div style="margin-bottom: 20px;">
    <a href="{{ route('loans.index') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back to Loans
    </a>
</div>

@if($errors->any())
<div class="flash-error">
    <div style="font-weight:600; margin-bottom:6px;"><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</div>
    <ul style="margin:0; padding-left:18px; font-size:13px;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('loans.store') }}" id="loanForm">
    @csrf

    {{-- ── Section 1: Customer ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-user"></i> Customer</div>

        @if(isset($customer))
            {{-- Pre-filled from customer profile --}}
            <input type="hidden" name="customer_id" value="{{ $customer->id }}">

            {{-- Outstanding loan block --}}
            @if($hasActiveLoan)
            <div class="flash-error" style="margin-bottom:0;">
                <i class="fas fa-ban" style="font-size:20px;"></i>
                <div>
                    <div style="font-weight:700; font-size:14px;">Cannot Apply — Outstanding Loan Exists</div>
                    <div style="font-size:13px; margin-top:4px;">
                        <strong>{{ $customer->full_name }}</strong> has an active loan
                        <strong>{{ $activeLoan->loan_number }}</strong>
                        (Status: <strong>{{ ucfirst($activeLoan->status) }}</strong>,
                        Balance: <strong>KSH {{ number_format($activeLoan->outstanding_balance, 0) }}</strong>).
                        The customer must complete or close this loan before applying for a new one.
                    </div>
                    <a href="{{ route('loans.show', $activeLoan) }}" class="btn btn-outline" style="margin-top:10px; font-size:12px;">
                        <i class="fas fa-eye"></i> View Active Loan
                    </a>
                </div>
            </div>
            @else
            <div class="selected-customer-badge">
                <i class="fas fa-user-check" style="color:var(--success); font-size:18px;"></i>
                <div>
                    <div style="font-weight:700;">
                        {{ $customer->full_name }}
                        @if($isReturningCustomer)
                            <span style="background:#E8F5E9; color:#2E7D32; font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; margin-left:6px;">
                                <i class="fas fa-redo-alt"></i> Returning Customer
                            </span>
                        @else
                            <span style="background:#E3F2FD; color:#1565C0; font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; margin-left:6px;">
                                <i class="fas fa-star"></i> First-Time Customer
                            </span>
                        @endif
                    </div>
                    <div style="font-size:12px; color:var(--text-secondary);">
                        {{ $customer->customer_number }} &nbsp;·&nbsp; {{ $customer->phone_number }}
                        &nbsp;·&nbsp; Credit Score: <strong>{{ $customer->credit_score }}</strong>
                        &nbsp;·&nbsp; Savings: <strong>KSH {{ number_format($customer->savings_balance, 0) }}</strong>
                    </div>
                </div>
            </div>
            @endif
        @else
            <div class="form-group">
                <label class="form-label">Search Customer <span class="req">*</span></label>
                <div class="customer-search-wrap">
                    <input type="text" id="customerSearch" autocomplete="off"
                           class="form-control" placeholder="Type name, phone or customer number…"
                           oninput="searchCustomers(this.value)">
                    <div class="customer-dropdown" id="customerDropdown"></div>
                </div>
                <input type="hidden" name="customer_id" id="customerId" value="{{ old('customer_id') }}">

                {{-- Dynamic status badge shown after selection --}}
                <div id="selectedCustomerBadge" style="display:none;" class="selected-customer-badge">
                    <i class="fas fa-user-check" style="color:var(--success); font-size:18px;"></i>
                    <div id="selectedCustomerInfo"></div>
                </div>

                {{-- Outstanding loan warning --}}
                <div id="activeLoanWarning" style="display:none;" class="flash-error" style="margin-top:8px;">
                    <i class="fas fa-ban"></i>
                    <div id="activeLoanWarningText"></div>
                </div>

                @error('customer_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        @endif
    </div>

    {{-- ── Section 2: Loan Details ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-file-invoice-dollar"></i> Loan Details</div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Loan Product <span class="req">*</span></label>
                <select name="product_id" id="productSelect"
                        class="form-control {{ $errors->has('product_id') ? 'is-invalid' : '' }}"
                        onchange="onProductChange()" required>
                    <option value="">-- Select Product --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                                data-min="{{ $product->min_amount }}"
                                data-max="{{ $product->max_amount }}"
                                data-min-weeks="{{ $product->min_term_weeks }}"
                                data-max-weeks="{{ $product->max_term_weeks }}"
                                data-rate="{{ $product->interest_rate }}"
                                data-method="{{ $product->interest_method }}"
                                {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} — {{ $product->interest_rate }}% p.a.
                        </option>
                    @endforeach
                </select>
                @error('product_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                <div id="productHint" class="form-hint"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Principal Amount (KSH) <span class="req">*</span></label>
                <input type="number" name="principal_amount" id="principalAmount"
                       value="{{ old('principal_amount') }}"
                       class="form-control {{ $errors->has('principal_amount') ? 'is-invalid' : '' }}"
                       placeholder="0.00" min="1" step="0.01"
                       oninput="recalculate()" required>
                @error('principal_amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Term (Weeks) <span class="req">*</span></label>
                <input type="number" name="term_weeks" id="termWeeks"
                       value="{{ old('term_weeks') }}"
                       class="form-control {{ $errors->has('term_weeks') ? 'is-invalid' : '' }}"
                       placeholder="e.g. 6" min="1"
                       oninput="recalculate()" required>
                @error('term_weeks')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Loan Purpose <span class="req">*</span></label>
                <select name="purpose" class="form-control {{ $errors->has('purpose') ? 'is-invalid' : '' }}" required>
                    <option value="">-- Select Purpose --</option>
                    @foreach(['business' => 'Business', 'education' => 'Education', 'medical' => 'Medical', 'agriculture' => 'Agriculture', 'home_improvement' => 'Home Improvement', 'other' => 'Other'] as $val => $label)
                        <option value="{{ $val }}" {{ old('purpose') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('purpose')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Purpose Description</label>
                <input type="text" name="purpose_description" value="{{ old('purpose_description') }}"
                       class="form-control" placeholder="Brief description of how funds will be used">
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Branch <span class="req">*</span></label>
                <select name="branch_id" class="form-control {{ $errors->has('branch_id') ? 'is-invalid' : '' }}" required>
                    <option value="">-- Select Branch --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', auth()->user()->branch_id) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

        </div>
    </div>

    {{-- ── Section 3: Collateral ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-shield-alt"></i> Collateral (Optional)</div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Collateral Description</label>
                <input type="text" name="collateral_description" value="{{ old('collateral_description') }}"
                       class="form-control" placeholder="e.g. Motor vehicle KBZ 123A, Land title LR/1234">
            </div>
            <div class="form-group">
                <label class="form-label">Estimated Value (KSH)</label>
                <input type="text" name="collateral_value" value="{{ old('collateral_value') }}"
                       class="form-control" placeholder="e.g. 500,000">
            </div>
        </div>
    </div>

    {{-- ── Section 4: Guarantors ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-handshake"></i> Guarantors</div>
        <div id="guarantorsContainer"></div>
        <button type="button" class="btn btn-outline" onclick="addGuarantor()" style="font-size:13px;">
            <i class="fas fa-plus"></i> Add Guarantor
        </button>
        <span class="form-hint" style="display:inline-block; margin-left:10px;">Add customers who will guarantee this loan</span>
    </div>

    {{-- ── Section 5: Processing Fee ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-receipt"></i> Processing Fee</div>
        <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">
            The processing fee is collected <strong>before disbursement</strong> and is <strong>not</strong> included in the weekly repayment installments.
        </p>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Processing Fee Amount (KSH) <span class="req">*</span></label>
                <input type="number" name="processing_fee" id="processingFee"
                       value="{{ old('processing_fee', $processingFee ?? 700) }}"
                       class="form-control {{ $errors->has('processing_fee') ? 'is-invalid' : '' }}"
                       placeholder="700" min="0" step="0.01" required>
                <div class="form-hint" id="processingFeeHint">
                    @if(isset($isReturningCustomer))
                        {{ $isReturningCustomer ? 'Returning customer fee: KSH 500' : 'First-time customer fee: KSH 700' }}
                    @else
                        First-time: KSH 700 &nbsp;·&nbsp; Returning: KSH 500 (auto-set when customer is selected)
                    @endif
                </div>
                @error('processing_fee')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Payment Method <span class="req">*</span></label>
                <select name="processing_fee_method" id="processingFeeMethod"
                        class="form-control {{ $errors->has('processing_fee_method') ? 'is-invalid' : '' }}" required>
                    <option value="cash"          {{ old('processing_fee_method','cash') === 'cash'          ? 'selected':'' }}>Cash</option>
                    <option value="mpesa"         {{ old('processing_fee_method') === 'mpesa'         ? 'selected':'' }}>M-Pesa</option>
                    <option value="bank_transfer" {{ old('processing_fee_method') === 'bank_transfer' ? 'selected':'' }}>Bank Transfer</option>
                </select>
                @error('processing_fee_method')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" id="processingFeeRefGroup" style="display:none;">
                <label class="form-label">M-Pesa / Reference No.</label>
                <input type="text" name="processing_fee_reference" value="{{ old('processing_fee_reference') }}"
                       class="form-control" placeholder="e.g. QAB1234XYZ">
            </div>
        </div>
    </div>

    {{-- ── Section 6: Loan Summary ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-calculator"></i> Loan Summary</div>
        <div id="calcBox" class="calc-box" style="display:none;">
            <div class="calc-row">
                <span class="label">Principal Amount</span>
                <span class="value" id="calcPrincipal">KSH 0</span>
            </div>
            <div class="calc-row">
                <span class="label">Interest (<span id="calcRateLabel">0%</span>)</span>
                <span class="value" id="calcInterest">KSH 0</span>
            </div>
            <div class="calc-row" style="color:var(--text-secondary);">
                <span class="label">Processing Fee <small>(paid before disbursement — not in installments)</small></span>
                <span class="value" id="calcProcessingFee">KSH 200</span>
            </div>
            <div class="calc-row total">
                <span class="label">Total Repayable (excl. processing fee)</span>
                <span class="value" id="calcTotal">KSH 0</span>
            </div>
            <div style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(0,188,212,0.2); display:flex; gap:24px; font-size:12px; color:var(--text-secondary);">
                <span><i class="fas fa-calendar-week" style="color:var(--primary);"></i> Weekly Installment: <strong id="calcWeekly" style="color:var(--text-primary);">KSH 0</strong></span>
                <span><i class="fas fa-clock" style="color:var(--primary);"></i> Term: <strong id="calcTerm" style="color:var(--text-primary);">0 weeks</strong></span>
            </div>
        </div>
        <div id="calcPlaceholder" style="text-align:center; padding:30px; color:var(--text-secondary); font-size:13px;">
            <i class="fas fa-calculator" style="font-size:32px; opacity:0.2; display:block; margin-bottom:10px;"></i>
            Select a product and enter an amount to see the loan summary
        </div>

        {{-- Hidden computed fields --}}
        <input type="hidden" name="interest_amount"    id="hiddenInterest">
        <input type="hidden" name="insurance_fee"      id="hiddenInsurance" value="0">
        <input type="hidden" name="total_repayable"    id="hiddenTotal">
        <input type="hidden" name="weekly_installment" id="hiddenWeekly">
        <input type="hidden" name="application_date"   id="hiddenApplicationDate" value="{{ old('application_date', today()->toDateString()) }}">
    </div>

    {{-- ── Section 7: Application Date (Backdating) ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-calendar-alt"></i> Application Date</div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Date Created <span class="req">*</span></label>
                <input type="date" name="created_at_date" id="createdAtDate"
                       value="{{ old('created_at_date', today()->toDateString()) }}"
                       max="{{ today()->toDateString() }}"
                       class="form-control {{ $errors->has('created_at_date') ? 'is-invalid' : '' }}"
                       onchange="onDateChange()" required>
                <div class="form-hint">Defaults to today. Select a past date to backdate the loan.</div>
                @error('created_at_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    {{-- ── Submit ── --}}
    <div style="display:flex; justify-content:flex-end; gap:12px; padding-bottom:30px;">
        <a href="{{ route('loans.index') }}" class="btn btn-outline" style="padding:10px 24px;">
            <i class="fas fa-times"></i> Cancel
        </a>
        @if(isset($hasActiveLoan) && $hasActiveLoan)
            <button type="submit" class="btn btn-primary" style="padding:10px 28px; font-size:14px;" disabled>
                <i class="fas fa-ban"></i> Cannot Apply — Outstanding Loan
            </button>
        @else
            <button type="submit" class="btn btn-primary" style="padding:10px 28px; font-size:14px;" id="submitBtn">
                <i class="fas fa-paper-plane"></i> Submit Application
            </button>
        @endif
    </div>

</form>
@endsection

@section('scripts')
<script>
// ── Customer search ──────────────────────────────────────────────
let searchTimer;
function searchCustomers(q) {
    clearTimeout(searchTimer);
    const dd = document.getElementById('customerDropdown');
    if (q.length < 2) { dd.style.display = 'none'; return; }
    searchTimer = setTimeout(() => {
        fetch(`/api/customers/search?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                if (!data.length) { dd.style.display = 'none'; return; }
                dd.innerHTML = data.map(c => `
                    <div class="customer-option"
                         onclick="selectCustomer(${c.id},'${escHtml(c.full_name)}','${c.phone_number}','${c.customer_number}')">
                        <strong>${c.full_name}</strong>
                        <span style="color:var(--text-secondary); margin-left:8px;">${c.phone_number}</span>
                        <span style="float:right; font-size:11px; color:var(--primary);">${c.customer_number}</span>
                    </div>`).join('');
                dd.style.display = 'block';
            });
    }, 280);
}

function selectCustomer(id, name, phone, num) {
    document.getElementById('customerId').value = id;
    document.getElementById('customerSearch').value = name;
    document.getElementById('customerDropdown').style.display = 'none';

    // Reset state
    document.getElementById('activeLoanWarning').style.display = 'none';
    document.getElementById('submitBtn').disabled = false;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';

    // Check loan eligibility for this customer
    fetch(`/api/customers/${id}/loan-eligibility`)
        .then(r => r.json())
        .then(data => {
            // Show customer badge
            const badge = document.getElementById('selectedCustomerBadge');
            badge.style.display = 'flex';
            const typeTag = data.is_returning
                ? `<span style="background:#E8F5E9;color:#2E7D32;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;margin-left:6px;"><i class="fas fa-redo-alt"></i> Returning Customer</span>`
                : `<span style="background:#E3F2FD;color:#1565C0;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;margin-left:6px;"><i class="fas fa-star"></i> First-Time Customer</span>`;
            document.getElementById('selectedCustomerInfo').innerHTML =
                `<div style="font-weight:700;">${name} ${typeTag}</div>
                 <div style="font-size:12px;color:var(--text-secondary);">${num} &nbsp;·&nbsp; ${phone}</div>`;

            // Auto-set processing fee
            const fee = data.processing_fee;
            document.getElementById('processingFee').value = fee;
            document.getElementById('processingFeeHint').textContent =
                data.is_returning
                    ? `Returning customer fee: KSH ${fee.toLocaleString()}`
                    : `First-time customer fee: KSH ${fee.toLocaleString()}`;
            const pfEl = document.getElementById('calcProcessingFee');
            if (pfEl) pfEl.textContent = 'KSH ' + fee.toLocaleString('en-KE');

            // Block if has active loan
            if (data.has_active_loan) {
                document.getElementById('activeLoanWarning').style.display = 'flex';
                document.getElementById('activeLoanWarningText').innerHTML =
                    `<strong>Cannot apply</strong> — this customer has an outstanding loan 
                    <strong>${data.active_loan_number}</strong> 
                    (${data.active_loan_status}, KSH ${Number(data.active_loan_balance).toLocaleString('en-KE')} remaining). 
                    They must complete it before applying again.`;
                document.getElementById('submitBtn').disabled = true;
                document.getElementById('submitBtn').innerHTML = '<i class="fas fa-ban"></i> Cannot Apply — Outstanding Loan';
            }
        });
}

document.addEventListener('click', e => {
    if (!e.target.closest('#customerSearch') && !e.target.closest('#customerDropdown'))
        document.getElementById('customerDropdown') && (document.getElementById('customerDropdown').style.display = 'none');
});

function escHtml(s) { return s.replace(/'/g,"&#39;").replace(/"/g,'&quot;'); }

// ── Processing fee method toggle ────────────────────────────────
document.getElementById('processingFeeMethod').addEventListener('change', function () {
    document.getElementById('processingFeeRefGroup').style.display =
        this.value !== 'cash' ? 'block' : 'none';
});

// ── Product change ───────────────────────────────────────────────
function onProductChange() {
    const sel  = document.getElementById('productSelect');
    const opt  = sel.options[sel.selectedIndex];
    if (!opt.value) { document.getElementById('productHint').textContent = ''; return; }

    const min  = parseFloat(opt.dataset.min);
    const max  = parseFloat(opt.dataset.max);
    const minW = opt.dataset.minWeeks;
    const maxW = opt.dataset.maxWeeks;

    document.getElementById('productHint').textContent =
        `Amount: KSH ${fmt(min)} – ${fmt(max)} · Term: ${minW}–${maxW} weeks`;

    const termInput = document.getElementById('termWeeks');
    termInput.min = minW;
    termInput.max = maxW;
    if (!termInput.value) termInput.value = minW;

    // Fetch product-specific rates
    fetch(`/api/loan-products/${opt.value}/rates`)
        .then(r => r.json())
        .then(data => { window._productRates = data; recalculate(); });
}

// ── Loan calculator ──────────────────────────────────────────────
// Interest = principal × rate%  (flat, total — not annualised)
// Processing fee is paid before disbursement — NOT included in installments.
function recalculate() {
    const sel       = document.getElementById('productSelect');
    const opt       = sel.options[sel.selectedIndex];
    const principal = parseFloat(document.getElementById('principalAmount').value) || 0;
    const weeks     = parseInt(document.getElementById('termWeeks').value) || 0;
    const procFee   = parseFloat(document.getElementById('processingFee').value) || 0;

    if (!opt.value || principal <= 0 || weeks <= 0) {
        document.getElementById('calcBox').style.display = 'none';
        document.getElementById('calcPlaceholder').style.display = 'block';
        return;
    }

    // Find matching rate from product rates table
    const rates  = window._productRates || [];
    const match  = rates.find(r => parseFloat(r.principal_amount) === principal && parseInt(r.term_weeks) === weeks);
    const rate   = match ? parseFloat(match.interest_rate) : parseFloat(opt.dataset.rate);
    const method = opt.dataset.method;

    let interest;
    if (method === 'flat') {
        // Flat = principal × rate% (total over full term)
        interest = principal * (rate / 100);
    } else {
        const weeklyRate = (rate / 100) / 52;
        const installment = principal * (weeklyRate / (1 - Math.pow(1 + weeklyRate, -weeks)));
        interest = (installment * weeks) - principal;
    }

    const total  = principal + interest;   // processing fee NOT included
    const weekly = total / weeks;

    document.getElementById('calcBox').style.display = 'block';
    document.getElementById('calcPlaceholder').style.display = 'none';
    document.getElementById('calcPrincipal').textContent    = 'KSH ' + fmt(principal);
    document.getElementById('calcRateLabel').textContent    = rate + '% flat';
    document.getElementById('calcInterest').textContent     = 'KSH ' + fmt(interest);
    document.getElementById('calcProcessingFee').textContent = 'KSH ' + fmt(procFee);
    document.getElementById('calcTotal').textContent        = 'KSH ' + fmt(total);
    document.getElementById('calcWeekly').textContent       = 'KSH ' + fmt(weekly);
    document.getElementById('calcTerm').textContent         = weeks + ' weeks';

    document.getElementById('hiddenInterest').value    = interest.toFixed(2);
    document.getElementById('hiddenInsurance').value   = '0';
    document.getElementById('hiddenTotal').value       = total.toFixed(2);
    document.getElementById('hiddenWeekly').value      = weekly.toFixed(2);
}

// Update processing fee display when field changes
document.getElementById('processingFee').addEventListener('input', function () {
    const el = document.getElementById('calcProcessingFee');
    if (el) el.textContent = 'KSH ' + fmt(parseFloat(this.value) || 0);
});

function fmt(n) { return Number(n.toFixed(2)).toLocaleString('en-KE'); }

// ── Backdating: sync application_date with created_at_date ──
function onDateChange() {
    const dateVal = document.getElementById('createdAtDate').value;
    document.getElementById('hiddenApplicationDate').value = dateVal;
}

// ── Guarantors ───────────────────────────────────────────────────
let guarantorCount = 0;
function addGuarantor() {
    const idx = guarantorCount++;
    const row = document.createElement('div');
    row.className = 'guarantor-row';
    row.id = `gRow${idx}`;
    row.innerHTML = `
        <div style="flex:2; position:relative;">
            <label class="form-label">Guarantor Customer</label>
            <input type="text" id="gSearch${idx}" autocomplete="off"
                   class="form-control" placeholder="Search by name or phone…"
                   oninput="searchGuarantor(${idx}, this.value)">
            <div id="gDrop${idx}" class="customer-dropdown"></div>
            <input type="hidden" name="guarantors[${idx}][customer_id]" id="gId${idx}">
            <div id="gBadge${idx}" style="display:none; margin-top:6px; font-size:12px; color:var(--success);">
                <i class="fas fa-check-circle"></i> <span></span>
            </div>
        </div>
        <div style="flex:1;">
            <label class="form-label">Guaranteed Amount (KSH)</label>
            <input type="number" name="guarantors[${idx}][amount]" class="form-control"
                   placeholder="0.00" min="0" step="0.01">
        </div>
        <div style="padding-bottom:2px;">
            <button type="button" onclick="removeGuarantor(${idx})"
                    class="btn btn-outline" style="color:var(--danger); border-color:var(--danger); padding:8px 12px;">
                <i class="fas fa-trash"></i>
            </button>
        </div>`;
    document.getElementById('guarantorsContainer').appendChild(row);
}

function removeGuarantor(idx) {
    document.getElementById(`gRow${idx}`)?.remove();
}

let gTimers = {};
function searchGuarantor(idx, q) {
    clearTimeout(gTimers[idx]);
    const dd = document.getElementById(`gDrop${idx}`);
    if (q.length < 2) { dd.style.display = 'none'; return; }
    gTimers[idx] = setTimeout(() => {
        fetch(`/api/customers/search?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                if (!data.length) { dd.style.display = 'none'; return; }
                dd.innerHTML = data.map(c => `
                    <div class="customer-option"
                         onclick="selectGuarantor(${idx},${c.id},'${escHtml(c.full_name)}','${c.phone_number}')">
                        <strong>${c.full_name}</strong>
                        <span style="color:var(--text-secondary); margin-left:8px;">${c.phone_number}</span>
                    </div>`).join('');
                dd.style.display = 'block';
            });
    }, 280);
}

function selectGuarantor(idx, id, name, phone) {
    document.getElementById(`gId${idx}`).value = id;
    document.getElementById(`gSearch${idx}`).value = name;
    document.getElementById(`gDrop${idx}`).style.display = 'none';
    const badge = document.getElementById(`gBadge${idx}`);
    badge.style.display = 'block';
    badge.querySelector('span').textContent = `${name} — ${phone}`;
}

// Restore product hint on page load (validation error)
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('productSelect').value) {
        onProductChange();
    }
});
</script>
@endsection
