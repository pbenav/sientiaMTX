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

        <!-- Action Buttons Row -->
        <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3"
            x-data="{ tab: 'surveys' }" @tab-changed.window="tab = $event.detail">

            <!-- Botones de la pestaña de Encuestas -->
            <div x-show="tab === 'surveys'" class="flex items-center gap-3" x-transition>
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

            <!-- Botones de la pestaña de Citas Previas -->
            @if(auth()->user()->hasAppointmentsEnabled())
                <div x-show="tab === 'map'" class="flex items-center gap-3" x-transition x-cloak>
                    <a href="{{ route('appointments.settings', $team ?: auth()->user()->firstTeamWithAppointments()) }}"
                        class="flex items-center gap-2 text-xs bg-cyan-600 hover:bg-cyan-500 text-white px-5 py-2.5 rounded-xl transition-all font-black shadow-lg shadow-cyan-500/20 active:scale-95 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>{{ __('Configurar Mis Citas y Servicios') }}</span>
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{
        currentTab: 'surveys',
        search: '',
        get filteredCount() {
            return Array.from(document.querySelectorAll('.survey-card')).filter(card => card.style.display !== 'none').length;
        }
    }">

        @if ($isGlobal)
            <!-- Selector de Pestañas del Canal Ciudadano -->
            <div class="flex items-center gap-2 border-b border-gray-150 dark:border-gray-800 pb-4">
                <button type="button" @click="currentTab = 'surveys'; $dispatch('tab-changed', 'surveys')"
                    :class="currentTab === 'surveys' ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/20' :
                        'bg-gray-100 dark:bg-gray-800/80 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    Encuestas Colectivas
                </button>
                <button type="button"
                    @click="currentTab = 'map'; $dispatch('tab-changed', 'map'); setTimeout(() => { if(window.intranetMap) window.intranetMap.invalidateSize() }, 100)"
                    :class="currentTab === 'map' ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/20' :
                        'bg-gray-100 dark:bg-gray-800/80 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all select-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Portal de Citas Previas
                </button>
            </div>
        @endif

        <!-- Pestaña: Encuestas Colectivas -->
        <div x-show="currentTab === 'surveys'" class="space-y-4">
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
                                        <span>{{ $survey->votes_count }}
                                            {{ trans_choice('Voto|Votos', $survey->votes_count) }}</span>
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

        @if ($isGlobal)
            <!-- Pestaña: Portal de Citas Previas -->
            <div x-show="currentTab === 'map'" class="space-y-4" x-cloak>
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm flex flex-col lg:flex-row h-[650px] z-10">

                    <!-- Sidebar de Puntos de Atención -->
                    <div
                        class="lg:w-1/4 bg-gray-50/30 dark:bg-gray-900/30 border-r border-gray-150 dark:border-gray-850 flex flex-col h-full z-10">
                        <div class="p-5 border-b border-gray-150 dark:border-gray-850">
                            <h2
                                class="text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white heading-font">
                                Red de Servicios</h2>
                            <p class="text-[10px] text-gray-400 dark:text-gray-550 font-medium mt-1">Busca un miembro o
                                punto de atención en el mapa interactivo.</p>

                            <div class="relative mt-4">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" id="map-search-input" placeholder="Buscar técnico, área..."
                                    class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-xl text-xs font-bold text-gray-900 dark:text-white outline-none transition-all placeholder-gray-450 dark:placeholder-gray-500 shadow-sm">
                            </div>

                            @if (!empty($allTeams))
                                <div class="mt-2">
                                    <select id="map-team-filter"
                                        class="w-full px-3 py-2 bg-white dark:bg-gray-955 border border-gray-200 dark:border-gray-800 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-xl text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 outline-none transition-all cursor-pointer shadow-sm">
                                        <option value="">🔍 Todos los equipos</option>
                                        @foreach ($allTeams as $teamName)
                                            <option value="{{ $teamName }}">👥 {{ $teamName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>

                        <div id="map-members-list"
                            class="flex-grow overflow-y-auto divide-y divide-gray-100 dark:divide-gray-850">
                            @forelse($members as $m)
                                <div class="map-member-item p-4 hover:bg-violet-50/30 dark:hover:bg-violet-950/15 cursor-pointer transition-colors"
                                    data-lat="{{ $m['lat'] }}" data-lng="{{ $m['lng'] }}"
                                    data-slug="{{ $m['slug'] }}" data-name="{{ $m['display_name'] }}"
                                    data-area="{{ $m['area'] ?? '' }}"
                                    data-teams="{{ json_encode($m['teams'] ?? []) }}">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-gradient-to-tr from-violet-500 to-indigo-600 flex items-center justify-center text-white shrink-0 shadow-sm">
                                            <span
                                                class="text-xs font-black uppercase">{{ substr($m['display_name'], 0, 2) }}</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="text-xs font-black text-gray-900 dark:text-white truncate">
                                                {{ $m['display_name'] }}</h3>
                                            @if (!empty($m['area']))
                                                <p
                                                    class="text-[9px] font-bold text-violet-600 dark:text-violet-400 uppercase tracking-wider mt-0.5">
                                                    {{ $m['area'] }}</p>
                                            @endif
                                            <div
                                                class="flex items-center gap-2 mt-1.5 text-[9px] text-gray-400 dark:text-gray-550 font-bold">
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-violet-500 shrink-0" fill="none"
                                                        stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2" />
                                                    </svg>
                                                    {{ $m['services'] }}
                                                    {{ $m['services'] == 1 ? 'servicio' : 'servicios' }}
                                                </span>
                                            </div>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 mt-1 self-center"
                                            fill="none" stroke="currentColor" stroke-width="2.5"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            @empty
                                <div class="p-8 text-center">
                                    <p class="text-2xl mb-1">🗺️</p>
                                    <p class="text-xs font-bold text-gray-450 dark:text-gray-550">Sin miembros
                                        geolocalizados</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Contenedor del Mapa -->
                    <div class="lg:w-3/4 relative h-full">
                        <div id="intranet-map" class="w-full h-full"></div>
                    </div>

                </div>
            </div>
        @endif
    </div>
</x-app-layout>

@if ($isGlobal)
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <style>
            #intranet-map {
                height: 100%;
                min-height: 400px;
                width: 100%;
            }

            .custom-pin {
                background: none;
                border: none;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Coordenadas iniciales por defecto (España / Centro)
                const defaultLat = 40.416775;
                const defaultLng = -3.703790;

                // Crear mapa
                const map = L.map('intranet-map', {
                    zoomControl: false
                }).setView([defaultLat, defaultLng], 6);

                window.intranetMap = map;

                // Control de zoom en la esquina superior derecha
                L.control.zoom({
                    position: 'topright'
                }).addTo(map);

                // Tile layer elegante y adaptativo
                const isDark = document.documentElement.classList.contains('dark');
                const cartoUrl = isDark ?
                    'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
                    'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';

                const tiles = L.tileLayer(cartoUrl, {
                    attribution: '© OpenStreetMap contributors, CartoDB',
                    maxZoom: 20
                }).addTo(map);

                // Icono premium de PIN de mapa
                const pinIcon = L.divIcon({
                    html: `
                        <div class="relative w-8 h-8 flex items-center justify-center">
                            <span class="absolute w-6 h-6 bg-cyan-500/35 rounded-full animate-ping opacity-75"></span>
                            <div class="w-7.5 h-7.5 bg-gradient-to-tr from-cyan-500 to-blue-600 rounded-full border-2 border-white dark:border-gray-950 flex items-center justify-center text-white shadow-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            </div>
                        </div>`,
                    className: 'custom-pin',
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32]
                });

                const members = @json($members);
                const markersGroup = L.featureGroup();
                const markersMap = new Map();

                // Añadir marcadores
                members.forEach(m => {
                    if (m.lat && m.lng) {
                        const marker = L.marker([m.lat, m.lng], {
                            icon: pinIcon
                        });

                        const popupContent = `
                            <div class="p-3 text-gray-900 dark:text-white font-sans max-w-xs">
                                <h4 class="font-black text-sm heading-font">${m.display_name}</h4>
                                ${m.area ? `<p class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400 uppercase mt-0.5 tracking-wider">${m.area}</p>` : ''}
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 font-medium">${m.services} servicios disponibles</p>
                                <a href="/citas/${m.slug}" target="_blank" class="block text-center mt-3 px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-sm transition-all hover:scale-102 select-none">
                                    Ver Ficha y Reservar &rarr;
                                </a>
                            </div>
                        `;

                        marker.bindPopup(popupContent);
                        markersMap.set(m.slug, marker);
                        marker.addTo(markersGroup);
                    }
                });

                markersGroup.addTo(map);

                // Ajustar vista inicial
                if (members.length > 0) {
                    map.fitBounds(markersGroup.getBounds(), {
                        padding: [50, 50]
                    });
                }

                // Filtro y Búsqueda Avanzada
                const searchInput = document.getElementById('map-search-input');
                const teamFilter = document.getElementById('map-team-filter');
                const items = document.querySelectorAll('.map-member-item');

                function applyFilters() {
                    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
                    const selectedTeam = teamFilter ? teamFilter.value : '';

                    const visibleBounds = L.latLngBounds();
                    let visibleCount = 0;

                    items.forEach(item => {
                        const name = item.getAttribute('data-name').toLowerCase();
                        const area = item.getAttribute('data-area').toLowerCase();
                        const teams = JSON.parse(item.getAttribute('data-teams') || '[]');
                        const slug = item.getAttribute('data-slug');
                        const lat = parseFloat(item.getAttribute('data-lat'));
                        const lng = parseFloat(item.getAttribute('data-lng'));

                        const matchesSearch = name.includes(query) || area.includes(query) || teams.some(t => t
                            .toLowerCase().includes(query));
                        const matchesTeam = !selectedTeam || teams.includes(selectedTeam);

                        const marker = markersMap.get(slug);

                        if (matchesSearch && matchesTeam) {
                            item.style.display = 'block';
                            if (marker) {
                                if (!markersGroup.hasLayer(marker)) {
                                    markersGroup.addLayer(marker);
                                }
                                if (!isNaN(lat) && !isNaN(lng)) {
                                    visibleBounds.extend([lat, lng]);
                                    visibleCount++;
                                }
                            }
                        } else {
                            item.style.display = 'none';
                            if (marker && markersGroup.hasLayer(marker)) {
                                markersGroup.removeLayer(marker);
                            }
                        }
                    });

                    if (visibleCount > 0) {
                        map.fitBounds(visibleBounds, {
                            padding: [50, 50],
                            maxZoom: 14
                        });
                    }
                }

                if (searchInput) searchInput.addEventListener('input', applyFilters);
                if (teamFilter) teamFilter.addEventListener('change', applyFilters);

                // Al hacer clic en un miembro
                items.forEach(item => {
                    item.addEventListener('click', function() {
                        const lat = parseFloat(this.getAttribute('data-lat'));
                        const lng = parseFloat(this.getAttribute('data-lng'));
                        const slug = this.getAttribute('data-slug');

                        if (!isNaN(lat) && !isNaN(lng)) {
                            map.setView([lat, lng], 14, {
                                animate: true,
                                duration: 1
                            });

                            const marker = markersMap.get(slug);
                            if (marker && markersGroup.hasLayer(marker)) {
                                marker.openPopup();
                            }
                        }
                    });
                });

                // Cambio dinámico de Tile Layer con Dark Mode
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.attributeName === "class") {
                            const isDark = document.documentElement.classList.contains('dark');
                            const newCartoUrl = isDark ?
                                'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
                                'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
                            tiles.setUrl(newCartoUrl);
                        }
                    });
                });

                observer.observe(document.documentElement, {
                    attributes: true
                });
            });
        </script>
    @endpush
@endif
