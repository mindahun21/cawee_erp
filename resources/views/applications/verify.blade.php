<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify Application</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-blue-50">

<div class="min-h-screen flex items-center justify-center py-16">

    <div class="w-full max-w-3xl bg-white p-8 rounded-xl shadow-lg">

        <h1 class="text-3xl font-bold text-center text-gray-900 mb-6">
            Verify Your Information
        </h1>

        <!-- Step Dots Progress -->
        <div class="flex justify-center mb-6">
            <div class="flex space-x-4">
                @for($i = 1; $i <= 6; $i++)
                    <div class="w-4 h-4 rounded-full
                        {{ $currentStep >= $i ? 'bg-blue-600' : 'bg-gray-300' }}
                        transition-colors duration-300"></div>
                @endfor
            </div>
        </div>

        <!-- Scrollable Info -->
        <div class="max-h-[450px] overflow-y-auto border rounded-lg p-6 space-y-4">

            <h2 class="font-bold text-lg">Personal Information</h2>
           <!-- Personal Info -->
<p><strong>Full Name:</strong> {{ $savedData['personal_info']['full_name'] ?? '' }}</p>
<p><strong>Email:</strong> {{ $savedData['personal_info']['email'] ?? '' }}</p>
<p><strong>Phone:</strong> {{ $savedData['personal_info']['phone_number'] ?? '' }}</p>

<!-- Education -->
<p><strong>Highest Education:</strong> {{ $savedData['education']['highest_education'] ?? '' }}</p>
<p><strong>Graduation Year:</strong> {{ $savedData['education']['graduation_year'] ?? '' }}</p>
<p><strong>Field of Study:</strong> {{ $savedData['education']['field_of_study'] ?? '' }}</p>

<!-- Work Experience -->
@foreach($savedData['work_experience'] ?? [] as $exp)
<div>
    <p><strong>Company:</strong> {{ $exp['company_name'] ?? '' }}</p>
    <p><strong>Role:</strong> {{ $exp['job_title'] ?? '' }}</p>
    <p><strong>Years:</strong> {{ $exp['years_of_experience'] ?? '' }}</p>
</div>
@endforeach

<!-- Certifications -->
@foreach($savedData['certifications'] ?? [] as $cert)
<div>
    <p><strong>Certificate:</strong> {{ $cert['certificate_title'] ?? '' }}</p>
    <p><strong>Organization:</strong> {{ $cert['awarding_company'] ?? '' }}</p>
</div>
@endforeach

<!-- Additional Info -->
<p><strong>LinkedIn:</strong> {{ $savedData['additional_info']['linkedin_profile'] ?? '' }}</p>
<p><strong>Portfolio:</strong> {{ $savedData['additional_info']['portfolio'] ?? '' }}</p>
<p><strong>Notes:</strong> {{ $savedData['additional_info']['additional_notes'] ?? '' }}</p>
        </div>

        <!-- Actions -->
        <div class="flex justify-between mt-6">

            <form method="POST" action="{{ route('apply.finalSubmit', $job->id) }}">
                @csrf
                <button type="submit"
                        class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold">
                    Verify & Submit
                </button>
            </form>

            <a href="{{ route('job.details', $job->id) }}"
               class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold">
                Cancel
            </a>

        </div>

    </div>

</div>

</body>
</html>