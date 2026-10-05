@props(['initialGuests' => [], 'initialMessage' => '', 'team' => null, 'mentionsUrl' => null])

<div x-data="guestCrud({{ json_encode($initialGuests) }}, {{ json_encode($initialMessage) }})" class="space-y-4">
    <!-- Barra superior de estado -->
    <div class="flex items-center justify-between gap-3 mb-2" x-cloak>
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Invitados en la lista:</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/40" x-text="guests.length"></span>
        </div>
        <div class="flex items-center gap-2">
            <template x-if="guests.length > 1">
                <button type="button" @click="clearAllGuests" class="text-[11px] font-bold text-red-500 hover:text-red-700 dark:hover:text-red-400 transition-colors px-2.5 py-1.5 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20">
                    Vaciar lista
                </button>
            </template>
            <button type="button" @click="showModal = true" 
                    class="text-xs font-bold transition-all flex items-center gap-2 px-3.5 py-1.5 rounded-xl border shadow-sm"
                    :class="customMessage.trim().length > 0 ? 'bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 border-violet-300 dark:border-violet-700' : 'text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 bg-violet-50 hover:bg-violet-100 dark:bg-violet-900/30 dark:hover:bg-violet-900/50 border-violet-200/80 dark:border-violet-500/30'">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                <span>Personalizar Mensaje</span>
                <template x-if="customMessage.trim().length > 0">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" title="Mensaje personalizado activo"></span>
                </template>
            </button>
        </div>
    </div>

    <!-- Hidden input que permanece dentro del form padre -->
    <input type="hidden" name="metadata[invitation_message]" :value="customMessage">

    <!-- Modal Mensaje Personalizado Teleportado al Body -->
    <template x-teleport="body">
        <div x-show="showModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.window.escape="showModal = false"
             class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6 bg-slate-950/70 backdrop-blur-md overflow-y-auto" 
             x-cloak>
            
            <div @click.away="showModal = false" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                 class="relative w-full max-w-2xl bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-2xl border border-gray-200/80 dark:border-gray-800 overflow-hidden flex flex-col max-h-[90vh]">
                
                <!-- Modal Header -->
                <div class="px-6 py-5 bg-gradient-to-r from-violet-600/10 via-indigo-600/10 to-transparent border-b border-gray-150 dark:border-gray-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-violet-600 text-white rounded-2xl shadow-lg shadow-violet-500/30">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-gray-900 dark:text-white tracking-tight">Personalizar Mensaje de Invitación</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Edición enriquecida con Markdown, menciones y vista previa en vivo</p>
                        </div>
                    </div>
                    <button type="button" @click="showModal = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors" title="Cerrar (Esc)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5 overflow-y-auto custom-scrollbar flex-1">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                Haz clic en una etiqueta para insertarla en el texto:
                            </label>
                            <span class="text-[10px] text-violet-600 dark:text-violet-400 font-bold hidden sm:inline">Variables dinámicas</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="insertTag('[nombre_invitado]')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-mono font-bold bg-violet-50 hover:bg-violet-100 dark:bg-violet-950/60 dark:hover:bg-violet-900/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/60 transition-all hover:scale-105 active:scale-95 shadow-sm">
                                <span class="text-violet-500 font-extrabold">+</span> [nombre_invitado]
                            </button>
                            <button type="button" @click="insertTag('[mi_nombre]')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-mono font-bold bg-violet-50 hover:bg-violet-100 dark:bg-violet-950/60 dark:hover:bg-violet-900/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/60 transition-all hover:scale-105 active:scale-95 shadow-sm">
                                <span class="text-violet-500 font-extrabold">+</span> [mi_nombre]
                            </button>
                            <button type="button" @click="insertTag('[titulo_reunion]')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-mono font-bold bg-violet-50 hover:bg-violet-100 dark:bg-violet-950/60 dark:hover:bg-violet-900/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/60 transition-all hover:scale-105 active:scale-95 shadow-sm">
                                <span class="text-violet-500 font-extrabold">+</span> [titulo_reunion]
                            </button>
                        </div>
                    </div>

                    <div>
                        <x-markdown-editor 
                            name="custom_invitation_message_body" 
                            label="Cuerpo del Mensaje" 
                            :value="$initialMessage" 
                            rows="4" 
                            placeholder="Ej. Hola [nombre_invitado], te escribo de parte de [mi_nombre] para convocarte a [titulo_reunion]. Por favor revisa los puntos de la agenda adjunta..." 
                            :mentions-url="$mentionsUrl ?? (isset($team) ? route('teams.mentions', $team) : (request()->route('team') ? route('teams.mentions', request()->route('team')) : null))"
                            @input="customMessage = $event.detail || $event.target.value"
                        />
                    </div>

                    <!-- Live Email Preview Card -->
                    <div class="pt-2">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Vista Previa del Correo (Simulación)
                            </span>
                            <span class="text-[10px] text-gray-400 font-mono">Destinatario de ejemplo</span>
                        </div>
                        <div class="bg-gradient-to-b from-gray-50 to-gray-100/70 dark:from-gray-800/60 dark:to-gray-800/30 rounded-2xl p-4.5 border border-gray-200/80 dark:border-gray-700/80 text-xs text-gray-700 dark:text-gray-300 space-y-3 font-sans shadow-inner">
                            <p>Hola <span class="font-bold text-gray-900 dark:text-white">Juan Pérez</span>,</p>
                            <p>Has recibido una convocatoria/aviso en <strong class="text-violet-600 dark:text-violet-400">SientiaMTX</strong> por parte de <strong class="text-gray-900 dark:text-white">{{ auth()->user()->name ?? 'Tu Nombre' }}</strong>.</p>
                            
                            <template x-if="customMessage.trim() !== ''">
                                <div class="p-3.5 rounded-xl bg-white dark:bg-gray-900 border-l-4 border-violet-500 shadow-sm prose prose-sm dark:prose-invert max-w-none text-xs italic leading-relaxed" x-html="previewText"></div>
                            </template>

                            <div class="pt-2 border-t border-gray-200/60 dark:border-gray-700/60 text-[11px] space-y-1 text-gray-500 dark:text-gray-400">
                                <p><strong class="text-gray-700 dark:text-gray-300">Asunto:</strong> <span class="text-gray-900 dark:text-white font-medium" x-text="activityTitle || '(Título de la actividad)'"></span></p>
                                <p><strong class="text-gray-700 dark:text-gray-300">Detalles:</strong> Fecha, hora y enlaces de acceso correspondientes</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50/70 dark:bg-gray-800/40 border-t border-gray-150 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <template x-if="customMessage.trim().length > 0">
                            <button type="button" @click="customMessage = ''; const ta = $el.closest('[x-teleport], body, div').querySelector('textarea'); if(ta) { ta.value = ''; ta.dispatchEvent(new Event('input', { bubbles: true })); }" class="text-[11px] font-bold text-gray-500 hover:text-red-500 dark:hover:text-red-400 transition-colors">
                                Borrar texto personalizado
                            </button>
                        </template>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-200/60 dark:hover:bg-gray-700 rounded-xl transition-colors">
                            Cerrar
                        </button>
                        <button type="button" @click="showModal = false" class="px-6 py-2.5 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white text-xs font-black uppercase tracking-wider rounded-xl shadow-lg shadow-violet-500/25 hover:shadow-violet-500/40 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Listo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Lista de Invitados Actuales -->
    <div class="space-y-2.5 max-h-80 overflow-y-auto pr-1" x-show="guests.length > 0" x-cloak>
        <template x-for="(guest, index) in guests" :key="index">
            <div class="group flex items-center justify-between p-3 bg-white dark:bg-gray-800/90 border border-gray-200 dark:border-gray-700 rounded-2xl hover:border-violet-300 dark:hover:border-violet-600 transition-all shadow-sm">
                
                <div class="flex items-center gap-3.5 flex-1 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0 border border-violet-100 dark:border-violet-800 text-xs font-black" x-text="guest.name ? guest.name.charAt(0).toUpperCase() : '?'">
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-gray-900 dark:text-white truncate flex items-center gap-2">
                            <span x-text="guest.name"></span>
                            <template x-if="guest.notify == 1 || guest.notify == '1' || guest.notify === true">
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/30 flex items-center gap-0.5" title="Se notificará por correo">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                    Notificar
                                </span>
                            </template>
                        </p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate font-mono" x-text="guest.email"></p>
                    </div>
                </div>

                <button type="button" @click="removeGuest(index)" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100 shrink-0" title="Eliminar Invitado">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>

                <!-- Hidden inputs for Laravel to capture the array -->
                <input type="hidden" :name="'metadata[guests][' + index + '][name]'" :value="guest.name">
                <input type="hidden" :name="'metadata[guests][' + index + '][email]'" :value="guest.email">
                <input type="hidden" :name="'metadata[guests][' + index + '][notify]'" :value="guest.notify ? 1 : 0">
                <template x-if="guest.response_status">
                    <input type="hidden" :name="'metadata[guests][' + index + '][response_status]'" :value="guest.response_status">
                </template>
            </div>
        </template>
    </div>
    
    <!-- Sentinel input: si la lista queda vacía, envía valor vacío para vaciar metadata.guests en el servidor -->
    <input type="hidden" name="metadata[guests]" value="" x-bind:disabled="guests.length > 0">

    <div x-show="guests.length === 0" class="text-center p-6 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-3xl bg-gray-50/50 dark:bg-gray-800/30" x-cloak>
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">No hay invitados externos añadidos</p>
    </div>

    <!-- Pestañas de Modo: Individual o Pegado Masivo (Bulk) -->
    <div class="bg-gray-50/70 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-3xl p-4.5 shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-200/80 dark:border-gray-700 pb-3 mb-3.5">
            <div class="flex items-center gap-1.5 p-1 bg-gray-200/60 dark:bg-gray-900/60 rounded-2xl">
                <button type="button" @click="addMode = 'single'" :class="addMode === 'single' ? 'bg-white dark:bg-gray-800 text-violet-700 dark:text-violet-300 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    Añadir uno a uno
                </button>
                <button type="button" @click="addMode = 'bulk'" :class="addMode === 'bulk' ? 'bg-white dark:bg-gray-800 text-violet-700 dark:text-violet-300 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Pegado Masivo (Bulk)
                </button>
            </div>
            <span class="text-[10px] text-gray-400 font-medium hidden sm:inline" x-text="addMode === 'single' ? 'Añade participantes con nombre y correo' : 'Pega listas desde correo, Excel o CSV'"></span>
        </div>

        <!-- MODO 1: Añadir uno a uno -->
        <div x-show="addMode === 'single'" class="flex flex-col md:flex-row gap-3 items-start md:items-end">
            <div class="flex-1 w-full">
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Nombre del Invitado</label>
                <input type="text" x-model="newName" @keydown.enter.prevent="addGuest" placeholder="Ej. Juan Pérez" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-xl px-3.5 py-2 text-sm outline-none transition-all dark:text-white">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Correo Electrónico</label>
                <input type="email" x-model="newEmail" @keydown.enter.prevent="addGuest" placeholder="juan@ejemplo.com" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-xl px-3.5 py-2 text-sm outline-none transition-all dark:text-white font-mono">
            </div>
            <div class="flex items-center self-center md:self-end md:pb-2 shrink-0 px-2">
                <label class="flex items-center gap-2 cursor-pointer group select-none">
                    <input type="checkbox" x-model="newNotify" class="w-4 h-4 text-violet-600 bg-gray-100 border-gray-300 rounded focus:ring-violet-500 dark:focus:ring-violet-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 transition-colors">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300 transition-colors flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        Notificar
                    </span>
                </label>
            </div>
            <button type="button" @click="addGuest" :disabled="!isValid" class="w-full md:w-auto px-5 py-2.5 bg-violet-600 hover:bg-violet-700 disabled:bg-violet-300 dark:disabled:bg-gray-700 disabled:cursor-not-allowed text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-md shrink-0">
                Añadir
            </button>
        </div>

        <!-- MODO 2: Pegado Masivo (Bulk) -->
        <div x-show="addMode === 'bulk'" class="space-y-3" x-cloak>
            <div class="flex items-center justify-between">
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                    Pega aquí tu lista de invitados (Formato libre, CSV o multilínea)
                </label>
                <template x-if="bulkParsedCount > 0">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span x-text="bulkParsedCount + ' contactos detectados'"></span>
                    </span>
                </template>
            </div>

            <textarea x-model="bulkText" rows="4" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-2xl p-3.5 text-xs outline-none transition-all dark:text-white font-mono leading-relaxed resize-y" placeholder="Ejemplos admitidos:
