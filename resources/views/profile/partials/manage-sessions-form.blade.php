<section class="space-y-6">
    <header>
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2 heading">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 21h6l-.75-4M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            {{ __('Sesiones de Navegador Activas') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Gestiona y cierra tus sesiones activas en otros navegadores y dispositivos.') }}
        </p>
    </header>

    @if (session('status') === 'session-logged-out')
        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-sm rounded-xl border border-emerald-100 dark:border-emerald-900/50 font-medium">
            ✓ La sesión remota ha sido cerrada exitosamente.
        </div>
    @endif

    <div class="mt-6 space-y-4">
        @if (count($sessions) > 0)
            <div class="grid gap-4">
                @foreach ($sessions as $session)
                    <div class="flex items-center p-4 rounded-2xl border transition-all duration-200 {{ $session->is_current_device ? 'bg-indigo-50/50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-800/50 ring-1 ring-indigo-500/10' : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 hover:shadow-md' }}">
                        
                        <div class="shrink-0 text-gray-400 dark:text-gray-500 {{ $session->is_current_device ? 'text-indigo-500 dark:text-indigo-400' : '' }}">
                            @if ($session->agent->isDesktop)
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 21h6l-.75-4M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            @endif
                        </div>

                        <div class="ml-4 flex-grow min-w-0">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-200 flex items-center gap-2 truncate">
                                {{ $session->agent->platform }} - {{ $session->agent->browser }}
                                @if ($session->is_current_device)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 animate-pulse">
                                        Este dispositivo
                                    </span>
                                @endif
                            </div>

                            <div class="mt-0.5 flex items-center gap-x-3 text-xs text-gray-500 dark:text-gray-400 tabular-nums">
                                <span class="font-mono">{{ $session->ip_address }}</span>
                                <span>•</span>
                                <span>
                                    @if ($session->is_current_device)
                                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">En línea ahora</span>
                                    @else
                                        Última actividad: {{ $session->last_active }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="ml-4 flex items-center">
                            @if (!$session->is_current_device)
                                <form method="POST" action="{{ route('profile.sessions.logout', $session->id) }}" onsubmit="return confirm('¿Estás seguro de que deseas cerrar esta sesión de forma remota?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="tab" value="security">
                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/30 transition-all group" title="Cerrar sesión remotamente">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 italic">{{ __('No se encontraron otras sesiones activas.') }}</p>
        @endif
    </div>
</section>
