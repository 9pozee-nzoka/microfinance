<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GetCash Capital')</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #00BCD4;
            --primary-dark: #00ACC1;
            --success: #4CAF50;
            --warning: #FF9800;
            --danger: #F44336;
            --bg: #F5F7FA;
            --card-bg: #FFFFFF;
            --text-primary: #2C3E50;
            --text-secondary: #7F8C8D;
            --sidebar-width: 260px;
            --sidebar-bg: #FFFFFF;
            --border: #E8ECF1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.5;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            z-index: 1000;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 18px;
            color: var(--primary);
            text-decoration: none;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .user-profile {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .user-info { flex: 1; }

        .user-name { font-weight: 600; font-size: 13px; }

        .user-role { font-size: 11px; color: var(--text-secondary); }

        .nav-section { padding: 10px 0; }

        .nav-label {
            padding: 8px 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            font-size: 14px;
            text-align: left;
        }

        .nav-item:hover { background: #F0F4F8; color: var(--primary); }

        .nav-item.active {
            background: #E3F2FD;
            color: var(--primary);
            border-right: 3px solid var(--primary);
        }

        .nav-item i { width: 20px; text-align: center; font-size: 16px; }

        .nav-item .chevron {
            margin-left: auto;
            font-size: 12px;
            transition: transform 0.2s;
        }

        .nav-item.expanded .chevron { transform: rotate(90deg); }

        .submenu { display: none; background: #FAFBFC; }

        .submenu.show { display: block; }

        .submenu .nav-item { padding-left: 52px; font-size: 13px; }

        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }

        .top-bar {
            background: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .page-title { font-size: 20px; font-weight: 600; }

        .search-box { position: relative; width: 300px; }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            outline: none;
        }

        .search-box input:focus { border-color: var(--primary); }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 14px;
        }

        .top-actions { display: flex; align-items: center; gap: 15px; }

        .icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .content-area { padding: 25px 30px; }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--border);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-primary { background: #E3F2FD; color: var(--primary); }
        .badge-success { background: #E8F5E9; color: var(--success); }
        .badge-warning { background: #FFF3E0; color: var(--warning); }
        .badge-danger { background: #FFEBEE; color: var(--danger); }

        .metric-value { font-size: 28px; font-weight: 700; color: var(--text-primary); }

        .metric-label { font-size: 12px; color: var(--text-secondary); margin-top: 4px; }

        .circle-progress { position: relative; width: 120px; height: 120px; }

        .circle-progress svg { transform: rotate(-90deg); }

        .circle-bg { fill: none; stroke: #E8ECF1; stroke-width: 8; }

        .circle-fill { fill: none; stroke-width: 8; stroke-linecap: round; }

        .circle-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .circle-percent { font-size: 24px; font-weight: 700; }

        .circle-label { font-size: 11px; color: var(--text-secondary); }

        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }

        .data-table { width: 100%; border-collapse: collapse; }

        .data-table th {
            text-align: left;
            padding: 12px 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            background: #FAFBFC;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }

        .data-table tr:hover { background: #FAFBFC; }

        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        .status-partially-approved { background: #FFF3E0; color: #E65100; }
        .status-active { background: #E8F5E9; color: #2E7D32; }
        .status-rejected { background: #FFEBEE; color: #C62828; }
        .status-pending { background: #E3F2FD; color: #1565C0; }
        .status-disbursed { background: #F3E5F5; color: #6A1B9A; }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: white; border: 1px solid var(--border); color: var(--text-primary); }

        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 13px;
            background: white;
            min-width: 180px;
        }

        @media (max-width: 1200px) { .grid-4 { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
        }
    </style>

    @yield('styles')
</head>
<body>
    @include('layouts.sidebar')

    <div class="main-content">
        @include('layouts.topbar')

        <div class="content-area">
            @yield('content')
        </div>
    </div>

    <script>
        document.querySelectorAll('.nav-item.has-submenu').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                this.classList.toggle('expanded');
                const submenu = this.nextElementSibling;
                if (submenu && submenu.classList.contains('submenu')) {
                    submenu.classList.toggle('show');
                }
            });
        });
    </script>

    @yield('scripts')
</body>
</html>