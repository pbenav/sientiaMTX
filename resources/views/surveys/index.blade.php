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
                    <h1
                        class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-3">
                        @if ($isGlobal)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                            {{ __('Canal Ciudadano') }}
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            {{ __('Encuestas') }}
                        @endif
                    </h1>
                </div>
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        @if ($team)
            <div class="mt-8 mb-4 flex w-full">
                @include('teams.partials.view-switcher')
            </div>
        @endif


    </x-slot>

    <div class="space-y-6" x-data="{
        currentTab: 'surveys',
        search: '',
        get filteredCount() {
            return Array.from(document.querySelectorAll('.survey-card')).filter(card => card.style.display !== 'none').length;
        }
    }">

        @if ($isGlobal)
            <!-- Selector de Pestañas del Canal Ciudadano (Estilo Unificado con Mis Citas) -->
            <div class="w-full mt-6 mb-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between bg-gray-100/50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-sm gap-3">
                    <div class="flex items-center gap-0.5 overflow-x-auto no-scrollbar">
                        <button type="button" @click="currentTab = 'surveys'; $dispatch('tab-changed', 'surveys')"
                            :class="currentTab === 'surveys' 
                                ? 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-700' 
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-700/60'"
                            class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 min-w-max border border-transparent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" :stroke-width="currentTab === 'surveys' ? '2.5' : '2'">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ __('Encuestas') }}</span>
                        </button>
                        
                        @php
                            $targetTeam = null;
                            if ($team && auth()->user()->hasAppointmentsEnabledForTeam($team->id)) {
                                $targetTeam = $team;
                            } else {
                                $targetTeam = auth()->user()->firstTeamWithAppointments();
                            }
                        @endphp
                        @if($targetTeam)
                            <a href="{{ route('appointments.index', $targetTeam) }}"
                                class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 min-w-max border border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-700/60"
                                title="{{ __('Gestión de Citas Previas') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ __('Gestión de Citas') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Pestaña: Encuestas Colectivas -->
        <div x-show="currentTab === 'surveys'" class="space-y-4">
            <!-- Action Buttons for Surveys -->
            <div class="flex flex-wrap items-center gap-3 pt-2">
                @can('create', App\Models\Survey::class)
                    @if (!$isGlobal || auth()->user()->is_admin)
                        <a href="{{ route($routePrefix . 'create', $team ? [$team] : []) }}"
                            class="flex items-center gap-2 text-xs bg-violet-600 hover:bg-violet-500 text-white px-5 py-2.5 rounded-xl transition-all font-black shadow-lg shadow-violet-500/20 active:scale-95 group">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 transition-transform group-hover:rotate-90" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>{{ __('Nueva Encuesta') }}</span>
                        </a>

                        <div x-data="{ uploading: false }">
                            <form
                                action="{{ route($team ? 'teams.surveys.import-json' : 'global-surveys.import-json', $team ? [$team] : []) }}"
                                method="POST" enctype="multipart/form-data" id="import-form">
                                @csrf
                                <input type="file" name="json_file" id="json_file" class="hidden" accept=".json,.txt"
                                    @change="if($event.target.files.length > 0) { uploading = true; $el.form.submit(); }">
                                <button type="button" @click="document.getElementById('json_file').click()"
                                    :disabled="uploading"
                                    class="flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm disabled:opacity-50">
                                    <svg x-show="!uploading" class="h-4 w-4 text-violet-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <span
                                        x-text="uploading ? '{{ __('Importando...') }}' : '{{ __('Importar JSON') }}'"></span>
                                </button>
                            </form>
                        </div>
                    @endif
                @endcan
            </div>

            <!-- Filters and Search Bar -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
                <div class="flex gap-4">
                    <div class="relative flex-1 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400 group-focus-within:text-violet-500 transition-colors"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" x-model="search"
                            placeholder="{{ __('Buscar por título o descripción...') }}" enterkeyhint="search"
                            :class="search !== '' ?
                                'bg-violet-50/50 dark:bg-violet-900/10 border-violet-300 dark:border-violet-800 ring-2 ring-violet-500/20' :
                                'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700'"
                            class="w-full pl-10 pr-12 py-2.5 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 dark:text-white transition-all shadow-sm">
                        <button x-show="search !== ''" @click="search = ''"
                            class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-gray-400 hover:text-red-500 transition-colors"
                            title="{{ __('Limpiar Filtros') }}" x-cloak>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                    class="mb-8 p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-2xl flex items-center gap-3 text-emerald-700 dark:text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-bold">{{ session('success') }}</span>
                </div>
            @endif

            @if ($surveys->isEmpty())
                <div
                    class="bg-white/50 dark:bg-gray-900/50 backdrop-blur-xl border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-3xl p-20 text-center shadow-inner">
                    <div
                        class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gray-100 dark:bg-gray-800 mb-6 text-gray-400">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ __('No hay encuestas activas') }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">
                        {{ $isGlobal
                            ? __('Pronto habrá nuevas consultas para toda la comunidad.')
                            : __('Sé el primero en proponer algo al equipo creando una encuesta ahora mismo.') }}
                    </p>
                    @can('create', App\Models\Survey::class)
                        @if (!$isGlobal || auth()->user()->is_admin)
                            <a href="{{ route($routePrefix . 'create', $team ? [$team] : []) }}"
                                class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                                {{ __('Crear mi primera encuesta') }} &rarr;
                            </a>
                        @endif
                    @endcan
                </div>
            @else
                <!-- Survey Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" x-ref="surveyGrid">
                    @foreach ($surveys as $survey)
                        <a href="{{ route($routePrefix . 'show', $team ? [$team, $survey] : [$survey]) }}"
                            class="survey-card group bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-violet-300 dark:hover:border-violet-500/50 rounded-2xl p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md flex flex-col relative overflow-hidden"
                            x-show="search === '' || '{{ strtolower($survey->title) }}'.includes(search.toLowerCase()) || '{{ strtolower($survey->description) }}'.includes(search.toLowerCase())"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100">

                            <!-- Card Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 px-2 py-1 rounded-md">
                                        {{ $survey->questions->count() }}
                                        {{ trans_choice('Pregunta|Preguntas', $survey->questions->count()) }}
                                    </span>
                                    @if ($survey->is_closed)
                                        <span
                                            class="text-[10px] font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-2 py-1 rounded-md">
                                            {{ __('Cerrada') }}
                                        </span>
                                    @elseif($survey->is_expired)
                                        <span
                                            class="text-[10px] font-black uppercase tracking-widest bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-2 py-1 rounded-md">
                                            {{ __('Expirada') }}
                                        </span>
                                    @else
                                        <span
                                            class="text-[10px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 px-2 py-1 rounded-md">
                                            {{ __('Activa') }}
                                        </span>
                                    @endif

                                    @if ($survey->is_public)
                                        <span
                                            class="text-[10px] font-black uppercase tracking-widest bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/40 dark:text-fuchsia-300 px-2 py-1 rounded-md"
                                            title="Pública (Enlace Externo)">
                                            <svg class="w-3 h-3 inline-block -mt-0.5 mr-0.5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                            </svg>
                                            {{ __('Pública') }}
                                        </span>
                                    @endif
                                </div>

                                <div
                                    class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    @if ($survey->is_public && $survey->uuid)
                                        <object>
                                            <button type="button"
                                                @click='event.preventDefault(); event.stopPropagation(); navigator.clipboard.writeText("{{ route('public.surveys.show', $survey->uuid) }}"); Swal.fire({title:"Enlace Copiado", text:"El enlace público ha sido copiado", icon:"success", toast:true, position:"top-end", showConfirmButton:false, timer:3000});'
                                                class="p-1 text-gray-400 hover:text-fuchsia-600 dark:hover:text-fuchsia-400 transition-colors"
                                                title="Copiar Enlace Público">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                </svg>
                                            </button>
                                        </object>
                                    @endif
                                    @can('update', $survey)
                                        <object>
                                            <a href="{{ route($routePrefix . 'edit', $team ? [$team, $survey] : [$survey]) }}"
                                                class="p-1 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
                                                onclick="event.stopPropagation()" title="Editar Encuesta">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </a>
                                        </object>
                                    @endcan
                                </div>
                            </div>

                            <!-- Title -->
                            <h4
                                class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors leading-tight mb-2">
                                {{ $survey->title }}
                            </h4>

                            <!-- Description short snippet -->
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-4 flex-grow">
                                {{ Str::limit($survey->description, 120) ?? 'Sin descripción.' }}
                            </p>

                            <!-- Footer Metadata -->
                            <div
                                class="mt-auto pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[11px] font-medium text-gray-400 dark:text-gray-550">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-1.5" title="Votos recibidos">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span>{{ $survey->unique_voters_count }}
                                            {{ trans_choice('Votante|Votantes', $survey->unique_voters_count) }}</span>
                                    </div>
                                    @if ($survey->expires_at)
                                        <div class="flex items-center gap-1" title="Fecha de expiración">
                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>{{ $survey->expires_at->diffForHumans() }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    <img src="{{ $survey->creator->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($survey->creator->name) }}"
                                        class="w-4 h-4 rounded-full border border-white dark:border-gray-800">
                                    <span
                                        class="truncate max-w-[80px]">{{ explode(' ', $survey->creator->name)[0] }}</span>
                                </div>
                            </div>

                            <!-- Accent background glow effect on hover -->
                            <div
                                class="absolute -right-6 -top-6 w-24 h-24 bg-violet-500/5 dark:bg-violet-400/5 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none">
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- Empty state for search results -->
                <div x-show="search !== '' && filteredCount === 0" x-transition x-cloak
                    class="mt-12 p-20 text-center bg-gray-50/50 dark:bg-gray-800/20 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800">
                    <div
                        class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-white dark:bg-gray-900 shadow-xl mb-6 text-gray-400">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __('No hay resultados') }}
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto font-medium">
                        {{ __('No hemos encontrado ninguna encuesta que coincida con') }} "<span x-text="search"
                            class="text-indigo-600 font-bold"></span>". {{ __('Prueba con otros términos.') }}
                    </p>
                    <button @click="search = ''"
                        class="mt-6 text-sm font-black uppercase tracking-widest text-indigo-600 hover:text-indigo-700 transition-colors">
                        {{ __('Limpiar búsqueda') }}
                    </button>
                </div>

                <div class="mt-12" x-show="search === ''">
                    {{ $surveys->links() }}
                </div>
            @endif
    </div>
</x-app-layout>
