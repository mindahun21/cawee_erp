<x-filament-panels::page>
    @if(! $employee)
        {{-- ─── Empty State ──────────────────────────────────────────── --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">
            <div class="relative px-6 py-20 text-center">
                {{-- Subtle background decoration --}}
                <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]" style="background-image: radial-gradient(circle at 1px 1px, currentColor 1px, transparent 0); background-size: 24px 24px;"></div>

                <div class="relative">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                        <x-heroicon-o-user-circle class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="mt-5 text-base font-bold text-gray-950 dark:text-white">No Employee Profile Linked</h3>
                    <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                        Your system user account has not been linked to an employee profile yet. Please ask an administrator to assign your user account to your employee record.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-6">

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- PROFILE HERO CARD                                      --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden">

                {{-- Minimal Banner --}}
                <div class="relative h-28 sm:h-32 bg-gray-50 dark:bg-gray-800/50">
                    <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.05]" style="background-image: radial-gradient(circle at 1px 1px, currentColor 1px, transparent 0); background-size: 20px 20px;"></div>
                </div>

                {{-- Profile Body --}}
                <div class="relative px-4 pb-6 sm:px-6">

                    {{-- Avatar + Identity --}}
                    <div class="-mt-11 flex flex-col gap-4 sm:flex-row sm:items-end">
                        {{-- Avatar --}}
                        <div class="relative shrink-0">
                            <div class="flex h-20 w-20 sm:h-24 sm:w-24 items-center justify-center rounded-full bg-gray-100 ring-4 ring-white dark:bg-gray-800 dark:ring-gray-900 text-2xl font-bold tracking-tight text-gray-500 dark:text-gray-400">
                                {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}
                            </div>
                            <div class="absolute bottom-1 right-1 flex h-4 w-4 sm:h-5 sm:w-5 items-center justify-center rounded-full ring-2 ring-white bg-success-500 dark:ring-gray-900" title="Active"></div>
                        </div>

                        {{-- Name Block --}}
                        <div class="min-w-0 flex-1 sm:pb-1">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <h1 class="truncate text-xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-2xl">
                                        {{ $employee->full_name }}
                                    </h1>
                                    <p class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="truncate">{{ $employee->jobPosition?->title ?? 'Position Pending' }}</span>
                                        <span class="text-gray-300 dark:text-gray-600">·</span>
                                        <span class="truncate">{{ $employee->department?->name ?? 'Unassigned Department' }}</span>
                                    </p>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-lg bg-gray-50 px-3 py-1.5 font-mono text-xs font-semibold text-gray-500 ring-1 ring-inset ring-gray-950/5 dark:bg-gray-800 dark:text-gray-400 dark:ring-white/10">
                                    <x-filament::icon icon="heroicon-m-identification" class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" />
                                    EMP-{{ str_pad($employee->id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Badges --}}
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-50 ring-1 ring-inset ring-gray-950/5 dark:bg-gray-800 dark:text-gray-400 dark:ring-white/10">
                            <x-filament::icon icon="heroicon-m-calendar-days" class="h-3.5 w-3.5 text-gray-400" />
                            Joined {{ $employee->date_of_employment?->format('M d, Y') ?? 'Unknown' }}
                        </span>
                        @if($employee->date_of_employment)
                            @php
                                $tenure = $employee->date_of_employment->diff(now());
                                $tenureText = $tenure->y > 0 ? $tenure->y . 'y ' . $tenure->m . 'm' : $tenure->m . ' month(s)';
                            @endphp
                            <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium text-gray-600 bg-gray-50 ring-1 ring-inset ring-gray-950/5 dark:bg-gray-800 dark:text-gray-400 dark:ring-white/10">
                                <x-filament::icon icon="heroicon-m-clock" class="h-3.5 w-3.5 text-gray-400" />
                                {{ $tenureText }} tenure
                            </span>
                        @endif
                        @if($employee->contractType)
                            <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium text-primary-700 bg-primary-50 ring-1 ring-inset ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/20">
                                <x-filament::icon icon="heroicon-m-document-text" class="h-3.5 w-3.5" />
                                {{ $employee->contractType->name }}
                            </span>
                        @endif
                        @if($lastMovement)
                            <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium {{ $lastMovement->movement_type === 'Promotion' ? 'text-success-700 bg-success-50 ring-success-600/20 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-500/20' : 'text-info-700 bg-info-50 ring-info-600/20 dark:bg-info-500/10 dark:text-info-400 dark:ring-info-500/20' }} ring-1 ring-inset">
                                <x-filament::icon icon="{{ $lastMovement->movement_type === 'Promotion' ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrows-right-left' }}" class="h-3.5 w-3.5" />
                                {{ $lastMovement->movement_type }} {{ $lastMovement->effective_date?->diffForHumans() }}
                            </span>
                        @endif
                    </div>

                    {{-- Info Grid --}}
                    <div class="mt-6 grid max-w-5xl grid-cols-2 gap-px overflow-hidden rounded-xl bg-gray-950/5 ring-1 ring-inset ring-gray-950/5 sm:grid-cols-4 dark:bg-white/5 dark:ring-white/10">
                        <dl class="bg-white p-4 dark:bg-gray-900">
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Email</dt>
                            <dd class="mt-1.5 truncate text-sm font-semibold text-gray-950 dark:text-white" title="{{ $employee->email }}">{{ $employee->email ?? '–' }}</dd>
                        </dl>
                        <dl class="bg-white p-4 dark:bg-gray-900">
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Phone</dt>
                            <dd class="mt-1.5 truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $employee->phone_number ?? '–' }}</dd>
                        </dl>
                        <dl class="bg-white p-4 dark:bg-gray-900">
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Education</dt>
                            <dd class="mt-1.5 truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $employee->educationLevel?->name ?? '–' }}</dd>
                        </dl>
                        <dl class="bg-white p-4 dark:bg-gray-900">
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">TIN / Pension</dt>
                            <dd class="mt-1.5 truncate text-sm font-semibold font-mono text-gray-950 dark:text-white">{{ $employee->tin ?? '–' }} / {{ $employee->pension_id ?? '–' }}</dd>
                        </dl>
                    </div>

                    {{-- Leave Balance Strip --}}
                    @if($leaveBalance)
                        @php
                            $leaveTypes = [
                                [
                                    'label'     => 'Annual',
                                    'used'      => (float) $leaveBalance->annual_used,
                                    'entitled'  => (float) $leaveBalance->annual_entitled,
                                    'balance'   => (float) $leaveBalance->annual_balance,
                                    'color'     => 'bg-primary-500',
                                    'textColor' => 'text-primary-600 dark:text-primary-400',
                                    'bgColor'   => 'bg-primary-50 dark:bg-primary-500/10',
                                    'icon'      => 'heroicon-m-sun',
                                ],
                                [
                                    'label'     => 'Sick',
                                    'used'      => (float) $leaveBalance->sick_used,
                                    'entitled'  => (float) $leaveBalance->sick_entitled,
                                    'balance'   => (float) $leaveBalance->sick_balance,
                                    'color'     => 'bg-warning-500',
                                    'textColor' => 'text-warning-600 dark:text-warning-400',
                                    'bgColor'   => 'bg-warning-50 dark:bg-warning-500/10',
                                    'icon'      => 'heroicon-m-heart',
                                ],
                                [
                                    'label'     => 'Maternity',
                                    'used'      => (float) $leaveBalance->maternity_used,
                                    'entitled'  => (float) $leaveBalance->maternity_entitled,
                                    'balance'   => (float) $leaveBalance->maternity_balance,
                                    'color'     => 'bg-pink-500',
                                    'textColor' => 'text-pink-600 dark:text-pink-400',
                                    'bgColor'   => 'bg-pink-50 dark:bg-pink-500/10',
                                    'icon'      => 'heroicon-m-sparkles',
                                ],
                                [
                                    'label'     => 'Field',
                                    'used'      => (float) $leaveBalance->field_used,
                                    'entitled'  => (float) $leaveBalance->field_entitled,
                                    'balance'   => (float) $leaveBalance->field_balance,
                                    'color'     => 'bg-success-500',
                                    'textColor' => 'text-success-600 dark:text-success-400',
                                    'bgColor'   => 'bg-success-50 dark:bg-success-500/10',
                                    'icon'      => 'heroicon-m-map-pin',
                                ],
                            ];
                        @endphp

                        <div class="mt-5">
                            <div class="mb-2.5 flex items-center justify-between">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                    Leave Balance — {{ now()->year }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @foreach($leaveTypes as $lt)
                                    @php
                                        $pct = $lt['entitled'] > 0
                                            ? min(100, round(($lt['used'] / $lt['entitled']) * 100))
                                            : 0;
                                        $isLow = $lt['entitled'] > 0 && ($lt['balance'] / $lt['entitled']) < 0.25;
                                    @endphp
                                    <div class="rounded-xl {{ $lt['bgColor'] }} p-3.5">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5">
                                                <x-filament::icon :icon="$lt['icon']" class="h-3.5 w-3.5 {{ $lt['textColor'] }}" />
                                                <span class="text-[11px] font-semibold uppercase tracking-wider {{ $lt['textColor'] }}">{{ $lt['label'] }}</span>
                                            </div>
                                            @if($isLow && $lt['entitled'] > 0)
                                                <x-filament::icon icon="heroicon-s-exclamation-triangle" class="h-3.5 w-3.5 text-danger-500" title="Low balance" />
                                            @endif
                                        </div>
                                        <div class="mt-2 flex items-baseline gap-1">
                                            <span class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white leading-none">{{ number_format($lt['balance'], 0) }}</span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">/ {{ number_format($lt['entitled'], 0) }} days</span>
                                        </div>
                                        {{-- Progress bar --}}
                                        <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-200/70 dark:bg-white/10">
                                            <div class="h-full rounded-full {{ $isLow ? 'bg-danger-500' : $lt['color'] }} transition-all"
                                                 style="width: {{ $pct }}%"></div>
                                        </div>
                                        <div class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">{{ $lt['used'] }} used this year</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════ --}}
            {{-- TWO-COLUMN LAYOUT                                      --}}
            {{-- ═══════════════════════════════════════════════════════ --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- ─── LEFT COLUMN ──────────────────────────────────── --}}
                <div class="space-y-6 lg:col-span-2">

                    {{-- CONTRACTS TIMELINE ──────────────────────────── --}}
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-m-document-check" class="h-5 w-5 text-gray-400" />
                                <span>Employment Contracts</span>
                            </div>
                        </x-slot>

                        <x-slot name="headerEnd">
                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                {{ $contracts->count() }} Record{{ $contracts->count() !== 1 ? 's' : '' }}
                            </span>
                        </x-slot>

                        <div class="relative">
                            {{-- Timeline spine --}}
                            @if($contracts->count() > 1)
                                <div class="absolute left-[19px] top-8 bottom-8 w-px bg-gray-200 dark:bg-white/10"></div>
                            @endif

                            <div class="space-y-4">
                                @forelse($contracts as $c)
                                    <div class="relative flex gap-4">
                                        {{-- Timeline node --}}
                                        <div class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full ring-1 ring-inset
                                            {{ $c->status === 'Active'
                                                ? 'bg-success-50 text-success-600 ring-success-500/20 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-400/20'
                                                : 'bg-gray-100 text-gray-400 ring-gray-200 dark:bg-gray-800 dark:text-gray-500 dark:ring-gray-700' }}">
                                            <x-filament::icon
                                                icon="{{ $c->status === 'Active' ? 'heroicon-m-check-circle' : 'heroicon-m-clock' }}"
                                                class="h-5 w-5" />
                                        </div>

                                        {{-- Card --}}
                                        <div class="flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition duration-150 hover:shadow-md dark:border-white/10 dark:bg-gray-800/50 {{ $c->status === 'Active' ? 'border-l-2 border-l-success-500 dark:border-l-success-400' : '' }}">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-sm font-semibold text-gray-950 dark:text-white">
                                                            {{ $c->contractType?->name ?? 'Standard Contract' }}
                                                        </span>
                                                        <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider
                                                            {{ $c->status === 'Active'
                                                                ? 'bg-success-50 text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-500/20'
                                                                : 'bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700' }}">
                                                            {{ $c->status }}
                                                        </span>
                                                    </div>
                                                    <div class="mt-1.5 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                        <x-filament::icon icon="heroicon-m-calendar" class="h-3.5 w-3.5" />
                                                        <span>{{ $c->start_date?->format('d M Y') }} → {{ $c->end_date?->format('d M Y') ?? 'Open Ended' }}</span>
                                                    </div>
                                                </div>
                                                <div class="shrink-0 text-left sm:text-right">
                                                    <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Base Salary</div>
                                                    <div class="mt-0.5 text-base font-bold tabular-nums text-gray-950 dark:text-white">
                                                        @if($c->salary)
                                                            {{ number_format($c->salary, 2) }}
                                                            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">ETB</span>
                                                        @else
                                                            –
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center dark:border-gray-700">
                                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-800">
                                            <x-filament::icon icon="heroicon-o-document-text" class="h-6 w-6 text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-300">No contracts on file</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Contract details will appear here once added.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </x-filament::section>

                    {{-- TRAINING TIMELINE ───────────────────────────── --}}
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-m-academic-cap" class="h-5 w-5 text-gray-400" />
                                <span>Training & Certifications</span>
                            </div>
                        </x-slot>

                        <x-slot name="headerEnd">
                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                {{ $trainings->count() }} Training{{ $trainings->count() !== 1 ? 's' : '' }}
                            </span>
                        </x-slot>

                        <div class="relative">
                            @if($trainings->count() > 1)
                                <div class="absolute left-[19px] top-8 bottom-8 w-px bg-gray-200 dark:bg-white/10"></div>
                            @endif

                            <div class="space-y-4">
                                @forelse($trainings as $t)
                                    <div class="relative flex gap-4">
                                        {{-- Timeline node --}}
                                        <div class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 ring-1 ring-inset ring-primary-500/20 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-400/20">
                                            <x-filament::icon icon="heroicon-m-academic-cap" class="h-5 w-5" />
                                        </div>

                                        {{-- Card --}}
                                        <div class="flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition duration-150 hover:shadow-md dark:border-white/10 dark:bg-gray-800/50">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $t->title }}</div>
                                                    <div class="mt-1.5 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                        <x-filament::icon icon="heroicon-m-building-office" class="h-3.5 w-3.5" />
                                                        <span class="truncate">{{ $t->institution ?? 'Internal Training' }}</span>
                                                    </div>
                                                </div>
                                                <span class="shrink-0 inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700">
                                                    {{ $t->start_date?->format('M Y') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center dark:border-gray-700">
                                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-800">
                                            <x-filament::icon icon="heroicon-o-academic-cap" class="h-6 w-6 text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-300">No training history</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Training records will appear here once added.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </x-filament::section>

                    {{-- CAREER HISTORY (Movements) ───────────────────────── --}}
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-m-arrows-right-left" class="h-5 w-5 text-gray-400" />
                                <span>Career History</span>
                            </div>
                        </x-slot>

                        <x-slot name="headerEnd">
                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                {{ $movements->count() }} Record{{ $movements->count() !== 1 ? 's' : '' }}
                            </span>
                        </x-slot>

                        <div class="relative">
                            @if($movements->count() > 1)
                                <div class="absolute left-[19px] top-8 bottom-8 w-px bg-gray-200 dark:bg-white/10"></div>
                            @endif

                            <div class="space-y-4">
                                @forelse($movements as $m)
                                    <div class="relative flex gap-4">
                                        {{-- Timeline node --}}
                                        @php
                                            $mColor = match($m->movement_type) {
                                                'Promotion' => 'bg-success-50 text-success-600 ring-success-500/20 dark:bg-success-500/10 dark:text-success-400',
                                                'Demotion'  => 'bg-danger-50 text-danger-600 ring-danger-500/20 dark:bg-danger-500/10 dark:text-danger-400',
                                                default     => 'bg-info-50 text-info-600 ring-info-500/20 dark:bg-info-500/10 dark:text-info-400',
                                            };
                                            $mIcon = match($m->movement_type) {
                                                'Promotion' => 'heroicon-m-arrow-trending-up',
                                                'Demotion'  => 'heroicon-m-arrow-trending-down',
                                                default     => 'heroicon-m-arrows-right-left',
                                            };
                                        @endphp

                                        <div class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full ring-1 ring-inset {{ $mColor }}">
                                            <x-filament::icon :icon="$mIcon" class="h-4 w-4" />
                                        </div>

                                        {{-- Card --}}
                                        <div class="flex-1 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition duration-150 hover:shadow-md dark:border-white/10 dark:bg-gray-800/50">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-sm font-semibold text-gray-950 dark:text-white">
                                                            {{ $m->movement_type }}
                                                        </span>
                                                    </div>

                                                    <div class="mt-2 flex flex-col gap-1 text-sm text-gray-600 dark:text-gray-300">
                                                        @if($m->toPosition)
                                                            <div class="flex items-center gap-1.5 text-xs">
                                                                <x-filament::icon icon="heroicon-m-briefcase" class="h-3.5 w-3.5 text-gray-400" />
                                                                <span class="line-through opacity-70">{{ $m->fromPosition?->title }}</span>
                                                                <x-filament::icon icon="heroicon-m-arrow-right" class="h-3 w-3 text-gray-400 mx-0.5" />
                                                                <span class="font-medium text-gray-900 dark:text-white">{{ $m->toPosition->title }}</span>
                                                            </div>
                                                        @endif
                                                        @if($m->toDepartment)
                                                            <div class="flex items-center gap-1.5 text-xs">
                                                                <x-filament::icon icon="heroicon-m-building-office-2" class="h-3.5 w-3.5 text-gray-400" />
                                                                <span class="line-through opacity-70">{{ $m->fromDepartment?->name }}</span>
                                                                <x-filament::icon icon="heroicon-m-arrow-right" class="h-3 w-3 text-gray-400 mx-0.5" />
                                                                <span class="font-medium text-gray-900 dark:text-white">{{ $m->toDepartment->name }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="shrink-0 text-left sm:text-right">
                                                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700">
                                                        {{ $m->effective_date?->format('d M Y') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center dark:border-gray-700">
                                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-800">
                                            <x-filament::icon icon="heroicon-o-arrows-right-left" class="h-6 w-6 text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-300">No career movements</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Promotions and transfers will appear here.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </x-filament::section>
                </div>

                {{-- ─── RIGHT COLUMN ─────────────────────────────────── --}}
                <div class="space-y-6">

                    {{-- DEPENDENTS ──────────────────────────────────── --}}
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-m-users" class="h-5 w-5 text-gray-400" />
                                <span>Family & Dependents</span>
                            </div>
                        </x-slot>

                        <x-slot name="headerEnd">
                            @if($dependents->count())
                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                    {{ $dependents->count() }}
                                </span>
                            @endif
                        </x-slot>

                        <div class="space-y-1 -mx-2">
                            @forelse($dependents as $d)
                                <div class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                        {{ strtoupper(substr($d->full_name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $d->full_name }}</div>
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ $d->relationship }}</div>
                                    </div>
                                    @if($d->is_beneficiary)
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-danger-50 text-danger-500 dark:bg-danger-500/10 dark:text-danger-400" title="Registered Beneficiary">
                                            <x-filament::icon icon="heroicon-s-heart" class="h-3.5 w-3.5" />
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="px-2 py-8 text-center">
                                    <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-800">
                                        <x-filament::icon icon="heroicon-o-users" class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-300">No dependents listed</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dependents will show here once added.</p>
                                </div>
                            @endforelse
                        </div>
                    </x-filament::section>

                    {{-- ACTIVE DELEGATIONS ──────────────────────────── --}}
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-m-users" class="h-5 w-5 text-gray-400" />
                                <span>Active Delegations</span>
                            </div>
                        </x-slot>

                        <x-slot name="headerEnd">
                            @if($activeDelegationsGiven->count() || $activeDelegationsReceived->count())
                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">
                                    {{ $activeDelegationsGiven->count() + $activeDelegationsReceived->count() }} active
                                </span>
                            @endif
                        </x-slot>

                        <div class="space-y-4">
                            @if($activeDelegationsGiven->isEmpty() && $activeDelegationsReceived->isEmpty())
                                <div class="px-2 py-6 text-center">
                                    <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-800">
                                        <x-filament::icon icon="heroicon-o-user-minus" class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-300">No active delegations</p>
                                </div>
                            @endif

                            @foreach($activeDelegationsReceived as $del)
                                <div class="rounded-xl border border-warning-200 bg-warning-50/50 p-4 dark:border-warning-500/20 dark:bg-warning-500/5">
                                    <div class="flex items-start gap-3">
                                        <x-filament::icon icon="heroicon-m-arrow-down-tray" class="mt-0.5 h-5 w-5 text-warning-500 shrink-0" />
                                        <div>
                                            <div class="text-sm font-semibold text-gray-950 dark:text-white">Acting on behalf of {{ $del->delegator?->full_name }}</div>
                                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $del->subject }}</div>
                                            <div class="mt-2 text-xs font-medium text-warning-600 dark:text-warning-500">
                                                Until {{ $del->end_date ? $del->end_date->format('M d, Y') : 'further notice' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @foreach($activeDelegationsGiven as $del)
                                <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-white/10 dark:bg-white/5">
                                    <div class="flex items-start gap-3">
                                        <x-filament::icon icon="heroicon-m-arrow-up-tray" class="mt-0.5 h-5 w-5 text-gray-400 shrink-0" />
                                        <div>
                                            <div class="text-sm font-semibold text-gray-950 dark:text-white">Duties delegated to {{ $del->delegate?->full_name }}</div>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $del->subject }}</div>
                                            <div class="mt-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                                                Until {{ $del->end_date ? $del->end_date->format('M d, Y') : 'further notice' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>

                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>