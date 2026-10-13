@extends('layouts.public')

@section('metadata')
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workouts | FitFusion – Premium Fitness & Wellness</title>
    <meta name="description" content="Explore expert-guided workout guides for strength training, cardio, flexibility, and full-body fitness. Find the perfect routine for every goal.">
    <meta name="keywords" content="FitFusion, Workouts, Exercise Guides, Strength Training, Cardio Workouts, Gym Routine, Fitness Plans">
    <meta name="author" content="FitFusion">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://fitfusion.com/workouts.html">
    <!-- Open Graph -->
    <meta property="og:title" content="Workouts | FitFusion – Premium Fitness & Wellness">
    <meta property="og:description" content="Explore expert-guided workout guides for strength training, cardio, flexibility, and full-body fitness.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://fitfusion.com/workouts.html">
    <meta property="og:site_name" content="FitFusion">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Workouts | FitFusion – Premium Fitness & Wellness">
    <meta name="twitter:description" content="Explore expert-guided workout guides for strength, cardio, flexibility, and full-body fitness.">
@endsection

@section('content')
<!-- NAVBAR (Fetched from JSON) -->
<div id="my-navbar" role="navigation" aria-label="Main navigation"></div>

<!-- PAGE HERO -->
<section class="page-hero" id="workout-hero" aria-label="Workouts hero">
    <div class="container">
        <span class="section-tag" data-aos="fade-up"><i class="bi bi-dumbbell-fill"></i> Training Library</span>
        <h1 class="page-hero-title" data-aos="fade-up" data-aos-delay="100">
            Workout <span>Guides</span>
        </h1>
        <p class="page-hero-text" data-aos="fade-up" data-aos-delay="200">
            Expert-crafted exercises across every category — from strength and cardio to flexibility.
            Filter the library to find the perfect routine for your goals.
        </p>
    </div>
</section>

<!-- WORKOUT FILTERS + CARDS -->
<section class="workouts-page" id="workout-library" aria-label="Workout library">
    <div class="container">
<div class="provided-visuals" aria-label="Fitness inspiration"><figure class="provided-visual" data-aos="fade-up"><img src="assets/images/provided/8433d93487ca721ae14c0027e2d8d92d.jpg" alt="Athlete performing an incline dumbbell press" width="736" height="1110" loading="lazy" decoding="async"><figcaption>Strength training</figcaption></figure><figure class="provided-visual" data-aos="fade-up"><img src="assets/images/provided/90a62bff2f1a14d789515d3c893f5199.jpg" alt="Athlete performing a lat pulldown" width="736" height="736" loading="lazy" decoding="async"><figcaption>Controlled movements</figcaption></figure><figure class="provided-visual" data-aos="fade-up"><img src="assets/images/provided/d5c0f0f69b06f97a4e23b0ee3e9a1378.jpg" alt="Runner exercising on a treadmill" width="474" height="711" loading="lazy" decoding="async"><figcaption>Cardio conditioning</figcaption></figure></div>
        <div class="workout-filters" id="workoutFilterNav" role="tablist" aria-label="Filter workouts by category"></div>
        <div id="workoutsContainer" aria-live="polite"></div>
    </div>
</section>

<!-- TRAINING LEVELS -->
<section class="training-levels-section" id="training-levels" aria-label="Training levels guide">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag"><i class="bi bi-bar-chart-line-fill"></i> Find Your Level</span>
            <h2 class="section-title">Choose The Right <span>Intensity</span></h2>
            <p class="section-text mx-auto">
                Not sure where to start? Pick a difficulty that matches your experience.
            </p>
        </div>
        <div class="about-values-grid">
            <div class="value-card" data-aos="fade-up">
                <div class="value-icon"><i class="bi bi-person-check-fill"></i></div>
                <h5>Beginner</h5>
                <p>Fundamental movements and lighter weights to build a solid, safe foundation.</p>
            </div>
            <div class="value-card" data-aos="fade-up" data-aos-delay="100">
                <div class="value-icon"><i class="bi bi-person-raised-hand"></i></div>
                <h5>Intermediate</h5>
                <p>Progressive overload and compound lifts to accelerate your gains.</p>
            </div>
            <div class="value-card" data-aos="fade-up" data-aos-delay="200">
                <div class="value-icon"><i class="bi bi-person-standing"></i></div>
                <h5>Advanced</h5>
                <p>High-intensity protocols and fine-tuned splits for elite performance.</p>
            </div>
            <div class="value-card" data-aos="fade-up" data-aos-delay="300">
                <div class="value-icon"><i class="bi bi-patch-check-fill"></i></div>
                <h5>Pro Tips</h5>
                <p>Recovery, form cues, and nutrition tips to maximize every session.</p>
            </div>
        </div>
    </div>
</section>

<!-- FINAL CTA -->
<section class="final-cta-section" id="workout-cta" aria-label="Call to action">
    <div class="container">
        <div data-aos="fade-up">
            <h2>Want a Custom Routine?</h2>
            <p>Tell Fusion-AI your goal and fitness level — get a personalized plan instantly.</p>
            <div class="final-cta-buttons">
                <a href="fusion-ai.html" class="btn-cta-dark"><i class="bi bi-robot"></i> Ask Fusion-AI</a>
                <a href="dietplans.html" class="btn-cta-light"><i class="bi bi-egg-fried"></i> Diet Plans</a>
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
<script src="{{ asset('javascript/workouts.js') }}"></script>
@endsection
