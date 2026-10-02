<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500 shrink-0" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                    </svg>
                    <span class="truncate">{{ __('forum.title') ?? 'Foro' }}</span>
                </h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @include('teams.partials.header-toolbar')
            </div>
        </div>

        @include('teams.partials.team-view-nav')

        <div class="mt-2">
            <x-demo-hint>
                El Foro del equipo es un espacio de debate organizado por hilos. Permite discutir ideas, vincular conversaciones a tareas concretas, adjuntar archivos desde Google Drive y mantener un registro permanente del conocimiento y las decisiones grupales.
            </x-demo-hint>
        </div>

        <!-- Action Buttons Row -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
            <div class="flex items-center gap-3 shrink-0">
                <button type="button" x-data="{}"
                    x-on:click.prevent="$dispatch('open-modal', 'create-thread-modal')"
                    class="flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-4 py-2.5 rounded-xl transition-all font-bold shadow-lg shadow-violet-500/20 active:scale-95 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('forum.new_thread') ?? 'Nuevo hilo' }}</span>
                </button>
            </div>

            <!-- Filters & Search -->
            <form action="{{ route('teams.forum.index', $team) }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3 flex-1 justify-end">
                @if($filters['orphaned'] ?? null)
                    <input type="hidden" name="orphaned" value="1">
                @endif

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select name="sort" onchange="this.form.submit()" class="flex-1 sm:flex-none bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold py-2.5 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition-all cursor-pointer text-gray-700 dark:text-gray-300">
                        <option value="updated_at_desc" {{ ($filters['sort'] ?? '') === 'updated_at_desc' ? 'selected' : '' }}>Nuevos</option>
                        <option value="updated_at_asc" {{ ($filters['sort'] ?? '') === 'updated_at_asc' ? 'selected' : '' }}>Antiguos</option>
                        <option value="messages_desc" {{ ($filters['sort'] ?? '') === 'messages_desc' ? 'selected' : '' }}>Con más respuestas</option>
                        <option value="views_desc" {{ ($filters['sort'] ?? '') === 'views_desc' ? 'selected' : '' }}>Más vistos</option>
                        <option value="title_asc" {{ ($filters['sort'] ?? '') === 'title_asc' ? 'selected' : '' }}>Alfabético</option>
                    </select>

                    <select name="limit" onchange="this.form.submit()" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold py-2.5 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition-all cursor-pointer text-gray-700 dark:text-gray-300">
                        <option value="15" {{ ($filters['limit'] ?? 15) == 15 ? 'selected' : '' }}>15</option>
                        <option value="30" {{ ($filters['limit'] ?? 15) == 30 ? 'selected' : '' }}>30</option>
                        <option value="50" {{ ($filters['limit'] ?? 15) == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ ($filters['limit'] ?? 15) == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                <div class="relative group w-full sm:w-auto min-w-[250px]">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400 group-focus-within:text-violet-500 transition-colors" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="{{ __('forum.search_threads') ?? 'Buscar en el foro...' }}"
                           enterkeyhint="search"
                           class="block w-full pl-10 pr-12 py-2.5 {{ !empty($filters['search']) ? 'bg-violet-50/50 dark:bg-violet-900/10 border-violet-300 dark:border-violet-800 ring-2 ring-violet-500/20' : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700' }} border rounded-xl text-sm outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition-all shadow-sm placeholder:text-gray-400 dark:text-white">

                    <div class="absolute inset-y-0 right-2 flex items-center gap-1">
                        @if(!empty($filters['search']))
                            <a href="{{ route('teams.forum.index', [$team, 'reset_filters' => 1]) }}" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors" title="{{ __('forum.clear_search') ?? 'Limpiar búsqueda' }}">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-violet-600 transition-colors" title="Buscar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if($team->isCoordinator(auth()->user()))
            @php
                $orphanCount = $team->forumThreads()->orphaned()->count();
            @endphp
            @if($orphanCount > 0)
                <div class="mb-8 p-6 bg-violet-50 dark:bg-violet-900/10 border border-violet-200 dark:border-violet-800/40 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-4 animate-fade-in shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-violet-900 dark:text-violet-200">{{ __('forum.orphaned_maintenance') }}</h4>
                            <p class="text-xs text-violet-700 dark:text-violet-400">{{ __('forum.orphaned_desc', ['count' => $orphanCount]) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($filters['orphaned'] ?? null)
                            <a href="{{ route('teams.forum.index', $team) }}" class="text-xs font-bold text-gray-500 hover:text-gray-700 px-4 py-2 transition-colors">{{ __('forum.back_to_forum') }}</a>
                        @else
                            <a href="{{ route('teams.forum.index', [$team, 'orphaned' => 1]) }}" class="text-xs font-black uppercase tracking-tighter text-violet-700 hover:text-violet-800 px-4 py-2 transition-colors">{{ __('forum.view_orphans') }}</a>
                        @endif

                        <form action="{{ route('teams.forum.cleanup', $team) }}" method="POST" id="cleanup-form">
                            @csrf
                            <button type="button"
                                    onclick="confirmCleanup()"
                                    class="bg-violet-600 hover:bg-violet-500 text-white text-[10px] font-black uppercase tracking-widest px-5 py-2.5 rounded-xl transition-all shadow-md shadow-violet-600/20 active:scale-95">{{ __('forum.cleanup_stale') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        @endif

        @if ($threads->isEmpty())
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-12 text-center shadow-sm">
                <div
                    class="w-16 h-16 bg-violet-50 dark:bg-violet-900/30 text-violet-500 rounded-2xl flex items-center justify-center mx-auto mb-4 rotate-[-5deg] hover:rotate-0 transition-all duration-300 shadow-sm border border-violet-100 dark:border-violet-800/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                    @if($filters['search'])
                        {{ __('forum.no_results_title') ?? 'No se encontraron resultados para tu búsqueda' }}
                    @else
                        {{ __('forum.empty_title') ?? 'No hay hilos de discusión todavía' }}
                    @endif
                </h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    @if($filters['search'])
                        {{ __('forum.no_results_desc') ?? 'Prueba con otros términos o limpia el buscador.' }}
                    @else
                        {{ __('forum.empty_desc') ?? 'Abre un nuevo hilo para compartir ideas, resolver dudas o documentar decisiones de equipo.' }}
                    @endif
                </p>

                @if($filters['search'])
                    <a href="{{ route('teams.forum.index', [$team, 'reset_filters' => 1]) }}"
                        class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold py-2.5 px-6 rounded-xl transition-all border border-gray-200 dark:border-gray-700">
                        {{ __('forum.clear_search') ?? 'Limpiar búsqueda' }}
                    </a>
                @else
                    <button x-data="{}" x-on:click.prevent="$dispatch('open-modal', 'create-thread-modal')"
                        class="inline-flex items-center gap-2 bg-gradient-to-r from-violet-600 to-violet-600 hover:from-violet-500 hover:to-violet-500 text-white font-bold py-2.5 px-6 rounded-xl transition-all shadow-lg shadow-violet-500/25">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('forum.create_first_thread') ?? 'Crear el primer hilo' }}
                    </button>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 gap-4">
                @foreach ($threads as $thread)
                    <div
                        class="bg-white dark:bg-gray-900 border {{ $thread->is_pinned ? 'border-violet-300 dark:border-violet-800/50 shadow-md shadow-violet-500/5' : 'border-gray-200 dark:border-gray-800 hover:border-violet-200 dark:hover:border-violet-900/50 shadow-sm' }} rounded-2xl p-5 transition-all w-full flex flex-col md:flex-row gap-5 md:items-center group">

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2.5 mb-2">
                                @if ($thread->is_pinned)
                                    <span
                                        class="inline-flex items-center justify-center p-1 bg-violet-100 dark:bg-violet-900/50 text-violet-600 dark:text-violet-400 rounded-full"
                                        title="{{ __('forum.pinned') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                            viewBox="0 0 24 24" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                @endif
                                @if ($thread->is_locked)
                                    <span
                                        class="inline-flex items-center justify-center p-1 bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 rounded-full"
                                        title="{{ __('forum.locked') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                            fill="currentColor">
                                            <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                @endif

                                @if(isset($recentThreadIds) && in_array($thread->id, $recentThreadIds))
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 rounded-xl text-[9px] font-black uppercase tracking-wider shrink-0 animate-pulse border border-amber-500/20"
                                        title="{{ __('forum.recent_activity') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('forum.recent_badge') }}
                                    </span>
                                @endif

                                <a href="{{ route('teams.forum.show', [$team, $thread]) }}"
                                    class="text-base font-bold text-gray-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-colors truncate">
                                    {{ $thread->title }}
                                </a>

                                @if ($thread->task)
                                    <a href="{{ route('teams.activities.show', [$team, $thread->task]) }}"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 dark:bg-gray-800 hover:bg-violet-100 dark:hover:bg-violet-900/30 text-[10px] font-bold text-gray-600 dark:text-gray-300 hover:text-violet-700 dark:hover:text-violet-400 rounded-md transition-colors border border-gray-200 dark:border-gray-700 hover:border-violet-200 dark:hover:border-violet-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        {{ __('forum.task') }}: {{ Str::limit($thread->task->title, 20) }}
                                    </a>
                                @endif
                            </div>

                            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1.5 font-medium">
                                    <img src="{{ $thread->user->profile_photo_url }}"
                                        alt="{{ $thread->user->name }}"
                                        class="w-5 h-5 rounded-full object-cover shadow-sm border border-white dark:border-gray-800 shrink-0">
                                    {{ $thread->user->name }}
                                </span>

                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $thread->created_at->format('d M y, H:i') }}
                                </span>

                                <span class="flex items-center gap-1.5" title="Vistas">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    {{ number_format($thread->views) }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between md:justify-end gap-6 border-t md:border-t-0 md:border-l border-gray-100 dark:border-gray-800 pt-4 md:pt-0 md:pl-6">
                            <div class="text-center">
                                <p
                                    class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-0.5">
                                    {{ __('forum.replies') ?? 'Respuestas' }}</p>
                                <p class="text-xl font-black text-gray-700 dark:text-gray-300 heading">
                                    {{ $thread->messages_count - 1 }}</p>
                            </div>

                            @if ($thread->messages->isNotEmpty())
                                <div class="text-right hidden sm:block">
                                    <p
                                        class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1">
                                        {{ __('forum.latest') ?? 'Último' }}</p>
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $thread->messages->first()->user->profile_photo_url }}"
                                            alt="{{ $thread->messages->first()->user->name }}"
                                            class="w-5 h-5 rounded-full object-cover shadow-sm border border-white dark:border-gray-800 shrink-0">
                                        <div>
                                            <p
                                                class="text-xs font-semibold text-gray-700 dark:text-gray-300 truncate w-24">
                                                {{ $thread->messages->first()->user->name }}</p>
                                            <p class="text-[9px] text-gray-400">
                                                {{ $thread->messages->first()->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <a href="{{ route('teams.forum.show', [$team, $thread]) }}"
                                class="p-2.5 bg-gray-50 hover:bg-violet-50 dark:bg-gray-800 dark:hover:bg-violet-900/20 text-gray-500 hover:text-violet-600 dark:text-gray-400 dark:hover:text-violet-400 rounded-xl transition-colors shrink-0 group-hover:bg-violet-100 group-hover:text-violet-600 dark:group-hover:bg-violet-900/40 border border-transparent group-hover:border-violet-200 dark:group-hover:border-violet-800/50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $threads->links() }}
            </div>
        @endif
    </div>

    <!-- Modal to create thread -->

    <x-modal name="create-thread-modal" focusable>
        <form method="post" action="{{ route('teams.forum.store', $team) }}"
              x-data="forumTaskSearch({{ json_encode([
                  'initialTaskId' => old('activity_id', ''),
                  'initialTaskQuery' => old('activity_query', optional(\App\Models\Activity::find(old('activity_id')))->title ?? ''),
                  'searchUrl' => route('teams.activities.search', $team),
              ]) }})"
              @drive-file-selected.window="addFile($event.detail)"
              class="p-6 dark:bg-gray-900" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="drive_attachments" :value="JSON.stringify(driveFiles)">

            <div class="flex items-center gap-3 mb-6">
                <div class="p-2.5 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white heading">
                    {{ __('forum.new_thread') ?? 'Nuevo hilo de discusión' }}
                </h2>
            </div>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 mb-6">
                {{ __('forum.new_thread_desc') ?? 'Inicia una nueva conversación con tu equipo.' }}
            </p>

            <div class="space-y-4">
                <div>
                    <x-input-label for="title" :value="__('forum.title_label') ?? 'Título'" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                        placeholder="Ej: Dudas sobre la arquitectura del nuevo módulo" required autofocus />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                </div>

                <div>
                    <x-input-label for="task_query" :value="__('forum.related_task') ?? 'Tarea relacionada (Opcional)'" />
                    <div class="relative z-[999999] overflow-visible">
                        <input id="task_query" name="activity_query" type="text" autocomplete="off"
                            x-model="taskQuery"
                            @input="selectedTaskId = ''; fetchTasks()"
                            @keydown.arrow-down.prevent="if(taskResults.length) taskHighlight = (taskHighlight + 1) % taskResults.length"
                            @keydown.arrow-up.prevent="if(taskResults.length) taskHighlight = (taskHighlight > 0 ? taskHighlight - 1 : taskResults.length - 1)"
                            @keydown.enter.prevent="if(taskResults.length && taskHighlight >= 0) selectTask(taskResults[taskHighlight])"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-violet-500 dark:focus:border-violet-600 focus:ring-violet-500 dark:focus:ring-violet-600 rounded-md shadow-sm sm:text-sm"
                            placeholder="{{ __('forum.search_task_placeholder') ?? 'Busca una tarea para relacionarla... (mínimo 2 caracteres)' }}"
                        />

                        <input type="hidden" name="activity_id" :value="selectedTaskId">

                        <button type="button" x-show="selectedTaskId || taskQuery" @click="clearTaskSelection()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 text-xs font-bold z-10"
                            x-cloak>
                            ✕
                        </button>

                        <div x-show="taskResults.length > 0" x-cloak
                            class="absolute z-[999999] mt-1 w-full overflow-visible rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
                            @mouseleave="taskHighlight = -1">
                            <template x-for="(task, index) in taskResults" :key="task.id">
                                <button type="button"
                                    @click="selectTask(task)"
                                    @mouseover="taskHighlight = index"
                                    class="w-full text-left px-4 py-3 text-sm transition-colors"
                                    :class="taskHighlight === index ? 'bg-violet-50 text-violet-700 dark:bg-violet-900/70 dark:text-violet-200' : 'text-gray-700 dark:text-gray-300'">
                                    <span x-text="task.text"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('activity_id')" />
                    <p class="text-[9px] text-gray-500 ml-1 italic">
                        {{ __('forum.related_task_help') ?? 'Busca la tarea por título y selecciona solo la tarea correspondiente. No se cargan todas las tareas del equipo de una vez.' }}
                    </p>
                </div>

                <div>
                    <x-input-label for="content" :value="__('forum.initial_message') ?? 'Mensaje inicial'" />
                    <x-markdown-editor
                        name="content"
                        id="content"
                        rows="12"
                        placeholder="Escribe aquí el contexto de la conversación... (Soporta Markdown)"
                        required
                        :upload-url="route('teams.forum.upload_image', $team)"
                        :mentions-url="route('teams.mentions', $team)"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('content')" />
                </div>

                <!-- File Attachments -->
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between px-1">
                        <x-input-label value="{{ __('Adjuntar archivos') }}" />

                        @php
                            $isTeamLinked = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
                        @endphp

                        @if($isTeamLinked)
                            <button type="button" @click="$dispatch('open-drive-picker', { mode: 'collect' })"
                                class="text-[10px] font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                <svg class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" viewBox="0 0 24 24"></svg>
                                {{ __('Google Drive') }}
                            </button>
                        @endif
                    </div>

                    <!-- Drive Files List -->
                    <template x-if="driveFiles.length > 0">
                        <div class="grid grid-cols-1 gap-2 mb-2">
                            <template x-for="file in driveFiles" :key="file.id">
                                <div class="flex items-center justify-between p-2 rounded-xl bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100/50 dark:border-blue-900/30">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <svg class="w-4 h-4 text-blue-500 shrink-0" viewBox="0 0 48 48">
                                            <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                            <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                            <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                        </svg>
                                        <span class="text-[11px] font-bold text-blue-800 dark:text-blue-300 truncate" x-text="file.name"></span>
                                    </div>
                                    <button type="button" @click="removeFile(file.id)" class="text-blue-400 hover:text-red-500 transition-colors p-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>

                    <input type="file" name="attachments[]" multiple
                        class="block w-full text-xs text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-xl file:border-0
                        file:text-[10px] file:font-black file:uppercase file:tracking-widest
                        file:bg-violet-50 file:text-violet-700
                        hover:file:bg-violet-100
                        dark:file:bg-violet-900/30 dark:file:text-violet-400
                        bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 rounded-2xl cursor-pointer">
                    <p class="text-[9px] text-gray-500 ml-1 italic">{{ __('Puedes seleccionar varios archivos locales o vincularlos desde Google Drive.') }}</p>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('forum.cancel') ?? 'Cancelar' }}
                </x-secondary-button>

                <x-primary-button>
                    {{ __('forum.create') ?? 'Crear hilo' }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    @push('modals')
        <x-google-drive-picker :team="$team" />
    @endpush

    @push('scripts')
        <script>
            // Auto-open modal if validation fails
            @if ($errors->any())
                window.addEventListener('load', () => {
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'create-thread-modal' }));
                });
            @endif

            window.confirmCleanup = function() {
                Swal.fire({
                    title: '{{ __('forum.orphaned_maintenance') }}',
                    text: '{{ __('forum.cleanup_confirm') }}',
                    icon: 'question',
                    width: '32rem',
                    showCancelButton: true,
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: '{{ __('forum.cleanup_stale') }}',
                    cancelButtonText: '{{ __('forum.cancel') }}',
                    customClass: {
                        popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white p-4',
                        title: 'text-violet-600 dark:text-violet-400 font-black uppercase tracking-tight pt-4 text-lg',
                        htmlContainer: 'text-[13px] font-medium text-slate-500 dark:text-slate-400 px-6 pb-6 leading-relaxed',
                        confirmButton: 'rounded-2xl px-6 py-2.5 shadow-lg shadow-violet-500/20 uppercase tracking-widest font-black text-[9px] mx-1',
                        cancelButton: 'rounded-2xl px-6 py-2.5 uppercase tracking-widest font-black text-[9px] mx-1',
                        icon: 'scale-75 mb-0 mt-4 border-violet-200 text-violet-400'
                    },
                    buttonsStyling: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('cleanup-form').submit();
                    }
                });
            }

            // Image Paste Handler for Textareas
            document.addEventListener('paste', function(e) {
                if (e.target.tagName.toLowerCase() === 'textarea') {
                    let items = (e.clipboardData || e.originalEvent.clipboardData).items;
                    let blob = null;
                    for (let i = 0; i < items.length; i++) {
                        if (items[i].type.indexOf("image") === 0) {
                            blob = items[i].getAsFile();
                            break;
                        }
                    }

                    if (blob !== null) {
                        e.preventDefault();
                        let textarea = e.target;

                        let cursorStart = textarea.selectionStart;
                        let cursorEnd = textarea.selectionEnd;
                        let textBefore = textarea.value.substring(0, cursorStart);
                        let textAfter = textarea.value.substring(cursorEnd, textarea.value.length);
                        let placeholder = `![Subiendo imagen...]()`;

                        textarea.value = textBefore + placeholder + textAfter;

                        let formData = new FormData();
                        formData.append('image', blob);
                        let tokenStr = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        if (tokenStr) formData.append('_token', tokenStr);
                        else {
                            const tokenEl = document.querySelector('input[name="_token"]');
                            if (tokenEl) formData.append('_token', tokenEl.value);
                        }

                        fetch(`{{ route('teams.forum.upload_image', $team) }}`, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.url) {
                                let markdownImg = `![Imagen adjunta](${data.url})`;
                                textarea.value = textarea.value.replace(placeholder, markdownImg);
                            }
                        })
                        .catch(error => {
                            console.error('Error uploading image', error);
                            textarea.value = textarea.value.replace(placeholder, '');
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un error al subir la imagen.', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                            } else {
                                alert('Hubo un error al subir la imagen.');
                            }
                        });
                    }
                }
            });

            document.addEventListener('DOMContentLoaded', () => {
                const dock = document.getElementById('forum-action-dock');
                if (!dock) return;
                let visible = false;
                let isDragging = false;
                let startX, startY;
                let hasDragged = false;

                function updateScrollDock(scrollY) {
                    const shouldShow = scrollY > 300;
                    if (shouldShow === visible) return;
                    visible = shouldShow;
                    if (visible) {
                        dock.style.opacity = '1';
                        if (!hasDragged) {
                            dock.style.transform = 'translateX(-50%) translateY(0)';
                        }
                        dock.style.pointerEvents = 'auto';
                    } else {
                        dock.style.opacity = '0';
                        if (!hasDragged) {
                            dock.style.transform = 'translateX(-50%) translateY(1rem)';
                        }
                        dock.style.pointerEvents = 'none';
                    }
                }

                const checkScroll = (e) => {
                    const target = e.target === document ? document.documentElement : e.target;
                    const scrollY = target.scrollTop || 0;
                    const finalScroll = scrollY || window.scrollY || 0;
                    updateScrollDock(finalScroll);
                };

                window.addEventListener('scroll', checkScroll, { passive: true, capture: true });

                // Chequeo inicial
                const initialScroll = window.scrollY || document.documentElement.scrollTop || 0;
                updateScrollDock(initialScroll);

                // --- SISTEMA DE ARRASTRE DRAGGABLE PREMIUM (Mouse y Touch) ---
                const startDrag = (e) => {
                    // Evitar arrastrar si clicamos en botones, inputs o enlaces interactivos
                    if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input')) {
                        return;
                    }

                    isDragging = true;
                    dock.style.transition = 'none'; // Desactivar transiciones durante el arrastre

                    // Obtener la posición inicial del toque/clic
                    const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                    const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

                    const rect = dock.getBoundingClientRect();

                    // Al iniciar, fijamos el left y top reales para evitar que salte por el transform del CSS
                    if (!hasDragged) {
                        dock.style.bottom = 'auto';
                        dock.style.transform = 'none';
                        dock.style.left = rect.left + 'px';
                        dock.style.top = rect.top + 'px';
                        hasDragged = true;
                    }

                    startX = clientX - rect.left;
                    startY = clientY - rect.top;

                    document.addEventListener('mousemove', drag);
                    document.addEventListener('mouseup', stopDrag);
                    document.addEventListener('touchmove', drag, { passive: false });
                    document.addEventListener('touchend', stopDrag);
                };

                const drag = (e) => {
                    if (!isDragging) return;
                    if (e.cancelable) e.preventDefault();

                    const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                    const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

                    let newLeft = clientX - startX;
                    let newTop = clientY - startY;

                    // Límites de la ventana para que no se salga de la pantalla
                    const rect = dock.getBoundingClientRect();
                    const maxLeft = window.innerWidth - rect.width;
                    const maxTop = window.innerHeight - rect.height;

                    newLeft = Math.max(0, Math.min(newLeft, maxLeft));
                    newTop = Math.max(0, Math.min(newTop, maxTop));

                    dock.style.left = newLeft + 'px';
                    dock.style.top = newTop + 'px';
                };

                const stopDrag = () => {
                    isDragging = false;
                    dock.style.transition = 'all 0.35s cubic-bezier(0.4, 0, 0.2, 1)'; // Restaurar transiciones
                    document.removeEventListener('mousemove', drag);
                    document.removeEventListener('mouseup', stopDrag);
                    document.removeEventListener('touchmove', drag);
                    document.removeEventListener('touchend', stopDrag);
                };

                dock.addEventListener('mousedown', startDrag);
                dock.addEventListener('touchstart', startDrag, { passive: true });
                dock.style.cursor = 'grab';

                dock.addEventListener('mouseenter', () => { if (!isDragging) dock.style.cursor = 'grab'; });
                dock.addEventListener('mousedown', () => { dock.style.cursor = 'grabbing'; });
                dock.addEventListener('mouseup', () => { dock.style.cursor = 'grab'; });
            });
        </script>
        <script>
            window.forumTaskSearch = function(config) {
                return {
                    driveFiles: [],
                    initialised: false,
                    taskQuery: config.initialTaskQuery || '',
                    selectedTaskId: config.initialTaskId || '',
                    taskResults: [],
                    taskHighlight: -1,
                    searchUrl: config.searchUrl || '/teams/' + (config.teamId || '0') + '/tasks/search',

                    addFile(file) { this.driveFiles.push(file); },
                    clearTaskSelection() { this.selectedTaskId = ''; this.taskQuery = ''; this.taskResults = []; },
                    selectTask(t) { this.selectedTaskId = t.id; this.taskQuery = t.text || t.title || ''; this.taskResults = []; this.taskHighlight = -1; },
                    fetchTasks() {
                        if (!this.taskQuery || this.taskQuery.length < 2) { this.taskResults = []; return; }
                        clearTimeout(this._fetchTimer);
                        this._fetchTimer = setTimeout(async () => {
                            try {
                                const url = new URL(this.searchUrl, window.location.origin);
                                url.searchParams.set('query', this.taskQuery);
                                const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                                if (!res.ok) { this.taskResults = []; return; }
                                const data = await res.json();
                                this.taskResults = Array.isArray(data) ? data : [];
                                this.taskHighlight = -1;
                            } catch (e) { console.error('fetchTasks error', e); this.taskResults = []; }
                        }, 300);
                    },
                    highlightNext() { if (!this.taskResults.length) return; this.taskHighlight = (this.taskHighlight + 1) % this.taskResults.length; },
                    highlightPrev() { if (!this.taskResults.length) return; this.taskHighlight = (this.taskHighlight > 0) ? this.taskHighlight - 1 : this.taskResults.length - 1; }
                };
            };
        </script>

        <!-- Floating Contextual Action Dock -->
        <div id="forum-action-dock"
             style="
                position: fixed;
                bottom: 2rem;
                left: 50%;
                transform: translateX(-50%) translateY(1rem);
                z-index: 99999;
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 0.6rem 1rem;
                background: rgba(255,255,255,0.92);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(229, 231, 235, 0.8);
                border-radius: 1.5rem;
                box-shadow: 0 15px 40px -10px rgba(0,0,0,0.12), 0 0 1px 0 rgba(0,0,0,0.1);
                opacity: 0;
                pointer-events: none;
                transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
             "
             class="dark:[background:rgba(17,24,39,0.92)] dark:[border-color:rgba(55,65,81,0.8)]">

             <!-- Drag Handle -->
             <div class="cursor-grab text-gray-300 hover:text-gray-500 dark:text-gray-700 dark:hover:text-gray-500 transition-colors px-1 select-none flex items-center justify-center" title="Arrastrar">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                     <circle cx="9" cy="5" r="1.5" />
                     <circle cx="15" cy="5" r="1.5" />
                     <circle cx="9" cy="12" r="1.5" />
                     <circle cx="15" cy="12" r="1.5" />
                     <circle cx="9" cy="19" r="1.5" />
                     <circle cx="15" cy="19" r="1.5" />
                 </svg>
             </div>

             <!-- Back to team button -->
             <a href="{{ route('teams.show', $team) }}"
                class="flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-violet-600 dark:text-gray-400 dark:hover:text-violet-400 transition-colors py-1.5 px-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                 </svg>
                 <span>Equipo</span>
             </a>

             <!-- Divider -->
             <div class="h-5 w-px bg-gray-200 dark:bg-gray-700"></div>

             <!-- Forum Title Context -->
             <span class="text-xs font-bold text-gray-600 dark:text-gray-300 tracking-tight py-0.5">
                 {{ __('forum.title') ?? 'Foro de Equipo' }}
             </span>

             <!-- Divider -->
             <div class="h-5 w-px bg-gray-200 dark:bg-gray-700"></div>

             <!-- Floating Scroll Button -->
             <button onclick="(function() {
                         window.scrollTo({ top: 0, behavior: 'smooth' });
                         document.documentElement.scrollTo({ top: 0, behavior: 'smooth' });
                         document.body.scrollTo({ top: 0, behavior: 'smooth' });
                         document.querySelectorAll('.overflow-y-auto, [style*=\'overflow-y: auto\'], [style*=\'' +
                         'overflow-y: scroll\']').forEach(el => {
                             el.scrollTo({ top: 0, behavior: 'smooth' });
                         });
                     })()"
                     class="flex items-center gap-1.5 text-xs font-bold text-white bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 py-1.5 px-3.5 rounded-xl shadow-sm hover:shadow hover:scale-105 active:scale-95 transition-all duration-300 shrink-0">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                 </svg>
                 <span>Inicio</span>
             </button>
        </div>
@endpush
</x-app-layout>
