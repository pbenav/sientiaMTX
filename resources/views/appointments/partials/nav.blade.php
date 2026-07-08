{{-- Partial reutilizable: barra de navegación de Citas Previas
     Fila 1: Barra inter-módulos compartida (igual que Encuestas y Micrositios)
     Fila 2: Sub-navegación específica de Citas (Panel, Citas, Servicios, Bloqueos, Config.)
--}}
@php
    $navItems = [
        ['route' => 'appointments.index',         'label' => 'Panel',      'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'appointments.list',          'label' => 'Citas',      'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['route' => 'appointments.services.index','label' => 'Servicios',  'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
        ['route' => 'appointments.blocks.index',  'label' => 'Bloqueos',   'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
        ['route' => 'appointments.visitors.index','label' => 'Personas',   'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
        ['route' => 'appointments.settings',      'label' => 'Config.',    'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ];

    $userTeamsWithAppointments = auth()->user()->teams()
        ->whereJsonContains('settings->has_appointments', true)
        ->wherePivot('allow_appointments', true)
        ->get();
@endphp

{{-- ── Fila 1: Barra inter-módulos (idéntica a Encuestas y Micrositios) ── --}}
@php $activeModule = 'appointments'; @endphp
@include('partials.cross-module-nav')

{{-- ── Fila 2: Sub-navegación específica de Citas ── --}}
<div class="w-full mt-1.5">
    <div class="flex w-full items-center justify-between bg-gray-50 dark:bg-gray-800/30 p-1 rounded-xl border border-gray-100 dark:border-gray-700/30 overflow-x-auto no-scrollbar gap-1">
        <div class="flex items-center gap-0.5">
            @foreach($navItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route'], $team) }}"
                   class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-1.5 rounded-lg transition-all shrink-0 min-w-max
                          {{ $active
                              ? 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-700'
                              : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-white/60 dark:hover:bg-gray-700/40 border border-transparent' }}"
                   title="{{ $item['label'] }}">
                    <svg class="h-3.5 sm:h-4 w-3.5 sm:w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $active ? '2.5' : '2' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span class="hidden sm:block text-[8px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>

        {{-- Selector de equipo --}}
        @if($userTeamsWithAppointments->count() > 1)
            <div class="flex items-center shrink-0">
                <x-dropdown align="right" width="60">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-1 px-2 py-1 bg-white hover:bg-gray-50 dark:bg-gray-850 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg transition-colors shadow-sm focus:outline-none text-[9px] font-black uppercase tracking-wider">
                            <span class="text-gray-400 whitespace-nowrap">{{ __('Equipo:') }}</span>
                            <span class="text-violet-600 dark:text-violet-400 whitespace-nowrap">👥 {{ $team->name }}</span>
                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        @foreach($userTeamsWithAppointments as $t)
                            @if($t->id !== $team->id)
                                @php
                                    $params = request()->route()->parameters();
                                    $params['team'] = $t->id;
                                    $routeName = request()->route()->getName();
                                    if (isset($params['service']) || isset($params['block']) || isset($params['appointment'])) {
                                        $routeName = 'appointments.index';
                                        $params = ['team' => $t->id];
                                    }
                                    $targetUrl = route($routeName, $params);
                                @endphp
                                <x-dropdown-link :href="$targetUrl">
                                    👥 {{ $t->name }}
                                </x-dropdown-link>
                            @endif
                        @endforeach
                    </x-slot>
                </x-dropdown>
            </div>
        @elseif($userTeamsWithAppointments->count() === 1)
            <div class="flex items-center gap-1 px-2 py-1 rounded-lg shrink-0 text-[9px] font-black text-violet-600 dark:text-violet-400 whitespace-nowrap">
                👥 {{ $team->name }}
            </div>
        @endif
    </div>
</div>
