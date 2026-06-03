@extends('layouts.app')

@section('title', 'Add Staff - Mweela Cash Capital')
@section('page-title', 'Add New Staff')

@section('content')

<div style="margin-bottom:20px;">
    <a href="{{ route('staff.index') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back to Staff
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

<form method="POST" action="{{ route('staff.store') }}">
    @csrf
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-user"></i> Staff Information</div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Full Name <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control {{ $errors->has('name')?'is-invalid':'' }}" required>
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Email <span class="req">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control {{ $errors->has('email')?'is-invalid':'' }}" required>
                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number <span class="req">*</span></label>
                <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="form-control {{ $errors->has('phone_number')?'is-invalid':'' }}" required>
                @error('phone_number')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Employee ID</label>
                <input type="text" name="employee_id" value="{{ old('employee_id') }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Designation <span class="req">*</span></label>
                <input type="text" name="designation" value="{{ old('designation') }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Branch <span class="req">*</span></label>
                <select name="branch_id" class="form-control" required>
                    <option value="">-- Select Branch --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id')==$branch->id?'selected':'' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Role <span class="req">*</span></label>
                <select name="role" class="form-control" required>
                    <option value="">-- Select Role --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ old('role')==$role->name?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$role->name)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status <span class="req">*</span></label>
                <select name="status" class="form-control" required>
                    <option value="active" {{ old('status')==='active'?'selected':'' }}>Active</option>
                    <option value="inactive" {{ old('status')==='inactive'?'selected':'' }}>Inactive</option>
                    <option value="suspended" {{ old('status')==='suspended'?'selected':'' }}>Suspended</option>
                </select>
            </div>
        </div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; padding-bottom:30px;">
        <a href="{{ route('staff.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create Staff</button>
    </div>
</form>
@endsection
