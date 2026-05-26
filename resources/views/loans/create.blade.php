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
            <div class="selected-customer-badge">
                <i class="fas fa-user-check" style="color:var(--success); font-size:18px;"></i>
                <div>
                    <div style="font-weight:700;">{{ $customer->full_name }}</div>
                    <div style="font-size:12px; color:var(--text-secondary);">
                        {{ $customer->customer_number }} &nbsp;·&nbsp; {{ $customer->phone_number }}
                        &nbsp;·&nbsp; Credit Score: <strong>{{ $customer->credit_score }}</strong>
                        &nbsp;·&nbsp; Savings: <strong>KSH {{ number_format($customer->savings_balance, 0) }}</strong>
                    </div>
                </div>
            </div>
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
                <div id="selectedCustomerBadge" style="display:none;" class="selected-customer-badge">
                    <i class="fas fa-user-check" style="color:var(--success); font-size:18px;"></i>
                    <div id="selectedCustomerInfo"></div>
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
                                data-proc="{{ $product->processing_fee_rate }}"
                                data-ins="{{ $product->insurance_fee_rate }}"
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
            <div class="form-group">
                <label class="form-label">Relationship Officer <span class="req">*</span></label>
                <select name="relationship_officer_id" class="form-control {{ $errors->has('relationship_officer_id') ? 'is-invalid' : '' }}" required>
                    <option value="">-- Select Officer --</option>
                    @foreach($officers as $officer)
                        <option value="{{ $officer->id }}" {{ old('relationship_officer_id', auth()->id()) == $officer->id ? 'selected' : '' }}>
                            {{ $officer->name }}
                        </option>
                    @endforeach
                </select>
                @error('relationship_officer_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
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

    {{-- ── Section 5: Loan Calculator ── --}}
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
            <div class="calc-row">
                <span class="label">Processing Fee</span>
                <span class="value" id="calcProcessing">KSH 0</span>
            </div>
            <div class="calc-row">
                <span class="label">Insurance Fee</span>
                <span class="value" id="calcInsurance">KSH 0</span>
            </div>
            <div class="calc-row total">
                <span class="label">Total Repayable</span>
                <span class="value" id="calcTotal">KSH 0</span>
            </div>
            <div style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(0,188,212,0.2); display:flex; gap:24px; font-size:12px; color:var(--text-secondary);">
                <span><i class="fas fa-calendar-week" style="color:var(--primary);"></i> Weekly: <strong id="calcWeekly" style="color:var(--text-primary);">KSH 0</strong></span>
                <span><i class="fas fa-clock" style="color:var(--primary);"></i> Term: <strong id="calcTerm" style="color:var(--text-primary);">0 weeks</strong></span>
            </div>
        </div>
        <div id="calcPlaceholder" style="text-align:center; padding:30px; color:var(--text-secondary); font-size:13px;">
            <i class="fas fa-calculator" style="font-size:32px; opacity:0.2; display:block; margin-bottom:10px;"></i>
            Select a product and enter an amount to see the loan summary
        </div>

        {{-- Hidden computed fields --}}
        <input type="hidden" name="interest_amount"   id="hiddenInterest">
        <input type="hidden" name="processing_fee"    id="hiddenProcessing">
        <input type="hidden" name="insurance_fee"     id="hiddenInsurance">
        <input type="hidden" name="total_repayable"   id="hiddenTotal">
        <input type="hidden" name="weekly_installment" id="hiddenWeekly">
        <input type="hidden" name="application_date"  value="{{ today()->toDateString() }}">
    </div>

    {{-- ── Submit ── --}}
    <div style="display:flex; justify-content:flex-end; gap:12px; padding-bottom:30px;">
        <a href="{{ route('loans.index') }}" class="btn btn-outline" style="padding:10px 24px;">
            <i class="fas fa-times"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary" style="padding:10px 28px; font-size:14px;" id="submitBtn">
            <i class="fas fa-paper-plane"></i> Submit Application
        </button>
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
    const badge = document.getElementById('selectedCustomerBadge');
    badge.style.display = 'flex';
    document.getElementById('selectedCustomerInfo').innerHTML =
        `<div style="font-weight:700;">${name}</div>
         <div style="font-size:12px;color:var(--text-secondary);">${num} &nbsp;·&nbsp; ${phone}</div>`;
}

document.addEventListener('click', e => {
    if (!e.target.closest('#customerSearch') && !e.target.closest('#customerDropdown'))
        document.getElementById('customerDropdown') && (document.getElementById('customerDropdown').style.display = 'none');
});

function escHtml(s) { return s.replace(/'/g,"&#39;").replace(/"/g,'&quot;'); }

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

    recalculate();
}

// ── Loan calculator ──────────────────────────────────────────────
function recalculate() {
    const sel       = document.getElementById('productSelect');
    const opt       = sel.options[sel.selectedIndex];
    const principal = parseFloat(document.getElementById('principalAmount').value) || 0;
    const weeks     = parseInt(document.getElementById('termWeeks').value) || 0;

    if (!opt.value || principal <= 0 || weeks <= 0) {
        document.getElementById('calcBox').style.display = 'none';
        document.getElementById('calcPlaceholder').style.display = 'block';
        return;
    }

    const rate    = parseFloat(opt.dataset.rate);
    const method  = opt.dataset.method;
    const procRate = parseFloat(opt.dataset.proc);
    const insRate  = parseFloat(opt.dataset.ins);

    let interest;
    if (method === 'flat') {
        interest = principal * (rate / 100) * (weeks / 52);
    } else {
        const weeklyRate = (rate / 100) / 52;
        const installment = principal * (weeklyRate / (1 - Math.pow(1 + weeklyRate, -weeks)));
        interest = (installment * weeks) - principal;
    }

    const processing = principal * (procRate / 100);
    const insurance  = principal * (insRate  / 100);
    const total      = principal + interest + processing + insurance;
    const weekly     = total / weeks;

    // Update display
    document.getElementById('calcBox').style.display = 'block';
    document.getElementById('calcPlaceholder').style.display = 'none';
    document.getElementById('calcPrincipal').textContent  = 'KSH ' + fmt(principal);
    document.getElementById('calcRateLabel').textContent  = rate + '% p.a. (' + method + ')';
    document.getElementById('calcInterest').textContent   = 'KSH ' + fmt(interest);
    document.getElementById('calcProcessing').textContent = 'KSH ' + fmt(processing);
    document.getElementById('calcInsurance').textContent  = 'KSH ' + fmt(insurance);
    document.getElementById('calcTotal').textContent      = 'KSH ' + fmt(total);
    document.getElementById('calcWeekly').textContent     = 'KSH ' + fmt(weekly);
    document.getElementById('calcTerm').textContent       = weeks + ' weeks';

    // Populate hidden fields
    document.getElementById('hiddenInterest').value   = interest.toFixed(2);
    document.getElementById('hiddenProcessing').value = processing.toFixed(2);
    document.getElementById('hiddenInsurance').value  = insurance.toFixed(2);
    document.getElementById('hiddenTotal').value      = total.toFixed(2);
    document.getElementById('hiddenWeekly').value     = weekly.toFixed(2);
}

function fmt(n) { return Number(n.toFixed(2)).toLocaleString('en-KE'); }

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
