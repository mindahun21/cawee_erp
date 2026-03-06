<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Application – {{ $job->position }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 dark:bg-gray-900">

<div class="min-h-screen flex items-center justify-center py-16">
    <div class="w-full max-w-4xl bg-white dark:bg-gray-800 p-10 rounded-2xl shadow-xl">

        <h1 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-6">
            Verify Your Application
        </h1>

        <p class="mb-6 text-gray-700 dark:text-gray-300 text-center">
            Review all your information below before submitting your application for <strong>{{ $job->position }}</strong>.
        </p>

        {{-- Display all saved data --}}
        <div class="space-y-6">

            {{-- Personal Info --}}
            @if(!empty($savedData['personal_info']))
                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                    <h2 class="font-semibold text-gray-900 dark:text-white mb-2">Personal Information</h2>
                    <p>Name: {{ $savedData['personal_info']['full_name'] ?? '-' }}</p>
                    <p>Email: {{ $savedData['personal_info']['email'] ?? '-' }}</p>
                    <p>Phone: {{ $savedData['personal_info']['phone'] ?? '-' }}</p>
                </div>
            @endif

            {{-- Education Info --}}
            @if(!empty($savedData['education_info']))
                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                    <h2 class="font-semibold text-gray-900 dark:text-white mb-2">Education</h2>
                    <p>Highest Education: {{ $savedData['education_info']['highest_education'] ?? '-' }}</p>
                    <p>Graduation Year: {{ $savedData['education_info']['graduation_year'] ?? '-' }}</p>
                    <p>Field of Study: {{ $savedData['education_info']['field_of_study'] ?? '-' }}</p>
                    <p>Organization: {{ $savedData['education_info']['educational_organization'] ?? '-' }}</p>
                </div>
            @endif

            {{-- Work Experience --}}
            @if(!empty($savedData['work_experience']))
                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                    <h2 class="font-semibold text-gray-900 dark:text-white mb-2">Work Experience</h2>
                    @foreach($savedData['work_experience'] as $exp)
                        <p>{{ $exp['job_title'] ?? '-' }} at {{ $exp['company'] ?? '-' }} ({{ $exp['from'] ?? '-' }} – {{ $exp['to'] ?? '-' }})</p>
                    @endforeach
                </div>
            @endif

            {{-- Certifications --}}
            @if(!empty($savedData['certifications']))
                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                    <h2 class="font-semibold text-gray-900 dark:text-white mb-2">Certifications</h2>
                    @foreach($savedData['certifications'] as $cert)
                        <p>{{ $cert['certificate_title'] ?? '-' }} - {{ $cert['awarding_company'] ?? '-' }} ({{ $cert['awarding_date'] ?? '-' }})</p>
                    @endforeach
                </div>
            @endif

            {{-- Additional Info --}}
            @if(!empty($savedData['additional_info']))
                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                    <h2 class="font-semibold text-gray-900 dark:text-white mb-2">Additional Information</h2>
                    <p>LinkedIn: {{ $savedData['additional_info']['linkedin_profile'] ?? '-' }}</p>
                    <p>Portfolio: {{ $savedData['additional_info']['portfolio'] ?? '-' }}</p>
                    <p>Notes: {{ $savedData['additional_info']['additional_notes'] ?? '-' }}</p>
                </div>
            @endif

        </div>

        {{-- Submit Form --}}
        <form action="{{ route('apply.finalSubmit', $job->id) }}" method="POST" class="mt-8 flex justify-between">
            @csrf
            <a href="{{ route('apply.step5', $job->id) }}"
               class="px-6 py-2 rounded-lg bg-gray-500 hover:bg-gray-600 text-white font-semibold">
               Back
            </a>
            <button type="submit"
                    class="px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                Submit Application
            </button>
        </form>

    </div>
</div>

</body>
</html>