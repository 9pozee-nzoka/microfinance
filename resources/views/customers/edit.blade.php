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
                <label class="form-label">First Name <span class="req">*</span></label>
                <input type="text" name="first_name" value="{{ old('first_name', $customer->first_name) }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middle_name" value="{{ old('middle_name', $customer->middle_name) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Last Name <span class="req">*</span></label>
                <input type="text" name="last_name" value="{{ old('last_name', $customer->last_name) }}" class="form-control" required>
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Phone Number <span class="req">*</span></label>
                <input type="text" name="phone_number" value="{{ old('phone_number', $customer->phone_number) }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">National ID Number <span class="req">*</span></label>
                <input type="text" name="id_number" value="{{ old('id_number', $customer->id_number) }}" class="form-control" required>
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Date of Birth <span class="req">*</span></label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $customer->date_of_birth?->toDateString()) }}" class="form-control" max="{{ now()->subYears(18)->toDateString() }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Gender <span class="req">*</span></label>
                <select name="gender" class="form-control" required>
                    @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $label)
                        <option value="{{ $val }}" {{ old('gender', $customer->gender) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Marital Status</label>
                <select name="marital_status" class="form-control">
                    <option value="">-- Select --</option>
                    @foreach(['single','married','divorced','widowed'] as $ms)
                        <option value="{{ $ms }}" {{ old('marital_status', $customer->marital_status) === $ms ? 'selected' : '' }}>{{ ucfirst($ms) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Nationality</label>
                <input type="text" name="nationality" value="{{ old('nationality', $customer->nationality) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Education Level</label>
                <select name="education_level" class="form-control">
                    <option value="">-- Select --</option>
                    @foreach(['none','primary','secondary','diploma','degree','masters','phd'] as $edu)
                        <option value="{{ $edu }}" {{ old('education_level', $customer->education_level) === $edu ? 'selected' : '' }}>{{ ucfirst($edu) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">KRA PIN Number</label>
                <input type="text" name="kra_pin_number" value="{{ old('kra_pin_number', $customer->kra_pin_number) }}" class="form-control">
            </div>
        </div>
    </div>

    {{-- ── Residential ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-home"></i> Residential Details</div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">County</label>
                <input type="text" name="residential_county" value="{{ old('residential_county', $customer->residential_county) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Sub-County</label>
                <input type="text" name="residential_sub_county" value="{{ old('residential_sub_county', $customer->residential_sub_county) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Ward</label>
                <input type="text" name="residential_ward" value="{{ old('residential_ward', $customer->residential_ward) }}" class="form-control">
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Estate / Village</label>
                <input type="text" name="residential_estate" value="{{ old('residential_estate', $customer->residential_estate) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">House Number</label>
                <input type="text" name="residential_house_number" value="{{ old('residential_house_number', $customer->residential_house_number) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Physical Address</label>
                <input type="text" name="address" value="{{ old('address', $customer->address) }}" class="form-control">
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">County (Legacy)</label>
                <input type="text" name="county" value="{{ old('county', $customer->county) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Sub-County (Legacy)</label>
                <input type="text" name="sub_county" value="{{ old('sub_county', $customer->sub_county) }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Ward (Legacy)</label>
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
                <select name="employment_type" id="employmentType" class="form-control" onchange="toggleEmploymentFields()" required>
                    @foreach(['salaried' => 'Salaried', 'self_employed' => 'Self Employed', 'business' => 'Business Owner', 'farmer' => 'Farmer', 'other' => 'Other'] as $val => $label)
                        <option value="{{ $val }}" {{ old('employment_type', $customer->employment_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Monthly Income (KSH) <span class="req">*</span></label>
                <input type="number" name="monthly_income" value="{{ old('monthly_income', $customer->monthly_income) }}" class="form-control" min="0" step="0.01" required>
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
                <input type="text" name="next_of_kin_name" value="{{ old('next_of_kin_name', $customer->next_of_kin_name) }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number <span class="req">*</span></label>
                <input type="text" name="next_of_kin_phone" value="{{ old('next_of_kin_phone', $customer->next_of_kin_phone) }}" class="form-control" required>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Relationship <span class="req">*</span></label>
                <select name="next_of_kin_relationship" class="form-control" required>
                    @foreach(['Spouse','Parent','Sibling','Child','Relative','Friend','Other'] as $rel)
                        <option value="{{ $rel }}" {{ old('next_of_kin_relationship', $customer->next_of_kin_relationship) === $rel ? 'selected' : '' }}>{{ $rel }}</option>
                    @endforeach
                </select>
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
                <select name="branch_id" class="form-control" required>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $customer->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
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
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Customer Type</label>
                <select name="customer_type" class="form-control">
                    <option value="">-- Select --</option>
                    <option value="permanent" {{ old('customer_type', $customer->customer_type)==='permanent'?'selected':'' }}>Permanent</option>
                    <option value="non_permanent" {{ old('customer_type', $customer->customer_type)==='non_permanent'?'selected':'' }}>Non-Permanent</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Qualified Amount (KSH)</label>
                <input type="number" name="qualified_amount" value="{{ old('qualified_amount', $customer->qualified_amount) }}" class="form-control" min="0" step="0.01">
            </div>
        </div>
    </div>

    {{-- ── KYC Documents (Edit) ── --}}
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-id-card"></i> KYC Documents</div>
        <div class="grid-2" style="gap: 20px;">
            <div class="form-group">
                <label class="form-label">National ID — Front</label>
                @if($customer->id_front_path)
                <div style="margin-bottom:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> Currently uploaded
                    <a href="{{ Storage::url($customer->id_front_path) }}" target="_blank" style="margin-left:8px; color:var(--primary);"><i class="fas fa-eye"></i> View</a>
                </div>
                @endif
                <div class="upload-box" id="idFrontBox" onclick="triggerFileUpload('id_front_input')">
                    <input type="file" name="id_front" id="id_front_input" accept="image/*,.pdf" capture="environment" onchange="previewFile(this,'idFrontBox','idFrontPreview')">
                    <i class="fas fa-id-card"></i>
                    <span>Click to upload or take photo</span>
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">JPG, PNG or PDF · Max 10MB</div>
                </div>
                <div style="display:flex; gap:8px; margin-top:8px;">
                    <button type="button" class="btn btn-outline" style="font-size:12px; padding:6px 12px; flex:1;" onclick="event.stopPropagation(); openCamera('id_front_input', 'idFrontBox', 'idFrontPreview')">
                        <i class="fas fa-camera"></i> Use Camera
                    </button>
                </div>
                <div id="idFrontPreview" style="display:none; margin-top:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> <span></span>
                </div>
                @error('id_front')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">National ID — Back</label>
                @if($customer->id_back_path)
                <div style="margin-bottom:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> Currently uploaded
                    <a href="{{ Storage::url($customer->id_back_path) }}" target="_blank" style="margin-left:8px; color:var(--primary);"><i class="fas fa-eye"></i> View</a>
                </div>
                @endif
                <div class="upload-box" id="idBackBox" onclick="triggerFileUpload('id_back_input')">
                    <input type="file" name="id_back" id="id_back_input" accept="image/*,.pdf" capture="environment" onchange="previewFile(this,'idBackBox','idBackPreview')">
                    <i class="fas fa-id-card"></i>
                    <span>Click to upload or take photo</span>
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">JPG, PNG or PDF · Max 10MB</div>
                </div>
                <div style="display:flex; gap:8px; margin-top:8px;">
                    <button type="button" class="btn btn-outline" style="font-size:12px; padding:6px 12px; flex:1;" onclick="event.stopPropagation(); openCamera('id_back_input', 'idBackBox', 'idBackPreview')">
                        <i class="fas fa-camera"></i> Use Camera
                    </button>
                </div>
                <div id="idBackPreview" style="display:none; margin-top:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> <span></span>
                </div>
                @error('id_back')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Passport Photo</label>
                @if($customer->passport_photo_path)
                <div style="margin-bottom:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> Currently uploaded
                    <a href="{{ Storage::url($customer->passport_photo_path) }}" target="_blank" style="margin-left:8px; color:var(--primary);"><i class="fas fa-eye"></i> View</a>
                </div>
                @endif
                <div class="upload-box" id="photoBox" onclick="triggerFileUpload('passport_photo_input')">
                    <input type="file" name="passport_photo" id="passport_photo_input" accept="image/*" capture="user" onchange="previewFile(this,'photoBox','photoPreview')">
                    <i class="fas fa-camera"></i>
                    <span>Click to upload or take photo</span>
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">JPG or PNG · Max 10MB</div>
                </div>
                <div style="display:flex; gap:8px; margin-top:8px;">
                    <button type="button" class="btn btn-outline" style="font-size:12px; padding:6px 12px; flex:1;" onclick="event.stopPropagation(); openCamera('passport_photo_input', 'photoBox', 'photoPreview')">
                        <i class="fas fa-camera"></i> Use Camera
                    </button>
                </div>
                <div id="photoPreview" style="display:none; margin-top:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> <span></span>
                </div>
                @error('passport_photo')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">KRA PIN Certificate</label>
                @if($customer->kra_pin_path)
                <div style="margin-bottom:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> Currently uploaded
                    <a href="{{ Storage::url($customer->kra_pin_path) }}" target="_blank" style="margin-left:8px; color:var(--primary);"><i class="fas fa-eye"></i> View</a>
                </div>
                @endif
                <div class="upload-box" id="kraBox" onclick="triggerFileUpload('kra_pin_input')">
                    <input type="file" name="kra_pin" id="kra_pin_input" accept="image/*,.pdf" capture="environment" onchange="previewFile(this,'kraBox','kraPreview')">
                    <i class="fas fa-file-alt"></i>
                    <span>Click to upload or take photo</span>
                    <div style="font-size:11px; color:var(--text-secondary); margin-top:4px;">JPG, PNG or PDF · Max 10MB</div>
                </div>
                <div style="display:flex; gap:8px; margin-top:8px;">
                    <button type="button" class="btn btn-outline" style="font-size:12px; padding:6px 12px; flex:1;" onclick="event.stopPropagation(); openCamera('kra_pin_input', 'kraBox', 'kraPreview')">
                        <i class="fas fa-camera"></i> Use Camera
                    </button>
                </div>
                <div id="kraPreview" style="display:none; margin-top:8px; font-size:12px; color:var(--success);">
                    <i class="fas fa-check-circle"></i> <span></span>
                </div>
                @error('kra_pin')<span class="invalid-feedback">{{ $message }}</span>@enderror
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

function previewFile(input, boxId, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 10 * 1024 * 1024) {
            alert('File size must not exceed 10MB.');
            input.value = '';
            return;
        }
        preview.style.display = 'block';
        preview.querySelector('span').textContent = file.name;
        document.getElementById(boxId).style.borderColor = 'var(--success)';
        document.getElementById(boxId).style.background  = '#F1F8E9';
    }
}

function triggerFileUpload(inputId) {
    document.getElementById(inputId).click();
}

function openCamera(inputId, boxId, previewId) {
    const input = document.getElementById(inputId);
    const originalAccept = input.accept;
    const originalCapture = input.getAttribute('capture');

    // Force camera on mobile: strip non-image accept values so browser shows camera
    // The capture attribute tells mobile browsers to open camera directly
    input.setAttribute('accept', 'image/*');
    input.setAttribute('capture', inputId === 'passport_photo_input' ? 'user' : 'environment');
    input.click();

    // Restore original attributes after picker closes
    requestAnimationFrame(() => {
        setTimeout(() => {
            input.setAttribute('accept', originalAccept);
            if (originalCapture) {
                input.setAttribute('capture', originalCapture);
            } else {
                input.removeAttribute('capture');
            }
        }, 500);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    toggleEmploymentFields();
});
</script>
@endsection
