<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $job->position }} – Job Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 dark:bg-gray-900">

<div class="min-h-screen flex items-start justify-center py-16">
    <div class="w-full max-w-4xl bg-white dark:bg-gray-800 p-10 rounded-2xl shadow-xl">

        {{-- Job Title --}}
        <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white text-center mb-4">
            {{ $job->position }}
        </h1>

        <div class="border-t border-gray-300 dark:border-gray-600 mb-6"></div>

        {{-- Company --}}
        <p class="text-gray-600 dark:text-gray-300 text-center text-lg mb-6">
            {{ $job->company ?? 'Company not listed' }}
        </p>

        {{-- Job Info Grid --}}
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
            <div class="flex items-center gap-2">
                <strong>Job Type:</strong> <span>{{ $job->working_form ?? '-' }}</span>
            </div>
            <div class="flex items-center gap-2">
                <strong>Job Grade:</strong> <span>{{ $job->seniority ?? '-' }}</span>
            </div>
            <div class="flex items-center gap-2">
                <strong>Department:</strong> <span>{{ $job->department ?? '-' }}</span>
            </div>
            <div class="flex items-center gap-2">
                <strong>Location:</strong> <span>{{ $job->workplace ?? '-' }}</span>
            </div>
            <div class="flex items-center gap-2">
                <strong>Deadline:</strong> <span>{{ $job->to_date?->format('Y-m-d') ?? '-' }}</span>
            </div>
            <div class="flex items-center gap-2">
                <strong>Salary:</strong> 
                <span>{{ $job->display_salary ? ($job->starting_salary_from.' - '.$job->starting_salary_to) : 'Not Displayed' }}</span>
            </div>
        </div>

        {{-- Job Description --}}
        <div class="mt-10">
            <h2 class="text-2xl font-bold mb-3">Job Description</h2>
            <div class="prose dark:prose-invert">
                {!! $job->job_description !!}
            </div>
        </div>

        {{-- Apply Button --}}
        <div class="mt-12 flex justify-center">
            <a href="{{ url('/apply/'.$job->id.'/step1') }}"
               class="inline-block px-16 py-4 rounded-2xl text-lg font-bold
                      bg-blue-600 text-white shadow-lg hover:bg-blue-700
                      transition-colors duration-200">
                Apply Now
            </a>
        </div>

    </div>
</div>
</body>
</html>