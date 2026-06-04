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

        {{-- Dashboard — All staff --}}
        <a href="{{ route('dashboard') }}"
           class="nav-item {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </a>

        {{-- Customer Management — Loan Officers, Branch Managers, Admins --}}
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
                <i class="fas fa-user-edit"></i><span>Member Registration</span>
            </a>
            <a href="{{ route('customers.new') }}"
               class="nav-item {{ request()->routeIs('customers.new') ? 'active' : '' }}">
                <i class="fas fa-user-plus"></i><span>Newly Registered</span>
            </a>
            <a href="{{ route('customers.rejected') }}"
               class="nav-item {{ request()->routeIs('customers.rejected') ? 'active' : '' }}">
                <i class="fas fa-user-times"></i><span>Rejected Customers</span>
            </a>
            <a href="{{ route('customers.credit-history') }}"
               class="nav-item {{ request()->routeIs('customers.credit-history') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i><span>Credit Score History</span>
            </a>
            <a href="{{ route('customers.limits') }}"
               class="nav-item {{ request()->routeIs('customers.limits') ? 'active' : '' }}">
                <i class="fas fa-sliders-h"></i><span>Limit Management</span>
            </a>
        </div>
        @endhasanyrole

        {{-- Loan Management — Multiple roles with different access --}}
        @hasanyrole('loan_officer|credit_committee|cashier|branch_manager|admin|super_admin')
        <button class="nav-item has-submenu {{ request()->routeIs('loans.*') || request()->routeIs('collection.*') || request()->routeIs('loan-products.*') ? 'active expanded' : '' }}">
            <i class="fas fa-hand-holding-usd"></i>
            <span>Loan Management</span>
            <i class="fas fa-chevron-right chevron"></i>
        </button>
        <div class="submenu {{ request()->routeIs('loans.*') || request()->routeIs('collection.*') || request()->routeIs('loan-products.*') ? 'show' : '' }}">

            {{-- Loan Approval — Credit Committee, Admins --}}
            @hasanyrole('credit_committee|admin|super_admin')
            <a href="{{ route('loans.approve') }}"
               class="nav-item {{ request()->routeIs('loans.approve') ? 'active' : '' }}">
                <i class="fas fa-check-circle"></i><span>Approve New Loans</span>
            </a>
            @endhasanyrole

            {{-- All Loans — Loan Officers, Branch Managers, Admins --}}
            @hasanyrole('loan_officer|branch_manager|admin|super_admin')
            <a href="{{ route('loans.index') }}"
               class="nav-item {{ request()->routeIs('loans.index') ? 'active' : '' }}">
                <i class="fas fa-list"></i><span>All Loans</span>
            </a>
            @endhasanyrole

            {{-- Loan Products — Admins only --}}
            @hasanyrole('admin|super_admin')
            <a href="{{ route('loan-products.index') }}"
               class="nav-item {{ request()->routeIs('loan-products.*') ? 'active' : '' }}">
                <i class="fas fa-box"></i><span>Loan Products</span>
            </a>
            @endhasanyrole

            {{-- Collections — Loan Officers, Branch Managers, Admins --}}
            @hasanyrole('loan_officer|branch_manager|admin|super_admin')
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
            @endhasanyrole
        </div>
        @endhasanyrole

        {{-- Transactions — Cashiers, Admins --}}
        @hasanyrole('cashier|admin|super_admin')
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

        {{-- M-Pesa — Cashiers, Admins --}}
        @hasanyrole('cashier|admin|super_admin')
        <a href="{{ route('mpesa.index') }}"
           class="nav-item {{ request()->routeIs('mpesa.*') ? 'active' : '' }}">
            <i class="fas fa-mobile-alt"></i>
            <span>M-Pesa</span>
        </a>
        @endhasanyrole

        {{-- Staff Management — Admins only --}}
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

        {{-- Branch Management — Admins only --}}
        @hasanyrole('admin|super_admin')
        <a href="{{ route('branches.index') }}"
           class="nav-item {{ request()->routeIs('branches.*') ? 'active' : '' }}">
            <i class="fas fa-building"></i>
            <span>Branch Management</span>
        </a>
        @endhasanyrole

        {{-- Reports — Auditors, Branch Managers, Admins --}}
        @hasanyrole('auditor|branch_manager|admin|super_admin')
        <a href="{{ route('reports.index') }}"
           class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="fas fa-chart-pie"></i>
            <span>Report Management</span>
        </a>
        @endhasanyrole

    </nav>

    <div class="sidebar-footer">
        <a href="{{ route('profile.change-password') }}"
           class="nav-item {{ request()->routeIs('profile.change-password') ? 'active' : '' }}"
           style="padding-left: 0;">
            <i class="fas fa-lock"></i>
            <span>Change Password</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-item" style="color: var(--danger); padding-left: 0;">
                <i class="fas fa-power-off"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>

</aside>
