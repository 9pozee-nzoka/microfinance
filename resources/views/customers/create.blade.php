@extends('layouts.app')

@section('title', 'Register Customer - GetCash Capital')
@section('page-title', 'Register New Customer')

@section('styles')
<style>
    .form-section {
        background: #fff;
        border-radius: 12px;
        border: 1px solid var(--border);
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .section-heading {
        font-size: 13px; font-weight: 700; color: var(--primary);
        text-transform: uppercase; letter-spacing: 0.5px;
        padding-bottom: 12px; margin-bottom: 20px;
        border-bottom: 2px solid #E3F2FD;
        display: flex; align-items: center; gap: 8px;
    }
    .form-group { margin-bottom: 18px; }
    .form-label {
        display: block; font-size: 12px; font-weight: 600;
        color: var(--text-primary); margin-bottom: 6px;
    }
    .form-label .req { color: var(--danger); margin-left: 2px; }
    .form-control {
        width: 100%; padding: 9px 13px;
        border: 1px solid var(--border); border-radius: 8px;
        font-size: 13px; font-family: inherit;
        background: #fff; color: var(--text-primary);
        transition: border-color 0.15s, box-shadow 0.15s;
        outline: none;
    }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,188,212,0.12); }
    .form-control.is-invalid { border-color: var(--danger); }
    .invalid-feedback { font-size: 11px; color: var(--danger); margin-top: 4px; display: block; }
    .form-hint { font-size: 11px; color: var(--text-secondary); margin-top: 4px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .upload-box {
        border: 2px dashed var(--border); border-radius: 8px;
        padding: 20px; text-align: center; cursor: pointer;
        transition: border-color 0.15s, background 0.15s;
        position: relative;
    }
    .upload-box:hover { border-color: var(--primary); background: #F0FBFD; }
    .upload-box input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .upload-box i { font-size: 24px; color: var(--text-secondary); margin-bottom: 6px; display: block; }
    .upload-box span { font-size: 12px; color: var(--text-secondary); }
    .step-indicator {
        display: flex; gap: 0; margin-bottom: 28px;
        border-radius: 10px; overflow: hidden; border: 1px solid var(--border);
    }
    .step {
        flex: 1; padding: 12px 8px; text-align: center;
        font-size: 12px; font-weight: 600; background: #FAFBFC;
        color: var(--text-secondary); border-right: 1px solid var(--border);
        cursor: pointer; transition: all 0.15s;
    }
    .step:last-child { border-right: none; }
    .step.active { background: var(--primary); color: #fff; }
    .step.done { background: #E8F5E9; color: var(--success); }
    .step-num {
        display: inline-flex; align-items: center; justify-content: center;
        width: 20px; height: 20px; border-radius: 50%;
        background: rgba(255,255,255,0.3); font-size: 11px;
        margin-right: 6px;
    }
</style>
@endsection

@section('content')

{{-- Back --}}
<div style="margin-bottom: 20px;">
    <a href="{{ route('customers.index') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back to Customers
    </a>
</div>

{{-- Flash errors --}}
@if($errors->any())
<div style="background:#FFEBEE; border:1px solid #FFCDD2; border-radius:8px; padding:14px 18px; margin-bottom:20px; color:#C62828;">
    <div style="font-weight:600; margin-bottom:6px;"><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</div>
    <ul style="margin:0; padding-left:18px; font-size:13px;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('customers.store') }}" enctype="multipart/form-data" id="customerForm">
    @csrf

    {{-- ── Section 1: Personal Information ── --}}
    <div class="form-section">
        <div class="section-heading">
            <i class="fas fa-user"></i> Personal Information
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Full Name <span class="req">*</span></label>
                <input type="text" name="full_name" value="{{ old('full_name') }}"
                       class="form-control {{ $errors->has('full_name') ? 'is-invalid' : '' }}"
                       placeholder="e.g. John Kamau Mwangi" required>
                @error('full_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number <span class="req">*</span></label>
                <input type="text" name="phone_number" value="{{ old('phone_number') }}"
                       class="form-control {{ $errors->has('phone_number') ? 'is-invalid' : '' }}"
                       placeholder="07XXXXXXXX" required>
                @error('phone_number')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                       placeholder="john@example.com">
                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">National ID Number <span class="req">*</span></label>
                <input type="text" name="id_number" value="{{ old('id_number') }}"
                       class="form-control {{ $errors->has('id_number') ? 'is-invalid' : '' }}"
                       placeholder="e.g. 12345678" required>
                @error('id_number')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Date of Birth <span class="req">*</span></label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                       class="form-control {{ $errors->has('date_of_birth') ? 'is-invalid' : '' }}"
                       max="{{ now()->subYears(18)->toDateString() }}" required>
                @error('date_of_birth')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Gender <span class="req">*</span></label>
                <select name="gender" class="form-control {{ $errors->has('gender') ? 'is-invalid' : '' }}" required>
                    <option value="">-- Select --</option>
                    <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other"  {{ old('gender') === 'other'  ? 'selected' : '' }}>Other</option>
                </select>
                @error('gender')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Nationality</label>
                <input type="text" name="nationality" value="{{ old('nationality', 'Kenyan') }}"
                       class="form-control" placeholder="Kenyan">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Physical Address</label>
                <input type="text" name="address" value="{{ old('address') }}"
                       class="form-control" placeholder="Street / Estate / Village">
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">County</label>
                <input type="text" name="county" value="{{ old('county') }}"
                       class="form-control" placeholder="e.g. Nairobi">
            </div>
            <div class="form-group">
                <label class="form-label">Sub-County</label>
                <input type="text" name="sub_county" value="{{ old('sub_county') }}"
                       class="form-control" placeholder="e.g. Westlands">
            </div>
            <div class="form-group">
                <label class="form-label">Ward</label>
                <input type="text" name="ward" value="{{ old('ward') }}"
                       class="form-control" placeholder="e.g. Parklands">
            </div>
        </div>
    </div>

    {{-- ── Section 2: Employment / Business ── --}}
    <div class="form-section">
        <div class="section-heading">
            <i class="fas fa-briefcase"></i> Employment / Business
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Employment Type <span class="req">*</span></label>
                <select name="employment_type" id="employmentType"
                        class="form-control {{ $errors->has('employment_type') ? 'is-invalid' : '' }}"
                        onchange="toggleEmploymentFields()" required>
                    <option value="">-- Select --</option>
                    <option value="salaried"      {{ old('employment_type') === 'salaried'      ? 'selected' : '' }}>Salaried</option>
                    <option value="self_employed" {{ old('employment_type') === 'self_employed' ? 'selected' : '' }}>Self Employed</option>
                    <option value="business"      {{ old('employment_type') === 'business'      ? 'selected' : '' }}>Business Owner</option>
                    <option value="farmer"        {{ old('employment_type') === 'farmer'        ? 'selected' : '' }}>Farmer</option>
                    <option value="other"         {{ old('employment_type') === 'other'         ? 'selected' : '' }}>Other</option>
                </select>
                @error('employment_type')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Monthly Income (KSH) <span class="req">*</span></label>
                <input type="number" name="monthly_income" value="{{ old('monthly_income') }}"
                       class="form-control {{ $errors->has('monthly_income') ? 'is-invalid' : '' }}"
                       placeholder="0.00" min="0" step="0.01" required>
                @error('monthly_income')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" id="employerField">
                <label class="form-label">Employer Name</label>
                <input type="text" name="employer_name" value="{{ old('employer_name') }}"
                       class="form-control" placeholder="Company / Organisation name">
            </div>
        </div>
        <div class="grid-2" id="businessFields" style="display:none;">
            <div class="form-group">
                <label class="form-label">Business Name</label>
                <input type="text" name="business_name" value="{{ old('business_name') }}"
                       class="form-control" placeholder="Registered business name">
            </div>
            <div class="form-group">
                <label class="form-label">Business Type</label>
                <input type="text" name="business_type" value="{{ old('business_type') }}"
                       class="form-control" placeholder="e.g. Retail, Wholesale, Agri">
            </div>
        </div>
    </div>

    {{-- ── Section 3: Next of Kin ── --}}
    <div class="form-section">
        <div class="section-heading">
            <i class="fas fa-users"></i> Next of Kin
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Full Name <span class="req">*</span></label>
                <input type="text" name="next_of_kin_name" value="{{ old('next_of_kin_name') }}"
                       class="form-control {{ $errors->has('next_of_kin_name') ? 'is-invalid' : '' }}"
                       placeholder="Next of kin full name" required>
                @error('next_of_kin_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number <span class="req">*</span></label>
                <input type="text" name="next_of_kin_phone" value="{{ old('next_of_kin_phone') }}"
                       class="form-control {{ $errors->has('next_of_kin_phone') ? 'is-invalid' : '' }}"
                       placeholder="07XXXXXXXX" required>
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
                        <option value="{{ $rel }}" {{ old('next_of_kin_relationship') === $rel ? 'selected' : '' }}>{{ $rel }}</option>
                    @endforeach
                </select>
                @error('next_of_kin_relationship')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="next_of_kin_address" value="{{ old('next_of_kin_address') }}"
                       class="form-control" placeholder="Next of kin address">
            </div>
        </div>
    </div>

    {{-- ── Section 4: SACCO Membership ── --}}
    <div class="form-section">
        <div class="section-heading">
            <i class="fas fa-building"></i> SACCO Membership
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Branch <span class="req">*</span></label>
                <select name="branch_id" class="form-control {{ $errors->has('branch_id') ? 'is-invalid' : '' }}" required>
                    <option value="">-- Select Branch --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }} ({{ $branch->code }})
                        </option>
                    @endforeach
                </select>
                @error('branch_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Relationship Officer <span class="req">*</span></label>
                <select name="relationship_officer_id"
                        class="form-control {{ $errors->has('relationship_officer_id') ? 'is-invalid' : '' }}" required>
                    <option value="">-- Select Officer --</option>
                    @foreach($officers as $officer)
                        <option value="{{ $officer->id }}" {{ old('relationship_officer_id') == $officer->id ? 'selected' : '' }}>
                            {{ $officer->name }} — {{ $officer->designation ?? 'Officer' }}
                        </option>
                    @endforeach
                </select>
                @error('relationship_officer_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Initial Share Capital (KSH)</label>
                <input type="number" name="share_capital" value="{{ old('share_capital', 0) }}"
                       class="form-control" placeholder="0.00" min="0" step="0.01">
                <span class="form-hint">Minimum share capital contribution</span>
            </div>
        </div>
    </div>

    {{-- ── Section 5: KYC Documents ── --}}
    <div class="form-section">
        <div class="section-heading">
            <i class="fas fa-id-card"></i> KYC Documents
        </div>
        <div class="grid-2" style="gap: 20px;">
            <div class="form-group">
                <label class="form-label">National ID — Front</label>
                <div class="upload-box" id="idFrontBox">
                    <input type="file" name="id_front" accept="image/*,.pdf" onchange="previewFile(this,'idFrontBox','idFrontPreview')">
                    <i class="fas fa-id-card"></i>
                    <span>Click to upload ID front side</span>
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">JPG, PNG or PDF · Max 2MB</div>
                </div>
                <div id="idFrontPreview" style="display:none; margin-top:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> <span></span>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">National ID — Back</label>
                <div class="upload-box" id="idBackBox">
                    <input type="file" name="id_back" accept="image/*,.pdf" onchange="previewFile(this,'idBackBox','idBackPreview')">
                    <i class="fas fa-id-card"></i>
                    <span>Click to upload ID back side</span>
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">JPG, PNG or PDF · Max 2MB</div>
                </div>
                <div id="idBackPreview" style="display:none; margin-top:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> <span></span>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Passport Photo</label>
                <div class="upload-box" id="photoBox">
                    <input type="file" name="passport_photo" accept="image/*" onchange="previewFile(this,'photoBox','photoPreview')">
                    <i class="fas fa-camera"></i>
                    <span>Click to upload passport photo</span>
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">JPG or PNG · Max 2MB</div>
                </div>
                <div id="photoPreview" style="display:none; margin-top:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> <span></span>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">KRA PIN Certificate</label>
                <div class="upload-box" id="kraBox">
                    <input type="file" name="kra_pin" accept="image/*,.pdf" onchange="previewFile(this,'kraBox','kraPreview')">
                    <i class="fas fa-file-alt"></i>
                    <span>Click to upload KRA PIN</span>
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">JPG, PNG or PDF · Max 2MB</div>
                </div>
                <div id="kraPreview" style="display:none; margin-top:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> <span></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Submit ── --}}
    <div style="display:flex; justify-content:flex-end; gap:12px; padding-bottom:30px;">
        <a href="{{ route('customers.index') }}" class="btn btn-outline" style="padding: 10px 24px;">
            <i class="fas fa-times"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary" style="padding: 10px 28px; font-size:14px;">
            <i class="fas fa-user-plus"></i> Register Customer
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

function previewFile(input, boxId, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must not exceed 2MB.');
            input.value = '';
            return;
        }
        preview.style.display = 'block';
        preview.querySelector('span').textContent = file.name;
        document.getElementById(boxId).style.borderColor = 'var(--success)';
        document.getElementById(boxId).style.background  = '#F1F8E9';
    }
}

// Restore employment fields on validation error
document.addEventListener('DOMContentLoaded', () => {
    const et = document.getElementById('employmentType');
    if (et.value) toggleEmploymentFields();
});
</script>
@endsection
