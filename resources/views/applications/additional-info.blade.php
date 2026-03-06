<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Additional Information – {{ $job->position }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 dark:bg-gray-900">

<div class="min-h-screen flex items-center justify-center py-16">
    <div class="w-full max-w-3xl bg-white dark:bg-gray-800 p-10 rounded-2xl shadow-xl">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-6">
            Additional Information
        </h1>

        <form method="POST" action="{{ route('apply.submitAdditionalInfo', $job->id) }}">
            @csrf

            <div class="grid grid-cols-1 gap-6">

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">LinkedIn Profile</label>
                    <input type="url" name="linkedin_profile" value="{{ old('linkedin_profile') }}"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Portfolio / Website</label>
                    <input type="url" name="portfolio" value="{{ old('portfolio') }}"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Additional Notes</label>
                    <textarea name="additional_notes" rows="4"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2">{{ old('additional_notes') }}</textarea>
                </div>

            </div>

            <div class="mt-6 flex justify-between">
                <a href="{{ route('apply.step4', $job->id) }}"
                   class="px-6 py-2 rounded-lg bg-gray-500 hover:bg-gray-600 text-white font-semibold">
                    Back
                </a>

                <button type="submit"
                        class="px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                    Save & Next
                </button>
            </div>
        </form>

    </div>
</div>

</body>
</html>