{{-- Partial reutilizable: sub-menú de navegación de Mis Citas --}}
@php
    $navItems = [
        ['route' => 'appointments.index',         'label' => 'Panel',      'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'appointments.list',          'label' => 'Citas',      'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['route' => 'appointments.services.index','label' => 'Servicios',  'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
        ['route' => 'appointments.blocks.index',  'label' => 'Bloqueos',   'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
        ['route' => 'appointments.settings',      'label' => 'Config.',    'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ];
@endphp

<div class="w-full mt-6 mb-4">
<div class="flex w-full items-center bg-gray-100/50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-sm overflow-x-auto no-scrollbar gap-1.5">
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-0.5">
            @foreach($navItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 min-w-max
                          {{ $active 
                              ? 'bg-white dark:bg-gray-800 text-cyan-600 dark:text-cyan-400 shadow-sm border border-gray-100 dark:border-gray-700' 
                              : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-700/60' }}"
                   title="{{ $item['label'] }}">
                    <svg class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $active ? '2.5' : '2' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
</div>
