            <!-- Historial de cambios como Timeline -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="bg-gray-50/50 dark:bg-gray-800/50 px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('tasks.activity_history') ?? 'Historial de Actividad' }}
                    </h3>
                </div>
                <div class="p-6 custom-scrollbar" style="max-height: 280px; overflow-y: auto;">
                    <div class="relative ml-4 border-l-2 border-gray-100 dark:border-gray-800 pl-8 space-y-8">
                        @forelse (($task->histories?->sortByDesc('created_at') ?? collect())->take(10) as $log)
                            <div onclick="showHistoryDiff({{ $log->id }})" class="relative group cursor-pointer">
                                <!-- Dot -->
                                <div class="absolute -left-[41px] top-1 w-5 h-5 rounded-full border-4 border-white dark:border-gray-900 bg-violet-500 shadow-sm ring-4 ring-violet-50 dark:ring-violet-900/20 group-hover:scale-125 transition-transform"></div>
                                
                                <div class="bg-gray-50/50 dark:bg-gray-800/30 rounded-2xl p-4 border border-transparent group-hover:border-violet-100 dark:group-hover:border-violet-900/30 transition-all">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ $log->user?->name ?? 'Sistema' }}</span>
                                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-lg bg-violet-100 dark:bg-violet-900/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/50 shadow-sm">
                                                {{ $log->action_label ?? 'ACTUALIZACIÓN' }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-gray-400 font-bold tabular-nums">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <img src="{{ $log->user ? $log->user->profile_photo_url : 'https://ui-avatars.com/api/?name=S&color=7c3aed&background=f5f3ff' }}" 
                                                alt="{{ $log->user?->name ?? 'System' }}"
                                                class="w-6 h-6 rounded-full object-cover border border-white dark:border-gray-800 shadow-sm">
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Realizó cambios en los detalles de la tarea</p>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-300 dark:text-gray-600 group-hover:text-violet-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-10 text-center">
                                <div class="w-12 h-12 bg-gray-50 dark:bg-gray-800/50 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-400 italic">{{ __('tasks.no_history') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

