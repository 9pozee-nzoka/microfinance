<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mweela Cash Capital')</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* ── Design tokens ─────────────────────────────────────────────────── */
        :root {
            --primary:        #00BCD4;
            --primary-dark:   #00ACC1;
            --success:        #4CAF50;
            --warning:        #FF9800;
            --danger:         #F44336;
            --bg:             #F5F7FA;
            --card-bg:        #FFFFFF;
            --text-primary:   #2C3E50;
            --text-secondary: #7F8C8D;
            --sidebar-width:  260px;
            --sidebar-bg:     #FFFFFF;
            --border:         #E8ECF1;
            --topbar-h:       60px;
        }

        /* ── Reset ─────────────────────────────────────────────────────────── */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.5;
            overflow-x: hidden;
        }

        /* ── Sidebar ───────────────────────────────────────────────────────── */
        .sidebar {
            position: fixed;
            left: 0; top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.28s cubic-bezier(.4,0,.2,1),
                        box-shadow 0.28s ease;
            /* Custom scrollbar */
            scrollbar-width: thin;
            scrollbar-color: var(--border) transparent;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        .sidebar-header {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .brand {
            display: flex; align-items: center; gap: 10px;
            font-weight: 700; font-size: 18px;
            color: var(--primary); text-decoration: none;
        }

        .brand-icon {
            width: 36px; height: 36px;
            background: var(--primary); border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 18px; flex-shrink: 0;
        }

        .user-profile {
            padding: 14px 20px;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .user-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: var(--primary);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 600; font-size: 15px;
            flex-shrink: 0;
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name  { font-weight: 600; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role  { font-size: 11px; color: var(--text-secondary); }

        .nav-section { padding: 8px 0; flex: 1; }

        .nav-label {
            padding: 8px 20px;
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.7px;
            color: var(--text-secondary);
        }

        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 20px;
            color: var(--text-primary); text-decoration: none;
            transition: background 0.15s, color 0.15s;
            cursor: pointer; border: none; background: none;
            width: 100%; font-size: 13.5px; text-align: left;
            white-space: nowrap;
        }
        .nav-item:hover  { background: #F0F4F8; color: var(--primary); }
        .nav-item.active {
            background: #E3F2FD; color: var(--primary);
            border-right: 3px solid var(--primary);
        }
        .nav-item i { width: 20px; text-align: center; font-size: 15px; flex-shrink: 0; }
        .nav-item .chevron { margin-left: auto; font-size: 11px; transition: transform 0.2s; }
        .nav-item.expanded .chevron { transform: rotate(90deg); }

        .submenu { display: none; background: #FAFBFC; }
        .submenu.show { display: block; }
        .submenu .nav-item { padding-left: 50px; font-size: 13px; }

        .sidebar-footer {
            margin-top: auto;
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        /* ── Sidebar overlay (mobile) ──────────────────────────────────────── */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 999;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }
        .sidebar-overlay.show { display: block; }

        /* ── Main content ──────────────────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.28s cubic-bezier(.4,0,.2,1);
        }

        /* ── Top bar ───────────────────────────────────────────────────────── */
        .top-bar {
            background: white;
            height: var(--topbar-h);
            padding: 0 24px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
            gap: 12px;
        }

        .top-bar-left { display: flex; align-items: center; gap: 12px; min-width: 0; }

        /* Hamburger — hidden on desktop */
        .hamburger {
            display: none;
            width: 36px; height: 36px; border-radius: 8px;
            border: 1px solid var(--border); background: white;
            align-items: center; justify-content: center;
            color: var(--text-secondary); cursor: pointer;
            flex-shrink: 0; font-size: 16px;
            transition: background 0.15s, color 0.15s;
        }
        .hamburger:hover { background: #F0F4F8; color: var(--primary); }

        .page-title {
            font-size: 18px; font-weight: 600;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .top-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

        .search-box { position: relative; }
        .search-box input {
            width: 240px;
            padding: 8px 14px 8px 38px;
            border: 1px solid var(--border); border-radius: 8px;
            font-size: 13px; outline: none;
            transition: border-color 0.15s, width 0.2s;
        }
        .search-box input:focus { border-color: var(--primary); width: 280px; }
        .search-box i {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary); font-size: 13px;
        }

        .icon-btn {
            width: 36px; height: 36px; border-radius: 8px;
            border: 1px solid var(--border); background: white;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-secondary); cursor: pointer;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
        }
        .icon-btn:hover { background: #F0F4F8; color: var(--primary); }

        /* ── Content area ──────────────────────────────────────────────────── */
        .content-area { padding: 24px 28px; }

        /* ── Cards ─────────────────────────────────────────────────────────── */
        .card {
            background: var(--card-bg);
            border-radius: 12px; padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--border);
        }

        .card-header {
            display: flex; align-items: center;
            justify-content: space-between;
            margin-bottom: 15px; flex-wrap: wrap; gap: 8px;
        }

        .card-title {
            font-size: 12px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--text-secondary);
        }

        /* ── Badges ────────────────────────────────────────────────────────── */
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-primary { background: #E3F2FD; color: var(--primary); }
        .badge-success { background: #E8F5E9; color: var(--success); }
        .badge-warning { background: #FFF3E0; color: var(--warning); }
        .badge-danger  { background: #FFEBEE; color: var(--danger); }

        /* ── Status pills ──────────────────────────────────────────────────── */
        .status {
            padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600; display: inline-block;
            white-space: nowrap;
        }
        .status-partially-approved { background: #FFF3E0; color: #E65100; }
        .status-active   { background: #E8F5E9; color: #2E7D32; }
        .status-rejected { background: #FFEBEE; color: #C62828; }
        .status-pending  { background: #E3F2FD; color: #1565C0; }
        .status-disbursed{ background: #F3E5F5; color: #6A1B9A; }
        .status-blacklisted { background: #F3E5F5; color: #7B1FA2; border: 1px solid #CE93D8; }

        /* ── Buttons ───────────────────────────────────────────────────────── */
        .btn {
            padding: 8px 16px; border-radius: 6px;
            font-size: 13px; font-weight: 500;
            cursor: pointer; border: none;
            transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 6px;
            text-decoration: none; white-space: nowrap;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); color: white; }
        .btn-outline { background: white; border: 1px solid var(--border); color: var(--text-primary); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

        /* ── Filter controls ───────────────────────────────────────────────── */
        .filter-bar { display: flex; gap: 12px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border); border-radius: 6px;
            font-size: 13px; background: white;
            min-width: 160px; max-width: 100%;
        }
        .filter-select:focus { outline: none; border-color: var(--primary); }

        /* ── Grids ─────────────────────────────────────────────────────────── */
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }

        /* ── Tables ────────────────────────────────────────────────────────── */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th {
            text-align: left; padding: 11px 14px;
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            background: #FAFBFC; white-space: nowrap;
        }
        .data-table td {
            padding: 11px 14px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }
        .data-table tr:hover { background: #FAFBFC; }

        /* Scrollable table wrapper — applied in content via inline style or here */
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* ── Metric helpers ────────────────────────────────────────────────── */
        .metric-value { font-size: 28px; font-weight: 700; color: var(--text-primary); }
        .metric-label { font-size: 12px; color: var(--text-secondary); margin-top: 4px; }

        /* ── Circle progress ───────────────────────────────────────────────── */
        .circle-progress { position: relative; width: 120px; height: 120px; }
        .circle-progress svg { transform: rotate(-90deg); }
        .circle-bg   { fill: none; stroke: #E8ECF1; stroke-width: 8; }
        .circle-fill { fill: none; stroke-width: 8; stroke-linecap: round; }
        .circle-text {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); text-align: center;
        }
        .circle-percent { font-size: 24px; font-weight: 700; }
        .circle-label   { font-size: 11px; color: var(--text-secondary); }

        /* ── Modals ────────────────────────────────────────────────────────── */
        [id$="Modal"] {
            /* All modals get scroll on small screens */
        }
        [id$="Modal"] > div {
            max-height: 92vh;
            overflow-y: auto;
        }

        /* ════════════════════════════════════════════════════════════════════
           RESPONSIVE BREAKPOINTS
           ════════════════════════════════════════════════════════════════════ */

        /* ── Large desktop: 4-col → 2-col ──────────────────────────────────── */
        @media (max-width: 1280px) {
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
        }

        /* ── Tablet landscape (1024px) ─────────────────────────────────────── */
        @media (max-width: 1024px) {
            :root { --sidebar-width: 220px; }
            .content-area { padding: 20px 20px; }
            .search-box input { width: 180px; }
            .search-box input:focus { width: 220px; }
        }

        /* ── Tablet portrait (768px) — sidebar becomes a drawer ────────────── */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
                width: var(--sidebar-width);  /* keep 260px on mobile */
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 4px 0 24px rgba(0,0,0,0.18);
            }

            .main-content { margin-left: 0; }

            .hamburger { display: flex; }

            .page-title { font-size: 16px; }

            /* Hide global search on mobile — too cramped */
            .search-box { display: none; }

            .content-area { padding: 16px 14px; }

            .grid-2,
            .grid-3,
            .grid-4 { grid-template-columns: 1fr; gap: 12px; }

            .card { padding: 16px; }

            /* Stack card-header on mobile */
            .card-header { flex-direction: column; align-items: flex-start; }

            /* Make filter bars scroll horizontally */
            .filter-bar {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 4px;
                gap: 8px;
            }
            .filter-bar::-webkit-scrollbar { height: 3px; }
            .filter-bar::-webkit-scrollbar-thumb { background: var(--border); }

            .filter-select { min-width: 140px; }

            /* Buttons: shrink padding slightly */
            .btn { padding: 7px 12px; font-size: 12px; }

            /* Metric value smaller */
            .metric-value { font-size: 22px; }

            /* Modals: full-width on mobile */
            [id$="Modal"] > div {
                width: 95% !important;
                max-width: 95% !important;
                margin: 0 auto;
                border-radius: 12px;
            }
        }

        /* ── Phone (480px) ─────────────────────────────────────────────────── */
        @media (max-width: 480px) {
            :root { --topbar-h: 54px; }

            .top-bar { padding: 0 14px; }

            .page-title { font-size: 15px; }

            .content-area { padding: 12px 10px; }

            .card { padding: 14px 12px; border-radius: 10px; }

            .data-table th,
            .data-table td { padding: 9px 10px; font-size: 12px; }

            .btn { padding: 6px 10px; font-size: 12px; gap: 4px; }

            /* Hide button text labels on very small screens, keep icons */
            .btn .btn-label { display: none; }

            .metric-value { font-size: 20px; }

            .badge, .status { font-size: 10px; padding: 2px 8px; }

            /* Stack top-actions */
            .top-actions { gap: 6px; }

            .icon-btn { width: 32px; height: 32px; font-size: 14px; }
        }

        /* ── Very small (360px) ────────────────────────────────────────────── */
        @media (max-width: 360px) {
            .content-area { padding: 10px 8px; }
            .card { padding: 12px 10px; }
        }

        /* ════════════════════════════════════════════════════════════════════
           GLOBAL RESPONSIVE UTILITIES — applied to every page
           ════════════════════════════════════════════════════════════════════ */

        /* ── All tables get horizontal scroll automatically ─────────────────── */
        .card > .data-table,
        .card > div > .data-table,
        [style*="overflow-x:auto"] > .data-table {
            /* already inside a scroll wrapper — no change needed */
        }

        /* Ensure every table wrapper scrolls on mobile */
        .table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -4px;
            padding: 0 4px;
        }

        /* ── Filter rows: always wrap, never overflow ────────────────────────── */
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        .filter-row > * { flex-shrink: 0; }

        /* ── Stat card: icon + text side-by-side, stacks on tiny screens ─────── */
        .stat-card {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        /* ── Dashboard circle cards: stack on mobile ────────────────────────── */
        .circle-card-inner {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        /* ── Pagination wrapper ─────────────────────────────────────────────── */
        .pagination-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 4px 4px;
            border-top: 1px solid var(--border);
            margin-top: 8px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .pagination-wrap span { font-size: 12px; color: var(--text-secondary); }

        /* ── Flash messages ────────────────────────────────────────────────── */
        .flash-success { background: #E8F5E9; border: 1px solid #A5D6A7; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; color: #2E7D32; display: flex; align-items: center; gap: 10px; }
        .flash-error { background: #FFEBEE; border: 1px solid #FFCDD2; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; color: #C62828; display: flex; align-items: center; gap: 10px; }
        .flash-info { background: #E3F2FD; border: 1px solid #90CAF9; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; color: #1565C0; display: flex; align-items: center; gap: 10px; }
        .flash-warning { background: #FFF3E0; border: 1px solid #FFCC80; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; color: #E65100; display: flex; align-items: center; gap: 10px; }

        /* ── Modals ────────────────────────────────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: white;
            border-radius: 12px;
            padding: 28px;
            width: 500px;
            max-width: 95%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 15px; font-weight: 600; }
        .modal-close { background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-secondary); }
        .modal-body { margin-bottom: 20px; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; }

        /* ── Empty state ───────────────────────────────────────────────────── */
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .empty-state i { font-size: 48px; opacity: 0.3; display: block; margin-bottom: 12px; }
        .empty-state p { font-size: 15px; margin-bottom: 6px; }
        .empty-state small { font-size: 12px; opacity: 0.7; }

        /* ── Tabs ──────────────────────────────────────────────────────────── */
        .tab-nav {
            display: flex; gap: 4px; margin-bottom: 20px;
            border-bottom: 2px solid var(--border);
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 10px 18px; border: none; background: none;
            font-size: 13px; font-weight: 500; cursor: pointer;
            color: var(--text-secondary); border-bottom: 2px solid transparent;
            margin-bottom: -2px; transition: color 0.15s, border-color 0.15s;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .tab-btn:hover { color: var(--primary); }
        .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ── Detail / info grid ────────────────────────────────────────────── */
        .section-title {
            font-size: 12px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; color: var(--text-secondary);
            margin-bottom: 14px; display: flex; align-items: center; gap: 6px;
        }
        .info-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;
        }
        .detail-label { font-size: 11px; color: var(--text-secondary); margin-bottom: 3px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
        .detail-value { font-size: 13px; color: var(--text-primary); font-weight: 500; }

        /* ── Action group ──────────────────────────────────────────────────── */
        .action-group { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
            .tab-btn { padding: 8px 12px; font-size: 12px; }
        }

        /* ── Scroll container ──────────────────────────────────────────────── */
        .scroll-container { max-height: 320px; overflow-y: auto; }

        /* ── Back + action bar ──────────────────────────────────────────────── */
        .page-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .page-actions-right {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ── Forms ─────────────────────────────────────────────────────────── */
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
            padding: 9px 13px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            background: #fff;
            outline: none;
            transition: border-color 0.15s;
        }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,188,212,0.12); }
        .form-control.is-invalid { border-color: var(--danger); }
        .invalid-feedback { color: var(--danger); font-size: 12px; margin-top: 4px; }

        /* ── Form grids collapse on mobile ──────────────────────────────────── */
        @media (max-width: 768px) {
            /* Tables: force scroll wrapper on all data tables */
            .card { overflow: visible; }
            .data-table { min-width: 600px; }

            /* Stat cards: keep horizontal on tablet */
            .stat-card { gap: 10px; }
            .stat-card > div:first-child { flex-shrink: 0; }

            /* Circle cards: stack vertically */
            .circle-card-inner { flex-direction: column; align-items: flex-start; gap: 14px; }

            /* Filter rows: allow wrapping */
            .filter-row { gap: 8px; }
            .filter-select { min-width: 0; flex: 1 1 140px; }

            /* Page action bars */
            .page-actions { flex-direction: column; align-items: flex-start; }
            .page-actions-right { width: 100%; }

            /* Inline search boxes inside cards */
            .search-box { width: 100% !important; max-width: 100%; }
            .search-box input { width: 100% !important; }

            /* Remove margin-left: auto from filter action groups */
            .filter-actions { margin-left: 0 !important; width: 100%; }

            /* Inline date inputs */
            input[type="date"].filter-select { width: 100% !important; min-width: 0; flex: 1 1 130px; }
        }

        @media (max-width: 480px) {
            /* Tables: tighter min-width on very small screens */
            .data-table { min-width: 480px; }
            .data-table th, .data-table td { padding: 8px 10px; font-size: 11.5px; }

            /* Stat cards: stack icon above text */
            .stat-card { flex-direction: column; align-items: flex-start; gap: 8px; }

            /* Modals: full-width, reduced padding */
            [id$="Modal"] > div {
                width: 96vw !important;
                max-width: 96vw !important;
                padding: 18px 14px !important;
                border-radius: 10px !important;
            }

            /* Grid-2 inside cards (e.g. form fields) */
            .grid-2 { grid-template-columns: 1fr !important; gap: 10px !important; }

            /* Pagination: stack */
            .pagination-wrap { flex-direction: column; align-items: flex-start; }

            /* Card headers: stack */
            .card-header { flex-direction: column; align-items: flex-start; gap: 8px; }

            /* Metric values */
            .metric-value { font-size: 20px !important; }

            /* Circle progress: smaller */
            .circle-progress { width: 90px !important; height: 90px !important; }
            .circle-progress svg { width: 90px !important; height: 90px !important; }
            .circle-percent { font-size: 18px !important; }

            /* Filter row: full-width items */
            .filter-row > div { flex: 1 1 100%; }
            .filter-select { width: 100% !important; }
        }

        @media (max-width: 360px) {
            .content-area { padding: 10px 8px; }
            .card { padding: 12px 10px; }
            .data-table { min-width: 420px; }
        }
        /* ── Forms — multi-section pages ──────────────────────────────────── */
        .form-section {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .section-heading {
            font-size: 14px; font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 8px;
        }
        .form-hint { font-size: 11px; color: var(--text-secondary); margin-top: 3px; }
        .req { color: var(--danger); }
        .grid-1 { display: grid; grid-template-columns: 1fr; gap: 16px; }

        /* ── Upload box ─────────────────────────────────────────────────────── */
        .upload-box {
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 18px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            position: relative;
        }
        .upload-box:hover { border-color: var(--primary); background: #F0FBFD; }
        .upload-box input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .upload-box i { font-size: 24px; color: var(--text-secondary); display: block; margin-bottom: 6px; }
        .upload-box span { font-size: 13px; color: var(--text-secondary); }

        /* ── Loan calculator box ─────────────────────────────────────────────── */
        .calc-box {
            background: linear-gradient(135deg, #E3F2FD 0%, #F0FBFD 100%);
            border: 1px solid #B3E5FC;
            border-radius: 10px;
            padding: 18px 20px;
        }
        .calc-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,188,212,0.15);
            font-size: 13px;
        }
        .calc-row:last-of-type { border-bottom: none; }
        .calc-row .label { color: var(--text-secondary); }
        .calc-row .value { font-weight: 600; color: var(--text-primary); }
        .calc-row.total .label,
        .calc-row.total .value { font-size: 15px; font-weight: 700; color: var(--primary); }

        /* ── Customer search / selection ─────────────────────────────────────── */
        .customer-search-wrap { position: relative; }
        .customer-dropdown {
            display: none;
            position: absolute; z-index: 500; top: 100%; left: 0; right: 0;
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            max-height: 280px; overflow-y: auto;
        }
        .customer-option {
            padding: 10px 14px;
            font-size: 13px;
            cursor: pointer;
            border-bottom: 1px solid var(--border);
            transition: background 0.1s;
        }
        .customer-option:last-child { border-bottom: none; }
        .customer-option:hover { background: #E3F2FD; }

        .selected-customer-badge {
            display: flex; align-items: center; gap: 12px;
            background: #E8F5E9;
            border: 1px solid #A5D6A7;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 8px;
        }

        /* ── Guarantors ─────────────────────────────────────────────────────── */
        .guarantor-row {
            display: flex; gap: 12px; align-items: flex-end;
            margin-bottom: 12px;
            padding: 12px;
            background: #FAFBFC;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        @media (max-width: 768px) {
            .form-section { padding: 16px; }
            .guarantor-row { flex-direction: column; }
            .calc-row { flex-direction: column; align-items: flex-start; gap: 2px; }
        }

    </style>

    @yield('styles')
</head>
<body>

    {{-- Sidebar overlay (tap to close on mobile) --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    @include('layouts.sidebar')

    <div class="main-content" id="mainContent">
        @include('layouts.topbar')

        <div class="content-area">
            @yield('content')
        </div>
    </div>

    <script>
        /* ── Sidebar submenu toggle ─────────────────────────────────────────── */
        document.querySelectorAll('.nav-item.has-submenu').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                this.classList.toggle('expanded');
                const submenu = this.nextElementSibling;
                if (submenu?.classList.contains('submenu')) {
                    submenu.classList.toggle('show');
                }
            });
        });

        /* ── Mobile sidebar open / close ────────────────────────────────────── */
        const sidebar  = document.getElementById('appSidebar');
        const overlay  = document.getElementById('sidebarOverlay');
        const hamburger = document.getElementById('hamburgerBtn');

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }

        hamburger?.addEventListener('click', openSidebar);
        overlay?.addEventListener('click', closeSidebar);

        /* Close sidebar when a nav link is tapped on mobile */
        document.querySelectorAll('.sidebar a.nav-item').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) closeSidebar();
            });
        });

        /* Close on resize back to desktop */
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) closeSidebar();
        });

        /* ── Swipe-to-close gesture ─────────────────────────────────────────── */
        let touchStartX = 0;
        sidebar?.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
        sidebar?.addEventListener('touchend', e => {
            const dx = e.changedTouches[0].clientX - touchStartX;
            if (dx < -60) closeSidebar(); // swipe left to close
        }, { passive: true });

        /* Swipe right from left edge to open */
        document.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
        document.addEventListener('touchend', e => {
            const dx = e.changedTouches[0].clientX - touchStartX;
            if (touchStartX < 24 && dx > 60 && window.innerWidth <= 768) openSidebar();
        }, { passive: true });
    </script>

    @yield('scripts')
</body>
</html>
