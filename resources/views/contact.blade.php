<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact Mweela Cash Capital in Mutomo, Kitui County. Apply for business loans, personal loans, or SME financing. Call, email, or visit our branch today.">
    <meta name="keywords" content="contact Mweela Cash Capital, Mutomo loans, apply for loan Kenya, microfinance contact, Kitui County loans, business loan application">
    <meta name="author" content="Mweela Cash Capital">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://mweelacredit.co.ke/contact">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://mweelacredit.co.ke/contact">
    <meta property="og:title" content="Contact Us - Mweela Cash Capital">
    <meta property="og:description" content="Get in touch with Mweela Cash Capital in Mutomo, Kitui County. Apply for loans or visit our branch today.">
    <meta property="og:image" content="https://mweelacredit.co.ke/images/og-image.jpg">
    <meta property="og:site_name" content="Mweela Cash Capital">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Contact Us - Mweela Cash Capital">
    <meta name="twitter:description" content="Get in touch with Mweela Cash Capital in Mutomo, Kitui County. Apply for loans or visit our branch today.">
    <meta name="twitter:image" content="https://mweelacredit.co.ke/images/og-image.jpg">

    <title>Contact Us - Mweela Cash Capital</title>

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
        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; }
        .info-cards { display: grid; gap: 16px; }
        .info-card { background: white; padding: 24px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; align-items: flex-start; gap: 16px; transition: var(--transition); }
        .info-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .info-card i { font-size: 20px; color: var(--primary); width: 44px; height: 44px; background: var(--primary-light); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .info-card h4 { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
        .info-card p { font-size: 14px; color: var(--text-secondary); line-height: 1.5; }
        .info-card a { color: var(--primary); text-decoration: none; font-weight: 500; }
        .info-card a:hover { text-decoration: underline; }

        .contact-form { background: white; padding: 36px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); }
        .contact-form h3 { font-size: 22px; font-weight: 700; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group label .req { color: var(--danger); }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 12px 14px; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-size: 15px; font-family: inherit; color: var(--text-primary); background: white;
            transition: var(--transition);
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,188,212,0.1);
        }
        .form-group textarea { resize: vertical; min-height: 110px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .submit-btn { width: 100%; padding: 14px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: white; border: none; border-radius: var(--radius-sm); font-size: 15px; font-weight: 600; cursor: pointer; transition: var(--transition); }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,188,212,0.4); }
        .form-note { font-size: 12px; color: var(--text-secondary); margin-top: 14px; text-align: center; }

        .map-section { background: white; padding: 0 0 90px; }
        .map-container { background: linear-gradient(135deg, #E8ECF1 0%, #F5F7FA 100%); border-radius: var(--radius-md); height: 380px; display: flex; align-items: center; justify-content: center; flex-direction: column; color: var(--text-secondary); box-shadow: inset 0 2px 8px rgba(0,0,0,0.04); }
        .map-container i { font-size: 48px; color: var(--primary); margin-bottom: 12px; opacity: 0.4; }

        .faq-item { background: white; border-radius: var(--radius-sm); margin-bottom: 12px; overflow: hidden; box-shadow: var(--shadow-sm); transition: var(--transition); }
        .faq-item:hover { box-shadow: var(--shadow-md); }
        .faq-question { padding: 20px 24px; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 15px; }
        .faq-question i { color: var(--primary); transition: transform 0.3s ease; }
        .faq-answer { padding: 0 24px 20px; color: var(--text-secondary); font-size: 15px; line-height: 1.6; display: none; }
        .faq-item.active .faq-answer { display: block; }
        .faq-item.active .faq-question i { transform: rotate(180deg); }

        @media (max-width: 768px) {
            .contact-grid { grid-template-columns: 1fr; gap: 40px; }
            .form-row { grid-template-columns: 1fr; }
            .contact-form { padding: 24px; }
            .map-container { height: 280px; }
        }
    </style>

    @php
    $contactJsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'ContactPage',
                '@id' => 'https://mweelacredit.co.ke/contact#contactpage',
                'url' => 'https://mweelacredit.co.ke/contact',
                'name' => 'Contact Mweela Cash Capital',
                'isPartOf' => ['@id' => 'https://mweelacredit.co.ke/#website'],
                'about' => ['@id' => 'https://mweelacredit.co.ke/#organization']
            ],
            [
                '@type' => 'LocalBusiness',
                '@id' => 'https://mweelacredit.co.ke/#localbusiness',
                'name' => 'Mweela Cash Capital',
                'image' => 'https://mweelacredit.co.ke/images/og-image.jpg',
                'url' => 'https://mweelacredit.co.ke',
                'telephone' => '+254-700-000-001',
                'email' => 'info@mweelacredit.co.ke',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'Mutomo Town',
                    'addressLocality' => 'Mutomo',
                    'addressRegion' => 'Kitui County',
                    'addressCountry' => 'KE'
                ],
                'geo' => ['@type' => 'GeoCoordinates', 'latitude' => -1.85, 'longitude' => 38.1833],
                'openingHoursSpecification' => [
                    ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday'], 'opens' => '08:00', 'closes' => '17:00'],
                    ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Saturday', 'opens' => '09:00', 'closes' => '13:00']
                ],
                'priceRange' => '$$',
                'currenciesAccepted' => 'KES',
                'paymentAccepted' => 'Cash, M-Pesa, Bank Transfer'
            ]
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $contactJsonLd !!}</script>
</head>
<body>
    @include('partials.public-nav', ['active' => 'contact'])

    <section class="hero">
        <div class="container hero-content">
            <h1>Contact Us</h1>
            <p>We are here to help. Reach out to us for loan inquiries, applications, or any questions you may have.</p>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">
            <div class="contact-grid">
                <div class="info-cards">
                    <div class="info-card">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Visit Our Branch</h4>
                            <p>Mutomo Town<br>Kitui County, Kenya</p>
                        </div>
                    </div>
                    <div class="info-card">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Call Us</h4>
                            <p><a href="tel:+254700000001">+254 700 000 001</a><br>Mon – Fri: 8AM – 5PM</p>
                        </div>
                    </div>
                    <div class="info-card">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email Us</h4>
                            <p><a href="mailto:info@mweelacredit.co.ke">info@mweelacredit.co.ke</a><br>We reply within 24 hours</p>
                        </div>
                    </div>
                    <div class="info-card">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Business Hours</h4>
                            <p>Mon – Fri: 8:00 AM – 5:00 PM<br>Sat: 9:00 AM – 1:00 PM<br>Sun: Closed</p>
                        </div>
                    </div>
                </div>

                <div class="contact-form">
                    <h3>Send Us a Message</h3>
                    @if(session('success'))
                        <div style="background: #E8F5E9; color: #2E7D32; padding: 12px 16px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 14px;">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                        </div>
                    @endif
                    <form action="/contact" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name <span class="req">*</span></label>
                                <input type="text" name="name" required placeholder="John Doe">
                            </div>
                            <div class="form-group">
                                <label>Phone <span class="req">*</span></label>
                                <input type="tel" name="phone" required placeholder="+254 7XX XXX XXX">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="john@example.com">
                        </div>
                        <div class="form-group">
                            <label>I need <span class="req">*</span></label>
                            <select name="interest" required>
                                <option value="">Select a loan product</option>
                                <option value="business">Business Loan</option>
                                <option value="personal">Personal Loan</option>
                                <option value="sme">SME Financing</option>
                                <option value="general">General Inquiry</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Message</label>
                            <textarea name="message" placeholder="Tell us about your needs..."></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Send Message</button>
                        <p class="form-note">By submitting, you agree to be contacted regarding your inquiry.</p>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="map-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Location</span>
                <h2>Find Us</h2>
                <p>Visit our branch in Mutomo, Kitui County.</p>
            </div>
            <div class="map-container">
                <i class="fas fa-map-marked-alt"></i>
                <p>Google Maps embed can be added here</p>
                <small>Mutomo Town, Kitui County, Kenya</small>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">FAQs</span>
                <h2>Frequently Asked Questions</h2>
                <p>Quick answers to common questions.</p>
            </div>

            <div class="faq-item active">
                <div class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                    What documents do I need to apply for a loan?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    You will need a valid National ID, proof of income or business registration, bank or M-Pesa statements for the last 3 months, and one or two guarantors depending on the loan amount.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                    How long does loan approval take?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Most personal and business loans are approved within 24 hours once all required documents are submitted. SME financing may take 1-3 business days.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                    Can I repay my loan via M-Pesa?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Yes! We support M-Pesa repayments for all loan products. You can also repay via bank transfer or cash at our branch.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                    Do I need collateral for a loan?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Personal loans and business loans under KSH 100,000 typically do not require collateral — guarantors are sufficient. Larger amounts may require collateral.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="this.parentElement.classList.toggle('active')">
                    What happens if I miss a repayment?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    We offer a grace period for missed payments. Contact us immediately if you anticipate difficulty — we are committed to working with you to restructure your plan.
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <h2>Ready to Apply for a Loan?</h2>
            <p>Visit our branch in Mutomo or contact us to start your application today.</p>
            <div class="hero-buttons" style="justify-content:center;">
                <a href="/our-loans" class="btn btn-primary">View Loan Products</a>
                <a href="tel:+254700000001" class="btn btn-outline"><i class="fas fa-phone"></i> Call Now</a>
            </div>
        </div>
    </section>

    @include('partials.public-footer')
</body>
</html>
