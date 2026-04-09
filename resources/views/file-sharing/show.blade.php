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
            width: min(62rem, 100%);
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

        .folder-files {
            border: 1px solid var(--border);
            border-radius: 1rem;
            background: #fff;
            overflow: hidden;
        }

        .folder-shell {
            display: grid;
            gap: 1rem;
        }

        .folder-breadcrumbs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .folder-breadcrumbs span:last-child {
            color: var(--ink);
            font-weight: 700;
        }

        .folder-grid {
            display: grid;
            grid-template-columns: minmax(15rem, 20rem) minmax(0, 1fr);
            gap: 1rem;
            align-items: start;
        }

        .folder-panel {
            border: 1px solid var(--border);
            border-radius: 1rem;
            background: #fff;
            overflow: hidden;
        }

        .folder-panel h2 {
            margin: 0;
            padding: 1rem 1.2rem;
            font-size: 1rem;
            border-bottom: 1px solid var(--border);
            background: #fffaf2;
        }

        .folder-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .folder-list li {
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid var(--border);
        }

        .folder-list li:last-child {
            border-bottom: 0;
        }

        .folder-list strong {
            display: block;
            margin-bottom: 0.2rem;
        }

        .folder-files h2 {
            margin: 0;
            padding: 1rem 1.2rem;
            font-size: 1.05rem;
            border-bottom: 1px solid var(--border);
        }

        .folder-files .table-wrap {
            overflow-x: auto;
        }

        .folder-files table {
            width: 100%;
            border-collapse: collapse;
        }

        .folder-files th,
        .folder-files td {
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }

        .folder-files th {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--muted);
            background: #fffaf2;
        }

        .folder-files tr:last-child td {
            border-bottom: 0;
        }

        .inline-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .file-name {
            min-width: 24rem;
            word-break: break-word;
        }

        .file-meta {
            color: var(--muted);
            font-size: 0.88rem;
            margin-top: 0.25rem;
        }

        .button.small {
            padding: 0.55rem 0.9rem;
            border-radius: 0.8rem;
            font-size: 0.92rem;
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

        @media (max-width: 840px) {
            .folder-grid {
                grid-template-columns: 1fr;
            }

            .file-name {
                min-width: 16rem;
            }
        }
    </style>
</head>
<body>
    <main class="card">
        <section class="hero">
            <span class="eyebrow">{{ ucfirst($share->share_type) }} Share</span>
            <h1>{{ $file?->display_name ?? $folder?->name ?? 'Shared item' }}</h1>
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
                    <span>{{ $file?->original_name ?? $folder?->name ?? 'Unavailable' }}</span>
                </div>
                <div class="meta-item">
                    <strong>Size</strong>
                    <span>{{ $file?->human_size ?? (($folderFiles ?? collect())->count() . ' files') }}</span>
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

                    @if ($share->shared_folder_id !== null && $share->allowsDownload())
                        <a class="button primary" href="{{ route('file-shares.folder.download', $share->share_token) }}">Download Folder</a>
                    @endif
                </div>

                @if ($share->shared_folder_id !== null)
                    <div class="folder-shell">
                        @if (! empty($folderBreadcrumbs))
                            <div class="folder-breadcrumbs">
                                @foreach ($folderBreadcrumbs as $crumb)
                                    <span>{{ $crumb['name'] }}</span>
                                    @if (! $loop->last)
                                        <span>/</span>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <div class="folder-grid">
                            <aside class="folder-panel">
                                <h2>Nested Folders</h2>
                                @if (($childFolders ?? collect())->isEmpty())
                                    <p style="padding: 1rem 1.2rem; color: var(--muted);">No nested folders inside this shared folder.</p>
                                @else
                                    <ul class="folder-list">
                                        @foreach ($childFolders as $childFolder)
                                            <li>
                                                <strong>{{ $childFolder->name }}</strong>
                                                <span style="color: var(--muted); font-size: 0.88rem;">
                                                    {{ $childFolder->files_count }} file{{ $childFolder->files_count === 1 ? '' : 's' }}
                                                    . {{ $childFolder->children_count }} subfolder{{ $childFolder->children_count === 1 ? '' : 's' }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </aside>

                            <div class="folder-files">
                                <h2>Folder Contents</h2>
                                @if (($folderFiles ?? collect())->isEmpty())
                                    <p style="padding: 1rem 1.2rem; color: var(--muted);">This shared folder does not contain any files yet.</p>
                                @else
                                    <div class="table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>File</th>
                                                <th>Location</th>
                                                <th>Type</th>
                                                <th>Size</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($folderFiles as $folderFile)
                                                <tr>
                                                    <td class="file-name">
                                                        <strong>{{ $folderFile->display_name }}</strong>
                                                        <div class="file-meta">{{ $folderFile->original_name ?? '-' }}</div>
                                                    </td>
                                                    <td>{{ $folderFile->relative_folder_path }}</td>
                                                    <td>{{ strtoupper($folderFile->extension ?: '-') }}</td>
                                                    <td>{{ $folderFile->human_size }}</td>
                                                    <td>
                                                        <div class="inline-actions">
                                                            @if ($share->allowsPreview())
                                                                <a class="button secondary small" href="{{ route('file-shares.folder-files.preview', ['token' => $share->share_token, 'file' => $folderFile->id]) }}" target="_blank" rel="noreferrer">Preview</a>
                                                            @endif
                                                            @if ($share->allowsDownload())
                                                                <a class="button primary small" href="{{ route('file-shares.folder-files.download', ['token' => $share->share_token, 'file' => $folderFile->id]) }}">Download</a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </section>
    </main>
</body>
</html>
