@props(['column'])

@php
    $currentSort = request('sort');
    $currentDirection = request('direction');
    $isActive = $currentSort === $column;
@endphp

<div
    class="flex flex-col -gap-1 opacity-0 group-hover:opacity-100 transition-opacity {{ $isActive ? 'opacity-100' : '' }}">
    <svg xmlns="http://www.w3.org/2000/svg"
        class="h-2 w-2 {{ $isActive && $currentDirection === 'asc' ? 'text-violet-600' : 'text-gray-300' }}"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
    </svg>
    <svg xmlns="http://www.w3.org/2000/svg"
        class="h-2 w-2 {{ $isActive && $currentDirection === 'desc' ? 'text-violet-600' : 'text-gray-300' }}"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
    </svg>
</div>
