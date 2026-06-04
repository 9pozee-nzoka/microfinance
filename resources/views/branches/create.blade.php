@extends('layouts.app')

@section('title', 'Add Branch - Mweela Cash Capital')
@section('page-title', 'Add New Branch')

@section('content')

<div style="margin-bottom:20px;">
    <a href="{{ route('branches.index') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back to Branches
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

<form method="POST" action="{{ route('branches.store') }}">
    @csrf
    <div class="form-section">
        <div class="section-heading"><i class="fas fa-building"></i> Branch Information</div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Branch Name <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-control {{ $errors->has('name')?'is-invalid':'' }}" required>
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Branch Code <span class="req">*</span></label>
                <input type="text" name="code" value="{{ old('code') }}" class="form-control {{ $errors->has('code')?'is-invalid':'' }}" required placeholder="e.g., HQ001, MUT001">
                @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Location <span class="req">*</span></label>
                <input type="text" name="location" value="{{ old('location') }}" class="form-control {{ $errors->has('location')?'is-invalid':'' }}" required>
                @error('location')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Status <span class="req">*</span></label>
                <select name="status" class="form-control" required>
                    <option value="active" {{ old('status')==='active'?'selected':'' }}>Active</option>
                    <option value="inactive" {{ old('status')==='inactive'?'selected':'' }}>Inactive</option>
                </select>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="+254...">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="branch@example.com">
            </div>
        </div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:12px; padding-bottom:30px;">
        <a href="{{ route('branches.index') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create Branch</button>
    </div>
</form>

@endsection
