<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center justify-between min-w-0 flex-1 gap-4 select-none">
                <h1 class="text-2xl font-black text-gray-900 dark:text-white heading truncate flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                    </svg>
                    {{ __('forum.title') ?? 'Anuncios' }}
                </h1>

                <button type="button" x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'create-thread-modal')"
                    class="flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-4 py-2.5 rounded-xl transition-all font-bold shadow-lg shadow-violet-500/20 active:scale-95 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('forum.new_thread') ?? 'Nuevo Hilo' }}</span>
                </button>
            </div>

            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    <div class="space-y-6">
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
                    {{ __('forum.empty_title') ?? 'No hay hilos de discusión todavía' }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    {{ __('forum.empty_desc') ?? 'Abre un nuevo hilo para compartir ideas, resolver dudas o documentar decisiones de equipo.' }}
                </p>

                <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-thread-modal')"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold py-2.5 px-6 rounded-xl transition-all shadow-lg shadow-violet-500/25">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('forum.create_first_thread') ?? 'Crear el primer hilo' }}
                </button>
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
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transform -rotate-45"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M5 21l-3-3 8-8V3a1 1 0 011-1h6a1 1 0 011 1v7l8 8-3 3-8-8H5z" />
                                        </svg>
                                    </span>
                                @endif
                                @if ($thread->is_locked)
                                    <span
                                        class="inline-flex items-center justify-center p-1 bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 rounded-full"
                                        title="{{ __('forum.locked') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                @endif

                                <a href="{{ route('teams.forum.show', [$team, $thread]) }}"
                                    class="text-base font-bold text-gray-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-colors truncate">
                                    {{ $thread->title }}
                                </a>

                                @if ($thread->task)
                                    <a href="{{ route('teams.tasks.show', [$team, $thread->task]) }}"
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
                                    <div
                                        class="w-5 h-5 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-[8px] font-bold text-violet-600 dark:text-violet-400">
                                        {{ strtoupper(substr($thread->user->name, 0, 2)) }}
                                    </div>
                                    {{ $thread->user->name }}
                                </span>

                                <span class="flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $thread->created_at->format('d M y, H:i') }}
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
                                        <div
                                            class="w-5 h-5 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-[8px] font-bold text-indigo-600 dark:text-indigo-400 shrink-0">
                                            {{ strtoupper(substr($thread->messages->first()->user->name, 0, 2)) }}
                                        </div>
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
        <form method="post" action="{{ route('teams.forum.store', $team) }}" class="p-6 dark:bg-gray-900">
            @csrf

            <div class="flex items-center gap-3 mb-6">
                <div class="p-2.5 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white heading">
                    {{ __('forum.new_thread') ?? 'Nuevo Hilo de Discusión' }}
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
                    <x-input-label for="task_id" :value="__('forum.related_task') ?? 'Tarea relacionada (Opcional)'" />
                    <select id="task_id" name="task_id"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-violet-500 dark:focus:border-violet-600 focus:ring-violet-500 dark:focus:ring-violet-600 rounded-md shadow-sm sm:text-sm">
                        <option value="">{{ __('forum.none') ?? '-- Ninguna --' }}</option>
                        @foreach (\App\Models\Task::where('team_id', $team->id)->whereDoesntHave('forumThread')->orderBy('title')->get() as $t)
                            <option value="{{ $t->id }}">[{{ __('tasks.statuses.' . $t->status) }}]
                                {{ $t->title }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('task_id')" />
                </div>

                <div>
                    <x-input-label for="content" :value="__('forum.initial_message') ?? 'Mensaje inicial'" />
                    <textarea id="content" name="content" rows="4"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-violet-500 dark:focus:border-violet-600 focus:ring-violet-500 dark:focus:ring-violet-600 rounded-md shadow-sm sm:text-sm"
                        placeholder="Escribe aquí el contexto de la conversación..." required></textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('content')" />
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('forum.cancel') ?? 'Cancelar' }}
                </x-secondary-button>

                <x-primary-button>
                    {{ __('forum.create') ?? 'Crear Hilo' }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
