@props(['user' => auth()->user(), 'teamId' => null, 'taskId' => null, 'threadId' => null, 'messageId' => null])

<div x-data="sientiaAiAssistant()" 
     class="fixed z-[9999] flex flex-col items-start font-sans bottom-32 sm:bottom-24 left-4 pointer-events-none"
     :style="`transform: translate3d(${pos.x}px, ${pos.y}px, 0);`"
     @mousemove.window="drag($event)"
     @touchmove.window="drag($event)"
     @mouseup.window="stopDrag()"
     @touchend.window="stopDrag()"
     @ai:set-context.window="setContext($event.detail)"
     @ai:analyze-file.window="analyzeFile($event.detail)">
    
    <!-- Chat Window -->
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90 translate-y-10"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-90 translate-y-10"
        style="display:none; resize: both; overflow: hidden; min-width: 280px; min-height: 400px;"
        class="mb-4 w-[calc(100vw-2rem)] sm:w-[420px] h-[580px] max-h-[85vh] bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-gray-100 dark:border-gray-800 flex flex-col overflow-hidden ring-1 ring-black/5 pointer-events-auto"
    >
        <!-- Header -->
        <div class="bg-indigo-600 px-6 py-4 text-white flex justify-between items-center cursor-default shrink-0 shadow-lg relative z-30">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg tracking-tight leading-none">Asistente Ax.ia</h3>
                    <span class="text-[10px] text-indigo-200 font-medium uppercase tracking-widest mt-0.5 block">Inteligencia Artificial Sientia</span>
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
            <template x-for="(msg, index) in messages" :key="index">
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
                            
                            <!-- Quick Actions (Only for AI messages) -->
                            <template x-if="msg.role === 'ai'">
                                <div class="absolute -bottom-10 right-0 flex space-x-1 text-sans">
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
                                    
                                    <button @click="transferToTask(msg.content)" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-2 shadow-lg hover:scale-110 active:scale-95 transition-all text-violet-600" title="Inyectar en tarea">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                    </button>
                                </div>
                            </template>
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
                <input 
                    x-model="input" 
                    @paste="handlePaste($event)"
                    type="text" 
                    class="flex-1 min-w-0 border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:border-indigo-500 focus:ring-0 rounded-2xl text-xs sm:text-sm py-2.5 sm:py-3 px-3 sm:px-5 shadow-inner"
                    :placeholder="isRecording ? 'Grabando...' : 'Pregunta...'" 
                    :disabled="loading || isRecording"
                >
                
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

    <button 
        @mousedown="startDrag($event)" 
        @touchstart="startDrag($event)" 
        @click="toggle($event)"
        class="w-12 h-12 sm:w-14 sm:h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full shadow-2xl backdrop-blur-sm transition-all flex items-center justify-center focus:outline-none ring-4 ring-white dark:ring-gray-950 active:scale-95 pointer-events-auto"
        :class="isDragging ? 'cursor-grabbing scale-110' : 'cursor-grab hover:scale-110'"
        style="touch-action: none;"
    >
        <svg x-show="!open" class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        <svg x-show="open" style="display:none;" class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sientiaAiAssistant', () => ({
            open: false,
            loading: false,
            isSendingFile: false,
            input: '',
            messages: [
                { role: 'ai', content: '¡Hola! Soy **Ax.ia**, tu asistente inteligente en Sientia. ¿En qué puedo ayudarte con tus tareas hoy?' }
            ],
            
            teamId: {{ $teamId ?: 'null' }},
            taskId: {{ $taskId ?: 'null' }},
            attachmentId: null,
            threadId: {{ $threadId ?: 'null' }},
            messageId: {{ $messageId ?: 'null' }},
            bottomPos: (window.innerWidth < 640) ? '8rem' : '6rem',
            showHelp: false,
            currentModel: 'Sincronizando...',

            // Undo State
            canUndo: false,
            undoTimeout: null,
            lastActionData: null,

            // Audio Recording State
            isRecording: false,
            mediaRecorder: null,
            audioChunks: [],
            recordingTime: 0,
            recordingInterval: null,
            pendingFile: null,

            init() {
                this.loadHistory();
                // Persistencia desactivada por petición del usuario
                // if (localStorage.getItem('ai_assistant_open') === '1') { this.open = true; }
            },

            async loadHistory() {
                try {
                    const response = await fetch(`{{ route('ai.history') }}?team_id=${this.teamId || ''}`);
                    const data = await response.json();
                    if (data.messages && data.messages.length > 0) {
                        console.log(`Ax.ia: Recuperados ${data.messages.length} mensajes del historial.`);
                        this.messages = data.messages;
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
                    const input = this.$el.querySelector('input[type="text"]');
                    if (input) {
                        input.focus();
                        input.select();
                    }
                });
            },

            analyzeFile(detail) {
                this.open = true;
                this.attachmentId = detail.fileId;
                this.messageId = null; // Clear forum context
                
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
                        const input = this.$el.querySelector('input[type="text"]');
                        if (input) {
                            input.focus();
                            input.select();
                        }
                    });
                }
            },

            async clearHistory() {
                if (!confirm('¿Seguro que quieres borrar todo el historial de este chat?')) return;
                
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
                if (this.open) return;
                this.isDragging = true;
                this.wasDragged = false;
                const event = e.type.includes('touch') ? e.touches[0] : e;
                this.startX = event.clientX - this.pos.x;
                this.startY = event.clientY - this.pos.y;
            },
            drag(e) {
                if (!this.isDragging) return;
                if (e.type.includes('touch') && e.cancelable) {
                    e.preventDefault();
                }
                
                const event = e.type.includes('touch') ? e.touches[0] : e;
                const newX = event.clientX - this.startX;
                const newY = event.clientY - this.startY;
                
                if (Math.abs(newX - this.pos.x) > 3 || Math.abs(newY - this.pos.y) > 3) {
                    this.wasDragged = true;
                }
                
                this.pos.x = newX;
                this.pos.y = newY;
            },
            stopDrag() {
                setTimeout(() => { this.isDragging = false; }, 50);
            },
            
            toggle(e) {
                if (this.wasDragged) {
                    if (e) e.preventDefault();
                    this.wasDragged = false;
                    return;
                }
                this.open = !this.open;
                
                if (!this.open) {
                    localStorage.setItem('ai_assistant_open', '0');
                } else {
                    localStorage.setItem('ai_assistant_open', '1');
                    this.$nextTick(() => {
                        this.scrollToBottom();
                        // Find the text input and focus it
                        const input = this.$el.querySelector('input[type="text"]');
                        if (input) input.focus();
                    });
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
                    this.mediaRecorder = new MediaRecorder(stream);
                    this.audioChunks = [];
                    this.recordingTime = 0;

                    this.mediaRecorder.ondataavailable = (event) => {
                        this.audioChunks.push(event.data);
                    };

                    this.mediaRecorder.onstop = () => {
                        const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                        this.pendingFile = new File([audioBlob], `recording_${new Date().getTime()}.webm`, { type: 'audio/webm' });
                        
                        // Stop all tracks to release the microphone
                        stream.getTracks().forEach(track => track.stop());
                        
                        // Trigger message send automatically for recordings
                        this.sendMessage();
                    };

                    this.mediaRecorder.start();
                    this.isRecording = true;
                    
                    this.recordingInterval = setInterval(() => {
                        this.recordingTime++;
                    }, 1000);

                } catch (err) {
                    console.error('Error al acceder al micrófono:', err);
                    alert('No se pudo acceder al micrófono. Por favor, revisa los permisos.');
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
                }
            },

            async sendMessage() {
                if (this.input.trim() === '' && !this.pendingFile) return;
                
                const userText = this.input.trim();
                const fileToSend = this.pendingFile;

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
                } else if (userText) {
                    this.messages.push({ role: 'user', content: userText });
                }

                this.input = '';
                this.pendingFile = null;
                this.loading = true;
                this.isSendingFile = !!fileToSend;
                this.scrollToBottom();

                try {
                    const formData = new FormData();
                    formData.append('prompt', userText);
                    formData.append('team_id', this.teamId || '');
                    formData.append('task_id', this.taskId || '');
                    formData.append('attachment_id', this.attachmentId || '');
                    formData.append('forum_thread_id', this.threadId || '');
                    formData.append('forum_message_id', this.messageId || '');
                    
                    if (fileToSend) {
                        formData.append('file', fileToSend);
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
                    this.messages.push({ role: 'ai', content: data.message });
                    if (data.current_model) this.currentModel = data.current_model;
                } catch (error) {
                    console.error('AI Assistant Error:', error);
                    this.messages.push({ 
                        role: 'ai', 
                        content: '⚠️ No se pudo procesar tu solicitud. Detalle: ' + error.message 
                    });
                } finally {
                    this.loading = false;
                    this.isSendingFile = false;
                    this.scrollToBottom();
                }
            },

            scrollToBottom() {
                setTimeout(() => {
                    const el = document.getElementById('ai-chat-messages');
                    if (el) el.scrollTop = el.scrollHeight;
                }, 100);
            },
            
            renderMarkdown(text) {
                if (!text) return '';
                
                // Format [PAYLOAD] blocks as distinct cards BEFORE standard markdown parsing
                let formatted = text.replace(/\[PAYLOAD\]([\s\S]*?)\[\/PAYLOAD\]/g, (match, content) => {
                    return `<div class="bg-indigo-50/50 dark:bg-indigo-500/5 border-2 border-dashed border-indigo-200/50 dark:border-indigo-500/20 rounded-2xl p-4 my-4 font-mono text-[11px] leading-relaxed text-gray-700 dark:text-gray-300 relative group/payload shadow-inner">
                        <span class="absolute -top-3 left-4 px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 text-[9px] font-black uppercase tracking-widest rounded-full border border-indigo-200 dark:border-indigo-800 shadow-sm">Contenido Inyectable</span>
                        ${content.trim().replace(/\n/g, '<br>')}
                    </div>`;
                });

                // Use Marked for the rest
                try {
                    return marked.parse(formatted);
                } catch (e) {
                    console.error('Marked error:', e);
                    return formatted.replace(/\n/g, '<br>');
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
                        alert(data.message);
                    }
                } catch (e) {
                    console.error('Error undoing action:', e);
                }
            },

            async transferToTask(content) {
                const isDark = document.documentElement.classList.contains('dark');
                const target = await Swal.fire({
                    width: (window.innerWidth < 640) ? '95%' : '500px',
                    padding: '0',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f1f5f9' : '#1e293b',
                    showConfirmButton: false,
                    showCancelButton: false,
                    html: `
                        <div class="text-left overflow-hidden rounded-[2rem]">
                            <!-- Header Section -->
                            <div class="p-6 bg-slate-50 dark:bg-slate-900/50 border-b border-gray-100 dark:border-gray-800 flex items-center gap-5">
                                <div class="w-14 h-14 bg-indigo-600 rounded-2xl shadow-xl shadow-indigo-500/30 flex items-center justify-center shrink-0">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-indigo-500 dark:text-indigo-400 block mb-1">Ax.ia Intelligence</span>
                                    <h2 class="text-xl font-black text-gray-900 dark:text-white leading-tight">¿Dónde ponemos el grano?</h2>
                                </div>
                            </div>

                            <!-- Grid Section -->
                            <div class="p-6">
                                <div class="grid grid-cols-2 gap-4" id="ai-destination-grid">
                                    <button data-target="observations_append" class="group p-4 bg-white dark:bg-gray-800/40 border-2 border-transparent hover:border-indigo-500 hover:bg-indigo-50/30 dark:hover:bg-indigo-500/5 rounded-2xl shadow-sm hover:shadow-md transition-all text-left flex flex-col gap-3 outline-none ring-1 ring-gray-100 dark:ring-gray-800">
                                        <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/50 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <span class="font-bold text-gray-800 dark:text-white text-xs">Cuerpo de tarea</span>
                                    </button>

                                    <button data-target="private_note" class="group p-4 bg-white dark:bg-gray-800/40 border-2 border-transparent hover:border-violet-500 hover:bg-violet-50/30 dark:hover:bg-violet-500/5 rounded-2xl shadow-sm hover:shadow-md transition-all text-left flex flex-col gap-3 outline-none ring-1 ring-gray-100 dark:ring-gray-800">
                                        <div class="w-10 h-10 bg-violet-100 dark:bg-violet-900/50 rounded-xl flex items-center justify-center text-violet-600 dark:text-violet-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </div>
                                        <span class="font-bold text-gray-800 dark:text-white text-xs">Notas</span>
                                    </button>

                                    <button data-target="comment" class="group p-4 bg-white dark:bg-gray-800/40 border-2 border-transparent hover:border-amber-500 hover:bg-amber-50/30 dark:hover:bg-amber-500/5 rounded-2xl shadow-sm hover:shadow-md transition-all text-left flex flex-col gap-3 outline-none ring-1 ring-gray-100 dark:ring-gray-800">
                                        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/50 rounded-xl flex items-center justify-center text-amber-600 dark:text-amber-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                        </div>
                                        <span class="font-bold text-gray-800 dark:text-white text-xs">Hilo del Foro</span>
                                    </button>

                                    <button data-target="dom-reply" class="group p-4 bg-white dark:bg-gray-800/40 border-2 border-transparent hover:border-emerald-500 hover:bg-emerald-50/30 dark:hover:bg-emerald-500/5 rounded-2xl shadow-sm hover:shadow-md transition-all text-left flex flex-col gap-3 outline-none ring-1 ring-gray-100 dark:ring-gray-800">
                                        <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/50 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </div>
                                        <span class="font-bold text-gray-800 dark:text-white text-xs">Pegar en editor</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Footer Section -->
                            <div class="p-6 bg-slate-50 dark:bg-slate-900/50 border-t border-gray-100 dark:border-gray-800 flex justify-center">
                                <button onclick="Swal.close()" class="px-8 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-red-500 hover:border-red-500 transition-all shadow-sm">
                                    Cancelar y cerrar
                                </button>
                            </div>
                        </div>
                    `,
                    customClass: {
                        popup: 'rounded-[2.5rem] border-none shadow-[0_25px_80px_rgba(0,0,0,0.4)]',
                        htmlContainer: 'p-0 m-0'
                    },
                    didOpen: () => {
                        const grid = document.getElementById('ai-destination-grid');
                        grid.addEventListener('click', (e) => {
                            const btn = e.target.closest('button');
                            if (btn && btn.hasAttribute('data-target')) {
                                grid.dataset.selected = btn.getAttribute('data-target');
                                Swal.clickConfirm();
                            }
                        });
                    },
                    preConfirm: () => {
                        return document.getElementById('ai-destination-grid').dataset.selected;
                    }
                }).then(result => result.isConfirmed ? result.value : null);

                if (target) {
                    // Extract clean content once
                    const lastAiMessage = this.messages.filter(m => m.role === 'ai').pop();
                    const cleanContent = lastAiMessage ? lastAiMessage.content : content;
                    const match = cleanContent.match(/\[PAYLOAD\]([\s\S]*?)\[\/PAYLOAD\]/);
                    const finalPayload = match ? match[1].trim() : cleanContent.replace(/\[PAYLOAD\]|\[\/PAYLOAD\]/g, '').trim();

                    // Try to FIND and INJECT in DOM immediately if visible
                    let domInjected = false;
                    let possibleIds = {
                        'description': 'description',
                        'observations': 'observations',
                        'observations_append': 'observations',
                        'comment': 'reply-content',
                        'reply': 'reply-content',
                        'dom-reply': 'reply-content',
                        'private_note': 'reply-content-private',
                        'private-notes': 'reply-content-private'
                    };

                    const elementId = possibleIds[target];
                    let targetEl = elementId ? document.getElementById(elementId) : null;
                    
                    // FALLBACK: If not found by ID, look for ANY active editor or visible textarea
                    if (!targetEl) {
                        // Look for Quill editors first
                        targetEl = document.querySelector('.ql-editor');
                        if (!targetEl) {
                            // Look for the first visible textarea
                            targetEl = Array.from(document.querySelectorAll('textarea')).find(t => t.offsetWidth > 0 && t.offsetHeight > 0);
                        }
                    }

                    if (targetEl) {
                        if (targetEl.classList.contains('ql-editor')) {
                            // Quill injection
                            targetEl.innerHTML = marked.parse(finalPayload);
                            domInjected = true;
                        } else if (targetEl.tagName === 'TEXTAREA' || targetEl.tagName === 'INPUT') {
                            targetEl.value = finalPayload;
                            targetEl.dispatchEvent(new Event('input', { bubbles: true }));
                            targetEl.dispatchEvent(new Event('change', { bubbles: true }));
                            domInjected = true;
                        }
                        
                        if (domInjected) {
                            targetEl.focus();
                            targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }

                    // Only skip server if it's explicitly local-only
                    if (target === 'dom-reply') {
                        if (domInjected) {
                            Swal.fire({
                                title: '<span class="text-xs font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400">¡Inyectado!</span>',
                                html: '<p class="text-lg font-bold text-gray-800 dark:text-white mt-2">Texto pegado en el editor.</p>',
                                icon: 'success',
                                iconColor: '#10b981',
                                timer: 1500,
                                showConfirmButton: false,
                                background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                                customClass: { popup: 'rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-2xl' }
                            });
                        } else {
                            Swal.fire({
                                title: 'Atención',
                                text: 'No se encontró una caja de texto activa en esta página.',
                                icon: 'warning',
                                confirmButtonColor: '#4f46e5',
                                background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                                color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                                customClass: { popup: 'rounded-[1.5rem]' }
                            });
                        }
                        return;
                    }

                    // Decide if we need to prompt for a title
                    let customTitle = null;
                    const needsTitle = (target === 'comment' && !this.threadId) || (!this.taskId && !this.threadId && ['private_note', 'observations_append', 'observations', 'description'].includes(target));

                    if (needsTitle) {
                        const { value: title } = await Swal.fire({
                            title: target === 'comment' ? 'Nuevo Hilo en el Foro' : 'Título para la nueva tarea',
                            input: 'text',
                            inputLabel: target === 'comment' ? '¿Qué título le ponemos a la conversación?' : '¿Cómo se llamará la tarea para esta nota?',
                            inputValue: target === 'comment' 
                                ? (this.taskId ? 'Discusión sobre tarea' : 'Consulta con Ax.ia') 
                                : '📝 Nota de Ax.ia: ' + new Date().toLocaleDateString(),
                            showCancelButton: true,
                            confirmButtonColor: '#4f46e5',
                            cancelButtonColor: '#ef4444',
                            background: isDark ? '#0f172a' : '#ffffff',
                            color: isDark ? '#f1f5f9' : '#1e293b',
                            customClass: { popup: 'rounded-[1.5rem]' },
                            inputValidator: (value) => {
                                if (!value) return '¡Necesitas un título!';
                            }
                        });
                        
                        if (title) {
                            customTitle = title;
                        } else {
                            return; // User cancelled the title prompt
                        }
                    }

                    // Server Synchronization
                    let url = '';
                    if (this.threadId) {
                        url = '{{ route('ai.transfer_forum', ['team' => $teamId ?? 0, 'thread' => 'THREAD_ID']) }}'.replace('THREAD_ID', this.threadId);
                    } else if (this.taskId) {
                        url = '{{ route('ai.transfer', ['team' => $teamId ?? 0, 'task' => 'TASK_ID']) }}'.replace('TASK_ID', this.taskId);
                    } else if (this.teamId) {
                        url = '{{ route('ai.transfer_global', ['team' => 'TEAM_ID']) }}'.replace('TEAM_ID', this.teamId);
                    } else {
                        if (domInjected) {
                            Swal.fire({ title: '¡Inyectado!', text: 'Se ha pegado en el editor móvil, pero no se ha guardado en el servidor (sin contexto de equipo/tarea/hilo).', icon: 'info', timer: 2500 });
                            return;
                        }
                        Swal.fire('Error', 'No hay contexto de equipo para guardar en el servidor.', 'error');
                        return;
                    }

                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                content: cleanContent,
                                target: target === 'reply' ? 'comment' : target, // Normalize naming
                                title: customTitle
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.canUndo = true;
                            if (this.undoTimeout) clearTimeout(this.undoTimeout);
                            this.undoTimeout = setTimeout(() => { this.canUndo = false; }, 60000);
                            
                            Swal.fire({
                                title: `<span class="text-xs font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">${domInjected ? '¡Guardado e Inyectado!' : '¡Hecho!'}</span>`,
                                html: `<p class="text-lg font-bold text-gray-800 dark:text-white mt-2">${data.message}</p>`,
                                icon: 'success',
                                iconColor: '#4f46e5',
                                timer: domInjected ? 1500 : 2500,
                                showConfirmButton: false,
                                background: isDark ? '#0f172a' : '#ffffff',
                                customClass: { popup: 'rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-2xl' }
                            });
                            
                            if (!domInjected) {
                                setTimeout(() => window.location.reload(), 1500);
                            }
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: '#ef4444',
                                background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                                color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                                customClass: { popup: 'rounded-[1.5rem]' }
                            });
                        }
                    } catch (error) {
                        console.error('Transfer error:', error);
                        Swal.fire('Error', 'Problema de conexión con el servidor.', 'error');
                    }
                }
            }
        }));
    });
</script>
