<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Education Background – {{ $job->position }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 dark:bg-gray-900">

<div class="min-h-screen flex items-center justify-center py-16">
    <div class="w-full max-w-3xl bg-white dark:bg-gray-800 p-10 rounded-2xl shadow-xl">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-6">
            Educational Background
        </h1>

        <form method="POST" action="{{ url('/apply/'.$job->id.'/step2') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Highest Education</label>
                    <select name="highest_education" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Select</option>
                        <option value="High School" {{ old('highest_education')=='High School'?'selected':'' }}>High School</option>
                        <option value="Diploma" {{ old('highest_education')=='Diploma'?'selected':'' }}>Diploma</option>
                        <option value="Bachelor" {{ old('highest_education')=='Bachelor'?'selected':'' }}>Bachelor</option>
                        <option value="Master" {{ old('highest_education')=='Master'?'selected':'' }}>Master</option>
                        <option value="PhD" {{ old('highest_education')=='PhD'?'selected':'' }}>PhD</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Graduation Year</label>
                    <input type="date" name="graduation_year" value="{{ old('graduation_year') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Education Program</label>
                    <select name="education_program" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Select Program</option>
                        <option value="Science" {{ old('education_program')=='Science'?'selected':'' }}>Science</option>
                        <option value="Arts" {{ old('education_program')=='Arts'?'selected':'' }}>Arts</option>
                        <option value="Commerce" {{ old('education_program')=='Commerce'?'selected':'' }}>Commerce</option>
                        <option value="Engineering" {{ old('education_program')=='Engineering'?'selected':'' }}>Engineering</option>
                        <option value="Other" {{ old('education_program')=='Other'?'selected':'' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">CGPA / Score</label>
                    <input type="text" name="cgpa" value="{{ old('cgpa') }}"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Field of Study</label>
                    <input type="text" name="field_of_study" value="{{ old('field_of_study') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Educational Organization</label>
                    <input type="text" name="educational_organization" value="{{ old('educational_organization') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Institution Type</label>
                    <select name="institution_type" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Select Type</option>
                        <option value="Public" {{ old('institution_type')=='Public'?'selected':'' }}>Public</option>
                        <option value="Private" {{ old('institution_type')=='Private'?'selected':'' }}>Private</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Exit Exam (Optional)</label>
                    <input type="text" name="exit_exam" value="{{ old('exit_exam') }}"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

            </div>

            <div class="mt-6 text-center">
                <button type="submit"
                        class="px-12 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition">
                    Next
                </button>
            </div>

        </form>
    </div>
</div>
</body>
</html>