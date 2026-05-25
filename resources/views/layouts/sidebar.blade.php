<aside class="sidebar">
    <div class="sidebar-header">
        <a href="{{ route('dashboard') }}" class="brand">
            <div class="brand-icon">G</div>
            <span>GetCash Capital</span>
        </a>
    </div>

    <div class="user-profile">
        <div class="user-avatar">
            {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
        </div>
        <div class="user-info">
            <div class="user-name">{{ auth()->user()->name ?? 'User' }}</div>
            <div class="user-role">{{ auth()->user()->designation ?? 'User' }}</div>
        </div>
    </div>

    <nav class="nav-section">
        <div class="nav-label">Navigation</div>

        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
            <i class="fas fa-chevron-right chevron" style="font-size: 10px;"></i>
        </a>

        <button class="nav-item has-submenu {{ request()->routeIs('customers.*') ? 'active expanded' : '' }}">
            <i class="fas fa-users"></i>
            <span>Customer Management</span>
            <i class="fas fa-chevron-right chevron"></i>
        </button>
        <div class="submenu {{ request()->routeIs('customers.*') ? 'show' : '' }}">
            <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers.index') ? 'active' : '' }}">
                <i class="fas fa-user-cog"></i>
                <span>Manage Customers</span>
            </a>
            <a href="{{ route('customers.new') }}" class="nav-item {{ request()->routeIs('customers.new') ? 'active' : '' }}">
                <i class="fas fa-user-plus"></i>
                <span>Newly Registered</span>
            </a>
            <a href="{{ route('customers.rejected') }}" class="nav-item {{ request()->routeIs('customers.rejected') ? 'active' : '' }}">
                <i class="fas fa-user-times"></i>
                <span>Rejected Customers</span>
            </a>
            <a href="{{ route('customers.credit-history') }}" class="nav-item {{ request()->routeIs('customers.credit-history') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i>
                <span>Credit Score History</span>
            </a>
            <a href="{{ route('customers.limits') }}" class="nav-item {{ request()->routeIs('customers.limits') ? 'active' : '' }}">
                <i class="fas fa-sliders-h"></i>
                <span>Limit Management</span>
            </a>
        </div>

        <button class="nav-item has-submenu {{ request()->routeIs('loans.*') || request()->routeIs('collection.*') ? 'active expanded' : '' }}">
            <i class="fas fa-hand-holding-usd"></i>
            <span>Loan Management</span>
            <i class="fas fa-chevron-right chevron"></i>
        </button>
        <div class="submenu {{ request()->routeIs('loans.*') || request()->routeIs('collection.*') ? 'show' : '' }}">
            <a href="{{ route('loans.approve') }}" class="nav-item {{ request()->routeIs('loans.approve') ? 'active' : '' }}">
                <i class="fas fa-check-circle"></i>
                <span>Approve New Loans</span>
            </a>
            <a href="{{ route('loans.index') }}" class="nav-item {{ request()->routeIs('loans.index') ? 'active' : '' }}">
                <i class="fas fa-list"></i>
                <span>All Loans</span>
            </a>
            <a href="{{ route('collection.index') }}" class="nav-item {{ request()->routeIs('collection.index') ? 'active' : '' }}">
                <i class="fas fa-bell"></i>
                <span>Loan Collection</span>
            </a>
            <a href="{{ route('collection.overdue') }}" class="nav-item {{ request()->routeIs('collection.overdue') ? 'active' : '' }}">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Overdue Loans</span>
            </a>
            <a href="{{ route('collection.schedules') }}" class="nav-item {{ request()->routeIs('collection.schedules') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i>
                <span>SMS Schedules</span>
            </a>
            <a href="{{ route('collection.sms-logs') }}" class="nav-item {{ request()->routeIs('collection.sms-logs') ? 'active' : '' }}">
                <i class="fas fa-sms"></i>
                <span>SMS Logs</span>
            </a>
        </div>

        <button class="nav-item has-submenu {{ request()->routeIs('transactions.*') ? 'active expanded' : '' }}">
            <i class="fas fa-dollar-sign"></i>
            <span>Transactions</span>
            <i class="fas fa-chevron-right chevron"></i>
        </button>
        <div class="submenu {{ request()->routeIs('transactions.*') ? 'show' : '' }}">
            <a href="{{ route('transactions.money-in') }}" class="nav-item {{ request()->routeIs('transactions.money-in') ? 'active' : '' }}">
                <i class="fas fa-money-bill-wave"></i>
                <span>Money In</span>
            </a>
            <a href="{{ route('transactions.suspense') }}" class="nav-item {{ request()->routeIs('transactions.suspense') ? 'active' : '' }}">
                <i class="fas fa-question-circle"></i>
                <span>Suspense</span>
            </a>
            <a href="{{ route('transactions.processed') }}" class="nav-item {{ request()->routeIs('transactions.processed') ? 'active' : '' }}">
                <i class="fas fa-check-double"></i>
                <span>Processed</span>
            </a>
        </div>

        <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="fas fa-chart-pie"></i>
            <span>Report Management</span>
            <i class="fas fa-chevron-right chevron" style="font-size: 10px;"></i>
        </a>
    </nav>

    <div style="margin-top: auto; padding: 15px 20px; border-top: 1px solid var(--border);">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-item" style="color: var(--danger);">
                <i class="fas fa-power-off"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>