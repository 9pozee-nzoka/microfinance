@extends('portal.layouts.app')

@php
    $typeLabels = [
        'early' => ['title' => 'Pay Next Installment Early', 'icon' => 'fa-calendar-check', 'color' => '#4CAF50'],
        'topup' => ['title' => 'Top-Up Payment', 'icon' => 'fa-layer-group', 'color' => '#FF9800'],
        'full'  => ['title' => 'Full Prepayment', 'icon' => 'fa-check-double', 'color' => '#00BCD4'],
    ];
    $typeInfo = $typeLabels[$prepayType] ?? null;
@endphp

@section('title', $typeInfo ? $typeInfo['title'] : 'Make Payment')
@section('page-title', $typeInfo ? $typeInfo['title'] : 'Make a Payment')

@section('content')

<div style="max-width: 640px; margin: 0 auto;">

    <div style="margin-bottom: 20px;">
        <a href="{{ route('portal.loan.detail', $loan) }}" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Loan
        </a>
    </div>

    {{-- Loan summary --}}
    <div class="card" style="margin-bottom: 20px; background: linear-gradient(135deg, {{ $typeInfo ? $typeInfo['color'] : '#00BCD4' }}, {{ $typeInfo ? $typeInfo['color'] : '#0097A7' }}); color: white; border: none;">
        <div style="font-size: 13px; opacity: 0.85; margin-bottom: 4px;">
            @if($typeInfo)
                <i class="fas {{ $typeInfo['icon'] }}"></i> {{ $typeInfo['title'] }}
            @else
                Paying for
            @endif
        </div>
        <div style="font-size: 20px; font-weight: 700; font-family: monospace; margin-bottom: 12px;">{{ $loan->loan_number }}</div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
            <div>
                <div style="font-size: 11px; opacity: 0.75;">Outstanding</div>
                <div style="font-size: 16px; font-weight: 700;">KSH {{ number_format($loan->outstanding_balance, 0) }}</div>
            </div>
            <div>
                <div style="font-size: 11px; opacity: 0.75;">Weekly Installment</div>
                <div style="font-size: 16px; font-weight: 700;">KSH {{ number_format($loan->weekly_installment, 0) }}</div>
            </div>
            <div>
                <div style="font-size: 11px; opacity: 0.75;">Next Due</div>
                <div style="font-size: 16px; font-weight: 700;">
                    {{ $nextSchedule ? $nextSchedule->due_date->format('d M') : 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    @if($typeInfo && $prepayType === 'early' && $nextSchedule)
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <i class="fas fa-calendar-check"></i>
        <div>
            You are paying installment #{{ $nextSchedule->installment_number }} early.
            Due date is <strong>{{ $nextSchedule->due_date->format('d M Y') }}</strong>.
            Paying early improves your credit score.
        </div>
    </div>
    @elseif($typeInfo && $prepayType === 'topup' && $projectedInstallments > 0)
    <div class="alert alert-warning" style="margin-bottom: 20px;">
        <i class="fas fa-layer-group"></i>
        <div>
            Top-up payment will cover approximately <strong>{{ $projectedInstallments }} installment{{ $projectedInstallments > 1 ? 's' : '' }}</strong>.
            This helps you get ahead on your loan and may qualify you for a larger top-up loan.
        </div>
    </div>
    @elseif($typeInfo && $prepayType === 'full')
    <div class="alert alert-info" style="margin-bottom: 20px;">
        <i class="fas fa-check-double"></i>
        <div>
            <strong>Full Prepayment</strong> — You are paying off the entire outstanding balance.
            Once confirmed, this loan will be marked as completed and you can apply for a new loan immediately.
        </div>
    </div>
    @elseif($nextSchedule)
    <div class="alert alert-info" style="margin-bottom: 20px;">
        <i class="fas fa-info-circle"></i>
        <div>
            Installment #{{ $nextSchedule->installment_number }} due on <strong>{{ $nextSchedule->due_date->format('d M Y') }}</strong>.
            Amount due: <strong>KSH {{ number_format($nextSchedule->total_amount - $nextSchedule->total_paid, 0) }}</strong>
        </div>
    </div>
    @endif

    {{-- Payment form --}}
    <div class="card">
        <div class="card-title" style="margin-bottom: 20px;">Payment Details</div>

        @if($errors->any())
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <ul style="margin: 0; padding-left: 16px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('portal.loan.pay.submit', $loan) }}" id="paymentForm">
            @csrf

            @if($typeInfo)
            <input type="hidden" name="prepay_type" value="{{ $prepayType }}">
            @endif

            <div class="form-group">
                <label class="form-label">Payment Amount (KSH) <span style="color: var(--danger);">*</span></label>
                <input type="number"
                       name="amount"
                       class="form-control @error('amount') is-invalid @enderror"
                       value="{{ old('amount', number_format($suggestedAmount, 2, '.', '')) }}"
                       min="1"
                       max="{{ $loan->outstanding_balance }}"
                       step="0.01"
                       required>
                @error('amount')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">
                    @if($prepayType === 'full')
                        Full outstanding balance: KSH {{ number_format($loan->outstanding_balance, 0) }}
                    @elseif($prepayType === 'topup')
                        Suggested top-up amount. You can adjust this.
                    @elseif($prepayType === 'early')
                        Next installment amount. You can adjust this.
                    @else
                        Maximum: KSH {{ number_format($loan->outstanding_balance, 0) }} (full outstanding balance)
                    @endif
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Payment Method <span style="color: var(--danger);">*</span></label>
                <select name="payment_method" id="paymentMethod" class="form-control @error('payment_method') is-invalid @enderror" required onchange="toggleMethodFields()">
                    <option value="">— Select method —</option>
                    <option value="mpesa" {{ old('payment_method') === 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                    <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash (at branch)</option>
                </select>
                @error('payment_method')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- M-Pesa fields --}}
            <div id="mpesaFields" style="display: none;">
                <div style="background: #E8F5E9; border: 1px solid #A5D6A7; border-radius: 10px; padding: 14px; margin-bottom: 16px;">
                    <div style="font-size: 13px; font-weight: 600; color: #2E7D32; margin-bottom: 8px;">
                        <i class="fas fa-mobile-alt"></i> M-Pesa Payment Instructions
                    </div>
                    <ol style="font-size: 12px; color: #388E3C; padding-left: 18px; line-height: 1.8;">
                        <li>Go to M-Pesa on your phone</li>
                        <li>Select <strong>Lipa na M-Pesa → Paybill</strong></li>
                        <li>Business No: <strong>{{ config('services.mpesa.paybill', '123456') }}</strong></li>
                        <li>Account No: <strong>{{ $loan->loan_number }}</strong></li>
                        <li>Enter the amount and your PIN</li>
                        <li>Enter the M-Pesa receipt number below</li>
                    </ol>
                </div>

                <div class="form-group">
                    <label class="form-label">M-Pesa Receipt Number <span style="color: var(--danger);">*</span></label>
                    <input type="text"
                           name="mpesa_receipt"
                           class="form-control @error('mpesa_receipt') is-invalid @enderror"
                           value="{{ old('mpesa_receipt') }}"
                           placeholder="e.g. QHX1234ABC"
                           style="text-transform: uppercase; font-family: monospace; letter-spacing: 1px;">
                    @error('mpesa_receipt')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">M-Pesa Phone Number</label>
                    <input type="text"
                           name="phone_number"
                           class="form-control"
                           value="{{ old('phone_number', $customer->phone_number) }}"
                           placeholder="+254700000000">
                </div>
            </div>

            {{-- Bank transfer fields --}}
            <div id="bankFields" style="display: none;">
                <div style="background: #E3F2FD; border: 1px solid #90CAF9; border-radius: 10px; padding: 14px; margin-bottom: 16px;">
                    <div style="font-size: 13px; font-weight: 600; color: #1565C0; margin-bottom: 8px;">
                        <i class="fas fa-university"></i> Bank Transfer Details
                    </div>
                    <div style="font-size: 12px; color: #1976D2; line-height: 1.8;">
                        Bank: <strong>{{ config('services.bank.name', 'Equity Bank') }}</strong><br>
                        Account: <strong>{{ config('services.bank.account', '0123456789') }}</strong><br>
                        Reference: <strong>{{ $loan->loan_number }}</strong>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Bank Reference / Transaction ID <span style="color: var(--danger);">*</span></label>
                    <input type="text"
                           name="bank_reference"
                           class="form-control @error('bank_reference') is-invalid @enderror"
                           value="{{ old('bank_reference') }}"
                           placeholder="Bank transaction reference">
                    @error('bank_reference')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Cash note --}}
            <div id="cashNote" style="display: none;">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    For cash payments, please visit your branch. This form will record your intent and a staff member will confirm the payment.
                </div>
            </div>

            <div style="margin-top: 24px; display: flex; gap: 12px;">
                <a href="{{ route('portal.loan.detail', $loan) }}" class="btn btn-outline" style="flex: 1; justify-content: center;">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary" style="flex: 2; justify-content: center;" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Submit Payment
                </button>
            </div>

            <div style="margin-top: 12px; font-size: 11px; color: var(--text-secondary); text-align: center;">
                <i class="fas fa-shield-alt"></i>
                Payments are reviewed and confirmed by our team within 24 hours.
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
function toggleMethodFields() {
    const method = document.getElementById('paymentMethod').value;
    document.getElementById('mpesaFields').style.display = method === 'mpesa' ? 'block' : 'none';
    document.getElementById('bankFields').style.display  = method === 'bank_transfer' ? 'block' : 'none';
    document.getElementById('cashNote').style.display    = method === 'cash' ? 'block' : 'none';
}

// Run on load in case of old() values
toggleMethodFields();

// Uppercase M-Pesa receipt
const receiptInput = document.querySelector('[name="mpesa_receipt"]');
if (receiptInput) {
    receiptInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
}

// Prevent double-submit
document.getElementById('paymentForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
});
</script>
@endsection
