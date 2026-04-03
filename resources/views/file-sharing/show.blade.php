<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shared File</title>
    <style>
        :root {
            --bg: #f4efe6;
            --panel: #fffdf8;
            --ink: #1f2933;
            --muted: #64748b;
            --accent: #bf5b04;
            --accent-dark: #8f4300;
            --border: #e7dccb;
            --danger-bg: #fff1f2;
            --danger-border: #fecdd3;
            --danger-text: #be123c;
            --success-bg: #ecfdf3;
            --success-border: #bbf7d0;
            --success-text: #166534;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(191, 91, 4, 0.12), transparent 28rem),
                linear-gradient(180deg, #fbf7f0 0%, var(--bg) 100%);
            display: grid;
            place-items: center;
            padding: 2rem;
        }

        .card {
            width: min(42rem, 100%);
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            box-shadow: 0 18px 40px rgba(31, 41, 51, 0.08);
            overflow: hidden;
        }

        .hero {
            padding: 2rem 2rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(191, 91, 4, 0.08), rgba(255, 255, 255, 0.2));
        }

        .eyebrow {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(191, 91, 4, 0.1);
            color: var(--accent-dark);
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 1rem 0 0.5rem;
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            line-height: 1.1;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .body {
            padding: 1.5rem 2rem 2rem;
            display: grid;
            gap: 1rem;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
            gap: 0.85rem;
        }

        .meta-item {
            padding: 0.9rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.9rem;
            background: #fff;
        }

        .meta-item strong {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 0.82rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .notice,
        .error-list {
            padding: 0.95rem 1rem;
            border-radius: 0.9rem;
            border: 1px solid;
        }

        .notice.success {
            background: var(--success-bg);
            border-color: var(--success-border);
            color: var(--success-text);
        }

        .error-list {
            background: var(--danger-bg);
            border-color: var(--danger-border);
            color: var(--danger-text);
            margin: 0;
            padding-left: 1.25rem;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .button {
            appearance: none;
            border: 0;
            border-radius: 999px;
            padding: 0.9rem 1.3rem;
            font: inherit;
            text-decoration: none;
            cursor: pointer;
            transition: transform 120ms ease, opacity 120ms ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button.primary {
            background: var(--accent);
            color: #fff;
        }

        .button.secondary {
            background: #fff;
            color: var(--ink);
            border: 1px solid var(--border);
        }

        .lockbox {
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.1rem;
            background: #fff;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        input[type="password"] {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid var(--border);
            font: inherit;
        }
    </style>
</head>
<body>
    <main class="card">
        <section class="hero">
            <span class="eyebrow">{{ ucfirst($share->share_type) }} Share</span>
            <h1>{{ $file?->display_name ?? 'Shared item' }}</h1>
            <p>
                Access level: <strong>{{ ucfirst($share->access_level) }}</strong>
                @if ($share->expires_at)
                    . Expires {{ $share->expires_at->format('M d, Y h:i A') }}
                @endif
            </p>
        </section>

        <section class="body">
            @if (session('status'))
                <div class="notice success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <ul class="error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="meta">
                <div class="meta-item">
                    <strong>Original Name</strong>
                    <span>{{ $file?->original_name ?? 'Unavailable' }}</span>
                </div>
                <div class="meta-item">
                    <strong>Size</strong>
                    <span>{{ $file?->human_size ?? 'Unknown' }}</span>
                </div>
                <div class="meta-item">
                    <strong>Downloads</strong>
                    <span>{{ $share->download_count }}@if ($share->max_downloads !== null) / {{ $share->max_downloads }} @endif</span>
                </div>
                <div class="meta-item">
                    <strong>Password</strong>
                    <span>{{ $share->password ? ($isUnlocked ? 'Unlocked' : 'Required') : 'Not required' }}</span>
                </div>
            </div>

            @if ($share->password && ! $isUnlocked)
                <div class="lockbox">
                    <form method="POST" action="{{ route('file-shares.unlock', $share->share_token) }}">
                        @csrf
                        <label for="password">Enter share password</label>
                        <input id="password" name="password" type="password" required>
                        <div class="actions">
                            <button class="button primary" type="submit">Unlock Share</button>
                        </div>
                    </form>
                </div>
            @else
                <div class="actions">
                    @if ($canPreview)
                        <a class="button secondary" href="{{ route('file-shares.preview', $share->share_token) }}" target="_blank" rel="noreferrer">Preview</a>
                    @endif

                    @if ($canDownload)
                        <a class="button primary" href="{{ route('file-shares.download', $share->share_token) }}">Download</a>
                    @endif
                </div>
            @endif
        </section>
    </main>
</body>
</html>
