<x-app-layout maxWidth="[1600px]">
    @php
        $routePrefix = $team ? 'teams.surveys.' : 'global-surveys.';
        $isGlobal = !$team;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ $team ? route('teams.dashboard', $team) : route('teams.index') }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        {{ $isGlobal ? __('Encuestas Globales') : __('Encuestas') }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-8 mb-4 flex w-full">
            @include('teams.partials.view-switcher')
        </div>

        <!-- Action Buttons Row -->
        <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
            @can('create', App\Models\Survey::class)
                @if(!$isGlobal || auth()->user()->is_admin)
                    <a href="{{ route($routePrefix . 'create', $team ? [$team] : []) }}" 
                       class="inline-flex items-center justify-center px-4 py-2 bg-[#7c3aed] hover:bg-[#6d28d9] text-white font-bold rounded-xl shadow-sm transition-all transform hover:scale-[1.02] active:scale-95 group text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('Nueva Encuesta') }}
                    </a>

                    <div x-data="{ uploading: false }">
                        <form action="{{ route($team ? 'teams.surveys.import-json' : 'global-surveys.import-json', $team ? [$team] : []) }}" 
                              method="POST" enctype="multipart/form-data" id="import-form">
                            @csrf
                            <input type="file" name="json_file" id="json_file" class="hidden" accept=".json,.txt" 
                                   @change="if($event.target.files.length > 0) { uploading = true; $el.form.submit(); }">
                            <button type="button" @click="document.getElementById('json_file').click()" 
                                    :disabled="uploading"
                                    class="inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 font-bold rounded-xl border border-indigo-100 dark:border-indigo-800 shadow-sm transition-all hover:bg-indigo-50 dark:hover:bg-indigo-900/20 disabled:opacity-50 text-sm">
                                <svg x-show="!uploading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <span x-text="uploading ? '{{ __('Importando...') }}' : '{{ __('Importar JSON') }}'"></span>
                            </button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    </x-slot>

    <div class="space-y-4" 
         x-data="{ 
            search: '',
            get filteredCount() {
                return Array.from(document.querySelectorAll('.survey-card')).filter(card => card.style.display !== 'none').length;
            }
         }">
        
        <!-- Filters and Search Bar -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm transition-all">
            <div class="flex flex-wrap items-center gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-[280px] flex gap-2">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" 
                               x-model="search"
                               placeholder="{{ __('Buscar por título o descripción...') }}" 
                               class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-violet-500/50 dark:text-white transition-all">
                    </div>
                </div>

                <template x-if="search !== ''">
                    <button @click="search = ''"
                        class="text-xs font-bold text-red-500 hover:text-red-600 transition-colors uppercase tracking-widest">
                        {{ __('Limpiar Filtros') }}
                    </button>
                </template>
            </div>
        </div>

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
                     class="mb-8 p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-2xl flex items-center gap-3 text-emerald-700 dark:text-emerald-400">
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-8" x-ref="surveyGrid">
                    @foreach($surveys as $survey)
                        <div class="survey-card group relative bg-white dark:bg-gray-900 rounded-2xl p-1 transition-all duration-500 hover:shadow-xl hover:shadow-indigo-500/10 hover:-translate-y-1 border border-gray-100 dark:border-gray-800 overflow-hidden"
                             x-show="search === '' || '{{ strtolower($survey->title) }}'.includes(search.toLowerCase()) || '{{ strtolower($survey->description) }}'.includes(search.toLowerCase())"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100">
                            <!-- Premium Background Accent -->
                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br {{ $survey->type_color }} opacity-[0.03] dark:opacity-[0.07] rounded-bl-full transition-all duration-500 group-hover:scale-150"></div>
                            
                            <div class="relative p-6 flex flex-col h-full">
                                <!-- Card Header -->
                                <div class="flex items-start justify-between mb-6">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-violet-600 text-white shadow-lg shadow-violet-500/20">
                                                {{ $survey->questions->count() }} {{ trans_choice('Pregunta|Preguntas', $survey->questions->count()) }}
                                            </span>
                                            @if($survey->is_closed)
                                                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                                                    {{ __('Cerrada') }}
                                                </span>
                                            @elseif($survey->is_expired)
                                                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-red-500 text-white shadow-lg shadow-red-500/20">
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
                                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ $survey->title }}
                                    </h3>
                                    @if($survey->description)
                                        <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mb-6 font-medium">
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
                                       class="block w-full text-center py-3 bg-gray-50 dark:bg-gray-800/50 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 dark:text-gray-300 rounded-xl font-bold text-sm transition-all duration-300">
                                        {{ $survey->hasVoted(auth()->user()) ? __('Ver Resultados') : __('Votar Ahora') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Empty state for search results -->
                <div x-show="search !== '' && filteredCount === 0" 
                     x-transition 
                     x-cloak
                     class="mt-12 p-20 text-center bg-gray-50/50 dark:bg-gray-800/20 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-white dark:bg-gray-900 shadow-xl mb-6 text-gray-400">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('No hay resultados') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto font-medium">
                        {{ __('No hemos encontrado ninguna encuesta que coincida con') }} "<span x-text="search" class="text-indigo-600 font-bold"></span>". {{ __('Prueba con otros términos.') }}
                    </p>
                    <button @click="search = ''" class="mt-6 text-sm font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-700 transition-colors">
                        {{ __('Limpiar búsqueda') }}
                    </button>
                </div>

                <div class="mt-12" x-show="search === ''">
                    {{ $surveys->links() }}
                </div>
            @endif
    </div>
</x-app-layout>
