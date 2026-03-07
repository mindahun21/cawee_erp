{{-- resources/views/components/application-progress.blade.php --}}

@props(['currentStep'])

<div class="flex items-center justify-center mb-8">

@php
$steps = [
    1 => '',
    2 => '',
    3 => '',
    4 => '',
    5 => '',
    6 => ''
];
@endphp

<div class="flex items-center space-x-6">

@foreach($steps as $number => $label)

<div class="flex flex-col items-center">

<div class="
w-4 h-4 rounded-full
{{ $currentStep >= $number ? 'bg-blue-600' : 'bg-gray-300' }}
transition-all duration-300
"></div>

<span class="text-xs mt-1 text-gray-600 dark:text-gray-300">
{{ $label }}
</span>

</div>

@if(!$loop->last)

<div class="w-10 h-0.5
{{ $currentStep > $number ? 'bg-blue-600' : 'bg-gray-300' }}">
</div>

@endif

@endforeach

</div>

</div>