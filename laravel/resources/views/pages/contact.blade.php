@extends('layouts.public')

@section('metadata')
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | FitFusion – Premium Fitness & Wellness</title>
    <meta name="description" content="Get in touch with FitFusion. We're here to help with your fitness, diet, and wellness questions. Reach us by phone, email, or message.">
    <meta name="keywords" content="FitFusion, Contact FitFusion, Fitness Support, Wellness Inquiries, Customer Support, Gym Contact">
    <meta name="author" content="FitFusion">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://fitfusion.com/contact.html">
    <!-- Open Graph -->
    <meta property="og:title" content="Contact Us | FitFusion – Premium Fitness & Wellness">
    <meta property="og:description" content="Get in touch with FitFusion. We're here to help with your fitness, diet, and wellness questions.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://fitfusion.com/contact.html">
    <meta property="og:site_name" content="FitFusion">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Contact Us | FitFusion – Premium Fitness & Wellness">
    <meta name="twitter:description" content="Get in touch with FitFusion. We're here to help with your fitness, diet, and wellness questions.">
@endsection

@section('content')
<!-- NAVBAR (Fetched from JSON) -->
<div id="my-navbar" role="navigation" aria-label="Main navigation"></div>

<!-- PAGE HERO -->
<section class="page-hero" id="contact-hero" aria-label="Contact FitFusion hero">
    <div class="container">
        <span class="section-tag" data-aos="fade-up"><i class="bi bi-chat-dots-fill"></i> We're Here For You</span>
        <h1 class="page-hero-title" data-aos="fade-up" data-aos-delay="100">
            Contact <span>FitFusion</span>
        </h1>
        <p class="page-hero-text" data-aos="fade-up" data-aos-delay="200">
            Questions, feedback, or partnership ideas? Our premium wellness team is ready to help you
            start — or continue — your transformation journey.
        </p>
    </div>
</section>

<!-- CONTACT INFO GRID -->
<section class="contact-section" id="contact-grid" aria-label="Contact information">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info-column" data-aos="fade-right">
                <span class="section-tag"><i class="bi bi-geo-alt-fill"></i> Get In Touch</span>
                <h2 class="section-title">Let's Talk <span>Health</span></h2>
                <p class="section-text">
                    Reach us through any channel below. Our team typically responds within
                    one business day.
                </p>
                <div class="contact-info-cards">
                    <div class="contact-info-card">
                        <div class="contact-info-icon"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <h5>Phone / WhatsApp</h5>
                            <a href="https://wa.me/923103701543" target="_blank" rel="noopener">+92 310-3701543</a>
                        </div>
                    </div>
                    <div class="contact-info-card">
                        <div class="contact-info-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <h5>Email</h5>
                            <a href="mailto:hiraaptech@gmail.com">hiraaptech@gmail.com</a>
                        </div>
                    </div>
                    <div class="contact-info-card">
                        <div class="contact-info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <h5>Visit Us</h5>
                            <p>Pakistan Homes Society, Block 9-A,<br>Gulistan-e-Johar, Karachi, Pakistan</p>
                        </div>
                    </div>
                    <div class="contact-info-card">
                        <div class="contact-info-icon"><i class="bi bi-clock-fill"></i></div>
                        <div>
                            <h5>Response Time</h5>
                            <p>Mon – Sat: 9:00 AM – 9:00 PM PKT<br>Usually within 24 hours</p>
                        </div>
                    </div>
                </div>
                <div class="contact-social">
                    <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="FitFusion on Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="FitFusion on Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://twitter.com" target="_blank" rel="noopener" aria-label="FitFusion on X"><i class="bi bi-twitter-x"></i></a>
                    <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="FitFusion on YouTube"><i class="bi bi-youtube"></i></a>
                </div>
            </div>

            <div class="contact-form-card" data-aos="fade-left">
                <h3><i class="bi bi-send-fill me-1"></i> Send Us a Message</h3>
                <form id="contactForm" novalidate aria-label="Contact form">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="fullName" placeholder="Enter your full name"
                               aria-label="Your full name" aria-required="true">
                        <span class="error-feedback" id="fullNameError" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="you@example.com"
                               aria-label="Your email address" aria-required="true">
                        <span class="error-feedback" id="emailError" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="+92 XXX XXXXXXX"
                               aria-label="Your phone number" aria-required="true">
                        <span class="error-feedback" id="phoneError" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" placeholder="How can we help?"
                               aria-label="Message subject" aria-required="true">
                        <span class="error-feedback" id="subjectError" role="alert"></span>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" placeholder="Write your message here..."
                                  aria-label="Your message" aria-required="true"></textarea>
                        <span class="error-feedback" id="messageError" role="alert"></span>
                    </div>
                    <div id="formSuccess" class="form-success" role="status" style="display: none;">
                        <i class="bi bi-check-circle-fill"></i>
                        <p><strong>Thank you!</strong> Your message has been received. We'll get back to you soon.</p>
                    </div>
                    <button type="submit" class="btn-primary-custom w-100">
                        <i class="bi bi-send-fill"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- MAP / LOCATION -->
<section class="about-intro-section" id="contact-map" aria-label="Our location">
    <div class="container">
        <div class="location-card" data-aos="fade-up">
            <div class="text-center mb-4">
                <span class="section-tag"><i class="bi bi-map-fill"></i> Find Us</span>
                <h2 class="section-title mb-0">Our <span>Location</span></h2>
            </div>
            <div class="map-embed">
                <iframe
                    src="https://www.google.com/maps?q=Gulistan-e-Johar,Karachi,Pakistan&output=embed"
                    width="100%" height="420" style="border:0;" allowfullscreen=""
                    loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    title="FitFusion location map"
                    aria-label="Map showing FitFusion location"></iframe>
            </div>
        </div>
    </div>
</section>

<!-- FINAL CTA -->
<section class="final-cta-section" id="contact-cta" aria-label="Call to action">
    <div class="container">
        <div data-aos="fade-up">
            <h2>Prefer a Quick Answer?</h2>
            <p>Try Fusion-AI — our fitness assistant that answers instantly, any time of day.</p>
            <div class="final-cta-buttons">
                <a href="fusion-ai.html" class="btn-cta-dark"><i class="bi bi-robot"></i> Chat with Fusion-AI</a>
                <a href="BMI.html" class="btn-cta-light"><i class="bi bi-calculator-fill"></i> Check Your BMI</a>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER (Fetched from JSON) -->
<div id="my-footer" role="contentinfo" aria-label="Site footer"></div>

<!-- Back to Top -->
<button class="back-to-top" id="backToTop" aria-label="Back to top">
    <i class="bi bi-arrow-up"></i>
</button>
@endsection

@section('page-scripts')
<script src="{{ asset('javascript/contact.js') }}"></script>
@endsection
