<x-app-layout>
    @php
        $routePrefix = $team ? 'teams.surveys.' : 'global-surveys.';
        $isGlobal = !$team;
    @endphp

    <div class="py-8 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-gray-50 to-white dark:from-gray-950 dark:to-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <!-- Header Section with Premium Look -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                <div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-2">
                        {{ $isGlobal ? __('Encuestas Globales') : __('Encuestas') }}
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">
                        {{ $isGlobal 
                            ? __('Consulta la opinión de toda la organización y participa en la toma de decisiones.') 
                            : __('Consulta la opinión del equipo y toma decisiones basadas en datos.') }}
                    </p>
                </div>
                
                @can('create', App\Models\Survey::class)
                @if(!$isGlobal || auth()->user()->is_admin)
                <a href="{{ route($routePrefix . 'create', $team ? [$team] : []) }}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-bold rounded-2xl shadow-xl shadow-indigo-500/20 transition-all transform hover:scale-105 active:scale-95 group">
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('Nueva Encuesta') }}
                </a>
                @endif
                @endcan
            </div>

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
                     class="mb-8 p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-2xl flex items-center gap-3 text-emerald-700 dark:text-emerald-400 animate-fade-in-down">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-bold">{{ session('success') }}</span>
                </div>
            @endif

            @if($surveys->isEmpty())
                <div class="bg-white/50 dark:bg-gray-900/50 backdrop-blur-xl border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-3xl p-20 text-center shadow-inner">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gray-100 dark:bg-gray-800 mb-6 text-gray-400">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('No hay encuestas activas') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">
                        {{ $isGlobal 
                            ? __('Pronto habrá nuevas consultas para toda la comunidad.') 
                            : __('Sé el primero en proponer algo al equipo creando una encuesta ahora mismo.') }}
                    </p>
                    @can('create', App\Models\Survey::class)
                    @if(!$isGlobal || auth()->user()->is_admin)
                    <a href="{{ route($routePrefix . 'create', $team ? [$team] : []) }}" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                        {{ __('Crear mi primera encuesta') }} &rarr;
                    </a>
                    @endif
                    @endcan
                </div>
            @else
                <!-- Survey Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($surveys as $survey)
                        <div class="group relative bg-white dark:bg-gray-900 rounded-[2.5rem] p-1 transition-all duration-500 hover:shadow-2xl hover:shadow-indigo-500/10 hover:-translate-y-2 border border-gray-100 dark:border-gray-800 overflow-hidden">
                            <!-- Premium Background Accent -->
                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br {{ $survey->type_color }} opacity-[0.03] dark:opacity-[0.07] rounded-bl-full transition-all duration-500 group-hover:scale-150"></div>
                            
                            <div class="relative p-6 flex flex-col h-full">
                                <!-- Card Header -->
                                <div class="flex items-start justify-between mb-6">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-violet-600 text-white shadow-lg shadow-violet-500/20">
                                                {{ $survey->questions->count() }} {{ trans_choice('Pregunta|Preguntas', $survey->questions->count()) }}
                                            </span>
                                            @if($survey->is_closed)
                                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                                                    {{ __('Cerrada') }}
                                                </span>
                                            @elseif($survey->is_expired)
                                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-red-500 text-white shadow-lg shadow-red-500/20">
                                                    {{ __('Expirada') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        @can('update', $survey)
                                            <a href="{{ route($routePrefix . 'edit', $team ? [$team, $survey] : [$survey]) }}" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </a>
                                        @endcan
                                    </div>
                                </div>

                                <!-- Title & Description -->
                                <a href="{{ route($routePrefix . 'show', $team ? [$team, $survey] : [$survey]) }}" class="flex-grow">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors line-clamp-2">
                                        {{ $survey->title }}
                                    </h3>
                                    @if($survey->description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 line-clamp-3 font-medium">
                                            {{ $survey->description }}
                                        </p>
                                    @endif
                                </a>

                                <!-- Stats & Footer -->
                                <div class="mt-auto">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex -space-x-2 overflow-hidden">
                                            <!-- Simple simulation of voters if not anonymous, or just a nice icon -->
                                            <div class="w-8 h-8 rounded-full border-2 border-white dark:border-gray-900 bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[10px] font-bold text-gray-500">
                                                {{ $survey->votes_count }}
                                            </div>
                                            <span class="ml-2 text-xs font-bold text-gray-400 flex items-center uppercase tracking-widest pl-4">
                                                {{ __('Votos') }}
                                            </span>
                                        </div>
                                        
                                        @if($survey->expires_at)
                                            <div class="flex items-center gap-1.5 text-xs font-bold text-gray-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                {{ $survey->expires_at->diffForHumans() }}
                                            </div>
                                        @endif
                                    </div>

                                    <a href="{{ route($routePrefix . 'show', $team ? [$team, $survey] : [$survey]) }}" 
                                       class="block w-full text-center py-4 bg-gray-50 dark:bg-gray-800/50 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 dark:text-gray-300 rounded-2xl font-bold text-sm transition-all duration-300">
                                        {{ $survey->hasVoted(auth()->user()) ? __('Ver Resultados') : __('Votar Ahora') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-12">
                    {{ $surveys->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
