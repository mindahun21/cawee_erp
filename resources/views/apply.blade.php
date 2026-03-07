@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-blue-50 dark:bg-gray-900 py-16">
    @livewire('job-application-form', ['jobId' => $jobId])
</div>
@endsection