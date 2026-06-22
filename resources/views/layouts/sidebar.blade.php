<aside class="sidebar" id="appSidebar">

    <div class="sidebar-header">
        <a href="{{ route('dashboard') }}" class="brand">
            <div class="brand-icon">M</div>
            <span>Mweela Cash Capital</span>
        </a>
    </div>

    <div class="user-profile">
        <div class="user-avatar">
            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
        </div>
        <div class="user-info">
            <div class="user-name">{{ auth()->user()->name ?? 'User' }}</div>
            <div class="user-role">{{ auth()->user()->designation ?? 'Staff' }}</div>
        </div>
    </div>

    <nav class="nav-section">
        <div class="nav-label">Navigation</div>

        {{-- Dashboard — all staff --}}
        <a href="{{ route('dashboard') }}"
           class="nav-item {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </a>

        {{-- Customer Management — loan officers + admins --}}
        @hasanyrole('loan_officer|branch_manager|admin|super_admin')
        <button class="nav-item has-submenu {{ request()->routeIs('customers.*') ? 'active expanded' : '' }}">
            <i class="fas fa-users"></i>
            <span>Customer Management</span>
            <i class="fas fa-chevron-right chevron"></i>
        </button>
        <div class="submenu {{ request()->routeIs('customers.*') ? 'show' : '' }}">
            <a href="{{ route('customers.index') }}"
               class="nav-item {{ request()->routeIs('customers.index') ? 'active' : '' }}">
                <i class="fas fa-user-cog"></i><span>Manage Customers</span>
            </a>
            <a href="{{ route('customers.create') }}"
               class="nav-item {{ request()->routeIs('customers.create') ? 'active' : '' }}">
                <i class="fas fa-user-edit"></i><span>Register Customer</span>
            </a>
            <a href="{{ route('customers.new') }}"
               class="nav-item {{ request()->routeIs('customers.new') ? 'active' : '' }}">
                <i class="fas fa-user-plus"></i><span>Newly Registered</span>
            </a>
            @hasanyrole('branch_manager|admin|super_admin')
            <a href="{{ route('customers.rejected') }}"
               class="nav-item {{ request()->routeIs('customers.rejected') ? 'active' : '' }}">
                <i class="fas fa-user-times"></i><span>Rejected</span>
            </a>
            <a href="{{ route('customers.credit-history') }}"
               class="nav-item {{ request()->routeIs('customers.credit-history') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i><span>Credit History</span>
            </a>
            <a href="{{ route('customers.limits') }}"
               class="nav-item {{ request()->routeIs('customers.limits') ? 'active' : '' }}">
                <i class="fas fa-sliders-h"></i><span>Limit Management</span>
            </a>
            @endhasanyrole
        </div>
        @endhasanyrole

        {{-- Loan Management — loan officers see their loans; admins see all --}}
        @hasanyrole('loan_officer|branch_manager|admin|super_admin')
        <button class="nav-item has-submenu {{ request()->routeIs('loans.*') || request()->routeIs('collection.*') || request()->routeIs('loan-products.*') ? 'active expanded' : '' }}">
            <i class="fas fa-hand-holding-usd"></i>
            <span>Loan Management</span>
            <i class="fas fa-chevron-right chevron"></i>
        </button>
        <div class="submenu {{ request()->routeIs('loans.*') || request()->routeIs('collection.*') || request()->routeIs('loan-products.*') ? 'show' : '' }}">

            {{-- Approve — admin/branch manager only --}}
            @hasanyrole('branch_manager|admin|super_admin')
            <a href="{{ route('loans.approve') }}"
               class="nav-item {{ request()->routeIs('loans.approve') ? 'active' : '' }}">
                <i class="fas fa-check-circle"></i><span>Approve New Loans</span>
            </a>
            @endhasanyrole

            <a href="{{ route('loans.index') }}"
               class="nav-item {{ request()->routeIs('loans.index') ? 'active' : '' }}">
                <i class="fas fa-list"></i><span>All Loans</span>
            </a>

            {{-- Loan Products — admin only --}}
            @hasanyrole('admin|super_admin')
            <a href="{{ route('loan-products.index') }}"
               class="nav-item {{ request()->routeIs('loan-products.*') ? 'active' : '' }}">
                <i class="fas fa-box"></i><span>Loan Products</span>
            </a>
            @endhasanyrole

            {{-- Collections — all loan-related roles --}}
            <a href="{{ route('collection.index') }}"
               class="nav-item {{ request()->routeIs('collection.index') ? 'active' : '' }}">
                <i class="fas fa-bell"></i><span>Loan Collection</span>
            </a>
            <a href="{{ route('collection.overdue') }}"
               class="nav-item {{ request()->routeIs('collection.overdue') ? 'active' : '' }}">
                <i class="fas fa-exclamation-triangle"></i><span>Overdue Loans</span>
            </a>
            <a href="{{ route('collection.schedules') }}"
               class="nav-item {{ request()->routeIs('collection.schedules') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i><span>SMS Schedules</span>
            </a>
            <a href="{{ route('collection.sms-logs') }}"
               class="nav-item {{ request()->routeIs('collection.sms-logs') ? 'active' : '' }}">
                <i class="fas fa-sms"></i><span>SMS Logs</span>
            </a>
        </div>
        @endhasanyrole

        {{-- Transactions — admin/branch manager --}}
        @hasanyrole('branch_manager|admin|super_admin')
        <button class="nav-item has-submenu {{ request()->routeIs('transactions.*') ? 'active expanded' : '' }}">
            <i class="fas fa-dollar-sign"></i>
            <span>Transactions</span>
            <i class="fas fa-chevron-right chevron"></i>
        </button>
        <div class="submenu {{ request()->routeIs('transactions.*') ? 'show' : '' }}">
            <a href="{{ route('transactions.money-in') }}"
               class="nav-item {{ request()->routeIs('transactions.money-in') ? 'active' : '' }}">
                <i class="fas fa-money-bill-wave"></i><span>Money In</span>
            </a>
            <a href="{{ route('transactions.suspense') }}"
               class="nav-item {{ request()->routeIs('transactions.suspense') ? 'active' : '' }}">
                <i class="fas fa-question-circle"></i><span>Suspense</span>
            </a>
            <a href="{{ route('transactions.processed') }}"
               class="nav-item {{ request()->routeIs('transactions.processed') ? 'active' : '' }}">
                <i class="fas fa-check-double"></i><span>Processed</span>
            </a>
        </div>
        @endhasanyrole

        {{-- M-Pesa — admin/branch manager --}}
        @hasanyrole('branch_manager|admin|super_admin')
        <a href="{{ route('mpesa.index') }}"
           class="nav-item {{ request()->routeIs('mpesa.*') ? 'active' : '' }}">
            <i class="fas fa-mobile-alt"></i>
            <span>M-Pesa</span>
        </a>
        @endhasanyrole

        {{-- Staff Management — admin only --}}
        @hasanyrole('admin|super_admin')
        <button class="nav-item has-submenu {{ request()->routeIs('staff.*') ? 'active expanded' : '' }}">
            <i class="fas fa-user-tie"></i>
            <span>Staff Management</span>
            <i class="fas fa-chevron-right chevron"></i>
        </button>
        <div class="submenu {{ request()->routeIs('staff.*') ? 'show' : '' }}">
            <a href="{{ route('staff.index') }}"
               class="nav-item {{ request()->routeIs('staff.index') ? 'active' : '' }}">
                <i class="fas fa-users-cog"></i><span>Staff Overview</span>
            </a>
            <a href="{{ route('staff.create') }}"
               class="nav-item {{ request()->routeIs('staff.create') ? 'active' : '' }}">
                <i class="fas fa-user-plus"></i><span>Add Staff</span>
            </a>
        </div>
        @endhasanyrole

        {{-- Branch Management — admin only --}}
        @hasanyrole('admin|super_admin')
        <a href="{{ route('branches.index') }}"
           class="nav-item {{ request()->routeIs('branches.*') ? 'active' : '' }}">
            <i class="fas fa-building"></i>
            <span>Branch Management</span>
        </a>
        @endhasanyrole

        {{-- Reports — branch manager + admin --}}
        @hasanyrole('branch_manager|admin|super_admin')
        <button class="nav-item has-submenu {{ request()->routeIs('reports.*') ? 'active expanded' : '' }}">
            <i class="fas fa-chart-pie"></i>
            <span>Reports</span>
            <i class="fas fa-chevron-right chevron"></i>
        </button>
        <div class="submenu {{ request()->routeIs('reports.*') ? 'show' : '' }}">
            <a href="{{ route('reports.categories.index') }}" class="nav-item {{ request()->routeIs('reports.categories.*') ? 'active' : '' }}">
                <i class="fas fa-th-large"></i><span>Report Categories</span>
            </a>
            <a href="{{ route('reports.categories.show', 'operational') }}" class="nav-item {{ request()->routeIs('reports.operational.*') ? 'active' : '' }}">
                <i class="fas fa-cogs"></i><span>Operational Reports</span>
            </a>
            <a href="{{ route('reports.categories.show', 'customer') }}" class="nav-item {{ request()->routeIs('reports.portfolio.loan-book') || request()->routeIs('reports.customers.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i><span>Customer Reports</span>
            </a>
            <a href="{{ route('reports.categories.show', 'risk') }}" class="nav-item {{ request()->routeIs('reports.risk.*') || request()->routeIs('reports.portfolio.par') ? 'active' : '' }}">
                <i class="fas fa-exclamation-triangle"></i><span>Risk Reports</span>
            </a>
        </div>
        @endhasanyrole

        {{-- Report Management — loan officers + managers + admins --}}
        @hasanyrole('loan_officer|branch_manager|admin|super_admin')
        <button class="nav-item has-submenu {{ request()->routeIs('staff.reports.*') ? 'active expanded' : '' }}">
            <i class="fas fa-file-download"></i>
            <span>Report Management</span>
            <i class="fas fa-chevron-right chevron"></i>
        </button>
        <div class="submenu {{ request()->routeIs('staff.reports.*') ? 'show' : '' }}">
            <a href="{{ route('staff.reports.categories') }}" class="nav-item {{ request()->routeIs('staff.reports.*') ? 'active' : '' }}">
                <i class="fas fa-folder-open"></i><span>My Reports</span>
            </a>
        </div>
        @endhasanyrole

    </nav>

    <div class="sidebar-footer">
        <a href="{{ route('profile.change-password') }}"
           class="nav-item {{ request()->routeIs('profile.change-password') ? 'active' : '' }}"
           style="padding-left:0;">
            <i class="fas fa-lock"></i>
            <span>Change Password</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-item" style="color:var(--danger);padding-left:0;">
                <i class="fas fa-power-off"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>

</aside>
