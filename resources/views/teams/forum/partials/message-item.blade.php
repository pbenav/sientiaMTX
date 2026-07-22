@php
    $isCurrentUser = $message->user_id === auth()->id();
    $isFirst = $isRoot && ($index ?? 0) === 0 && ($currentPage ?? 1) === 1;
@endphp

<div id="msg-{{ $message->id }}" class="flex gap-4 {{ $isCurrentUser ? 'flex-row-reverse' : '' }} scroll-mt-24 {{ !$isRoot ? 'scale-95 origin-left opacity-90' : '' }}">
    <!-- Avatar -->
    <div class="flex-shrink-0 mt-1">
        <img src="{{ $message->user->profile_photo_url }}" alt="{{ $message->user->name }}" 
            class="{{ $isRoot ? 'w-10 h-10' : 'w-8 h-8' }} rounded-full object-cover shadow-sm border border-white dark:border-gray-900">
    </div>

    <!-- Message Bubble -->
    <div class="flex flex-col {{ $isCurrentUser ? 'items-end' : 'items-start' }} w-full {{ $isRoot ? 'max-w-[85%]' : 'max-w-[92%]' }}">
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
                    <!-- Vote Button -->
                    <button type="button" onclick="voteMessage({{ $message->id }}, this)"
                        class="p-1 text-gray-400 {{ $message->hasVotedBy(auth()->user()) ? 'text-violet-600 dark:text-violet-400' : 'hover:text-violet-600' }} bg-gray-50 dark:bg-gray-800 rounded-lg transition-all flex items-center gap-1 text-[10px] font-bold"
                        title="Votar comentario">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="{{ $message->hasVotedBy(auth()->user()) ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3zM7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3" />
                        </svg>
                        <span class="votes-count">{{ $message->votes_count ?? $message->votes()->count() }}</span>
                    </button>

                    <!-- Share -->
                    <button type="button" onclick="shareMessage({{ $message->id }})"
                        class="p-1 text-gray-400 hover:text-emerald-500 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors"
                        title="Compartir enlace directo">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                    </button>

                    <!-- Reply Button -->
                    <button type="button"
                        onclick="replyTo({{ $message->id }}, {{ json_encode($message->user->name) }})"
                        class="p-1 text-gray-400 hover:text-violet-500 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors"
                        title="Responder a este mensaje">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l5 5m-5-5l5-5" />
                        </svg>
                    </button>

                    <!-- Quote -->
                    <button type="button"
                        onclick="quoteMessage({{ json_encode($message->user->name) }}, {{ json_encode($message->content) }})"
                        class="p-1 text-gray-400 hover:text-violet-500 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors"
                        title="Citar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </button>

                    <!-- Print -->
                    <button type="button"
                        onclick="openForumPrintModal({{ $message->id }})"
                        class="p-1 text-gray-400 hover:text-emerald-500 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors"
                        title="Opciones de impresión">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>

                    <!-- Edit -->
                    @if ($isCurrentUser)
                        <button type="button" onclick="editMessage({{ $message->id }}, {{ json_encode($message->content) }})"
                            class="p-1 text-gray-400 hover:text-blue-500 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors"
                            title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
                        class="p-1 text-gray-400 hover:text-violet-500 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors"
                        title="Preguntar a Ax.ia">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </button>

                    <!-- Delete -->
                    @if ($isCurrentUser || auth()->user()->getRole($team) === 'coordinator')
                        <form action="{{ route('teams.forum.messages.destroy', [$team, $message]) }}"
                            method="POST" onsubmit="return confirmDeleteMessage(this, {{ $isFirst ? 'true' : 'false' }})"
                            class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="p-1 text-gray-400 hover:text-red-500 bg-gray-50 dark:bg-gray-800 rounded-lg transition-colors"
                                title="Eliminar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
                class="p-4 rounded-2xl shadow-sm border {{ $isCurrentUser ? 'bg-violet-50 border-violet-100 dark:bg-violet-900/10 dark:border-violet-800/50 rounded-tr-none text-violet-900 dark:text-violet-100' : 'bg-white border-gray-200 dark:bg-gray-900 dark:border-gray-800 rounded-tl-none text-gray-800 dark:text-gray-200' }} {{ !$isRoot ? 'py-3' : '' }}">
                <div x-data="{ expanded: false, isOverflowing: false }"
                     x-init="$nextTick(() => { if ($refs.contentBox.scrollHeight > 300) isOverflowing = true })"
                     class="relative">
                    
                    <div x-ref="contentBox"
                         class="markdown-content leading-relaxed transition-all duration-300"
                         :class="(!expanded && isOverflowing) ? 'max-h-[300px] overflow-hidden' : ''">
                        @php
                            $decoded = json_decode($message->content, true);
                            $isJson = (json_last_error() === JSON_ERROR_NONE) && (is_array($decoded) || is_object($decoded));
                        @endphp

                    @if($isJson)
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-4 my-2 border border-gray-100 dark:border-gray-700 font-mono text-xs overflow-x-auto">
                            <pre><code>{{ json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    @else
                        {!! Str::markdown($message->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    @endif
                    </div>
                    
                    <!-- Gradiente y Botón "Ver más" -->
                    <template x-if="!expanded && isOverflowing">
                        <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-white dark:from-gray-900 to-transparent flex items-end justify-center pb-2 pointer-events-none">
                            <button @click="expanded = true" type="button" class="pointer-events-auto text-[10px] uppercase tracking-widest font-black text-violet-600 dark:text-violet-400 bg-white dark:bg-gray-800 px-4 py-2 rounded-full shadow-md border border-violet-100 dark:border-violet-900/50 hover:bg-violet-50 dark:hover:bg-violet-900/30 transition-all hover:scale-105">
                                Mostrar más
                            </button>
                        </div>
                    </template>
                    
                    <!-- Botón "Ver menos" -->
                    <template x-if="expanded && isOverflowing">
                        <div class="flex justify-center mt-3">
                            <button @click="expanded = false; $el.closest('.relative').scrollIntoView({ behavior: 'smooth', block: 'center' })" type="button" class="text-[10px] uppercase tracking-widest font-black text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                Mostrar menos
                            </button>
                        </div>
                    </template>
                </div>

                @if($message->attachments->isNotEmpty())
                    @php
                        $imageAttachments = $message->attachments->filter(fn($a) => str_contains($a->mime_type, 'image'));
                        $otherAttachments = $message->attachments->reject(fn($a) => str_contains($a->mime_type, 'image'));
                    @endphp

                    {{-- Image Attachments Gallery --}}
                    @if($imageAttachments->isNotEmpty())
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800/60">
                            <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-wider block mb-2">Imágenes adjuntas ({{ $imageAttachments->count() }})</span>
                            <div class="flex flex-wrap gap-3">
                                @foreach($imageAttachments as $attachment)
                                    @php
                                        $imgUrl = $attachment->storage_provider === 'google' ? $attachment->web_view_link : route('teams.attachments.view', [$team, $attachment]);
                                        $downloadUrl = $attachment->storage_provider === 'google' ? $attachment->web_view_link : route('teams.attachments.download', [$team, $attachment]);
                                    @endphp
                                    <div class="group/img relative w-48 rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-800 bg-gray-900/5 dark:bg-gray-800/50 shadow-sm hover:shadow-md transition-all shrink-0">
                                        <div class="h-28 w-full overflow-hidden bg-gray-900/20 cursor-pointer flex items-center justify-center relative"
                                             onclick="openImageLightbox('{{ $imgUrl }}', '{{ addslashes($attachment->file_name) }}')">
                                            @if($attachment->exists)
                                                <img src="{{ $imgUrl }}" alt="{{ $attachment->file_name }}"
                                                     class="w-full h-full object-cover group-hover/img:scale-105 transition-transform duration-300">
                                                <div class="absolute inset-0 bg-black/0 group-hover/img:bg-black/25 transition-all flex items-center justify-center">
                                                    <span class="opacity-0 group-hover/img:opacity-100 bg-black/60 text-white text-[9px] font-bold px-2.5 py-1 rounded-full backdrop-blur-sm transition-all transform translate-y-1 group-hover/img:translate-y-0 flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                                        Ampliar
                                                    </span>
                                                </div>
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-red-500 text-xs font-bold">Archivo Purgado</div>
                                            @endif
                                        </div>
                                        
                                        <div class="p-2 flex items-center justify-between bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800">
                                            <div class="min-w-0 flex-1 pr-1.5">
                                                <p class="text-[10px] font-bold text-gray-800 dark:text-gray-200 truncate" title="{{ $attachment->file_name }}">
                                                    {{ $attachment->file_name }}
                                                </p>
                                                <p class="text-[8px] text-gray-400">
                                                    @if($attachment->storage_provider === 'google')
                                                        <span class="text-blue-500 font-bold">Drive</span>
                                                    @else
                                                        {{ number_format($attachment->file_size / 1024, 1) }} KB
                                                    @endif
                                                </p>
                                            </div>
                                            
                                            <div class="flex items-center gap-0.5 shrink-0">
                                                @if($attachment->exists)
                                                    <a href="{{ $downloadUrl }}" target="_blank"
                                                       class="p-1 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-md transition-colors"
                                                       title="Descargar">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                    </a>
                                                @endif

                                                @if ($isCurrentUser || auth()->user()->getRole($team) === 'coordinator')
                                                    @if($attachment->storage_provider === 'local')
                                                        <button type="button" onclick="editAttachmentImage({{ $attachment->id }}, '{{ route('teams.attachments.view', [$team, $attachment]) }}')"
                                                            class="p-1 text-gray-400 hover:text-violet-500 rounded-md transition-colors" title="Editar Imagen">
                                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                                        </button>
                                                    @endif

                                                    <button type="button" onclick="renameAttachment({{ $attachment->id }}, '{{ addslashes($attachment->file_name) }}')"
                                                        class="p-1 text-gray-400 hover:text-blue-500 rounded-md transition-colors" title="Renombrar">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    </button>

                                                    <form action="{{ route('teams.attachments.destroy', [$team, $attachment]) }}" method="POST"
                                                        onsubmit="return confirm('¿Seguro que quieres eliminar este adjunto?')" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-1 text-gray-400 hover:text-red-500 rounded-md transition-colors" title="Eliminar">
                                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Non-Image Document Attachments --}}
                    @if($otherAttachments->isNotEmpty())
                        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800/60 grid grid-cols-1 {{ $isRoot ? 'sm:grid-cols-2' : '' }} gap-2">
                            @foreach($otherAttachments as $attachment)
                                <div class="flex flex-col gap-2 p-3 rounded-2xl bg-gray-50/80 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-700/50 group/file transition-all hover:bg-white dark:hover:bg-gray-800 shadow-sm hover:shadow-md">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-900 flex items-center justify-center text-gray-400 shrink-0 shadow-sm border border-gray-100 dark:border-gray-700">
                                            @if(!$attachment->exists)
                                                <div class="p-2 text-red-500/50">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                </div>
                                            @else
                                                <div class="p-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.414a4 4 0 00-5.656-5.656l-6.415 6.414a6 6 0 108.486 8.486L20.5 13" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[10px] font-black text-gray-900 dark:text-gray-100 truncate leading-tight">
                                                @if($attachment->exists)
                                                    <a href="{{ $attachment->storage_provider === 'google' ? $attachment->web_view_link : route('teams.attachments.download', [$team, $attachment]) }}" 
                                                       target="_blank" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                                        {{ $attachment->file_name }}
                                                    </a>
                                                @else
                                                    <span class="text-gray-400 line-through decoration-red-500/50">{{ $attachment->file_name }}</span>
                                                @endif
                                            </p>
                                            <p class="text-[9px] text-gray-400 mt-0.5 flex items-center gap-1">
                                                @if(!$attachment->exists)
                                                    <span class="text-red-500 font-bold uppercase tracking-tighter">Archivo Purgado</span>
                                                @elseif($attachment->storage_provider === 'google')
                                                    <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-1 rounded-[4px] font-black uppercase text-[7px]">Google Drive</span>
                                                @else
                                                    {{ number_format($attachment->file_size / 1024, 1) }} KB
                                                @endif
                                            </p>
                                        </div>

                                        <!-- Actions for Attachments -->
                                        @if ($isCurrentUser || auth()->user()->getRole($team) === 'coordinator')
                                        <div class="flex items-center gap-1 ml-2 shrink-0">
                                            <!-- Edit/Rename -->
                                            <button type="button" onclick="renameAttachment({{ $attachment->id }}, '{{ addslashes($attachment->file_name) }}')"
                                                class="p-1.5 text-gray-400 hover:text-blue-500 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm rounded-lg transition-colors"
                                                title="Renombrar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>

                                            <!-- Delete -->
                                            <form action="{{ route('teams.attachments.destroy', [$team, $attachment]) }}" method="POST"
                                                onsubmit="return confirm('¿Seguro que quieres eliminar este adjunto?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-1.5 text-gray-400 hover:text-red-500 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm rounded-lg transition-colors"
                                                    title="Eliminar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            <!-- Edit Mode (Hidden) -->
            @if ($isCurrentUser)
                <div id="message-edit-{{ $message->id }}" class="hidden w-full pt-2"
                     x-data="{ driveFiles: [], uploadingLocal: false }"
                     @drive-file-selected.window="if($event.detail.targetId === 'edit-{{ $message->id }}') driveFiles.push($event.detail.file)">
                    <form action="{{ route('teams.forum.messages.update', [$team, $message]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        <x-markdown-editor name="content" id="edit-content-{{ $message->id }}" :value="$message->content" rows="8" :upload-url="route('teams.forum.upload_image', $team)" :mentions-url="route('teams.mentions', $team)" />
                        
                        <!-- Adjuntar nuevos archivos al editar -->
                        <div class="flex flex-col gap-3 mt-4">
                            <div class="flex items-center justify-between px-1">
                                <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('Adjuntar nuevos archivos') }}</label>
                                
                                @php 
                                    $isTeamLinked = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
                                @endphp

                                @if($isTeamLinked)
                                    <button type="button" @click="$dispatch('open-drive-picker', { mode: 'collect', targetId: 'edit-{{ $message->id }}' })"
                                        class="text-[10px] font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                        <svg class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" viewBox="0 0 24 24"></svg>
                                        {{ __('Google Drive') }}
                                    </button>
                                @endif
                            </div>

                            <!-- Drive Files List en Edición -->
                            <template x-if="driveFiles.length > 0">
                                <div class="grid grid-cols-1 gap-2 mb-1">
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
                                            <button type="button" @click="driveFiles = driveFiles.filter(f => f.id !== file.id)" class="text-blue-400 hover:text-red-500 transition-colors p-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <input type="hidden" name="drive_attachments" :value="JSON.stringify(driveFiles)">

                            <input type="file" name="attachments[]" multiple
                                class="block w-full text-xs text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-xl file:border-0
                                file:text-[10px] file:font-black file:uppercase file:tracking-widest
                                file:bg-violet-50 file:text-violet-700
                                hover:file:bg-violet-100
                                dark:file:bg-violet-900/30 dark:file:text-violet-400
                                bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 rounded-2xl cursor-pointer">
                        </div>

                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" onclick="cancelEdit({{ $message->id }})" class="text-xs font-bold text-gray-500">Cancelar</button>
                            <button type="submit" class="px-4 py-1.5 bg-violet-600 hover:bg-violet-500 active:scale-95 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-violet-500/10">Guardar</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Recursive Replies --}}
@if($message->replies->isNotEmpty())
    <div class="ml-6 sm:ml-12 mt-4 space-y-4 border-l-2 border-gray-100 dark:border-gray-800 pl-4 sm:pl-6">
        @foreach($message->replies as $reply)
            @include('teams.forum.partials.message-item', [
                'message' => $reply, 
                'isRoot' => false
            ])
        @endforeach
    </div>
@endif
