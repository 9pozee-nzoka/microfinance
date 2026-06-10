<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore Mweela Cash Capital loan products - Business Loans, Personal Loans, and SME Financing. Competitive rates, flexible terms, and quick approval in Kenya.">
    <meta name="keywords" content="business loans Kenya, personal loans, SME financing, microfinance products, loan rates Kenya, Mweela Cash Capital loans, quick loans Mutomo">
    <meta name="author" content="Mweela Cash Capital">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://mweelacredit.co.ke/our-loans">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://mweelacredit.co.ke/our-loans">
    <meta property="og:title" content="Loan Products - Mweela Cash Capital">
    <meta property="og:description" content="Business Loans, Personal Loans, and SME Financing with competitive rates and quick approval in Kenya.">
    <meta property="og:image" content="https://mweelacredit.co.ke/images/og-image.jpg">
    <meta property="og:site_name" content="Mweela Cash Capital">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Loan Products - Mweela Cash Capital">
    <meta name="twitter:description" content="Business Loans, Personal Loans, and SME Financing with competitive rates and quick approval in Kenya.">
    <meta name="twitter:image" content="https://mweelacredit.co.ke/images/og-image.jpg">

    <title>Loan Products - Mweela Cash Capital</title>

    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#00BCD4">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @include('partials.public-styles')

    <style>
        .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 28px; }
        .product-card { background: white; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-md); transition: var(--transition); }
        .product-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); }
        .product-header { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; padding: 36px; text-align: center; }
        .product-header i { font-size: 44px; margin-bottom: 14px; }
        .product-header h3 { font-size: 24px; font-weight: 700; }
        .product-header .tagline { font-size: 14px; opacity: 0.9; margin-top: 6px; }
        .product-body { padding: 32px; }
        .product-body > p { color: var(--text-secondary); font-size: 15px; margin-bottom: 24px; line-height: 1.6; }

        .product-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
        .meta-item { background: var(--bg); padding: 14px; border-radius: var(--radius-sm); text-align: center; }
        .meta-item .value { font-size: 18px; font-weight: 700; color: var(--primary); }
        .meta-item .label { font-size: 12px; color: var(--text-secondary); margin-top: 4px; }

        .feature-list { list-style: none; margin-bottom: 24px; }
        .feature-list li { display: flex; align-items: center; gap: 10px; padding: 10px 0; font-size: 14px; color: var(--text-primary); border-bottom: 1px solid #F0F0F0; }
        .feature-list li:last-child { border-bottom: none; }
        .feature-list i { color: var(--success); font-size: 14px; }

        .product-cta { display: block; width: 100%; padding: 14px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; text-align: center; text-decoration: none; border-radius: var(--radius-sm); font-weight: 600; font-size: 15px; transition: var(--transition); }
        .product-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,188,212,0.4); }

        .comparison-table { width: 100%; border-collapse: collapse; background: white; border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm); }
        .comparison-table th { background: var(--text-primary); color: white; padding: 18px; text-align: left; font-size: 14px; font-weight: 600; }
        .comparison-table td { padding: 16px 18px; border-bottom: 1px solid #F0F0F0; font-size: 14px; }
        .comparison-table tr:last-child td { border-bottom: none; }
        .comparison-table td:first-child { font-weight: 600; color: var(--text-primary); }
        .comparison-table .check { color: var(--success); }

        .process-steps { display: flex; justify-content: space-between; gap: 32px; }
        .step { flex: 1; text-align: center; position: relative; }
        .step:not(:last-child)::after { content: ''; position: absolute; top: 28px; right: -24px; width: 32px; height: 2px; background: var(--border); }
        .step-number { width: 56px; height: 56px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; margin: 0 auto 16px; box-shadow: 0 4px 14px rgba(0,188,212,0.35); }
        .step h4 { font-size: 17px; font-weight: 700; margin-bottom: 8px; }
        .step p { font-size: 14px; color: var(--text-secondary); line-height: 1.5; }

        @media (max-width: 768px) {
            .products-grid { grid-template-columns: 1fr; }
            .process-steps { flex-direction: column; gap: 32px; }
            .step:not(:last-child)::after { display: none; }
            .comparison-table { font-size: 12px; }
            .comparison-table th, .comparison-table td { padding: 12px; }
            .product-header { padding: 28px; }
            .product-header h3 { font-size: 20px; }
        }
    </style>

    @php
    $productsJsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'CollectionPage',
                '@id' => 'https://mweelacredit.co.ke/our-loans#collectionpage',
                'url' => 'https://mweelacredit.co.ke/our-loans',
                'name' => 'Loan Products - Mweela Cash Capital',
                'isPartOf' => ['@id' => 'https://mweelacredit.co.ke/#website'],
                'about' => ['@id' => 'https://mweelacredit.co.ke/#organization'],
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'item' => [
                                '@type' => 'FinancialProduct',
                                'name' => 'Business Loans',
                                'description' => 'Flexible financing to grow your enterprise with competitive rates and quick approval.',
                                'provider' => ['@id' => 'https://mweelacredit.co.ke/#organization'],
                                'areaServed' => 'Kenya'
                            ]
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'item' => [
                                '@type' => 'FinancialProduct',
                                'name' => 'Personal Loans',
                                'description' => 'Quick personal loans for education, medical, agriculture, and home improvement needs.',
                                'provider' => ['@id' => 'https://mweelacredit.co.ke/#organization'],
                                'areaServed' => 'Kenya'
                            ]
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'item' => [
                                '@type' => 'FinancialProduct',
                                'name' => 'SME Financing',
                                'description' => 'Specialized financing solutions for small and medium enterprises in Kenya.',
                                'provider' => ['@id' => 'https://mweelacredit.co.ke/#organization'],
                                'areaServed' => 'Kenya'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $productsJsonLd !!}</script>
</head>
<body>
    @include('partials.public-nav', ['active' => 'loans'])

    <section class="hero">
        <div class="container hero-content">
            <h1>Our Loan Products</h1>
            <p>Flexible financing solutions designed to meet your personal and business needs. Competitive rates, transparent terms, and quick approval.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Products</span>
                <h2>Choose the Right Loan for You</h2>
                <p>Whether you are growing a business or handling personal expenses, we have a loan product that fits.</p>
            </div>
            <div class="products-grid">
                <div class="product-card">
                    <div class="product-header">
                        <i class="fas fa-briefcase"></i>
                        <h3>Business Loans</h3>
                        <div class="tagline">Grow your enterprise</div>
                    </div>
                    <div class="product-body">
                        <p>Flexible financing designed to help your business expand, purchase inventory, or manage cash flow. Perfect for traders, shop owners, and service providers.</p>
                        <div class="product-meta">
                            <div class="meta-item">
                                <div class="value">KSH 10K – 500K</div>
                                <div class="label">Loan Amount</div>
                            </div>
                            <div class="meta-item">
                                <div class="value">4 – 52 weeks</div>
                                <div class="label">Repayment Term</div>
                            </div>
                        </div>
                        <ul class="feature-list">
                            <li><i class="fas fa-check-circle"></i> Competitive interest rates</li>
                            <li><i class="fas fa-check-circle"></i> Weekly or monthly repayments</li>
                            <li><i class="fas fa-check-circle"></i> No collateral under KSH 100K</li>
                            <li><i class="fas fa-check-circle"></i> Approval within 24 hours</li>
                            <li><i class="fas fa-check-circle"></i> Top-up for returning clients</li>
                        </ul>
                        <a href="/contact" class="product-cta">Apply Now</a>
                    </div>
                </div>

                <div class="product-card">
                    <div class="product-header">
                        <i class="fas fa-user"></i>
                        <h3>Personal Loans</h3>
                        <div class="tagline">For life's needs</div>
                    </div>
                    <div class="product-body">
                        <p>Quick personal financing for education, medical expenses, home improvements, agriculture, or any unexpected costs.</p>
                        <div class="product-meta">
                            <div class="meta-item">
                                <div class="value">KSH 5K – 200K</div>
                                <div class="label">Loan Amount</div>
                            </div>
                            <div class="meta-item">
                                <div class="value">4 – 24 weeks</div>
                                <div class="label">Repayment Term</div>
                            </div>
                        </div>
                        <ul class="feature-list">
                            <li><i class="fas fa-check-circle"></i> Minimal documentation</li>
                            <li><i class="fas fa-check-circle"></i> Flexible repayment schedules</li>
                            <li><i class="fas fa-check-circle"></i> No hidden charges</li>
                            <li><i class="fas fa-check-circle"></i> Same-day approval possible</li>
                            <li><i class="fas fa-check-circle"></i> Build credit history</li>
                        </ul>
                        <a href="/contact" class="product-cta">Apply Now</a>
                    </div>
                </div>

                <div class="product-card">
                    <div class="product-header">
                        <i class="fas fa-building"></i>
                        <h3>SME Financing</h3>
                        <div class="tagline">Scale your business</div>
                    </div>
                    <div class="product-body">
                        <p>Specialized financing for SMEs looking to scale operations, acquire equipment, or fund larger projects.</p>
                        <div class="product-meta">
                            <div class="meta-item">
                                <div class="value">KSH 100K – 2M</div>
                                <div class="label">Loan Amount</div>
                            </div>
                            <div class="meta-item">
                                <div class="value">12 – 104 weeks</div>
                                <div class="label">Repayment Term</div>
                            </div>
                        </div>
                        <ul class="feature-list">
                            <li><i class="fas fa-check-circle"></i> Higher loan limits</li>
                            <li><i class="fas fa-check-circle"></i> Asset financing options</li>
                            <li><i class="fas fa-check-circle"></i> Dedicated relationship manager</li>
                            <li><i class="fas fa-check-circle"></i> Custom repayment structuring</li>
                            <li><i class="fas fa-check-circle"></i> Business advisory support</li>
                        </ul>
                        <a href="/contact" class="product-cta">Apply Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Compare</span>
                <h2>Product Comparison</h2>
                <p>Quick overview to help you choose the right product.</p>
            </div>
            <div style="overflow-x: auto;">
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Business Loans</th>
                            <th>Personal Loans</th>
                            <th>SME Financing</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Loan Amount</td><td>KSH 10K – 500K</td><td>KSH 5K – 200K</td><td>KSH 100K – 2M</td></tr>
                        <tr><td>Term</td><td>4 – 52 weeks</td><td>4 – 24 weeks</td><td>12 – 104 weeks</td></tr>
                        <tr><td>Interest Method</td><td>Flat or Reducing</td><td>Flat or Reducing</td><td>Reducing Balance</td></tr>
                        <tr><td>Collateral</td><td>No (under KSH 100K)</td><td>No</td><td>Yes (over KSH 500K)</td></tr>
                        <tr><td>Approval Time</td><td>Within 24 hours</td><td>Same day possible</td><td>1 – 3 business days</td></tr>
                        <tr><td>Guarantors</td><td>1 – 2 required</td><td>1 required</td><td>2 – 3 required</td></tr>
                        <tr><td>M-Pesa Repayment</td><td><i class="fas fa-check check"></i></td><td><i class="fas fa-check check"></i></td><td><i class="fas fa-check check"></i></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Process</span>
                <h2>How It Works</h2>
                <p>Getting a loan from Mweela Cash Capital is simple and straightforward.</p>
            </div>
            <div class="process-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4>Apply</h4>
                    <p>Visit our branch or contact us to start your application with minimal documentation.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h4>Assessment</h4>
                    <p>Our team reviews your application and conducts a quick credit assessment.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h4>Approval</h4>
                    <p>Receive approval and sign your loan agreement with clear, transparent terms.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h4>Disbursement</h4>
                    <p>Funds are disbursed to your M-Pesa or bank account. Start growing today.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <h2>Have Questions?</h2>
            <p>Our team is ready to help you find the right financing solution.</p>
            <div class="hero-buttons" style="justify-content:center;">
                <a href="/contact" class="btn btn-primary">Contact Us</a>
                <a href="/about" class="btn btn-outline">Learn About Us</a>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</body>
</html>
