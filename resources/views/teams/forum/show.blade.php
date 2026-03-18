<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 overflow-hidden">
            <div class="flex items-center gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.forum.index', $team) }}"
                    class="p-2 bg-white dark:bg-gray-800 text-gray-500 hover:text-violet-600 dark:text-gray-400 dark:hover:text-violet-400 rounded-xl shadow-sm transition-colors border border-gray-200 dark:border-gray-700 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <div class="min-w-0 flex-1">
                    <h2
                        class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-3 truncate">
                        @if ($thread->is_pinned)
                            <span class="text-violet-500"><svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 transform -rotate-45" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M5 21l-3-3 8-8V3a1 1 0 011-1h6a1 1 0 011 1v7l8 8-3 3-8-8H5z" />
                                </svg></span>
                        @endif
                        {{ $thread->title }}
                    </h2>
                    <div class="text-xs text-gray-500 flex items-center gap-3 mt-1.5 font-medium">
                        <span class="flex items-center gap-1.5 shrink-0">
                            <div
                                class="w-4 h-4 rounded-full bg-violet-100 text-[8px] font-bold text-violet-600 flex items-center justify-center">
                                {{ strtoupper(substr($thread->user->name, 0, 2)) }}</div> Creado por
                            {{ $thread->user->name }}
                        </span>
                        <span class="shrink-0">•</span>
                        <span class="shrink-0">{{ $thread->created_at->format('d M y, H:i') }}</span>
                        @if ($thread->task)
                            <span class="shrink-0">•</span>
                            <a href="{{ route('teams.tasks.show', [$team, $thread->task]) }}"
                                class="text-violet-600 dark:text-violet-400 hover:underline truncate">Referencia a
                                tarea:
                                {{ Str::limit($thread->task->title, 30) }}</a>
                        @endif
                    </div>
                </div>

                @if (auth()->id() === $thread->user_id || auth()->user()->getRole($team) === 'coordinator')
                    <div class="flex items-center gap-2 shrink-0 hidden md:flex">
                        <form action="{{ route('teams.forum.update', [$team, $thread]) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_locked" value="{{ $thread->is_locked ? 0 : 1 }}">
                            <button type="submit"
                                class="p-2 {{ $thread->is_locked ? 'bg-amber-100 text-amber-600' : 'bg-white text-gray-500 hover:text-amber-600 hover:bg-amber-50 border-gray-200' }} border dark:bg-gray-800 dark:border-gray-700 rounded-xl transition-colors shadow-sm"
                                title="{{ $thread->is_locked ? 'Desbloquear hilo' : 'Bloquear hilo' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    @if ($thread->is_locked)
                                        <path fill-rule="evenodd"
                                            d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z"
                                            clip-rule="evenodd" />
                                    @else
                                        <path
                                            d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H5V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z" />
                                    @endif
                                </svg>
                            </button>
                        </form>

                        <form action="{{ route('teams.forum.update', [$team, $thread]) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_pinned" value="{{ $thread->is_pinned ? 0 : 1 }}">
                            <button type="submit"
                                class="p-2 {{ $thread->is_pinned ? 'bg-violet-100 text-violet-600' : 'bg-white text-gray-500 hover:text-violet-600 hover:bg-violet-50 border-gray-200' }} border dark:bg-gray-800 dark:border-gray-700 rounded-xl transition-colors shadow-sm"
                                title="{{ $thread->is_pinned ? 'Desfijar' : 'Fijar' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform -rotate-45"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M5 21l-3-3 8-8V3a1 1 0 011-1h6a1 1 0 011 1v7l8 8-3 3-8-8H5z" />
                                </svg>
                            </button>
                        </form>

                        <form action="{{ route('teams.forum.destroy', [$team, $thread]) }}" method="POST"
                            onsubmit="return confirm('¿Seguro que deseas eliminar todo este hilo? No se puede deshacer.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="p-2 bg-white border border-gray-200 text-gray-500 hover:text-red-600 hover:bg-red-50 hover:border-red-200 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-red-900/30 dark:hover:border-red-800 dark:hover:text-red-400 rounded-xl transition-colors shadow-sm"
                                title="Eliminar hilo">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">

        @if ($thread->is_locked)
            <div
                class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 flex items-center justify-center gap-3">
                <div
                    class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-600 dark:text-amber-500 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-amber-800 dark:text-amber-400">
                        {{ __('forum.thread_locked') ?? 'Hilo bloqueado' }}</h4>
                    <p class="text-xs text-amber-700 dark:text-amber-500/80 mt-0.5">No se pueden añadir más mensajes
                        a esta
                        conversación.</p>
                </div>
            </div>
        @endif

        <div class="space-y-4">
            @foreach ($messages as $index => $message)
                @php
                    $isFirst = $index === 0 && $messages->currentPage() === 1;
                    $isCurrentUser = $message->user_id === auth()->id();
                @endphp

                <div class="flex gap-4 {{ $isCurrentUser ? 'flex-row-reverse' : '' }}">
                    <!-- Avatar -->
                    <div class="flex-shrink-0 mt-1">
                        <div
                            class="w-10 h-10 rounded-full {{ $isCurrentUser ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400' : ($isFirst ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400') }} flex items-center justify-center text-sm font-bold shadow-sm border border-white dark:border-gray-900">
                            {{ strtoupper(substr($message->user->name, 0, 2)) }}
                        </div>
                    </div>

                    <!-- Message Bubble -->
                    <div class="flex flex-col {{ $isCurrentUser ? 'items-end' : 'items-start' }} max-w-[85%]">
                        <div class="flex items-baseline gap-2 mb-1 px-1">
                            <span
                                class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $message->user->name }}</span>
                            @if ($isFirst)
                                <span
                                    class="text-[9px] font-bold uppercase tracking-widest bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 px-1.5 py-0.5 rounded-md">OP</span>
                            @endif
                            <span class="text-[10px] text-gray-400 font-medium"
                                title="{{ $message->created_at }}">{{ $message->created_at->diffForHumans() }}</span>
                            @if ($message->is_edited)
                                <span class="text-[10px] text-gray-400 italic">(editado)</span>
                            @endif
                        </div>

                        <div class="relative group">
                            <div
                                class="p-4 rounded-2xl shadow-sm border {{ $isCurrentUser ? 'bg-indigo-50 border-indigo-100 dark:bg-indigo-900/10 dark:border-indigo-800/50 rounded-tr-none text-indigo-900 dark:text-indigo-100' : 'bg-white border-gray-200 dark:bg-gray-900 dark:border-gray-800 rounded-tl-none text-gray-800 dark:text-gray-200' }}">
                                <div class="text-sm whitespace-pre-wrap leading-relaxed">{{ $message->content }}
                                </div>
                            </div>

                            <!-- Edit/Delete actions (visible on hover) -->
                            @if (!$thread->is_locked && ($isCurrentUser || auth()->user()->getRole($team) === 'coordinator'))
                                <div
                                    class="absolute top-0 {{ $isCurrentUser ? 'left-0 -translate-x-full pr-2' : 'right-0 translate-x-full pl-2' }} opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                                    @if ($isCurrentUser)
                                        <button type="button"
                                            onclick="editMessage({{ $message->id }}, `{{ addslashes($message->content) }}`)"
                                            class="p-1.5 text-gray-400 hover:text-blue-500 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                    @endif
                                    @if (!$isFirst)
                                        <!-- Don't allow deleting the first message easily, user should delete the thread -->
                                        <form action="{{ route('teams.forum.messages.destroy', [$team, $message]) }}"
                                            method="POST" onsubmit="return confirm('¿Eliminar este mensaje?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-1.5 text-gray-400 hover:text-red-500 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mt-8">
                {{ $messages->links() }}
            </div>

            <!-- Reply Box -->
            @if (!$thread->is_locked)
                <div
                    class="mt-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-violet-400 to-indigo-600">
                    </div>
                    <form action="{{ route('teams.forum.messages.store', [$team, $thread]) }}" method="POST">
                        @csrf
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 hidden sm:block">
                                <div
                                    class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400 flex items-center justify-center text-sm font-bold shadow-sm">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                            </div>
                            <div class="flex-1 space-y-3 pl-2">
                                <label for="reply-content" class="sr-only">Escribe tu respuesta</label>
                                <textarea id="reply-content" name="content" rows="4"
                                    class="w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-violet-500 focus:border-violet-500 text-sm p-4 placeholder-gray-400 dark:text-gray-200 transition-colors"
                                    placeholder="Escribe tu respuesta aquí..." required></textarea>

                                <div class="flex justify-end relative">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white font-bold py-2.5 px-6 rounded-xl transition-all shadow-lg shadow-violet-600/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform rotate-90"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        Enviar respuesta
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Edit Message Modal -->
    <x-modal name="edit-message-modal" focusable>
        <form id="edit-message-form" method="post" action="" class="p-6 dark:bg-gray-900">
            @csrf
            @method('PATCH')

            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                Editar Mensaje
            </h2>

            <div class="space-y-4">
                <div>
                    <label for="edit_content" class="sr-only">Mensaje</label>
                    <textarea id="edit_content" name="content" rows="5"
                        class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-violet-500 dark:focus:border-violet-600 focus:ring-violet-500 dark:focus:ring-violet-600 rounded-md shadow-sm sm:text-sm"
                        required></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-primary-button>
                    Guardar Cambios
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <script>
        function editMessage(messageId, content) {
            const form = document.getElementById('edit-message-form');
            form.action = `/teams/{{ $team->id }}/forum/messages/${messageId}`;
            document.getElementById('edit_content').value = content;
            window.dispatchEvent(new CustomEvent('open-modal', {
                detail: 'edit-message-modal'
            }));
        }
    </script>
</x-app-layout>
