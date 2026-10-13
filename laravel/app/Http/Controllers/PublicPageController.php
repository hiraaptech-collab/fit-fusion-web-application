<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

// The public pages still use the existing vanilla JavaScript and JSON data.
class PublicPageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function dietPlans(): View
    {
        return view('pages.dietplans');
    }

    public function workouts(): View
    {
        return view('pages.workouts');
    }

    public function bmi(): View
    {
        return view('pages.bmi');
    }

    public function fusionAI(): View
    {
        return view('pages.fusion-ai');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }
}
