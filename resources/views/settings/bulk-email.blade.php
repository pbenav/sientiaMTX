<x-app-layout>
    @section('title', __('Enviar Correo Masivo / Invitaciones'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl shadow-sm border border-emerald-200 dark:border-emerald-800/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">{{ __('Gestor de Envío Masivo & Invitaciones') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Envía correos masivos o genera invitaciones con token único para nuevos miembros.') }}</p>
                </div>
            </div>
            <div>
                <a href="{{ route('settings.users') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm font-bold rounded-xl transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('Volver') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-4 shadow-sm" x-data="{ 
        showCc: false, 
        showBcc: false, 
        isInvitation: true, 
        showAntiSpam: true 
    }">
        <div class="max-w-5xl mx-auto">
            @include('settings.partials.tabs')

            @if(session('info'))
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 dark:bg-blue-950/30 dark:border-blue-900/50 rounded-2xl flex items-center gap-3 text-blue-700 dark:text-blue-300 animate-pulse">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm font-medium">{{ session('info') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('settings.users.bulk-email.send') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                @csrf

                <!-- Columna Principal: El Cliente de Correo -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm flex flex-col">
                    <!-- Barra superior de herramientas del cliente de correo -->
                    <div class="bg-gray-50 dark:bg-gray-800/50 px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Redactar Nuevo Mensaje
                        </span>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="showCc = !showCc" class="text-xs font-bold px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 transition-colors" :class="{'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400': showCc}">CC</button>
                            <button type="button" @click="showBcc = !showBcc" class="text-xs font-bold px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 transition-colors" :class="{'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400': showBcc}">CCO</button>
                        </div>
                    </div>

                    <!-- Campos de Encabezado -->
                    <div class="divide-y divide-gray-100 dark:divide-gray-800 px-6">
                        
                        <!-- Campo DESTAINAITARIO -->
                        <div class="py-3 flex items-start gap-4">
                            <span class="text-sm font-bold text-gray-400 w-12 pt-2">Para:</span>
                            <div class="flex-1">
                                <textarea name="to" rows="2" required
                                    class="w-full text-sm bg-transparent border-none focus:ring-0 outline-none p-2 resize-none text-gray-900 dark:text-white placeholder-gray-400"
                                    placeholder="correos@ejemplo.com, otro@ejemplo.com (separa por comas, saltos de línea o espacios)"></textarea>
                                <p class="text-[11px] text-gray-400 mt-1">Pega aquí tu lista de correos. Limpiaremos duplicados automáticamente.</p>
                            </div>
                        </div>

                        <!-- Campo CC -->
                        <div class="py-3 flex items-start gap-4" x-show="showCc" x-transition>
                            <span class="text-sm font-bold text-gray-400 w-12 pt-2">CC:</span>
                            <input type="text" name="cc" 
                                class="flex-1 text-sm bg-transparent border-none focus:ring-0 outline-none p-2 text-gray-900 dark:text-white placeholder-gray-400"
                                placeholder="Copia visible...">
                        </div>

                        <!-- Campo CCO (BCC) -->
                        <div class="py-3 flex items-start gap-4" x-show="showBcc" x-transition>
                            <span class="text-sm font-bold text-gray-400 w-12 pt-2">CCO:</span>
                            <input type="text" name="bcc" 
                                class="flex-1 text-sm bg-transparent border-none focus:ring-0 outline-none p-2 text-gray-900 dark:text-white placeholder-gray-400"
                                placeholder="Copia oculta...">
                        </div>

                        <!-- Campo ASUNTO -->
                        <div class="py-3 flex items-center gap-4">
                            <span class="text-sm font-bold text-gray-400 w-12">Asunto:</span>
                            <input type="text" name="subject" required
                                class="flex-1 text-sm bg-transparent border-none focus:ring-0 outline-none p-2 text-gray-900 dark:text-white placeholder-gray-400 font-medium"
                                placeholder="Escribe el título del correo...">
                        </div>
                    </div>

                    <!-- Campo de Texto Principal (Cuerpo) -->
                    <div class="flex-1 min-h-[300px] bg-gray-50/30 dark:bg-gray-900/30 px-6 py-4 flex flex-col border-t border-gray-100 dark:border-gray-800">
                        <div class="mb-2 flex flex-wrap items-center gap-1.5" x-show="isInvitation">
                            <span class="text-[11px] font-black uppercase tracking-widest text-gray-400 mr-1">Variables dinámicas:</span>
                            <button type="button" onclick="insertAtCursor('{enlace_invitacion}')" class="px-2 py-0.5 text-xs bg-violet-50 text-violet-600 hover:bg-violet-100 dark:bg-violet-900/20 dark:text-violet-400 rounded-md border border-violet-100 dark:border-violet-800/50 font-mono font-bold shadow-sm active:scale-95 transition-all" title="Inserta el enlace dinámico único de registro para cada email">{enlace_invitacion}</button>
                            <button type="button" onclick="insertAtCursor('{nombre_equipo}')" class="px-2 py-0.5 text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 rounded-md border border-blue-100 dark:border-blue-800/50 font-mono font-bold shadow-sm active:scale-95 transition-all" title="Inserta el nombre del equipo seleccionado">{nombre_equipo}</button>
                            <button type="button" onclick="insertAtCursor('{email}')" class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 rounded-md border border-transparent font-mono font-bold shadow-sm active:scale-95 transition-all" title="Inserta el email destinatario en el cuerpo">{email}</button>
                        </div>
                        <textarea name="body" id="email-body" required rows="12"
                            class="w-full flex-1 bg-transparent border-none focus:ring-0 outline-none p-2 text-sm font-normal text-gray-700 dark:text-gray-200 placeholder-gray-400 resize-y"
                            placeholder="Hola,\n\nTe invitamos a formar parte de nuestro equipo en Sientia.\n\nHaz clic aquí para unirte: {enlace_invitacion}\n\n¡Un saludo!"></textarea>
                    </div>
                </div>

                <!-- Columna Derecha: Parámetros y Configuración Anti-Spam -->
                <div class="flex flex-col gap-6">
                    
                    <!-- Tarjeta de Configuración de Invitación -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-800">
                            <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                                Sistema de Tokens
                            </h3>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_invitation" x-model="isInvitation" value="1" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600"></div>
                            </label>
                        </div>

                        <div x-show="isInvitation" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="space-y-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-3">
                                Al activar esta opción, se generará automáticamente un token y enlace de registro único para cada destinatario de la lista.
                            </p>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Asignar al Equipo:</label>
                                <select name="team_id" class="w-full text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 focus:ring-2 focus:ring-violet-500 outline-none text-gray-700 dark:text-gray-300">
                                    <option value="">-- Seleccionar un equipo --</option>
                                    @foreach($teams as $team)
                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Rol del Invitado:</label>
                                <select name="role_id" class="w-full text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 focus:ring-2 focus:ring-violet-500 outline-none text-gray-700 dark:text-gray-300">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ $role->name === 'member' || $role->name === 'user' ? 'selected' : '' }}>
                                            {{ ucfirst($role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div x-show="!isInvitation" x-transition class="p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/50 rounded-xl text-amber-700 dark:text-amber-400 text-xs flex items-start gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span><strong>Modo Boletín/Informativo:</strong> Los correos se enviarán tal cual, sin enlaces de invitación individuales.</span>
                        </div>
                    </div>

                    <!-- Panel Anti-Spam e Inteligencia de Envío -->
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-800 cursor-pointer select-none" @click="showAntiSpam = !showAntiSpam">
                            <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                Escudo Anti-Spam
                            </h3>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{'rotate-180': !showAntiSpam}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <div x-show="showAntiSpam" x-transition:enter="transition ease-out duration-200" class="space-y-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                Para proteger vuestra reputación de correo, procesaremos los correos en segundo plano dividiéndolos en lotes espaciados en el tiempo.
                            </p>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Emails / Lote:</label>
                                    <input type="number" name="batch_size" value="25" min="1" max="500" required
                                        class="w-full text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 outline-none text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Pausa (min):</label>
                                    <input type="number" name="delay_minutes" value="5" min="1" max="60" required
                                        class="w-full text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 outline-none text-gray-900 dark:text-white">
                                </div>
                            </div>
                            <div class="text-[10px] bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 p-2.5 rounded-lg border border-emerald-100 dark:border-emerald-900/30 flex gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Envío Inteligente: El sistema enviará el primer lote ahora, y los siguientes lotes se encolarán para salir secuencialmente cada 5 minutos.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Disparo -->
                    <button type="submit" class="w-full flex items-center justify-center gap-3 py-4 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-sm font-black uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-emerald-500/25 group active:scale-95 border border-emerald-400/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Lanzar Envío Programado
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function insertAtCursor(textToInsert) {
            const textarea = document.getElementById('email-body');
            if (!textarea) return;
            
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            
            textarea.value = text.substring(0, start) + textToInsert + text.substring(end);
            textarea.focus();
            
            // Put cursor at the end of the inserted text
            const newCursorPos = start + textToInsert.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
        }
    </script>
    @endpush
</x-app-layout>
