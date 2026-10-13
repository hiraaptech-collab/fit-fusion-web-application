@extends('layouts.public')

@section('metadata')
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMI Calculator | FitFusion – Premium Fitness & Wellness</title>
    <meta name="description" content="Calculate your Body Mass Index instantly with FitFusion's free BMI calculator and get personalized health recommendations and wellness tips.">
    <meta name="keywords" content="FitFusion, BMI Calculator, Body Mass Index, Health Assessment, Weight Range, BMI Check">
    <meta name="author" content="FitFusion">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://fitfusion.com/BMI.html">
    <!-- Open Graph -->
    <meta property="og:title" content="BMI Calculator | FitFusion – Premium Fitness & Wellness">
    <meta property="og:description" content="Calculate your Body Mass Index instantly with FitFusion's free BMI calculator and get personalized health recommendations.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://fitfusion.com/BMI.html">
    <meta property="og:site_name" content="FitFusion">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="BMI Calculator | FitFusion – Premium Fitness & Wellness">
    <meta name="twitter:description" content="Calculate your Body Mass Index instantly with FitFusion's free BMI calculator and get personalized health recommendations.">
@endsection

@section('content')
<!-- NAVBAR (Fetched from JSON) -->
<div id="my-navbar" role="navigation" aria-label="Main navigation"></div>

<!-- PAGE HERO -->
<section class="page-hero" id="bmi-hero" aria-label="BMI Calculator hero">
    <div class="container">
        <span class="section-tag" data-aos="fade-up"><i class="bi bi-calculator-fill"></i> Health Assessment</span>
        <h1 class="page-hero-title" data-aos="fade-up" data-aos-delay="100">
            BMI <span>Calculator</span>
        </h1>
        <p class="page-hero-text" data-aos="fade-up" data-aos-delay="200">
            Enter your height and weight to get an instant, accurate Body Mass Index reading
            with a personalized recommendation for your health journey.
        </p>
    </div>
</section>

<!-- BMI CALCULATOR -->
<section class="bmi-calculator-section" id="bmi-calculator" aria-label="BMI calculator form">
    <div class="container">
        <div class="bmi-grid">
            <div class="bmi-form-card" data-aos="fade-right">
                <h3><i class="bi bi-calculator-fill me-1"></i> Enter Your Details</h3>
                <form id="bmiForm" novalidate aria-label="BMI calculator form">
                    <div class="bmi-form-holder">
                        <div class="form-group">
                            <label for="bmiHeight">Height (cm)</label>
                            <input type="number" id="bmiHeight" name="height"
                                   placeholder="e.g. 175" min="100" max="250" inputmode="decimal"
                                   aria-label="Height in centimeters" aria-required="true">
                            <span class="error-feedback" id="bmiHeightError" role="alert"></span>
                        </div>
                        <div class="form-group">
                            <label for="bmiWeight">Weight (kg)</label>
                            <input type="number" id="bmiWeight" name="weight"
                                   placeholder="e.g. 70" min="30" max="300" inputmode="decimal"
                                   aria-label="Weight in kilograms" aria-required="true">
                            <span class="error-feedback" id="bmiWeightError" role="alert"></span>
                        </div>
                        <div class="hero-buttons">
                            <button type="submit" class="btn-primary-custom">
                                <i class="bi bi-calculator-fill"></i> Calculate BMI
                            </button>
                            <button type="button" id="resetBmi" class="btn-outline-custom">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
                <div class="bmi-helper mt-4">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    BMI = weight (kg) / height (m)². It's a quick screening tool, not a complete health diagnosis.
                </div>
            </div>

            <div class="bmi-result-card" data-aos="fade-left">
                <h3>Your <span class="text-gold">BMI Result</span></h3>
                <div id="bmiResult" class="bmi-result-hidden" role="region" aria-label="BMI calculation results">
                    <span class="bmi-value" id="bmiValue">0</span>
                    <div>
                        <span class="bmi-category-label bmi-normal" id="bmiCategory">Normal Weight</span>
                    </div>
                    <p class="bmi-range" id="bmiRange">18.5 – 24.9</p>
                    <div class="bmi-progress">
                        <div class="bmi-progress-bar progress-bar bmi-normal" id="bmiProgressBar" style="width: 0%"></div>
                    </div>
                    <p class="bmi-range">
                        Healthiest weight for your height:
                        <strong id="healthyRange" class="text-gold">0 kg</strong>
                    </p>
                    <div class="bmi-recommendation" id="bmiRecommendation">
                        <i class="bi bi-lightbulb-fill"></i>
                        <p><strong>Recommendation:</strong> Your personalized advice will appear here after calculation.</p>
                    </div>
                </div>
                @if(auth()->check() && auth()->user()->role==='client')
                <div id="bmiSavePanel" class="bmi-helper mt-4" hidden>
                    <label><input type="checkbox" id="bmiSaveConsent"> I allow this height, weight and BMI to be saved to my fitness progress.</label>
                    <button type="button" id="bmiSave" class="btn-outline-custom mt-3" data-endpoint="{{ route('client.progress.store') }}" data-date="{{ \App\Support\GymClock::today()->toDateString() }}">Save to my progress</button>
                    <p id="bmiSaveStatus" role="status"></p>
                </div>
                @else
                <p class="bmi-helper mt-4"><a href="{{ route('login') }}">Log in as a Client</a> to save your fitness progress.</p>
                @endif
                <div id="bmiPlaceholder">
                    <i class="bi bi-heart-pulse-fill" style="font-size: 48px; color: var(--primary);"></i>
                    <p style="color: var(--muted); margin-top: 16px;">
                        Enter your height and weight on the left, then press
                        <strong class="text-gold">Calculate BMI</strong> to see your personalized result.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- BMI CATEGORIES -->
