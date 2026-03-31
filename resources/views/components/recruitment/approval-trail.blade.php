@props(['document', 'documentType'])

@php
    $allRecords = \App\Models\Recruitment\RecruitmentApprovalRecord::query()
        ->where('approvable_type', get_class($document))
        ->where('approvable_id', $document->getKey())
        ->orderBy('submission_cycle')
        ->orderBy('stage_order')
        ->get()
        ->groupBy('submission_cycle');
@endphp

@if($allRecords->isEmpty())
    <div class="text-gray-500 italic text-sm">No workflow stages defined or started.</div>
@else
    @php
        $latestCycle = $allRecords->keys()->max();
    @endphp
    
    <div class="space-y-6">
        @foreach($allRecords as $cycle => $records)
            @php
                $isLatest = ($cycle === $latestCycle);
                $cycleLabel = "Submission #{$cycle}" . ($isLatest ? ' (current)' : '');
                $borderColor = $isLatest ? 'border-blue-300 bg-blue-50/30 dark:border-blue-700 dark:bg-blue-950/30' : 'border-gray-200 bg-gray-50/30 dark:border-gray-700 dark:bg-gray-800/30';
            @endphp
            
            @if($allRecords->count() > 1)
                <div class="rounded-lg border {{ $borderColor }} p-4 space-y-3">
                    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">{{ $cycleLabel }}</h3>
            @else
                <div class="space-y-3">
            @endif
            
            @foreach($records as $record)
                @php
                    $statusColor = match ($record->status) {
                        'Approved' => 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-950/50 drop-shadow-sm',
                        'Rejected' => 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-950/50 drop-shadow-sm',
                        default    => 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-800',
                    };
                @endphp
                
                <div class="flex items-start bg-white dark:bg-gray-800 p-3 border rounded-lg shadow-sm w-full border-gray-200 dark:border-gray-700">
                    <div class="flex-shrink-0 mt-0.5">
                        @if($record->status === 'Approved')
                            <svg class="w-5 h-5 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        @elseif($record->status === 'Rejected')
                            <svg class="w-5 h-5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        @else
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        @endif
                    </div>
                    <div class="ml-3 flex-1">
                        <div class="flex justify-between items-center">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $record->stage_name }}</h4>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColor }} border border-current opacity-75">{{ $record->status }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Required Role: <code class="dark:text-gray-300">{{ $record->required_role }}</code></p>
                        
                        @if($record->status !== 'Pending')
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                {{ $record->status }} 
                                @if($record->decidedBy) by <strong>{{ $record->decidedBy->name }}</strong> @endif
                                @if($record->decided_at) on {{ $record->decided_at->timezone('Africa/Addis_Ababa')->format('M d, Y H:i') }} @endif
                            </p>
                        @endif
                        
                        @if($record->notes)
                            <div class="text-sm text-gray-700 dark:text-gray-300 mt-2 bg-white dark:bg-gray-800 p-2 border rounded shadow-sm border-gray-200 dark:border-gray-600">
                                <strong>Notes:</strong> {{ $record->notes }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
            
            </div>
        @endforeach
    </div>
@endif
