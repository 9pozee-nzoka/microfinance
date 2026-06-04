@extends('layouts.app')

@section('title', 'Change Password - Mweela Cash Capital')
@section('page-title', 'Change Password')

@section('content')

<div style="margin-bottom:20px;">
    <a href="{{ route('dashboard') }}" class="btn btn-outline" style="font-size:13px;">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

@if(session('success'))
<div class="flash-success">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div class="grid-2" style="max-width:600px;">
    <div class="card" style="grid-column:span 2;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <div style="width:50px;height:50px;background:#E3F2FD;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-lock" style="color:#1565C0;font-size:20px;"></i>
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;">Change Your Password</h3>
                <p style="margin:4px 0 0;color:var(--text-secondary);font-size:13px;">Update your account password for security</p>
            </div>
        </div>

        <form method="POST" action="{{ route('profile.update-password') }}">
            @csrf

            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Current Password <span class="req">*</span></label>
                <input type="password" name="current_password" class="form-control {{ $errors->has('current_password')?'is-invalid':'' }}" required>
                @error('current_password')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">New Password <span class="req">*</span></label>
                <input type="password" name="password" class="form-control {{ $errors->has('password')?'is-invalid':'' }}" required minlength="8">
                @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
                <small style="color:var(--text-secondary);font-size:11px;">Minimum 8 characters</small>
            </div>

            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label">Confirm New Password <span class="req">*</span></label>
                <input type="password" name="password_confirmation" class="form-control" required minlength="8">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <a href="{{ route('dashboard') }}" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Password</button>
            </div>
        </form>
    </div>
</div>

@endsection
