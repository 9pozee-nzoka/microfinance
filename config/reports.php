<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Report Categories
    |--------------------------------------------------------------------------
    |
    | Each category contains the reports shown on the category listing page.
    | Routes must exist for every report defined here.
    |
    */

    'categories' => [
        'operational' => [
            'slug'  => 'operational',
            'name'  => 'Operational Reports',
            'icon'  => 'fa-cogs',
            'color' => '#FF9800',
            'bg'    => '#FFF3E0',
            'reports' => [
                [
                    'slug'        => 'loans-due',
                    'name'        => 'Loans Due',
                    'route'       => 'reports.operational.loans-due',
                    'description' => 'Loans with scheduled repayments falling due in the selected period.',
                    'icon'        => 'fa-calendar-day',
                ],
                [
                    'slug'        => 'disbursed-loans',
                    'name'        => 'Disbursed Loans',
                    'route'       => 'reports.portfolio.disbursements',
                    'description' => 'Loans disbursed in a selected period by product, branch, and method.',
                    'icon'        => 'fa-paper-plane',
                ],
                [
                    'slug'        => 'new-loans',
                    'name'        => 'New Loans',
                    'route'       => 'reports.operational.new-loans',
                    'description' => 'Loan applications created in the selected period.',
                    'icon'        => 'fa-file-signature',
                ],
                [
                    'slug'        => 'pending-disbursements',
                    'name'        => 'Loans Pending Disbursement',
                    'route'       => 'reports.operational.pending-disbursements',
                    'description' => 'Approved loans awaiting disbursement.',
                    'icon'        => 'fa-hourglass-half',
                ],
                [
                    'slug'        => 'daily-activity',
                    'name'        => 'Daily Activity Summary',
                    'route'       => 'reports.operational.daily',
                    'description' => 'New customers, loans applied/approved/disbursed, and collections for any day.',
                    'icon'        => 'fa-calendar-check',
                ],
                [
                    'slug'        => 'officer-performance',
                    'name'        => 'Officer Performance',
                    'route'       => 'reports.operational.officers',
                    'description' => 'Loans originated, disbursed amounts, and collections per relationship officer.',
                    'icon'        => 'fa-user-tie',
                ],
                [
                    'slug'        => 'branch-performance',
                    'name'        => 'Branch Performance',
                    'route'       => 'reports.operational.branches',
                    'description' => 'Customer counts, active portfolio, disbursements, and collections per branch.',
                    'icon'        => 'fa-building',
                ],
            ],
        ],

        'customer' => [
            'slug'  => 'customer',
            'name'  => 'Customer Reports',
            'icon'  => 'fa-users',
            'color' => '#9C27B0',
            'bg'    => '#F3E5F5',
            'reports' => [
                [
                    'slug'        => 'outstanding-loan-balances',
                    'name'        => 'Outstanding Loan Balances',
                    'route'       => 'reports.portfolio.loan-book',
                    'description' => 'All active loans with balances, product breakdown, and risk categories.',
                    'icon'        => 'fa-book-open',
                ],
                [
                    'slug'        => 'customer-register',
                    'name'        => 'Customer Register',
                    'route'       => 'reports.customers.register',
                    'description' => 'Full customer directory with status, branch, employment, and savings filters.',
                    'icon'        => 'fa-address-book',
                ],
                [
                    'slug'        => 'credit-score-distribution',
                    'name'        => 'Credit Score Distribution',
                    'route'       => 'reports.customers.credit-scores',
                    'description' => 'Score band breakdown with top-ranked customers.',
                    'icon'        => 'fa-star',
                ],
            ],
        ],

        'risk' => [
            'slug'  => 'risk',
            'name'  => 'Risk Reports',
            'icon'  => 'fa-exclamation-triangle',
            'color' => '#F44336',
            'bg'    => '#FFEBEE',
            'reports' => [
                [
                    'slug'        => 'loan-arrears',
                    'name'        => 'Loan Arrears',
                    'route'       => 'reports.risk.loan-arrears',
                    'description' => 'Detailed arrears listing with principal, interest, OLB, overdue days, and guarantors.',
                    'icon'        => 'fa-money-bill-wave',
                ],
                [
                    'slug'        => 'loan-arrears-summary',
                    'name'        => 'Loan Arrears Summary',
                    'route'       => 'reports.risk.loan-arrears-summary',
                    'description' => 'Summary view of arrears by branch, officer, and risk category.',
                    'icon'        => 'fa-chart-bar',
                ],
                [
                    'slug'        => 'loan-dues-summary',
                    'name'        => 'Loan Dues Summary',
                    'route'       => 'reports.risk.loan-dues-summary',
                    'description' => 'Summary of loans due and expected collections by period.',
                    'icon'        => 'fa-calendar-alt',
                ],
                [
                    'slug'        => 'portfolio-at-risk',
                    'name'        => 'Portfolio at Risk (PAR)',
                    'route'       => 'reports.portfolio.par',
                    'description' => 'Loans in arrears bucketed by PAR aging.',
                    'icon'        => 'fa-exclamation-circle',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    */

    'export' => [
        'company_name'    => env('REPORT_COMPANY_NAME', config('app.name', 'Mweela Cash Capital')),
        'company_phone'   => env('REPORT_COMPANY_PHONE', '+254 700 000000'),
        'company_address' => env('REPORT_COMPANY_ADDRESS', 'Nairobi, Kenya'),
        'pdf_orientation' => 'landscape',
    ],
];
