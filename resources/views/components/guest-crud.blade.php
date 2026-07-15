@props(['initialGuests' => [], 'initialMessage' => ''])

<div x-data="guestCrud({{ json_encode($initialGuests) }}, {{ json_encode($initialMessage) }})" class="space-y-4">
    <div class="flex justify-end mb-2" x-cloak>
        <button type="button" @click="showModal = true" class="text-[10px] font-bold text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors flex items-center gap-1.5 px-3 py-1.5 bg-violet-50 dark:bg-violet-900/30 rounded-lg border border-violet-100 dark:border-violet-500/20">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            Personalizar Mensaje de Invitación
        </button>
    </div>

    <!-- Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-cloak>
        <div @click.away="showModal = false" class="bg-white dark:bg-gray-900 rounded-3xl p-6 w-full max-w-2xl shadow-xl border border-gray-200 dark:border-gray-800 flex flex-col max-h-[90vh]">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                <svg class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                Personalizar Mensaje
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Este mensaje se incluirá en el correo de invitación enviado a los asistentes externos.<br>
                <span class="font-bold text-gray-700 dark:text-gray-300 mt-2 block">Etiquetas disponibles para personalizar:</span>
                <span class="flex flex-wrap gap-2 mt-1">
                    <code class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-700">[nombre_invitado]</code>
                    <code class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-700">[mi_nombre]</code>
                    <code class="text-[10px] bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-700">[titulo_reunion]</code>
                </span>
            </p>
            
            <textarea x-model="customMessage" rows="5" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none resize-none" placeholder="Ej. Hola [nombre_invitado], te escribo de parte de [mi_nombre] para hablar sobre [titulo_reunion]..."></textarea>
            
            <input type="hidden" name="metadata[invitation_message]" :value="customMessage">

            <!-- Preview -->
            <div class="mt-4 flex-1 overflow-y-auto">
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Vista Previa (Aproximada)</p>
                <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300">
                    <p class="mb-4">Hola <span class="font-bold">Nombre Invitado</span>,</p>
                    <p class="mb-4">Has sido invitado/a a una reunión en <strong>SientiaMTX</strong> por <strong>Tu Nombre</strong>.</p>
                    
                    <div x-show="customMessage.trim() !== ''" class="bg-white dark:bg-gray-900 p-4 rounded-xl border-l-4 border-violet-500 mb-4 whitespace-pre-wrap text-gray-800 dark:text-gray-200 shadow-sm" x-text="customMessage.replace(/\[nombre_invitado\]/g, 'Juan Pérez').replace(/\[mi_nombre\]/g, 'Tu Nombre').replace(/\[titulo_reunion\]/g, document.querySelector('input[name=\'title\']')?.value || '(Título de la reunión)')"></div>
                    
                    <p class="mb-2"><strong>Asunto / Título:</strong> <span x-text="document.querySelector('input[name=\'title\']')?.value || '(Título de la reunión)'"></span></p>
                    <p><strong>Detalles de la Reunión:</strong><br>...</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" @click="showModal = false" class="px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-md">Listo</button>
            </div>
        </div>
    </div>

    <!-- Lista de Invitados -->
    <div class="space-y-3" x-show="guests.length > 0" x-cloak>
        <template x-for="(guest, index) in guests" :key="index">
            <div class="group flex items-center justify-between p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-violet-300 dark:hover:border-violet-600 transition-all shadow-sm">
                
                <div class="flex items-center gap-4 flex-1 min-w-0">
                    <div class="w-10 h-10 rounded-lg bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0 border border-violet-100 dark:border-violet-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate flex items-center gap-2">
                            <span x-text="guest.name"></span>
                            <template x-if="guest.notify == 1 || guest.notify == '1' || guest.notify === true">
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/30 flex items-center gap-0.5" title="Se notificará por correo">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                    Notificar
                                </span>
                            </template>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate flex items-center gap-1" x-text="guest.email"></p>
                    </div>
                </div>

                <button type="button" @click="removeGuest(index)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100 shrink-0" title="Eliminar Invitado">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>

                <!-- Hidden inputs for Laravel to capture the array -->
                <input type="hidden" :name="'metadata[guests][' + index + '][name]'" :value="guest.name">
                <input type="hidden" :name="'metadata[guests][' + index + '][email]'" :value="guest.email">
                <input type="hidden" :name="'metadata[guests][' + index + '][notify]'" :value="guest.notify ? 1 : 0">
            </div>
        </template>
    </div>
    
    <div x-show="guests.length === 0" class="text-center p-6 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl bg-gray-50/50 dark:bg-gray-800/30" x-cloak>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">No hay invitados externos añadidos</p>
    </div>

    <!-- Formulario para añadir -->
    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 flex flex-col md:flex-row gap-3 items-start md:items-end">
        <div class="flex-1 w-full">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Nombre del Invitado</label>
            <input type="text" x-model="newName" @keydown.enter.prevent="addGuest" placeholder="Ej. Juan Pérez" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2 text-sm outline-none transition-all dark:text-white">
        </div>
        <div class="flex-1 w-full">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Correo Electrónico</label>
            <input type="email" x-model="newEmail" @keydown.enter.prevent="addGuest" placeholder="juan@ejemplo.com" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2 text-sm outline-none transition-all dark:text-white">
        </div>
        <div class="flex items-center self-center md:self-end md:pb-2.5 shrink-0 px-2">
            <label class="flex items-center gap-2 cursor-pointer group">
                <input type="checkbox" x-model="newNotify" class="w-4 h-4 text-violet-600 bg-gray-100 border-gray-300 rounded focus:ring-violet-500 dark:focus:ring-violet-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 transition-colors">
                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-300 transition-colors flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    Notificar
                </span>
            </label>
        </div>
        <button type="button" @click="addGuest" :disabled="!isValid" class="w-full md:w-auto px-4 py-2 bg-violet-600 hover:bg-violet-700 disabled:bg-violet-300 disabled:cursor-not-allowed text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-md">
            Añadir
        </button>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        // Prevent re-registering if already registered
        if (!Alpine.data('guestCrud')) {
            Alpine.data('guestCrud', (initialGuests, initialMessage) => ({
                guests: initialGuests || [],
                customMessage: initialMessage || '',
                showModal: false,
                newName: '',
                newEmail: '',
                newNotify: true,
                
                get isValid() {
                    return this.newName.trim().length > 0 && this.isValidEmail(this.newEmail);
                },
                
                isValidEmail(string) {
                    return string.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/);
                },
                
                addGuest() {
                    if (this.isValid) {
                        this.guests.push({
                            name: this.newName.trim(),
                            email: this.newEmail.trim(),
                            notify: this.newNotify ? 1 : 0
                        });
                        this.newName = '';
                        this.newEmail = '';
                        this.newNotify = true;
                    }
                },
                
                removeGuest(index) {
                    this.guests.splice(index, 1);
                }
            }));
        }
    });
</script>
