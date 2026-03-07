<x-filament::page>
    <div class="space-y-6">

        {{-- Search & filters --}}
        <div class="flex flex-col sm:flex-row gap-4">
            <input type="text" placeholder="Search jobs..." wire:model="search"
                   class="flex-1 rounded-lg border-gray-300 p-2">

            <select wire:model="jobCategory" class="rounded-lg border-gray-300 p-2">
                <option value="">All Departments</option>
                @foreach($this->jobCategories as $category)
                    <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
            </select>

            <select wire:model="jobType" class="rounded-lg border-gray-300 p-2">
                <option value="">All Job Types</option>
                @foreach($this->jobTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
        </div>

        {{-- Jobs Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($this->campaigns as $job)
                <div wire:ignore>
                    <a href="{{ route('jobs.show', $job->id) }}" target="_self"
                       class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow hover:shadow-lg transition transform hover:-translate-y-1">
                        <h2 class="font-bold text-lg text-gray-900 dark:text-white">{{ $job->position }}</h2>
                        <p class="text-gray-600 dark:text-gray-300">{{ $job->company }}</p>
                        <p class="text-gray-500 dark:text-gray-400">{{ $job->department }} | {{ $job->working_form }}</p>
                    </a>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 col-span-full text-center">No jobs found.</p>
            @endforelse
        </div>

    </div>
</x-filament::page>