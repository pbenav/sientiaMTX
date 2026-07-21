<x-app-layout maxWidth="[1600px]">
    @php
        $isGlobal = is_null($survey->team_id);
        $routePrefix = $isGlobal ? 'global-surveys.' : 'teams.surveys.';
        $contextTeam = $isGlobal ? null : ($team ?? $survey->team);
    @endphp

    <div class="py-8 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-gray-50 to-white dark:from-gray-950 dark:to-gray-900 min-h-screen">
        <div class="max-w-full mx-auto">
            
    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ route($routePrefix . 'index', $contextTeam ? [$contextTeam] : []) }}"
                    class="print-hide mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @if(!$isGlobal)
                        <div class="print-hide">
                            @include('teams.partials.breadcrumb')
                        </div>
                    @endif
                    <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        {{ $survey->title }}
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                @if($survey->is_public && $survey->uuid)
                    <button type="button" @click='navigator.clipboard.writeText("{{ route("public.surveys.show", $survey->uuid) }}"); Swal.fire({title:"Enlace Copiado", text:"El enlace público de la encuesta ha sido copiado al portapapeles", icon:"success", toast:true, position:"top-end", showConfirmButton:false, timer:3000})'
                            class="flex items-center gap-2 px-4 py-2.5 bg-fuchsia-50 dark:bg-fuchsia-900/20 text-fuchsia-600 dark:text-fuchsia-400 font-bold rounded-2xl hover:bg-fuchsia-100 dark:hover:bg-fuchsia-900/40 transition-colors border border-fuchsia-100 dark:border-fuchsia-800/50 shadow-sm"
                            title="{{ __('Copiar Enlace Público') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        <span class="text-sm hidden sm:inline">{{ __('Enlace Público') }}</span>
                    </button>
                @endif

                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="p-3 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 text-gray-500 hover:text-indigo-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute right-0 mt-3 w-64 bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 py-3 z-50">
                        
                        @can('update', $survey)
                        <a href="{{ route($routePrefix . 'edit', $contextTeam ? [$contextTeam, $survey] : [$survey]) }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            {{ __('Editar Encuesta') }}
                        </a>

                        @if(!$survey->is_closed)
                            <form action="{{ route($routePrefix . 'close', $contextTeam ? [$contextTeam, $survey] : [$survey]) }}" method="POST">
                                @csrf @method('POST')
                                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ __('Cerrar Encuesta') }}
                                </button>
                            </form>
                        @else
                            <form action="{{ route($routePrefix . 'reactivate', $contextTeam ? [$contextTeam, $survey] : [$survey]) }}" method="POST">
                                @csrf @method('POST')
                                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ __('Reactivar Encuesta') }}
                                </button>
                            </form>
                        @endif
                        @endcan

                        @if($contextTeam && auth()->user()->is_admin)
                            <form action="{{ route('teams.surveys.duplicate', [$contextTeam, $survey]) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                                    {{ __('Promocionar a Global') }}
                                </button>
                            </form>
                        @endif

                        @if($isGlobal && auth()->user()->teams()->exists())
                            <div x-data="{ showCloning: false }" class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
                                <button @click="showCloning = !showCloning" class="w-full flex items-center justify-between text-sm font-bold text-indigo-600 hover:text-indigo-700 transition-colors">
                                    <span>{{ __('Clonar a un Equipo') }}</span>
                                    <svg class="w-4 h-4" :class="{'rotate-180': showCloning}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <div x-show="showCloning" class="mt-2 space-y-1">
                                    @foreach(auth()->user()->teams as $userTeam)
                                        <form action="{{ route('global-surveys.duplicate', $survey) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="target_team_id" value="{{ $userTeam->id }}">
                                            <button type="submit" class="w-full text-left px-2 py-1.5 text-xs font-bold text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                                {{ $userTeam->name }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="my-2 border-t border-gray-100 dark:border-gray-800"></div>

                        <button onclick="window.print()" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            {{ __('Imprimir Informe') }}
                        </button>

                        @can('delete', $survey)

                        <a href="{{ route($contextTeam ? 'teams.surveys.export-json' : 'global-surveys.export-json', $contextTeam ? [$contextTeam, $survey] : [$survey]) }}" 
                           class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            {{ __('Exportar JSON') }}
                        </a>

                        <form action="{{ route($routePrefix . 'destroy', $contextTeam ? [$contextTeam, $survey] : [$survey]) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta encuesta?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                {{ __('Eliminar Encuesta') }}
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        @if(!$isGlobal)
            @include('teams.partials.team-view-nav', ['switcherClass' => 'mt-8 mb-4 flex w-full view-switcher-container'])
        @endif
    </x-slot>

            @if($isGlobal)
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-12 items-start">
                <!-- Columna principal (Encuesta): 8 columnas en LG -->
                <div class="lg:col-span-8 bg-white dark:bg-gray-900 rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden flex flex-col">
            @else
                <!-- Main Content Card -->
                <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden mb-12">
            @endif
                <!-- Status Banner -->
                <div class="print-hide px-8 py-4 bg-gray-50 dark:bg-gray-800/50 flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white dark:bg-gray-900 rounded-xl shadow-sm">
                                <span class="w-2.5 h-2.5 block rounded-full {{ $survey->is_closed ? 'bg-red-500' : 'bg-emerald-500 animate-pulse' }}"></span>
                            </div>
                            <span class="text-[11px] sm:text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                {{ $survey->is_closed ? __('Finalizada') : __('Activa') }}
                            </span>
                        </div>
                        
                        <div class="h-4 w-px bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>

                        <div class="flex items-center gap-2">
                            <img src="{{ $survey->creator->profile_photo_url }}" class="w-6 h-6 rounded-full border border-white dark:border-gray-800 shadow-sm">
                            <span class="text-[11px] sm:text-xs font-black uppercase tracking-widest text-gray-400">{{ __('Por') }}</span>
                            <span class="text-[11px] sm:text-xs font-black uppercase tracking-widest text-gray-600 dark:text-gray-300">{{ $survey->creator->name }}</span>
                        </div>
                    </div>

                    @if($survey->expires_at)
                        <div class="flex items-center gap-2 text-[11px] sm:text-xs font-black uppercase tracking-widest text-gray-400">
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $survey->is_expired ? __('Expiró') : __('Finaliza') }} {{ $survey->expires_at->diffForHumans() }}
                        </div>
                    @endif
                </div>

                <div class="p-6 sm:p-8">
                    @if(session('success'))
                        <div class="mb-10 p-6 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-[2rem] flex items-center gap-4 text-emerald-700 dark:text-emerald-400">
                            <div class="p-3 bg-emerald-600 text-white rounded-2xl shadow-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="font-black uppercase tracking-wider text-sm">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if($showResults)
                        <div class="mb-12">
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex flex-col">
                                    <h2 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight uppercase">
                                        {{ __('Resumen de Resultados') }}
                                    </h2>
                                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ __('Métricas clave y participación en tiempo real') }}</p>
                                </div>
                                <button onclick="window.print()" class="print-hide flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-indigo-500/20 active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    {{ __('Imprimir Reporte') }}
                                </button>
                            </div>
                            <!-- Top KPI Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                                <!-- Total Votes KPI -->
                                <div class="bg-white dark:bg-gray-800/60 p-5 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm flex items-center justify-between group hover:border-indigo-500/50 transition-all duration-300">
                                    <div class="min-w-0">
                                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('Participación') }}</p>
                                        <h4 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white">{{ $totalVotes }}</h4>
                                        <p class="text-[9px] font-bold text-emerald-500 mt-1 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                            {{ __('Votantes únicos') }}
                                        </p>
                                    </div>
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                </div>

                                <!-- Status KPI -->
                                <div class="bg-white dark:bg-gray-800/60 p-5 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm flex items-center justify-between group hover:border-indigo-500/50 transition-all duration-300">
                                    <div class="min-w-0">
                                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('Estado') }}</p>
                                        <h4 class="text-lg sm:text-xl font-black {{ $survey->is_closed ? 'text-red-500' : 'text-emerald-500' }} uppercase tracking-tight">
                                            {{ $survey->is_closed ? __('Cerrada') : __('Abierta') }}
                                        </h4>
                                        <p class="text-[9px] font-bold text-gray-400 mt-1">
                                            {{ $survey->is_closed ? __('Finalizado el plazo') : __('Recibiendo respuestas') }}
                                        </p>
                                    </div>
                                    <div class="w-12 h-12 rounded-2xl {{ $survey->is_closed ? 'bg-red-50 dark:bg-red-500/10 text-red-500' : 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500' }} flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                </div>

                                <!-- Questions KPI -->
                                <div class="bg-white dark:bg-gray-800/60 p-5 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm flex items-center justify-between group hover:border-indigo-500/50 transition-all duration-300">
                                    <div class="min-w-0">
                                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('Estructura') }}</p>
                                        <h4 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white">{{ $survey->questions->count() }}</h4>
                                        <p class="text-[9px] font-bold text-indigo-500 mt-1">
                                            {{ __('Preguntas totales') }}
                                        </p>
                                    </div>
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                </div>

                                <!-- Visibility KPI -->
                                <div x-data="{ copied: false }"
                                     @if($survey->is_public && $survey->uuid) 
                                        @click='navigator.clipboard.writeText("{{ route("public.surveys.show", $survey->uuid) }}"); copied = true; setTimeout(() => copied = false, 3000); Swal.fire({title:"Enlace Copiado", text:"El enlace público de la encuesta ha sido copiado al portapapeles", icon:"success", toast:true, position:"top-end", showConfirmButton:false, timer:3000})' 
                                        class="cursor-pointer bg-white dark:bg-gray-800/60 p-5 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm flex items-center justify-between group hover:border-violet-500/50 hover:bg-violet-50/50 dark:hover:bg-violet-900/10 transition-all duration-300"
                                        title="{{ __('Copiar Enlace Público') }}"
                                     @else 
                                        class="bg-white dark:bg-gray-800/60 p-5 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm flex items-center justify-between group hover:border-indigo-500/50 transition-all duration-300"
                                     @endif>
                                    <div class="min-w-0">
                                        <p class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">{{ __('Ámbito') }}</p>
                                        <h4 class="text-sm sm:text-base font-black text-gray-900 dark:text-white uppercase tracking-tight truncate">
                                            {{ $isGlobal ? __('Global') : ($contextTeam->name ?? __('Equipo')) }}
                                        </h4>
                                        <p class="text-[9px] font-bold text-violet-500 mt-1 transition-colors duration-300 group-hover:text-violet-600">
                                            @if($survey->is_public && $survey->uuid)
                                                <span x-show="!copied">{{ __('Acceso Público (Clic para copiar enlace)') }}</span>
                                                <span x-show="copied" x-cloak class="text-emerald-500">{{ __('¡Enlace Copiado!') }}</span>
                                            @else
                                                {{ $isGlobal ? __('Toda la plataforma') : __('Acceso restringido al equipo') }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 group-hover:scale-110 transition-all duration-300"
                                         :class="copied ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500' : ''">
                                        <svg x-show="!copied" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                        <svg x-show="copied" x-cloak class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-4">
                                @foreach($survey->questions as $question)
                                    <div x-data="{ showModal: false }" 
                                         class="bg-white dark:bg-gray-800/40 p-3 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col h-full hover:shadow-md transition-all cursor-pointer hover:border-indigo-500/50 group/card"
                                         @click="showModal = true">
                                        <div class="flex items-start justify-between mb-2 border-l-2 border-indigo-600 pl-3">
                                            <div class="min-w-0">
                                                <h3 class="text-xs sm:text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight" title="{{ $question->title }}">{{ $question->title }}</h3>
                                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">
                                                    {{ $question->type !== 'text' ? $question->votes()->count() . ' ' . __('Respuestas') : __('Pregunta abierta') }}
                                                </p>
                                            </div>
                                            <div class="shrink-0 text-indigo-400 opacity-0 group-hover/card:opacity-100 transition-opacity">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                            </div>
                                        </div>
                                        
                                        @if($question->type !== 'text')
                                            <div class="flex flex-1 items-center gap-4">
                                                <!-- Legend (Ultra compact KPI style) -->
                                                <div class="flex-1 space-y-2">
                                                    @php 
                                                        $qTotalVotes = $question->votes()->count();
                                                        $maxVotes = $question->options->max('votes_count');
                                                    @endphp
                                                    @foreach($question->options->sortByDesc('votes_count')->take(3) as $option)
                                                        @php
                                                            $percentage = $qTotalVotes > 0 ? round(($option->votes_count / $qTotalVotes) * 100, 0) : 0;
                                                            $isWinner = $qTotalVotes > 0 && $maxVotes === $option->votes_count;
                                                        @endphp
                                                        <div class="relative">
                                                            <div class="flex items-center justify-between mb-1 px-0.5">
                                                                <span class="text-[11px] sm:text-xs font-bold {{ $isWinner ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500' }}">
                                                                    {{ $option->label }}
                                                                </span>
                                                                <span class="text-xs font-black {{ $isWinner ? 'text-indigo-600' : 'text-gray-400' }}">
                                                                    {{ $percentage }}%
                                                                </span>
                                                            </div>
                                                            <div class="h-1 w-full bg-gray-100 dark:bg-gray-800/50 rounded-full overflow-hidden">
                                                                <div class="h-full rounded-full {{ $isWinner ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-700' }}"
                                                                     style="width: {{ $percentage }}%">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <!-- Mini Chart -->
                                                <div class="relative shrink-0 flex justify-center">
                                                    <div class="w-[70px] h-[70px]">
                                                        <canvas id="chart-{{ $question->id }}" 
                                                                data-type="{{ $question->type }}" 
                                                                data-labels='@json($question->options->pluck("label"))'
                                                                data-values='@json($question->options->pluck("votes_count"))'></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Compact Text Answers -->
                                            <div class="flex-1 mt-2 space-y-2 overflow-y-auto max-h-[80px] custom-scrollbar pr-2 pointer-events-none">
                                                @foreach($question->votes->take(5) as $vote)
                                                    @if($vote->text_value)
                                                        <div class="p-2 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50">
                                                            <p class="text-[10px] text-gray-600 dark:text-gray-400 font-medium leading-tight italic line-clamp-2">"{{ $vote->text_value }}"</p>
                                                            <div class="flex items-center gap-1.5 mt-1">
                                                                @if($vote->user)
                                                                    <img src="{{ $vote->user->profile_photo_url }}" class="w-3.5 h-3.5 rounded-full">
                                                                    <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">{{ $vote->user->name }}</span>
                                                                @else
                                                                    <div class="w-3.5 h-3.5 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center shrink-0">
                                                                        <svg class="w-2.5 h-2.5 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                                                    </div>
                                                                    <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">{{ __('Anónimo') }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        <!-- Modal for Answers (All Types) -->
                                        <div x-show="showModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" style="display: none;">
                                            <div x-show="showModal" x-transition.opacity class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showModal = false"></div>
                                            <div x-show="showModal" 
                                                 x-transition:enter="transition ease-out duration-300"
                                                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                                 x-transition:leave="transition ease-in duration-200"
                                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                                 x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                                                 class="relative w-full max-w-2xl bg-white dark:bg-gray-900 rounded-[2rem] shadow-2xl overflow-hidden flex flex-col max-h-[85vh] cursor-default"
                                                 @click.stop>
                                                
                                                <!-- Modal Header -->
                                                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0 bg-gray-50/50 dark:bg-gray-800/50">
                                                    <div class="flex items-center gap-3">
                                                        <div class="p-2 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 rounded-xl">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                                                        </div>
                                                        <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ $question->title }}</h3>
                                                    </div>
                                                    <button @click="showModal = false" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>

                                                <!-- Modal Body -->
                                                <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-gray-50/30 dark:bg-gray-900/30">
                                                    <div class="space-y-4">
                                                        @if($question->type === 'text')
                                                            @foreach($question->votes as $vote)
                                                                @if($vote->text_value)
                                                                    <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-sm">
                                                                        <p class="text-sm text-gray-700 dark:text-gray-300 font-medium leading-relaxed italic">"{{ $vote->text_value }}"</p>
                                                                        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-50 dark:border-gray-700/50">
                                                                            @if($vote->user)
                                                                                <img src="{{ $vote->user->profile_photo_url }}" class="w-5 h-5 rounded-full border border-gray-100 dark:border-gray-700">
                                                                                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">{{ $vote->user->name }}</span>
                                                                            @else
                                                                                <div class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center shrink-0">
                                                                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                                                                </div>
                                                                                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">{{ __('Anónimo') }}</span>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            <div class="flex justify-center mb-8 mt-4">
                                                                <div class="w-full max-w-[300px] sm:max-w-[400px]">
                                                                    <canvas id="chart-modal-{{ $question->id }}" 
                                                                            data-type="{{ $question->type }}" 
                                                                            data-labels='@json($question->options->pluck("label"))'
                                                                            data-values='@json($question->options->pluck("votes_count"))'></canvas>
                                                                </div>
                                                            </div>
                                                            <!-- Detail Legend -->
                                                            <div class="space-y-3">
                                                                @php 
                                                                    $qTotalVotes = $question->votes()->count();
                                                                    $maxVotes = $question->options->max('votes_count');
                                                                @endphp
                                                                @foreach($question->options->sortByDesc('votes_count') as $option)
                                                                    @php
                                                                        $percentage = $qTotalVotes > 0 ? round(($option->votes_count / $qTotalVotes) * 100, 0) : 0;
                                                                        $isWinner = $qTotalVotes > 0 && $maxVotes === $option->votes_count;
                                                                    @endphp
                                                                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                                                                        <div class="flex items-center justify-between mb-2">
                                                                            <span class="text-sm font-bold {{ $isWinner ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300' }}">
                                                                                {{ $option->label }}
                                                                            </span>
                                                                            <div class="flex items-center gap-3">
                                                                                <span class="text-xs font-medium text-gray-500">{{ $option->votes_count }} votos</span>
                                                                                <span class="text-sm font-black {{ $isWinner ? 'text-indigo-600' : 'text-gray-400' }}">{{ $percentage }}%</span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="h-2 w-full bg-gray-100 dark:bg-gray-800/50 rounded-full overflow-hidden">
                                                                            <div class="h-full rounded-full {{ $isWinner ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-700' }}"
                                                                                 style="width: {{ $percentage }}%">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!$hasVoted || ($hasVoted && !$survey->is_closed))
                    <div x-data="{ showForm: @json(!$hasVoted) }">
                        @if($hasVoted)
                            <div class="flex justify-center mb-12">
                                <button @click="showForm = !showForm" 
                                        class="px-8 py-4 bg-white dark:bg-gray-900 border-2 border-indigo-100 dark:border-indigo-800 rounded-2xl text-sm font-black uppercase tracking-widest text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all shadow-sm flex items-center gap-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span x-text="showForm ? '{{ __('Ocultar mis respuestas') }}' : '{{ __('Cambiar mis respuestas') }}'"></span>
                                </button>
                            </div>
                        @endif

                        @php
                            $initialAnswers = $userVotes->mapWithKeys(function($votes, $qId) use ($survey) {
                                $question = $survey->questions->where('id', $qId)->first();
                                if (!$question) return [];
                                
                                if ($question->type === 'text') {
                                    return [$qId => $votes->first()->text_value ?? ''];
                                } elseif ($question->type === 'multiple_choice') {
                                    return [$qId => $votes->pluck('option_id')->toArray()];
                                } else {
                                    // single_choice o rating (asumiendo que rating guarda option_id o valor similar)
                                    return [$qId => $votes->first()->option_id ?? ''];
                                }
                            });
                        @endphp

                        <div x-show="showForm" x-collapse x-cloak class="space-y-12" x-data="votingManager(@json($initialAnswers))">
                            <form action="{{ route($routePrefix . 'vote', $contextTeam ? [$contextTeam, $survey] : [$survey]) }}" method="POST" id="survey-form">
                                @csrf
                                
                                <div class="space-y-8">
                                    @foreach($survey->questions as $index => $question)
                                        <div class="relative">
                                            <!-- Question Label -->
                                            <div class="flex items-start gap-4 mb-4">
                                                <div class="w-8 h-8 shrink-0 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center font-black text-base text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20">
                                                    {{ $index + 1 }}
                                                </div>
                                                <div>
                                                    <h3 class="text-base font-black text-gray-900 dark:text-white tracking-tight uppercase mb-1">{{ $question->title }}</h3>
                                                    @if($question->description)
                                                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ $question->description }}</p>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Question Content -->
                                            <div class="pl-0 sm:pl-12">
                                                @if($question->type === 'single_choice' || $question->type === 'multiple_choice')
                                                    <div class="grid grid-cols-1 gap-4">
                                                        @foreach($question->options as $option)
                                                            <label class="group relative flex items-center p-4 bg-gray-50 dark:bg-gray-800/50 border-2 rounded-2xl cursor-pointer transition-all duration-300 hover:shadow-xl hover:shadow-indigo-500/5"
                                                                   :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-500/10' : 'border-transparent'">
                                                                
                                                                <div class="relative flex items-center justify-center w-5 h-5 border-2 border-gray-300 dark:border-gray-600 rounded-md group-hover:border-indigo-500 transition-colors overflow-hidden"
                                                                     :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'border-indigo-600' : ''">
                                                                    <input type="{{ $question->type === 'single_choice' ? 'radio' : 'checkbox' }}" 
                                                                           name="answers[{{ $question->id }}]{{ $question->type === 'multiple_choice' ? '[]' : '' }}" 
                                                                           value="{{ $option->id }}"
                                                                           x-model="answers['{{ $question->id }}']"
                                                                           class="peer absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                                                    
                                                                    <div class="w-full h-full bg-indigo-600 p-1 transition-opacity duration-300 pointer-events-none"
                                                                         :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'opacity-100' : 'opacity-0'">
                                                                        <svg class="w-full h-full text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/></svg>
                                                                    </div>
                                                                </div>

                                                                <div class="ml-4">
                                                                    <span class="block text-sm font-bold text-gray-900 dark:text-white transition-colors group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                                                                          :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'text-indigo-600 dark:text-indigo-400' : ''">
                                                                        {{ $option->label }}
                                                                    </span>
                                                                    @if($option->description)
                                                                        <span class="block text-sm text-gray-500 dark:text-gray-400 font-medium">
                                                                            {{ $option->description }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @elseif($question->type === 'rating')
                                                    <div class="flex flex-col items-center p-6 bg-gray-50 dark:bg-gray-800/30 rounded-3xl border border-gray-100 dark:border-gray-800">
                                                        <div class="flex gap-4">
                                                            <template x-for="i in 5">
                                                                <button type="button" @click="answers['{{ $question->id }}'] = i" 
                                                                        class="p-2 transition-all duration-300 transform hover:scale-125"
                                                                        :class="answers['{{ $question->id }}'] >= i ? 'text-amber-400 drop-shadow-xl' : 'text-gray-300 dark:text-gray-600'">
                                                                    <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                                </button>
                                                            </template>
                                                        </div>
                                                        <input type="hidden" name="answers[{{ $question->id }}]" :value="answers['{{ $question->id }}']">
                                                        <div class="mt-4 text-xs font-black uppercase tracking-[0.2em] text-gray-400">
                                                            <span x-show="!answers['{{ $question->id }}']">{{ __('Haz clic para valorar') }}</span>
                                                            <span x-show="answers['{{ $question->id }}']" x-text="answers['{{ $question->id }}'] + ' / 5 Estrellas'"></span>
                                                        </div>
                                                    </div>
                                                @elseif($question->type === 'text')
                                                    <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-4 border-2 border-transparent focus-within:border-indigo-500/50 transition-all duration-300">
                                                        <textarea name="answers[{{ $question->id }}]" rows="3" x-model="answers['{{ $question->id }}']"
                                                                  class="w-full bg-transparent border-none focus:ring-0 text-base text-gray-900 dark:text-white placeholder-gray-400 font-medium resize-none"
                                                                  placeholder="{{ __('Escribe aquí tus ideas o comentarios...') }}"></textarea>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-10 pt-8 border-t border-gray-100 dark:border-gray-800 flex justify-center">
                                    <button type="submit" 
                                            class="group relative inline-flex items-center justify-center px-10 py-4 font-bold text-white tracking-wider uppercase transition-all duration-500 ease-in-out transform bg-indigo-600 rounded-2xl hover:scale-105 active:scale-95 shadow-[0_20px_50px_rgba(79,70,229,0.3)] hover:shadow-[0_20px_50px_rgba(79,70,229,0.5)] overflow-hidden">
                                        <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700"></div>
                                        <span class="relative flex items-center gap-2 text-lg">
                                            {{ $hasVoted ? __('Actualizar mis respuestas') : __('Enviar Encuesta') }}
                                            <svg class="w-5 h-5 transition-transform duration-500 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @if($isGlobal)
                <!-- Columna lateral (Organismo Emisor / Transparencia) -->
                <div class="lg:col-span-4 space-y-6 print-hide">
                    <!-- Tarjeta de Información Oficial del Canal Ciudadano -->
                    <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden p-6 sm:p-8 flex flex-col gap-6">
                        <!-- Cabecera Institucional -->
                        <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                            <div class="p-3 bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400 rounded-2xl shadow-sm">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.5H4.5V21M3 21h18M12 6.75a.75.75 0 11-.75.75.75.75 0 01.75-.75z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-violet-600 dark:text-violet-400 uppercase tracking-widest">{{ __('Canal Ciudadano') }}</h3>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('Consulta Pública y Transparencia') }}</p>
                            </div>
                        </div>

                        <!-- Organismo Promotor -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('Entidad Promotora') }}</h4>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                                <img src="{{ $survey->creator->profile_photo_url }}" class="w-12 h-12 rounded-full border border-white dark:border-gray-800 shadow-md">
                                <div class="min-w-0 flex-1">
                                    <span class="block text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight truncate">{{ $survey->creator->name }}</span>
                                    <span class="inline-flex items-center gap-1.5 text-[9px] font-black text-violet-600 dark:text-violet-400 uppercase tracking-widest mt-1 bg-violet-50 dark:bg-violet-500/10 px-2 py-0.5 rounded-full">
                                        {{ $survey->creator->working_area_name ?: __('Ayuntamiento General') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Mapa Interactivo / Localización -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('Ubicación del Centro') }}</h4>
                            <div class="relative w-full h-[220px] rounded-[1.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-inner">
                                <div id="promoter-map" class="w-full h-full"></div>
                                <div class="absolute bottom-3 left-3 right-3 bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm p-3 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 z-[1000] flex items-center gap-2.5">
                                    <div class="p-1.5 bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <span class="block text-[10px] font-black text-gray-900 dark:text-white uppercase tracking-tight truncate">{{ $survey->creator->working_area_name ?: __('Ayuntamiento General') }}</span>
                                        <span class="block text-[9px] font-bold text-gray-400 truncate">
                                            @if($survey->creator->location_lat && $survey->creator->location_lng)
                                                Lat: {{ number_format($survey->creator->location_lat, 4) }}, Lng: {{ number_format($survey->creator->location_lng, 4) }}
                                            @else
                                                {{ __('Ubicación Centralizada') }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nota de Privacidad y RGPD -->
                        <div class="p-4 bg-emerald-50/50 dark:bg-emerald-500/5 rounded-2xl border border-emerald-100/50 dark:border-emerald-500/10 flex items-start gap-3">
                            <div class="p-2 bg-emerald-600 text-white rounded-xl shadow-lg shadow-emerald-600/20 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                            </div>
                            <div class="flex-1">
                                <span class="block text-xs font-black text-emerald-800 dark:text-emerald-400 uppercase tracking-widest mb-1">{{ __('Garantía de Confidencialidad') }}</span>
                                <p class="text-[10px] text-emerald-700/80 dark:text-emerald-400/80 font-bold leading-normal">
                                    {{ __('Tus respuestas son totalmente anónimas y se procesan de acuerdo con el RGPD para la mejora continua de la gestión ciudadana.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- Cierre del grid -->
            @endif

        </div>
    </div>

    <!-- Chart.js and Initialization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#94a3b8' : '#64748b';
            
            const colors = [
                '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f59e0b', 
                '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6'
            ];

            // Apply colors to indicators
            document.querySelectorAll('.chart-color-indicator').forEach(indicator => {
                const index = parseInt(indicator.dataset.index);
                indicator.style.backgroundColor = colors[index % colors.length];
            });

            // Initialize Charts
            document.querySelectorAll('canvas[id^="chart-"]').forEach(canvas => {
                const ctx = canvas.getContext('2d');
                const type = canvas.dataset.type;
                const labels = JSON.parse(canvas.dataset.labels);
                const values = JSON.parse(canvas.dataset.values);

                let chartConfig = {
                    type: type === 'single_choice' ? 'doughnut' : 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: type === 'single_choice' ? colors : colors[0],
                            borderRadius: type === 'single_choice' ? 0 : 8,
                            borderWidth: 0,
                            hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                };

                if (type === 'multiple_choice' || type === 'rating') {
                    chartConfig.options.indexAxis = type === 'multiple_choice' ? 'y' : 'x';
                    chartConfig.options.scales = {
                        y: {
                            beginAtZero: true,
                            grid: { display: false },
                            ticks: { color: textColor, font: { weight: 'bold' } }
                        },
                        x: {
                            beginAtZero: true,
                            grid: { display: false },
                            ticks: { color: textColor, font: { weight: 'bold' } }
                        }
                    };
                }

                if (type === 'single_choice') {
                    chartConfig.options.cutout = '60%';
                }

                new Chart(ctx, chartConfig);
            });
        });

        function votingManager(initialAnswers) {
            return {
                answers: {},
                init() {
                    // Inicializar el objeto answers con los datos que vienen del servidor
                    // initialAnswers debe ser un objeto: { question_id: [option_ids] o valor }
                    const data = initialAnswers || {};
                    
                    // Procesamos todas las preguntas para asegurar que existan en el modelo de Alpine
                    @foreach($survey->questions as $question)
                        @php
                            $qId = $question->id;
                            $type = $question->type;
                        @endphp
                        
                        if (data['{{ $qId }}'] !== undefined) {
                            @if($type === 'multiple_choice')
                                // Checkboxes esperan un array de strings
                                this.answers['{{ $qId }}'] = Array.isArray(data['{{ $qId }}']) 
                                    ? data['{{ $qId }}'].map(String) 
                                    : [String(data['{{ $qId }}'])];
                            @elseif($type === 'single_choice' || $type === 'rating')
                                // Radios y rating esperan un string o número único
                                const val = data['{{ $qId }}'];
                                this.answers['{{ $qId }}'] = Array.isArray(val) ? String(val[0]) : String(val);
                            @else
                                // Texto
                                this.answers['{{ $qId }}'] = String(data['{{ $qId }}']);
                            @endif
                        } else {
                            // Inicializar vacíos para evitar que Laravel no reciba el campo
                            @if($type === 'multiple_choice')
                                this.answers['{{ $qId }}'] = [];
                            @else
                                this.answers['{{ $qId }}'] = '';
                            @endif
                        }
                    @endforeach
                },
                isSelected(qId, oId, type) {
                    const current = this.answers[String(qId)];
                    if (current === undefined || current === null || current === '') return false;
                    
                    if (type === 'single_choice') {
                        return String(current) === String(oId);
                    } else if (type === 'multiple_choice') {
                        return Array.isArray(current) && current.includes(String(oId));
                    }
                    return false;
                }
            }
        }
    </script>

    <style>
        @media print {
            @page {
                size: auto;
                margin: 0.5cm;
            }

            .print-hide { display: none !important; }
            
            body { 
                background: white !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Reset layout constraints */
            .py-8, .px-4, .sm\:px-6, .lg\:px-8 { padding: 0 !important; }
            .max-w-7xl { max-width: none !important; width: 100% !important; margin: 0 !important; }
            .bg-gradient-to-b { background: white !important; }
            .min-h-screen { min-height: 0 !important; }

            /* Remove main card decoration */
            .bg-white.dark\:bg-gray-900.rounded-\[2rem\].shadow-2xl {
                background: transparent !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                margin-bottom: 0 !important;
                overflow: visible !important;
                display: block !important;
            }

            /* Container preservation - Ultra compact */
            .bg-gray-50 {
                background-color: #f9fafb !important;
                border-radius: 1rem !important;
                padding: 0.5rem !important;
                margin: 0.25rem !important;
            }

            /* Main padding reduction and layout block fix */
            .p-8, .sm\:p-12, .pb-0, .mb-12, .mb-10, .mb-8, .mb-4 { 
                padding: 0 !important; 
                margin-bottom: 0.5rem !important;
                display: block !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
            }

            /* Card preservation */
            .bg-white { 
                background-color: #ffffff !important;
                border-radius: 0.5rem !important;
                border: 1px solid #e5e7eb !important;
                box-shadow: none !important;
            }

            /* Grid Layout - Optimized for A4 */
            .grid {
                display: grid !important;
                gap: 0.5rem !important;
            }
            
            /* KPI Grid (Top cards) - 4 columns */
            .lg\:grid-cols-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            }
            .lg\:grid-cols-4 > div { padding: 0.4rem !important; border-radius: 0.5rem !important; }
            .lg\:grid-cols-4 h4 { font-size: 10pt !important; }

            /* Dashboard Grid (Question cards) - Force 3 columns for maximum density */
            .grid-cols-1.md\:grid-cols-2.xl\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }

            /* Typography Scaling */
            h1 { font-size: 13pt !important; color: #111827 !important; margin-bottom: 0.25rem !important; }
            h2 { font-size: 10pt !important; color: #111827 !important; }
            h3 { font-size: 8.5pt !important; color: #111827 !important; }
            p, span, div { font-size: 7pt !important; color: #374151 !important; }
            
            /* Charts and Indicators - Condensed */
            canvas {
                max-width: 70px !important;
                max-height: 70px !important;
            }
            
            .h-1 { height: 2px !important; }
            .space-y-2 { margin-top: 0.15rem !important; }
            .space-y-2 > * + * { margin-top: 0.15rem !important; }

            /* Page Management */
            .grid-cols-1.md\:grid-cols-2 > div,
            .lg\:grid-cols-4 > div {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            /* Global Layout Fix */
            .lg\:grid-cols-12 {
                display: block !important;
            }
            .lg\:col-span-8 {
                width: 100% !important;
                max-width: 100% !important;
            }
            
            /* Aggressive hiding */
            header, nav, footer, aside, #sidebar, .view-switcher-container, #survey-floating-bar, 
            .status-banner, button:not(.print-hide), x-slot[name="header"] a,
            .animate-pulse {
                display: none !important;
            }

            /* Adjust header gap */
            .flex.flex-col.xl\:flex-row.xl\:items-center.justify-between.gap-6 { gap: 0.25rem !important; }
        }
    </style>

    {{-- BARRA FLOTANTE DE ACCIONES RÁPIDAS --}}
    <div id="survey-floating-bar"
         x-data="floatingDraggable"
         @mousedown="startDrag"
         @touchstart.passive="startDrag"
         @window:mousemove="drag"
         @window:touchmove.passive="drag"
         @window:mouseup="stopDrag"
         @window:touchend="stopDrag"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 translate-y-4 z-50 flex items-center gap-2 p-2.5 bg-white/90 dark:bg-gray-900/90 backdrop-blur-xl border border-gray-100 dark:border-gray-800 rounded-2xl shadow-2xl opacity-0 pointer-events-none transition-all duration-300 whitespace-nowrap cursor-move"
         :class="isDragging ? 'scale-105 shadow-[0_20px_50px_rgba(0,0,0,0.2)]' : ''">
        
        <a href="{{ route($routePrefix . 'index', $contextTeam ? [$contextTeam] : []) }}"
           class="flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-indigo-600 transition-colors rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-500/10">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            <span>{{ __('Volver') }}</span>
        </a>

        <div class="w-px h-5 bg-gray-100 dark:bg-gray-800"></div>

        <span class="px-2 text-[10px] font-black uppercase tracking-tight text-gray-900 dark:text-white max-w-[400px] truncate">
            {{ $survey->title }}
        </span>

        <div class="w-px h-5 bg-gray-100 dark:bg-gray-800"></div>

        <div class="flex items-center gap-1">
            <button onclick="window.print()" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-500/10" title="{{ __('Imprimir Informe') }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            </button>

            @can('update', $survey)
            <a href="{{ route($routePrefix . 'edit', $contextTeam ? [$contextTeam, $survey] : [$survey]) }}" 
               class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                <span>{{ __('Editar') }}</span>
            </a>
            @endcan
        </div>
    </div>

    <script>
        (function() {
            const bar = document.getElementById('survey-floating-bar');
            if (!bar) return;

            window.addEventListener('scroll', () => {
                const scrollY = window.scrollY || document.documentElement.scrollTop;
                if (scrollY > 150) {
                    bar.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
                    bar.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
                } else {
                    bar.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
                    bar.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
                }
            }, { passive: true });
        })();
    </script>
    @if ($isGlobal)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <style>
            #promoter-map {
                height: 100%;
                width: 100%;
                z-index: 1;
            }
            .custom-promoter-pin {
                background: none;
                border: none;
            }
        </style>

        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const lat = {{ $survey->creator->location_lat ?: '40.416775' }};
                    const lng = {{ $survey->creator->location_lng ?: '-3.703790' }};

                    const map = L.map('promoter-map', {
                        zoomControl: false,
                        attributionControl: false
                    }).setView([lat, lng], 13);

                    const isDark = document.documentElement.classList.contains('dark');
                    const cartoUrl = isDark ?
                        'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
                        'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';

                    L.tileLayer(cartoUrl, {
                        maxZoom: 20
                    }).addTo(map);

                    // Icono del PIN premium adaptativo
                    const pinIcon = L.divIcon({
                        html: `
                            <div class="relative w-8 h-8 flex items-center justify-center">
                                <span class="absolute w-6 h-6 bg-violet-500/35 rounded-full animate-ping opacity-75"></span>
                                <div class="w-7.5 h-7.5 bg-gradient-to-tr from-violet-500 to-indigo-600 rounded-full border-2 border-white dark:border-gray-950 flex items-center justify-center text-white shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.5H4.5V21M3 21h18"/></svg>
                                </div>
                            </div>`,
                        className: 'custom-promoter-pin',
                        iconSize: [32, 32],
                        iconAnchor: [16, 16]
                    });

                    L.marker([lat, lng], { icon: pinIcon }).addTo(map);

                    setTimeout(() => {
                        map.invalidateSize();
                    }, 250);
                });
            </script>
        @endpush
    @endif
</x-app-layout>
