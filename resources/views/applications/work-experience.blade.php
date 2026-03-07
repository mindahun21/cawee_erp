<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Work Experience – {{ $job->position }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 dark:bg-gray-900">

<div class="min-h-screen flex items-center justify-center py-16">
    <div class="w-full max-w-3xl bg-white dark:bg-gray-800 p-10 rounded-2xl shadow-xl">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-6">
            Work Experience
        </h1>
<x-application-progress :currentStep="3" />
        {{-- Success box --}}
        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-4 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ url('/apply/'.$job->id.'/step3') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Job Title</label>
                    <input type="text" name="job_title" value="{{ old('job_title') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Company Name</label>
                    <input type="text" name="company_name" value="{{ old('company_name') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Years of Experience</label>
                    <input type="number" name="years_of_experience" value="{{ old('years_of_experience') }}" required min="0"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">From Date</label>
                    <input type="date" name="from_date" value="{{ old('from_date') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">To Date</label>
                    <input type="date" name="to_date" value="{{ old('to_date') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="sm:col-span-2 flex items-center gap-4">
                    <input type="checkbox" name="banking_experience" value="1" {{ old('banking_experience') ? 'checked' : '' }}>
                    <label class="font-semibold text-gray-700 dark:text-gray-200">Banking Experience</label>
                </div>

            </div>

            <div class="mt-6 flex justify-between">
                <a href="{{ url('/apply/'.$job->id.'/step2') }}"
                   class="px-6 py-3 rounded-xl bg-gray-400 text-white font-bold hover:bg-gray-500 transition">
                    Back
                </a>

                <button type="submit"
                        class="px-12 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition">
                    Save Experience
                </button>
            </div>
        </form>

        {{-- Display saved experiences --}}
        @if($savedExperiences && count($savedExperiences) > 0)
            <div class="mt-8">
                <h2 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Saved Experiences</h2>
                <ul class="space-y-2">
                    @foreach($savedExperiences as $exp)
                        <li class="bg-gray-100 dark:bg-gray-700 p-3 rounded-lg">
                            <strong>{{ $exp['job_title'] }}</strong> at <em>{{ $exp['company_name'] }}</em><br>
                            {{ $exp['from_date'] }} - {{ $exp['to_date'] }} | {{ $exp['years_of_experience'] }} years
                            @if($exp['banking_experience'])
                                | Banking Experience
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Next Step Button --}}
        <div class="mt-6 text-center">
            <a href="{{ url('/apply/'.$job->id.'/step4') }}"
               class="px-12 py-3 rounded-xl bg-green-600 text-white font-bold hover:bg-green-700 transition">
                Next
            </a>
        </div>

    </div>
</div>

</body>
</html>