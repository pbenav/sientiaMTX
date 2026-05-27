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
                    <!-- Breadcrumbs -->
                    <div class="flex items-center gap-2 mb-1 overflow-hidden">
                        <a href="{{ route('teams.show', $team) }}" 
                           class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 hover:text-violet-600 transition-all truncate">
                            {{ $team->name }}
                        </a>
                        <svg class="w-3 h-3 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                        <a href="{{ route('teams.forum.index', $team) }}" 
                           class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 hover:text-violet-600 transition-all shrink-0">
                            {{ __('forum.title') ?? 'Foro' }}
                        </a>
                    </div>
                    <div class="flex items-start gap-3" 
                         x-data="{ editingTitle: false }">
                        @if ($thread->is_pinned)
                            <span x-show="!editingTitle" class="text-violet-500 shrink-0 mt-1.5"><svg xmlns="http://www.w3.org/2000/svg"
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
                            <form x-cloak x-show="editingTitle" class="w-full max-w-2xl flex flex-col gap-3" method="POST" action="{{ route('teams.forum.update', [$team, $thread]) }}">
                                @csrf
                                @method('PATCH')
                                <div class="flex items-center gap-2">
                                    <input type="text" name="title" x-ref="titleInput" value="{{ $thread->title }}"
                                        class="font-bold text-xl text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-900 border-2 border-violet-500 rounded-xl px-4 py-2 w-full focus:ring-0 focus:outline-none -ml-2 transition-all">
                                    <button type="submit" class="px-5 py-2 bg-violet-600 hover:bg-violet-500 text-white rounded-xl text-sm font-bold shadow-lg shadow-violet-600/20 transition-all active:scale-95">Guardar</button>
                                    <button type="button" @click="editingTitle = false" class="px-5 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl text-sm font-bold transition-all">Cancelar</button>
                                </div>
                                

                                <!-- Quick Emoji Picker -->
                                <div class="flex items-center gap-1.5 flex-wrap -ml-1">
                                    @foreach(['🚀', '💡', '🐞', '🚨', '📅', '📂', '💬', '✅', '❌', '🤔', '📈', '🔒'] as $emoji)
                                        <button type="button" 
                                            @click="
                                                const el = $refs.titleInput;
                                                const start = el.selectionStart;
                                                const end = el.selectionEnd;
                                                const text = el.value;
                                                el.value = text.slice(0, start) + '{{ $emoji }} ' + text.slice(end);
                                                el.focus();
                                                el.setSelectionRange(start + 3, start + 3);
                                            "
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-violet-100 dark:hover:bg-violet-900/30 border border-gray-100 dark:border-gray-700 text-sm transition-all hover:scale-110 active:scale-90">
                                            {{ $emoji }}
                                        </button>
                                    @endforeach
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest ml-2">Iconos Rápidos</span>
                                </div>
                            </form>
                        @else
                            <h2 class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate">
                                {{ $thread->title }}
                            </h2>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 flex items-center gap-3 mt-1.5 font-medium">
                        <span class="flex items-center gap-1.5 shrink-0">
                        <img src="{{ $thread->user->profile_photo_url }}" alt="{{ $thread->user->name }}" 
                            class="w-5 h-5 rounded-full object-cover shadow-sm border border-white dark:border-gray-900"> Creado por
                            {{ $thread->user->name }}
                        </span>
                        <span class="shrink-0">•</span>
                        <span class="shrink-0">{{ $thread->created_at->format('d M y, H:i') }}</span>
                        <span class="shrink-0">•</span>
                        <span class="flex items-center gap-1.5 shrink-0" title="Vistas">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{ number_format($thread->views) }}
                        </span>
                        @if ($thread->task)
                            <span class="shrink-0">•</span>
                            <a href="{{ route('teams.tasks.show', [$team, $thread->task]) }}"
                                class="text-blue-600 dark:text-blue-400 hover:underline truncate flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.172 13.828a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.102-1.101"/></svg>
                                <span>Tarea: {{ Str::limit($thread->task->title, 30) }}</span>
                            </a>
                        @else
                            @if (auth()->id() === $thread->user_id || auth()->user()->getRole($team) === 'coordinator')
                                <span class="shrink-0">•</span>
                                <button type="button" @click="$dispatch('open-modal', 'link-task-modal')" class="text-gray-400 hover:text-blue-500 transition-colors flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.172 13.828a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.102-1.101"/></svg>
                                    Vincular Tarea
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                @if (auth()->id() === $thread->user_id || auth()->user()->getRole($team) === 'coordinator')
                    <div class="items-center gap-2 shrink-0 hidden md:inline-flex">
                        <button type="button" @click="$dispatch('open-modal', 'link-task-modal')"
                            class="p-2 {{ $thread->task_id ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-white text-gray-500 hover:text-blue-600 hover:bg-blue-50 border-gray-200' }} border dark:bg-gray-800 dark:border-gray-700 rounded-xl transition-colors shadow-sm"
                            title="{{ $thread->task_id ? 'Cambiar tarea vinculada' : 'Vincular a tarea' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.172 13.828a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.102-1.101" />
                            </svg>
                        </button>

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
                            onsubmit="return confirmDeleteThread(this)">
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

        <div class="flex items-center justify-end mb-4">
            <form action="{{ route('teams.forum.show', [$team, $thread]) }}" method="GET" class="flex items-center gap-3">
                <label for="sort_messages" class="text-xs font-bold text-gray-500 uppercase tracking-widest">{{ __('Ordenar:') }}</label>
                <select name="sort_messages" id="sort_messages" onchange="this.form.submit()"
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl text-xs font-bold py-1.5 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition-all cursor-pointer text-gray-700 dark:text-gray-300">
                    <option value="oldest" {{ ($filters['sort_messages'] ?? 'oldest') === 'oldest' ? 'selected' : '' }}>Más antiguos primero</option>
                    <option value="newest" {{ ($filters['sort_messages'] ?? 'oldest') === 'newest' ? 'selected' : '' }}>Más recientes primero</option>
                </select>
            </form>
        </div>

        <div class="space-y-6" x-data="{ 
            replyingToId: null, 
            replyingToName: '',
            driveFiles: [], 
            uploadingLocal: false,
            addFile(detail) {
                if (!detail.targetId || detail.targetId === 'reply-box') {
                    const file = detail.file;
                    const fileId = file.id || file.google_id || (file.provider + '_' + file.name);
                    if (!this.driveFiles.find(f => f.id === fileId)) {
                        this.driveFiles = [...this.driveFiles, {
                            id: fileId,
                            name: file.name,
                            webViewLink: file.webViewLink || file.url,
                            size: file.size || 0,
                            mime_type: file.mimeType || file.mime_type,
                            provider: file.provider || 'google'
                        }];
                    }
                }
            },
            async uploadLocalFile(e) {
                const files = e.target.files;
                if (!files.length) return;
                this.uploadingLocal = true;
                for (let i = 0; i < files.length; i++) {
                    const formData = new FormData();
                    formData.append('attachment_file', files[i]);
                    try {
                        const res = await fetch('{{ route('teams.forum.upload_attachment', $team) }}', {
                            method: 'POST',
                            headers: { 
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });
                        if (!res.ok) {
                            const errorData = await res.json();
                            throw new Error(errorData.message || 'Error de subida');
                        }
                        const data = await res.json();
                        this.driveFiles = [...this.driveFiles, {
                            id: 'local_' + Date.now() + i,
                            name: data.name,
                            path: data.path,
                            size: data.size,
                            mime_type: data.mime_type,
                            provider: 'local'
                        }];
                    } catch (err) { 
                        console.error('Upload failed', err);
                    }
                }
                this.uploadingLocal = false;
                e.target.value = '';
            },
            removeFile(id) {
                this.driveFiles = this.driveFiles.filter(f => f.id !== id);
            }
        }" @drive-file-selected.window="addFile($event.detail)">
            @foreach ($messages as $index => $message)
                <div class="space-y-4">
                    @include('teams.forum.partials.message-item', [
                        'message' => $message, 
                        'isRoot' => true, 
                        'index' => $index, 
                        'currentPage' => $messages->currentPage()
                    ])
                </div>
            @endforeach

            @if($messages instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-8">
                    {{ $messages->links() }}
                </div>
            @endif

            <!-- Reply Box -->
            @if (!$thread->is_locked)
                <div id="reply-box-container" class="mt-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm relative overflow-hidden scroll-mt-24">
                    <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-violet-400 to-violet-600"></div>
                    
                    <form action="{{ route('teams.forum.messages.store', [$team, $thread]) }}" method="POST" enctype="multipart/form-data"
                          onsubmit="return window.validateForumForm(this)">
                        @csrf
                        <input type="hidden" name="drive_attachments" :value="JSON.stringify(driveFiles)">
                        <input type="hidden" name="parent_id" x-model="replyingToId">

                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <img src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover shadow-sm">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Respuesta rápida</span>
                                </div>
                                <template x-if="replyingToId">
                                    <div class="flex items-center gap-2 bg-violet-50 dark:bg-violet-900/30 px-3 py-1 rounded-full border border-violet-100 dark:border-violet-800">
                                        <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 uppercase tracking-widest">Respondiendo a <span x-text="replyingToName"></span></span>
                                        <button type="button" @click="replyingToId = null; replyingToName = ''" class="text-violet-400 hover:text-violet-600">✕</button>
                                    </div>
                                </template>
                            </div>

                            <div class="flex-1">
                                <x-markdown-editor 
                                    name="content" 
                                    id="reply-content"
                                    rows="8"
                                    placeholder="Escribe tu respuesta aquí..."
                                    :upload-url="route('teams.forum.upload_image', $team)"
                                    :mentions-url="route('teams.mentions', $team)"
                                />

                                <!-- Unified Attachments Preview -->
                                <div x-show="driveFiles.length > 0" class="mt-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <template x-for="(file, index) in driveFiles" :key="file.id">
                                            <div class="flex items-center justify-between p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-800">
                                                <div class="flex items-center gap-2 truncate">
                                                    <span class="text-[10px] font-bold text-gray-700 dark:text-gray-300 truncate" x-text="file.name"></span>
                                                </div>
                                                <button type="button" @click="removeFile(file.id)" class="text-gray-400 hover:text-red-500">✕</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between gap-4 pt-4 border-t border-gray-100 dark:border-gray-800 mt-4">
                                    <div class="flex items-center gap-2">
                                        @php
                                            $isTeamLinked = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
                                        @endphp
                                        <label class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-violet-500 rounded-xl cursor-pointer transition-all group">
                                            <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.414a4 4 0 00-5.656-5.656l-6.415 6.414a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            <span class="text-[10px] font-black text-gray-600 dark:text-gray-400 uppercase tracking-tight">{{ __('Local') }}</span>
                                            <input type="file" multiple class="hidden" @change="uploadLocalFile($event)">
                                        </label>
                                        @if($isTeamLinked)
                                            <button type="button" @click="$dispatch('open-drive-picker', { targetId: 'reply-box' })"
                                                class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-blue-500 rounded-xl cursor-pointer transition-all group">
                                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                                                <span class="text-[10px] font-black text-gray-600 dark:text-gray-400 uppercase tracking-tight">{{ __('Drive') }}</span>
                                            </button>
                                        @else
                                            <a href="{{ route('profile.edit', ['tab' => 'integrations']) }}" 
                                                class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-gray-500 rounded-xl cursor-pointer transition-all group" title="Vincular Drive">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.172 13.828a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.102-1.101" /></svg>
                                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-tight">{{ __('Vincular') }}</span>
                                            </a>
                                        @endif
                                    </div>

                                    <button type="submit" 
                                        class="inline-flex items-center gap-2 px-8 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-black rounded-2xl transition-all shadow-lg shadow-violet-600/20 active:scale-95">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        {{ __('Enviar respuesta') }}
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

        @php
            $isManager = auth()->user()->getRole($team) === 'coordinator';
            $availableTasks = \App\Models\Task::where('team_id', $team->id)
                ->whereNull('parent_id')
                ->visibleTo(auth()->user(), $isManager)
                ->where(function($q) use ($thread) {
                    $q->whereDoesntHave('forumThread')
                      ->orWhere('id', $thread->task_id);
                })
                ->orderBy('title')
                ->get();
        @endphp

        <x-modal name="link-task-modal" focusable>
            <form method="post" action="{{ route('teams.forum.update', [$team, $thread]) }}" class="p-6"
                  x-data="{}" x-init="$nextTick(() => window.initForumTaskSelect($refs.taskSelectModal))">
                @csrf
                @method('PATCH')
                
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.172 13.828a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.102-1.101" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white heading">
                        Vincular a Tarea
                    </h2>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Selecciona una tarea para vincular este hilo. Esto permitirá acceder rápidamente a la conversación desde la tarea y viceversa.
                </p>

                <div class="mt-6">
                    <x-input-label for="task_id_modal" value="Seleccionar Tarea" class="mb-2" />
                    <select x-ref="taskSelectModal" id="task_id_modal" name="task_id" class="task-selector-tom w-full">
                        <option value="">{{ __('forum.none') ?? '-- Ninguna (Biblioteca de Conocimiento) --' }}</option>
                        @foreach ($availableTasks as $t)
                            <option value="{{ $t->id }}" {{ $thread->task_id == $t->id ? 'selected' : '' }}
                                data-assignee="{{ $t->assignedUser ? $t->assignedUser->name : ($t->assignedTo->count() > 0 ? $t->assignedTo->first()->name : 'Sin asignar') }}">
                                [{{ __('tasks.statuses.' . $t->status) }}] {{ $t->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('forum.cancel') ?? 'Cancelar' }}
                    </x-secondary-button>

                    <x-primary-button class="bg-blue-600 hover:bg-blue-500 shadow-blue-600/20">
                        {{ __('Guardar vinculación') }}
                    </x-primary-button>
                </div>
            </form>
        </x-modal>

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
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        /* Bulletproof Modern TomSelect Wrapper */
        .ts-control {
            border-radius: 0.75rem !important;
            border-width: 1px !important;
            background-color: #f9fafb !important;
            border-color: #e5e7eb !important;
            padding: 0.625rem 1rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            min-height: 40px !important;
            display: flex !important;
            align-items: center !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }
        .ts-control input { 
            font-size: 12px !important; 
            padding: 0 !important; 
            margin: 0 !important; 
            background: transparent !important; 
            border: none !important; 
            outline: none !important; 
            box-shadow: none !important;
            line-height: 1 !important;
            height: auto !important;
            color: inherit !important;
        }
        .ts-control input::placeholder { color: #9ca3af !important; font-weight: 500 !important; }
        
        .dark .ts-control {
            background-color: #1f2937 !important;
            border-color: #374151 !important;
            color: #f3f4f6 !important;
        }
        
        .ts-wrapper.focus .ts-control {
            border-color: #7c3aed !important;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2) !important;
        }
        
        .ts-dropdown { 
            border-radius: 1rem !important; 
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important; 
            margin-top: 6px !important; 
            padding: 0.5rem !important; 
            z-index: 9999 !important;
        }
        .dark .ts-dropdown { background-color: #111827 !important; border-color: #374151 !important; }
        
        .ts-dropdown .option { 
            padding: 0.625rem 0.75rem !important; 
            border-radius: 0.6rem !important; 
            margin-bottom: 2px !important; 
            transition: all 0.15s ease !important;
            color: #374151 !important;
        }
        .dark .ts-dropdown .option { color: #e5e7eb !important; }
        
        .ts-dropdown .active { 
            background-color: #f5f3ff !important; 
            color: #4f46e5 !important; 
        }
        .dark .ts-dropdown .active { background-color: #4f46e5 !important; color: #ffffff !important; }
        
        select.task-selector-tom { display: none !important; }
    </style>
    <script>
            function quoteMessage(name, content) {
                const textarea = document.getElementById('reply-content');
                if (textarea) {
                    const quote = `> **${name}**: ${content}\n\n`;
                    textarea.value = quote + textarea.value;
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                    textarea.focus();
                    textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            function replyTo(messageId, name) {
                const container = document.getElementById('reply-box-container');
                if (container) {
                    const outerData = Alpine.$data(document.querySelector('[x-data*="replyingToId"]'));
                    if (outerData) {
                        outerData.replyingToId = messageId;
                        outerData.replyingToName = name;
                    }
                    
                    const textarea = document.getElementById('reply-content');
                    if (textarea) {
                        textarea.focus();
                        container.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }

            if (typeof window.printMessage === 'undefined') {
                window.printMessage = function(messageId) {
                    const content = document.getElementById('msg-content-' + messageId) || document.getElementById('message-view-' + messageId);
                    if (!content) return;
                    const printWindow = window.open('', '', 'height=600,width=800');
                    printWindow.document.write('<html><head><title>Imprimir Mensaje</title>');
                    printWindow.document.write('<style>body { font-family: sans-serif; padding: 20px; line-height: 1.5; color: #333; } pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }</style>');
                    printWindow.document.write('</head><body>');
                    printWindow.document.write(content.innerHTML);
                    printWindow.document.write('</body></html>');
                    printWindow.document.close();
                    printWindow.focus();
                    setTimeout(() => {
                        printWindow.print();
                        printWindow.close();
                    }, 250);
                };
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
                fetch(`/teams/{{ $team->id }}/attachments/history/${id}`)
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

            window.confirmDeleteThread = function(form) {
                Swal.fire({
                    title: '{{ __('¿Eliminar todo el hilo?') }}',
                    text: '{{ __('Esta acción no se puede deshacer y eliminará todos los mensajes.') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: '{{ __('Sí, eliminar todo') }}',
                    cancelButtonText: '{{ __('Cancelar') }}',
                    customClass: {
                        popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                        title: 'text-red-600 dark:text-red-400 font-black uppercase tracking-tighter pt-8 text-lg',
                        htmlContainer: 'text-sm font-medium text-slate-600 dark:text-slate-400 px-8 pb-4',
                        confirmButton: 'rounded-2xl px-6 py-3 shadow-lg shadow-red-500/30 uppercase tracking-widest font-black text-[10px]',
                        cancelButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]'
                    },
                    buttonsStyling: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
                return false;
            }

            window.validateForumForm = function(form) {
                const content = form.querySelector('textarea[name="content"]').value.trim();
                if (!content) {
                    Swal.fire({
                        title: '{{ __('Mensaje vacío') }}',
                        text: '{{ __('El mensaje será desechado si no escribes algo.') }}',
                        icon: 'warning',
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: '{{ __('Entendido') }}',
                        customClass: {
                            popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]'
                        }
                    });
                    return false;
                }
                return true;
            }

            window.confirmDeleteMessage = function(form, isFirst) {
                const text = isFirst ? '{{ __('Este es el primer post. Borrarlo eliminará todo el hilo. ¿Estás seguro?') }}' : '{{ __('¿Eliminar este mensaje?') }}';
                Swal.fire({
                    title: '{{ __('Eliminar mensaje') }}',
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: '{{ __('Sí, eliminar') }}',
                    cancelButtonText: '{{ __('Cancelar') }}',
                    customClass: {
                        popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                        title: 'text-red-600 dark:text-red-400 font-black uppercase tracking-tighter pt-8 text-lg',
                        htmlContainer: 'text-sm font-medium text-slate-600 dark:text-slate-400 px-8 pb-4',
                        confirmButton: 'rounded-2xl px-6 py-3 shadow-lg shadow-red-500/30 uppercase tracking-widest font-black text-[10px]',
                        cancelButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]'
                    },
                    buttonsStyling: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
                return false;
            }

            window.confirmAttachmentDelete = function(id, provider = 'local') {
                if (provider === 'google') {
                    Swal.fire({
                        title: '¿Qué deseas hacer?',
                        text: "Este archivo está en Google Drive. ¿Quieres eliminarlo de la nube o solo desvincularlo de esta tarea?",
                        icon: 'question',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Eliminar de Drive y MTX',
                        denyButtonText: 'Solo desvincular de MTX',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#ef4444',
                        denyButtonColor: '#6b7280',
                        customClass: {
                            popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]',
                            denyButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]',
                            cancelButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById(`delete-attachment-${id}`);
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'delete_from_drive';
                            input.value = '1';
                            form.appendChild(input);
                            form.submit();
                        } else if (result.isDenied) {
                            document.getElementById(`delete-attachment-${id}`).submit();
                        }
                    });
                } else {
                    Swal.fire({
                        title: "{{ __('¿Eliminar este archivo?') }}",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: '{{ __('Sí, eliminar') }}',
                        cancelButtonText: '{{ __('Cancelar') }}',
                        customClass: {
                            popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]',
                            cancelButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById(`delete-attachment-${id}`).submit();
                        }
                    });
                }
            }

            window.shareMessage = function(id) {
                const url = window.location.origin + window.location.pathname + '#msg-' + id;
                navigator.clipboard.writeText(url).then(() => {
                    Swal.fire({
                        title: '{{ __("Enlace copiado") }}',
                        text: '{{ __("El enlace directo a este mensaje ha sido copiado al portapapeles.") }}',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            title: 'text-emerald-600 dark:text-emerald-400 font-black uppercase tracking-tighter pt-8 text-lg',
                            htmlContainer: 'text-sm font-medium text-slate-600 dark:text-slate-400 px-8 pb-4',
                        }
                    });
                });
            }

            window.voteMessage = function(messageId, button) {
                const url = `/teams/{{ $team->id }}/forum/messages/${messageId}/vote`;
                
                button.disabled = true;
                
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const countSpan = button.querySelector('.votes-count');
                        if (countSpan) {
                            countSpan.textContent = data.votes_count;
                        }
                        const svg = button.querySelector('svg');
                        if (data.voted) {
                            button.classList.remove('text-gray-400');
                            button.classList.add('text-violet-600', 'dark:text-violet-400');
                            if (svg) svg.setAttribute('fill', 'currentColor');
                            
                            button.classList.add('scale-125', 'transition-transform', 'duration-200');
                            setTimeout(() => button.classList.remove('scale-125'), 200);
                        } else {
                            button.classList.remove('text-violet-600', 'dark:text-violet-400');
                            button.classList.add('text-gray-400');
                            if (svg) svg.setAttribute('fill', 'none');
                            
                            button.classList.add('scale-75', 'transition-transform', 'duration-200');
                            setTimeout(() => button.classList.remove('scale-75'), 200);
                        }
                    }
                })
                .catch(err => console.error(err))
                .finally(() => {
                    button.disabled = false;
                });
            }
            window.initForumTaskSelect = function(el) {
                if (el && window.TomSelect && !el.tomselect) {
                    new TomSelect(el, {
                        create: false,
                        sortField: { field: 'text', direction: 'asc' },
                        placeholder: 'Buscar tarea...',
                        allowEmptyOption: true,
                        render: {
                            option: function(data, escape) {
                                // TomSelect maps data-assignee from native <option> to data.assignee
                                const usr = data.assignee || 'Sin asignar';
                                return `<div class="flex items-center gap-3 py-0.5">
                                    <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 shrink-0 border border-gray-200/50 dark:border-gray-700/50">
                                        <span class="text-[8px] font-mono font-black">#${escape(data.value)}</span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-bold text-gray-900 dark:text-white truncate text-xs">${escape(data.text)}</span>
                                        <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest mt-0.5">@${escape(usr)}</span>
                                    </div>
                                </div>`;
                            },
                            item: function(data, escape) {
                                return `<div class="flex items-center gap-2">
                                    <span class="text-[9px] font-mono font-bold text-violet-500 bg-violet-50 dark:bg-violet-900/30 px-1 py-0.5 rounded border border-violet-100/50 dark:border-violet-800/50">#${escape(data.value)}</span>
                                    <span class="font-bold text-xs text-gray-900 dark:text-white truncate max-w-[200px]">${escape(data.text)}</span>
                                </div>`;
                            }
                        }
                    });
                }
            };
            // Re-attempt init once fully loaded in case it missed the window
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('.task-selector-tom').forEach(select => {
                    window.initForumTaskSelect(select);
                });
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

             <!-- Back button -->
             <a href="{{ route('teams.forum.index', $team) }}" 
                class="flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-violet-600 dark:text-gray-400 dark:hover:text-violet-400 transition-colors py-1.5 px-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                 </svg>
                 <span>Volver</span>
             </a>

             <!-- Divider -->
             <div class="h-5 w-px bg-gray-200 dark:bg-gray-700"></div>

             <!-- Thread Title Context -->
             <span class="text-xs font-bold text-gray-600 dark:text-gray-300 max-w-[150px] sm:max-w-[280px] truncate tracking-tight py-0.5">
                 {{ $thread->title }}
             </span>

             <!-- Divider -->
             <div class="h-5 w-px bg-gray-200 dark:bg-gray-700"></div>

             <!-- Floating Scroll Button -->
             <button onclick="(function() {
                         window.scrollTo({ top: 0, behavior: 'smooth' });
                         document.documentElement.scrollTo({ top: 0, behavior: 'smooth' });
                         document.body.scrollTo({ top: 0, behavior: 'smooth' });
                         document.querySelectorAll('.overflow-y-auto, [style*=\'overflow-y: auto\'], [style*=\'overflow-y: scroll\']').forEach(el => {
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
