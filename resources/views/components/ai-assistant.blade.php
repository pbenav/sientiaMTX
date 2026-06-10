@props(['user' => auth()->user(), 'teamId' => null, 'taskId' => null, 'threadId' => null, 'messageId' => null])

<div x-data="sientiaAiAssistant()" 
     class="fixed z-[9999] flex flex-col items-start font-sans bottom-32 sm:bottom-24 left-4 pointer-events-none ai-assistant-container"
     :style="`transform: translate3d(${pos.x}px, ${pos.y}px, 0);`"
     @mousemove.window="drag($event)"
     @touchmove.window="drag($event)"
     @mouseup.window="stopDrag()"
     @touchend.window="stopDrag()"
     @ai:set-context.window="setContext($event.detail)"
     @ai:analyze-file.window="analyzeFile($event.detail)"
     @ai:analyze-task.window="analyzeTask($event.detail)"
     @ai:transfer-direct.window="transferToTask($event.detail)"
     @ai:smart-inject.window="smartInject($event.detail)"
     @ai:inject-note.window="injectNote($event.detail)"
     @ai:inject-microsite.window="injectMicrosite($event.detail)"
     @ai:inject-survey.window="injectSurvey($event.detail)"
     @quicknote-state-changed.window="quickNotesVisible = $event.detail.anyVisible">
    
    <!-- Chat Window -->
    <div 
        x-show="open" 
        x-cloak
        style="display: none;"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90 translate-y-10"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-90 translate-y-10"
        :style="`width: ${dimensions.width}px; height: ${dimensions.height}px; display: ${open ? 'flex' : 'none'} !important;`"
        class="mb-4 max-w-[95vw] max-h-[98vh] bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-gray-100 dark:border-gray-800 flex flex-col overflow-hidden ring-1 ring-black/5 pointer-events-auto relative"
    >
        <!-- Tirador de redimensionamiento -->
        <div class="absolute bottom-0 right-0 w-8 h-8 cursor-nwse-resize z-50 p-2 flex items-end justify-end opacity-30 hover:opacity-100 transition-opacity"
             @mousedown.stop.prevent="startResize($event)"
             @touchstart.stop.prevent="startResize($event)">
            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="18" y1="13" x2="13" y2="18" />
            </svg>
        </div>

        <!-- Header -->
        <div @mousedown="startDrag($event)" @touchstart="startDrag($event)" class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center cursor-grab active:cursor-grabbing shrink-0 shadow-lg relative z-30">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg tracking-tight leading-none">Asistente Ax.ia</h3>
                    <span class="text-[10px] text-indigo-200 font-medium uppercase tracking-widest mt-0.5 block">Sientia Open Source Lab</span>
                </div>
            </div>
            <div class="flex items-center space-x-1">
                <button @click="clearHistory()" 
                        class="p-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 text-indigo-200 hover:text-white rounded-lg transition-colors"
                        title="Limpiar historial">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>

                <!-- Undo Button -->
                <button x-show="canUndo" @click="undoLastAction()" 
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/40 rounded-lg transition-all text-xs font-medium border border-amber-200/50 dark:border-amber-800/50 animate-pulse"
                        title="Deshacer último cambio">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                    <span>Deshacer</span>
                </button>
                <button @click="soundEnabled = !soundEnabled; localStorage.setItem('ai_sound_enabled', soundEnabled ? '1' : '0')" 
                        class="p-2 hover:bg-white/10 rounded-full transition-colors" 
                        :class="soundEnabled ? 'text-white' : 'text-indigo-300'"
                        :title="soundEnabled ? 'Desactivar sonido' : 'Activar sonido'">
                    <svg x-show="soundEnabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>
                    <svg x-show="!soundEnabled" style="display:none;" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path></svg>
                </button>
                <button @click="showHelp = !showHelp" class="p-2 hover:bg-white/10 rounded-full transition-colors" title="Ayuda">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </button>
                <button @click="toggle()" class="p-2 hover:bg-white/10 rounded-full transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <!-- Help Panel (Overlay) -->
        <div x-show="showHelp" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
             class="absolute inset-x-0 bottom-0 top-[65px] z-50 bg-[#1e1b4b] p-8 text-white flex flex-col shadow-2xl" 
             style="display:none;">
            
            <div class="mb-6 flex justify-between items-start">
                <div>
                    <h4 class="text-xl font-bold text-white">¿Cómo te ayudo?</h4>
                    <div class="h-1 w-12 bg-indigo-400 rounded-full mt-2"></div>
                </div>
                <button @click="showHelp = false" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto space-y-6 pr-2 custom-scrollbar">
                <div class="flex gap-4">
                    <div class="w-10 h-10 shrink-0 bg-white/10 rounded-xl flex items-center justify-center text-xl">📝</div>
                    <div>
                        <div class="font-bold text-sm text-indigo-300 uppercase tracking-wider mb-1">Redacción</div>
                        <p class="text-xs text-gray-200 leading-relaxed">"Redacta una descripción profesional para esta tarea de diseño..."</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="w-10 h-10 shrink-0 bg-white/10 rounded-xl flex items-center justify-center text-xl">🧠</div>
                    <div>
                        <div class="font-bold text-sm text-indigo-300 uppercase tracking-wider mb-1">Simplificación</div>
                        <p class="text-xs text-gray-200 leading-relaxed">"Explícame este texto técnico de forma sencilla..."</p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="w-10 h-10 shrink-0 bg-white/10 rounded-xl flex items-center justify-center text-xl">🚀</div>
                    <div>
                        <div class="font-bold text-sm text-indigo-300 uppercase tracking-wider mb-1">Productividad</div>
                        <p class="text-xs text-gray-200 leading-relaxed">"Divide esta tarea compleja en pasos accionables."</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-6 border-t border-white/10">
                <p class="text-[10px] text-indigo-300 font-bold uppercase tracking-widest text-center mb-4">Próximamente: Integración con Drive</p>
                <button @click="showHelp = false" class="w-full py-4 bg-indigo-500 hover:bg-indigo-400 text-white rounded-2xl text-xs font-bold uppercase tracking-widest transition-all shadow-xl">
                    ¡Entendido!
                </button>
            </div>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 p-6 overflow-y-auto flex flex-col space-y-6 bg-gray-50/10 dark:bg-gray-950/20" id="ai-chat-messages">
            <!-- Status Badge -->
            <div class="flex justify-center mb-6 px-2">
                <div class="w-full px-3 py-2 bg-indigo-600/10 dark:bg-indigo-400/10 rounded-2xl border border-indigo-600/20 dark:border-indigo-400/20 flex flex-col items-center gap-1 shadow-sm backdrop-blur-md">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></div>
                        <span class="text-[9px] font-black uppercase tracking-wider text-indigo-700 dark:text-indigo-300">
                            Sintonizando Ax.ia
                        </span>
                    </div>
                    <div class="flex flex-wrap justify-center gap-x-3 gap-y-1 text-[8px] font-bold text-gray-500 uppercase tracking-tighter opacity-80 text-center">
                        <span class="flex items-center gap-1 shrink-0">📍 <span x-text="teamId ? 'Equipo ' + teamId : 'Global'"></span></span>
                        <span class="flex items-center gap-1 shrink-0">🤖 <span x-text="currentModel" class="max-w-[120px] truncate"></span></span>
                        <span class="flex items-center gap-1 shrink-0">⚡ <span x-text="currentModel.includes('flash') ? 'Turbo' : 'Ultra'"></span></span>
                    </div>
                </div>
            </div>
            <template x-for="(msg, index) in messages" :key="msg.id || ('local-' + index)">
                <div class="flex flex-col w-full">
                    <!-- Event / System Message -->
                    <template x-if="msg.role === 'system'">
                        <div class="flex flex-col items-center w-full my-2 mb-4">
                            <div class="self-center px-4 py-1.5 bg-gray-100 dark:bg-gray-800/50 rounded-full border border-gray-200 dark:border-gray-700 text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.15em] flex items-center gap-2 shadow-sm">
                                <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span x-html="renderMarkdown(msg.content)"></span>
                            </div>
                            
                            <!-- Attachment in system message -->
                            <template x-if="msg.file_url">
                                <div class="w-full max-w-[80%] mt-2 px-4 py-2 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-700/50">
                                    <template x-if="msg.file_type && msg.file_type.startsWith('audio/')">
                                        <audio controls class="w-full h-8" :src="msg.file_url"></audio>
                                    </template>
                                    <template x-if="!msg.file_type || !msg.file_type.startsWith('audio/')">
                                        <a :href="msg.file_url" target="_blank" class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-2">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span x-text="msg.file_name || 'Ver archivo'"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Bubble Message (User/AI) -->
                    <template x-if="msg.role !== 'system'">
                        <div class="flex flex-col w-full mb-8 last:mb-12">
                            <div :class="msg.role === 'user' ? 'self-end bg-indigo-600 text-white rounded-3xl rounded-tr-none shadow-indigo-500/20' : 'self-start bg-white dark:bg-gray-800 dark:text-gray-100 text-gray-800 rounded-3xl rounded-tl-none shadow-black/5 border border-gray-100 dark:border-gray-700/50'" 
                                 class="px-5 py-3.5 max-w-[90%] text-sm relative group shadow-xl transition-all">
                                <div x-html="renderMarkdown(msg.content)" 
                                     :class="msg.role === 'user' ? 'prose-invert text-white' : 'dark:prose-invert text-gray-800 dark:text-gray-100'" 
                                     class="leading-relaxed prose prose-sm max-w-none"></div>

                                <!-- Attachment Preview -->
                                <template x-if="msg.file_url">
                                    <div class="mt-4 pt-4 border-t border-white/20 dark:border-gray-700/50">
                                        <template x-if="msg.file_type && msg.file_type.startsWith('audio/')">
                                            <div class="flex flex-col gap-2">
                                                <div class="flex items-center gap-2 text-[10px] opacity-70">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                                                    <span x-text="msg.file_name || 'Audio grabado'"></span>
                                                </div>
                                                <audio controls class="w-full h-8 rounded-lg" :src="msg.file_url"></audio>
                                            </div>
                                        </template>
                                        <template x-if="!msg.file_type || !msg.file_type.startsWith('audio/')">
                                            <a :href="msg.file_url" target="_blank" 
                                               class="flex items-center gap-3 p-3 rounded-2xl bg-black/5 dark:bg-white/5 hover:bg-black/10 dark:hover:bg-white/10 transition-all border border-transparent hover:border-indigo-400/30 group/file">
                                                <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center text-indigo-500">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-[11px] font-bold truncate" x-text="msg.file_name || 'Descargar archivo'"></div>
                                                    <div class="text-[9px] opacity-60 uppercase tracking-widest font-black" x-text="msg.file_type || 'Archivo'"></div>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </template>

                                <!-- Quick Actions (User messages) -->
                                <template x-if="msg.role === 'user'">
                                    <div class="absolute -bottom-10 left-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="resendUserMessage(index)" 
                                                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-2 shadow-lg hover:scale-110 active:scale-95 transition-all text-indigo-500 dark:text-indigo-400" 
                                                title="Repetir envío">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                        </button>
                                        <button @click="editUserMessage(index)" 
                                                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-2 shadow-lg hover:scale-110 active:scale-95 transition-all text-amber-500 dark:text-amber-400" 
                                                title="Editar mensaje">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button @click="deleteUserMessage(index)" 
                                                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-2 shadow-lg hover:scale-110 active:scale-95 transition-all text-red-500 dark:text-red-400" 
                                                title="Eliminar mensaje">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </template>
                                
                                <!-- Quick Actions (Only for AI messages) -->
                                <template x-if="msg.role === 'ai'">
                                    <div class="absolute -bottom-10 right-0 flex items-center gap-2">
                                        <div class="flex space-x-1 text-sans">
                                            <button @click="copyToClipboard(msg.content)" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-2 shadow-lg hover:scale-110 active:scale-95 transition-all text-gray-500 dark:text-gray-300" title="Copiar al portapapeles">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                            </button>
                                            @if(auth()->user()->google_token)
                                            <button @click="saveToDrive(msg.content)" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-2 shadow-lg hover:scale-110 active:scale-95 transition-all text-blue-500" title="Guardar en Google Drive">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 48 48">
                                                    <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                                    <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                                    <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                                </svg>
                                            </button>
                                            @endif
                                        </div>

                                        <template x-if="msg.is_error">
                                            <div class="flex space-x-2">
                                                <button @click="retryMessage(index)" class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-red-600 text-white shadow-xl shadow-red-500/40 hover:bg-red-700 transition-all text-[10px] font-black uppercase tracking-tight" title="Reintentar ahora">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                    <span>Reintentar</span>
                                                </button>
                                                <button @click="recoverMessage(index)" class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-amber-500 text-white shadow-xl shadow-amber-500/40 hover:bg-amber-600 transition-all text-[10px] font-black uppercase tracking-tight" title="Rescatar mi texto">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                                                    <span>Rescatar</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <div x-show="loading" class="self-start bg-white dark:bg-gray-800 text-gray-800 rounded-3xl rounded-tl-none shadow-xl border border-gray-100 dark:border-gray-700/50 px-5 py-3.5 animate-in fade-in slide-in-from-left-4 duration-300">
                <div class="flex flex-col gap-2">
                    <div class="flex space-x-1.5 items-center h-5">
                        <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                    <div x-show="isSendingFile" class="text-[10px] font-bold text-gray-400 animate-pulse tracking-wide uppercase">
                        Procesando contenido multimedia...
                    </div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-3 sm:p-4 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
            <form @submit.prevent="sendMessage" class="flex items-center space-x-2 sm:space-x-3">
                <textarea 
                    x-model="input" 
                    x-ref="aiInput"
                    @paste="handlePaste($event)"
                    @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); sendMessage(); }"
                    rows="1"
                    class="flex-1 min-w-0 border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:border-indigo-500 focus:ring-0 rounded-2xl text-xs sm:text-sm py-2.5 sm:py-3 px-3 sm:px-5 shadow-inner resize-none overflow-y-auto max-h-32 transition-all"
                    :placeholder="isRecording ? 'Grabando...' : 'Pregunta...'" 
                    :disabled="loading || isRecording"
                    @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                ></textarea>
                
                <!-- Quick Multi-modal Actions -->
                <div class="flex items-center gap-0.5 sm:gap-1 shrink-0">
                    <!-- Audio Record -->
                    <button type="button" 
                            @click="toggleRecording"
                            class="p-2 sm:p-2.5 rounded-xl transition-all relative flex items-center justify-center group"
                            :class="isRecording ? 'bg-red-50 text-red-600 dark:bg-red-950/30' : 'text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30'"
                            :title="isRecording ? 'Detener' : 'Grabar'">
                        <svg x-show="!isRecording" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                        <div x-show="isRecording" class="flex items-center gap-1 sm:gap-2">
                            <span class="flex h-1.5 w-1.5 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-red-500"></span>
                            </span>
                            <span class="text-[9px] font-black font-mono" x-text="formatTime(recordingTime)"></span>
                        </div>
                    </button>

                    <!-- File Upload -->
                    <button type="button" 
                            @click="$refs.fileInput.click()"
                            class="p-2 sm:p-2.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-xl transition-all"
                            title="Adjuntar">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.414a4 4 0 00-5.656-5.656l-6.415 6.414a6 6 0 108.486 8.486L20.5 13"/></svg>
                    </button>
                    <input type="file" x-ref="fileInput" @change="handleFileUpload" class="hidden" accept="audio/*,image/*,application/pdf">
                </div>
                <button 
                    type="submit" 
                    class="bg-indigo-600 text-white rounded-xl sm:rounded-2xl p-2.5 sm:p-3 hover:bg-indigo-700 disabled:opacity-50 transition-all shadow-lg hover:shadow-indigo-500/30 active:scale-95 flex items-center justify-center cursor-pointer shrink-0"
                    :disabled="loading || input.trim() === ''"
                >
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Actions Row (Dynamic Layout: Vertical when closed, Horizontal when open) -->
    <div class="flex flex-col-reverse items-center gap-2 mb-3 pointer-events-auto transition-all duration-500"
         :class="open ? 'flex-row items-center gap-3' : 'flex-col-reverse items-center gap-2'">
        
        <!-- Main Toggle / Close Button -->
        <button 
            @mousedown="startDrag($event)" 
            @touchstart="startDrag($event)" 
            @click="toggle($event)"
            class="w-12 h-12 sm:w-14 sm:h-14 text-white rounded-full shadow-2xl backdrop-blur-sm transition-all flex items-center justify-center focus:outline-none ring-4 ring-white dark:ring-gray-950 active:scale-95 pointer-events-auto relative"
            :class="[
                open ? 'bg-red-500 hover:bg-red-600 rotate-0' : 'bg-indigo-600 hover:bg-indigo-700 rotate-0',
                isDragging ? 'cursor-grabbing scale-110' : 'cursor-grab hover:scale-110'
            ]"
            style="touch-action: none;"
        >
            <div class="relative flex items-center justify-center">
                <!-- Icono Cerrar (cuando está abierto) -->
                <svg x-show="open" x-cloak class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <!-- Icono IA (cuando está cerrado) -->
                <svg x-show="!open" x-cloak class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <!-- Loading Indicator -->
                <div x-show="loading" class="absolute -inset-2">
                    <svg class="animate-spin w-16 h-16 sm:w-20 sm:h-20 text-indigo-400 opacity-40" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
            <!-- Unread Indicator -->
            <div x-show="hasUnread && !open" style="display:none;" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 border-2 border-white dark:border-gray-950 rounded-full animate-bounce"></div>
        </button>

        <!-- Quick Notes Controls (Visible in both states) -->
        <div class="flex transition-all duration-500" :class="open ? 'flex-row items-center gap-3' : 'flex-col items-center gap-2'">
            <!-- Toggle All Notes Button -->
            <button type="button" 
                @click="window.dispatchEvent(new CustomEvent('quicknote-toggle-all', { bubbles: true }))"
                class="w-10 h-10 transition-all rounded-full shadow-lg flex items-center justify-center hover:scale-110 active:scale-95 ring-2 ring-white dark:ring-gray-950 backdrop-blur-md quick-notes-trigger pointer-events-auto"
                :class="quickNotesVisible ? 'bg-amber-500 text-white' : 'bg-gray-800/80 text-white hover:bg-gray-700'"
                :title="quickNotesVisible ? 'Ocultar todas las notas' : 'Ver todas las notas'">
                <svg x-show="quickNotesVisible" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg x-show="!quickNotesVisible" style="display:none;" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>
            </button>
            
            <!-- Create New Note Button -->
            <button type="button"
                @click="window.dispatchEvent(new CustomEvent('quicknote-create', { bubbles: true }))"
                class="w-10 h-10 bg-amber-400 hover:bg-amber-500 text-amber-900 rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-110 active:scale-95 ring-2 ring-white dark:ring-gray-950 pointer-events-auto"
                title="Nueva Nota Rápida (Post-it)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>
        </div>
    </div>

