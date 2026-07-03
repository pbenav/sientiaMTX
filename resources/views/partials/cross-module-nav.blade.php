{{--
    Partial: nav inter-módulos
    Con equipo: delega al view-switcher completo (todas las vistas del equipo).
    Sin equipo (Canal Ciudadano global): nav reducida Encuestas / Citas / Micrositios.
    Variables requeridas: $team (puede ser null en contexto global)
    Variables opcionales: $activeModule ('surveys'|'appointments'|'microsites')
--}}
@if($team)
    @include('teams.partials.team-view-nav', [
        'switcherClass' => $switcherClass ?? 'w-full mt-2',
    ])
@else
@php
    $activeModule = $activeModule ?? null;
    $isSurveys      = $activeModule === 'surveys';
    $isAppointments = $activeModule === 'appointments';
    $isMicrosites   = $activeModule === 'microsites';

    $surveysRoute = route('global-surveys.index');

    $appointmentsTeam = auth()->user()->firstTeamWithAppointments() ?? null;
    $micrositesTeam = auth()->user()->firstTeamWithMicrosites() ?? null;
@endphp

<div class="w-full mt-2">
    <div class="flex w-full items-center bg-gray-100/50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-sm overflow-x-auto no-scrollbar gap-1.5">
        <div class="flex items-center gap-0.5">

            {{-- Escritorio --}}
            <a href="{{ route('teams.index') }}"
                class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 min-w-max border border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-700/60"
                title="{{ __('Escritorio') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ __('Escritorio') }}</span>
            </a>

            {{-- Encuestas --}}
            @if($isSurveys)
                <span class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl shrink-0 min-w-max bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ __('Encuestas') }}</span>
                </span>
            @else
                <a href="{{ $surveysRoute }}"
                    class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 min-w-max border border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-700/60"
                    title="{{ __('Encuestas') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ __('Encuestas') }}</span>
                </a>
            @endif

            {{-- Gestión de Citas --}}
            @if($appointmentsTeam)
                @if($isAppointments)
                    <span class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl shrink-0 min-w-max bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ __('Gestión de Citas') }}</span>
                    </span>
                @else
                    <a href="{{ route('appointments.index', $appointmentsTeam) }}"
                        class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 min-w-max border border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-700/60"
                        title="{{ __('Gestión de Citas') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ __('Gestión de Citas') }}</span>
                    </a>
                @endif
            @endif

            {{-- Micrositios --}}
            @if($micrositesTeam)
                @if($isMicrosites)
                    <span class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl shrink-0 min-w-max bg-white dark:bg-gray-800 text-pink-600 dark:text-pink-400 shadow-sm border border-gray-100 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                        <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ __('Micrositios') }}</span>
                    </span>
                @else
                    <a href="{{ route('teams.microsites.index', $micrositesTeam) }}"
                        class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 min-w-max border border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-700/60"
                        title="{{ __('Micrositios') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                        </svg>
                        <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ __('Micrositios') }}</span>
                    </a>
                @endif
            @endif

        </div>
    </div>
</div>
@endif
