<!-- Attachments Section -->
            <div x-data="{}"
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">

                {{-- ── Header premium de la sección ── --}}
                <div class="flex items-start justify-between mb-5 gap-3">
                    {{-- Título con icono --}}
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0 shadow-sm border border-violet-200/50 dark:border-violet-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-white">{{ __('activities.attachments') }}</h3>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-wide">{{ __('Máx. :size por archivo', ['size' => ini_get('upload_max_filesize')]) }}</p>
                        </div>
                    </div>

                    {{-- Barra de acciones premium --}}
                    <div class="flex flex-wrap items-center justify-end gap-2">

                        {{-- Botón: Subir archivo --}}
                        <form id="attachment-form" action="{{ route('teams.activities.attachments.upload', [$team, $activity]) }}" method="POST" enctype="multipart/form-data" class="m-0 p-0 inline-block">
                            @csrf
                            <label class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold
                                   bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400
                                   border border-violet-200 dark:border-violet-500/20
                                   hover:bg-violet-600 hover:text-white hover:border-violet-600
                                   dark:hover:bg-violet-500 dark:hover:text-white dark:hover:border-violet-500
                                   shadow-sm hover:shadow-violet-500/25 hover:shadow-md
                                   transition-all duration-200 active:scale-95 cursor-pointer mb-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                {{ __('activities.add_attachment') }}
                                <input type="file" id="attachment-input" name="files[]" multiple
                                    onchange="handleAttachmentUpload(this)" class="hidden">
                            </label>
                        </form>

                        {{-- Botón: Nuevo documento (dropdown) --}}
                        @can('update', $activity)
                        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                            <button type="button" @click="open = !open"
                                class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold
                                       bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400
                                       border border-teal-200 dark:border-teal-500/20
                                       hover:bg-teal-600 hover:text-white hover:border-teal-600
                                       dark:hover:bg-teal-500 dark:hover:text-white dark:hover:border-teal-500
                                       shadow-sm hover:shadow-teal-500/25 hover:shadow-md
                                       transition-all duration-200 active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ __('Nuevo documento') }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            {{-- Formularios ocultos para cada tipo --}}
                            <form id="create-docx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="docx">
                            </form>
                            <form id="create-xlsx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="xlsx">
                            </form>
                            <form id="create-pptx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="pptx">
                            </form>

                            <div x-show="open"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                x-cloak
                                class="absolute right-0 top-full mt-2 w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl z-50 overflow-hidden ring-1 ring-black/5 dark:ring-white/5">
                                <div class="px-3 pt-3 pb-1.5">
                                    <p class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Crear con OnlyOffice</p>
                                </div>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('create-docx-form').submit()"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group/item">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 group-hover/item:scale-110 transition-transform">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13zM9 13h6v1H9v-1zm0 2h6v1H9v-1zm0 2h4v1H9v-1z"/></svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-xs font-bold text-gray-800 dark:text-white">Documento de texto</div>
                                        <div class="text-[10px] text-gray-400 font-medium">.docx · Word / Writer</div>
                                    </div>
                                </button>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('create-xlsx-form').submit()"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group/item">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 group-hover/item:scale-110 transition-transform">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13zM8 12h2v1H8v-1zm0 2h2v1H8v-1zm0 2h2v1H8v-1zm3-4h5v1h-5v-1zm0 2h5v1h-5v-1zm0 2h5v1h-5v-1z"/></svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-xs font-bold text-gray-800 dark:text-white">Hoja de cálculo</div>
                                        <div class="text-[10px] text-gray-400 font-medium">.xlsx · Excel / Calc</div>
                                    </div>
                                </button>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('create-pptx-form').submit()"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors group/item">
                                    <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400 shrink-0 group-hover/item:scale-110 transition-transform">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-2 3l-2 3h4l-2-3zm2.5 3.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/></svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-xs font-bold text-gray-800 dark:text-white">Presentación</div>
                                        <div class="text-[10px] text-gray-400 font-medium">.pptx · PowerPoint / Impress</div>
                                    </div>
                                </button>
                                <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-800 mt-1">
                                    <p class="text-[9px] text-gray-400 dark:text-gray-500 text-center">Se abre en una nueva pestaña ↗</p>
                                </div>
                            </div>
                        </div>
                        @endcan

                        {{-- Botón: Google Drive --}}
                        @php 
                            $isTeamLinked = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
                        @endphp
                        @if($isTeamLinked)
                            <button type="button" @click="$dispatch('open-drive-picker', { id: {{ $activity->id }}, type: 'App\\Models\\Activity' })"
                                class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold
                                       bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400
                                       border border-blue-200 dark:border-blue-500/20
                                       hover:bg-blue-600 hover:text-white hover:border-blue-600
                                       dark:hover:bg-blue-500 dark:hover:text-white dark:hover:border-blue-500
                                       shadow-sm hover:shadow-blue-500/25 hover:shadow-md
                                       transition-all duration-200 active:scale-95">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 48 48">
                                    <path fill="currentColor" opacity=".8" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                    <path fill="currentColor" opacity=".5" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                    <path fill="currentColor" d="M15 6l9 16 9-16H15z"/>
                                </svg>
                                Google Drive
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            </button>
                        @else
                            <a href="{{ route('profile.edit', ['tab' => 'integrations']) }}"
                                class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold
                                       bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-500
                                       border border-gray-200 dark:border-gray-700
                                       hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300
                                       transition-all duration-200 active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101M10.172 13.828a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.102-1.101"/>
                                </svg>
                                Vincular Drive
                            </a>
                        @endif
                    </div>
                </div>



                @if ($allAttachments->isEmpty())
                    <p class="text-xs text-gray-400 italic">{{ __('activities.no_attachments') }}</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach ($allAttachments as $attachment)
                            @php 
                                $isFromMe = $attachment->user_id === auth()->id();
                                $isTaskType = $attachment->attachable_type === 'App\Models\Activity';
                                $isFromParent = $isTaskType && $attachment->attachable_id === $activity->parent_id;
                                $isFromChild = $isTaskType && $attachment->attachable_id !== $activity->id && $attachment->attachable_id !== $activity->parent_id;
                            @endphp
                            <div
                                class="group flex items-center justify-between p-3 {{ $isFromParent ? 'bg-violet-50/30 dark:bg-violet-900/10 border-violet-100/50' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-700/50' }} border rounded-xl hover:border-violet-200 dark:hover:border-violet-800 transition-all">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="w-10 h-10 rounded-lg flex items-center justify-center shadow-sm border shrink-0 {{ $attachment->storage_provider === 'google' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800' : ($isFromParent ? 'bg-violet-50 dark:bg-gray-800 text-violet-500 border-gray-100 dark:border-gray-700' : 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 border-gray-100 dark:border-gray-700') }}">
                                        @if(!$attachment->exists)
                                            <div class="text-red-500/50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </div>
                                        @elseif($attachment->storage_provider === 'google')
                                            <svg class="w-6 h-6" viewBox="0 0 48 48">
                                                <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                                <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                                <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                            </svg>
                                        @elseif(str_starts_with($attachment->mime_type ?? '', 'image/'))
                                            <img src="{{ route('teams.activities.attachments.view', [$team, $activity, $attachment]) }}" alt="Preview" class="w-full h-full object-cover rounded-lg" />
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[12px] font-bold text-gray-800 dark:text-white truncate"
                                            title="{{ $attachment->file_name }}">
                                            @if(!$attachment->exists)
                                                <span class="text-gray-400 line-through decoration-red-500/30">{{ $attachment->file_name }}</span>
                                            @elseif($attachment->storage_provider === 'google' && $attachment->web_view_link)
                                                <a href="{{ $attachment->web_view_link }}" 
                                                   target="_blank" 
                                                   class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors flex items-center gap-1">
                                                    {{ $attachment->file_name }}
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                </a>
                                            @else
                                                <a href="{{ route('teams.activities.attachments.download', [$team, $activity, $attachment]) }}" 
                                                   target="_blank" 
                                                   class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                                    {{ $attachment->file_name }}
                                                </a>
                                            @endif
                                        </p>
                                        <p class="text-[10px] text-gray-400 flex items-center gap-1.5">
                                            @if(!$attachment->exists)
                                                <span class="text-red-500/70 font-bold uppercase tracking-tighter">{{ __('Archivo Purgado') }}</span>
                                            @elseif($attachment->storage_provider === 'google')
                                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-1.5 rounded font-black uppercase text-[8px]">Google Drive</span>
                                            @else
                                                {{ number_format($attachment->file_size / 1024 / 1024, 2) }} MB
                                            @endif
                                            •
                                            @if($isFromParent) 
                                                <span class="text-violet-500 font-bold uppercase tracking-tighter">{{ __('activities.shared') ?? 'Plan' }}</span>
                                            @elseif($isFromChild)
                                                <span class="text-amber-500 font-bold uppercase tracking-tighter">{{ $attachment->attachable->assignedUser?->name ?? 'Equipo' }}</span>
                                            @else
                                                {{ $attachment->created_at->diffForHumans() }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-0.5 opacity-60 group-hover:opacity-100 transition-all duration-200">
                                    @if($attachment->storage_provider === 'local' && auth()->user()->google_token)
                                        <form action="{{ route('teams.activities.attachments.to-drive', [$team, $activity, $attachment]) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                class="p-1.5 text-gray-500 hover:text-blue-600 transition-colors"
                                                title="Subir a Google Drive">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    {{-- Botón de Historial --}}
                                    <button type="button" 
                                        onclick="showAttachmentHistory({{ $attachment->id }})"
                                        class="p-1.5 text-amber-500 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition-colors"
                                        title="{{ __('activities.history') ?? 'Ver histórico' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>

                                    {{-- Botón de Inyección IA --}}
                                    <button type="button" 
                                        @click="$dispatch('ai:analyze-file', { 
                                            fileName: '{{ addslashes($attachment->file_name) }}', 
                                            fileId: {{ $attachment->id }},
                                            fileUrl: '{{ $attachment->storage_provider === 'google' ? $attachment->web_view_link : route('teams.activities.attachments.view', [$team, $activity, $attachment]) }}',
                                            fileType: '{{ $attachment->mime_type }}',
                                            taskId: {{ $activity->id }},
                                            teamId: {{ $team->id }},
                                            autoSubmit: false 
                                        })"
                                        class="p-1.5 text-violet-500 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300 transition-colors"
                                        title="Preguntar a la IA sobre este archivo">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </button>

                                    @if($attachment->is_office_compatible)
                                        <a href="{{ route('onlyoffice.activity.edit', $attachment) }}"
                                            target="_blank" rel="noopener noreferrer"
                                            onclick="sessionStorage.setItem('needs_office_reload', '1')"
                                            class="p-1.5 text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 transition-colors"
                                            title="{{ __('Editar con Office') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    @endif

                                    <a href="{{ route('teams.activities.attachments.download', [$team, $activity, $attachment]) }}"
                                        target="_blank" rel="noopener noreferrer"
                                        class="p-1.5 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
                                        title="{{ __('activities.view_or_download') ?? 'Ver o descargar' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                    @can('delete', $attachment)
                                        @if($attachment->storage_provider === 'local' && str_starts_with($attachment->mime_type, 'image/'))
                                            <button type="button"
                                                onclick="editAttachmentImage({{ $attachment->id }}, '{{ route('teams.activities.attachments.view', [$team, $activity, $attachment]) }}')"
                                                class="p-1.5 text-gray-500 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-lg transition-all"
                                                title="{{ __('Editar Imagen') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
</svg>
                                            </button>
                                        @endif
                                        <button type="button"
                                            onclick="renameAttachment({{ $attachment->id }}, '{{ addslashes($attachment->file_name) }}')"
                                            class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all"
                                            title="{{ __('activities.edit') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <form
                                            action="{{ route('teams.activities.attachments.destroy', [$team, $activity, $attachment]) }}"
                                            method="POST" class="inline"
                                            id="delete-attachment-{{ $attachment->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                onclick="confirmAttachmentDelete({{ $attachment->id }}, '{{ $attachment->storage_provider }}')"
                                                class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                                                title="{{ __('activities.delete') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        @if(!$isTaskType || $attachment->attachable_id !== $activity->id)
                                            {{-- Informativo para compartidos si el usuario normal no tiene permisos --}}
                                            <span class="p-1.5 text-gray-300 dark:text-gray-600 cursor-help"
                                                title="{{ $isFromParent ? 'Este archivo es del Plan Maestro y debe eliminarse desde allí.' : 'Este archivo pertenece a una subtarea.' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-40"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </span>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
