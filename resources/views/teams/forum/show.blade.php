<x-app-layout>
    @push('styles')
        <style>
            /* Markdown Content Styling */
            .markdown-content ul { list-style-type: disc !important; padding-left: 1.5rem; margin-bottom: 1rem; }
            .markdown-content ol { list-style-type: decimal !important; padding-left: 1.5rem; margin-bottom: 1rem; }
            .markdown-content h1 { font-size: 1.5rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 1rem; }
            .markdown-content h2 { font-size: 1.25rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: 0.75rem; }
            .markdown-content code { background: #f3f4f6; padding: 0.2rem 0.4rem; border-radius: 0.25rem; font-size: 0.875em; }
            .dark .markdown-content code { background: #374151; }
            .markdown-content pre { background: #1f2937; color: #f9fafb; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; overflow-x: auto; }
            .markdown-content blockquote { border-left: 4px solid #8b5cf6; padding-left: 1rem; font-style: italic; color: #6b7280; margin-bottom: 1rem; }
            .dark .markdown-content blockquote { color: #9ca3af; }
            .markdown-content a { color: #8b5cf6; text-decoration: underline; }
            .markdown-content img { max-width: 100%; height: auto; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin: 1rem 0; border: 1px solid #e5e7eb; }
            .dark .markdown-content img { border-color: #374151; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5); }
        </style>
    @endpush
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.forum.index', $team) }}"
                    class="p-2 bg-white dark:bg-gray-800 text-gray-500 hover:text-violet-600 dark:text-gray-400 dark:hover:text-violet-400 rounded-xl shadow-sm transition-colors border border-gray-200 dark:border-gray-700 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-3">
                        @if ($thread->is_pinned)
                            <span class="text-violet-500 shrink-0"><svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                                </svg></span>
                        @endif
                        @if (auth()->id() === $thread->user_id || auth()->user()->getRole($team) === 'coordinator')
                            <h2 id="thread-title-display" onclick="enableTitleEdit(event)"
                                class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate cursor-text hover:bg-gray-100 dark:hover:bg-gray-800 px-2 py-0.5 rounded transition-colors -ml-2 border border-transparent hover:border-gray-200 dark:hover:border-gray-700"
                                title="Haz clic para editar el título">
                                {{ $thread->title }}
                            </h2>
                            <form id="thread-title-form" class="hidden w-full max-w-2xl" onsubmit="event.preventDefault(); saveTitle(); return false;">
                                <input type="text" id="thread-title-input" value="{{ $thread->title }}"
                                    class="font-bold text-xl text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-900 border-2 border-violet-500 rounded px-2 py-0.5 w-full focus:ring-0 focus:outline-none -ml-2"
                                    onblur="saveTitle()">
                            </form>
                        @else
                            <h2 class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate">
                                {{ $thread->title }}
                            </h2>
                        @endif
                    </div>
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
                    <div class="items-center gap-2 shrink-0 hidden md:inline-flex">
                        <form action="{{ route('teams.forum.update', [$team, $thread]) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_locked" value="{{ $thread->is_locked ? 0 : 1 }}">
                            <button type="submit"
                                class="p-2 {{ $thread->is_locked ? 'bg-amber-100 text-amber-600' : 'bg-white text-gray-500 hover:text-amber-600 hover:bg-amber-50 border-gray-200' }} border dark:bg-gray-800 dark:border-gray-700 rounded-xl transition-colors shadow-sm"
                                title="{{ $thread->is_locked ? 'Desbloquear hilo' : 'Bloquear hilo' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    @if ($thread->is_locked)
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" fill="{{ $thread->is_pinned ? 'currentColor' : 'none' }}" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
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
                    <div class="flex flex-col {{ $isCurrentUser ? 'items-end' : 'items-start' }} w-full max-w-[85%]">
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

                        <div class="relative group w-full">
                            <!-- Actions Bar -->
                            <div id="actions-{{ $message->id }}" 
                                class="absolute -top-3 {{ $isCurrentUser ? 'right-0' : 'left-0' }} z-10 flex items-center gap-1 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                @if (!$thread->is_locked)
                                    <!-- Reply -->
                                    <button type="button"
                                        onclick="quoteMessage(`{{ addslashes($message->user->name) }}`, `{{ addslashes($message->content) }}`)"
                                        class="p-1.5 text-gray-400 hover:text-violet-500 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-colors"
                                        title="Responder citando">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l5 5m-5-5l5-5" />
                                        </svg>
                                    </button>

                                    <!-- Edit -->
                                    @if ($isCurrentUser)
                                        <button type="button"
                                            onclick="editMessage({{ $message->id }}, `{{ addslashes($message->content) }}`)"
                                            class="p-1.5 text-gray-400 hover:text-blue-500 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-colors"
                                            title="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                    @endif

                                    <!-- Delete -->
                                    @if ($isCurrentUser || auth()->user()->getRole($team) === 'coordinator')
                                        <form action="{{ route('teams.forum.messages.destroy', [$team, $message]) }}"
                                            method="POST" onsubmit="return confirm('{{ $isFirst ? 'Este es el primer post. Borrarlo eliminará todo el hilo. ¿Estás seguro?' : '¿Eliminar este mensaje?' }}');"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-1.5 text-gray-400 hover:text-red-500 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-colors"
                                                title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>

                            <!-- View Mode -->
                            <div id="message-view-{{ $message->id }}"
                                class="p-4 rounded-2xl shadow-sm border {{ $isCurrentUser ? 'bg-indigo-50 border-indigo-100 dark:bg-indigo-900/10 dark:border-indigo-800/50 rounded-tr-none text-indigo-900 dark:text-indigo-100' : 'bg-white border-gray-200 dark:bg-gray-900 dark:border-gray-800 rounded-tl-none text-gray-800 dark:text-gray-200' }}">
                                <div class="text-sm markdown-content leading-relaxed">
                                    {!! Str::markdown($message->content) !!}
                                </div>
                            </div>

                            <!-- Edit Mode (Hidden) -->
                            @if ($isCurrentUser)
                                <div id="message-edit-{{ $message->id }}" class="hidden w-full pt-2">
                                    <form action="{{ route('teams.forum.messages.update', [$team, $message]) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <textarea id="edit-content-{{ $message->id }}" name="content" style="min-height: 400px !important;"
                                            class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-violet-500 dark:border-violet-600 rounded-2xl focus:ring-0 text-sm p-4 dark:text-gray-200 transition-colors shadow-inner"
                                            rows="12">{{ $message->content }}</textarea>
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" onclick="cancelEdit({{ $message->id }})" 
                                                class="px-3 py-1.5 text-xs font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                                Cancelar
                                            </button>
                                            <button type="submit" 
                                                class="px-4 py-1.5 text-xs font-bold bg-violet-600 hover:bg-violet-500 text-white rounded-xl transition-all shadow-lg shadow-violet-600/20 active:scale-95">
                                                Guardar Cambios
                                            </button>
                                        </div>
                                    </form>
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
                                <textarea id="reply-content" name="content" rows="10" style="min-height: 300px !important;"
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

    @push('scripts')
        <script>
            function quoteMessage(name, content) {
                const textarea = document.getElementById('reply-content');
                if (textarea) {
                    const quote = `> **${name}**: ${content}\n\n`;
                    textarea.value = quote + textarea.value;
                    textarea.focus();
                    textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            function editMessage(messageId, content) {
                // Show edit form, hide message and actions
                document.getElementById(`message-view-${messageId}`).classList.add('hidden');
                document.getElementById(`actions-${messageId}`).classList.add('hidden');
                document.getElementById(`message-edit-${messageId}`).classList.remove('hidden');
                document.getElementById(`edit-content-${messageId}`).focus();
            }

            function cancelEdit(messageId) {
                document.getElementById(`message-view-${messageId}`).classList.remove('hidden');
                document.getElementById(`actions-${messageId}`).classList.remove('hidden');
                document.getElementById(`message-edit-${messageId}`).classList.add('hidden');
            }

            function enableTitleEdit(e) {
                if (e) e.stopPropagation();
                const display = document.getElementById('thread-title-display');
                if (!display) return;
                display.classList.add('hidden');
                document.getElementById('thread-title-form').classList.remove('hidden');
                const input = document.getElementById('thread-title-input');
                setTimeout(() => {
                    input.focus();
                    input.setSelectionRange(input.value.length, input.value.length);
                }, 50);
            }

            function saveTitle() {
                const form = document.getElementById('thread-title-form');
                if (!form || form.classList.contains('hidden')) return;
                
                const input = document.getElementById('thread-title-input');
                const display = document.getElementById('thread-title-display');
                const newTitle = input.value.trim();
                const oldTitle = display.innerText.trim();
                
                if (!newTitle || newTitle === oldTitle) {
                    input.value = oldTitle;
                    form.classList.add('hidden');
                    display.classList.remove('hidden');
                    return;
                }

                form.classList.add('hidden');
                display.classList.remove('hidden');
                display.innerText = newTitle;
                display.classList.add('opacity-50');

                fetch(`{{ route('teams.forum.update', [$team, $thread]) }}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ title: newTitle })
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Error en la petición');
                    return data;
                })
                .then(data => {
                    display.classList.remove('opacity-50');
                    if (data && data.title) {
                        display.innerText = data.title;
                        input.value = data.title;
                    }
                })
                .catch(err => {
                    console.error('Error saving title', err);
                    display.classList.remove('opacity-50');
                    display.innerText = oldTitle;
                    input.value = oldTitle;
                    alert('Error al guardar el título: ' + err.message);
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
                            // Fallback to searching input text
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
                            alert('Hubo un error al subir la imagen.');
                        });
                    }
                }
            });
        </script>
    @endpush
</x-app-layout>
