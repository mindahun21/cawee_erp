<h1 class="text-3xl font-bold mb-4">{{ $job->position }}</h1>

@if(session('success'))
<div class="mb-6 p-4 bg-green-500 text-white rounded-lg">
    {{ session('success') }}
</div>
@endif