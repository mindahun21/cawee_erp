<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shared File Status</title>
    <style>
        :root {
            --bg: #f5f3ef;
            --panel: #fffdfa;
            --ink: #1f2933;
            --muted: #667085;
            --accent: #0f766e;
            --warn: #b45309;
            --danger: #b42318;
            --border: #e8ddd0;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem;
            background:
                radial-gradient(circle at top right, rgba(15, 118, 110, 0.10), transparent 22rem),
                radial-gradient(circle at bottom left, rgba(180, 83, 9, 0.08), transparent 18rem),
                var(--bg);
            color: var(--ink);
            font-family: "Inter", "Segoe UI", sans-serif;
        }

        .card {
            width: min(42rem, 100%);
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            padding: 2rem;
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08);
        }

        .eyebrow {
            display: inline-flex;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.09);
            color: var(--accent);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        h1 {
            margin: 1rem 0 0.45rem;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            line-height: 1.1;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
            gap: 0.85rem;
            margin: 1.5rem 0;
        }

        .meta-item {
            padding: 0.9rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.95rem;
            background: #fff;
        }

        .meta-item strong {
            display: block;
            margin-bottom: 0.25rem;
            font-size: 0.76rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .banner {
            border-radius: 1rem;
            padding: 1rem 1.1rem;
            margin-top: 1rem;
            border: 1px solid;
            font-weight: 600;
        }

        .banner.expired {
            color: var(--warn);
            background: #fff7ed;
            border-color: #fed7aa;
        }

        .banner.forbidden,
        .banner.unavailable {
            color: var(--danger);
            background: #fef3f2;
            border-color: #fecaca;
        }

        .banner.password_required {
            color: var(--accent);
            background: #ecfeff;
            border-color: #a5f3fc;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1.4rem;
        }

        .button {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.85rem 1.15rem;
            border-radius: 999px;
            font-weight: 700;
            border: 1px solid var(--border);
            color: var(--ink);
            background: #fff;
        }

        .button.primary {
            color: #fff;
            background: var(--accent);
            border-color: var(--accent);
        }
    </style>
</head>
<body>
    <main class="card">
        <span class="eyebrow">{{ ucfirst($share->share_type) }} Share</span>
        <h1>{{ $share->file?->display_name ?? $share->folder?->name ?? 'Shared item unavailable' }}</h1>
        <p>{{ $message }}</p>

        <div class="meta">
            <div class="meta-item">
                <strong>Access Level</strong>
                <span>{{ ucfirst($share->access_level) }}</span>
            </div>
            <div class="meta-item">
                <strong>Expires</strong>
                <span>{{ $share->expires_at?->format('M d, Y h:i A') ?? 'No expiry' }}</span>
            </div>
            <div class="meta-item">
                <strong>Recipient</strong>
                <span>{{ $share->shared_with_email ?? ($share->recipient?->name ?? 'Protected share') }}</span>
            </div>
        </div>

        <div class="banner {{ $state }}">
            @if ($state === 'expired')
                This link is no longer usable. Ask the sender to create a new share.
            @elseif ($state === 'forbidden')
                This share is restricted to a different account. Sign in with the intended account or contact the sender.
            @elseif ($state === 'password_required')
                This share needs to be unlocked first. Return to the share page to enter the password.
            @else
                This shared item is currently unavailable.
            @endif
        </div>

        <div class="actions">
            <a class="button" href="{{ route('file-shares.show', $share->share_token) }}">Back to Share</a>
            @if ($share->share_type === 'client')
                <a class="button primary" href="{{ route('supplier.shares.index') }}">Open Shared Documents</a>
            @elseif ($share->share_type === 'staff')
                <a class="button primary" href="{{ route('recipient-shares.index') }}">Open My Shares</a>
            @endif
        </div>
    </main>
</body>
</html>
