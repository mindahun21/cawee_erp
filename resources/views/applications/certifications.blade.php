<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certifications – {{ $job->position }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 dark:bg-gray-900">

<div class="min-h-screen flex items-center justify-center py-16">
    <div class="w-full max-w-3xl bg-white dark:bg-gray-800 p-10 rounded-2xl shadow-xl">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-6">
            Certifications
        </h1>
<x-application-progress :currentStep="4" />
        <form method="POST" action="{{ route('apply.submitCertifications', $job->id) }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Certificate Title</label>
                    <input type="text" name="certificate_title" value="{{ old('certificate_title') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Awarding Company</label>
                    <input type="text" name="awarding_company" value="{{ old('awarding_company') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Awarding Date</label>
                    <input type="date" name="awarding_date" value="{{ old('awarding_date') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2">
                </div>

            </div>

            <div class="mt-6 flex justify-between">
                <a href="{{ route('apply.step3', $job->id) }}"
                   class="px-6 py-2 rounded-lg bg-gray-500 hover:bg-gray-600 text-white font-semibold">
                    Back
                </a>

                <button type="submit"
                        class="px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                    Save & Next
                </button>
            </div>
        </form>

        {{-- Display saved certifications --}}
        @if(!empty($savedCertifications) && count($savedCertifications) > 0)
            <div class="mt-10">
                <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Saved Certifications</h2>
                <ul class="space-y-2">
                    @foreach($savedCertifications as $cert)
                        <li class="bg-gray-100 dark:bg-gray-700 p-3 rounded-lg">
                            {{ $cert['certificate_title'] }} - {{ $cert['awarding_company'] }} ({{ $cert['awarding_date'] }})
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div>
</div>

</body>
</html>