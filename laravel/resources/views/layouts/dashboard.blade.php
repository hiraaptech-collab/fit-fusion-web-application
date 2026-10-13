<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><meta name="robots" content="noindex,nofollow">
<title>@yield('title', 'Dashboard') | FitFusion</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="{{ asset('assets/style.css') }}" rel="stylesheet"><link href="{{ asset('assets/dashboard.css') }}" rel="stylesheet">
@yield('head')
</head><body class="dash-body">
@php($currentUser = auth()->user())
<div class="dash-overlay" id="dashOverlay" hidden></div>
<aside class="dash-sidebar" id="dashSidebar" aria-label="Dashboard navigation">
<a href="{{ route('dashboard') }}" class="dash-brand"><i class="bi bi-heart-pulse-fill"></i><strong>Fit<span>Fusion</span></strong></a>
<p class="dash-role">{{ ucfirst($currentUser->role) }} panel <button id="dashClose" type="button" aria-label="Close navigation">&times;</button></p>
<nav class="dash-nav">
<a class="dash-nav-link {{ request()->routeIs('*.dashboard') ? 'selected' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid" aria-hidden="true"></i><span>Overview</span></a>
@if($currentUser->role === 'client')
<details class="dash-nav-section" open>
<summary><i class="bi bi-person-heart" aria-hidden="true"></i><span>My fitness</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
@foreach([['My profile','client.account-profile','person'],['Membership','client.memberships','card-checklist'],['Fitness plans','client.plans','activity'],['Progress','client.progress','graph-up'],['Payments','client.payments','receipt'],['My feedback','client.reviews','star']] as [$label,$route,$icon])
<a class="dash-nav-link dash-nav-child {{ request()->routeIs($route) ? 'selected' : '' }}" href="{{ route($route) }}"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span></a>
@endforeach
</details>
<details class="dash-nav-section" @if(request()->routeIs('client.ai','bmi','gym.messages','gym.notifications')) open @endif>
<summary><i class="bi bi-stars" aria-hidden="true"></i><span>Tools &amp; support</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
<a class="dash-nav-link dash-nav-child {{ request()->routeIs('client.ai') ? 'selected' : '' }}" href="{{ route('client.ai') }}"><i class="bi bi-robot" aria-hidden="true"></i><span>Fusion-AI history</span></a>
<a class="dash-nav-link dash-nav-child {{ request()->routeIs('bmi') ? 'selected' : '' }}" href="{{ route('bmi') }}"><i class="bi bi-calculator" aria-hidden="true"></i><span>BMI calculator</span></a>
@if($currentUser->role === 'client')<a class="dash-nav-link dash-nav-child {{ request()->routeIs('gym.messages') ? 'selected' : '' }}" href="{{ route('gym.messages') }}"><i class="bi bi-chat-dots" aria-hidden="true"></i><span>Messages</span></a>@endif
</details>
@else
<details class="dash-nav-section" open>
<summary><i class="bi bi-people" aria-hidden="true"></i><span>People &amp; staff</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
@if($currentUser->role === 'owner')<a class="dash-nav-link dash-nav-child {{ request()->routeIs('gym.accounts') && request()->route('accountType') === 'managers' ? 'selected' : '' }}" href="{{ route('gym.accounts','managers') }}"><i class="bi bi-person-badge" aria-hidden="true"></i><span>Managers</span></a>@endif
@can('clients.manage')<a class="dash-nav-link dash-nav-child {{ request()->routeIs('gym.accounts') && request()->route('accountType') === 'clients' ? 'selected' : '' }}" href="{{ route('gym.accounts','clients') }}"><i class="bi bi-people" aria-hidden="true"></i><span>Clients</span></a>@endcan
@can('trainers.manage')<a class="dash-nav-link dash-nav-child {{ request()->routeIs('gym.staff') && request()->route('staffType') === 'trainers' ? 'selected' : '' }}" href="{{ route('gym.staff','trainers') }}"><i class="bi bi-person-arms-up" aria-hidden="true"></i><span>Trainers</span></a>@endcan
@can('workers.manage')<a class="dash-nav-link dash-nav-child {{ request()->routeIs('gym.staff') && request()->route('staffType') === 'workers' ? 'selected' : '' }}" href="{{ route('gym.staff','workers') }}"><i class="bi bi-person-gear" aria-hidden="true"></i><span>Workers</span></a>@endcan
@can('staff.schedules')<a class="dash-nav-link dash-nav-child" href="{{ route('gym.staff-operations','schedules') }}"><i class="bi bi-calendar" aria-hidden="true"></i><span>Schedules</span></a>@endcan
@can('staff.attendance')<a class="dash-nav-link dash-nav-child" href="{{ route('gym.staff-operations','attendance') }}"><i class="bi bi-calendar-check" aria-hidden="true"></i><span>Attendance</span></a>@endcan
</details>
<details class="dash-nav-section" @if(request()->routeIs('gym.memberships','gym.membership-plans','gym.finance','gym.fitness-plans','gym.reports','gym.reviews')) open @endif>
<summary><i class="bi bi-briefcase" aria-hidden="true"></i><span>Operations</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
@can('memberships.manage')<a class="dash-nav-link dash-nav-child" href="{{ route('gym.memberships') }}"><i class="bi bi-card-checklist" aria-hidden="true"></i><span>Memberships</span></a><a class="dash-nav-link dash-nav-child" href="{{ route('gym.membership-plans') }}"><i class="bi bi-tags" aria-hidden="true"></i><span>Membership plans</span></a>@endcan
@if($currentUser->hasGymPermission('finance.view') || $currentUser->hasGymPermission('finance.manage'))<a class="dash-nav-link dash-nav-child" href="{{ route('gym.finance') }}"><i class="bi bi-wallet2" aria-hidden="true"></i><span>Finance</span></a>@endif
@can('workouts.manage')<a class="dash-nav-link dash-nav-child" href="{{ route('gym.fitness-plans','workouts') }}"><i class="bi bi-activity" aria-hidden="true"></i><span>Workout plans</span></a>@endcan
@can('diets.manage')<a class="dash-nav-link dash-nav-child" href="{{ route('gym.fitness-plans','diets') }}"><i class="bi bi-egg-fried" aria-hidden="true"></i><span>Diet plans</span></a>@endcan
@can('reports.view')<a class="dash-nav-link dash-nav-child" href="{{ route('gym.reports') }}"><i class="bi bi-bar-chart" aria-hidden="true"></i><span>Reports</span></a>@endcan
@can('feedback.manage')<a class="dash-nav-link dash-nav-child" href="{{ route('gym.reviews') }}"><i class="bi bi-chat-square-heart" aria-hidden="true"></i><span>Feedback</span></a>@endcan
</details>
@endif
<details class="dash-nav-section" open>
<summary><i class="bi bi-chat-square-text" aria-hidden="true"></i><span>Communication</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
@if($currentUser->role === 'client' || $currentUser->hasGymPermission('messages.manage'))<a class="dash-nav-link dash-nav-child {{ request()->routeIs('gym.messages') ? 'selected' : '' }}" href="{{ route('gym.messages') }}"><i class="bi bi-chat-dots" aria-hidden="true"></i><span>Messages &amp; inquiries</span></a>@endif
<a class="dash-nav-link dash-nav-child {{ request()->routeIs('gym.notifications') ? 'selected' : '' }}" href="{{ route('gym.notifications') }}"><i class="bi bi-bell" aria-hidden="true"></i><span>Notifications</span><span class="dash-badge">{{ $currentUser->unreadNotifications()->count() }}</span></a>
</details>
@if($currentUser->role === 'owner')
<details class="dash-nav-section" @if(request()->routeIs('gym.ai-usage','gym.settings','gym.audit')) open @endif>
<summary><i class="bi bi-shield-check" aria-hidden="true"></i><span>Administration</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
<a class="dash-nav-link dash-nav-child" href="{{ route('gym.ai-usage') }}"><i class="bi bi-robot" aria-hidden="true"></i><span>AI usage</span></a>
<a class="dash-nav-link dash-nav-child" href="{{ route('gym.settings') }}"><i class="bi bi-gear" aria-hidden="true"></i><span>Gym settings</span></a>
<a class="dash-nav-link dash-nav-child" href="{{ route('gym.audit') }}"><i class="bi bi-journal-check" aria-hidden="true"></i><span>Audit log</span></a>
</details>
@endif
<a class="dash-nav-link dash-nav-public" href="{{ route('home') }}"><i class="bi bi-arrow-up-right-square" aria-hidden="true"></i><span>Public website</span></a>
</nav>
</aside>
<div class="dash-workspace">
<header class="dash-topbar"><button type="button" id="dashToggle" aria-label="Open navigation" aria-controls="dashSidebar" aria-expanded="false"><i class="bi bi-list"></i></button><div><strong>FitFusion</strong><span>Your gym, connected.</span></div><div class="dash-user"><span>{{ $currentUser->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="dash-button secondary" type="submit">Logout</button></form></div></header>
<main class="dash-page"><div class="dash-page-heading"><div><p class="dash-eyebrow">{{ ucfirst($currentUser->role) }} workspace</p><h1>@yield('title','Dashboard')</h1><p>@yield('description','Manage your gym with confidence.')</p></div></div>
@if(session('status'))<div class="dash-alert" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="dash-alert error" role="alert"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main><footer class="dash-footer">FitFusion &middot; One gym. Stronger together.</footer></div>
<dialog id="dashConfirm" class="dash-dialog"><h2>Confirm this action</h2><p id="dashConfirmText"></p><div class="dash-actions"><button type="button" class="dash-button secondary" id="dashCancel">Cancel</button><button type="button" class="dash-button" id="dashProceed">Confirm</button></div></dialog>
<script src="{{ asset('javascript/dashboard.js') }}"></script>@yield('scripts')
</body></html>



