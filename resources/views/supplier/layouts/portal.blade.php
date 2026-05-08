<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Supplier Portal') — Procurement</title>
    <meta name="description" content="@yield('description', 'Official supplier and vendor portal for procurement tenders and bid submissions.')">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy:   #362A72;
            --navy2:  #2a2058;
            --cyan:   #6C5CE7;
            --cyan2:  #5a4bd1;
            --silver: #f4f6f8;
            --border: #e2e8f0;
            --text:   #1a2332;
            --muted:  #6b7a90;
            --success:#0d7a4e;
            --danger: #c0392b;
            --warn:   #b45309;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--silver);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
        }

        /* ── Header ─────────────────────────────────── */
        .sp-header {
            background: var(--navy);
            color: #fff;
            position: sticky; top: 0; z-index: 50;
            box-shadow: 0 2px 16px rgba(0,0,0,0.18);
        }
        .sp-header-inner {
            max-width: 1200px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 1.5rem; height: 64px;
        }
        .sp-logo {
            display: flex; align-items: center; gap: .75rem;
            text-decoration: none; color: #fff;
        }
        .sp-logo-mark {
            width: 38px; height: 38px; background: var(--cyan);
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1.1rem; letter-spacing: -.5px;
        }
        .sp-logo-text { font-size: .95rem; font-weight: 600; line-height: 1.2; }
        .sp-logo-sub  { font-size: .7rem; font-weight: 400; opacity: .7; letter-spacing: .04em; text-transform: uppercase; }

        .sp-nav { display: flex; align-items: center; gap: .25rem; }
        .sp-nav a, .sp-nav button {
            color: rgba(255,255,255,.85); text-decoration: none;
            padding: .45rem .85rem; border-radius: 6px; font-size: .875rem; font-weight: 500;
            transition: all .15s; border: none; background: transparent; cursor: pointer;
        }
        .sp-nav a:hover, .sp-nav button:hover { background: rgba(255,255,255,.12); color: #fff; }
        .sp-nav a.active { background: var(--cyan); color: #fff; }
        .sp-nav .sp-btn-outline {
            border: 1.5px solid rgba(255,255,255,.35);
            padding: .4rem .9rem;
        }
        .sp-nav .sp-btn-outline:hover { border-color: #fff; background: rgba(255,255,255,.1); }

        /* ── Strip ──────────────────────────────────── */
        .sp-strip {
            background: var(--cyan); height: 4px;
        }

        /* ── Page wrapper ───────────────────────────── */
        .sp-page { max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem; }

        /* ── Page header ────────────────────────────── */
        .sp-page-header { margin-bottom: 2rem; }
        .sp-page-header h1 { font-size: 1.6rem; font-weight: 700; color: var(--navy); }
        .sp-page-header p  { color: var(--muted); margin-top: .35rem; font-size: .9rem; }
        .sp-breadcrumb { display: flex; gap: .4rem; font-size: .8rem; color: var(--muted); margin-bottom: .75rem; align-items: center; }
        .sp-breadcrumb a { color: var(--cyan2); text-decoration: none; }
        .sp-breadcrumb a:hover { text-decoration: underline; }
        .sp-breadcrumb span { opacity: .5; }

        /* ── Cards ──────────────────────────────────── */
        .sp-card {
            background: #fff; border: 1px solid var(--border);
            border-radius: 12px; padding: 1.75rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .sp-card + .sp-card { margin-top: 1.25rem; }
        .sp-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; }
        .sp-card-title  { font-size: 1rem; font-weight: 700; color: var(--navy); }
        .sp-card-sub    { font-size: .8rem; color: var(--muted); margin-top: .2rem; }

        /* ── Grid ───────────────────────────────────── */
        .sp-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem; }
        .sp-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; }
        .sp-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; }
        @media(max-width:900px)  { .sp-grid-3, .sp-grid-4 { grid-template-columns: repeat(2,1fr); } }
        @media(max-width:600px)  { .sp-grid-2, .sp-grid-3, .sp-grid-4 { grid-template-columns: 1fr; } }

        /* ── Tender card ────────────────────────────── */
        .tender-card {
            background: #fff; border: 1px solid var(--border); border-radius: 12px;
            overflow: hidden; transition: box-shadow .2s, transform .2s;
            display: flex; flex-direction: column;
        }
        .tender-card:hover { box-shadow: 0 8px 24px rgba(0,51,102,.1); transform: translateY(-2px); }
        .tender-card-top { background: var(--navy); padding: 1.25rem 1.5rem; }
        .tender-card-num { font-size: .8rem; color: var(--cyan); font-weight: 600; letter-spacing: .05em; }
        .tender-card-title { color: #fff; font-size: 1rem; font-weight: 600; margin-top: .25rem; line-height: 1.35; }
        .tender-card-body { padding: 1.25rem 1.5rem; flex: 1; }
        .tender-card-meta { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .tender-meta-item { display: flex; align-items: center; gap: .3rem; font-size: .8rem; color: var(--muted); }
        .tender-meta-icon { width: 14px; height: 14px; flex-shrink: 0; }
        .tender-card-footer { padding: .75rem 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .tender-deadline { font-size: .8rem; color: var(--muted); }
        .tender-deadline strong { color: var(--danger); }

        /* ── Badges ─────────────────────────────────── */
        .badge { display: inline-flex; align-items: center; padding: .2rem .6rem; border-radius: 20px; font-size: .72rem; font-weight: 600; letter-spacing: .02em; text-transform: uppercase; }
        .badge-cyan    { background: #e0f4fc; color: #0077aa; }
        .badge-navy    { background: #e8eef5; color: var(--navy); }
        .badge-success { background: #dcf5ea; color: var(--success); }
        .badge-warn    { background: #fef3c7; color: var(--warn); }
        .badge-danger  { background: #fde8e6; color: var(--danger); }
        .badge-gray    { background: #f1f3f6; color: #64748b; }

        /* ── Buttons ─────────────────────────────────── */
        .sp-btn {
            display: inline-flex; align-items: center; gap: .4rem;
            padding: .55rem 1.25rem; border-radius: 8px; border: none;
            font-size: .875rem; font-weight: 600; cursor: pointer;
            text-decoration: none; transition: all .15s; line-height: 1;
        }
        .sp-btn-primary   { background: var(--cyan); color: #fff; }
        .sp-btn-primary:hover { background: var(--cyan2); }
        .sp-btn-navy      { background: var(--navy); color: #fff; }
        .sp-btn-navy:hover { background: var(--navy2); }
        .sp-btn-outline   { background: transparent; border: 1.5px solid var(--border); color: var(--text); }
        .sp-btn-outline:hover { border-color: var(--cyan); color: var(--cyan2); }
        .sp-btn-danger    { background: var(--danger); color: #fff; }
        .sp-btn-sm        { padding: .35rem .85rem; font-size: .8rem; }
        .sp-btn-lg        { padding: .75rem 1.75rem; font-size: 1rem; }

        /* ── Forms ───────────────────────────────────── */
        .sp-form-group { margin-bottom: 1.25rem; }
        .sp-form-group label { display: block; font-size: .8rem; font-weight: 600; color: var(--navy); margin-bottom: .4rem; letter-spacing: .02em; text-transform: uppercase; }
        .sp-form-group label .req { color: var(--danger); margin-left: 2px; }
        .sp-input, .sp-select, .sp-textarea {
            width: 100%; padding: .625rem .875rem; border: 1.5px solid var(--border);
            border-radius: 8px; font-size: .9rem; font-family: inherit;
            background: #fff; color: var(--text); transition: border-color .15s, box-shadow .15s;
            appearance: none;
        }
        .sp-input:focus, .sp-select:focus, .sp-textarea:focus {
            outline: none; border-color: var(--cyan); box-shadow: 0 0 0 3px rgba(0,163,224,.15);
        }
        .sp-input.error, .sp-select.error, .sp-textarea.error { border-color: var(--danger); }
        .sp-error-msg { font-size: .78rem; color: var(--danger); margin-top: .3rem; }
        .sp-hint { font-size: .78rem; color: var(--muted); margin-top: .3rem; }
        .sp-textarea { resize: vertical; min-height: 100px; }
        .sp-select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7a90' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right .75rem center; padding-right: 2.5rem; }

        /* Checkbox */
        .sp-checkbox-group { display: flex; align-items: flex-start; gap: .6rem; }
        .sp-checkbox-group input[type=checkbox] { width: 16px; height: 16px; margin-top: 2px; accent-color: var(--cyan); flex-shrink: 0; }
        .sp-checkbox-group label { font-size: .875rem; font-weight: 400; color: var(--text); text-transform: none; letter-spacing: 0; }

        /* Section title in forms */
        .sp-section-label { font-size: .7rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); padding-bottom: .5rem; border-bottom: 1px solid var(--border); margin-bottom: 1rem; margin-top: 1.5rem; }

        /* ── Alerts ──────────────────────────────────── */
        .sp-alert { padding: .875rem 1.125rem; border-radius: 8px; font-size: .875rem; display: flex; gap: .6rem; align-items: flex-start; margin-bottom: 1.25rem; }
        .sp-alert-success { background: #dcf5ea; border: 1px solid #a7d9bc; color: #0d5c38; }
        .sp-alert-error   { background: #fde8e6; border: 1px solid #f5b7b1; color: #8b1a1a; }
        .sp-alert-info    { background: #e0f4fc; border: 1px solid #9cd8f0; color: #005580; }
        .sp-alert-warn    { background: #fef3c7; border: 1px solid #fcd34d; color: #7c5a00; }

        /* ── Table ───────────────────────────────────── */
        .sp-table-wrap { overflow-x: auto; }
        .sp-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .sp-table th { background: #f8fafc; padding: .75rem 1rem; text-align: left; font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); border-bottom: 2px solid var(--border); white-space: nowrap; }
        .sp-table td { padding: .875rem 1rem; border-bottom: 1px solid var(--border); color: var(--text); vertical-align: middle; }
        .sp-table tr:last-child td { border-bottom: none; }
        .sp-table tr:hover td { background: #f8fafc; }

        /* ── Stats ───────────────────────────────────── */
        .sp-stat { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem 1.5rem; border-left: 4px solid var(--cyan); }
        .sp-stat-label { font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        .sp-stat-value { font-size: 2rem; font-weight: 800; color: var(--navy); margin-top: .2rem; line-height: 1; }
        .sp-stat-sub   { font-size: .78rem; color: var(--muted); margin-top: .3rem; }

        /* ── Divider ─────────────────────────────────── */
        .sp-divider { border: none; border-top: 1px solid var(--border); margin: 1.5rem 0; }

        /* ── Footer ──────────────────────────────────── */
        .sp-footer { background: var(--navy2); color: rgba(255,255,255,.6); text-align: center; padding: 1.5rem; font-size: .78rem; margin-top: auto; }
        .sp-footer a { color: var(--cyan); text-decoration: none; }

        /* ── Pagination ──────────────────────────────── */
        .sp-pagination { display: flex; gap: .35rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap; }
        .sp-pagination a, .sp-pagination span { padding: .4rem .75rem; border-radius: 6px; border: 1px solid var(--border); font-size: .8rem; color: var(--text); text-decoration: none; background: #fff; }
        .sp-pagination a:hover { border-color: var(--cyan); color: var(--cyan2); }
        .sp-pagination .active { background: var(--cyan); color: #fff; border-color: var(--cyan); }
        .sp-pagination .disabled { opacity: .4; pointer-events: none; }

        /* ── Mobile nav ──────────────────────────────── */
        @media(max-width:700px) {
            .sp-nav .show-mobile { display: flex; }
            .sp-nav .hide-mobile { display: none; }
        }

        /* ── Flash banner ────────────────────────────── */
        .sp-flash { padding: .6rem 1.5rem; text-align: center; font-size: .875rem; font-weight: 500; }
        .sp-flash-success { background: #dcf5ea; color: #0d5c38; border-bottom: 2px solid #a7d9bc; }
        .sp-flash-info    { background: #e0f4fc; color: #005580; border-bottom: 2px solid #9cd8f0; }
    </style>
    @stack('styles')
</head>
<body>

<header class="sp-header">
    <div class="sp-header-inner">
        <a href="{{ route('supplier.home') }}" class="sp-logo">
            <div class="sp-logo-mark">CE</div>
            <div>
                <div class="sp-logo-text">Cawee ERP</div>
                <div class="sp-logo-sub">Supplier Portal</div>
            </div>
        </a>

        <nav class="sp-nav">
            <a href="{{ route('supplier.public.tenders') }}" class="{{ request()->routeIs('supplier.public.*') ? 'active' : '' }}">Tenders</a>

            @auth('supplier')
                <a href="{{ route('supplier.dashboard') }}" class="{{ request()->routeIs('supplier.dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('supplier.tenders') }}" class="{{ request()->routeIs('supplier.tenders*') ? 'active' : '' }}">Apply</a>
                <a href="{{ route('supplier.my-bids') }}" class="{{ request()->routeIs('supplier.my-bids') ? 'active' : '' }}">My Bids</a>
                <a href="{{ route('supplier.shares.index') }}" class="{{ request()->routeIs('supplier.shares.*') ? 'active' : '' }}">Shared Documents</a>
                <a href="{{ route('supplier.profile') }}" class="{{ request()->routeIs('supplier.profile*') ? 'active' : '' }}">Profile</a>
                <form method="POST" action="{{ route('supplier.logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="sp-btn-outline">Sign Out</button>
                </form>
            @else
                <a href="{{ route('supplier.login') }}" class="{{ request()->routeIs('supplier.login') ? 'active' : '' }}">Sign In</a>
                <a href="{{ route('supplier.register') }}" class="sp-btn-outline">Register</a>
            @endauth
        </nav>
    </div>
</header>
<div class="sp-strip"></div>

@if(session('success'))
<div class="sp-flash sp-flash-success">{{ session('success') }}</div>
@endif
@if(session('info'))
<div class="sp-flash sp-flash-info">{{ session('info') }}</div>
@endif

<main style="flex:1;">
    @yield('content')
</main>

<footer class="sp-footer">
    &copy; {{ date('Y') }} Cawee ERP &mdash; Procurement Portal &nbsp;|&nbsp;
    All bid submissions are subject to our <a href="#">terms and conditions</a>.
</footer>

@stack('scripts')
</body>
</html>
