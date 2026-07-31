<x-app-layout>
    @section('title', 'Medallas de ' . $team->name)

    <x-slot name="header">
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
            @include('teams.partials.header-toolbar', ['class' => 'self-start'])
        </div>
        
        @include('teams.partials.team-view-nav', ['switcherClass' => 'mt-4 mb-2 flex w-full'])
    </x-slot>

    <div class="py-12 px-4">
        <div class="max-w-7xl mx-auto">
            @include('teams.partials.settings-tabs')

            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <form method="POST" action="{{ route('teams.badges.update', $team) }}" class="p-6">
                    @csrf
                    
                    <div class="mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                        <h2 class="text-xl font-black text-gray-900 dark:text-white mb-2">Medallas Habilitadas</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Selecciona qué medallas quieres que estén activas para los miembros de este equipo. 
                            Las medallas desmarcadas no podrán ser conseguidas ni se mostrarán como objetivo en el panel de gamificación.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($badges as $badge)
                            <label class="relative flex items-start p-4 cursor-pointer rounded-2xl border-2 transition-all duration-200 
                                {{ in_array($badge->id, $enabledBadges) ? 'border-violet-500 bg-violet-50/50 dark:bg-violet-900/20' : 'border-gray-100 dark:border-gray-800 hover:border-violet-200 dark:hover:border-violet-800/50' }}">
                                
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="enabled_badges[]" value="{{ $badge->id }}" 
                                        class="w-5 h-5 text-violet-600 bg-gray-100 border-gray-300 rounded focus:ring-violet-500 dark:focus:ring-violet-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                        {{ in_array($badge->id, $enabledBadges) ? 'checked' : '' }}>
                                </div>
                                <div class="ml-3 text-sm">
                                    <div class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <span class="text-xl">{{ $badge->icon }}</span>
                                        {{ $badge->name }}
                                    </div>
                                    <p class="text-xs font-normal text-gray-500 dark:text-gray-400 mt-1 line-clamp-2" title="{{ $badge->description }}">
                                        {{ $badge->description }}
                                    </p>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    @if($badges->isEmpty())
                        <div class="text-center py-12 text-gray-500">
                            No hay medallas globales configuradas en el sistema.
                        </div>
                    @endif

                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="btn-primary">
                            Guardar Configuración de Medallas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