<section class="about-values-section" id="bmi-categories" aria-label="BMI category information">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag"><i class="bi bi-bar-chart-fill"></i> Understand Your Results</span>
            <h2 class="section-title">BMI <span>Categories</span></h2>
            <p class="section-text mx-auto">
                BMI is a helpful starting point for understanding your body composition.
                Here's what each range means.
            </p>
        </div>
        <div class="about-values-grid">
            <div class="bmi-category-card" data-aos="fade-up">
                <span class="range-badge bmi-underweight">Below 18.5</span>
                <h5>Underweight</h5>
                <p>You may need more calories and nutrient-dense foods to reach a healthy weight.</p>
            </div>
            <div class="bmi-category-card" data-aos="fade-up" data-aos-delay="100">
                <span class="range-badge bmi-normal">18.5 – 24.9</span>
                <h5>Normal Weight</h5>
                <p>You're in a healthy range — maintain balance with good nutrition and exercise.</p>
            </div>
            <div class="bmi-category-card" data-aos="fade-up" data-aos-delay="200">
                <span class="range-badge bmi-overweight">25 – 29.9</span>
                <h5>Overweight</h5>
                <p>A balanced diet and regular activity can help you move toward a healthier range.</p>
            </div>
            <div class="bmi-category-card" data-aos="fade-up" data-aos-delay="300">
                <span class="range-badge bmi-obese">30 and above</span>
                <h5>Obese</h5>
                <p>Structured guidance and professional support are recommended to improve health.</p>
            </div>
        </div>
    </div>
</section>

<!-- HEALTH TIPS -->
<section class="services-preview-section" id="bmi-tips" aria-label="Health and wellness tips">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag"><i class="bi bi-lightbulb-fill"></i> Wellness Tips</span>
            <h2 class="section-title">Beyond The <span>Number</span></h2>
            <p class="section-text mx-auto">
                Your BMI is just one piece of the puzzle. Use these habits to support overall wellness.
            </p>
        </div>
        <div class="features-grid">
            <div class="feature-card" data-aos="fade-up">
                <div class="feature-icon"><i class="bi bi-egg-fried"></i></div>
                <h4>Balanced Nutrition</h4>
                <p>Prioritize whole foods, lean protein, and plenty of fruits and vegetables.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="feature-icon"><i class="bi bi-activity"></i></div>
                <h4>Stay Active</h4>
                <p>Aim for at least 150 minutes of moderate exercise every week.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon"><i class="bi bi-droplet-fill"></i></div>
                <h4>Stay Hydrated</h4>
                <p>Drink 8+ glasses of water daily to support energy and recovery.</p>
            </div>
            <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-icon"><i class="bi bi-moon-stars-fill"></i></div>
                <h4>Sleep Well</h4>
                <p>Get 7–9 hours of quality sleep to support weight and mood regulation.</p>
            </div>
        </div>
    </div>
</section>

<!-- FINAL CTA -->
<section class="final-cta-section" id="bmi-cta" aria-label="Call to action">
    <div class="container">
        <div data-aos="fade-up">
            <h2>Ready to Take Action?</h2>
            <p>Pair your BMI result with expert diet plans and workout guides tailored to your goals.</p>
            <div class="final-cta-buttons">
                <a href="dietplans.html" class="btn-cta-dark"><i class="bi bi-egg-fried"></i> Diet Plans</a>
                <a href="workouts.html" class="btn-cta-light"><i class="bi bi-dumbbell-fill"></i> Workouts</a>
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
<script src="{{ asset('javascript/bmi.js') }}"></script>
@endsection

