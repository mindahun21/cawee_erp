<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Careers') — Cawee ERP</title>
    <meta name="description" content="@yield('description', 'Browse open positions and apply to join our team.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy:   #362A72;
            --navy2:  #2a2058;
            --teal:   #6C5CE7;
            --teal2:  #5a4bd1;
            --silver: #f4f6f8;
            --border: #e2e8f0;
            --text:   #1a2332;
            --muted:  #6b7a90;
            --success:#0d7a4e;
            --danger: #c0392b;
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

        /* ── Header ── */
        .rp-header {
            background: var(--navy);
            color: #fff;
            position: sticky; top: 0; z-index: 50;
            box-shadow: 0 2px 16px rgba(0,0,0,0.18);
        }
        .rp-header-inner {
            max-width: 1200px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 1.5rem; height: 64px;
        }
        .rp-logo {
            display: flex; align-items: center; gap: .75rem;
            text-decoration: none; color: #fff;
        }
        .rp-logo-mark {
            width: 38px; height: 38px; background: var(--teal);
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1.1rem; letter-spacing: -.5px;
        }
        .rp-logo-text { font-size: .95rem; font-weight: 600; line-height: 1.2; }
        .rp-logo-sub  { font-size: .7rem; font-weight: 400; opacity: .7; letter-spacing: .04em; text-transform: uppercase; }

        .rp-nav { display: flex; align-items: center; gap: .25rem; }
        .rp-nav a, .rp-nav button {
            color: rgba(255,255,255,.85); text-decoration: none;
            padding: .45rem .85rem; border-radius: 6px; font-size: .875rem; font-weight: 500;
            transition: all .15s; border: none; background: transparent; cursor: pointer;
        }
        .rp-nav a:hover, .rp-nav button:hover { background: rgba(255,255,255,.12); color: #fff; }
        .rp-nav a.active { background: var(--teal); color: #fff; }
        .rp-nav .rp-btn-outline {
            border: 1.5px solid rgba(255,255,255,.35);
            padding: .4rem .9rem;
        }
        .rp-nav .rp-btn-outline:hover { border-color: #fff; background: rgba(255,255,255,.1); }

        .rp-strip { background: var(--teal); height: 4px; }

        /* ── Page ── */
        .rp-page { max-width: 1200px; width: 100%; margin: 0 auto; padding: 2rem 1.5rem; flex: 1; }

        /* ── Footer ── */
        .rp-footer { background: var(--navy2); color: rgba(255,255,255,.6); text-align: center; padding: 1.5rem; font-size: .78rem; margin-top: auto; }
        .rp-footer a { color: var(--teal); text-decoration: none; }

        /* ── Badges ── */
        .badge { display: inline-flex; align-items: center; padding: .2rem .6rem; border-radius: 20px; font-size: .72rem; font-weight: 600; letter-spacing: .02em; text-transform: uppercase; }
        .badge-teal   { background: #ede9ff; color: var(--teal2); }
        .badge-navy   { background: #e8eef5; color: var(--navy); }
        .badge-gray   { background: #f1f3f6; color: #64748b; }

        /* ── Pagination ── */
        .rp-pagination { display: flex; gap: .35rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap; }
        .rp-pagination a, .rp-pagination span { padding: .4rem .75rem; border-radius: 6px; border: 1px solid var(--border); font-size: .8rem; color: var(--text); text-decoration: none; background: #fff; }
        .rp-pagination a:hover { border-color: var(--teal); color: var(--teal2); }
        .rp-pagination .active { background: var(--teal); color: #fff; border-color: var(--teal); }
        .rp-pagination .disabled { opacity: .4; pointer-events: none; }

        /* ── Flash ── */
        .rp-flash { padding: .6rem 1.5rem; text-align: center; font-size: .875rem; font-weight: 500; }
        .rp-flash-success { background: #dcf5ea; color: #0d5c38; border-bottom: 2px solid #a7d9bc; }
    </style>
    @stack('styles')
</head>
<body>

<header class="rp-header">
    <div class="rp-header-inner">
        <a href="{{ route('candidate.home') }}" class="rp-logo">
            <div class="rp-logo-mark">CE</div>
            <div>
                <div class="rp-logo-text">Cawee ERP</div>
                <div class="rp-logo-sub">Careers Portal</div>
            </div>
        </a>

        <nav class="rp-nav">
            <a href="{{ route('candidate.campaigns') }}" class="{{ request()->routeIs('candidate.campaigns*') || request()->routeIs('candidate.home') ? 'active' : '' }}">Open Positions</a>

            @auth('candidate')
                <a href="{{ route('candidate.my-applications') }}" class="{{ request()->routeIs('candidate.my-applications*') ? 'active' : '' }}">My Applications</a>
                <a href="{{ route('candidate.my-offers') }}" class="{{ request()->routeIs('candidate.my-offers*') ? 'active' : '' }}">My Offers</a>
                <a href="{{ route('candidate.profile') }}" class="{{ request()->routeIs('candidate.profile') ? 'active' : '' }}" style="margin-left: .5rem;">Profile</a>
                <form method="POST" action="{{ route('candidate.logout') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="rp-btn-outline">Sign Out</button>
                </form>
            @else
                <a href="{{ route('candidate.login') }}" class="{{ request()->routeIs('candidate.login') ? 'active' : '' }}">Sign In</a>
            @endauth
        </nav>
    </div>
</header>
<div class="rp-strip"></div>

@if(session('success'))
<div class="rp-flash rp-flash-success">{{ session('success') }}</div>
@endif

<main class="rp-page">
    @yield('content')
</main>

<footer class="rp-footer">
    &copy; {{ date('Y') }} Cawee ERP &mdash; Careers Portal &nbsp;|&nbsp;
    We are an equal opportunity employer.
</footer>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@stack('scripts')
</body>
</html>