• Juan Pérez <juan@ejemplo.com>
• Maria Lopez, maria@empresa.es
• ana@dominio.com, carlos@colegio.org
• O una dirección por cada línea..."></textarea>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" x-model="bulkNotify" class="w-4 h-4 text-violet-600 bg-gray-100 border-gray-300 rounded focus:ring-violet-500 dark:focus:ring-violet-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 transition-colors">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        Marcar para notificar a todos los importados
                    </span>
                </label>
                <div class="flex items-center gap-2">
                    <button type="button" @click="bulkText = ''" x-show="bulkText.trim().length > 0" class="px-3 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                        Limpiar
                    </button>
                    <button type="button" @click="addBulkGuests" :disabled="bulkParsedCount === 0" class="w-full sm:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:cursor-not-allowed text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <span x-text="bulkParsedCount > 0 ? 'Añadir ' + bulkParsedCount + ' Invitados' : 'Añadir Invitados'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const initGuestCrud = () => {
            if (typeof Alpine !== 'undefined' && !Alpine.data('guestCrud')) {
                Alpine.data('guestCrud', (initialGuests, initialMessage) => ({
                    guests: initialGuests || [],
                    customMessage: initialMessage || '',
                    showModal: false,
                    addMode: 'single', // 'single' | 'bulk'
                    newName: '',
                    newEmail: '',
                    newNotify: true,
                    bulkText: '',
                    bulkNotify: true,
                    
                    get isValid() {
                        return this.newName.trim().length > 0 && this.isValidEmail(this.newEmail);
                    },
                    
                    isValidEmail(string) {
                        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((string || '').trim());
                    },

                    guessNameFromEmail(email) {
                        let part = email.split('@')[0] || '';
                        part = part.replace(/[._+-]+/g, ' ').trim();
                        if (!part) return 'Invitado';
                        return part.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                    },

                    insertTag(tag) {
                        const textarea = this.$refs.customMessageArea;
                        if (textarea) {
                            const start = textarea.selectionStart || 0;
                            const end = textarea.selectionEnd || 0;
                            const text = this.customMessage || '';
                            this.customMessage = text.substring(0, start) + tag + text.substring(end);
                            this.$nextTick(() => {
                                textarea.focus();
                                textarea.setSelectionRange(start + tag.length, start + tag.length);
                            });
                        } else {
                            this.customMessage = (this.customMessage || '') + ' ' + tag;
                        }
                    },

                    get activityTitle() {
                        const titleInput = document.querySelector('input[name="title"]');
                        return titleInput && titleInput.value ? titleInput.value : '';
                    },

                    get previewText() {
                        let msg = this.customMessage || '';
                        if (!msg.trim()) return '';
                        const title = this.activityTitle;
                        let replaced = msg
                            .replace(/\[nombre_invitado\]/gi, 'Juan Pérez')
                            .replace(/\[mi_nombre\]/gi, '{{ auth()->user()->name ?? "Tu Nombre" }}')
                            .replace(/\[titulo_reunion\]/gi, title || '(Título)');

                        if (typeof marked !== 'undefined') {
                            try {
                                marked.use({ breaks: true, gfm: true });
                                const parsed = marked.parse(replaced);
                                return typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(parsed) : parsed;
                            } catch(e) {
                                return replaced;
                            }
                        }
                        return replaced;
                    },

                    get parsedBulkGuests() {
                        if (!this.bulkText || !this.bulkText.trim()) return [];
                        const lines = this.bulkText.split(/[\r\n]+/);
                        const results = [];
                        const seenInBatch = new Set();
                        const existingEmails = new Set(this.guests.map(g => (g.email || '').toLowerCase().trim()));
                        const emailRegex = /([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/g;

                        for (let rawLine of lines) {
                            let line = rawLine.trim();
                            if (!line) continue;

                            let entries = [line];
                            if (line.includes(';') || (line.match(emailRegex) && line.match(emailRegex).length > 1)) {
                                entries = line.split(/[;,]+/);
                            }

                            for (let entry of entries) {
                                entry = entry.trim();
                                if (!entry) continue;

                                // Formato: Nombre <email@domain.com>
                                const angleMatch = entry.match(/^(.*?)\s*<([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})>/);
                                if (angleMatch) {
                                    const name = angleMatch[1].replace(/["']/g, '').trim();
                                    const email = angleMatch[2].toLowerCase().trim();
                                    if (!seenInBatch.has(email) && !existingEmails.has(email)) {
                                        seenInBatch.add(email);
                                        results.push({
                                            name: name || this.guessNameFromEmail(email),
                                            email: email,
                                            notify: this.bulkNotify ? 1 : 0
                                        });
                                    }
                                    continue;
                                }

                                // Formato simple: extrae email y toma el resto como nombre
                                const directMatch = entry.match(/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/);
                                if (directMatch) {
                                    const email = directMatch[1].toLowerCase().trim();
                                    let name = entry.replace(directMatch[0], '').replace(/[<>,;"']/g, '').trim();
                                    if (!name) {
                                        name = this.guessNameFromEmail(email);
                                    }
                                    if (!seenInBatch.has(email) && !existingEmails.has(email)) {
                                        seenInBatch.add(email);
                                        results.push({
                                            name: name,
                                            email: email,
                                            notify: this.bulkNotify ? 1 : 0
                                        });
                                    }
                                }
                            }
                        }
                        return results;
                    },

                    get bulkParsedCount() {
                        return this.parsedBulkGuests.length;
                    },
                    
                    addGuest() {
                        if (this.isValid) {
                            const email = this.newEmail.trim().toLowerCase();
                            if (!this.guests.some(g => (g.email || '').toLowerCase().trim() === email)) {
                                this.guests.push({
                                    name: this.newName.trim(),
                                    email: email,
                                    notify: this.newNotify ? 1 : 0
                                });
                            }
                            this.newName = '';
                            this.newEmail = '';
                            this.newNotify = true;
                        }
                    },

                    addBulkGuests() {
                        const parsed = this.parsedBulkGuests;
                        if (parsed.length > 0) {
                            parsed.forEach(item => {
                                item.notify = this.bulkNotify ? 1 : 0;
                                this.guests.push(item);
                            });
                            this.bulkText = '';
                            this.addMode = 'single';
                        }
                    },
                    
                    removeGuest(index) {
                        this.guests.splice(index, 1);
                    },

                    clearAllGuests() {
                        if (confirm('¿Vaciar todos los invitados de la lista?')) {
                            this.guests = [];
                        }
                    }
                }));
            }
        };

        if (window.Alpine && window.Alpine.data) {
            initGuestCrud();
        } else {
            document.addEventListener('alpine:init', initGuestCrud);
        }
    })();
</script>
