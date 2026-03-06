<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for {{ $job->position }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-blue-50 dark:bg-gray-900">

<div class="min-h-screen flex items-center justify-center py-16">
    <div class="w-full max-w-3xl bg-white dark:bg-gray-800 p-10 rounded-2xl shadow-xl">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white text-center mb-6">
            Personal Information
        </h1>

        <form method="POST" action="{{ url('/apply/'.$job->id.'/step1') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Full Name</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Phone Number</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Birthplace</label>
                    <input type="text" name="birthplace" value="{{ old('birthplace') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Current Address</label>
                    <input type="text" name="current_address" value="{{ old('current_address') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Gender</label>
                    <select name="gender" required
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Select Gender</option>
                        <option value="male" {{ old('gender')=='male'?'selected':'' }}>Male</option>
                        <option value="female" {{ old('gender')=='female'?'selected':'' }}>Female</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-200">Birthdate</label>
                    <input type="date" name="birthdate" value="{{ old('birthdate') }}" required
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