{{-- Partial reutilizable: barra de navegación unificada (Canal Ciudadano + sub-menú de Mis Citas) --}}
@php
    $navItems = [
        ['route' => 'appointments.index',         'label' => 'Panel',      'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'appointments.list',          'label' => 'Citas',      'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['route' => 'appointments.services.index','label' => 'Servicios',  'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
        ['route' => 'appointments.blocks.index',  'label' => 'Bloqueos',   'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
        ['route' => 'appointments.settings',      'label' => 'Config.',    'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ];

    $userTeamsWithAppointments = auth()->user()->teams()
        ->whereJsonContains('settings->has_appointments', true)
        ->wherePivot('allow_appointments', true)
        ->get();
@endphp

<div class="w-full mt-6 mb-4">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between bg-gray-100/50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-sm gap-3">
        <div class="flex items-center gap-0.5 overflow-x-auto no-scrollbar">

            {{-- Nivel superior: Encuestas Colectivas --}}
            <a href="{{ route('global-surveys.index') }}"
                class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 min-w-max border border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-700/60"
                title="{{ __('Encuestas') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ __('Encuestas') }}</span>
            </a>

            {{-- Nivel superior: Gestión de Citas (activo) --}}
            <span class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 min-w-max bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ __('Citas Previas') }}</span>
            </span>

            {{-- Divisor visual --}}
            <div class="w-px h-7 bg-gray-300 dark:bg-gray-600 mx-1 shrink-0"></div>

            {{-- Sub-menú: Panel, Citas, Servicios, Bloqueos, Config --}}
            @foreach($navItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route'], $team) }}"
                   class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 min-w-max
                          {{ $active
                              ? 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-700'
                              : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-700/60 border border-transparent' }}"
                   title="{{ $item['label'] }}">
                    <svg class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ $active ? '2.5' : '2' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>

        {{-- Selector de Equipo --}}
        @if($userTeamsWithAppointments->count() > 1)
            <div class="flex items-center gap-2 px-2 shrink-0 self-start lg:self-center">
                <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 whitespace-nowrap">{{ __('Equipo:') }}</span>
                <select onchange="window.location.href = this.value"
                        class="px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring-1 focus:ring-violet-500 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 outline-none cursor-pointer shadow-sm">
                    @foreach($userTeamsWithAppointments as $t)
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
                        <option value="{{ $targetUrl }}" {{ $team->id == $t->id ? 'selected' : '' }}>
                            👥 {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @elseif($userTeamsWithAppointments->count() === 1)
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-white/50 dark:bg-gray-850/50 border border-gray-150 dark:border-gray-755 rounded-xl shrink-0 self-start lg:self-center shadow-sm">
                <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 whitespace-nowrap">{{ __('Equipo:') }}</span>
                <span class="text-xs font-black text-violet-600 dark:text-violet-400">👥 {{ $team->name }}</span>
            </div>
        @endif
    </div>
</div>