</div>

<script>
    (function() {
        const registerAssistant = () => {
            if (window.sientiaAiAssistantRegistered) return;
            window.sientiaAiAssistantRegistered = true;
            
            Alpine.data('sientiaAiAssistant', () => ({
            open: false,
            loading: false,
            hasUnread: false,
            soundEnabled: localStorage.getItem('ai_sound_enabled') !== '0',
            isSendingFile: false,
            showHelp: false,
            input: '',
            messages: [
                { role: 'ai', content: '¡Hola! Soy **Ax.ia**, tu asistente inteligente en Sientia Open Source Lab. ¿En qué puedo ayudarte con tus tareas hoy?' }
            ],
            
            teamId: @json($teamId ?? null),
            taskId: @json($taskId ?? null),
            threadId: @json($threadId ?? null),
            messageId: @json($messageId ?? null),
            quickNotesVisible: false,
            attachmentId: null,

            dimensions: { width: 420, height: 580 },
            isResizing: false,


            currentModel: 'Sincronizando...',
            canUndo: false,
            lastPrompt: '',
            lastFile: null,
            retryCount: 0,
            retrying: false,
            soothingInterval: null,
            soothingTexts: [
                "Esto está tomando un poco más de lo habitual debido a la carga de los servidores...",
                "Sigo aquí, procesando los datos para darte la mejor respuesta posible...",
                "Casi listo, las redes cuánticas están ajustando sus engranajes...",
                "La conexión parece estar algo lenta hoy, pero no me rindo..."
            ],

            // Notification Audio
            audio: new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3'),
            
            bottomPos: (window.innerWidth < 640) ? '8rem' : '6rem',
            
            // Undo State
            undoTimeout: null,
            lastActionData: null,
            
            // Audio Recording State
            isRecording: false,
            mediaRecorder: null,
            audioChunks: [],
            recordingTime: 0,
            maxRecordingTime: {{ (int)\App\Models\Setting::get('quick_notes_audio_max_duration', 30) }},
            recordingInterval: null,
            pendingFile: null,
            pendingReuseFilePath: null,
            pendingReuseFileName: null,
            pendingReuseAttachmentId: null,

            micrositeScaffoldCss: @json(app(\App\Services\Microsite\MicrositeContentService::class)->getBaseScaffoldCss()),

            cleanJson(content) {
                if (!content) return '';
                let sanitized = content.trim();
                // Fix 1: Bad backslashes
                sanitized = sanitized.replace(/\\(?!(["\\\/bfnrt]|u[0-9a-fA-F]{4}))/g, "\\\\");
                // Fix 2: Bad control characters (newlines/tabs) INSIDE string literals
                sanitized = sanitized.replace(/"([^"\\]*(\\.[^"\\]*)*)"/g, function(match) {
                    return match.replace(/\n/g, '\\n').replace(/\r/g, '\\r').replace(/\t/g, '\\t');
                });
                return sanitized;
            },

            init() {
                this.loadHistory();
                
                this.syncContext();

                this.lastFocusedEl = null;
                window.addEventListener('focusin', (e) => {
                    if (!e.target.closest('.ai-assistant-container') && 
                        !e.target.closest('.swal2-container') &&
                        (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT')) {
                        this.lastFocusedEl = e.target;
                    }
                }, true);

                window.addEventListener('quicknote-state-changed', (e) => {
                    this.quickNotesVisible = e.detail.anyVisible;
                });
            },

            async loadHistory() {
                try {
                    const response = await fetch(`{{ route('ai.history') }}?team_id=${this.teamId || ''}`);
                    const data = await response.json();
                    if (data.messages && data.messages.length > 0) {
                        console.log(`Ax.ia: Recuperados ${data.messages.length} mensajes del historial.`);
                        
                        // Re-evaluar is_error en tiempo de ejecución al cargar el historial
                        this.messages = data.messages.map(msg => {
                            if (msg.role === 'ai') {
                                const msgLower = (msg.content || '').toLowerCase();
                                msg.is_error = msgLower.includes('lo siento') || 
                                               msgLower.includes('error') || 
                                               msgLower.includes('no está disponible') || 
                                               msgLower.includes('⚠️');
                            }
                            return msg;
                        });

                        if (data.current_model) this.currentModel = data.current_model;
                        this.$nextTick(() => this.scrollToBottom());
                    } else {
                        console.log('Ax.ia: No se encontró historial previo para este contexto.');
                    }
                } catch (e) {
                    console.error('Ax.ia: Error cargando historial:', e);
                }
            },

            setContext(detail) {
                this.messageId = detail.messageId;
                this.attachmentId = null; // Clear attachment when setting forum context
                this.threadId = detail.threadId || this.threadId;
                this.taskId = detail.taskId || this.taskId;
                this.teamId = detail.teamId || this.teamId;
                this.open = true;
                
                // Add a system feedback message
                this.messages.push({ 
                    role: 'system', 
                    content: `Inyectando contexto: comentario de **${detail.userName}**`
                });

                this.input = `Háblame del comentario de ${detail.userName}...`;
                
                // Focus and SELECT the input
                this.$nextTick(() => {
                    const input = this.$refs.aiInput;
                    if (input) {
                        input.focus();
                        input.select();
                    }
                });
            },

            injectNote(detail) {
                this.open = true;
                this.messages.push({ 
                    role: 'system', 
                    content: `Inyectando contenido de nota rápida.`
                });
                
                this.input = `Sobre esta nota: "${detail.content}"\n\n¿Qué podemos hacer con ella?`;
                
                this.$nextTick(() => {
                    this.scrollToBottom();
                    const input = this.$refs.aiInput;
                    if (input) {
                        input.focus();
                        input.select();
                    }
                });
            },

            analyzeFile(detail) {
                this.open = true;
                this.attachmentId = detail.fileId;
                
                // Set context if provided
                if (detail.messageId) this.messageId = detail.messageId;
                if (detail.threadId) this.threadId = detail.threadId;
                if (detail.taskId) this.taskId = detail.taskId;
                if (detail.teamId) this.teamId = detail.teamId;
                
                // Add a system feedback message
                this.messages.push({ 
                    role: 'system', 
                    content: `📁 **Archivo inyectado:** ${detail.fileName}`,
                    file_url: detail.fileUrl,
                    file_name: detail.fileName,
                    file_type: detail.fileType
                });

                this.input = `Analiza el archivo "${detail.fileName}" y hazme un resumen de su contenido relevante para esta tarea.`;
                
                if (detail.taskId) this.taskId = detail.taskId;
                if (detail.teamId) this.teamId = detail.teamId;

                // Auto-trigger the analysis if requested
                if (detail.autoSubmit) {
                    this.$nextTick(() => this.sendMessage());
                } else {
                    // Focus and SELECT the input
                    this.$nextTick(() => {
                        const input = this.$refs.aiInput;
                        if (input) {
                            input.focus();
                            input.select();
                        }
                    });
                }
            },

            analyzeTask(detail) {
                this.open = true;
                if (detail.taskId) this.taskId = detail.taskId;
                if (detail.teamId) this.teamId = detail.teamId;
                this.attachmentId = null;
                this.messageId = null;
                
                this.messages.push({ 
                    role: 'system', 
                    content: `🎯 **Inyectando contexto de Tarea:** ${detail.taskTitle}`
                });

                if (detail.section === 'description') {
                    this.input = `Ayúdame a mejorar el breve resumen (descripción) de esta tarea. Hazlo más claro y directo.`;
                } else if (detail.section === 'observaciones') {
                    this.input = `Voy a desarrollar el meollo de esta tarea (las observaciones). Propón un esquema, lista de pasos o mejora lo que ya hay escrito.`;
                } else {
                    this.input = `Evalúa esta tarea en general. ¿Qué puedo mejorar en su definición o alcance?`;
                }
                
                // Focus and SELECT the input
                this.$nextTick(() => {
                    const input = this.$refs.aiInput;
                    if (input) {
                        input.focus();
                        input.select();
                    }
                });
            },

            injectSurvey(detail) {
                const jsonStr = detail.json;
                // Si ya estamos en la página de creación de encuestas, enviamos el evento para que la página lo procese
                if (window.location.pathname.includes('/surveys/create')) {
                    const surveyComp = document.querySelector('[x-data="surveyManager()"]');
                    if (surveyComp && surveyComp.__x && surveyComp.__x.$data) {
                        surveyComp.__x.$data.processImportedJSON(jsonStr);
                        this.open = false; // Cerramos el asistente para que vea la página
                    }
                } else {
                    // Si no estamos en la página, guardamos en localstorage y redirigimos
                    localStorage.setItem('ai_pending_survey_json', jsonStr);
                    window.location.href = this.teamId ? `/teams/${this.teamId}/surveys/create` : '/global-surveys/create';
                }
            },

            async clearHistory() {
                const result = await Swal.fire({
                    title: '¿Borrar historial?',
                    text: 'Esta acción eliminará todos los mensajes de este chat para el contexto actual.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Sí, borrar todo',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                        title: 'text-red-600 dark:text-red-400 font-black uppercase tracking-tighter pt-8 text-lg',
                        htmlContainer: 'text-sm font-medium text-slate-600 dark:text-slate-400 px-8 pb-4',
                        confirmButton: 'rounded-2xl px-6 py-3 shadow-lg shadow-red-500/30 uppercase tracking-widest font-black text-[10px]',
                        cancelButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]'
                    },
                    buttonsStyling: true
                });

                if (!result.isConfirmed) return;
                
                try {
                    await fetch('{{ route('ai.clear-history') }}', {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });
                    this.messages = [{ role: 'ai', content: 'Historial borrado. ¿En qué puedo ayudarte ahora?' }];
                } catch (e) {
                    console.error('Error clearing history:', e);
                }
            },
            
            // Drag variables
            pos: { x: 0, y: 0 },
            isDragging: false,
            wasDragged: false,
            startX: 0,
            startY: 0,

            startDrag(e) {
                this.isDragging = true;
                this.wasDragged = false;
                const event = e.type.includes('touch') ? e.touches[0] : e;
                this.startX = event.clientX - this.pos.x;
                this.startY = event.clientY - this.pos.y;
            },
            drag(e) {
                if (!this.isDragging && !this.isResizing) return;
                if (e.type.includes('touch') && e.cancelable) {
                    e.preventDefault();
                }
                
                const event = e.type.includes('touch') ? e.touches[0] : e;
                
                if (this.isDragging) {
                    const newX = event.clientX - this.startX;
                    const newY = event.clientY - this.startY;
                    
                    if (Math.abs(newX - this.pos.x) > 3 || Math.abs(newY - this.pos.y) > 3) {
                        this.wasDragged = true;
                    }
                    
                    this.pos.x = newX;
                    this.pos.y = newY;
                }
            },
            stopDrag() {
                setTimeout(() => { 
                    this.isDragging = false; 
                    this.isResizing = false;
                }, 50);
            },

            startResize(e) {
                this.isResizing = true;
                const event = e.type.includes('touch') ? e.touches[0] : e;
                const initialX = event.clientX;
                const initialY = event.clientY;
                const initialWidth = this.dimensions.width;
                const initialHeight = this.dimensions.height;
                const initialPosY = this.pos.y;
                
                const onMouseMove = (moveEvent) => {
                    if (!this.isResizing) return;
                    const mevent = moveEvent.type.includes('touch') ? moveEvent.touches[0] : moveEvent;
                    
                    const deltaX = mevent.clientX - initialX;
                    const deltaY = mevent.clientY - initialY;

                    this.dimensions.width = initialWidth + deltaX;
                    this.dimensions.height = initialHeight + deltaY;
                    
                    // CRITICAL: Ajustar pos.y para que crezca hacia abajo
                    this.pos.y = initialPosY + deltaY;
                    
                    // Límites
                    if (this.dimensions.width < 320) this.dimensions.width = 320;
                    if (this.dimensions.height < 400) {
                        this.dimensions.height = 400;
                        this.pos.y = initialPosY + (400 - initialHeight);
                    }
                };
                
                const onMouseUp = () => {
                    this.isResizing = false;
                    window.removeEventListener('mousemove', onMouseMove);
                    window.removeEventListener('mouseup', onMouseUp);
                    window.removeEventListener('touchmove', onMouseMove);
                    window.removeEventListener('touchend', onMouseUp);
                };
                
                window.addEventListener('mousemove', onMouseMove);
                window.addEventListener('mouseup', onMouseUp);
                window.addEventListener('touchmove', onMouseMove, { passive: false });
                window.addEventListener('touchend', onMouseUp);
            },

            
            toggle(e) {
                if (this.wasDragged) {
                    if (e) e.preventDefault();
                    this.wasDragged = false;
                    return;
                }
                this.open = !this.open;
                
                if (this.open) {
                    this.syncContext();
                    this.hasUnread = false;
                    localStorage.setItem('ai_assistant_open', '1');
                    this.$nextTick(() => {
                        this.scrollToBottom();
                        const input = this.$refs.aiInput;
                        if (input) input.focus();
                    });
                } else {
                    localStorage.setItem('ai_assistant_open', '0');
                }
            },

            // Audio Recording Methods
            async toggleRecording() {
                if (this.isRecording) {
                    this.stopRecording();
                } else {
                    await this.startRecording();
                }
            },

            async startRecording() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    
                    // Detect supported mime type
                    const mimeType = ['audio/webm', 'audio/mp4', 'audio/ogg', 'audio/wav']
                        .find(type => MediaRecorder.isTypeSupported(type)) || '';
                    
                    this.mediaRecorder = new MediaRecorder(stream, mimeType ? { mimeType } : {});
                    this.audioChunks = [];
                    this.recordingTime = this.maxRecordingTime; // Start from max for countdown

                    this.mediaRecorder.ondataavailable = (event) => {
                        this.audioChunks.push(event.data);
                    };

                    this.mediaRecorder.onstop = () => {
                        console.log("Ax.ia: Grabación detenida. Chunks:", this.audioChunks.length);
                        const finalMimeType = this.mediaRecorder.mimeType || 'audio/webm';
                        console.log("Ax.ia: MIME Type detectado:", finalMimeType);
                        
                        const extension = finalMimeType.includes('mp4') ? 'm4a' : 
                                         (finalMimeType.includes('webm') ? 'webm' : 
                                         (finalMimeType.includes('ogg') ? 'ogg' : 'wav'));
                        
                        try {
                            const audioBlob = new Blob(this.audioChunks, { type: finalMimeType });
                            this.pendingFile = new File([audioBlob], `recording_${new Date().getTime()}.${extension}`, { type: finalMimeType });
                            console.log("Ax.ia: Archivo creado:", this.pendingFile.name, this.pendingFile.size, "bytes");
                        } catch (e) {
                            console.error("Ax.ia: Error creando archivo de audio:", e);
                        }
                        
                        // Stop all tracks to release the microphone
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                        }
                        
                        // Trigger message send automatically for recordings
                        this.sendMessage();
                    };

                    this.mediaRecorder.start(1000); // Send data every second
                    this.isRecording = true;
                    
                    this.recordingInterval = setInterval(() => {
                        this.recordingTime--;
                        if (this.recordingTime <= 0) {
                            this.stopRecording();
                        }
                    }, 1000);

                } catch (err) {
                    console.error('Error al acceder al micrófono:', err);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Micrófono bloqueado', text: 'No se pudo acceder al micrófono. Por favor, revisa los permisos en tu navegador.', toast: true, position: 'top-end', showConfirmButton: false, timer: 4000 });
                    } else {
                        alert('No se pudo acceder al micrófono. Por favor, revisa los permisos.');
                    }
                }
            },

            stopRecording() {
                if (this.mediaRecorder && this.isRecording) {
                    this.mediaRecorder.stop();
                    this.isRecording = false;
                    clearInterval(this.recordingInterval);
                }
            },

            formatTime(seconds) {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            },

            handlePaste(e) {
                const items = (e.clipboardData || e.originalEvent.clipboardData).items;
                let found = false;
                for (let index in items) {
                    const item = items[index];
                    if (item.kind === 'file') {
                        const blob = item.getAsFile();
                        if (!blob) continue;
                        
                        const file = new File([blob], `pasted_file_${new Date().getTime()}.${blob.type.split('/')[1] || 'png'}`, { type: blob.type });
                        this.pendingFile = file;
                        const localUrl = URL.createObjectURL(file);
                        this.messages.push({ 
                            role: 'system', 
                            content: `📸 Archivo pegado del portapapeles: **${file.name}**`,
                            file_url: localUrl,
                            file_name: file.name,
                            file_type: file.type
                        });
                        
                        if (this.input.trim() === '') {
                            this.input = blob.type.startsWith('image/') ? 'Analiza esta imagen...' : 'Analiza este archivo...';
                            this.$nextTick(() => {
                                if (this.$refs.aiInput) {
                                    this.$refs.aiInput.focus();
                                    this.$refs.aiInput.select();
                                }
                            });
                        }
                        found = true;
                    }
                }
                // Si no es un archivo, dejamos que el evento de pegado de texto normal siga su curso
            },

            handleFileUpload(event) {
                const file = event.target.files[0];
                if (file) {
                    this.pendingFile = file;
                    const localUrl = URL.createObjectURL(file);
                    this.messages.push({ 
                        role: 'system', 
                        content: `📎 Archivo listo para enviar: **${file.name}**`,
                        file_url: localUrl,
                        file_name: file.name,
                        file_type: file.type
                    });
                    this.input = `Analiza este archivo...`;
                    this.$nextTick(() => {
                        if (this.$refs.aiInput) {
                            this.$refs.aiInput.focus();
                            this.$refs.aiInput.select();
                        }
                    });
                }
            },

            recoverMessage(index) {
                if (index <= 0) return;
                const prevMsg = this.messages[index - 1];
                if (!prevMsg || prevMsg.role !== 'user') return;
                this._loadUserMessageIntoInput(prevMsg);
            },

            retryMessage(index) {
                if (index <= 0) return;
                const prevMsg = this.messages[index - 1];
                if (!prevMsg || prevMsg.role !== 'user') return;
                this._loadUserMessageIntoInput(prevMsg);
                this.sendMessage();
            },

            _extractUserPlainText(msg) {
                let plainText = msg.content || '';
                plainText = plainText.replace(/^📁 \[Archivo:.*?\]\n\n/, '');
                plainText = plainText.replace(/^🎤 \[Grabación de audio\]$/, '');
                plainText = plainText.replace(/^📎 \[Archivo:.*?\]$/, '');
                return plainText.trim();
            },

            _loadUserMessageIntoInput(msg) {
                this.input = this._extractUserPlainText(msg);
                this.pendingFile = null;
                this.pendingReuseFilePath = (msg.file_path && !msg.task_attachment_id) ? msg.file_path : null;
                this.pendingReuseFileName = msg.file_name || null;
                this.pendingReuseAttachmentId = msg.task_attachment_id || null;

                this.$nextTick(() => {
                    const textarea = this.$refs.aiInput;
                    if (textarea) {
                        textarea.style.height = 'auto';
                        textarea.style.height = textarea.scrollHeight + 'px';
                        textarea.focus();
                        textarea.select();
                    }
                });
            },

            resendUserMessage(index) {
                const msg = this.messages[index];
                if (!msg || msg.role !== 'user' || this.loading) return;
                this._loadUserMessageIntoInput(msg);
                this.sendMessage();
            },

            async editUserMessage(index) {
                const msg = this.messages[index];
                if (!msg || msg.role !== 'user' || this.loading) return;

                this._loadUserMessageIntoInput(msg);
                await this._removeMessagePairAt(index, false);
            },

            async deleteUserMessage(index) {
                const msg = this.messages[index];
                if (!msg || msg.role !== 'user') return;

                const result = await Swal.fire({
                    title: '¿Eliminar mensaje?',
                    text: 'Se eliminará este mensaje y la respuesta de la IA asociada.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Eliminar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                        confirmButton: 'rounded-2xl px-6 py-3 shadow-lg shadow-red-500/30 uppercase tracking-widest font-black text-[10px]',
                        cancelButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]'
                    }
                });

                if (!result.isConfirmed) return;
                await this._removeMessagePairAt(index, true);
            },

            async _removeMessagePairAt(index, showFeedback = true) {
                const toDelete = [this.messages[index]];
                if (this.messages[index + 1]?.role === 'ai') {
                    toDelete.push(this.messages[index + 1]);
                }

                for (const msg of toDelete) {
                    if (msg.id) {
                        try {
                            await fetch(`{{ url('/ai/messages') }}/${msg.id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                }
                            });
                        } catch (e) {
                            console.error('Error eliminando mensaje:', e);
                        }
                    }
                }

                const removeCount = toDelete.length;
                this.messages.splice(index, removeCount);

                if (showFeedback) {
                    this.$nextTick(() => this.scrollToBottom());
                }
            },

            async sendMessage(isRetry = false) {
                if (!isRetry) {
                    this.syncContext(); // Safe final verify before dispatch
                    if (this.input.trim() === '' && !this.pendingFile && !this.pendingReuseFilePath) return;
                }
                
                const userText = this.input.trim();
                const fileToSend = this.pendingFile;
                const reuseFilePathToSend = this.pendingReuseFilePath;
                const reuseFileNameToSend = this.pendingReuseFileName;
                const reuseAttachmentIdToSend = this.pendingReuseAttachmentId;

                // SAVE FOR RETRY
                this.lastPrompt = userText;
                this.lastFile = fileToSend;

                let userMsgIndex = this.messages.length;

                if (!isRetry) {
                    if (fileToSend) {
                        const localUrl = URL.createObjectURL(fileToSend);
                        const isAudio = fileToSend.type.startsWith('audio/');
                        this.messages.push({ 
                            role: 'user', 
                            content: userText || (isAudio ? '🎤 [Grabación de audio]' : `📎 [Archivo: ${fileToSend.name}]`),
                            file_url: localUrl,
                            file_name: fileToSend.name,
                            file_type: fileToSend.type,
                            is_local: true
                        });
                    } else if (reuseFilePathToSend) {
                        this.messages.push({
                            role: 'user',
                            content: `📁 [Archivo: ${reuseFileNameToSend || 'Reutilizado'}]\n\n` + userText,
                            file_name: reuseFileNameToSend,
                            file_path: reuseFilePathToSend
                        });
                    } else if (userText) {
                        this.messages.push({ role: 'user', content: userText });
                    }
                } else {
                    userMsgIndex = this.messages.length - 1; // It's already there
                }

                this.loading = true;
                this.retrying = false;
                this.isSendingFile = !!fileToSend || !!reuseFilePathToSend;
                this.scrollToBottom();

                let soothingIndex = 0;
                if (this.soothingInterval) clearInterval(this.soothingInterval);
                this.soothingInterval = setInterval(() => {
                    if (this.loading && soothingIndex < this.soothingTexts.length) {
                        this.messages.push({
                            role: 'ai',
                            content: '⏳ *' + this.soothingTexts[soothingIndex] + '*',
                            is_soothing: true
                        });
                        this.scrollToBottom();
                        soothingIndex++;
                    }
                }, 10000); // 10 seconds

                try {
                    const formData = new FormData();
                    formData.append('prompt', userText);
                    formData.append('team_id', this.teamId || '');
                    formData.append('task_id', this.taskId || '');
                    formData.append('attachment_id', this.attachmentId || reuseAttachmentIdToSend || '');
                    formData.append('forum_thread_id', this.threadId || '');
                    formData.append('forum_message_id', this.messageId || '');
                    
                    if (fileToSend) {
                        formData.append('file', fileToSend);
                    } else if (reuseFilePathToSend) {
                        formData.append('reuse_file_path', reuseFilePathToSend);
                        formData.append('reuse_file_name', reuseFileNameToSend || '');
                    }

                    const response = await fetch('{{ route('ai.ask') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({}));
                        throw new Error(errorData.message || `Error del servidor (${response.status})`);
                    }
                    
                    const data = await response.json();
                    
                    // Determine if the returned message is actually an error message
                    const msgLower = (data.message || '').toLowerCase();
                    const isError = msgLower.includes('lo siento') || 
                                    msgLower.includes('error') || 
                                    msgLower.includes('no está disponible') || 
                                    msgLower.includes('⚠️');

                    // CLEAR INPUT ONLY ON SUCCESS
                    if (!isError) {
                        this.input = '';
                        this.pendingFile = null;
                        this.pendingReuseFilePath = null;
                        this.pendingReuseFileName = null;
                        this.pendingReuseAttachmentId = null;
                    } else {
                        this.lastPrompt = userText;
                        this.lastFile = fileToSend;
                    }

                if (data.user_message_id && this.messages[userMsgIndex]) {
                    this.messages[userMsgIndex].id = data.user_message_id;
                }

                this.messages.push({ 
                    role: 'ai', 
                    content: data.message,
                    is_error: isError,
                    id: data.ai_message_id || null
                });
                if (data.current_model) this.currentModel = data.current_model;
                this.retryCount = 0;

                // Notifications
                if (!this.open) {
                    this.hasUnread = true;
                    this.playNotification();
                } else {
                    this.playNotification(); // Also play if open, for feedback
                }
            } catch (error) {
                console.error('AI Assistant Error:', error);
                
                if (this.retryCount < 1) {
                    this.retryCount++;
                    this.retrying = true;
                    this.messages.push({
                        role: 'ai',
                        content: '🔄 Hubo un micro-corte o saturación. Reintentando la consulta de forma transparente...',
                        is_soothing: true
                    });
                    this.scrollToBottom();
                    setTimeout(() => this.sendMessage(true), 2000);
                } else {
                    // KEEP PROMPT ON ERROR but give feedback
                    this.messages.push({ 
                        role: 'ai', 
                        content: '⚠️ No se pudo procesar tu solicitud. El texto se ha mantenido en la caja de abajo. Detalle: ' + error.message,
                        is_error: true
                    });
                }
            } finally {
                if (!this.retrying) {
                    if (this.soothingInterval) clearInterval(this.soothingInterval);
                    this.messages = this.messages.filter(m => !m.is_soothing);
                    this.loading = false;
                    this.isSendingFile = false;
                    this.scrollToBottom();
                }
            }
        },

            syncContext() {
                // 1. Ultimate Source: Explicit DOM beacon (Highest reliability)
                const beacon = document.getElementById('sientia-active-task-beacon');
                if (beacon && beacon.dataset.taskId) {
                    this.taskId = beacon.dataset.taskId;
                }
                
                // 2. Secondary: Realtime Path Auditing (For auxiliary pages like edit/dashboard/forum)
                const path = window.location.pathname;
                const teamMatch = path.match(/\/teams\/(\d+)/);
                const taskMatch = path.match(/\/tasks\/(\d+)/);
                const forumMatch = path.match(/\/forum\/(\d+)/);

                if (teamMatch) this.teamId = teamMatch[1];
                if (taskMatch) this.taskId = taskMatch[1];
                if (forumMatch) this.threadId = forumMatch[1];
            },

            scrollToBottom() {
                setTimeout(() => {
                    const el = document.getElementById('ai-chat-messages');
                    if (el) el.scrollTop = el.scrollHeight;
                }, 100);
            },
            
            renderMarkdown(text) {
                if (!text) return '';
                
                // 1. Limpieza de respuestas JSON (Deep Research / Intent formats)
                // Si la respuesta es un JSON con "content", extraemos solo el contenido
                let cleanText = text.trim();
                if (cleanText.startsWith('{') && cleanText.includes('"content"')) {
                    try {
                        const parsed = JSON.parse(this.cleanJson(cleanText));
                        if (parsed.content) cleanText = parsed.content;
                    } catch (e) {}
                }

                // 2. Extracción de [PAYLOAD] para evitar que marked los rompa
                const payloads = [];
                let textWithPlaceholders = cleanText.replace(/\[PAYLOAD\]([\s\S]*?)\[\/PAYLOAD\]/g, (match, content) => {
                    const id = `[[[PAYLOAD_${payloads.length}]]]`;
                    payloads.push({
                        id: id,
                        html: this.generatePayloadCard(content.trim())
                    });
                    return id;
                });

                // 3. Renderizado de Markdown del texto restante
                let rendered;
                try {
                    rendered = marked.parse(textWithPlaceholders);
                } catch (e) {
                    rendered = textWithPlaceholders.replace(/\n/g, '<br>');
                }

                // 4. Re-inyección de las tarjetas de Payload protegidas
                payloads.forEach(p => {
                    rendered = rendered.replace(p.id, p.html);
                });

                return rendered;
            },

            buildMicrositePreviewSrcdoc(html, css) {
                const usesTailwind = /\bclass="[^"]*\b(bg-|text-|flex|grid|p-\d|rounded-|shadow-)/.test(html);
                const tailwindScript = usesTailwind ? '<script src="https://cdn.tailwindcss.com"><\/script>' : '';
                let wrappedHtml = html.includes('ms-root') ? html : `<div class="ms-root">${html}</div>`;
                const fullCss = (this.micrositeScaffoldCss || '') + '\n' + (css || '');
                const escAttr = (s) => String(s).replace(/"/g, '&quot;');
                const fsScript = '<script>document.addEventListener("click",function(e){var b=e.target.closest("[data-ms-fullscreen]");if(!b)return;var v=b.closest(".ms-pdf-viewer");if(!v)return;document.fullscreenElement?document.exitFullscreen():v.requestFullscreen();});<\/script>';
                return `${tailwindScript}<style>${escAttr(fullCss)}</style>${escAttr(wrappedHtml)}${fsScript}`;
            },

            generatePayloadCard(content) {
                try {
                    const sanitizedContent = this.cleanJson(content);
                    const data = JSON.parse(sanitizedContent);
                    
                    // 2.4 SPECIAL: SURVEY GENERATOR
                    if (data.intent === 'generate_survey') {
                        const surveyJson = JSON.stringify(data.survey_data, null, 2);
                        const numQuestions = data.survey_data && Array.isArray(data.survey_data) ? data.survey_data.length : 0;
                        
                        return `
                        <div class="group/payload my-6 relative transition-all duration-500">
                            <div class="absolute -inset-1 bg-gradient-to-r from-fuchsia-500 to-pink-600 rounded-[2.5rem] blur opacity-20 group-hover/payload:opacity-40 transition duration-1000"></div>
                            <div class="relative p-6 rounded-[2.5rem] bg-fuchsia-50/90 dark:bg-slate-900 border border-fuchsia-100 dark:border-slate-800 shadow-2xl backdrop-blur-xl">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-fuchsia-600 flex items-center justify-center text-white shadow-lg shadow-fuchsia-500/30">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-black uppercase tracking-widest text-fuchsia-600 dark:text-fuchsia-400">Encuesta generada por Ax.ia</span>
                                            <div class="text-[9px] text-slate-500 dark:text-slate-400 mt-0.5">${numQuestions} preguntas diseñadas</div>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-fuchsia-100 dark:bg-fuchsia-900/40 text-fuchsia-700 dark:text-fuchsia-300 text-[9px] font-black uppercase tracking-wider">● Lista</span>
                                </div>
                                
                                <div class="mt-3 relative">
                                    <pre class="bg-gray-900/95 text-fuchsia-400 p-4 rounded-2xl text-[10px] overflow-x-auto shadow-inner border border-gray-800 font-mono max-h-48 overflow-y-auto"><code>${surveyJson.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></pre>
                                </div>

                                <div class="mt-4 flex items-center justify-end gap-3 pt-4 border-t border-fuchsia-100/50 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-fuchsia-500/80 mr-auto uppercase tracking-tighter italic">Llevar al creador de encuestas</span>
                                    <button onclick="window.dispatchEvent(new CustomEvent('ai:inject-survey', { detail: { json: ${JSON.stringify(JSON.stringify(data.survey_data)).replace(/"/g, '&quot;')} } }))" 
                                            class="px-6 py-2.5 bg-fuchsia-600 hover:bg-fuchsia-700 text-white text-[10px] font-bold uppercase tracking-widest rounded-2xl transition-all shadow-lg active:scale-95 flex items-center gap-3">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        <span>Inyectar Encuesta</span>
                                    </button>
                                </div>
                            </div>
                        </div>`;
                    }

                    // 2.5 SPECIAL: MICROSITE GENERATOR
                    if (data.intent === 'generate_microsite') {
                        const htmlCode = data.html || '';
                        const cssCode = data.css || '';
                        const previewSrcdoc = this.buildMicrositePreviewSrcdoc(htmlCode, cssCode);
                        const htmlLines = htmlCode.split('\n').length;
                        const cssLines = cssCode.split('\n').length;
                        const uniqueId = 'ms_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
                        
                        return `
                        <div class="group/payload my-6 relative transition-all duration-500">
                            <div class="absolute -inset-1 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-[2.5rem] blur opacity-20 group-hover/payload:opacity-40 transition duration-1000"></div>
                            <div class="relative p-6 rounded-[2.5rem] bg-emerald-50/90 dark:bg-slate-900 border border-emerald-100 dark:border-slate-800 shadow-2xl backdrop-blur-xl">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/30">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Micrositio generado por Ax.ia</span>
                                            <div class="text-[9px] text-slate-500 dark:text-slate-400 mt-0.5">${htmlLines} líneas HTML · ${cssLines} líneas CSS</div>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-[9px] font-black uppercase tracking-wider">● Listo</span>
                                </div>
                                
                                <!-- Tabs HTML / CSS / Preview -->
                                <div x-data="{ tab: 'html' }" class="mt-3">
                                    <div class="flex gap-1 mb-3">
                                        <button @click="tab='html'" :class="tab==='html' ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'" class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all">&lt;/&gt; HTML</button>
                                        <button @click="tab='css'" :class="tab==='css' ? 'bg-blue-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'" class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all">🎨 CSS</button>
                                        <button @click="tab='preview'" :class="tab==='preview' ? 'bg-violet-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300'" class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all">👁 Preview</button>
                                    </div>
                                    <div x-show="tab==='html'" class="relative">
                                        <pre class="bg-gray-900/95 text-emerald-400 p-4 rounded-2xl text-[10px] overflow-x-auto shadow-inner border border-gray-800 font-mono max-h-48 overflow-y-auto"><code>${htmlCode.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></pre>
                                    </div>
                                    <div x-show="tab==='css'" class="relative" style="display:none;">
                                        <pre class="bg-gray-900/95 text-blue-400 p-4 rounded-2xl text-[10px] overflow-x-auto shadow-inner border border-gray-800 font-mono max-h-48 overflow-y-auto"><code>${cssCode.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></pre>
                                    </div>
                                    <div x-show="tab==='preview'" class="relative" style="display:none;">
                                        <iframe id="${uniqueId}" srcdoc="${previewSrcdoc}" class="w-full rounded-2xl border border-slate-200 dark:border-slate-700 bg-white" style="height:280px;" sandbox="allow-scripts allow-same-origin"></iframe>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-end gap-3 pt-4 border-t border-emerald-100/50 dark:border-slate-800">
                                    <span class="text-[9px] font-bold text-emerald-500/80 mr-auto uppercase tracking-tighter italic">Crear o inyectar micrositio</span>
                                    <button onclick="window.dispatchEvent(new CustomEvent('ai:inject-microsite', { detail: { html: ${JSON.stringify(htmlCode).replace(/"/g, '&quot;')}, css: ${JSON.stringify(cssCode).replace(/"/g, '&quot;')} } }))" 
                                            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-bold uppercase tracking-widest rounded-2xl transition-all shadow-lg active:scale-95 flex items-center gap-3">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        <span>Crear Nuevo Sitio</span>
                                    </button>
                                </div>
                            </div>
                        </div>`;
                    }

                    // 3. SPECIAL: SEARCH RESULTS CARD
                    if (data.intent === 'search_results') {
                        let html = `
                        <div class="group/payload my-6 relative transition-all duration-500">
                            <!-- Efecto de brillo/aura de fondo -->
                            <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500/20 to-purple-500/20 rounded-[2.5rem] blur opacity-10 group-hover/payload:opacity-30 transition duration-1000"></div>
                            
                            <div class="relative p-6 rounded-[2.5rem] bg-gradient-to-br from-indigo-50/90 to-white/90 dark:from-slate-800/90 dark:to-slate-900/90 border border-white/50 dark:border-slate-700/50 shadow-2xl backdrop-blur-xl overflow-hidden group">
                                <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl group-hover:bg-indigo-500/10 transition-colors"></div>
                                
                                <div class="flex items-center gap-4 mb-5">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">Resultados de Búsqueda</h4>
                                        <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400 mt-0.5">${data.query ? `Búsqueda: "${data.query}"` : 'Consulta finalizada'}</p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                        `;

                        if (data.results && (data.results.tasks?.length || data.results.threads?.length || data.results.message_matches?.length)) {
                            if (data.results.tasks?.length) {
                                html += `<div class="space-y-2">
                                    <div class="text-[9px] font-black uppercase tracking-tighter text-slate-400 mb-1">Tareas Encontradas</div>
                                    <div class="grid grid-cols-1 gap-2">`;
                                data.results.tasks.forEach(t => {
                                    html += `
                                    <a href="/teams/${t.team_id || this.teamId || '0'}/tasks/${t.id}" class="flex items-center gap-3 p-3 rounded-2xl bg-white/50 dark:bg-slate-800/50 hover:bg-white dark:hover:bg-slate-700 transition-all border border-slate-100 dark:border-slate-700/50 group/item">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 font-bold text-[10px]">#${t.id}</div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate">${t.title}</div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="px-1.5 py-0.5 rounded-md bg-slate-200/50 dark:bg-slate-700 text-[8px] font-black uppercase tracking-tighter text-slate-500">${t.status}</span>
                                            </div>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-300 group-hover/item:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                                    </a>`;
                                });
                                html += `</div></div>`;
                            }

                            if (data.results.threads?.length || data.results.message_matches?.length) {
                                html += `<div class="space-y-2">
                                    <div class="text-[9px] font-black uppercase tracking-tighter text-slate-400 mb-1">Foro y Mensajes</div>
                                    <div class="grid grid-cols-1 gap-2">`;
                                (data.results.threads || []).forEach(th => {
                                    html += `
                                    <a href="/teams/${th.team_id || this.teamId || '0'}/forum/${th.id}" class="flex items-center gap-3 p-3 rounded-2xl bg-amber-50/50 dark:bg-amber-900/10 hover:bg-amber-100/50 dark:hover:bg-amber-900/20 transition-all border border-amber-100/50 dark:border-amber-900/20 group/item">
                                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 font-bold text-[10px]">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-[11px] font-bold text-slate-800 dark:text-slate-200 truncate">${th.title}</div>
                                            <div class="text-[8px] text-amber-600/70 font-black uppercase mt-0.5">Hilo de conversación</div>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-300 group-hover/item:text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                                    </a>`;
                                });

                                (data.results.message_matches || []).forEach(m => {
                                    html += `
                                    <a href="/teams/${m.team_id || this.teamId || '0'}/forum/${m.thread_id}" class="flex items-start gap-3 p-3 rounded-2xl bg-white/30 dark:bg-slate-800/30 hover:bg-white/60 dark:hover:bg-slate-700/60 transition-all border border-slate-100/30 dark:border-slate-700/30 group/item">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mt-0.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 002 2v8a2 2 0 00-2 2h-3l-4 4z"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-[10px] font-bold text-slate-700 dark:text-slate-300 truncate mb-1">En el hilo: "${m.thread_title}"</div>
                                            <div class="text-[9px] text-slate-500 dark:text-slate-400 italic line-clamp-2 leading-relaxed">"...${m.snippet}..."</div>
                                        </div>
                                    </a>`;
                                });
                                html += `</div></div>`;
                            }
                        } else {
                            html += `<div class="p-8 text-center bg-slate-100/50 dark:bg-slate-800/50 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 text-[11px] font-bold text-slate-500 italic">No se han encontrado resultados.</div>`;
                        }

                        html += `</div></div></div>`;
                        return html;
                    }

                    // NORMAL SMART PAYLOAD
                    const highlights = [
                        { regex: /#(\d+)/g, class: 'bg-indigo-600 text-white px-1.5 py-0.5 rounded-md font-black shadow-sm' },
                        { regex: /\[(URGENTE|ALTA|MEDIA|BAJA)\]/g, class: 'bg-red-500 text-white px-2 py-0.5 rounded-full text-[8px] font-black shadow-md' },
                        { regex: /\[(PENDIENTE|EN PROCESO|COMPLETADA)\]/g, class: 'bg-emerald-500 text-white px-2 py-0.5 rounded-full text-[8px] font-black shadow-md' }
                    ];
                    let payloadContent = data.content || data.description || (data.task_data ? (data.task_data.description || data.task_data.observations || '') : '');
                    if (data.title || (data.task_data && data.task_data.title)) {
                        payloadContent = `**${data.title || data.task_data.title}**\n\n${payloadContent}`;
                    }
                    highlights.forEach(h => { payloadContent = payloadContent.replace(h.regex, `<span class="${h.class}">$1</span>`); });

                    return `
                    <div class="group/payload my-6 relative transition-all duration-500">
                        <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-[2.5rem] blur opacity-20 group-hover/payload:opacity-40 transition duration-1000"></div>
                        <div class="relative p-6 rounded-[2.5rem] bg-indigo-50/90 dark:bg-slate-900 border border-white/50 dark:border-slate-800 shadow-2xl backdrop-blur-xl">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg></div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-300">Smart Payload</span>
                                </div>
                            </div>
                            <div class="prose prose-sm dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 text-[13px] leading-relaxed font-medium">${marked.parse(payloadContent)}</div>
                            <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-indigo-100/50 dark:border-slate-800">
                                <span class="text-[9px] font-bold text-indigo-400/80 mr-auto uppercase tracking-tighter italic">Listo para inyectar</span>
                                <button onclick="window.dispatchEvent(new CustomEvent('ai:smart-inject', { detail: { content: ${JSON.stringify(sanitizedContent).replace(/"/g, '&quot;')} } }))" 
                                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold uppercase tracking-widest rounded-2xl transition-all shadow-lg active:scale-95 flex items-center gap-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    <span>Inyectar</span>
                                </button>
                            </div>
                        </div>
                    </div>`;
                } catch (e) {
                    // Silenciado para mantener la consola limpia, ya se maneja visualmente
                    return `<div class="p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-2xl text-xs border border-red-100">Error en Payload Inteligente: ${e.message}</div>`;
                }
            },

            renderPayloadPreview(content) {
                if (!content) return '';
                
                // Limpiador de texto para resaltar IDs de tareas y otros patrones
                const highlightPatterns = (text) => {
                    if (typeof text !== 'string') return text;
                    return text
                        .replace(/ID\s*(\d+)/gi, '<span class="px-2 py-0.5 bg-violet-100 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 font-black rounded-lg border border-violet-200 dark:border-violet-700/50 text-[10px]">#$1</span>')
                        .replace(/Estado:\s*([^\n,)]+)/gi, 'Estado: <span class="font-bold text-indigo-600 dark:text-indigo-400">$1</span>')
                        .replace(/Carga:\s*(\d\/\d)/gi, 'Carga: <span class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-md font-mono text-[9px] border border-amber-200 dark:border-amber-700/30">$1</span>');
                };

                // Si parece JSON, lo ponemos bonito
                if (content.trim().startsWith('{') || content.trim().startsWith('[')) {
                    try {
                        const obj = JSON.parse(this.cleanJson(content));
                        if (obj.content && obj.intent) {
                            return highlightPatterns(marked.parse(obj.content));
                        }
                        if (obj.title || obj.name) {
                            const desc = (obj.description || obj.content || '').substring(0, 150);
                            return `<div class="flex flex-col gap-1">
                                <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-tighter">Entidad Detectada</span>
                                <h4 class="text-base font-bold m-0 p-0 text-gray-900 dark:text-white leading-tight">${obj.title || obj.name}</h4>
                                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">${highlightPatterns(marked.parse(desc))}...</div>
                            </div>`;
                        }
                        return `<pre class="bg-gray-900/95 text-violet-400 p-4 rounded-2xl text-[10px] overflow-x-auto shadow-inner border border-gray-800 font-mono">${JSON.stringify(obj, null, 4)}</pre>`;
                    } catch (e) { /* Fallback */ }
                }

                try {
                    return highlightPatterns(marked.parse(content));
                } catch (e) {
                    return highlightPatterns(content.substring(0, 250)) + '...';
                }
            },

            copyToClipboard(text) {
                const match = text.match(/\[PAYLOAD\]([\s\S]*?)\[\/PAYLOAD\]/);
                const finalContent = match ? match[1].trim() : text.replace(/\[PAYLOAD\]|\[\/PAYLOAD\]/g, '').trim();

                navigator.clipboard.writeText(finalContent).then(() => {
                    const btn = event.currentTarget;
                    const oldHtml = btn.innerHTML;
                    btn.innerHTML = '<svg class="w-3 h-3 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
                    setTimeout(() => btn.innerHTML = oldHtml, 2000);
                });
            },
            
            async saveToDrive(content) {
                const match = content.match(/\[PAYLOAD\]([\s\S]*?)\[\/PAYLOAD\]/);
                const finalContent = match ? match[1].trim() : content.replace(/\[PAYLOAD\]|\[\/PAYLOAD\]/g, '').trim();
                const btn = event.currentTarget;
                const oldHtml = btn.innerHTML;
                btn.innerHTML = '<svg class="w-3.5 h-3.5 animate-spin text-blue-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83" stroke-width="2" stroke-linecap="round"/></svg>';
                
                try {
                    const response = await fetch('{{ route('google.drive.save-response') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ content: finalContent })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        btn.innerHTML = '<svg class="w-3.5 h-3.5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>';
                    } else {
                        btn.innerHTML = '<svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>';
                    }
                } catch (error) {
                    btn.innerHTML = '<svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>';
                } finally {
                    setTimeout(() => btn.innerHTML = oldHtml, 3000);
                }
            },

            async undoLastAction() {
                try {
                    const response = await fetch('{{ route('ai.undo') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.canUndo = false;
                        this.messages.push({ role: 'system', content: '🔄 ' + data.message });
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                        } else {
                            alert(data.message);
                        }
                    }
                } catch (e) {
                    console.error('Error undoing action:', e);
                }
            },

            async transferToTask(detail) {
                // 1. DATA PREPARATION
                let content = (typeof detail === 'string') ? detail : (detail.content || '');
                const isDirect = (typeof detail === 'object' && detail.direct);
                const isSilent = (typeof detail === 'object' && detail.silent);
                
                if (!isSilent) this.open = false;
                const isDark = document.documentElement.classList.contains('dark');
                
                const match = content.match(/\[PAYLOAD\]([\s\S]*?)\[\/PAYLOAD\]/);
                let rawPayload = (isDirect && !match) ? content : (match ? match[1].trim() : content.replace(/\[PAYLOAD\]|\[\/PAYLOAD\]/g, '').trim());

                // Limpiar posibles bloques de código Markdown de la respuesta
                rawPayload = rawPayload.replace(/^```[\w]*\n/, '').replace(/\n```$/, '').trim();

                let payloadData = {};
                let textToInject = rawPayload; 

                try {
                    payloadData = JSON.parse(this.cleanJson(rawPayload));
                    if (typeof payloadData === 'object' && payloadData !== null) {
                        // Normalize the new intent format into the flat format expected by the frontend UI
                        if (payloadData.intent === 'full_task' && payloadData.task_data) {
                            payloadData = payloadData.task_data;
                        } else if (payloadData.intent === 'simple_text') {
                            payloadData = { description: payloadData.content, observations: payloadData.content, title: '' };
                        }
                        
                        textToInject = payloadData.observations || payloadData.description || payloadData.content || payloadData.text || rawPayload;
                    }
                } catch (e) {
                    payloadData = { description: rawPayload, observations: rawPayload };
                }

                // 2. FAST PATH: DIRECT INJECTION
                let targetEl = null;
                
                if (isDirect) {
                    // Priority 1: Explicit target passed from Swal / UI interactive choice
                    if (typeof detail === 'object' && detail.targetId) {
                        const explicit = document.getElementById(detail.targetId);
                        if (explicit) {
                            targetEl = explicit;
                            console.log('[Ax.ia] Objetivo explícito seleccionado por el usuario:', detail.targetId);
                        }
                    }

                    // Priority 2: Focus tracking (Active element / Last focused element)
                    if (!targetEl) {
                        let tracked = this.lastFocusedEl;
                        if (!tracked || !document.body.contains(tracked)) {
                            tracked = document.activeElement;
                        }
                        if (tracked && (tracked.closest('.ai-assistant-container') || tracked.closest('.swal2-container'))) {
                            tracked = null;
                        }
                        if (tracked && (tracked.tagName === 'TEXTAREA' || tracked.tagName === 'INPUT')) {
                            targetEl = tracked;
                            console.log('[Ax.ia] Usando elemento con el foco activo real:', targetEl.id || targetEl.name || 'sin ID');
                        }
                    }

                    // Priority 3: Preferred fallback IDs scanning
                    if (!targetEl) {
                        let searchOrder = ['reply-content-private', 'reply-content', 'description', 'observations', 'private_note'];
                        if (payloadData.private_note) {
                            searchOrder = ['reply-content-private', 'private_note', 'description', 'observations'];
                        } else if (payloadData.description && !payloadData.observations) {
                            searchOrder = ['description', 'observations', 'reply-content-private', 'reply-content'];
                        }
                        
                        console.log('[Ax.ia] Ningún foco detectado. Buscando por ID predeterminado:', searchOrder);
                        for (const id of searchOrder) {
                            const el = document.getElementById(id);
                            if (el && (el.offsetParent !== null || el.closest('[x-data]'))) { 
                                targetEl = el;
                                console.log('[Ax.ia] Objetivo de respaldo encontrado:', id);
                                break;
                            }
                        }
                    }
                    
                    // Priority 4: Final fallback to generic editors
                    if (!targetEl) {
                        const anyEditor = document.querySelector('[id*="reply-content"], [id*="textarea"]');
                        if (anyEditor) {
                            targetEl = anyEditor;
                            console.log('[Ax.ia] Objetivo encontrado por selector de respaldo general:', anyEditor.id);
                        }
                    }

                    if (targetEl) {
                        // Visual Feedback: Pulse effect
                        targetEl.classList.add('ai-inject-pulse');
                        setTimeout(() => targetEl.classList.remove('ai-inject-pulse'), 1500);

                        // Support for Sientia Custom Markdown Editor (Alpine.js based)
                        const targetComponent = typeof Alpine !== 'undefined' ? Alpine.$data(targetEl.closest('[x-data]')) : null;
                        if (targetComponent && targetComponent.tab) {
                            targetComponent.tab = 'write';
                        }
                        
                        const isQuill = targetEl.classList.contains('ql-editor') || targetEl.closest('.ql-editor');
                        const actualEditor = targetEl.classList.contains('ql-editor') ? targetEl : targetEl.closest('.ql-editor');
                        const isSientiaEditor = targetComponent && (typeof targetComponent.insertAtCursor === 'function' || targetComponent.content !== undefined);

                        if (isQuill && actualEditor) {
                            actualEditor.innerHTML += marked.parse(textToInject);
                            actualEditor.dispatchEvent(new Event('input', { bubbles: true }));
                        } else if (isSientiaEditor) {
                            // If it's our custom editor, we use its native insertion method or direct content update
                            if (typeof targetComponent.insertAtCursor === 'function') {
                                targetComponent.insertAtCursor(textToInject);
                            } else {
                                targetComponent.content = (targetComponent.content || '') + textToInject;
                            }
                            // Also ensure the tab is switched to 'write'
                            if (targetComponent.tab) targetComponent.tab = 'write';
                        } else {
                            const start = targetEl.selectionStart || 0;
                            const end = targetEl.selectionEnd || 0;
                            const val = targetEl.value;
                            const newVal = val.substring(0, start) + textToInject + val.substring(end);
                            targetEl.value = newVal;
                            targetEl.dispatchEvent(new Event('input', { bubbles: true }));
                            targetEl.dispatchEvent(new Event('change', { bubbles: true }));
                            if (window.Alpine && targetEl._x_model) { targetEl._x_model.set(newVal); }
                        }
                        
                        targetEl.focus();
                        if (targetEl.scrollIntoView) { targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                        
                        if (!isSilent) Swal.fire({ icon: 'success', title: '¡Inyectado!', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                        return true;
                    }
                    return false;
                }
                return false;
            },

            async submitServerTransfer(target, rawContent, title = null) {
                const isDark = document.documentElement.classList.contains('dark');
                let url = '';
                
                // Re-verify taskId from URL if it's missing in state just before transfer
                if (!this.taskId) {
                    const taskMatch = window.location.pathname.match(/\/tasks\/(\d+)/);
                    if (taskMatch) this.taskId = taskMatch[1];
                }

                const isTaskSpecific = ['description', 'observations', 'private_note', 'observations_append'].includes(target);
                console.log(`[Ax.ia] Iniciando transferencia: ${target} para tarea ${this.taskId} en equipo ${this.teamId}`);

                if ((target === 'task' || target === 'quick-note' || !this.taskId) && !isTaskSpecific) {
                    url = '{{ route('ai.transfer_global', ['team' => 'TEAM_ID']) }}'
                        .replace('TEAM_ID', this.teamId || '');
                    url = url.replace(/\/$/, ""); 
                } else {
                    if (!this.taskId) {
                        const taskMatch = window.location.pathname.match(/\/tasks\/(\d+)/);
                        if (taskMatch) this.taskId = taskMatch[1];
                    }
                    
                    if (!this.taskId) {
                        Swal.fire('Error', 'No se ha podido detectar la tarea actual.', 'error');
                        return;
                    }

                    url = '{{ route('ai.transfer', ['team' => 'TEAM_ID', 'task' => 'TASK_ID']) }}'
                        .replace('TEAM_ID', this.teamId || '')
                        .replace('TASK_ID', this.taskId || '');
                }

                console.log(`[Ax.ia] URL final: ${url}`);

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ content: rawContent, target: target, title: title })
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.canUndo = true;
                        if (target === 'quick-note') window.dispatchEvent(new CustomEvent('quicknote-refresh'));

                        if (target === 'task' && data.task_id) {
                            let teamId = data.team_id || this.teamId || 0;
                            let editUrl = '{{ route('teams.tasks.edit', ['team' => 'TEAM_ID', 'task' => 'TASK_ID']) }}'.replace('TEAM_ID', teamId).replace('TASK_ID', data.task_id);
                            Swal.fire({ title: '¡Tarea Creada!', text: 'Redirigiendo...', icon: 'success', timer: 1500, showConfirmButton: false, background: isDark ? '#0f172a' : '#ffffff', customClass: { popup: 'rounded-[2rem]' } }).then(() => { window.location.href = editUrl; });
                        } else {
                            Swal.fire({ title: '¡Hecho!', text: data.message, icon: 'success', timer: 2000, showConfirmButton: false, background: isDark ? '#0f172a' : '#ffffff', customClass: { popup: 'rounded-[2rem]' } }).then(() => {
                                if (['description', 'observations', 'private_note', 'observations_append'].includes(target)) window.location.reload();
                            });
                        }
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (error) {
                    console.error('Transfer error:', error);
                    Swal.fire('Error', 'Problema de conexión.', 'error');
                }
            },


            async smartInject(detail) {
            const isDark = document.documentElement.classList.contains('dark');
            let content = (typeof detail === 'string') ? detail : (detail.content || '');
            const match = content.match(/\[PAYLOAD\]([\s\S]*?)\[\/PAYLOAD\]/);
            let rawPayload = (match ? match[1].trim() : content.replace(/\[PAYLOAD\]|\[\/PAYLOAD\]/g, '').trim());
            rawPayload = rawPayload.replace(/^```[\w]*\n/, '').replace(/\n```$/, '').trim();

            let firstLevelSelection = null;
            await Swal.fire({
            title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">¿Qué quieres hacer?</span>',
            background: isDark ? '#0f172a' : '#ffffff',
            showConfirmButton: false,
                    html: `
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-2">
            <button type="button" onclick="window._aiSelect1('new-task')" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-indigo-100 dark:border-indigo-900/30 bg-white dark:bg-slate-900 hover:border-indigo-600 transition-all text-center group">
            <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div class="font-black text-[10px] uppercase tracking-tighter text-gray-900 dark:text-white">Nueva Tarea</div>
            </button>
            <button type="button" onclick="window._aiSelect1('current-task')" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-violet-100 dark:border-violet-900/30 bg-white dark:bg-slate-900 hover:border-violet-600 transition-all text-center group">
            <div class="w-12 h-12 rounded-2xl bg-violet-100 dark:bg-violet-900/50 flex items-center justify-center text-violet-600 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            </div>
            <div class="font-black text-[10px] uppercase tracking-tighter text-gray-900 dark:text-white">Tarea Actual</div>
            </button>
            <button type="button" onclick="window._aiSelect1('quick-note')" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-amber-100 dark:border-amber-900/30 bg-white dark:bg-slate-900 hover:border-amber-600 transition-all text-center group">
            <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 7h.01M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
            </div>
            <div class="font-black text-[10px] uppercase tracking-tighter text-gray-900 dark:text-white">Nota Post-it</div>
            </button>
            <button type="button" onclick="window._aiSelect1('active-editor')" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-emerald-100 dark:border-emerald-900/30 bg-white dark:bg-slate-900 hover:border-emerald-600 transition-all text-center group">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="font-black text-[10px] uppercase tracking-tighter text-gray-900 dark:text-white">Editor Activo</div>
            </button>
            <button type="button" onclick="window._aiSelect1('new-survey')" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-blue-100 dark:border-blue-900/30 bg-white dark:bg-slate-900 hover:border-blue-600 transition-all text-center group">
            <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div class="font-black text-[10px] uppercase tracking-tighter text-gray-900 dark:text-white">Nueva Encuesta</div>
            </button>
            <button type="button" onclick="window._aiSelect1('microsite')" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-pink-100 dark:border-pink-900/30 bg-white dark:bg-slate-900 hover:border-pink-600 transition-all text-center group">
            <div class="w-12 h-12 rounded-2xl bg-pink-100 dark:bg-pink-900/50 flex items-center justify-center text-pink-600 group-hover:scale-110 transition-transform">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
            </div>
            <div class="font-black text-[10px] uppercase tracking-tighter text-gray-900 dark:text-white">Micrositio</div>
            </button>
            </div>
            `,
            didOpen: () => {
            window._aiSelect1 = (val) => {
            firstLevelSelection = val;
            Swal.clickConfirm();
            };
            }
            });

            if (!firstLevelSelection) return;

            if (firstLevelSelection === 'new-task') {
            this.submitServerTransfer('task', rawPayload);
            } else if (firstLevelSelection === 'quick-note') {
            this.submitServerTransfer('quick-note', rawPayload);
            } else if (firstLevelSelection === 'active-editor') {
            const success = await this.transferToTask({ content: rawPayload, direct: true, silent: false });
            if (!success) Swal.fire('Aviso', 'No hay editor activo.', 'info');
            } else if (firstLevelSelection === 'new-survey') {
            localStorage.setItem('ai_pending_survey_json', rawPayload);
            let teamId = this.teamId || 0;
            let surveyUrl = teamId ? `/teams/${teamId}/surveys/create` : `/global-surveys/create`;
            window.location.href = surveyUrl;
            } else if (firstLevelSelection === 'microsite') {
                let micrositeData = { html: rawPayload, css: '' };
                try {
                    const parsed = JSON.parse(rawPayload);
                    if (parsed.html !== undefined || parsed.css !== undefined) {
                        micrositeData = { html: parsed.html || '', css: parsed.css || '' };
                    }
                } catch (e) { /* si no es JSON, lo tratamos como HTML puro */ }
                this.injectMicrosite(micrositeData);
            } else if (firstLevelSelection === 'current-task') {
            if (!this.taskId) {
                        const taskMatch = window.location.pathname.match(/\/tasks\/(\d+)/);
            if (taskMatch) this.taskId = taskMatch[1];
            }
            if (!this.taskId) {
            Swal.fire('Error', 'No se detecta tarea actual.', 'error');
            return;
            }

            let secondLevelSelection = null;
            await Swal.fire({
            title: '<span class="text-xs font-black uppercase tracking-widest text-violet-600">¿En qué parte de la tarea?</span>',
            background: isDark ? '#0f172a' : '#ffffff',
            showConfirmButton: false,
                        html: `
                            <div class="grid grid-cols-1 gap-3 p-2">
            <button type="button" onclick="window._aiSelect2('description')" class="flex items-center gap-4 p-4 rounded-[1.8rem] border-2 border-indigo-100 dark:border-indigo-900/30 bg-white dark:bg-slate-900 hover:border-indigo-600 transition-all text-left group">
            <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="font-black text-[11px] uppercase tracking-tight text-gray-900 dark:text-white">Resumen</div>
            </button>
            <button type="button" onclick="window._aiSelect2('observations')" class="flex items-center gap-4 p-4 rounded-[1.8rem] border-2 border-violet-100 dark:border-violet-900/30 bg-white dark:bg-slate-900 hover:border-violet-600 transition-all text-left group">
            <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center text-violet-600 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </div>
            <div class="font-black text-[11px] uppercase tracking-tight text-gray-900 dark:text-white">Desarrollo</div>
            </button>
            <button type="button" onclick="window._aiSelect2('private_note')" class="flex items-center gap-4 p-4 rounded-[1.8rem] border-2 border-slate-100 bg-white dark:bg-slate-900 hover:border-slate-600 transition-all text-left group">
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 group-hover:scale-110 transition-transform">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div class="font-black text-[11px] uppercase tracking-tight text-gray-900 dark:text-white">Nota Privada</div>
            </button>
            </div>
            `,
            didOpen: () => {
            window._aiSelect2 = (val) => {
            secondLevelSelection = val;
            Swal.clickConfirm();
            };
            }
            });

                    if (secondLevelSelection) {
                        // Inteligente: Si el usuario está editando la tarea y el campo está presente en el DOM actual,
                        // inyectamos directamente sobre el editor/input en pantalla en lugar de guardarlo directamente en base de datos.
                        const targetField = document.getElementById(secondLevelSelection);
                        if (targetField && (targetField.offsetParent !== null || targetField.closest('[x-data]'))) {
                            console.log(`[Ax.ia] Inyectando directamente en el campo presente en el DOM: ${secondLevelSelection}`);
                            this.transferToTask({ content: rawPayload, direct: true, silent: false, targetId: secondLevelSelection });
                        } else {
                            console.log(`[Ax.ia] Campo ${secondLevelSelection} no detectado en DOM visible. Ejecutando persistencia en servidor.`);
                            this.submitServerTransfer(secondLevelSelection, rawPayload);
                        }
                    }
                }
            },

            injectMicrosite(detail) {
                const htmlField = document.getElementById('html_content');
                const cssField = document.getElementById('css_content');
                
                // Modo 1: Si estamos en el editor de un micrositio, inyectamos directamente
                if (htmlField || cssField) {
                    if (htmlField) {
                        htmlField.value = detail.html || '';
                        htmlField.dispatchEvent(new Event('input', { bubbles: true }));
                        // Soporte Alpine.js
                        if (window.Alpine && htmlField._x_model) htmlField._x_model.set(detail.html || '');
                    }
                    if (cssField) {
                        cssField.value = detail.css || '';
                        cssField.dispatchEvent(new Event('input', { bubbles: true }));
                        if (window.Alpine && cssField._x_model) cssField._x_model.set(detail.css || '');
                    }

                    Swal.fire({
                        icon: 'success',
                        title: '¡Código Inyectado!',
                        text: 'El HTML y CSS han sido inyectados en los campos del micrositio.',
                        timer: 3000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'bottom-end'
                    });
                    return;
                }

                // Modo 2: No estamos en el editor → crear micrositio "al vuelo" vía AJAX
                const isDark = document.documentElement.classList.contains('dark');
                const htmlLines = (detail.html || '').split('\n').length;
                const cssLines = (detail.css || '').split('\n').length;

                Swal.fire({
                    title: 'Crear Nuevo Micrositio',
                    html: `
                        <div style="text-align:left; font-size:13px; line-height:1.7;">
                            <p style="margin-bottom:12px;">No estás en el editor de un micrositio. ¿Quieres crear uno nuevo con este contenido?</p>
                            <div style="display:flex; gap:12px; margin-bottom:16px;">
                                <div style="flex:1; padding:12px; border-radius:12px; background:${isDark ? '#1e293b' : '#f0fdf4'}; border:1px solid ${isDark ? '#334155' : '#bbf7d0'};">
                                    <div style="font-weight:800; font-size:10px; text-transform:uppercase; color:#059669; letter-spacing:0.05em;">HTML</div>
                                    <div style="font-size:18px; font-weight:900; color:${isDark ? '#fff' : '#111'};">${htmlLines} líneas</div>
                                </div>
                                <div style="flex:1; padding:12px; border-radius:12px; background:${isDark ? '#1e293b' : '#eff6ff'}; border:1px solid ${isDark ? '#334155' : '#bfdbfe'};">
                                    <div style="font-weight:800; font-size:10px; text-transform:uppercase; color:#2563eb; letter-spacing:0.05em;">CSS</div>
                                    <div style="font-size:18px; font-weight:900; color:${isDark ? '#fff' : '#111'};">${cssLines} líneas</div>
                                </div>
                            </div>
                            <label style="font-weight:700; font-size:12px; display:block; margin-bottom:4px;">Título del micrositio:</label>
                            <input id="swal-microsite-title" class="swal2-input" style="width:100%; font-size:13px;" placeholder="Mi Micrositio Ax.ia" value="Micrositio Ax.ia - ${new Date().toLocaleDateString('es-ES')}">
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '🚀 Crear Micrositio',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#059669',
                    showLoaderOnConfirm: true,
                    preConfirm: async () => {
                        const title = document.getElementById('swal-microsite-title')?.value || 'Micrositio Ax.ia';
                        try {
                            const response = await fetch('{{ route("ai.microsites.quick-create") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    html: detail.html || '',
                                    css: detail.css || '',
                                    title: title,
                                    team_id: this.teamId || null
                                })
                            });
                            const data = await response.json();
                            if (!response.ok) {
                                throw new Error(data.message || 'Error del servidor');
                            }
                            return data;
                        } catch (error) {
                            Swal.showValidationMessage('Error: ' + error.message);
                        }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed && result.value?.success) {
                        const data = result.value;
                        Swal.fire({
                            icon: 'success',
                            title: '¡Micrositio Creado!',
                            html: `
                                <div style="text-align:left; font-size:13px;">
                                    <p><strong>${data.microsite.title}</strong> ha sido creado en el equipo <strong>${data.team.name}</strong>.</p>
                                    <p style="margin-top:8px; font-size:11px; color:#6b7280;">Slug: <code>${data.microsite.slug}</code></p>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: '✏️ Ir al Editor',
                            cancelButtonText: 'Cerrar',
                            confirmButtonColor: '#059669',
                        }).then((editResult) => {
                            if (editResult.isConfirmed && data.edit_url) {
                                window.location.href = data.edit_url;
                            }
                        });
                    }
                });
            },

            playNotification() {
                if (!this.soundEnabled) return;
                this.audio.play().catch(e => console.log('Audio playback failed', e));
            }
        }));
        };

        if (window.Alpine) {
            registerAssistant();
        } else {
            document.addEventListener('alpine:init', registerAssistant);
        }
    })();
</script>
