<x-filament-panels::page>
    @php
        $shareTotal = max(1, ($summary['publicShares'] + $summary['staffShares'] + $summary['clientShares']));
        $topDownloads = max(1, (int) ($topFiles->max('downloads_count') ?? 0));
        $topUploads = max(1, (int) ($uploadsByUser->max('uploads_count') ?? 0));
        $activityTotal = max(1, array_sum($activityByAction));
        $deniedChartMax = max(1, max($deniedByDay ?: [0]));
        $actionPalette = [
            'uploaded' => ['label' => 'Uploads', 'bar' => 'fs-seg-staff'],
            'previewed' => ['label' => 'Previews', 'bar' => 'fs-seg-client'],
            'downloaded' => ['label' => 'Downloads', 'bar' => 'fs-seg-public'],
            'shared' => ['label' => 'Shares', 'bar' => 'fs-seg-staff'],
            'revoked' => ['label' => 'Revokes', 'bar' => 'fs-seg-denied'],
            'deleted' => ['label' => 'Deletes', 'bar' => 'fs-seg-denied'],
        ];
        $rangeLabel = match ($rangeDays) {
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
            default => 'All time',
        };
    @endphp

    <style>
        .fs-report-shell {
            --panel: #fffdf8;
            --ink: #1f2937;
            --muted: #64748b;
            --line: #eadfce;
            --amber: #bf5b04;
            --amber-soft: #fff1de;
            --teal: #0f766e;
            --teal-soft: #ddf7f3;
            --sky: #2563eb;
            --sky-soft: #e6efff;
            --rose: #be123c;
            --rose-soft: #fff1f2;
        }

        .fs-report-shell {
            display: grid;
            gap: 1.25rem;
        }

        .fs-hero,
        .fs-panel {
            border: 1px solid var(--line);
            border-radius: 1.35rem;
            background: var(--panel);
        }

        .fs-hero {
            overflow: hidden;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.14), transparent 18rem),
                radial-gradient(circle at left center, rgba(191, 91, 4, 0.12), transparent 22rem),
                var(--panel);
            padding: 1.5rem;
        }

        .fs-hero-grid,
        .fs-band,
        .fs-grid-two {
            display: grid;
            gap: 1rem;
        }

        .fs-hero-grid {
            grid-template-columns: 1.5fr 1fr;
        }

        .fs-band {
            grid-template-columns: 1.1fr .9fr;
        }

        .fs-grid-two {
            grid-template-columns: 1fr 1fr;
        }

        .fs-kicker {
            font-size: .78rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--amber);
            font-weight: 700;
        }

        .fs-range-row {
            margin-top: .9rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: .55rem;
        }

        .fs-range-chip {
            border: 1px solid #dccab2;
            background: #fff7eb;
            color: #8a4b09;
            border-radius: 999px;
            font-size: .76rem;
            font-weight: 700;
            line-height: 1;
            padding: .45rem .7rem;
            transition: all .18s ease;
        }

        .fs-range-chip:hover {
            border-color: #bf5b04;
            color: #743805;
        }

        .fs-range-chip.active {
            border-color: #bf5b04;
            background: linear-gradient(90deg, #bf5b04, #dd7b20);
            color: #fff;
            box-shadow: 0 6px 14px rgba(191, 91, 4, 0.24);
        }

        .fs-range-label {
            color: var(--muted);
            font-size: .82rem;
            font-weight: 700;
            margin-left: .2rem;
        }

        .fs-headline {
            margin-top: .45rem;
            font-size: clamp(1.8rem, 2vw + 1rem, 2.7rem);
            line-height: 1.05;
            font-weight: 800;
            color: var(--ink);
        }

        .fs-copy {
            margin-top: .6rem;
            max-width: 38rem;
            color: var(--muted);
            font-size: .98rem;
            line-height: 1.6;
        }

        .fs-mini-grid {
            display: grid;
            gap: .8rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .fs-stat {
            border: 1px solid var(--line);
            border-radius: 1.2rem;
            background: rgba(255, 255, 255, 0.86);
            padding: 1rem 1.05rem;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
        }

        .fs-stat-label {
            color: var(--muted);
            font-size: .76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .fs-stat-value {
            margin-top: .5rem;
            color: var(--ink);
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1;
        }

        .fs-stat-hint,
        .fs-panel-sub,
        .fs-row-sub,
        .fs-activity-meta,
        .fs-chart-meta,
        .fs-time,
        .fs-denied-label {
            color: var(--muted);
        }

        .fs-stat-hint {
            margin-top: .35rem;
            font-size: .83rem;
        }

        .fs-panel {
            padding: 1.15rem 1.2rem;
        }

        .fs-panel-title {
            color: var(--ink);
            font-size: 1.02rem;
            font-weight: 800;
        }

        .fs-panel-sub {
            margin-top: .2rem;
            font-size: .88rem;
        }

        .fs-share-mix,
        .fs-list,
        .fs-activity,
        .fs-chart-stack {
            margin-top: 1rem;
            display: grid;
            gap: .8rem;
        }

        .fs-mix-track,
        .fs-progress,
        .fs-chart-track {
            overflow: hidden;
            border-radius: 999px;
            background: #f2ede4;
        }

        .fs-mix-track {
            display: flex;
            height: .95rem;
        }

        .fs-progress {
            margin-top: .7rem;
            height: .55rem;
        }

        .fs-chart-track {
            height: .72rem;
        }

        .fs-progress > span,
        .fs-chart-track > span {
            display: block;
            height: 100%;
            border-radius: inherit;
        }

        .fs-seg-public { background: linear-gradient(90deg, #bf5b04, #dd7b20); }
        .fs-seg-staff { background: linear-gradient(90deg, #2563eb, #4f8df7); }
        .fs-seg-client { background: linear-gradient(90deg, #0f766e, #22a599); }
        .fs-seg-denied { background: linear-gradient(90deg, #be123c, #ef476f); }

        .fs-mix-legend {
            display: grid;
            gap: .55rem;
        }

        .fs-legend-row,
        .fs-chart-head {
            display: grid;
            gap: .7rem;
            align-items: center;
            color: var(--ink);
        }

        .fs-legend-row {
            grid-template-columns: auto 1fr auto;
            font-size: .92rem;
        }

        .fs-chart-head {
            grid-template-columns: 1fr auto;
            font-size: .9rem;
            font-weight: 700;
        }

        .fs-chart-meta {
            font-size: .8rem;
            font-weight: 600;
        }

        .fs-dot {
            width: .8rem;
            height: .8rem;
            border-radius: 999px;
        }

        .fs-row,
        .fs-activity-row {
            border: 1px solid var(--line);
            border-radius: 1rem;
            background: rgba(255, 255, 255, .8);
        }

        .fs-row {
            padding: .9rem 1rem;
        }

        .fs-activity-row {
            padding: .95rem 1rem;
        }

        .fs-activity-row.danger {
            border-color: #f3c6d0;
            background: var(--rose-soft);
        }

        .fs-row-head,
        .fs-activity-top {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: start;
        }

        .fs-row-title {
            color: var(--ink);
            font-weight: 700;
            line-height: 1.35;
        }

        .fs-row-sub,
        .fs-activity-meta {
            margin-top: .2rem;
            font-size: .85rem;
            word-break: break-word;
        }

        .fs-pill {
            padding: .26rem .55rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .fs-pill-amber { background: var(--amber-soft); color: var(--amber); }
        .fs-pill-teal { background: var(--teal-soft); color: var(--teal); }
        .fs-pill-sky { background: var(--sky-soft); color: var(--sky); }
        .fs-pill-rose { background: var(--rose-soft); color: var(--rose); }

        .fs-time {
            font-size: .78rem;
            white-space: nowrap;
        }

        .fs-denied-bars {
            margin-top: 1rem;
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            align-items: end;
            min-height: 11rem;
        }

        .fs-denied-col {
            display: grid;
            gap: .45rem;
            justify-items: center;
        }

        .fs-denied-value {
            color: var(--rose);
            font-size: .78rem;
            font-weight: 800;
        }

        .fs-denied-bar-wrap {
            width: 100%;
            max-width: 3rem;
            height: 8rem;
            border-radius: 1rem;
            background: linear-gradient(180deg, #fff6f7, #fde1e7);
            border: 1px solid #f6c8d3;
            padding: .35rem;
            display: flex;
            align-items: end;
        }

        .fs-denied-bar {
            width: 100%;
            border-radius: .7rem;
            background: linear-gradient(180deg, #ef476f, #be123c);
            min-height: .3rem;
        }

        .fs-denied-label {
            font-size: .76rem;
            font-weight: 700;
        }

        @media (max-width: 1024px) {
            .fs-hero-grid,
            .fs-band,
            .fs-grid-two {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .fs-mini-grid,
            .fs-denied-bars {
                grid-template-columns: 1fr;
            }

            .fs-row-head,
            .fs-activity-top {
                flex-direction: column;
                align-items: start;
            }

            .fs-denied-bar-wrap {
                max-width: 100%;
                height: 4rem;
            }
        }
    </style>

    <div class="fs-report-shell">
        <section class="fs-hero">
            <div class="fs-hero-grid">
                <div>
                    <div class="fs-kicker">File Sharing Intelligence</div>
                    <div class="fs-headline">See what is being shared, opened, and denied.</div>
                    <div class="fs-copy">
                        This dashboard highlights the current file-sharing footprint across staff, clients, and public links, with the biggest activity signals surfaced first.
                    </div>
                    <div class="fs-range-row">
                        <button class="fs-range-chip {{ $rangeDays === '7' ? 'active' : '' }}" wire:click="setRange('7')" type="button">7d</button>
                        <button class="fs-range-chip {{ $rangeDays === '30' ? 'active' : '' }}" wire:click="setRange('30')" type="button">30d</button>
                        <button class="fs-range-chip {{ $rangeDays === '90' ? 'active' : '' }}" wire:click="setRange('90')" type="button">90d</button>
                        <button class="fs-range-chip {{ $rangeDays === 'all' ? 'active' : '' }}" wire:click="setRange('all')" type="button">All</button>
                        <span class="fs-range-label">{{ $rangeLabel }}</span>
                    </div>
                </div>

                <div class="fs-mini-grid">
                    <div class="fs-stat">
                        <div class="fs-stat-label">Files</div>
                        <div class="fs-stat-value">{{ number_format($summary['files']) }}</div>
                        <div class="fs-stat-hint">Stored and tracked in the module</div>
                    </div>
                    <div class="fs-stat">
                        <div class="fs-stat-label">Active Shares</div>
                        <div class="fs-stat-value">{{ number_format($summary['activeShares']) }}</div>
                        <div class="fs-stat-hint">Currently usable by recipients</div>
                    </div>
                    <div class="fs-stat">
                        <div class="fs-stat-label">Downloads</div>
                        <div class="fs-stat-value">{{ number_format($summary['downloads']) }}</div>
                        <div class="fs-stat-hint">Confirmed completed downloads</div>
                    </div>
                    <div class="fs-stat">
                        <div class="fs-stat-label">Expired Shares</div>
                        <div class="fs-stat-value">{{ number_format($summary['expiredShares']) }}</div>
                        <div class="fs-stat-hint">Links already past expiry</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="fs-band">
            <div class="fs-panel">
                <div class="fs-panel-title">Share Type Mix</div>
                <div class="fs-panel-sub">Current distribution of public, staff, and client shares.</div>

                <div class="fs-share-mix">
                    <div class="fs-mix-track">
                        <div class="fs-seg-public" style="width: {{ ($summary['publicShares'] / $shareTotal) * 100 }}%"></div>
                        <div class="fs-seg-staff" style="width: {{ ($summary['staffShares'] / $shareTotal) * 100 }}%"></div>
                        <div class="fs-seg-client" style="width: {{ ($summary['clientShares'] / $shareTotal) * 100 }}%"></div>
                    </div>

                    <div class="fs-mix-legend">
                        <div class="fs-legend-row">
                            <span class="fs-dot fs-seg-public"></span>
                            <span>Public Shares</span>
                            <strong>{{ number_format($summary['publicShares']) }}</strong>
                        </div>
                        <div class="fs-legend-row">
                            <span class="fs-dot fs-seg-staff"></span>
                            <span>Staff Shares</span>
                            <strong>{{ number_format($summary['staffShares']) }}</strong>
                        </div>
                        <div class="fs-legend-row">
                            <span class="fs-dot fs-seg-client"></span>
                            <span>Client Shares</span>
                            <strong>{{ number_format($summary['clientShares']) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fs-panel">
                <div class="fs-panel-title">At a Glance</div>
                <div class="fs-panel-sub">A quick operational read of current module health.</div>

                <div class="fs-list">
                    <div class="fs-row">
                        <div class="fs-row-head">
                            <div class="fs-row-title">Download-to-share ratio</div>
                            <span class="fs-pill fs-pill-teal">
                                {{ $summary['activeShares'] > 0 ? number_format($summary['downloads'] / $summary['activeShares'], 1) : '0.0' }}x
                            </span>
                        </div>
                        <div class="fs-row-sub">Average completed downloads relative to currently active shares.</div>
                    </div>

                    <div class="fs-row">
                        <div class="fs-row-head">
                            <div class="fs-row-title">Expired share pressure</div>
                            <span class="fs-pill {{ $summary['expiredShares'] > 0 ? 'fs-pill-rose' : 'fs-pill-sky' }}">
                                {{ $summary['expiredShares'] > 0 ? 'Needs review' : 'Clean' }}
                            </span>
                        </div>
                        <div class="fs-row-sub">Use this to see whether many stale links are accumulating in the system.</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="fs-grid-two">
            <div class="fs-panel">
                <div class="fs-panel-title">Most Downloaded Files</div>
                <div class="fs-panel-sub">Ranked by completed download events.</div>
                <div class="fs-list">
                    @forelse ($topFiles as $file)
                        @php $width = (($file->downloads_count ?? 0) / $topDownloads) * 100; @endphp
                        <div class="fs-row">
                            <div class="fs-row-head">
                                <div>
                                    <div class="fs-row-title">{{ $file->display_name }}</div>
                                    <div class="fs-row-sub">{{ $file->original_name ?? '-' }}</div>
                                </div>
                                <span class="fs-pill fs-pill-amber">{{ $file->downloads_count }} downloads</span>
                            </div>
                            <div class="fs-progress">
                                <span class="fs-seg-public" style="width: {{ $width }}%"></span>
                            </div>
                        </div>
                    @empty
                        <div class="fs-row">
                            <div class="fs-row-sub">No download activity yet.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="fs-panel">
                <div class="fs-panel-title">Files Uploaded By User</div>
                <div class="fs-panel-sub">Top uploaders in the current file-sharing repository.</div>
                <div class="fs-list">
                    @forelse ($uploadsByUser as $row)
                        @php $width = ($row->uploads_count / $topUploads) * 100; @endphp
                        <div class="fs-row">
                            <div class="fs-row-head">
                                <div>
                                    <div class="fs-row-title">{{ $row->uploader?->name ?? 'Unknown user' }}</div>
                                    <div class="fs-row-sub">{{ $row->uploader?->email ?? '-' }}</div>
                                </div>
                                <span class="fs-pill fs-pill-sky">{{ $row->uploads_count }} files</span>
                            </div>
                            <div class="fs-progress">
                                <span class="fs-seg-staff" style="width: {{ $width }}%"></span>
                            </div>
                        </div>
                    @empty
                        <div class="fs-row">
                            <div class="fs-row-sub">No uploads found.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="fs-grid-two">
            <div class="fs-panel">
                <div class="fs-panel-title">Recent Share Activity</div>
                <div class="fs-panel-sub">Latest actions across previews, downloads, and share events.</div>

                <div class="fs-chart-stack">
                    @foreach ($actionPalette as $action => $config)
                        @php
                            $count = (int) ($activityByAction[$action] ?? 0);
                            $width = ($count / $activityTotal) * 100;
                        @endphp
                        <div>
                            <div class="fs-chart-head">
                                <span>{{ $config['label'] }}</span>
                                <span class="fs-chart-meta">{{ $count }} events</span>
                            </div>
                            <div class="fs-chart-track">
                                <span class="{{ $config['bar'] }}" style="width: {{ $width }}%"></span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="fs-activity">
                    @forelse ($recentActivity as $log)
                        <div class="fs-activity-row">
                            <div class="fs-activity-top">
                                <div>
                                    <div class="fs-row-title">{{ ucfirst($log->action) }}</div>
                                    <div class="fs-activity-meta">
                                        {{ $log->file?->display_name ?? 'No file' }} &middot; {{ ucfirst($log->share?->share_type ?? 'n/a') }} &middot; {{ $log->user?->name ?? 'Guest' }}
                                    </div>
                                </div>
                                <div class="fs-time">{{ $log->accessed_at?->format('M d, Y h:i A') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="fs-row">
                            <div class="fs-row-sub">No access logs yet.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="fs-panel">
                <div class="fs-panel-title">Denied Access Attempts</div>
                <div class="fs-panel-sub">Recent blocked access attempts that may need investigation.</div>

                <div class="fs-denied-bars">
                    @foreach ($deniedByDay as $label => $count)
                        @php $height = max(4, ($count / $deniedChartMax) * 100); @endphp
                        <div class="fs-denied-col">
                            <div class="fs-denied-value">{{ $count }}</div>
                            <div class="fs-denied-bar-wrap">
                                <div class="fs-denied-bar" style="height: {{ $height }}%"></div>
                            </div>
                            <div class="fs-denied-label">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="fs-activity">
                    @forelse ($deniedAccess as $log)
                        <div class="fs-activity-row danger">
                            <div class="fs-activity-top">
                                <div>
                                    <div class="fs-row-title">{{ $log->file?->display_name ?? 'No file' }}</div>
                                    <div class="fs-activity-meta">{{ $log->notes }}</div>
                                    <div class="fs-activity-meta">{{ $log->user?->name ?? 'Guest' }} &middot; {{ $log->ip_address ?? '-' }}</div>
                                </div>
                                <div class="fs-time">{{ $log->accessed_at?->format('M d, Y h:i A') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="fs-row">
                            <div class="fs-row-sub">No denied access attempts found.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
