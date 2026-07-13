<x-app-layout>
    @section('title', 'Editar ' . $activity->type_label . ': ' . $activity->title)

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('teams.activities.show', [$team, $activity]) }}"
                class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                @include('teams.partials.breadcrumb')
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white heading truncate">
                        Editar Actividad: {{ $activity->title }} 
                        <span class="ml-2 text-sm font-medium text-gray-500 bg-gray-100 dark:bg-gray-800 dark:text-gray-400 px-2 py-1 rounded-md">
                            {{ $activity->type_label }}
                        </span>
                    </h1>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="w-full sm:px-6 lg:px-8 py-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all duration-300">
            <form id="edit-activity-form" method="POST" action="{{ route('teams.activities.update', [$team, $activity]) }}" class="space-y-6" enctype="multipart/form-data">
                @csrf
                @method('PATCH')
                <input type="hidden" name="type" value="{{ $activity->type }}">

                
    <!-- BLOCK: Título de la Actividad -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Título de la Actividad
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Identificación principal</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            <div>
                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">Título de la Actividad</label>
                    <input type="text" name="title" value="{{ old('title', $activity->title) }}" required
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-2xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 outline-none transition-all"
                        placeholder="Ej. Redactar el acta de inicio...">
                    @error('title')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
        </div>
    </div>

    <!-- BLOCK: Descripción -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Descripción
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Detalles y contexto de la actividad</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            <div>
                    <x-markdown-editor 
                        name="description" 
                        id="description"
                        :value="old('description', $activity->description)"
                        :label="__('Descripción o Contenido')"
                        rows="4"
                        :upload-url="route('teams.forum.upload_image', $team)"
                        :mentions-url="route('teams.mentions', $team)"
                    />
                </div>

                @if($activity->type === "task")
        </div>
    </div>

    <!-- BLOCK: Información Específica -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Información Específica
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Campos dinámicos según el tipo de actividad</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            <div class="flex items-center gap-2 border-b border-gray-200/50 dark:border-gray-800 pb-3">
                        <div class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center font-bold">
                            ✨
                        </div>
                        
                    </div>

                    @if ($activity->type === 'document')
                        <!-- DOCUMENTO ESPECÍFICO -->
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                Esta actividad es un documento colaborativo accesible mediante OnlyOffice.
                            </p>
                            <div class="mt-4">
                                <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Versión Actual</label>
                                <input type="text" name="version" value="{{ old('version', data_get($activity->metadata, 'version', '1.0.0')) }}" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2 text-sm text-gray-900 dark:text-white outline-none">
                            </div>
                        </div>
                    @elseif ($activity->type === 'link')
                        <!-- ENLACE ESPECÍFICO -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Dirección URL (Enlace)</label>
                            <input type="url" name="url" value="{{ old('url', data_get($activity->metadata, 'url')) }}" required class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none" placeholder="https://example.com/recurso">
                            @error('url')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    @elseif ($activity->type === 'decision')
                        <!-- DECISIÓN ESPECÍFICO -->
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                Decisión registrada en el historial. Modifica la descripción para documentar cualquier cambio en el acuerdo.
                            </p>
                        </div>
                    @elseif ($activity->type === 'meeting')
                        <!-- REUNIÓN ESPECÍFICO -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Modalidad</label>
                                <select name="modality" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                                    <option value="remote" {{ old('modality', data_get($activity->metadata, 'modality')) == 'remote' ? 'selected' : '' }}>💻 En remoto / Online</option>
                                    <option value="presential" {{ old('modality', data_get($activity->metadata, 'modality', 'presential')) == 'presential' ? 'selected' : '' }}>🏢 Presencial</option>
                                    <option value="hybrid" {{ old('modality', data_get($activity->metadata, 'modality')) == 'hybrid' ? 'selected' : '' }}>🤝 Híbrido</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Duración (Minutos)</label>
                                <input type="number" name="duration_minutes" value="{{ old('duration_minutes', data_get($activity->metadata, 'duration_minutes', 60)) }}" min="1" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                            </div>

                            <div class="md:col-span-2" x-data="{
                                link: '{{ old('location', data_get($activity->metadata, 'location')) }}',
                                generateJitsi() {
                                    this.link = 'https://meet.jit.si/SientiaMTX-' + Math.random().toString(36).substring(2, 12);
                                },
                                async generateMeet() {
                                    Swal.fire({
                                        title: '🌐 Creando sala Meet...',
                                        text: 'Conectando con Google Meet',
                                        allowOutsideClick: false,
                                        showConfirmButton: false,
                                        didOpen: () => Swal.showLoading(),
                                    });

                                    try {
                                        let response = await fetch('{{ route('meet.generate') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                'Accept': 'application/json',
                                            },
                                            body: JSON.stringify({ team_id: {{ $team->id ?? 'null' }} })
                                        });
                                        let data = await response.json();
                                        if (data.success && data.meet_url) {
                                            this.link = data.meet_url;
                                            Swal.close();
                                        } else {
                                            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo iniciar la llamada.', toast: true, position: 'top-end', timer: 4000, showConfirmButton: false });
                                        }
                                    } catch (err) {
                                        Swal.fire({ icon: 'error', title: 'Error de red', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                                    }
                                }
                            }">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Lugar / Enlace Videollamada</label>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="generateJitsi()" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1.5 border border-emerald-100 dark:border-emerald-800/50">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                                            Generar Jitsi
                                        </button>
                                        <button type="button" @click="generateMeet()" class="text-[10px] font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1.5 border border-blue-100 dark:border-blue-800/50">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 48 48">
                                                <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                                <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                                <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                            </svg>
                                            Generar Meet
                                        </button>
                                    </div>
                                </div>
                                <input type="text" name="location" x-model="link" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none" placeholder="Ej. Sala de juntas principal o Enlace de Google Meet/Teams">
                            </div>
                        </div>
                    @elseif ($activity->type === 'reminder')
                        <!-- RECORDATORIO ESPECÍFICO -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Canales de Notificación</label>
                                @php $channels = data_get($activity->metadata, 'channels', ['email']); @endphp
                                <div class="flex flex-wrap gap-4 mt-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="channels[]" value="email" {{ in_array('email', $channels) ? 'checked' : '' }} class="accent-violet-600 rounded">
                                        <span class="text-sm text-gray-700 dark:text-gray-300"> Correo Electrónico</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="channels[]" value="push" {{ in_array('push', $channels) ? 'checked' : '' }} class="accent-violet-600 rounded">
                                        <span class="text-sm text-gray-700 dark:text-gray-300"> Notificación en la App (Push/Nudge)</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="channels[]" value="whatsapp" {{ in_array('whatsapp', $channels) ? 'checked' : '' }} class="accent-violet-600 rounded">
                                        <span class="text-sm text-gray-700 dark:text-gray-300"> WhatsApp</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="channels[]" value="telegram" {{ in_array('telegram', $channels) ? 'checked' : '' }} class="accent-violet-600 rounded">
                                        <span class="text-sm text-gray-700 dark:text-gray-300"> Telegram</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endif
        </div>
    </div>

    <!-- BLOCK: Observaciones -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Observaciones
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Notas internas o apuntes adicionales</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            <div>
                    <x-markdown-editor 
                        name="metadata[observations]" 
                        id="observations"
                        :value="old('metadata.observations', data_get($activity->metadata, 'observations'))"
                        :label="__('tasks.observations')"
                        rows="4"
                        :upload-url="route('teams.forum.upload_image', $team)"
                        :mentions-url="route('teams.mentions', $team)"
                    />
                </div>
                @endif
        </div>
    </div>

    <!-- BLOCK: Miembros Asignados -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Miembros Asignados
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Responsables y colaboradores</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            @php
                    $assignedUserIds = $activity->assignedTo->pluck('id')->toArray();
                    $assignedGroupIds = $activity->assignedGroups->pluck('id')->toArray();
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4 border-t border-gray-100 dark:border-gray-800" x-data="{
                    selectAll(status) {
                        document.querySelectorAll('.user-checkbox').forEach(cb => {
                            cb.checked = status;
                            cb.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    },
                    syncGroup(groupCb) {
                        try {
                            const memberIds = JSON.parse(groupCb.dataset.members);
                            const isChecked = groupCb.checked;
                            memberIds.forEach(id => {
                                const userCb = document.getElementById('user_checkbox_' + id);
                                if (userCb) {
                                    userCb.checked = isChecked;
                                    userCb.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            });
                        } catch (err) {
                            console.error('Group sync error:', err);
                        }
                    }
                }">
                    @if ($members->count() > 0)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    Miembros Asignados
                                </label>
                                <div class="flex gap-2">
                                    <button type="button" @click="selectAll(true)" class="text-[10px] font-black uppercase tracking-widest text-violet-600 hover:text-violet-700 dark:text-violet-400 transition-colors">
                                        Todos
                                    </button>
                                    <span class="text-gray-300 dark:text-gray-700 text-[10px]">|</span>
                                    <button type="button" @click="selectAll(false)" class="text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-gray-700 dark:text-gray-400 transition-colors">
                                        Ninguno
                                    </button>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 space-y-2.5 max-h-80 overflow-y-auto">
                                @foreach ($members as $member)
                                    <label class="flex items-center gap-3 p-2 rounded-xl hover:bg-white dark:hover:bg-gray-800 cursor-pointer group transition-all border border-transparent hover:border-gray-150 shadow-sm">
                                        <input type="checkbox" name="assigned_to[]" value="{{ $member->id }}"
                                            id="user_checkbox_{{ $member->id }}"
                                            {{ in_array($member->id, old('assigned_to', $assignedUserIds)) ? 'checked' : '' }}
                                            class="user-checkbox accent-violet-600 w-5 h-5 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-violet-500/20">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200 truncate group-hover:text-gray-900 transition-colors leading-tight">{{ $member->name }}</span>
                                            <span class="text-[10px] text-gray-500 truncate">{{ $member->email }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($groups->count() > 0)
                        <div class="space-y-3">
                            <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                Grupos Asignados
                            </label>
                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 space-y-2.5 max-h-80 overflow-y-auto">
                                @foreach ($groups as $group)
                                    <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-white dark:hover:bg-gray-800 cursor-pointer group transition-all border border-transparent hover:border-gray-150 shadow-sm">
                                        <input type="checkbox" name="assigned_groups[]" value="{{ $group->id }}"
                                            data-members="{{ json_encode($group->users->pluck('id')) }}"
                                            @change="syncGroup($el)"
                                            {{ in_array($group->id, old('assigned_groups', $assignedGroupIds)) ? 'checked' : '' }}
                                            class="group-checkbox accent-violet-600 w-5 h-5 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-violet-500/20">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200 truncate group-hover:text-gray-900 transition-colors leading-tight">{{ $group->name }}</span>
                                            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">{{ $group->users->count() }} Miembros</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
        </div>
    </div>

    <!-- BLOCK: Nivel de Visibilidad -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Nivel de Visibilidad
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Privacidad y acceso</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/30 dark:bg-gray-800/10 p-6 rounded-3xl border border-gray-150 dark:border-gray-800">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">{{ __('activities.visibility') ?? 'Nivel de Privacidad' }}</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative flex cursor-pointer">
                                <input type="radio" name="visibility" value="public" class="peer sr-only" {{ old('visibility', $activity->visibility) === 'public' ? 'checked' : '' }}>
                                <div class="w-full p-3 bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-950/30 transition-all flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-900 flex items-center justify-center text-violet-600 shadow-sm border border-gray-100 dark:border-gray-800">
                                        👥
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ __('activities.public') ?? 'Pública' }}</span>
                                        <span class="text-[10px] text-gray-500">Todo el equipo</span>
                                    </div>
                                </div>
                            </label>
                            <label class="relative flex cursor-pointer">
                                <input type="radio" name="visibility" value="private" class="peer sr-only" {{ old('visibility', $activity->visibility) === 'private' ? 'checked' : '' }}>
                                <div class="w-full p-3 bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-950/30 transition-all flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-900 flex items-center justify-center text-amber-600 shadow-sm border border-gray-100 dark:border-gray-800">
                                        🔒
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ __('activities.private') ?? 'Privada' }}</span>
                                        <span class="text-[10px] text-gray-500">Solo yo</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex flex-col justify-center">
                        <label class="relative flex items-center gap-3 cursor-pointer group w-full bg-violet-50/50 dark:bg-violet-900/10 border border-violet-100/50 dark:border-violet-800/50 rounded-2xl p-4 transition-all">
                            <input type="hidden" name="metadata[is_ephemeral]" value="0">
                            <input type="checkbox" name="metadata[is_ephemeral]" value="1" {{ old('metadata.is_ephemeral', data_get($activity->metadata, 'is_ephemeral', false)) ? 'checked' : '' }} class="accent-violet-600 rounded w-5 h-5 border-gray-300 dark:border-gray-600 focus:ring-violet-500/20">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Actividad Efímera (Ocultar)</span>
                                <span class="text-[11px] text-gray-500">No aparecerá en el Kanban ni en Gantt.</span>
                            </div>
                        </label>
                    </div>
                </div>
        </div>
    </div>

    <!-- BLOCK: Prioridad, Urgencia y Estado -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Prioridad, Urgencia y Estado
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Matriz Eisenhower y situación actual</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-{{ $activity->type === 'task' ? '3' : '2' }} gap-6 bg-gray-50/30 dark:bg-gray-800/10 p-6 rounded-3xl border border-gray-150 dark:border-gray-800">

                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">{{ __('activities.priority') ?? 'Prioridad' }}</label>
                        <select name="priority" id="priority_select" required class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                            <option value="low" {{ old('priority', $activity->priority) == 'low' ? 'selected' : '' }}>{{ __('activities.priorities.low') ?? 'Baja' }}</option>
                            <option value="medium" {{ old('priority', $activity->priority) == 'medium' ? 'selected' : '' }}>{{ __('activities.priorities.medium') ?? 'Media' }}</option>
                            <option value="high" {{ old('priority', $activity->priority) == 'high' ? 'selected' : '' }}>{{ __('activities.priorities.high') ?? 'Alta' }}</option>
                            <option value="critical" {{ old('priority', $activity->priority) == 'critical' ? 'selected' : '' }}>{{ __('activities.priorities.critical') ?? 'Crítica' }}</option>
                        </select>
                    </div>


                    @if ($activity->type === 'task')
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">{{ __('activities.urgency') ?? 'Urgencia' }}</label>
                        <select name="urgency" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                            <option value="low" {{ old('urgency', data_get($activity->metadata, 'urgency', 'medium')) == 'low' ? 'selected' : '' }}>{{ __('activities.urgencies.low') ?? 'Baja' }}</option>
                            <option value="medium" {{ old('urgency', data_get($activity->metadata, 'urgency', 'medium')) == 'medium' ? 'selected' : '' }}>{{ __('activities.urgencies.medium') ?? 'Media' }}</option>
                            <option value="high" {{ old('urgency', data_get($activity->metadata, 'urgency', 'medium')) == 'high' ? 'selected' : '' }}>{{ __('activities.urgencies.high') ?? 'Alta' }}</option>
                            <option value="critical" {{ old('urgency', data_get($activity->metadata, 'urgency', 'medium')) == 'critical' ? 'selected' : '' }}>{{ __('activities.urgencies.critical') ?? 'Crítica' }}</option>
                        </select>
                    </div>
                    @endif


                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">{{ __('activities.status') ?? 'Estado' }}</label>
                        <select name="status" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                            @foreach ($statuses as $val => $label)
                                <option value="{{ $val }}" {{ old('status', $activity->status_value) === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
@if($activity->type === 'task')
                    <!-- Eisenhower Matrix Preview -->
                    <div id="quadrant-preview" class="rounded-xl border p-3 text-xs hidden transition-all">
                        <span class="font-bold uppercase tracking-wider" id="qp-label"></span>
                        <span class="text-gray-500 dark:text-gray-400 ml-1 italic font-medium" id="qp-desc"></span>
                    </div>
                @endif
<div class='my-8 border-t border-gray-100 dark:border-gray-800'></div>
<div class="bg-gray-50/30 dark:bg-gray-800/10 p-6 rounded-3xl border border-gray-150 dark:border-gray-800">
                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">Porcentaje de Progreso</label>
                    <div x-data="{ progress: {{ old('progress_percentage', $activity->progress_percentage ?? 0) }} }" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <input type="range" name="progress_percentage" min="0" max="100" step="5" x-model="progress"
                                class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-violet-600">
                            <span class="text-sm font-bold text-violet-600 dark:text-violet-400 w-12 text-right select-none ml-3" x-text="progress + '%'">0%</span>
                        </div>
                        @error('progress_percentage')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
        </div>
    </div>

    <!-- BLOCK: Fechas y Bloqueo -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Fechas y Bloqueo
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Planificación temporal e inamovilidad</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/30 dark:bg-gray-800/10 p-6 rounded-3xl border border-gray-150 dark:border-gray-800">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">{{ __('activities.scheduled_date') ?? 'Fecha Programada' }}</label>
                        <input type="datetime-local" name="scheduled_date" value="{{ old('scheduled_date', $activity->scheduled_date ? $activity->scheduled_date->format('Y-m-d\TH:i') : '') }}"
                            class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">{{ __('activities.due_date') ?? 'Fecha de Vencimiento' }}</label>
                        <input type="datetime-local" name="due_date" value="{{ old('due_date', $activity->due_date ? $activity->due_date->format('Y-m-d\TH:i') : '') }}"
                            class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none">
                    </div>

                    <!-- Timeline Lock -->
                    <div class="md:col-span-2 bg-white dark:bg-gray-800/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                                🔒
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">Bloquear programación (Inamovible)</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">Evita que la actividad sea desplazada o redimensionada en el Gantt de forma accidental.</span>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_timeline_locked" value="1" {{ old('is_timeline_locked', $activity->is_timeline_locked ?? false) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-focus:ring-4 peer-focus:ring-red-500/20 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                        </label>
                    </div>
                </div>
        </div>
    </div>

    <!-- BLOCK: Autoprogramación -->
    <div id="recurrence-block" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Autoprogramación
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Recurrencia y automatización</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            @php
                            $hasAutoprogram = data_get($activity->metadata, 'autoprogram_settings') !== null;
                            $autoSettings = data_get($activity->metadata, 'autoprogram_settings', []);
                        @endphp
                        <div x-data="{ 
                            isAutoprogrammable: {{ old('is_autoprogrammable', $hasAutoprogram ? 1 : 0) ? 'true' : 'false' }},
                            frequency: '{{ old('autoprogram_settings.frequency', $autoSettings['frequency'] ?? 'daily') }}',
                            monthlyType: '{{ old('autoprogram_settings.monthly_type', $autoSettings['monthly_type'] ?? 'date') }}',
                            labels: {
                                'daily': 'días',
                                'weekly': 'semanas',
                                'monthly': 'meses',
                                'yearly': 'años'
                            }
                        }" class="bg-violet-50/30 dark:bg-gray-900/40 backdrop-blur-md border border-violet-100 dark:border-violet-500/20 rounded-2xl p-6 shadow-sm transition-all mt-6">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">Autoprogramable (Recurrencia)</span>
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400">Permite que esta actividad se duplique automáticamente según el patrón definido.</span>
                                    </div>
                                </div>
                                
                                <div class="flex p-1 bg-gray-200 dark:bg-gray-950/50 rounded-xl w-fit self-start sm:self-center border border-transparent dark:border-gray-800">
                                    <button type="button" @click="isAutoprogrammable = false" 
                                        :class="!isAutoprogrammable ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-200">
                                        Desactivado
                                    </button>
                                    <button type="button" @click="isAutoprogrammable = true" 
                                        :class="isAutoprogrammable ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/20' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-200">
                                        Activo
                                    </button>
                                </div>
                                <input type="hidden" name="is_autoprogrammable" :value="isAutoprogrammable ? 1 : 0">
                            </div>

                            <div x-show="isAutoprogrammable" x-transition class="space-y-6 pt-6 border-t border-violet-100/50 dark:border-violet-500/10">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                            Frecuencia
                                        </label>
                                        <select name="autoprogram_settings[frequency]" x-model="frequency" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                                            <option value="daily">Diaria</option>
                                            <option value="weekly">Semanal</option>
                                            <option value="monthly">Mensual</option>
                                            <option value="yearly">Anual</option>
                                        </select>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                            Repetir cada
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <input type="number" name="autoprogram_settings[interval]" value="{{ old('autoprogram_settings.interval', $autoSettings['interval'] ?? 1) }}" min="1" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                                            <span class="text-xs font-medium text-gray-500 w-16" x-text="labels[frequency]">días</span>
                                        </div>
                                    </div>

                                    <div x-show="frequency === 'weekly'" class="col-span-2 space-y-3 pb-2" x-transition>
                                        <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                            Días de la semana
                                        </label>
                                        <div class="flex flex-wrap gap-2">
                                            @php $selectedDays = $autoSettings['days'] ?? []; @endphp
                                            @foreach(['1' => 'L', '2' => 'M', '3' => 'X', '4' => 'J', '5' => 'V', '6' => 'S', '7' => 'D'] as $val => $label)
                                                <label class="relative cursor-pointer">
                                                    <input type="checkbox" name="autoprogram_settings[days][]" value="{{ $val }}" 
                                                        {{ in_array($val, old('autoprogram_settings.days', $selectedDays)) ? 'checked' : '' }}
                                                        class="peer sr-only">
                                                    <div class="w-9 h-9 rounded-xl border-2 border-gray-100 dark:border-gray-800 flex items-center justify-center text-xs font-black text-gray-400 peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-900/30 peer-checked:text-violet-600 transition-all hover:border-violet-200 shadow-sm">
                                                        {{ $label }}
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div x-show="frequency === 'monthly'" class="col-span-2 space-y-3 pb-2" x-transition>
                                        <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                            Patrón Mensual
                                        </label>
                                        <div class="flex flex-col sm:flex-row gap-4">
                                            <label class="relative flex items-center gap-3 cursor-pointer group">
                                                <input type="radio" name="autoprogram_settings[monthly_type]" value="date" x-model="monthlyType" class="peer sr-only">
                                                <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 flex items-center justify-center transition-all">
                                                    <div class="w-2 h-2 rounded-full bg-violet-500 hidden peer-checked:block"></div>
                                                </div>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">El mismo día del mes</span>
                                            </label>
                                            <label class="relative flex items-center gap-3 cursor-pointer group">
                                                <input type="radio" name="autoprogram_settings[monthly_type]" value="ordinal" x-model="monthlyType" class="peer sr-only">
                                                <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 flex items-center justify-center transition-all">
                                                    <div class="w-2 h-2 rounded-full bg-violet-500 hidden peer-checked:block"></div>
                                                </div>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Un día específico de la semana</span>
                                            </label>
                                        </div>
                                        
                                        <div x-show="monthlyType === 'ordinal'" class="flex items-center gap-2 mt-3" x-transition>
                                            <span class="text-sm text-gray-500">El</span>
                                            <select name="autoprogram_settings[monthly_ordinal]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-1.5 text-sm text-gray-900 dark:text-white outline-none">
                                                <option value="first" {{ ($autoSettings['monthly_ordinal'] ?? '') === 'first' ? 'selected' : '' }}>Primer</option>
                                                <option value="second" {{ ($autoSettings['monthly_ordinal'] ?? '') === 'second' ? 'selected' : '' }}>Segundo</option>
                                                <option value="third" {{ ($autoSettings['monthly_ordinal'] ?? '') === 'third' ? 'selected' : '' }}>Tercer</option>
                                                <option value="fourth" {{ ($autoSettings['monthly_ordinal'] ?? '') === 'fourth' ? 'selected' : '' }}>Cuarto</option>
                                                <option value="last" {{ ($autoSettings['monthly_ordinal'] ?? '') === 'last' ? 'selected' : '' }}>Último</option>
                                            </select>
                                            <select name="autoprogram_settings[monthly_day]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-1.5 text-sm text-gray-900 dark:text-white outline-none">
                                                <option value="monday" {{ ($autoSettings['monthly_day'] ?? '') === 'monday' ? 'selected' : '' }}>Lunes</option>
                                                <option value="tuesday" {{ ($autoSettings['monthly_day'] ?? '') === 'tuesday' ? 'selected' : '' }}>Martes</option>
                                                <option value="wednesday" {{ ($autoSettings['monthly_day'] ?? '') === 'wednesday' ? 'selected' : '' }}>Miércoles</option>
                                                <option value="thursday" {{ ($autoSettings['monthly_day'] ?? '') === 'thursday' ? 'selected' : '' }}>Jueves</option>
                                                <option value="friday" {{ ($autoSettings['monthly_day'] ?? '') === 'friday' ? 'selected' : '' }}>Viernes</option>
                                                <option value="saturday" {{ ($autoSettings['monthly_day'] ?? '') === 'saturday' ? 'selected' : '' }}>Sábado</option>
                                                <option value="sunday" {{ ($autoSettings['monthly_day'] ?? '') === 'sunday' ? 'selected' : '' }}>Domingo</option>
                                            </select>
                                            <span class="text-sm text-gray-500">del mes</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                        Antelación de creación (despertar)
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <input type="number" name="autoprogram_settings[lead_value]" value="{{ old('autoprogram_settings.lead_value', $autoSettings['lead_value'] ?? 7) }}" min="1" class="w-24 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                                        <select name="autoprogram_settings[lead_unit]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                                            <option value="hours" {{ ($autoSettings['lead_unit'] ?? '') === 'hours' ? 'selected' : '' }}>Horas</option>
                                            <option value="days" {{ ($autoSettings['lead_unit'] ?? 'days') === 'days' ? 'selected' : '' }}>Días</option>
                                            <option value="weeks" {{ ($autoSettings['lead_unit'] ?? '') === 'weeks' ? 'selected' : '' }}>Semanas</option>
                                            <option value="months" {{ ($autoSettings['lead_unit'] ?? '') === 'months' ? 'selected' : '' }}>Meses</option>
                                        </select>
                                        <span class="text-[10px] text-gray-400 italic">antes de la fecha señalada</span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                        Terminar
                                    </label>
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-8">
                                        <label class="relative flex items-center gap-3 cursor-pointer group">
                                            <input type="radio" name="autoprogram_settings[limit_type]" value="count" {{ ($autoSettings['limit_type'] ?? 'count') === 'count' ? 'checked' : '' }} class="peer sr-only">
                                            <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 flex items-center justify-center transition-all">
                                                <div class="w-2 h-2 rounded-full bg-violet-500 hidden peer-checked:block"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Después de</span>
                                            <input type="number" name="autoprogram_settings[limit_value_count]" value="{{ old('autoprogram_settings.limit_value_count', $autoSettings['limit_value_count'] ?? 5) }}" min="1" class="w-16 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-lg px-2 py-1 text-xs text-gray-900 dark:text-white outline-none">
                                            <span class="text-xs text-gray-500">veces</span>
                                        </label>
                                        <label class="relative flex items-center gap-3 cursor-pointer group">
                                            <input type="radio" name="autoprogram_settings[limit_type]" value="date" {{ ($autoSettings['limit_type'] ?? '') === 'date' ? 'checked' : '' }} class="peer sr-only">
                                            <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 flex items-center justify-center transition-all">
                                                <div class="w-2 h-2 rounded-full bg-violet-500 hidden peer-checked:block"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">El día</span>
                                            <input type="date" name="autoprogram_settings[limit_value_date]" value="{{ old('autoprogram_settings.limit_value_date', $autoSettings['limit_value_date'] ?? '') }}" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-lg px-2 py-1 text-xs text-gray-900 dark:text-white outline-none">
                                        </label>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-4 pt-2">
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="autoprogram_settings[skip_weekends]" value="1" {{ ($autoSettings['skip_weekends'] ?? false) ? 'checked' : '' }} class="accent-violet-600 rounded">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Saltar fines de semana</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="autoprogram_settings[sequential]" value="1" {{ ($autoSettings['sequential'] ?? false) ? 'checked' : '' }} class="accent-violet-600 rounded">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Dependencias secuenciales (Gantt)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
        </div>
    </div>

    <!-- BLOCK: Contexto y Vinculaciones -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Contexto y Vinculaciones
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Relaciones con expedientes y servicios</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            <div class="flex items-center gap-3 mb-1">
                        <div class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-200 dark:border-violet-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <div>
                            
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">Asocia esta actividad a un expediente o dependencias.</p>
                        </div>
                    </div>

                    <!-- Expediente Vinculado -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Expediente Vinculado</label>
                        @if($activity->parent_id && !$activity->is_template)
                            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900 p-3 rounded-xl border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    @if($activity->expediente)
                                        <span class="px-2 py-1 bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-md text-[10px] font-black uppercase font-mono">
                                            {{ $activity->expediente->code }}
                                        </span>
                                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $activity->expediente->title }}</span>
                                    @else
                                        <span class="text-sm text-gray-400 italic">(Ningún expediente)</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 text-[10px] font-bold text-gray-400 bg-white dark:bg-gray-800 px-2 py-1 rounded-lg shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    Heredado del Maestro
                                </div>
                            </div>
                            <!-- Hidden input to preserve existing value on submission -->
                            <input type="hidden" name="expediente_id" value="{{ $activity->expediente_id }}">
                        @else
                            <select name="expediente_id" id="expediente_id_select" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white transition-all cursor-pointer font-medium">
                                <option value="">(Ningún expediente)</option>
                                @foreach ($expedientes as $exp)
                                    <option value="{{ $exp->id }}" data-code="{{ $exp->code }}" {{ old('expediente_id', $activity->expediente_id) == $exp->id ? 'selected' : '' }}>
                                        {{ $exp->code }} — {{ $exp->title }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- Secondary Grid: Actividad Padre y Dependencia de Servicio -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <!-- Actividad Padre -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-2">Actividad Padre (Dependencia)</label>
                            <select name="parent_id" id="parent_id_select" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer font-medium">
                                <option value="">(Ninguna)</option>
                                @foreach ($parentActivities as $parent)
                                    <option value="{{ $parent->id }}" {{ old('parent_id', $activity->parent_id) == $parent->id ? 'selected' : '' }}
                                        data-assignee="{{ $parent->creator ? $parent->creator->name : 'Sin asignar' }}">
                                        {{ $parent->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Dependencia de Servicio -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-2">Dependencia de Servicio</label>
                            <select name="service_id" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer font-medium font-sans">
                                <option value="">Sin dependencia externa</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}" {{ old('service_id', data_get($activity->metadata, 'service_id')) == $service->id ? 'selected' : '' }}>
                                        {{ $service->icon }} {{ $service->name }} ({{ $service->getStatusLabel() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
        </div>
    </div>

    <!-- BLOCK: Impacto y Bienestar -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Impacto y Bienestar
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Métricas humanas y resiliencia</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div>
        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
            Impacto Social / Humano (Puntos)
        </label>
        <input type="number" name="metadata[impact_human_metric]" value="{{ old('metadata.impact_human_metric', data_get($activity->metadata, 'impact_human_metric', 0)) }}" min="0" max="100" class="w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 transition-all shadow-inner">
    </div>
    <div class="flex flex-col justify-center gap-3 pt-1">
        <label class="relative flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:border-violet-300 dark:hover:border-violet-500/50 transition-all group shadow-inner">
            <input type="checkbox" name="metadata[is_out_of_skill_tree]" value="1" {{ old('metadata.is_out_of_skill_tree', data_get($activity->metadata, 'is_out_of_skill_tree', false)) ? 'checked' : '' }} class="accent-violet-600 rounded w-5 h-5 focus:ring-violet-500/20">
            <div class="flex flex-col">
                <span class="text-sm font-bold text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Fuera de mi Skill Tree</span>
                <span class="text-[10px] text-gray-400 uppercase font-black tracking-widest mt-0.5">+ Puntos de Resiliencia</span>
            </div>
        </label>
        
        <label class="relative flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:border-emerald-300 dark:hover:border-emerald-500/50 transition-all group shadow-inner">
            <input type="checkbox" name="metadata[is_backstage]" value="1" {{ old('metadata.is_backstage', data_get($activity->metadata, 'is_backstage', false)) ? 'checked' : '' }} class="accent-emerald-600 rounded w-5 h-5 focus:ring-emerald-500/20">
            <div class="flex flex-col">
                <span class="text-sm font-bold text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Backstage / Preparación</span>
                <span class="text-[10px] text-gray-400 uppercase font-black tracking-widest mt-0.5">Visibiliza el esfuerzo invisible</span>
            </div>
        </label>
    </div>
</div>

        </div>
    </div>

    <!-- BLOCK: Capacidades y Carga Cognitiva -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Capacidades y Carga Cognitiva
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Esfuerzo mental y skills requeridas</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div x-data="{ selectedSkills: {{ json_encode(old('skills', $activity->skills->pluck('id')->toArray())) }} }">
        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
            Árbol de Capacidades (Selección Múltiple)
        </label>
        <select name="skills[]" multiple class="w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 outline-none transition-all text-gray-900 dark:text-white h-64 resize-y shadow-inner">
            @foreach($skills as $skill)
                <option value="{{ $skill->id }}" :selected="selectedSkills.includes({{ $skill->id }})">
                    {{ $skill->name }} ({{ $skill->category }})
                </option>
            @endforeach
        </select>
        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-2 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Mantén presionado Ctrl (o Cmd) para seleccionar varias habilidades
        </p>
    </div>

    <div x-data="{ load: {{ old('metadata.cognitive_load', data_get($activity->metadata, 'cognitive_load', 1)) }} }">
        <label class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-4 flex items-center justify-between">
            <span>Carga Cognitiva (Drenaje de Energía)</span>
            <span :class="{
                'text-emerald-500': load <= 3,
                'text-blue-500': load > 3 && load <= 6,
                'text-amber-500': load > 6 && load <= 8,
                'text-red-500': load > 8
            }" class="font-black tabular-nums transition-colors text-lg" x-text="load"></span>
        </label>
        <div class="relative pt-2">
            <input type="range" name="metadata[cognitive_load]" min="1" max="10" step="1" x-model="load"
                class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-violet-600 shadow-inner">
            <div class="flex justify-between text-[10px] text-gray-400 mt-3 font-black uppercase tracking-tighter">
                <span>Baja (1)</span>
                <span>Media (5)</span>
                <span>Extrema (10)</span>
            </div>
        </div>
    </div>
</div>

        </div>
    </div>

    <!-- BLOCK: Archivos Adjuntos -->
    <div  class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all hover:shadow-md mb-8 group relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8 opacity-5">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
        </div>
        <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-gray-800 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-inner border border-violet-100/50 dark:border-violet-500/10 group-hover:scale-110 transition-transform duration-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 dark:text-gray-200">
                    Archivos Adjuntos
                </h3>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 mt-0.5 uppercase tracking-wider">Documentos y evidencias</p>
            </div>
        </div>
        <div class="space-y-6 relative z-10">
            <div class="pt-8 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400">
                                📎
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Archivos Adjuntos</h3>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Administra los archivos actuales o añade nuevos.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @php
                                $isGoogleLinked = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
                            @endphp
                            @if($isGoogleLinked)
                                <button type="button" onclick="openDrivePicker()"
                                    class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-600 dark:text-blue-400 text-xs font-bold px-4 py-2 rounded-xl border border-blue-200 dark:border-blue-800 transition-all shadow-sm flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M7.71 3.5L1.15 15l3.43 6 6.55-11.5H7.71zM9.73 15L6.3 21h13.12l3.43-6H9.73zM18.74 3.5l-6.55 11.5 3.43 6L22.18 9.5l-3.44-6z"/>
                                    </svg>
                                    Google Drive
                                </button>
                            @endif
                            <label class="cursor-pointer bg-violet-50 dark:bg-violet-900/20 hover:bg-violet-100 dark:hover:bg-violet-900/40 text-violet-600 dark:text-violet-400 px-4 py-2 rounded-xl text-xs font-bold transition-all border border-violet-200 dark:border-violet-500/20 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                Añadir Archivo
                                <input type="file" name="attachments[]" multiple class="hidden" onchange="updateFileList(this)">
                            </label>
                        </div>
                        <input type="hidden" name="drive_attachments" id="drive_attachments_input">
                    </div>

                    <!-- Archivos existentes -->
                    @if ($activity->attachments->count() > 0)
                        <div class="mb-4">
                            <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wider">Archivos ya subidos</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($activity->attachments as $attach)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <span class="text-xl">📄</span>
                                            <div class="flex flex-col min-w-0">
                                                @if($attach->storage_provider === 'google' && $attach->web_view_link)
                                                    <a href="{{ $attach->web_view_link }}" target="_blank"
                                                       class="text-xs font-bold text-blue-600 dark:text-blue-400 truncate hover:underline transition-colors flex items-center gap-1">
                                                        {{ $attach->file_name }}
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </a>
                                                @else
                                                    <a href="{{ route('teams.activities.attachments.view', [$team, $activity, $attach]) }}" target="_blank"
                                                       class="text-xs font-bold text-gray-700 dark:text-gray-200 truncate hover:text-violet-600 transition-colors">
                                                        {{ $attach->file_name }}
                                                    </a>
                                                @endif
                                                <span class="text-[9px] text-gray-400">{{ number_format($attach->file_size / 1024, 1) }} KB</span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center gap-1">
                                            @if($attach->is_office_compatible)
                                                <a href="{{ route('onlyoffice.activity.edit', $attach) }}" target="_blank" rel="noopener noreferrer"
                                                   class="text-teal-500 hover:text-teal-700 p-1 hover:bg-teal-50 dark:hover:bg-teal-950/20 rounded-lg transition-all"
                                                   title="Editar con Office">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>
                                            @endif
                                            
                                            <a href="{{ route('teams.activities.attachments.download', [$team, $activity, $attach]) }}"
                                               class="text-gray-500 hover:text-violet-600 p-1 hover:bg-violet-50 dark:hover:bg-violet-950/20 rounded-lg transition-all"
                                               title="Descargar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                            </a>

                                            <!-- Botón para eliminar archivo de la DB -->
                                            <button type="button" onclick="deleteExistingAttachment(this, {{ $attach->id }})" class="text-red-500 hover:text-red-700 p-1 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-lg transition-all" title="Eliminar">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div id="file-list-preview" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pb-4">
                        <!-- Lista temporal de nuevos archivos seleccionados -->
                    </div>
                </div>
        </div>
    </div>
<!-- Botones de Acción -->
                <div class="flex justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('teams.activities.show', [$team, $activity]) }}"
                        class="text-sm text-gray-500 hover:text-gray-900 px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 transition-all font-medium">Cancelar</a>
                    <button type="submit"
                        class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-8 py-2.5 rounded-xl font-bold transition-all shadow-lg hover:shadow-violet-500/25">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

        @if ($activity->type === 'document')
            @php
                $chapters = $activity->metadata['chapters'] ?? [];
                $docVersion = $activity->metadata['version'] ?? '1.0.0';
                $canEditDocument = auth()->user()->is_admin || $team->isCoordinator(auth()->user()) || auth()->id() === $activity->created_by_id || auth()->id() === $activity->assigned_user_id || $activity->assignedTo->contains(auth()->id()) || auth()->user()->can('update', $activity);
            @endphp
            <div id="chapters-section" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-colors space-y-6 mt-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-violet-50 dark:bg-violet-950/40 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-100 dark:border-violet-800/50 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-gray-800 dark:text-white">Estructura del Documento (Modo Libro)</h3>
                                <span class="px-2.5 py-0.5 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 text-[10px] font-black uppercase tracking-widest border border-violet-200 dark:border-violet-700/50 shadow-sm">
                                    v{{ $docVersion }}
                                </span>
                            </div>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-wide">Añade secciones sin interferir en la descripción principal</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        @if($canEditDocument)
                        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="flex items-center gap-1.5 text-xs bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-200 dark:border-teal-500/20 hover:bg-teal-600 hover:text-white hover:border-teal-600 dark:hover:bg-teal-500 dark:hover:text-white dark:hover:border-teal-500 px-3.5 py-2 rounded-xl font-bold transition-all shadow-sm active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ __('Nuevo documento OnlyOffice') }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <form id="edit-act-docx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="docx">
                            </form>
                            <form id="edit-act-xlsx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="xlsx">
                            </form>
                            <form id="edit-act-pptx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="pptx">
                            </form>

                            <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95 -translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute right-0 top-full mt-2 w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl z-[300] overflow-hidden ring-1 ring-black/5 dark:ring-white/5">
                                <div class="px-3 pt-3 pb-1.5">
                                    <p class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Crear con OnlyOffice</p>
                                </div>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('edit-act-docx-form').submit()" class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group/item">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 group-hover/item:scale-110 transition-transform">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13zM9 13h6v1H9v-1zm0 2h6v1H9v-1zm0 2h4v1H9v-1z"/></svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-xs font-bold text-gray-800 dark:text-white">Documento de texto</div>
                                        <div class="text-[10px] text-gray-400 font-medium">.docx · Word / Writer</div>
                                    </div>
                                </button>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('edit-act-xlsx-form').submit()" class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group/item">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 group-hover/item:scale-110 transition-transform">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13zM8 12h2v1H8v-1zm0 2h2v1H8v-1zm0 2h2v1H8v-1zm3-4h5v1h-5v-1zm0 2h5v1h-5v-1zm0 2h5v1h-5v-1z"/></svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-xs font-bold text-gray-800 dark:text-white">Hoja de cálculo</div>
                                        <div class="text-[10px] text-gray-400 font-medium">.xlsx · Excel / Calc</div>
                                    </div>
                                </button>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('edit-act-pptx-form').submit()" class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors group/item">
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
                        @endif

                        <button type="button" onclick="printDocumentBook()" class="flex items-center gap-1.5 text-xs bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:text-violet-600 dark:hover:text-violet-400 px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 font-bold transition-all shadow-sm active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            📖 Imprimir Libro
                        </button>
                        @if($canEditDocument)
                            <button type="button" @click="$dispatch('open-add-chapter-modal')" class="flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-xl font-bold transition-all shadow-lg shadow-violet-500/25 active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Añadir Capítulo
                            </button>
                        @endif
                    </div>
                </div>

                @if (empty($chapters))
                    <div class="text-center py-8">
                        <p class="text-xs text-gray-400 italic">No hay capítulos registrados en este documento. Haz clic en "Añadir Capítulo" para comenzar.</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach ($chapters as $idx => $chapter)
                            <div x-data="{ editing: false }" class="bg-gray-50/40 dark:bg-gray-800/20 border border-gray-100 dark:border-gray-800/60 rounded-2xl p-5 space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800/50">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-7 h-7 rounded-xl bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-black text-xs flex items-center justify-center border border-violet-200 dark:border-violet-800 shadow-sm shrink-0">
                                            {{ $idx + 1 }}
                                        </span>
                                        <div class="min-w-0">
                                            <h4 class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $chapter['title'] }}</h4>
                                            <p class="text-[10px] text-gray-400">Por {{ $chapter['author_name'] ?? 'Autor' }} • {{ \Carbon\Carbon::parse($chapter['updated_at'] ?? $chapter['created_at'] ?? now())->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        @if($canEditDocument)
                                            <button type="button" @click="editing = !editing" class="p-1.5 text-gray-400 hover:text-blue-500 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" title="Editar capítulo">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <form action="{{ route('teams.activities.chapters.destroy', [$team, $activity, $chapter['id']]) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este capítulo?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all" title="Eliminar capítulo">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <div x-show="!editing" id="chapter-content-{{ $chapter['id'] }}" style="height: 200px; max-height: none; overflow-y: auto;" class="prose dark:prose-invert prose-sm max-w-none text-xs text-gray-700 dark:text-gray-300 leading-relaxed resize-y min-h-[120px] custom-scrollbar pr-4 p-4 bg-white dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800/80 rounded-2xl shadow-sm">
                                    {!! str($chapter['content'] ?? '')->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                </div>

                                <form x-show="editing" x-cloak action="{{ route('teams.activities.chapters.update', [$team, $activity, $chapter['id']]) }}" method="POST" class="space-y-4 pt-2">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Título del Capítulo</label>
                                        <input type="text" name="chapter_title" value="{{ $chapter['title'] }}" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2 text-xs text-gray-800 dark:text-white outline-none shadow-sm">
                                    </div>
                                    <div style="height: 220px; max-height: none; overflow-y: auto;" class="resize-y min-h-[150px] overflow-y-auto custom-scrollbar border border-gray-100 dark:border-gray-800 rounded-2xl p-2 bg-white dark:bg-gray-900 shadow-sm">
                                        <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Contenido (Markdown)</label>
                                        <x-markdown-editor 
                                            name="chapter_content" 
                                            id="edit-chap-{{ $chapter['id'] }}"
                                            :value="$chapter['content'] ?? ''"
                                            :label="null"
                                            rows="4"
                                            placeholder="Contenido del capítulo..."
                                            :upload-url="route('teams.forum.upload_image', $team)"
                                        />
                                    </div>
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="editing = false" class="bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl transition-all active:scale-95">
                                            Cancelar
                                        </button>
                                        <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-lg shadow-violet-500/20 transition-all active:scale-95">
                                            Guardar Capítulo
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>

    @push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        .ts-wrapper {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        .ts-control {
            border-radius: 0.75rem !important;
            border-width: 1px !important;
            background-color: #f9fafb !important;
            border-color: #e5e7eb !important;
            padding: 0.625rem 1rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            min-height: 44px !important;
            display: flex !important;
            align-items: center !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }
        .ts-control input { 
            font-size: 14px !important; 
            padding: 0 !important; 
            margin: 0 !important; 
            background: transparent !important; 
            border: none !important; 
            outline: none !important; 
            box-shadow: none !important;
            line-height: 1 !important;
            height: auto !important;
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
        
        .ts-wrapper .clear-button { 
            right: 1rem !important; 
            top: 50% !important; 
            transform: translateY(-50%) !important; 
            font-size: 1.25rem !important;
            color: #9ca3af !important;
            opacity: 0.7 !important;
            transition: all 0.2s ease !important;
        }
        .ts-wrapper .clear-button:hover { opacity: 1 !important; color: #ef4444 !important; }
        .ts-wrapper .ts-control { padding-right: 2.5rem !important; }
        
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
    </style>

    <script>
        window.deleteExistingAttachment = function(btnEl, attachmentId) {
            Swal.fire({
                title: '¿Eliminar archivo?',
                text: 'Esta acción borrará permanentemente el archivo del servidor.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/teams/{{ $team->id }}/activities/attachments/${attachmentId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => {
                        if (res.ok) {
                            btnEl.closest('.flex.items-center.justify-between').remove();
                            Swal.fire('Eliminado', 'El archivo ha sido borrado.', 'success');
                        } else {
                            Swal.fire('Error', 'No se pudo borrar el archivo.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'Ocurrió un fallo de conexión.', 'error');
                    });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // --- Eisenhower Matrix Preview ---
            const quadrantData = {
                1: { label: 'Urgente e Importante', description: 'Hacer de inmediato' },
                2: { label: 'Importante pero No Urgente', description: 'Planificar/Programar' },
                3: { label: 'Urgente pero No Importante', description: 'Delegar' },
                4: { label: 'Ni Urgente ni Importante', description: 'Eliminar/Posponer' }
            };
            const priorityEl = document.querySelector('[name="priority"]');
            const urgencyEl = document.querySelector('[name="urgency"]');
            const preview = document.getElementById('quadrant-preview');
            const highLevels = ['high', 'critical'];

            const qColors = {
                1: { border: 'border-red-200 dark:border-red-700', bg: 'bg-red-50 dark:bg-red-950/30', text: 'text-red-600 dark:text-red-300' },
                2: { border: 'border-blue-200 dark:border-blue-700', bg: 'bg-blue-50 dark:bg-blue-950/30', text: 'text-blue-600 dark:text-blue-300' },
                3: { border: 'border-amber-200 dark:border-amber-700', bg: 'bg-amber-50 dark:bg-amber-950/30', text: 'text-amber-600 dark:text-amber-300' },
                4: { border: 'border-gray-200 dark:border-gray-700', bg: 'bg-gray-50 dark:bg-gray-800', text: 'text-gray-600 dark:text-gray-300' },
            };

            function updatePreview() {
                if (!priorityEl || !urgencyEl || !preview) return;
                const imp = highLevels.includes(priorityEl.value);
                const urg = highLevels.includes(urgencyEl.value);
                let q = 4;
                if (imp && urg) q = 1;
                else if (imp) q = 2;
                else if (urg) q = 3;

                const cfg = qColors[q];
                preview.className = `rounded-xl border p-3 text-xs transition-all shadow-sm dark:shadow-none ${cfg.border} ${cfg.bg}`;
                preview.classList.remove('hidden');
                document.getElementById('qp-label').className = `font-bold uppercase tracking-wider ${cfg.text}`;
                document.getElementById('qp-label').textContent = `Q${q}: ${quadrantData[q].label}`;
                document.getElementById('qp-desc').className = `text-gray-500 dark:text-gray-400 ml-1 italic font-medium`;
                document.getElementById('qp-desc').textContent = `— ${quadrantData[q].description}`;
            }

            if (priorityEl && urgencyEl) {
                priorityEl.addEventListener('change', updatePreview);
                urgencyEl.addEventListener('change', updatePreview);
                updatePreview();
            }

            // --- TomSelect for Expedientes ---
            const expedSelectEl = document.getElementById('expediente_id_select');
            if (expedSelectEl) {
                new TomSelect("#expediente_id_select", {
                    plugins: ['clear_button'],
                    create: false,
                    sortField: { field: "text", direction: "asc" },
                    placeholder: 'Buscar expediente...',
                    allowEmptyOption: true,
                    render: {
                        option: function(data, escape) {
                            if (!data.value) return '<div class="text-gray-400 italic py-1 px-2 text-sm">' + escape(data.text) + '</div>';
                            const p = data.text.split('—');
                            const code = p[0].trim();
                            const title = p.length > 1 ? p.slice(1).join('—').trim() : code;
                            return '<div style="display:flex; align-items:center; gap:12px; padding:2px 4px;">' +
                                '<div style="flex-shrink:0; min-width:85px; height:24px; display:flex; align-items:center; justify-content:center; background:rgba(79,70,229,0.08); border:1px solid rgba(79,70,229,0.15); border-radius:6px; font-size:9px; font-weight:900; font-family:monospace; color:#4f46e5;">' + escape(code) + '</div>' +
                                '<div style="font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + escape(title) + '</div>' +
                            '</div>';
                        },
                        item: function(data, escape) {
                            if (!data.value) return '<div class="text-gray-500 font-bold text-sm">' + escape(data.text) + '</div>';
                            const p = data.text.split('—');
                            const code = p[0].trim();
                            const title = p.length > 1 ? p.slice(1).join('—').trim() : code;
                            return '<div style="display:flex; align-items:center; gap:8px;">' +
                                '<span style="flex-shrink:0; display:inline-flex; align-items:center; height:18px; padding:0 6px; background:rgba(79,70,229,0.1); color:#4f46e5; border-radius:4px; font-size:9px; font-weight:900; font-family:monospace;">' + escape(code) + '</span>' +
                                '<span style="font-size:13px; font-weight:700;">' + escape(title) + '</span>' +
                            '</div>';
                        }
                    }
                });
            }

            // --- TomSelect for Searchable Dependencies ---
            const parenSelectEl = document.getElementById('parent_id_select');
            if (parenSelectEl) {
                new TomSelect("#parent_id_select", {
                    create: false,
                    sortField: { field: "text", direction: "asc" },
                    placeholder: 'Buscar actividad padre...',
                    render: {
                        option: function(data, escape) {
                            return '<div class="flex items-center gap-3">' +
                                '<div class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 shrink-0 border border-gray-200/50 dark:border-gray-700/50">' +
                                    '<span class="text-[9px] font-mono font-black">#' + escape(data.value) + '</span>' +
                                '</div>' +
                                '<div class="flex flex-col min-w-0">' +
                                    '<span class="font-bold text-gray-900 dark:text-white truncate text-xs">' + escape(data.text) + '</span>' +
                                    '<span class="text-[10px] text-gray-700 dark:text-gray-200 font-black uppercase tracking-widest mt-0.5 flex items-center gap-1.5">' + 
                                        '<span class="w-1.5 h-1.5 rounded-full bg-violet-400"></span>' +
                                        escape(data.assignee) + 
                                    '</span>' +
                                '</div>' +
                            '</div>';
                        },
                        item: function(data, escape) {
                            return '<div class="flex items-center gap-2">' + 
                                '<span class="text-[10px] font-mono font-bold text-violet-500 bg-violet-50 dark:bg-violet-900/30 px-1.5 py-0.5 rounded">#' + escape(data.value) + '</span>' +
                                '<span class="font-medium text-gray-900 dark:text-white">' + escape(data.text) + '</span>' +
                                '<span class="text-[9px] text-gray-500 bg-gray-100 dark:bg-gray-800 dark:text-gray-400 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-700 font-black uppercase tracking-tighter">@' + escape(data.assignee) + '</span>' +
                            '</div>';
                        }
                    }
                });
            }
        });

        let selectedDriveFiles = [];

        function openDrivePicker(folderId = 'root') {
            Swal.fire({
                title: 'Google Drive',
                html: `
                    <div class="flex flex-col gap-4">
                        <div id="drive-contents" class="max-h-64 overflow-y-auto flex flex-col gap-1 text-left">
                            <div class="flex items-center justify-center py-8">
                                <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                `,
                width: '32rem',
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cerrar',
                didOpen: () => {
                    loadDriveFolder(folderId);
                },
                background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
            });
        }

        function loadDriveFolder(folderId) {
            const container = document.getElementById('drive-contents');
            const teamId = '{{ $team->id }}';
            
            fetch(`{{ route('google.drive.list') }}?team_id=${teamId}&folderId=${folderId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.files) {
                        container.innerHTML = '<p class="text-center py-4 text-gray-500">No se pudieron cargar los archivos.</p>';
                        return;
                    }

                    container.innerHTML = '';
                    
                    if (folderId !== 'root') {
                        const backBtn = document.createElement('button');
                        backBtn.className = 'p-2 text-blue-600 font-bold text-sm mb-2';
                        backBtn.innerHTML = '⬅️ Volver';
                        backBtn.onclick = () => loadDriveFolder('root');
                        container.appendChild(backBtn);
                    }

                    data.files.forEach(file => {
                        const isFolder = file.mimeType === 'application/vnd.google-apps.folder';
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'flex items-center justify-between p-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl w-full text-left';
                        btn.innerHTML = `
                            <div class="flex items-center gap-3">
                                <span>${isFolder ? '📁' : '📄'}</span>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold truncate">${file.name}</span>
                                    <span class="text-[9px] text-gray-400">${file.mimeType.split('.').pop()}</span>
                                </div>
                            </div>
                        `;
                        btn.onclick = () => {
                            if (isFolder) {
                                loadDriveFolder(file.id);
                            } else {
                                selectDriveFile(file);
                            }
                        };
                        container.appendChild(btn);
                    });
                });
        }

        function selectDriveFile(file) {
            if (!selectedDriveFiles.some(f => f.id === file.id)) {
                selectedDriveFiles.push(file);
                updateFileListDisplays();
                Swal.close();
            } else {
                Swal.fire('Info', 'Este archivo ya está seleccionado', 'info');
            }
        }

        function updateFileListDisplays() {
            const driveInput = document.getElementById('drive_attachments_input');
            driveInput.value = JSON.stringify(selectedDriveFiles);
            renderFilesUI(document.querySelector('input[name="attachments[]"]'));
        }

        window.updateFileList = function(input) {
            renderFilesUI(input);
        }

        function renderFilesUI(fileInput) {
            const list = document.getElementById('file-list-preview');
            list.innerHTML = '';

            // Drive Files
            selectedDriveFiles.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800';
                div.innerHTML = `
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-800 flex items-center justify-center text-blue-600">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7.71 3.5L1.15 15l3.43 6 6.55-11.5H7.71zM9.73 15L6.3 21h13.12l3.43-6H9.73zM18.74 3.5l-6.55 11.5 3.43 6L22.18 9.5l-3.44-6z"/>
                            </svg>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-xs font-bold text-blue-700 dark:text-blue-300 truncate">${file.name}</span>
                            <span class="text-[9px] text-blue-400 uppercase font-bold">Google Drive</span>
                        </div>
                    </div>
                    <button type="button" onclick="removeDriveFile(${index})" class="text-red-500 hover:text-red-700 p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                `;
                list.appendChild(div);
            });

            // Local Files
            if (fileInput && fileInput.files.length > 0) {
                Array.from(fileInput.files).forEach((file, fileIndex) => {
                    const isImage = file.type.startsWith('image/');
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50';
                    
                    let imagePreview = '';
                    if (isImage) {
                        const objectUrl = URL.createObjectURL(file);
                        imagePreview = `<div class="w-8 h-8 rounded-lg overflow-hidden shrink-0"><img src="${objectUrl}" class="w-full h-full object-cover"></div>`;
                    } else {
                        imagePreview = `<div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 flex items-center justify-center text-gray-400 font-mono text-[9px] shrink-0">${file.name.split('.').pop().toUpperCase()}</div>`;
                    }

                    div.innerHTML = `
                        <div class="flex items-center gap-3 overflow-hidden">
                            ${imagePreview}
                            <div class="flex flex-col min-w-0">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-200 truncate">${file.name}</span>
                                <span class="text-[10px] text-gray-400">${(file.size / 1024).toFixed(1)} KB</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            ${isImage ? `
                            <button type="button" onclick="editLocalFile(${fileIndex})" class="text-violet-500 hover:text-violet-700 p-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 transition-all hover:bg-violet-50" title="Editar Imagen">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            ` : ''}
                            <button type="button" onclick="removeLocalFile(${fileIndex})" class="text-red-500 hover:text-red-700 p-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 transition-all hover:bg-red-50" title="Eliminar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    `;
                    list.appendChild(div);
                });
            }
        }

        window.removeDriveFile = function(index) {
            selectedDriveFiles.splice(index, 1);
            document.getElementById('drive_attachments_input').value = JSON.stringify(selectedDriveFiles);
            renderFilesUI(document.querySelector('input[name="attachments[]"]'));
        }

        window.removeLocalFile = function(index) {
            const input = document.querySelector('input[name="attachments[]"]');
            const dataTransfer = new DataTransfer();
            Array.from(input.files).forEach((file, i) => {
                if (i !== index) dataTransfer.items.add(file);
            });
            input.files = dataTransfer.files;
            renderFilesUI(input);
        }

        window.editLocalFile = function(index) {
            const input = document.querySelector('input[name="attachments[]"]');
            const file = input.files[index];
            if (typeof window.openGlobalImageEditor === 'function') {
                window.openGlobalImageEditor(file, (editedFile) => {
                    const dataTransfer = new DataTransfer();
                    Array.from(input.files).forEach((f, i) => {
                        if (i === index) dataTransfer.items.add(editedFile);
                        else dataTransfer.items.add(f);
                    });
                    input.files = dataTransfer.files;
                    renderFilesUI(input);
                });
            }
        }
    </script>
    @endpush

    {{-- BARRA FLOTANTE DE ACCIONES RÁPIDAS --}}
    <div id="activity-edit-floating-bar"
         x-data="floatingDraggable"
         @mousedown="startDrag"
         @touchstart.passive="startDrag"
         @window:mousemove="drag"
         @window:touchmove.passive="drag"
         @window:mouseup="stopDrag"
         @window:touchend="stopDrag"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 translate-y-4 z-[800] flex items-center gap-2 px-4 py-2.5 bg-white/93 dark:bg-gray-900/93 backdrop-blur-xl border border-gray-100 dark:border-gray-800 rounded-2xl shadow-2xl opacity-0 pointer-events-none transition-all duration-300 whitespace-nowrap cursor-move"
         :class="isDragging ? 'scale-105 shadow-[0_20px_50px_rgba(0,0,0,0.2)]' : ''">

        <a href="{{ route('teams.activities.show', [$team, $activity]) }}"
           style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#6b7280;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:all 0.15s ease;"
           onmouseover="this.style.color='#7c3aed';this.style.background='#f5f3ff'"
           onmouseout="this.style.color='#6b7280';this.style.background='transparent'">
            <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            <span>Cancelar</span>
        </a>

        <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>

        <span style="font-size:0.75rem;font-weight:900;color:#1f2937;max-width:200px;overflow:hidden;text-overflow:ellipsis;" class="dark:text-gray-300">
            {{ $activity->title }}
        </span>

        <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>

        <button type="button"
                onclick="document.getElementById('edit-activity-form').submit()"
           style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#fff;background:#7c3aed;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:background 0.15s ease;border:none;cursor:pointer;"
           onmouseover="this.style.background='#6d28d9'"
           onmouseout="this.style.background='#7c3aed'">
            <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Guardar</span>
        </button>
    </div>

    <script>
        (function() {
            const bar = document.getElementById('activity-edit-floating-bar');
            if (bar) {
                const checkScroll = (e) => {
                    const target = e.target === document ? document.documentElement : e.target;
                    const scrollY = target.scrollTop || 0;
                    const finalScroll = scrollY || window.scrollY || 0;
                    
                    if (finalScroll > 150) {
                        bar.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
                        bar.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
                    } else {
                        bar.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
                        bar.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
                    }
                };
                window.addEventListener('scroll', checkScroll, { passive: true, capture: true });
            }
        })();

        function printDocumentBook() {
            const printWin = window.open('', '_blank');
            const title = @json($activity->title);
            const teamName = @json($team->name);
            const docVersion = @json($activity->metadata['version'] ?? '1.0.0');
            const chapters = @json($activity->metadata['chapters'] ?? []);
            
            let chaptersHtml = '';
            let tocHtml = '';
            
            chapters.forEach((chap, idx) => {
                tocHtml += `
                    <div class="toc-item">
                        <span class="toc-title">${idx + 1}. ${chap.title}</span>
                        <span class="toc-dots"></span>
                        <span class="toc-page">Capítulo ${idx + 1}</span>
                    </div>
                `;
                
                chaptersHtml += `
                    <div class="chapter-page">
                        <div class="chapter-header">
                            <span class="chapter-num">CAPÍTULO ${idx + 1}</span>
                            <h2 class="chapter-title">${chap.title}</h2>
                            <div class="chapter-meta">Por ${chap.author_name || 'Autor'} • ${chap.updated_at}</div>
                        </div>
                        <div class="chapter-body">${marked.parse ? marked.parse(chap.content) : chap.content}</div>
                    </div>
                `;
            });

            printWin.document.write(`
                <!DOCTYPE html>
                <html>
                    <head>
                        <title>${title} - Libro Digital</title>
                        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">
                        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"><\/script>
                        <style>
                            @page { size: A4; margin: 2.5cm 2cm; }
                            body { font-family: 'Merriweather', serif; color: #1e293b; line-height: 1.8; margin: 0; padding: 0; font-size: 14px; }
                            h1, h2, h3, h4, h5, h6, .outfit { font-family: 'Outfit', sans-serif; }
                            
                            /* Portada */
                            .cover-page { height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; page-break-after: always; padding: 2rem; box-sizing: border-box; }
                            .cover-team { font-size: 16px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 4px; margin-bottom: 2rem; font-family: 'Outfit', sans-serif; }
                            .cover-title { font-size: 42px; font-weight: 900; color: #0f172a; line-height: 1.2; margin-bottom: 2rem; font-family: 'Outfit', sans-serif; }
                            .cover-badge { display: inline-block; background: #f1f5f9; color: #475569; padding: 8px 24px; border-radius: 50px; font-size: 14px; font-weight: 700; margin-bottom: 4rem; font-family: 'Outfit', sans-serif; border: 1px solid #e2e8f0; }
                            .cover-footer { margin-top: auto; font-size: 14px; color: #64748b; font-family: 'Outfit', sans-serif; }
                            
                            /* Índice */
                            .toc-page { page-break-after: always; padding: 2rem 0; }
                            .toc-main-title { font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 3rem; font-family: 'Outfit', sans-serif; border-bottom: 2px solid #e2e8f0; padding-bottom: 1rem; }
                            .toc-item { display: flex; align-items: baseline; margin-bottom: 1.5rem; font-family: 'Outfit', sans-serif; font-size: 16px; }
                            .toc-title { font-weight: 600; color: #334155; }
                            .toc-dots { flex: 1; border-bottom: 1px dotted #cbd5e1; margin: 0 12px; }
                            .toc-page { font-weight: 700; color: #64748b; font-size: 14px; }
                            
                            /* Capítulos */
                            .chapter-page { page-break-before: always; padding: 2rem 0; }
                            .chapter-header { margin-bottom: 3rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 2rem; }
                            .chapter-num { font-size: 14px; font-weight: 800; color: #8b5cf6; text-transform: uppercase; letter-spacing: 3px; font-family: 'Outfit', sans-serif; display: block; margin-bottom: 0.5rem; }
                            .chapter-title { font-size: 32px; font-weight: 800; color: #0f172a; margin: 0 0 1rem 0; font-family: 'Outfit', sans-serif; line-height: 1.2; }
                            .chapter-meta { font-size: 13px; color: #64748b; font-family: 'Outfit', sans-serif; }
                            .chapter-body { color: #334155; }
                            .chapter-body p { margin-bottom: 1.5rem; }
                            .chapter-body h1, .chapter-body h2, .chapter-body h3 { font-family: 'Outfit', sans-serif; color: #0f172a; margin-top: 2.5rem; margin-bottom: 1rem; font-weight: 700; }
                        </style>
                    </head>
                    <body>
                        <div class="cover-page">
                            <div class="cover-team">${teamName}</div>
                            <h1 class="cover-title">${title}</h1>
                            <div class="cover-badge">DOCUMENTO VERSIÓN ${docVersion}</div>
                            <div class="cover-footer">Sientia MTX • Exportado el ${new Date().toLocaleDateString('es-ES')}</div>
                        </div>
                        
                        <div class="toc-page">
                            <h2 class="toc-main-title">Índice General</h2>
                            ${tocHtml}
                        </div>

                        ${chaptersHtml}
                        
                        <script>
                            window.onload = () => {
                                setTimeout(() => window.print(), 500);
                            };
                        <\/script>
                    </body>
                </html>
            `);
            printWin.document.close();
        }
    </script>

    <!-- MODAL DE AÑADIR CAPÍTULO A DOCUMENTO -->
    <div x-data="{ show: false }"
        @open-add-chapter-modal.window="show = true"
        x-show="show"
        class="fixed inset-0 z-[1000] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click.self="show = false">
        
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] w-full max-w-2xl overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transform transition-all text-left flex flex-col max-h-[90vh]"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            
            <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-violet-50/50 dark:bg-violet-955/20">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xl">✍️</span>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">
                            {{ __('Añadir Nuevo Capítulo') }}
                        </h3>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">
                        {{ __('Añade una nueva sección estructurada al documento. Podrás editarla o eliminarla de forma independiente.') }}
                    </p>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-2 rounded-2xl hover:bg-white dark:hover:bg-gray-800 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('teams.activities.chapters.store', [$team, $activity]) }}" method="POST" class="flex flex-col flex-1 overflow-hidden m-0">
                @csrf
                <div class="p-8 overflow-y-auto custom-scrollbar space-y-5 flex-1">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">
                            Título del Capítulo
                        </label>
                        <input type="text" name="chapter_title" required placeholder="Ej. 1. Introducción y Objetivos..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 text-xs text-gray-800 dark:text-white outline-none shadow-sm">
                    </div>

                    <div style="height: 250px; max-height: none; overflow-y: auto;" class="resize-y min-h-[150px] overflow-y-auto custom-scrollbar border border-gray-200 dark:border-gray-700 rounded-2xl p-3 bg-white dark:bg-gray-900 shadow-sm">
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">
                            Contenido (Markdown)
                        </label>
                        <x-markdown-editor 
                            name="chapter_content" 
                            id="new-chap-content"
                            :value="''"
                            :label="null"
                            rows="5"
                            placeholder="Escribe el contenido del capítulo utilizando sintaxis Markdown..."
                            :upload-url="route('teams.forum.upload_image', $team)"
                        />
                    </div>
                </div>

                <div class="px-8 py-5 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-3 shrink-0">
                    <button type="button" @click="show = false" class="px-6 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-xs font-black uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-800 transition-all shadow-sm active:scale-95">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-violet-500/25 active:scale-95">
                        Guardar Capítulo
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
