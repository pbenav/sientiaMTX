<x-app-layout>
    @push('styles')
    <!-- Global Markdown styles are now handled via x-markdown-styles in app layout -->
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
                    <div class="flex items-center gap-3" x-data="{ editingTitle: false }">
                        @if ($thread->is_pinned)
                            <span class="text-violet-500 shrink-0"><svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                                </svg></span>
                        @endif
                        @if (auth()->id() === $thread->user_id || auth()->user()->getRole($team) === 'coordinator')
                            <h2 x-show="!editingTitle" @click="editingTitle = true; setTimeout(() => $refs.titleInput.focus(), 50)"
                                class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 px-2 py-0.5 rounded transition-colors -ml-2 border border-transparent hover:border-gray-200 dark:hover:border-gray-700"
                                title="Editar título">
                                {{ $thread->title }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                            </h2>
                            <form x-cloak x-show="editingTitle" class="w-full max-w-2xl flex items-center gap-2" method="POST" action="{{ route('teams.forum.update', [$team, $thread]) }}">
                                @csrf
                                @method('PATCH')
                                <input type="text" name="title" x-ref="titleInput" value="{{ $thread->title }}"
                                    class="font-bold text-xl text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-900 border-2 border-violet-500 rounded px-2 py-0.5 w-full focus:ring-0 focus:outline-none -ml-2">
                                <button type="submit" class="px-3 py-1 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-sm font-bold shadow transition-colors">Guardar</button>
                                <button type="button" @click="editingTitle = false" class="px-3 py-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-sm font-medium transition-colors">Cancelar</button>
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
                    $isPaginator = $messages instanceof \Illuminate\Pagination\LengthAwarePaginator;
                    $currentPage = $isPaginator ? $messages->currentPage() : 1;
                    $isFirst = $index === 0 && $currentPage === 1;
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
                        <div class="flex items-center justify-between w-full mb-1 px-1 gap-4">
                            <div class="flex items-baseline gap-2 min-w-0">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate">{{ $message->user->name }}</span>
                                @if ($isFirst)
                                    <span class="text-[9px] font-bold uppercase tracking-widest bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 px-1.5 py-0.5 rounded-md shrink-0">OP</span>
                                @endif
                                @if ($message->is_private)
                                    <span class="text-[9px] font-bold uppercase tracking-widest bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-1.5 py-0.5 rounded-md shrink-0 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="currentColor">
                                            <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('Privado') }}
                                    </span>
                                @endif
                                <span class="text-[10px] text-gray-400 font-medium shrink-0" title="{{ $message->created_at }}">{{ $message->created_at->diffForHumans() }}</span>
                                @if ($message->is_edited)
                                    <span class="text-[10px] text-gray-400 italic shrink-0">(editado)</span>
                                @endif
                            </div>

                            <!-- Actions Bar -->
                            <div id="actions-{{ $message->id }}" class="flex items-center gap-1 shrink-0">
                                @if (!$thread->is_locked)
                                    <!-- Reply -->
                                    <button type="button"
                                        onclick="quoteMessage({{ json_encode($message->user->name) }}, {{ json_encode($message->content) }})"
                                        class="p-1.5 text-gray-400 hover:text-violet-500 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors"
                                        title="Responder citando">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l5 5m-5-5l5-5" />
                                        </svg>
                                    </button>
 
                                    <!-- Edit -->
                                    @if ($isCurrentUser)
                                        <button type="button"
                                            onclick="editMessage({{ $message->id }}, {{ json_encode($message->content) }})"
                                            class="p-1.5 text-gray-400 hover:text-blue-500 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors"
                                            title="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                    @endif
 
                                    <!-- Ask AI -->
                                    <button type="button"
                                        @click="$dispatch('ai:set-context', { 
                                            messageId: {{ $message->id }}, 
                                            userName: {{ json_encode($message->user->name) }},
                                            teamId: {{ $team->id }},
                                            threadId: {{ $thread->id }}
                                        })"
                                        class="p-1.5 text-gray-400 hover:text-indigo-500 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors"
                                        title="Preguntar a Ax.ia sobre esto">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </button>

                                    <!-- Delete -->
                                    @if ($isCurrentUser || auth()->user()->getRole($team) === 'coordinator')
                                        <form action="{{ route('teams.forum.messages.destroy', [$team, $message]) }}"
                                            method="POST" onsubmit="return confirm('{{ $isFirst ? 'Este es el primer post. Borrarlo eliminará todo el hilo. ¿Estás seguro?' : '¿Eliminar este mensaje?' }}');"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-1.5 text-gray-400 hover:text-red-500 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors"
                                                title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <div class="relative group w-full">
                            <!-- View Mode -->
                            <div id="message-view-{{ $message->id }}"
                                class="p-4 rounded-2xl shadow-sm border {{ $isCurrentUser ? 'bg-indigo-50 border-indigo-100 dark:bg-indigo-900/10 dark:border-indigo-800/50 rounded-tr-none text-indigo-900 dark:text-indigo-100' : 'bg-white border-gray-200 dark:bg-gray-900 dark:border-gray-800 rounded-tl-none text-gray-800 dark:text-gray-200' }}">
                                <div class="text-sm markdown-content leading-relaxed">
                                    {!! Str::markdown($message->content) !!}
                                </div>

                                @if($message->attachments->isNotEmpty())
                                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800/60 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach($message->attachments as $attachment)
                                            <div class="flex flex-col gap-2 p-3 rounded-2xl bg-gray-50/80 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-700/50 group/file transition-all hover:bg-white dark:hover:bg-gray-800 shadow-sm hover:shadow-md">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-900 flex items-center justify-center text-gray-400 shrink-0 shadow-sm border border-gray-100 dark:border-gray-700">
                                                        @if(str_contains($attachment->mime_type, 'image'))
                                                            <img src="{{ $attachment->storage_provider === 'google' ? $attachment->web_view_link : route('teams.attachments.view', [$team, $attachment]) }}" class="w-full h-full object-cover rounded-xl">
                                                        @else
                                                            <div class="p-2">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.414a4 4 0 00-5.656-5.656l-6.415 6.414a6 6 0 108.486 8.486L20.5 13" />
                                                                </svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-[10px] font-black text-gray-900 dark:text-gray-100 truncate leading-tight">{{ $attachment->file_name }}</p>
                                                        <p class="text-[9px] text-gray-400 mt-0.5 flex items-center gap-1">
                                                            @if($attachment->storage_provider === 'google')
                                                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-1 rounded-[4px] font-black uppercase text-[7px]">Google Drive</span>
                                                            @else
                                                                {{ number_format($attachment->file_size / 1024, 1) }} KB
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <!-- Attachment Actions -->
                                                <div class="flex items-center justify-end gap-1 pt-2 border-t border-gray-100 dark:border-gray-700/30 opacity-60 group-hover/file:opacity-100 transition-opacity">
                                                    @if($attachment->storage_provider === 'local' && auth()->user()->google_token)
                                                        <form action="{{ route('teams.attachments.to-drive', [$team, $attachment]) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" 
                                                                class="p-1.5 text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                                                title="Mover a Google Drive">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                            </button>
                                                        </form>
                                                    @endif

                                                    <button type="button" 
                                                        onclick="showAttachmentHistory({{ $attachment->id }})"
                                                        class="p-1.5 text-amber-500 hover:text-amber-700 dark:text-amber-400 transition-colors"
                                                        title="Ver historial">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </button>

                                                    <button type="button" 
                                                        @click="$dispatch('ai:analyze-file', { 
                                                            fileName: '{{ addslashes($attachment->file_name) }}', 
                                                            fileId: {{ $attachment->id }},
                                                            fileUrl: '{{ $attachment->storage_provider === 'google' ? $attachment->web_view_link : route('teams.attachments.view', [$team, $attachment]) }}',
                                                            fileType: '{{ $attachment->mime_type }}',
                                                            threadId: {{ $thread->id }},
                                                            messageId: {{ $message->id }},
                                                            teamId: {{ $team->id }},
                                                            autoSubmit: false 
                                                        })"
                                                        class="p-1.5 text-indigo-500 hover:text-indigo-700 dark:text-indigo-400 transition-colors"
                                                        title="Analizar con Ax.ia">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                        </svg>
                                                    </button>

                                                    <a href="{{ $attachment->storage_provider === 'google' ? $attachment->web_view_link : route('teams.attachments.download', [$team, $attachment]) }}" 
                                                       target="_blank"
                                                       class="p-1.5 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
                                                       title="Descargar">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                        <!-- Edit Mode (Hidden) -->
                        @if ($isCurrentUser)
                            <div id="message-edit-{{ $message->id }}" class="hidden w-full pt-2">
                                <form action="{{ route('teams.forum.messages.update', [$team, $message]) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="space-y-3">
                                        <x-markdown-editor 
                                            name="content" 
                                            id="edit-content-{{ $message->id }}"
                                            :value="$message->content"
                                            rows="12"
                                            :upload-url="route('teams.forum.upload_image', $team)"
                                        />
                                        <div class="flex justify-end gap-3 mt-2">
                                            <button type="button" onclick="cancelEdit({{ $message->id }})" 
                                                class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors uppercase tracking-widest">
                                                {{ __('Cancelar') }}
                                            </button>
                                            <button type="submit" 
                                                class="px-6 py-2 text-xs font-bold bg-violet-600 hover:bg-violet-500 text-white rounded-xl transition-all shadow-lg shadow-violet-600/20 active:scale-95 uppercase tracking-widest">
                                                {{ __('Guardar Cambios') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif
                        </div>
                    </div>
                </div>
            @endforeach

            @if($messages instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-8">
                    {{ $messages->links() }}
                </div>
            @endif

            <!-- Reply Box -->
            @if (!$thread->is_locked)
                <div
                    class="mt-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-violet-400 to-indigo-600">
                    </div>
                    <form action="{{ route('teams.forum.messages.store', [$team, $thread]) }}" method="POST" enctype="multipart/form-data"
                          x-data="{ 
                            driveFiles: [], 
                            addFile(file) { 
                                if (!this.driveFiles.find(f => f.id === file.id)) {
                                    this.driveFiles.push({
                                        id: file.id,
                                        name: file.name,
                                        webViewLink: file.webViewLink,
                                        size: file.size || 0,
                                        mimeType: file.mimeType
                                    });
                                }
                            },
                            removeFile(id) {
                                this.driveFiles = this.driveFiles.filter(f => f.id !== id);
                            }
                          }"
                          @drive-file-selected.window="addFile($event.detail)">
                        @csrf
                        <input type="hidden" name="drive_attachments" :value="JSON.stringify(driveFiles)">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 hidden sm:block">
                                <div
                                    class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400 flex items-center justify-center text-sm font-bold shadow-sm">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                            </div>
                            <div class="flex-1 space-y-3 pl-2">
                                <x-markdown-editor 
                                    name="content" 
                                    id="reply-content"
                                    rows="10"
                                    placeholder="Escribe tu respuesta aquí..."
                                    :upload-url="route('teams.forum.upload_image', $team)"
                                />

                                @if($thread->task_id)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-2">
                                        <div class="flex items-center gap-3">
                                            <label class="relative inline-flex items-center cursor-pointer group">
                                                <input type="checkbox" name="is_private" value="1" class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                                                <span class="ml-3 text-xs font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 group-hover:text-red-600 transition-colors">
                                                    {{ __('Respuesta Privada') }}
                                                </span>
                                            </label>
                                            <div class="flex items-center gap-1 text-[10px] text-gray-400 font-medium italic">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836c-.149.598.013 1.224.421 1.633a.75.75 0 001.06-1.06 1.124 1.124 0 01-.316-.925l.71-2.837c.474-1.895-1.48-3.483-3.2-2.62a.75.75 0 00.708 1.322zM12 9a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
                                                </svg>
                                                {{ __('Solo visible para intervinientes de la tarea') }}
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- File Attachments -->
                                <div class="flex flex-col gap-3">
                                    <div class="flex items-center justify-between px-1">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Adjuntar archivos') }}</label>
                                        
                                        @if(auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists())
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
                                    <p class="text-[9px] text-gray-500 ml-1 italic">{{ __('Puedes seleccionar varios archivos locales o vincularlos desde Drive.') }}</p>
                                </div>

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

    @push('modals')
        <x-google-drive-picker :team="$team" />

        <!-- Attachment History Modal -->
        <div id="attachment-history-modal" class="hidden fixed inset-0 z-[110] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-950/80 backdrop-blur-sm transition-opacity" onclick="closeAttachmentHistory()"></div>
                <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-200 dark:border-gray-800">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-gray-900/50">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-white heading uppercase tracking-tight">Historial del Archivo</h3>
                            <p id="history-filename" class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate max-w-sm"></p>
                        </div>
                        <button onclick="closeAttachmentHistory()" class="text-gray-400 hover:text-gray-500 p-2">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <div class="px-6 py-6 max-h-[60vh] overflow-y-auto" id="history-content">
                        <!-- Logs will be injected here -->
                    </div>
                </div>
            </div>
        </div>
    @endpush

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

            function showAttachmentHistory(id) {
                fetch(`/teams/{{ $team->id }}/attachments/history/${id}`) // Using a helper route or direct fetch if available
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('history-filename').innerText = data.attachment.file_name;
                        const content = document.getElementById('history-content');
                        content.innerHTML = '';

                        if (data.logs && data.logs.length > 0) {
                            let html = '<div class="space-y-6 relative ml-4 border-l-2 border-gray-100 dark:border-gray-800 pl-8">';
                            data.logs.forEach(log => {
                                const date = new Date(log.created_at).toLocaleString();
                                html += `
                                    <div class="relative">
                                        <div class="absolute -left-[45px] top-1 w-8 h-8 rounded-full border-4 border-white dark:border-gray-900 bg-gray-400 flex items-center justify-center text-white shadow-sm ring-4 ring-gray-100 dark:ring-gray-800/30">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" /></svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight">${log.action}</span>
                                                <span class="text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-2 py-0.5 rounded-full font-bold tabular-nums">${date}</span>
                                            </div>
                                            <div class="flex items-center gap-2 group">
                                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-tighter">${log.user?.name || 'Sistema'}</span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            html += '</div>';
                            content.innerHTML = html;
                        } else {
                            content.innerHTML = '<div class="text-center py-10"><p class="text-gray-500 italic">Sin movimientos registrados.</p></div>';
                        }
                        document.getElementById('attachment-history-modal').classList.remove('hidden');
                    });
            }

            function closeAttachmentHistory() {
                document.getElementById('attachment-history-modal').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        </script>
    @endpush
</x-app-layout>
