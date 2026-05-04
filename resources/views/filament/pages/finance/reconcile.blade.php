<x-filament-panels::page>
    <div class="space-y-6">

        {{-- ══════════════════════════════════════════════════════════════════
             SECTION 1: Start a New Reconciliation
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5 shadow-sm overflow-hidden">

            {{-- Panel header --}}
            <div class="border-b border-gray-100 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    Start a New Reconciliation
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Select a bank account and enter the closing balance from your bank statement.
                    Clicking <strong class="text-gray-700 dark:text-gray-300">Start Reconciling</strong>
                    will create a new reconciliation record and take you to a workspace where you can
                    add outstanding items (deposits in transit, unpresented cheques, etc.) and clear them off.
                </p>
            </div>

            <form wire:submit.prevent="startReconciling" class="p-6 space-y-6">

                {{-- Account selector --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Bank Account <span class="text-danger-500">*</span>
                    </label>
                    <x-filament::input.wrapper class="max-w-xl">
                        <x-filament::input.select
                            wire:model.live="bank_account_id"
                            id="bank_account_id"
                            class="w-full"
                        >
                            <option value="">— Select a bank account —</option>
                            @foreach($bankAccounts as $id => $label)
                                <option value="{{ $id }}" @selected($this->bank_account_id == $id)>{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    @error('bank_account_id')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- In-progress warning --}}
                @if($inProgressReconciliation)
                <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10 px-4 py-3">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"/>
                    <div class="text-sm text-amber-800 dark:text-amber-300">
                        <strong>An in-progress reconciliation already exists for this account:</strong>
                        {{ $inProgressReconciliation->reference }}
                        ({{ $inProgressReconciliation->statement_date?->format('d M Y') }}).
                        Clicking <strong>Start Reconciling</strong> will resume it,
                        or <a href="{{ route('filament.admin.resources.finance.bank.reconciliations.edit', $inProgressReconciliation) }}"
                               class="underline font-semibold">go directly →</a>
                    </div>
                </div>
                @endif

                {{-- Balances + Date --}}
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">

                    {{-- GL Balance — auto-computed from ledger (read-only) --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Book Balance (GL) <span class="text-xs text-gray-400 dark:text-gray-500">(auto)</span>
                        </label>
                        <div class="flex h-10 items-center rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 font-mono text-sm text-gray-700 dark:text-gray-200">
                            @if($computed_gl_balance !== null)
                                {{ number_format($computed_gl_balance, 2) }}
                            @else
                                <span class="text-gray-400 dark:text-gray-500">— select account & date —</span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            Fetched automatically from your General Ledger as of the statement date.
                        </p>
                    </div>

                    {{-- Ending balance --}}
                    <div>
                        <label for="ending_balance" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Ending Balance (Statement) <span class="text-danger-500">*</span>
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" id="ending_balance"
                                wire:model.live="ending_balance" step="0.01" placeholder="0.00" required />
                        </x-filament::input.wrapper>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            The closing balance shown on your bank statement.
                        </p>
                        @error('ending_balance')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Statement end date --}}
                    <div>
                        <label for="ending_date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Statement End Date <span class="text-danger-500">*</span>
                        </label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" id="ending_date"
                                wire:model.live="ending_date" required />
                        </x-filament::input.wrapper>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            The closing date printed on your bank statement.
                        </p>
                        @error('ending_date')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- Difference preview --}}
                @if($this->ending_balance !== null && $this->computed_gl_balance !== null)
                @php $diff = (float)($this->ending_balance ?? 0) - (float)($this->computed_gl_balance ?? 0); @endphp
                <div class="flex items-center gap-3 rounded-lg border px-4 py-3 text-sm
                    {{ abs($diff) < 0.01
                        ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-300'
                        : 'border-blue-200 bg-blue-50 dark:border-blue-500/30 dark:bg-blue-500/10 text-blue-800 dark:text-blue-300' }}">
                    <x-filament::icon
                        :icon="abs($diff) < 0.01 ? 'heroicon-o-check-circle' : 'heroicon-o-information-circle'"
                        class="h-5 w-5 shrink-0"
                    />
                    @if(abs($diff) < 0.01)
                        <span><strong>Accounts balance.</strong> No difference — this reconciliation may complete automatically.</span>
                    @else
                        <span>
                            <strong>Preliminary difference:</strong>
                            <span class="font-mono ml-1">{{ number_format($diff, 2) }}</span>
                            — you will clear individual transactions on the next screen to resolve this.
                        </span>
                    @endif
                </div>
                @endif

                {{-- Submit --}}
                <div class="flex justify-end pt-2">
                    <x-filament::button type="submit" size="lg" color="primary"
                        wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed">
                        <span wire:loading.remove class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-play-circle" class="h-5 w-5"/>
                            Start Reconciling
                        </span>
                        <span wire:loading class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 3 12 3 12h1z"></path>
                            </svg>
                            Starting…
                        </span>
                    </x-filament::button>
                </div>

            </form>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             SECTION 2: Full Reconciliation List (Filament Table)
        ══════════════════════════════════════════════════════════════════ --}}
        {{ $this->table }}

    </div>
</x-filament-panels::page>
