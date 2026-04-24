<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">
                        {{ __('Gestión de Almacenamiento') }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">
                        {{ $team->name }} • Mantén tu equipo ligero y optimizado
                    </p>
                </div>
                <a href="{{ route('teams.dashboard', $team) }}" 
                   class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-xs font-bold uppercase tracking-widest text-gray-600 dark:text-gray-400 hover:bg-gray-50 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Volver') }}
                </a>
            </div>

            @if(session('success'))
                <div class="mb-8 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-100 dark:border-emerald-800 rounded-3xl flex items-center gap-4 animate-fade-in shadow-sm">
                    <div class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-white shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-emerald-800 dark:text-emerald-200 font-bold text-sm">{{ session('success') }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Columna Izquierda: Estadísticas -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Card Principal: Espacio Total -->
                    <div class="bg-gradient-to-br from-gray-900 to-black rounded-[3rem] p-8 text-white shadow-2xl relative overflow-hidden ring-1 ring-white/10">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-500 opacity-20 blur-[100px] rounded-full"></div>
                        <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-500 opacity-20 blur-[100px] rounded-full"></div>
                        
                        <div class="relative z-10">
                            <h3 class="text-xs font-black uppercase tracking-[0.3em] text-gray-400 mb-2">Espacio en disco utilizado</h3>
                            @php
                                $sizeStr = $stats['total_size']['readable_size'] ?? '0 B';
                                $parts = explode(' ', $sizeStr);
                                $value = $parts[0] ?? '0';
                                $unit = $parts[1] ?? 'B';
                                
                                // Formatear cuota para mostrar
                                $quotaGB = round($team->disk_quota / 1024 / 1024 / 1024, 1);
                                $percentage = $team->disk_usage_percentage;
                                $barColor = $percentage > 90 ? 'from-rose-500 to-red-600' : ($percentage > 70 ? 'from-amber-500 to-orange-600' : 'from-blue-500 via-indigo-500 to-purple-500');
                            @endphp

                            <div class="flex items-baseline gap-2 mb-6">
                                <span class="text-6xl font-black tracking-tighter">{{ $value }}</span>
                                <span class="text-2xl font-bold text-blue-400 uppercase">{{ $unit }}</span>
                            </div>

                            <!-- Barra de progreso real -->
                            <div class="space-y-2">
                                <div class="h-4 bg-white/5 rounded-full overflow-hidden border border-white/10 p-0.5">
                                    <div class="h-full bg-gradient-to-r {{ $barColor }} rounded-full shadow-[0_0_15px_rgba(59,130,246,0.3)] transition-all duration-1000" 
                                         style="width: {{ $percentage }}%"></div>
                                </div>
                                <div class="flex justify-between text-[10px] font-black uppercase tracking-widest text-gray-500">
                                    <span>Límite del Equipo: {{ $quotaGB }} GB</span>
                                    <span class="{{ $percentage > 90 ? 'text-rose-500 animate-pulse' : '' }} font-black">{{ $percentage }}% Utilizado</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desglose por carpetas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Telegram -->
                        <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-6 shadow-xl border border-gray-100 dark:border-gray-700 relative group transition-all hover:scale-[1.02]">
                            <div class="flex items-center justify-between mb-6">
                                <div class="w-12 h-12 rounded-2xl bg-sky-50 dark:bg-sky-900/30 flex items-center justify-center text-sky-500 shadow-inner">
                                    <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.35-.01-1.02-.2-1.53-.37-.6-.2-1.07-.31-1.03-.66.02-.18.27-.36.75-.55 2.94-1.28 4.9-2.13 5.88-2.54 2.8-.1.5.15.5.99c.01.26-.01.52-.06.78z"/></svg>
                                </div>
                                <span class="bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-tighter">Telegram Bot</span>
                            </div>
                            <h4 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tighter mb-1">Media Chat</h4>
                            <p class="text-sm text-gray-500 mb-6">Fotos, stickers y audios recibidos</p>
                            <div class="flex items-center justify-between border-t border-gray-50 dark:border-gray-700/50 pt-4">
                                <div class="text-center flex-1 border-r border-gray-50 dark:border-gray-700/50">
                                    <span class="block text-lg font-black text-gray-800 dark:text-white">{{ $stats['telegram']['count'] }}</span>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Archivos</span>
                                </div>
                                <div class="text-center flex-1">
                                    <span class="block text-lg font-black text-blue-500">{{ $stats['telegram']['readable_size'] }}</span>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Tamaño</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tareas -->
                        <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-6 shadow-xl border border-gray-100 dark:border-gray-700 relative group transition-all hover:scale-[1.02]">
                            <div class="flex items-center justify-between mb-6">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-500 shadow-inner">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <span class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-tighter">Gestión de Tareas</span>
                            </div>
                            <h4 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tighter mb-1">Documentación</h4>
                            <p class="text-sm text-gray-500 mb-6">Adjuntos subidos a tareas y proyectos</p>
                            <div class="flex items-center justify-between border-t border-gray-50 dark:border-gray-700/50 pt-4">
                                <div class="text-center flex-1 border-r border-gray-50 dark:border-gray-700/50">
                                    <span class="block text-lg font-black text-gray-800 dark:text-white">{{ $stats['attachments']['count'] }}</span>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Archivos</span>
                                </div>
                                <div class="text-center flex-1">
                                    <span class="block text-lg font-black text-blue-500">{{ $stats['attachments']['readable_size'] }}</span>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Tamaño</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Panel de Purga -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-[3rem] p-8 shadow-2xl border border-rose-100 dark:border-rose-900/20 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-5 rotate-12 scale-150">
                            <svg class="w-32 h-32 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>

                        <h3 class="text-2xl font-black text-gray-800 dark:text-white uppercase tracking-tighter mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-rose-500 flex items-center justify-center text-white text-base">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            Herramienta de Purga
                        </h3>

                        <form action="{{ route('teams.storage.purge', $team) }}" method="POST" onsubmit="return confirmPurge(this)">
                            @csrf
                            <div class="space-y-6">
                                <!-- Antigüedad -->
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3 ml-1">Antigüedad de los archivos</label>
                                    <select name="days" class="w-full bg-gray-50 dark:bg-gray-900 border-none rounded-2xl px-4 py-3 text-sm font-bold text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-rose-500 transition-all">
                                        <option value="7">Más de 7 días</option>
                                        <option value="15">Más de 15 días</option>
                                        <option value="30" selected>Más de 30 días (Recomendado)</option>
                                        <option value="90">Más de 3 meses</option>
                                        <option value="180">Más de 6 meses</option>
                                    </select>
                                </div>

                                <!-- Categorías -->
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4 ml-1">Categorías a limpiar</label>
                                    <div class="space-y-3">
                                        <label class="flex items-center gap-3 p-3 rounded-2xl border border-gray-50 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors cursor-pointer group">
                                            <input type="checkbox" name="types[]" value="telegram" checked class="w-5 h-5 rounded-lg text-rose-500 focus:ring-rose-500 border-gray-300">
                                            <div>
                                                <span class="block text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-tight">Media de Telegram</span>
                                                <span class="text-[10px] text-gray-400 font-medium">Fotos, audios y stickers</span>
                                            </div>
                                        </label>
                                        <label class="flex items-center gap-3 p-3 rounded-2xl border border-gray-50 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors cursor-pointer group">
                                            <input type="checkbox" name="types[]" value="attachments" class="w-5 h-5 rounded-lg text-rose-500 focus:ring-rose-500 border-gray-300">
                                            <div>
                                                <span class="block text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-tight">Adjuntos de Tareas</span>
                                                <span class="text-[10px] text-gray-400 font-medium italic">Se recomienda discreción</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="w-full py-4 bg-rose-500 hover:bg-rose-600 text-white rounded-3xl font-black uppercase tracking-widest shadow-xl shadow-rose-500/30 transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-3">
                                    <span>Iniciar Purga</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 7l5 5m0 0l-5 5m5-5H6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                
                                <p class="text-center text-[9px] font-bold text-gray-400 uppercase tracking-widest">
                                    Nota: Los registros de texto se conservarán
                                </p>
                            </div>
                        </form>
                    </div>

                    <!-- Card Informativa: Prevención -->
                    <div class="p-6 bg-amber-50 dark:bg-amber-900/20 rounded-[2rem] border border-amber-100 dark:border-amber-800/50">
                        <div class="flex gap-4">
                            <span class="text-amber-500 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </span>
                            <div>
                                <h5 class="text-sm font-black text-amber-800 dark:text-amber-300 uppercase tracking-tight mb-1">Tu Cuota de Equipo</h5>
                                <p class="text-[11px] text-amber-700/80 dark:text-amber-400/80 leading-relaxed font-medium">
                                    Este equipo tiene asignados <strong>{{ $quotaGB }} GB</strong> de almacenamiento. Al alcanzar el límite, los miembros no podrán subir nuevos archivos hasta que se libere espacio o se amplíe la cuota.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-bounce-subtle { animation: bounceSubtle 2s infinite ease-in-out; }
        @keyframes bounceSubtle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }
    </style>
    @push('scripts')
        <script>
            window.confirmPurge = function(form) {
                Swal.fire({
                    title: '{{ __('¿Confirmar Purga?') }}',
                    text: '{{ __('Esta acción eliminará permanentemente los archivos seleccionados del servidor. No se puede deshacer.') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f43f5e',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: '{{ __('Sí, iniciar purga') }}',
                    cancelButtonText: '{{ __('Cancelar') }}',
                    customClass: {
                        popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                        title: 'text-rose-600 dark:text-rose-400 font-black uppercase tracking-tighter pt-8 text-lg',
                        htmlContainer: 'text-sm font-medium text-slate-600 dark:text-slate-400 px-8 pb-4',
                        confirmButton: 'rounded-2xl px-6 py-3 shadow-lg shadow-rose-500/30 uppercase tracking-widest font-black text-[10px]',
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
        </script>
    @endpush
</x-app-layout>
