@extends('portal.layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')

@if(session('success'))
<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
@endif

@php
    $initials = collect(explode(' ', $customer->full_name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->implode('');
    $colors   = ['#00BCD4','#4CAF50','#FF9800','#9C27B0','#F44336','#3F51B5'];
    $avatarBg = $colors[abs(crc32($customer->customer_number)) % count($colors)];
    $score    = $customer->credit_score ?? 0;
    $scoreColor = match(true) {
        $score >= 800 => '#4CAF50', $score >= 650 => '#8BC34A',
        $score >= 500 => '#FF9800', $score >= 350 => '#FF5722', default => '#F44336'
    };
    $scoreLabel = match(true) {
        $score >= 800 => 'Excellent', $score >= 650 => 'Good',
        $score >= 500 => 'Fair', $score >= 350 => 'Poor', default => 'Bad'
    };
@endphp

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; align-items: start;">

    {{-- Left: identity card --}}
    <div>
        <div class="card" style="text-align: center; margin-bottom: 16px;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: {{ $avatarBg }}; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; font-weight: 700; margin: 0 auto 14px;">
                {{ $initials }}
            </div>
            <div style="font-size: 18px; font-weight: 700;">{{ $customer->full_name }}</div>
            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">{{ $customer->customer_number }}</div>
            <div style="margin-top: 10px;">
                <span class="badge {{ $customer->status === 'active' ? 'badge-success' : 'badge-warning' }}">
                    {{ ucfirst($customer->status) }}
                </span>
            </div>
        </div>

        <div class="card" style="margin-bottom: 16px;">
            <div class="card-title">Credit Score</div>
            <div style="text-align: center; padding: 10px 0;">
                <div style="font-size: 48px; font-weight: 800; color: {{ $scoreColor }}; line-height: 1;">{{ $score }}</div>
                <div style="font-size: 14px; font-weight: 600; color: {{ $scoreColor }}; margin-top: 4px;">{{ $scoreLabel }}</div>
                <div style="margin-top: 12px;">
                    <div class="progress-bar-wrap" style="height: 10px;">
                        <div class="progress-bar-fill" style="width: {{ min(100, ($score / 1000) * 100) }}%; background: {{ $scoreColor }};"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: var(--text-secondary); margin-top: 4px;">
                        <span>0</span><span>1000</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Account Summary</div>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 4px;">
                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                    <span style="color: var(--text-secondary);">Savings Balance</span>
                    <span style="font-weight: 700;">KSH {{ number_format($customer->savings_balance, 0) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                    <span style="color: var(--text-secondary);">Share Capital</span>
                    <span style="font-weight: 700;">KSH {{ number_format($customer->share_capital, 0) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                    <span style="color: var(--text-secondary);">Credit Limit</span>
                    <span style="font-weight: 700;">KSH {{ number_format($customer->credit_limit, 0) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                    <span style="color: var(--text-secondary);">Total Loans</span>
                    <span style="font-weight: 700;">{{ $customer->loans()->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: details + password --}}
    <div>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-title" style="margin-bottom: 16px;">Personal Information</div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Full Name</div>
                    <div style="font-size: 14px; font-weight: 500;">{{ $customer->full_name }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Phone Number</div>
                    <div style="font-size: 14px; font-weight: 500;">{{ $customer->phone_number }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Email</div>
                    <div style="font-size: 14px; font-weight: 500;">{{ $customer->email ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">ID Number</div>
                    <div style="font-size: 14px; font-weight: 500;">{{ $customer->id_number }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Date of Birth</div>
                    <div style="font-size: 14px; font-weight: 500;">{{ $customer->date_of_birth?->format('d M Y') ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Gender</div>
                    <div style="font-size: 14px; font-weight: 500;">{{ ucfirst($customer->gender ?? '—') }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">County</div>
                    <div style="font-size: 14px; font-weight: 500;">{{ $customer->county ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Address</div>
                    <div style="font-size: 14px; font-weight: 500;">{{ $customer->address ?? '—' }}</div>
                </div>
            </div>

            <div style="border-top: 1px solid var(--border); margin-top: 16px; padding-top: 16px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px;">Employment</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Employment Type</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ ucfirst(str_replace('_', ' ', $customer->employment_type ?? '—')) }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Monthly Income</div>
                        <div style="font-size: 14px; font-weight: 500;">KSH {{ number_format($customer->monthly_income ?? 0, 0) }}</div>
                    </div>
                    @if($customer->employer_name)
                    <div>
                        <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Employer</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $customer->employer_name }}</div>
                    </div>
                    @endif
                    @if($customer->business_name)
                    <div>
                        <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Business</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $customer->business_name }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div style="border-top: 1px solid var(--border); margin-top: 16px; padding-top: 16px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-primary); margin-bottom: 12px;">Branch & Officer</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Branch</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $customer->branch->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Relationship Officer</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $customer->relationshipOfficer->name ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Change password --}}
        <div class="card">
            <div class="card-title" style="margin-bottom: 16px;">Change Password</div>

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

            <form method="POST" action="{{ route('portal.change-password') }}" style="max-width: 400px;">
                @csrf

                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i> Update Password
                </button>
            </form>
        </div>
    </div>

</div>

@endsection
