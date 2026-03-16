<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="timesheetGrid({
        statePath: '{{ $getStatePath() }}',
        initialState: @js($getState()),
        month: $wire.$entangle('data.month'),
        year: $wire.$entangle('data.year'),
        projects: {{ $projects->toJson() }},
        leaveTypes: {{ $leaveTypes->toJson() }},
        locations: {{ $locations->toJson() }}
    })" 
    class="relative border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 transition-all duration-300 w-full font-sans shadow-sm"
    :class="{ 'opacity-50 pointer-events-none cursor-not-allowed': !month || !year }"
    >
        <!-- Excel Legend / Tooltip info -->
        <div class="bg-gray-100 dark:bg-gray-900 px-3 py-2 border-b-2 border-gray-300 dark:border-gray-700 flex justify-between items-center select-none">
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-black uppercase text-gray-500 dark:text-gray-400 tracking-widest border-r border-gray-300 dark:border-gray-700 pr-4">Workbook View</span>
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-4 h-4 bg-blue-600 text-white rounded-sm">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <span class="text-[9px] font-bold text-gray-600 dark:text-gray-400 italic">Tip: Click the blue [+] on any date to log specific work accomplishments or locations.</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div x-show="!_isWaitingForServer" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                <div x-show="_isWaitingForServer" class="w-2 h-2 rounded-full bg-blue-500 animate-ping"></div>
                <span class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase" x-text="_isWaitingForServer ? 'Refreshing Data...' : 'Live Spreadsheet Mode'"></span>
            </div>
        </div>
 
        <!-- Loading Overlay -->
        <div x-show="_isWaitingForServer" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="absolute inset-0 z-[100] bg-gray-50/60 dark:bg-gray-950/60 backdrop-blur-[1px] flex items-center justify-center select-none cursor-wait"
        >
            <div class="flex flex-col items-center gap-2">
                <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-[10px] font-black uppercase text-blue-700 dark:text-blue-400 tracking-widest animate-pulse">Loading Workbook...</span>
            </div>
        </div>

        <div class="overflow-x-auto overflow-y-auto custom-scrollbar max-h-[700px]">
            <table class="w-full text-[11px] text-left border-collapse table-auto">
                <thead class="sticky top-0 z-20">
                    <!-- Excel-style Main Headers -->
                    <tr class="bg-gray-200 dark:bg-gray-800 border-b-2 border-gray-400 dark:border-gray-700">
                        <th rowspan="2" class="p-2 border-r-2 border-gray-400 dark:border-gray-700 bg-gray-200 dark:bg-gray-800 sticky left-0 z-30 min-w-[80px] font-black text-gray-700 dark:text-gray-200 uppercase tracking-tighter shadow-[2px_0_0_0_rgba(0,0,0,0.1)] dark:shadow-[2px_0_0_0_rgba(255,255,255,0.05)]">Code</th>
                        <th rowspan="2" class="p-2 border-r-2 border-gray-400 dark:border-gray-700 bg-gray-200 dark:bg-gray-800 sticky left-[80px] z-30 min-w-[220px] font-black text-gray-700 dark:text-gray-200 uppercase tracking-tighter shadow-[2px_0_0_0_rgba(0,0,0,0.1)] dark:shadow-[2px_0_0_0_rgba(255,255,255,0.05)]">Project Name / Category</th>
                        <th rowspan="2" class="p-2 border-r-2 border-gray-400 dark:border-gray-700 bg-gray-300 dark:bg-gray-700 text-center min-w-[60px] font-black text-gray-800 dark:text-white">M.Total</th>
                        <th colspan="31" class="p-1 text-center bg-gray-50 dark:bg-gray-900 font-black border-b border-gray-400 dark:border-gray-800 text-gray-500 dark:text-gray-400 uppercase text-[9px] tracking-[0.3em]">Daily Hours Spreadsheet</th>
                    </tr>
                    <tr class="bg-gray-100 dark:bg-gray-900 divide-x border-b-2 border-gray-400 dark:border-gray-700 text-center">
                        <template x-for="dayObj in daysInMonthList" :key="dayObj.day">
                            <th class="p-1 border-r border-gray-200 dark:border-gray-800 min-w-[34px] font-black relative group bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-100" 
                                :class="{ 
                                    'bg-red-100/40 dark:bg-rose-900/40 !text-red-600 dark:!text-rose-400': dayObj.isWeekend && !dayObj.isHoliday,
                                    'bg-amber-500/20 dark:bg-amber-400/30 !text-amber-700 dark:!text-amber-400 ring-inset ring-2 ring-amber-500/30': dayObj.isHoliday
                                }"
                                :title="dayObj.holidayName || ''"
                            >
                                <div x-text="dayObj.dayName" class="text-[8px] uppercase opacity-70"></div>
                                <div class="flex items-center justify-center gap-0.5">
                                    <div x-text="dayObj.day" class="text-[10px] font-black"></div>
                                    <template x-if="dayObj.isHoliday">
                                        <span class="text-[7px] bg-amber-600 text-white px-0.5 rounded-sm font-black leading-none py-0.5 shadow-sm">HOL</span>
                                    </template>
                                </div>
                                <template x-if="dayObj.isHoliday">
                                    <div class="h-1 w-1 bg-current rounded-full mx-auto mt-0.5"></div>
                                </template>
                                
                                <button @click="openDetails(dayObj.day)" 
                                    class="absolute -top-1 -right-1 p-0.5 bg-blue-600 text-white opacity-0 group-hover:opacity-100 transition-all z-10 shadow-sm hover:scale-110"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                                </button>

                                <div x-show="state.daily_details[dayObj.day]?.description || state.daily_details[dayObj.day]?.location_id" 
                                    class="absolute bottom-0 left-0 w-full h-[3px] bg-blue-500 shadow-[0_-1px_3px_rgba(59,130,246,0.5)]"
                                ></div>
                            </th>
                        </template>
                        <template x-if="daysCount < 31">
                            <template x-for="i in (31 - daysCount)">
                                <th class="p-1 border-r border-gray-200 dark:border-gray-800 bg-gray-200/50 dark:bg-gray-800/50 min-w-[34px]"></th>
                            </template>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y-2 border-b-2 border-gray-400 dark:border-gray-800">
                    <!-- Projects Section -->
                    <template x-for="project in projects" :key="'project-'+project.id">
                        <tr class="divide-x border-gray-200 dark:border-gray-800 group hover:bg-blue-500/5 dark:hover:bg-blue-400/10 transition-colors even:bg-gray-50/50 dark:even:bg-white/5">
                            <td class="p-2 sticky left-0 z-10 bg-white dark:bg-gray-950 group-hover:bg-inherit font-mono text-[10px] text-gray-400 dark:text-gray-500 border-r-2 border-gray-400 dark:border-gray-800 shadow-[2px_0_0_0_rgba(0,0,0,0.05)] dark:shadow-[2px_0_0_0_rgba(255,255,255,0.02)]" x-text="project.project_code || '-'"></td>
                            <td class="p-2 sticky left-[80px] z-10 bg-white dark:bg-gray-950 group-hover:bg-inherit font-bold text-gray-700 dark:text-gray-100 border-r-2 border-gray-400 dark:border-gray-800 shadow-[2px_0_0_0_rgba(0,0,0,0.05)] dark:shadow-[2px_0_0_0_rgba(255,255,255,0.02)] whitespace-nowrap overflow-hidden text-ellipsis" x-text="project.project_name"></td>
                            <td class="p-2 text-center font-black bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white border-r-2 border-gray-400 dark:border-gray-800" x-text="projectTotal(project.id)"></td>
                            
                            <template x-for="dayObj in daysInMonthList" :key="dayObj.day">
                                <td class="p-0 border-r border-gray-100 dark:border-gray-900 relative min-w-[34px]" 
                                    :class="{ 
                                        'bg-red-500/[0.03] dark:bg-rose-400/[0.05]': dayObj.isWeekend && !dayObj.isHoliday,
                                        'bg-amber-500/[0.08] dark:bg-amber-400/[0.1]': dayObj.isHoliday
                                    }"
                                >
                                    <input type="number" min="0" max="24" step="0.5"
                                        class="w-full h-8 p-0 text-center border-0 focus:ring-2 focus:ring-blue-500 focus:z-10 bg-transparent transition-all outline-none rounded-none text-xs text-gray-700 dark:text-gray-100 font-bold placeholder-transparent hover:bg-gray-100 dark:hover:bg-gray-800 shadow-none"
                                        x-model.number="state.projects[project.id][dayObj.day]"
                                        @input="handleCellChange($event)"
                                        @focus="$el.select()"
                                        placeholder="0"
                                    />
                                </td>
                            </template>
                            <template x-if="daysCount < 31">
                                <template x-for="i in (31 - daysCount)">
                                    <td class="bg-gray-100/30 dark:bg-gray-900/10 border-r border-gray-100 dark:border-gray-900 opacity-20"></td>
                                </template>
                            </template>
                        </tr>
                    </template>

                    <!-- Aggregated Unassigned Row -->
                    <tr class="divide-x border-gray-200 dark:border-gray-800 hover:bg-emerald-500/5 dark:hover:bg-emerald-400/10 transition-colors bg-emerald-50/10 dark:bg-emerald-950/20">
                        <td class="p-2 sticky left-0 z-10 bg-inherit font-mono text-[10px] text-emerald-600 dark:text-emerald-400 border-r-2 border-gray-400 dark:border-gray-800 font-black italic shadow-[2px_0_0_0_rgba(16,185,129,0.05)]">GEN</td>
                        <td class="p-2 sticky left-[80px] z-10 bg-inherit font-black text-gray-600 dark:text-gray-300 border-r-2 border-gray-400 dark:border-gray-800 italic shadow-[2px_0_0_0_rgba(16,185,129,0.05)]">General / Maintenance Work</td>
                        <td class="p-2 text-center font-black bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border-r-2 border-gray-400 dark:border-gray-800" x-text="sumRow(state.projects['null'])"></td>
                        
                        <template x-for="dayObj in daysInMonthList" :key="dayObj.day">
                            <td class="p-0 border-r border-gray-100 dark:border-gray-900 relative min-w-[34px]" 
                                :class="dayObj.isWeekend ? 'bg-red-500/[0.03] dark:bg-rose-400/[0.05]' : ''"
                            >
                                <input type="number" min="0" max="24" step="0.5"
                                    class="w-full h-8 p-0 text-center border-0 focus:ring-2 focus:ring-emerald-500 focus:z-10 bg-transparent transition-all outline-none rounded-none text-xs text-emerald-800 dark:text-emerald-100 font-black placeholder-transparent hover:bg-emerald-500/10"
                                    x-model.number="state.projects['null'][dayObj.day]"
                                    @input="handleCellChange($event)"
                                    @focus="$el.select()"
                                    placeholder="0"
                                />
                            </td>
                        </template>
                        <template x-if="daysCount < 31">
                            <template x-for="i in (31 - daysCount)">
                                <td class="bg-gray-100/30 dark:bg-gray-900/10 border-r border-gray-100 dark:border-gray-900 opacity-20"></td>
                            </template>
                        </template>
                    </tr>

                    <!-- Footer Calculations (The "Summary Row") -->
                    <tr class="bg-gray-200 dark:bg-gray-800 font-black text-gray-800 dark:text-gray-100 border-t-4 border-gray-300 dark:border-gray-700 divide-x-2 border-gray-300 dark:border-gray-700">
                        <td colspan="2" class="p-2 text-right bg-inherit sticky left-0 z-10 border-r-2 border-gray-400 text-[10px] uppercase tracking-widest">Total Daily Work Units</td>
                        <td class="p-2 text-center text-blue-700 dark:text-blue-400 border-r-2 border-gray-400 dark:border-gray-700 bg-gray-300 dark:bg-gray-700" x-text="formatNum(totalWorkHours())"></td>
                        <template x-for="dayObj in daysInMonthList" :key="'pw-'+dayObj.day">
                            <td class="p-1 text-center text-[10px] opacity-100" x-text="formatNum(dayTotalWorkHours(dayObj.day)) || ''"></td>
                        </template>
                        <template x-if="daysCount < 31">
                            <template x-for="i in (31 - daysCount)">
                                <td class="bg-gray-300 dark:bg-gray-700 border-r border-gray-700"></td>
                            </template>
                        </template>
                    </tr>

                    <!-- Absence / Off-time Section Header -->
                    <tr class="bg-amber-100 dark:bg-amber-950/40 border-y-2 border-amber-300 dark:border-amber-900/60">
                        <td colspan="34" class="p-1.5 px-3 font-black text-amber-800 dark:text-amber-400 text-[10px] uppercase tracking-tighter">Verified Absences (HR Leave Records)</td>
                    </tr>

                    <!-- Leaves Rows (Excel Style) -->
                    <template x-for="leave in leaveTypes" :key="'leave-'+leave.id">
                        <tr class="divide-x border-gray-200 dark:border-gray-900 text-[10px] bg-white dark:bg-gray-950 even:bg-amber-50/20 dark:even:bg-amber-900/5">
                            <td class="p-2 sticky left-0 z-10 bg-inherit font-mono text-gray-400 border-r-2 border-gray-400 dark:border-gray-800 italic">ABS</td>
                            <td class="p-2 sticky left-[80px] z-10 bg-inherit font-bold text-gray-500 dark:text-gray-400 border-r-2 border-gray-400 dark:border-gray-800 whitespace-nowrap overflow-hidden text-ellipsis italic" x-text="leave.name"></td>
                            <td class="p-2 text-center font-black bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400 border-r-2 border-gray-400 dark:border-gray-800" x-text="leaveTotal(leave.id)"></td>
                            
                            <template x-for="dayObj in daysInMonthList" :key="dayObj.day">
                                <td class="p-0 border-r border-gray-100 dark:border-gray-900 relative min-w-[34px] text-center text-[10px] text-amber-600 dark:text-amber-500 font-black select-none">
                                    <div x-show="state.leaves[leave.id][dayObj.day] > 0" class="flex flex-col items-center justify-center h-8 leading-none opacity-80">
                                        <span class="text-[7px] uppercase font-bold">Hrs</span>
                                        <span x-text="state.leaves[leave.id][dayObj.day]"></span>
                                    </div>
                                </td>
                            </template>
                            <template x-if="daysCount < 31">
                                <template x-for="i in (31 - daysCount)">
                                    <td class="bg-gray-50 dark:bg-gray-900 border-r border-gray-100 dark:border-gray-900 opacity-20"></td>
                                </template>
                            </template>
                        </tr>
                    </template>

                    <!-- The Grand "Bottom Line" -->
                    <tr class="bg-gray-950 dark:bg-black text-white font-black border-t-2 border-gray-800 divide-x-2 border-gray-800 shadow-2xl">
                        <td colspan="2" class="p-4 text-right bg-inherit sticky left-0 z-10 border-r-2 border-gray-800 uppercase text-[11px] tracking-[0.4em] text-gray-500">Net Monthly Summation</td>
                        <td class="p-4 text-center text-emerald-400 bg-gray-950 border-r-2 border-gray-800 text-[18px] font-black underline decoration-emerald-500/30 underline-offset-8" x-text="formatNum(grandTotal())"></td>
                        <template x-for="dayObj in daysInMonthList" :key="'gt-'+dayObj.day">
                            <td class="p-1 text-center text-[11px] text-emerald-400 font-bold" x-text="formatNum(dayGrandTotal(dayObj.day)) || ''"></td>
                        </template>
                        <template x-if="daysCount < 31">
                            <template x-for="i in (31 - daysCount)">
                                <td class="bg-gray-900 border-r border-gray-800 text-center text-gray-700 font-mono opacity-10">---</td>
                            </template>
                        </template>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Excel-style Task Entry Dialog -->
        <div x-show="editingDay" 
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-950/80 backdrop-blur-sm" 
            style="display: none;"
        >
            <div @click.away="editingDay = null" class="bg-white dark:bg-gray-900 shadow-2xl w-full max-w-md border-2 border-gray-400 dark:border-gray-700 rounded-none overflow-hidden text-gray-900 dark:text-gray-100">
                <div class="px-4 py-3 bg-blue-700 dark:bg-indigo-900 text-white flex justify-between items-center font-black text-xs uppercase tracking-widest">
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 bg-white/20 flex items-center justify-center rounded-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </div>
                        <span>Date Log Meta &bull; Day <span x-text="editingDay"></span></span>
                    </div>
                    <button @click="closeDetails()" class="hover:bg-red-600 p-1 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-6 space-y-6">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Assignment Location</label>
                        <select x-model="state.daily_details[editingDay].location_id" 
                            class="w-full h-10 rounded-none border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-[11px] font-bold text-gray-700 dark:text-gray-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 outline-none transition-all">
                            <option :value="null">-- Standard Site --</option>
                            <template x-for="loc in (locations || [])" :key="loc.id">
                                <option :value="loc.id" x-text="loc.location_name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Task Details & Commentary</label>
                        <textarea x-model="state.daily_details[editingDay].description" rows="5" 
                            class="w-full rounded-none border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-[11px] p-3 font-medium text-gray-700 dark:text-gray-100 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-600 outline-none transition-all shadow-inner" 
                            placeholder="What specific tasks were completed today?"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-950 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3 text-gray-900 dark:text-gray-100">
                    <button @click="closeDetails()" 
                        class="px-8 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-300 text-[10px] font-black uppercase tracking-widest transition-all">Discard</button>
                    <button @click="closeDetails()" 
                        class="px-10 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-blue-500/20 active:scale-95">Commit Log</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 12px; height: 12px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; border-left: 1px solid #e2e8f0; }
        .dark .custom-scrollbar::-webkit-scrollbar-track { background: #0f172a; border-color: #1e293b; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #94a3b8; border: 3px solid #f8fafc; border-radius: 6px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-color: #0f172a; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748b; }
        
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        
        .sticky { background-color: inherit !important; }
        [x-cloak] { display: none !important; }

        /* Excel grid line behavior */
        tr:hover td { border-color: #94a3b8 !important; }
        .dark tr:hover td { border-color: #64748b !important; }
        
        /* Focus styles for the workbook */
        input:focus { background-color: rgba(59, 130, 246, 0.05) !important; }
        .dark input:focus { background-color: rgba(59, 130, 246, 0.1) !important; }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('timesheetGrid', ({ statePath, initialState, month, year, projects, leaveTypes, locations }) => {
                const ensureObject = (val) => (val && typeof val === 'object' && !Array.isArray(val)) ? val : {};
                
                // Build a clean state from whatever the server gave us
                let state = {
                    projects: ensureObject(initialState?.projects),
                    leaves: ensureObject(initialState?.leaves),
                    daily_details: ensureObject(initialState?.daily_details),
                    holidays: ensureObject(initialState?.holidays),
                };

                if (!state.projects['null']) state.projects['null'] = {};
                projects.forEach(p => { if (!state.projects[p.id]) state.projects[p.id] = {}; });
                leaveTypes.forEach(l => { if (!state.leaves[l.id]) state.leaves[l.id] = {}; });

                return {
                    state, month, year, projects, leaveTypes, locations, editingDay: null,
                    _syncTimer: null,
                    _isSyncing: false,
                    _isWaitingForServer: false,
                    _lastMonth: null,
                    _lastYear: null,
 
                    init() {
                        this._lastMonth = this.month;
                        this._lastYear = this.year;
 
                        // Listen for server-side state changes (e.g. month/year change or initial load)
                        this.$wire.$watch(statePath, (newVal) => {
                            if (!newVal || typeof newVal !== 'object') return;
                            
                            const periodChanged = (this._lastMonth != this.month || this._lastYear != this.year);
 
                            // If we fundamental changed period OR we were waiting for an update, 
                            // we ALWAYS accept the server update and release the lock.
                            if (periodChanged || this._isWaitingForServer) {
                                this._isWaitingForServer = false;
                                this._isSyncing = false; 
                            } else if (this._isSyncing) {
                                // Standard lock to prevent jumping while typing in a cell
                                return;
                            }
 
                            // Update our trackers
                            this._lastMonth = this.month;
                            this._lastYear = this.year;
 
                            // Deep copy to prevent reference leakage
                            const cleanData = JSON.parse(JSON.stringify(newVal));
                            
                            this.state.projects = ensureObject(cleanData.projects);
                            this.state.leaves = ensureObject(cleanData.leaves);
                            this.state.daily_details = ensureObject(cleanData.daily_details);
                            this.state.holidays = ensureObject(cleanData.holidays);
                            
                            if (!this.state.projects['null']) this.state.projects['null'] = {};
                            projects.forEach(p => { if (!this.state.projects[p.id]) this.state.projects[p.id] = {}; });
                            leaveTypes.forEach(l => { if (!this.state.leaves[l.id]) this.state.leaves[l.id] = {}; });
                        });

                        // Watch for Month/Year changes
                        this.$watch('month', (val) => {
                            clearTimeout(this._syncTimer);
                            this._isWaitingForServer = true; // LOCK UI
                            this._isSyncing = false;
                            this.resetAllData(); 
                            this.pruneInvalidDays();
                        });
                        this.$watch('year', (val) => {
                            clearTimeout(this._syncTimer);
                            this._isWaitingForServer = true; // LOCK UI
                            this._isSyncing = false;
                            this.resetAllData();
                            this.pruneInvalidDays();
                        });
                    },
 
                    resetAllData() {
                        // Hard reset of all entered hours when period changes
                        // This ensures one month's data NEVER bleeds into another
                        this.state = {
                            projects: { 'null': {} },
                            leaves: {},
                            daily_details: {},
                            holidays: {}
                        };
                        this.projects.forEach(p => { this.state.projects[p.id] = {}; });
                        this.leaveTypes.forEach(l => { this.state.leaves[l.id] = {}; });
                    },

                    pruneInvalidDays() {
                        const maxDays = this.daysCount;
                        const prune = (obj) => {
                            if (!obj || typeof obj !== 'object') return;
                            Object.keys(obj).forEach(key => {
                                if (parseInt(key) > maxDays) delete obj[key];
                            });
                        };
 
                        // Prune projects
                        Object.values(this.state.projects).forEach(dayObj => prune(dayObj));
                        // Prune leaves
                        Object.values(this.state.leaves).forEach(dayObj => prune(dayObj));
                        // Prune daily details
                        prune(this.state.daily_details);
                        
                        // IMPORTANT: We do NOT call syncToServer here anymore.
                        // Pruning is just a local UI cleanup while we wait for the server refresh.
                    },

                    /**
                     * Sync the Alpine state to Livewire so it's available on form submit.
                     * Debounced to avoid excessive network calls.
                     */
                    syncToServer() {
                        clearTimeout(this._syncTimer);
                        this._isSyncing = true;
                        this._syncTimer = setTimeout(() => {
                            this.$wire.set(statePath, JSON.parse(JSON.stringify(this.state)));
                            // After a short delay, allow the watch to resume
                            setTimeout(() => { this._isSyncing = false; }, 1000);
                        }, 300);
                    },

                    /**
                     * Force an immediate sync (no debounce). For use right before save.
                     */
                    syncNow() {
                        clearTimeout(this._syncTimer);
                        this.$wire.set(statePath, JSON.parse(JSON.stringify(this.state)));
                    },

                    get daysCount() {
                        let y = parseInt(this.year) || new Date().getFullYear();
                        let m = parseInt(this.month) || new Date().getMonth() + 1;
                        return new Date(y, m, 0).getDate();
                    },
                    get daysInMonthList() {
                        let list = [];
                        let y = parseInt(this.year) || new Date().getFullYear();
                        let m = parseInt(this.month) || new Date().getMonth() + 1;
                        let count = this.daysCount;
                        for (let d = 1; d <= count; d++) {
                            let date = new Date(y, m - 1, d);
                            let holidayName = this.state.holidays[d] || null;
                            list.push({
                                day: d,
                                dayName: date.toLocaleDateString('en-US', { weekday: 'short' }),
                                isWeekend: date.getDay() === 0 || date.getDay() === 6,
                                isHoliday: !!holidayName,
                                holidayName: holidayName
                            });
                        }
                        return list;
                    },
                    validateInput(e) {
                        let val = e.target.value;
                        if (val === '') return;
                        
                        let num = parseFloat(val);
                        if (isNaN(num)) return;

                        if (num > 24) {
                            e.target.value = 24;
                            return 24;
                        }
                        if (num < 0) {
                            e.target.value = 0;
                            return 0;
                        }
                        return num;
                    },
                    handleCellChange(e) {
                        let validated = this.validateInput(e);
                        // If validation forced a change, we don't need to do more here
                        // as x-model.number will pick it up or we already set e.target.value
                        this.syncToServer();
                    },
                    projectTotal(id) { return this.formatNum(this.sumRow(this.state.projects[id])); },
                    leaveTotal(id) { return this.formatNum(this.sumRow(this.state.leaves[id])); },
                    sumRow(row) {
                        if (!row) return 0;
                        let sum = 0;
                        for (let day in row) { 
                            let val = parseFloat(row[day]);
                            if (!isNaN(val)) sum += val; 
                        }
                        return sum;
                    },
                    dayTotalWorkHours(day) {
                        let sum = 0;
                        this.projects.forEach(p => {
                            let val = parseFloat(this.state.projects[p.id]?.[day]);
                            if (!isNaN(val)) sum += val;
                        });
                        let genVal = parseFloat(this.state.projects['null']?.[day]);
                        if (!isNaN(genVal)) sum += genVal;
                        return sum;
                    },
                    dayGrandTotal(day) {
                        let sum = this.dayTotalWorkHours(day);
                        this.leaveTypes.forEach(l => {
                            let val = parseFloat(this.state.leaves[l.id]?.[day]);
                            if (!isNaN(val)) sum += val;
                        });
                        return sum;
                    },
                    totalWorkHours() {
                        let sum = 0;
                        this.projects.forEach(p => { sum += this.sumRow(this.state.projects[p.id]); });
                        sum += this.sumRow(this.state.projects['null']);
                        return sum;
                    },
                    grandTotal() {
                        let sum = this.totalWorkHours();
                        this.leaveTypes.forEach(l => { sum += this.sumRow(this.state.leaves[l.id]); });
                        return sum;
                    },
                    formatNum(num) {
                        // Round to 2 decimal places to avoid JS floating point errors (e.g. 0.1 + 0.2)
                        if (!num) return 0;
                        return Math.round((num + Number.EPSILON) * 100) / 100;
                    },
                    openDetails(day) {
                        if (!this.state.daily_details[day]) {
                            this.state.daily_details[day] = { location_id: null, description: '' };
                        }
                        this.editingDay = day;
                    },
                    closeDetails() {
                        this.editingDay = null;
                        this.syncNow(); // Sync immediately when closing the modal
                    }
                }
            })
        })
    </script>
</x-dynamic-component>
