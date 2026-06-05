@extends('layouts.app')

@section('title', 'Edit Loan Product - Mweela Cash Capital')
@section('page-title', 'Edit Loan Product: ' . $loanProduct->name)

@section('content')

<div style="margin-bottom:20px;">
    <a href="{{ route('loan-products.index') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back to Products
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

<form method="POST" action="{{ route('loan-products.update', $loanProduct) }}">
    @csrf @method('PUT')
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-tag"></i> Product Details</div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Name <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $loanProduct->name) }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Code <span class="req">*</span></label>
                <input type="text" name="code" value="{{ old('code', $loanProduct->code) }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Status <span class="req">*</span></label>
                <select name="status" class="form-control" required>
                    <option value="active" {{ old('status', $loanProduct->status)==='active'?'selected':'' }}>Active</option>
                    <option value="inactive" {{ old('status', $loanProduct->status)==='inactive'?'selected':'' }}>Inactive</option>
                </select>
            </div>
        </div>
        <div class="grid-1">
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $loanProduct->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="section-heading"><i class="fas fa-percentage"></i> Interest & Terms</div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Interest Method <span class="req">*</span></label>
                <select name="interest_method" class="form-control" required>
                    <option value="flat" {{ old('interest_method', $loanProduct->interest_method)==='flat'?'selected':'' }}>Flat</option>
                    <option value="reducing_balance" {{ old('interest_method', $loanProduct->interest_method)==='reducing_balance'?'selected':'' }}>Reducing Balance</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Interest Rate (%) <span class="req">*</span></label>
                <input type="number" name="interest_rate" value="{{ old('interest_rate', $loanProduct->interest_rate) }}" step="0.01" min="0" max="100" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Min Term (weeks) <span class="req">*</span></label>
                <input type="number" name="min_term_weeks" value="{{ old('min_term_weeks', $loanProduct->min_term_weeks) }}" min="1" class="form-control" required>
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Max Term (weeks) <span class="req">*</span></label>
                <input type="number" name="max_term_weeks" value="{{ old('max_term_weeks', $loanProduct->max_term_weeks) }}" min="1" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Min Amount (KSH) <span class="req">*</span></label>
                <input type="number" name="min_amount" value="{{ old('min_amount', $loanProduct->min_amount) }}" min="0" step="0.01" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Max Amount (KSH) <span class="req">*</span></label>
                <input type="number" name="max_amount" value="{{ old('max_amount', $loanProduct->max_amount) }}" min="0" step="0.01" class="form-control" required>
            </div>
        </div>
    </div>

    {{-- Rates Table --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-table"></i> Principal / Term Rates</div>
        <div id="ratesContainer">
            @php $rates = old('rates') ?? $loanProduct->rates->toArray(); @endphp
            @if(count($rates))
                @foreach($rates as $i => $rate)
                <div class="grid-3 rate-row" style="margin-bottom:10px;">
                    <div class="form-group">
                        <input type="number" name="rates[{{ $i }}][principal_amount]" value="{{ $rate['principal_amount'] ?? '' }}" placeholder="Principal (KSH)" class="form-control" step="0.01" min="1">
                    </div>
                    <div class="form-group">
                        <input type="number" name="rates[{{ $i }}][term_weeks]" value="{{ $rate['term_weeks'] ?? '' }}" placeholder="Term (weeks)" class="form-control" min="1">
                    </div>
                    <div class="form-group" style="display:flex; gap:8px;">
                        <input type="number" name="rates[{{ $i }}][interest_rate]" value="{{ $rate['interest_rate'] ?? '' }}" placeholder="Interest Rate (%)" class="form-control" step="0.01" min="0">
                        <button type="button" class="btn btn-outline btn-sm" onclick="this.closest('.rate-row').remove()" style="white-space:nowrap;"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                @endforeach
            @else
                <p style="color:var(--text-secondary); font-size:13px;">No rates added yet.</p>
            @endif
        </div>
        <button type="button" class="btn btn-outline" onclick="addRateRow()" style="margin-top:10px;"><i class="fas fa-plus"></i> Add Rate</button>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; padding-bottom:30px;">
        <a href="{{ route('loan-products.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Product</button>
    </div>
</form>
@endsection

@section('scripts')
<script>
let rateIndex = {{ count(old('rates') ?? $loanProduct->rates->toArray() ?? []) }};
function addRateRow() {
    const container = document.getElementById('ratesContainer');
    if (container.querySelector('p')) container.querySelector('p').remove();
    const div = document.createElement('div');
    div.className = 'grid-3 rate-row';
    div.style.marginBottom = '10px';
    div.innerHTML = `
        <div class="form-group">
            <input type="number" name="rates[${rateIndex}][principal_amount]" placeholder="Principal (KSH)" class="form-control" step="0.01" min="1">
        </div>
        <div class="form-group">
            <input type="number" name="rates[${rateIndex}][term_weeks]" placeholder="Term (weeks)" class="form-control" min="1">
        </div>
        <div class="form-group" style="display:flex; gap:8px;">
            <input type="number" name="rates[${rateIndex}][interest_rate]" placeholder="Interest Rate (%)" class="form-control" step="0.01" min="0">
            <button type="button" class="btn btn-outline btn-sm" onclick="this.closest('.rate-row').remove()" style="white-space:nowrap;"><i class="fas fa-trash"></i></button>
        </div>
    `;
    container.appendChild(div);
    rateIndex++;
}
</script>
@endsection
