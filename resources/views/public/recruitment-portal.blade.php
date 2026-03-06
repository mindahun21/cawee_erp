<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recruitment Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 dark:bg-gray-900">

<div class="min-h-screen bg-blue-50 dark:bg-gray-900 pb-20">

    {{-- NAVBAR --}}
    <div class="flex justify-between items-center px-10 py-4 bg-blue-700 dark:bg-gray-800 text-white">
        <h1 class="text-xl font-bold">Recruitment Portal</h1>
        <div class="space-x-6">
            <a href="{{ url('/login') }}" class="bg-yellow-400 text-black px-4 py-2 rounded-lg">Login</a>
        </div>
    </div>

    {{-- HERO --}}
    <div class="text-center text-white mt-16">
        <p class="mt-4 text-lg text-blue-100 dark:text-gray-300">
            Find Jobs, Employment & Career Opportunities.
        </p>
    </div>

    {{-- SEARCH BOX --}}
    <div class="flex justify-center mt-12">
        <form method="GET" action="{{ url('/jobs') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 flex gap-4 w-3/4">

            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search..." class="border rounded-lg px-4 py-2 w-1/3 dark:bg-gray-700 dark:border-gray-600 dark:text-white"/>

            <select name="jobCategory" class="border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Select Job Category</option>
                @foreach($jobCategories as $category)
                    <option value="{{ $category }}" @selected(request('jobCategory') === $category)>{{ $category }}</option>
                @endforeach
            </select>

            <select name="jobType" class="border rounded-lg px-4 py-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Select Job Type</option>
                @foreach($jobTypes as $type)
                    <option value="{{ $type }}" @selected(request('jobType') === $type)>{{ $type }}</option>
                @endforeach
            </select>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2 rounded-lg">Search</button>
        </form>
    </div>

    {{-- JOB RESULTS --}}
    @if(isset($campaigns))
        <div class="flex justify-center mt-16">
            <div class="bg-gray-100 dark:bg-gray-900 rounded-xl shadow-lg p-10 w-1/2 text-center">
                <h2 class="text-2xl font-bold mb-8 dark:text-white">Search Results</h2>

                @if($campaigns->count())
                    <ul class="list-disc inline-block text-left space-y-3">
                        @foreach($campaigns as $job)
                            <li>
                                <a href="{{ url('/jobs/'.$job->id) }}"
                                   class="text-blue-700 dark:text-blue-400 hover:underline font-medium">
                                    {{ $job->position }} - {{ $job->department }} - {{ $job->working_form }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No search results...</p>
                @endif
            </div>
        </div>
    @endif

</div>
</body>
</html>