<div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <th class="p-3 font-semibold text-gray-700 dark:text-gray-200 sticky left-0 bg-gray-50 dark:bg-gray-900 z-10">Project / Activity</th>
                    <th class="p-3 font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800">Total</th>
                    @for ($d = 1; $d <= $daysInMonth; $d++)
                        <th class="p-2 font-medium text-center text-gray-600 dark:text-gray-400 min-w-[40px]">{{ $d }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                {{-- Projects --}}
                @foreach ($projects as $project)
                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-850 transition-colors">
                        <td class="p-3 font-medium text-gray-800 dark:text-gray-100 sticky left-0 bg-white dark:bg-gray-800">
                            {{ $project->project_name }}
                        </td>
                        <td class="p-3 font-bold text-center bg-gray-50 dark:bg-gray-900">
                            @php
                                $rowTotal = collect($entries[$project->id] ?? [])->sum();
                            @endphp
                            {{ number_format($rowTotal, 1) }}
                        </td>
                        @for ($d = 1; $d <= $daysInMonth; $d++)
                            <td class="p-1">
                                <input 
                                    type="number" 
                                    step="0.5" 
                                    wire:model="entries.{{ $project->id }}.{{ $d }}" 
                                    class="w-full p-1 text-center bg-transparent border-0 focus:ring-2 focus:ring-amber-500 rounded text-gray-900 dark:text-gray-100 placeholder-gray-400"
                                    placeholder="0"
                                >
                            </td>
                        @endfor
                    </tr>
                @endforeach

                <tr class="bg-gray-50 dark:bg-gray-900/50">
                    <td colspan="{{ $daysInMonth + 2 }}" class="p-2 font-bold text-gray-400 uppercase text-xs tracking-wider">Leave Types</td>
                </tr>

                {{-- Leaves --}}
                @foreach ($leaveTypes as $leaveType)
                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-850 transition-colors">
                        <td class="p-3 font-medium text-gray-800 dark:text-gray-100 sticky left-0 bg-white dark:bg-gray-800">
                            {{ $leaveType->name }}
                        </td>
                        <td class="p-3 font-bold text-center bg-gray-50 dark:bg-gray-900">
                            @php
                                $rowTotal = collect($leaves[$leaveType->id] ?? [])->sum();
                            @endphp
                            {{ number_format($rowTotal, 1) }}
                        </td>
                        @for ($d = 1; $d <= $daysInMonth; $d++)
                            <td class="p-1">
                                <input 
                                    type="number" 
                                    step="0.5" 
                                    wire:model="leaves.{{ $leaveType->id }}.{{ $d }}" 
                                    class="w-full p-1 text-center bg-transparent border-0 focus:ring-2 focus:ring-amber-500 rounded text-gray-900 dark:text-gray-100 placeholder-gray-400"
                                    placeholder="0"
                                >
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-gray-100 dark:bg-gray-900 border-t-2 border-gray-200 dark:border-gray-700 font-bold">
                    <td class="p-3 sticky left-0 bg-gray-100 dark:bg-gray-900">Grand Total</td>
                    <td class="p-3 text-center text-amber-600 dark:text-amber-400">
                        @php
                            $grandTotal = 0;
                            foreach($entries as $p) $grandTotal += array_sum($p);
                            foreach($leaves as $l) $grandTotal += array_sum($l);
                        @endphp
                        {{ number_format($grandTotal, 1) }}
                    </td>
                    @for ($d = 1; $d <= $daysInMonth; $d++)
                        <td class="p-2 text-center text-xs">
                            @php
                                $dayTotal = 0;
                                foreach($entries as $p) $dayTotal += ($p[$d] ?? 0);
                                foreach($leaves as $l) $dayTotal += ($l[$d] ?? 0);
                            @endphp
                            {{ $dayTotal ?: '' }}
                        </td>
                    @endfor
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mt-6 flex justify-end gap-3">
        @if (session()->has('message'))
            <span class="text-green-600 self-center mr-2">{{ session('message') }}</span>
        @endif
        <button wire:click="save" class="px-6 py-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg shadow-sm transition-all focus:ring-4 focus:ring-amber-200">
            Save Changes
        </button>
    </div>
</div>
