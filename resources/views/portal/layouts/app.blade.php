<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO: Portal pages should not be indexed --}}
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    <meta name="description" content="Mweela Cash Capital Customer Portal - Access your loan account, make payments, and view your transaction history.">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'My Account') — Mweela Cash Capital">
    <meta property="og:description" content="Mweela Cash Capital Customer Portal - Access your loan account, make payments, and view your transaction history.">
    <meta property="og:site_name" content="Mweela Cash Capital">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('title', 'My Account') — Mweela Cash Capital">
    <meta name="twitter:description" content="Mweela Cash Capital Customer Portal - Access your loan account, make payments, and view your transaction history.">

    <title>@yield('title', 'My Account') — Mweela Cash Capital</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#00BCD4">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #00BCD4;
            --primary-dark: #00ACC1;
            --primary-light: #E0F7FA;
            --success: #4CAF50;
            --warning: #FF9800;
            --danger: #F44336;
            --bg: #F0F4F8;
            --card-bg: #FFFFFF;
            --text-primary: #1A2332;
            --text-secondary: #6B7A8D;
            --border: #E2E8F0;
            --sidebar-width: 240px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.6;
        }

        /* ── Sidebar ── */
        .portal-sidebar {
            position: fixed;
            left: 0; top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: #0F1923;
            display: flex;
            flex-direction: column;
            z-index: 200;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 24px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-brand .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .sidebar-brand .logo-icon {
            width: 38px; height: 38px;
            background: var(--primary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 20px; font-weight: 700;
        }

        .sidebar-brand .logo-text {
            color: white;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
        }

        .sidebar-brand .logo-sub {
            color: rgba(255,255,255,0.45);
            font-size: 11px;
            font-weight: 400;
        }

        .sidebar-user {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user .avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 16px;
            flex-shrink: 0;
        }

        .sidebar-user .user-name {
            color: white;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-user .user-id {
            color: rgba(255,255,255,0.45);
            font-size: 11px;
        }

        .sidebar-nav {
            padding: 12px 0;
            flex: 1;
        }

        .nav-section-label {
            padding: 8px 20px 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: rgba(255,255,255,0.3);
        }

        .portal-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .portal-nav-item:hover {
            color: white;
            background: rgba(255,255,255,0.06);
        }

        .portal-nav-item.active {
            color: var(--primary);
            background: rgba(0,188,212,0.1);
            border-left-color: var(--primary);
        }

        .portal-nav-item i {
            width: 18px;
            text-align: center;
            font-size: 15px;
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-footer form button {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.5);
            background: none;
            border: none;
            font-size: 13px;
            cursor: pointer;
            padding: 8px 0;
            transition: color 0.15s;
            width: 100%;
        }

        .sidebar-footer form button:hover { color: var(--danger); }

        /* ── Main ── */
        .portal-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .portal-topbar {
            background: white;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .portal-topbar .page-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .portal-topbar .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .portal-content {
            padding: 24px 28px;
            flex: 1;
        }

        /* ── Cards ── */
        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }

        .card-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }

        /* ── Stat cards ── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* ── Badges & Status ── */
        .badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success { background: #E8F5E9; color: #2E7D32; }
        .badge-warning { background: #FFF3E0; color: #E65100; }
        .badge-danger  { background: #FFEBEE; color: #C62828; }
        .badge-info    { background: #E3F2FD; color: #1565C0; }
        .badge-purple  { background: #F3E5F5; color: #6A1B9A; }

        /* ── Buttons ── */
        .btn {
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); color: white; }
        .btn-outline { background: white; border: 1px solid var(--border); color: var(--text-primary); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        /* ── Tables ── */
        .data-table { width: 100%; border-collapse: collapse; }

        .data-table th {
            text-align: left;
            padding: 11px 14px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            background: #FAFBFC;
        }

        .data-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }

        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #FAFBFC; }

        /* ── Progress bar ── */
        .progress-bar-wrap {
            background: #E8ECF1;
            border-radius: 99px;
            height: 8px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 99px;
            background: var(--primary);
            transition: width 0.4s ease;
        }

        /* ── Alerts ── */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success { background: #E8F5E9; color: #2E7D32; border: 1px solid #A5D6A7; }
        .alert-danger  { background: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; }
        .alert-warning { background: #FFF3E0; color: #E65100; border: 1px solid #FFCC80; }
        .alert-info    { background: #E3F2FD; color: #1565C0; border: 1px solid #90CAF9; }

        /* ── Forms ── */
        .form-group { margin-bottom: 18px; }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.15s;
            background: white;
        }

        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,188,212,0.1); }

        .form-control.is-invalid { border-color: var(--danger); }

        .invalid-feedback { color: var(--danger); font-size: 12px; margin-top: 4px; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .portal-sidebar { transform: translateX(-100%); }
            .portal-main { margin-left: 0; }
            .portal-content { padding: 16px; }
        }
    </style>

    @yield('styles')
</head>
<body>

{{-- Sidebar --}}
@php
    $customer = \App\Models\Customer::where('user_id', auth()->id())->first();
    $initials = $customer
        ? collect(explode(' ', $customer->full_name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->implode('')
        : strtoupper(substr(auth()->user()->name ?? 'U', 0, 1));
@endphp

<aside class="portal-sidebar">
    <div class="sidebar-brand">
        <a href="{{ route('portal.dashboard') }}" class="logo">
            <div class="logo-icon">M</div>
            <div>
                <div class="logo-text">Mweela Cash Capital</div>
                <div class="logo-sub">Customer Portal</div>
            </div>
        </a>
    </div>

    <div class="sidebar-user">
        <div class="avatar">{{ $initials }}</div>
        <div>
            <div class="user-name">{{ $customer?->full_name ?? auth()->user()->name }}</div>
            <div class="user-id">{{ $customer?->customer_number ?? '' }}</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Menu</div>

        <a href="{{ route('portal.dashboard') }}"
           class="portal-nav-item {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
            <i class="fas fa-home"></i> Dashboard
        </a>

        <a href="{{ route('portal.loans') }}"
           class="portal-nav-item {{ request()->routeIs('portal.loans', 'portal.loan.*') ? 'active' : '' }}">
            <i class="fas fa-hand-holding-usd"></i> My Loans
        </a>

        <a href="{{ route('portal.transactions') }}"
           class="portal-nav-item {{ request()->routeIs('portal.transactions') ? 'active' : '' }}">
            <i class="fas fa-exchange-alt"></i> Transactions
        </a>

        <div class="nav-section-label" style="margin-top:8px;">Account</div>

        <a href="{{ route('portal.profile') }}"
           class="portal-nav-item {{ request()->routeIs('portal.profile') ? 'active' : '' }}">
            <i class="fas fa-user-circle"></i> My Profile
        </a>
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            <button type="submit">
                <i class="fas fa-sign-out-alt"></i> Sign Out
            </button>
        </form>
    </div>
</aside>

{{-- Main content --}}
<div class="portal-main">
    <div class="portal-topbar">
        <div class="page-title">@yield('page-title', 'Dashboard')</div>
        <div class="topbar-right">
            @if($customer && $customer->activeLoans()->exists())
                <span style="font-size:12px; color:var(--text-secondary);">
                    <i class="fas fa-circle" style="color:var(--success); font-size:8px;"></i>
                    Active Loan
                </span>
            @endif
            <a href="{{ route('portal.profile') }}" style="text-decoration:none; display:flex; align-items:center; gap:8px; color:var(--text-primary);">
                <div style="width:32px; height:32px; border-radius:50%; background:var(--primary); display:flex; align-items:center; justify-content:center; color:white; font-size:13px; font-weight:700;">
                    {{ $initials }}
                </div>
            </a>
        </div>
    </div>

    <div class="portal-content">
        @yield('content')
    </div>
</div>

@yield('scripts')
</body>
</html>
