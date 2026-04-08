<x-app-layout>
    @section('title', isset($team) ? 'Habilidades de ' . $team->name : 'Gestión de Habilidades')

    <x-slot name="header">
        @if(isset($team))
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                <div class="flex items-start gap-4 min-w-0 flex-1">
                    <a href="{{ route('teams.index') }}"
                        class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                        title="{{ __('navigation.back') ?? 'Volver' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <div class="min-w-0 flex-1">
                        @include('teams.partials.breadcrumb')
                        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                            {{ __('teams.edit') }}: {{ $team->name }}
                        </h1>
                    </div>
                </div>
            </div>

            <!-- View Switcher Sub-Header -->
            <div class="mt-4 mb-2 flex w-full">
                @include('teams.partials.view-switcher')
            </div>

            <!-- Action Buttons Row -->
            <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                @include('teams.partials.header-actions')
            </div>
        @else
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">
                        Catálogo de Habilidades
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Gestiona las especialidades globales del sistema.
                    </p>
                </div>
            </div>
        @endif
    </x-slot>

    <div class="py-12 px-4 text-xs font-bold">
        <div class="max-w-7xl mx-auto">
            @if(!isset($team))
                @include('settings.partials.tabs')
            @else
                @include('teams.partials.settings-tabs')
            @endif

            @include('settings.partials.skill-management')
        </div>
    </div>
</x-app-layout>
