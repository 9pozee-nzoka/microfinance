@extends('layouts.app')

@section('title', 'Edit Customer - Mweela Cash Capital')
@section('page-title', 'Edit Customer')

@section('content')

<div style="margin-bottom:20px; display:flex; gap:10px;">
    <a href="{{ route('customers.profile', $customer) }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back to Profile
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

<form method="POST" action="{{ route('customers.update', $customer) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    {{-- ── Personal ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-user"></i> Personal Information</div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Full Name <span class="req">*</span></label>
                <input type="text" name="full_name" value="{{ old('full_name', $customer->full_name) }}"
                       class="form-control {{ $errors->has('full_name') ? 'is-invalid' : '' }}" required>
                @error('full_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number <span class="req">*</span></label>
                <input type="text" name="phone_number" value="{{ old('phone_number', $customer->phone_number) }}"
                       class="form-control {{ $errors->has('phone_number') ? 'is-invalid' : '' }}" required>
                @error('phone_number')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                       class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}">
                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">National ID Number <span class="req">*</span></label>
                <input type="text" name="id_number" value="{{ old('id_number', $customer->id_number) }}"
                       class="form-control {{ $errors->has('id_number') ? 'is-invalid' : '' }}" required>
                @error('id_number')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Date of Birth <span class="req">*</span></label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $customer->date_of_birth?->toDateString()) }}"
                       class="form-control {{ $errors->has('date_of_birth') ? 'is-invalid' : '' }}"
                       max="{{ now()->subYears(18)->toDateString() }}" required>
                @error('date_of_birth')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Gender <span class="req">*</span></label>
                <select name="gender" class="form-control {{ $errors->has('gender') ? 'is-invalid' : '' }}" required>
                    <option value="">-- Select --</option>
                    @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $label)
                        <option value="{{ $val }}" {{ old('gender', $customer->gender) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('gender')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Nationality</label>
                <input type="text" name="nationality" value="{{ old('nationality', $customer->nationality) }}" class="form-control">
            </div>
            <div class="form-group" style="grid-column:span 2;">
                <label class="form-label">Physical Address</label>
                <input type="text" name="address" value="{{ old('address', $customer->address) }}" class="form-control">
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">County</label>
                <input type="text" name="county" value="{{ old('county', $customer->county) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Sub-County</label>
                <input type="text" name="sub_county" value="{{ old('sub_county', $customer->sub_county) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Ward</label>
                <input type="text" name="ward" value="{{ old('ward', $customer->ward) }}" class="form-control">
            </div>
        </div>
    </div>

    {{-- ── Employment ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-briefcase"></i> Employment / Business</div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Employment Type <span class="req">*</span></label>
                <select name="employment_type" id="employmentType"
                        class="form-control {{ $errors->has('employment_type') ? 'is-invalid' : '' }}"
                        onchange="toggleEmploymentFields()" required>
                    <option value="">-- Select --</option>
                    @foreach(['salaried' => 'Salaried', 'self_employed' => 'Self Employed', 'business' => 'Business Owner', 'farmer' => 'Farmer', 'other' => 'Other'] as $val => $label)
                        <option value="{{ $val }}" {{ old('employment_type', $customer->employment_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('employment_type')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Monthly Income (KSH) <span class="req">*</span></label>
                <input type="number" name="monthly_income" value="{{ old('monthly_income', $customer->monthly_income) }}"
                       class="form-control {{ $errors->has('monthly_income') ? 'is-invalid' : '' }}"
                       min="0" step="0.01" required>
                @error('monthly_income')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" id="employerField">
                <label class="form-label">Employer Name</label>
                <input type="text" name="employer_name" value="{{ old('employer_name', $customer->employer_name) }}" class="form-control">
            </div>
        </div>
        <div class="grid-2" id="businessFields">
            <div class="form-group">
                <label class="form-label">Business Name</label>
                <input type="text" name="business_name" value="{{ old('business_name', $customer->business_name) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Business Type</label>
                <input type="text" name="business_type" value="{{ old('business_type', $customer->business_type) }}" class="form-control">
            </div>
        </div>
    </div>

    {{-- ── Next of Kin ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-users"></i> Next of Kin</div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Full Name <span class="req">*</span></label>
                <input type="text" name="next_of_kin_name" value="{{ old('next_of_kin_name', $customer->next_of_kin_name) }}"
                       class="form-control {{ $errors->has('next_of_kin_name') ? 'is-invalid' : '' }}" required>
                @error('next_of_kin_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number <span class="req">*</span></label>
                <input type="text" name="next_of_kin_phone" value="{{ old('next_of_kin_phone', $customer->next_of_kin_phone) }}"
                       class="form-control {{ $errors->has('next_of_kin_phone') ? 'is-invalid' : '' }}" required>
                @error('next_of_kin_phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Relationship <span class="req">*</span></label>
                <select name="next_of_kin_relationship"
                        class="form-control {{ $errors->has('next_of_kin_relationship') ? 'is-invalid' : '' }}" required>
                    <option value="">-- Select --</option>
                    @foreach(['Spouse','Parent','Sibling','Child','Relative','Friend','Other'] as $rel)
                        <option value="{{ $rel }}" {{ old('next_of_kin_relationship', $customer->next_of_kin_relationship) === $rel ? 'selected' : '' }}>{{ $rel }}</option>
                    @endforeach
                </select>
                @error('next_of_kin_relationship')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="next_of_kin_address" value="{{ old('next_of_kin_address', $customer->next_of_kin_address) }}" class="form-control">
            </div>
        </div>
    </div>

    {{-- ── SACCO ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-building"></i> SACCO Membership</div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Branch <span class="req">*</span></label>
                <select name="branch_id" class="form-control {{ $errors->has('branch_id') ? 'is-invalid' : '' }}" required>
                    <option value="">-- Select Branch --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $customer->branch_id) == $branch->id ? 'selected' : '' }}>
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
                        <option value="{{ $officer->id }}" {{ old('relationship_officer_id', $customer->relationship_officer_id) == $officer->id ? 'selected' : '' }}>
                            {{ $officer->name }}
                        </option>
                    @endforeach
                </select>
                @error('relationship_officer_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    @foreach(['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'dormant' => 'Dormant'] as $val => $label)
                        <option value="{{ $val }}" {{ old('status', $customer->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- ── Submit ── --}}
    <div style="display:flex; justify-content:flex-end; gap:12px; padding-bottom:30px;">
        <a href="{{ route('customers.profile', $customer) }}" class="btn btn-outline" style="padding:10px 24px;">
            <i class="fas fa-times"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary" style="padding:10px 28px; font-size:14px;">
            <i class="fas fa-save"></i> Save Changes
        </button>
    </div>
</form>
@endsection

@section('scripts')
<script>
function toggleEmploymentFields() {
    const type = document.getElementById('employmentType').value;
    const isBusiness = ['business', 'self_employed'].includes(type);
    document.getElementById('businessFields').style.display = isBusiness ? 'grid' : 'none';
    document.getElementById('employerField').style.display  = type === 'salaried' ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', () => {
    toggleEmploymentFields();
});
</script>
@endsection
