@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-violet-500 text-sm font-medium leading-5 text-white focus:outline-none focus:border-violet-400 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-400 hover:text-gray-200 hover:border-gray-700 focus:outline-none focus:text-gray-200 focus:border-gray-700 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
