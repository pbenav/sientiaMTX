
<div x-data="telegramChat()" 
     class="fixed z-[9999] flex flex-col items-end bottom-32 sm:bottom-24 right-4 pointer-events-none"
     :style="`transform: translate3d(${pos.x}px, ${pos.y}px, 0);`"
     @mousemove.window="drag($event)"
     @touchmove.window="drag($event)"
     @mouseup.window="stopDrag()"
     @touchend.window="stopDrag()"
     x-init="initChat()"
     @keydown.escape="open = false">
    
    <!-- Ventana de Chat -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90 translate-y-10"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-90 translate-y-10"
         class="mb-4 bg-white dark:bg-gray-900 rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-800 flex flex-col overflow-hidden ring-1 ring-black/5 pointer-events-auto relative"
         style="background: white; border: 1px solid #eee; overflow: hidden; display: none;"
         :style="`display: ${open ? 'flex' : 'none'} !important; width: ${dimensions.width}px; height: ${dimensions.height}px; max-width: 90vw; max-height: 85vh;`"
         x-cloak>
        
        <!-- Tirador de redimensionamiento (Coherencia visual con Ax.ia) -->
        <div class="absolute bottom-0 left-0 w-6 h-6 cursor-sw-resize z-[60] p-1 flex items-end justify-start opacity-30 hover:opacity-100 transition-opacity translate-x-1 -translate-y-1"
             @mousedown.stop.prevent="startResize($event)"
             @touchstart.stop.prevent="startResize($event)">
            <svg class="w-3 h-3 text-gray-600 dark:text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="18" y1="13" x2="13" y2="18" />
            </svg>
        </div>

        <!-- Cabecera -->
        <div class="px-6 py-4 bg-gradient-to-r from-sky-500 to-blue-600 flex items-center justify-between shadow-lg shrink-0"
             style="background: linear-gradient(135deg, #0ea5e9, #2563eb);">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 shadow-inner">
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.35-.01-1.02-.2-1.53-.37-.6-.2-1.07-.31-1.03-.66.02-.18.27-.36.75-.55 2.94-1.28 4.9-2.13 5.88-2.54 2.8-.1.5.15.5.99c.01.26-.01.52-.06.78z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-white font-bold text-sm leading-tight">Telegram MTX</h4>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" style="background: #34d399; width: 8px; height: 8px; border-radius: 9999px;"></span>
                        <span class="text-white/70 text-[10px] font-medium uppercase tracking-wider">En línea</span>
                    </div>
                </div>
            </div>
            <button @click="open = false" class="text-white/50 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Área de Mensajes -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50/50 dark:bg-gray-950/50 custom-scrollbar" id="telegram-messages-container"
             style="background-color: rgba(249, 250, 251, 0.5); flex: 1; overflow-y: auto;">
            
            <!-- Botón Cargar Más -->
            <div class="flex justify-center mb-4" x-show="canLoadMore">
                <button @click="loadOlderMessages()" 
                        class="px-4 py-1.5 rounded-full bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-[10px] font-black text-gray-400 hover:text-blue-500 hover:border-blue-500 transition-all shadow-sm uppercase tracking-widest disabled:opacity-50"
                        :disabled="loading">
                    <span x-show="!loading">Ver anteriores</span>
                    <span x-show="loading">Cargando...</span>
                </button>
            </div>

            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.from_me ? 'flex justify-end' : 'flex justify-start'" class="group relative">
                    <div :class="msg.from_me 
                        ? 'bg-blue-600 text-white rounded-2xl rounded-tr-none px-4 py-2 shadow-md max-w-[85%]' 
                        : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-2xl rounded-tl-none px-4 py-2 shadow-sm border border-gray-100 dark:border-gray-700 max-w-[85%]'"
                        class="px-4 py-2 relative overflow-hidden">
                        
                        <span x-show="!msg.from_me" class="block text-[10px] font-bold mb-1 uppercase tracking-tight opacity-70" x-text="msg.author"></span>
                        
                        <!-- Imagen si existe -->
                        <template x-if="msg.file_type === 'photo'">
                            <div class="mb-2 -mx-2 -mt-2 overflow-hidden border-b border-black/5 bg-gray-100 dark:bg-gray-900">
                                <template x-if="msg.photo">
                                    <img :src="msg.photo" class="w-full h-auto max-h-60 object-cover cursor-pointer hover:scale-105 transition-transform duration-300" 
                                         @click="window.open(msg.photo, '_blank')">
                                </template>
                                <template x-if="!msg.photo">
                                    <div class="py-10 flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-8 h-8 mb-2 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg>
                                        <span class="text-[10px] font-bold uppercase tracking-widest">Imagen purgada</span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Sticker estático (.webp) -->
                        <template x-if="msg.file_type === 'sticker'">
                            <div class="mb-2 flex justify-center">
                                <template x-if="msg.sticker">
                                    <img :src="msg.sticker" class="w-32 h-32 object-contain" :title="msg.text">
                                </template>
                                <template x-if="!msg.sticker">
                                    <div class="py-4 text-gray-400 flex flex-col items-center">
                                        <svg class="w-8 h-8 mb-1 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"/></svg>
                                        <span class="text-[9px] font-bold uppercase tracking-widest italic">Sticker no disponible</span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Sticker de vídeo (.webm) -->
                        <template x-if="msg.file_type === 'sticker_video'">
                            <div class="mb-2 flex justify-center">
                                <template x-if="msg.sticker">
                                    <video :src="msg.sticker" class="w-32 h-32 object-contain" autoplay loop muted playsinline></video>
                                </template>
                                <template x-if="!msg.sticker">
                                    <span class="text-4xl">🎬</span>
                                </template>
                            </div>
                        </template>

                        <!-- Sticker animado (.tgs → .json con Lottie) -->
                        <template x-if="msg.file_type === 'sticker_animated'">
                            <div class="mb-2 flex justify-center">
                                <template x-if="msg.sticker">
                                    <div 
                                        class="w-32 h-32"
                                        x-init="
                                            $nextTick(() => {
                                                if (typeof lottie !== 'undefined') {
                                                    lottie.loadAnimation({
                                                        container: $el,
                                                        renderer: 'svg',
                                                        loop: true,
                                                        autoplay: true,
                                                        path: msg.sticker
                                                    });
                                                }
                                            })
                                        "
                                    ></div>
                                </template>
                                <template x-if="!msg.sticker">
                                    <div class="py-4 text-gray-400 flex flex-col items-center">
                                        <span class="text-4xl">🌀</span>
                                        <span class="text-[9px] font-bold uppercase tracking-widest mt-1">Sticker animado</span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Animación (GIF enviado como MP4) -->
                        <template x-if="msg.file_type === 'animation'">
                            <div class="mb-2 -mx-2 -mt-2 overflow-hidden border-b border-black/5 bg-gray-100 dark:bg-gray-900">
                                <template x-if="msg.photo">
                                    <video :src="msg.photo" class="w-full max-h-60 object-cover" autoplay loop muted playsinline
                                           @click="window.open(msg.photo, '_blank')" style="cursor:pointer"></video>
                                </template>
                                <template x-if="!msg.photo">
                                    <div class="py-10 flex flex-col items-center justify-center text-gray-400">
                                        <span class="text-4xl mb-2">🎞️</span>
                                        <span class="text-[10px] font-bold uppercase tracking-widest">Animación purgada</span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Audio si existe -->
                        <template x-if="msg.file_type === 'voice'">
                            <div class="mb-2 py-2 px-1 bg-black/5 dark:bg-white/5 rounded-xl border border-black/5 dark:border-white/5">
                                <div class="flex items-center gap-2 mb-1 px-1">
                                    <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" stroke-width="2.5"/></svg>
                                    <span class="text-[9px] font-bold uppercase tracking-widest opacity-60">Mensaje de voz</span>
                                </div>
                                <template x-if="msg.voice">
                                    <audio controls class="w-full h-8" :src="msg.voice"></audio>
                                </template>
                                <template x-if="!msg.voice">
                                    <div class="flex items-center gap-2 py-1 px-2 border-t border-black/5 mt-1">
                                        <svg class="w-3 h-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-width="2"/></svg>
                                        <span class="text-[9px] font-bold text-gray-500 uppercase tracking-tighter">Fichero purgado</span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Respuesta a otro mensaje -->
                        <template x-if="msg.reply_to_text">
                            <div class="mb-2 py-1.5 px-2.5 bg-black/5 dark:bg-white/5 rounded-lg border-l-2 border-white/30 dark:border-blue-500/50 italic text-[10px] opacity-80 line-clamp-2">
                                <span class="font-bold block uppercase text-[8px] mb-0.5 tracking-tighter">Respuesta a:</span>
                                <span x-text="msg.reply_to_text"></span>
                            </div>
                        </template>

                        <p class="text-sm leading-relaxed whitespace-pre-wrap" x-text="msg.text" x-show="msg.text"></p>
                        
                        <!-- Footer del mensaje: Hora y acciones -->
                        <div class="flex items-center justify-between gap-4 mt-1.5 border-t border-black/5 pt-1">
                            <span :class="msg.from_me ? 'text-white/60' : 'text-gray-400'" class="text-[9px] block" x-text="msg.time"></span>
                            
                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <!-- Botón de editar -->
                                <template x-if="msg.from_me">
                                    <button @click="startEdit(msg)"
                                            class="text-[9px] font-bold uppercase tracking-tighter"
                                            :class="msg.from_me ? 'text-white/70 hover:text-white' : 'text-blue-400 hover:text-blue-500'">
                                        {{ __('Editar') }}
                                    </button>
                                </template>

                                <!-- Botón de responder -->
                                <button @click="setReply(msg)"
                                        class="text-[9px] font-bold uppercase tracking-tighter"
                                        :class="msg.from_me ? 'text-white/70 hover:text-white' : 'text-blue-400 hover:text-blue-500'">
                                    {{ __('Responder') }}
                                </button>

                                <!-- Botón de borrar -->
                                <button @click="deleteMsg(msg.id)"
                                        class="text-[9px] font-bold uppercase tracking-tighter"
                                        :class="msg.from_me ? 'text-white/70 hover:text-white' : 'text-red-400 hover:text-red-500'">
                                    {{ __('Eliminar') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Input -->
        <form @submit.prevent="send()" class="p-4 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 relative" x-data="{ showEmojis: false }">
            <!-- Emoji Picker -->
            <div x-show="showEmojis" @click.outside="showEmojis = false"
                 class="absolute bottom-24 left-4 right-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-2xl p-4 z-50 origin-bottom flex flex-col"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 style="display: none; max-height: 250px;">
                
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-12 gap-0">
                        @foreach([
                            '😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🤭','🤫','🤥','😶','😐','😑','😬','🙄','😯','😦','😧','😮','😲','🥱','😴','🤤','😪','😵','🤐','🥴','🤢','🤮','🤧','👋','👌','👍','👎','👏','🙏','💪','❤️','🔥','✨','🚀','✅','❌','⚠️','💡'
                        ] as $emoji)
                            <button type="button" @click="newMessage += '{{ $emoji }}'; $refs.chatInput.focus();" 
                                    class="text-base hover:bg-gray-100 dark:hover:bg-gray-700 p-0.5 rounded transition-all hover:scale-125">{{ $emoji }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700 text-[10px] text-gray-400 font-bold uppercase tracking-widest text-center">
                    Selector de Emojis
                </div>
            </div>
            
            <div class="relative flex items-center gap-2">
                <!-- Voice/Emoji Toggle -->
                <div class="flex items-center gap-1">
                    <button type="button" @click="showEmojis = !showEmojis" :disabled="!teamId || isRecording"
                            class="p-2 text-gray-400 hover:text-sky-500 dark:hover:text-sky-400 transition-colors disabled:opacity-50"
                            title="Emojis">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </button>

                    <!-- Voice Recorder Button -->
                    <button type="button" @click="toggleRecording()" :disabled="!teamId"
                            class="p-2 transition-all duration-300 relative group flex items-center justify-center rounded-xl"
                            :class="isRecording ? 'bg-red-50 text-red-500 dark:bg-red-900/30' : 'text-gray-400 hover:text-rose-500 dark:hover:text-rose-400'"
                            title="Enviar audio">
                        <svg x-show="!isRecording" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
                        <div x-show="isRecording" class="flex items-center gap-1">
                            <span class="flex h-1.5 w-1.5 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-red-500"></span>
                            </span>
                            <span class="text-[9px] font-black font-mono" x-text="formatTime(recordingTime)"></span>
                        </div>
                    </button>
                </div>

                <div class="relative flex-1">
                    <textarea x-ref="chatInput" x-model="newMessage" 
                           @paste="handlePaste($event)"
                           @keydown.enter.prevent="if(!$event.shiftKey) send()"
                           rows="2"
                           :placeholder="isRecording ? 'Grabando audio...' : (editingId ? 'Editando mensaje...' : 'Escribe un mensaje...')"
                           :disabled="!teamId || isRecording"
                           :class="editingId ? 'ring-2 ring-amber-500 bg-amber-50 dark:bg-amber-950/20' : 'bg-gray-100 dark:bg-gray-800 border-none'"
                           class="w-full rounded-2xl px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-inner resize-none min-h-[45px] max-h-[120px] custom-scrollbar"></textarea>
                    
                    <!-- Preview of pending reply -->
                    <template x-if="replyToId">
                        <div class="absolute bottom-full mb-3 left-0 right-0 bg-white dark:bg-gray-800 p-3 rounded-2xl shadow-2xl border-l-4 border-blue-500 flex items-center justify-between gap-3 animate-in fade-in slide-in-from-bottom-2">
                            <div class="overflow-hidden">
                                <span class="text-[9px] font-black uppercase text-blue-500 tracking-widest block mb-0.5">Respondiendo a:</span>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate italic" x-text="replyToText"></p>
                            </div>
                            <button type="button" @click="cancelReply()" class="text-gray-400 hover:text-red-500 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </template>

                    <!-- Preview of pending photo -->
                    <template x-if="pendingPhoto">
                        <div class="absolute bottom-full mb-3 left-0 bg-white dark:bg-gray-800 p-2 rounded-2xl shadow-2xl border border-blue-100 dark:border-blue-900/50 flex items-center gap-3 animate-bounce-subtle">
                            <div class="relative">
                                <img :src="previewUrl" class="w-12 h-12 object-cover rounded-lg border border-gray-100 dark:border-gray-700">
                                <button type="button" @click="pendingPhoto = null; previewUrl = null" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-0.5 shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 max-w-[100px] truncate" x-text="pendingPhoto.name"></span>
                        </div>
                    </template>

                    <!-- Preview of pending voice -->
                    <template x-if="pendingVoice">
                        <div class="absolute bottom-full mb-3 left-0 bg-white dark:bg-gray-800 p-3 rounded-2xl shadow-2xl border border-rose-100 dark:border-rose-900/50 flex flex-col gap-2 min-w-[180px]">
                            <div class="flex items-center justify-between">
                                <span class="text-[9px] font-black uppercase text-rose-500 tracking-widest">Audio grabado</span>
                                <button type="button" @click="pendingVoice = null; voicePreviewUrl = null" class="text-gray-400 hover:text-red-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                            <audio controls class="w-full h-8" :src="voicePreviewUrl"></audio>
                        </div>
                    </template>

                    <!-- Cancel Edit Button -->
                    <template x-if="editingId">
                        <button type="button" @click="cancelEdit()" 
                                class="absolute right-2 top-1.5 p-1 text-amber-600 hover:bg-amber-100 dark:hover:bg-amber-900/40 rounded-full transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </template>
                </div>
                <button type="submit" 
                        :disabled="(!newMessage.trim() && !pendingPhoto && !pendingVoice) || !teamId || isRecording"
                        :class="editingId ? 'bg-amber-600 hover:bg-amber-500 shadow-amber-500/30' : 'bg-blue-600 hover:bg-blue-500 shadow-blue-500/30'"
                        class="p-2.5 sm:p-3 text-white rounded-xl sm:rounded-2xl shadow-lg transition-all disabled:opacity-50 disabled:grayscale disabled:cursor-not-allowed active:scale-95">
                    <svg x-show="!editingId" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <svg x-show="editingId" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </button>
            </div>
            <p class="text-[9px] text-gray-400 mt-2 text-center">Conectado vía @SientiaBot</p>
        </form>
    </div>

    <!-- Botón Flotante -->
    <button @mousedown="startDrag($event)" 
            @touchstart="startDrag($event)" 
            @click="toggleChat($event)" 
            class="group relative w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-sky-400 to-blue-600 backdrop-blur-sm rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 active:scale-95 ring-4 ring-white dark:ring-gray-950 pointer-events-auto"
            :class="isDragging ? 'cursor-grabbing scale-110' : 'cursor-grab group-hover:scale-110'"
            style="touch-action: none;">
        <div class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-white dark:border-gray-950 shadow-sm" x-show="unread > 0" x-text="unread" x-cloak></div>
        
        <!-- Icono de edición (indicador) -->
        <div x-show="editingId" class="absolute -top-1 -left-1 w-5 h-5 bg-amber-500 text-white rounded-full flex items-center justify-center border-2 border-white dark:border-gray-950 shadow-sm animate-pulse" x-cloak>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        
        <svg x-show="!open" x-transition class="w-6 h-6 sm:w-7 sm:h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.35-.01-1.02-.2-1.53-.37-.6-.2-1.07-.31-1.03-.66.02-.18.27-.36.75-.55 2.94-1.28 4.9-2.13 5.88-2.54 2.8-.1.5.15.5.99c.01.26-.01.52-.06.78z"/>
        </svg>
        <svg x-show="open" x-transition style="display:none;" class="w-6 h-6 sm:w-7 sm:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>

<script>
    function telegramChat() {
        @php
            $routeTeamId = null;
            if (request()->route('team')) {
                $routeTeamId = is_object(request()->route('team')) 
                    ? request()->route('team')->id 
                    : request()->route('team');
            }
        @endphp

        return {
            open: false,
            loading: false,
            unread: 0,
            newMessage: '',
            teamId: {{ $routeTeamId ?: 'null' }},
            messages: [],
            lastMessageId: 0,
            firstMessageId: 0,
            canLoadMore: true,
            pendingPhoto: null,
            previewUrl: null,
            editingId: null,
            replyToId: null,
            replyToText: null,
            
            // Voice Recording
            isRecording: false,
            recordingTime: 0,
            recordingInterval: null,
            mediaRecorder: null,
            audioChunks: [],
            pendingVoice: null,
            voicePreviewUrl: null,

            pos: { x: 0, y: 0 },
            bottomPos: (window.innerWidth < 640) ? '8rem' : '6rem',
            isDragging: false,
            wasDragged: false,
            startX: 0,
            startY: 0,

            dimensions: { 
                width: (window.innerWidth < 640) ? 350 : 400, 
                height: (window.innerWidth < 640) ? 620 : 550 
            },
            isResizing: false,
            
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

                    this.mediaRecorder.ondataavailable = (e) => {
                        this.audioChunks.push(e.data);
                    };

                    this.mediaRecorder.onstop = () => {
                        const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                        this.pendingVoice = new File([audioBlob], `voice_${new Date().getTime()}.webm`, { type: 'audio/webm' });
                        this.voicePreviewUrl = URL.createObjectURL(audioBlob);
                        
                        // Stop all tracks to release mic
                        stream.getTracks().forEach(track => track.stop());
                    };

                    this.mediaRecorder.start();
                    this.isRecording = true;
                    this.recordingTime = 0;
                    this.recordingInterval = setInterval(() => {
                        this.recordingTime++;
                    }, 1000);
                } catch (err) {
                    console.error("No se pudo acceder al micrófono:", err);
                    alert("Error: No se pudo acceder al micrófono.");
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

            startResize(e) {
                this.isResizing = true;
                const event = e.type.includes('touch') ? e.touches[0] : e;
                const initialX = event.clientX;
                const initialY = event.clientY;
                const initialWidth = this.dimensions.width;
                const initialHeight = this.dimensions.height;
                
                const onMouseMove = (moveEvent) => {
                    if (!this.isResizing) return;
                    const mevent = moveEvent.type.includes('touch') ? moveEvent.touches[0] : moveEvent;
                    
                    // Al estar anclado a la derecha, para crecer a la izquierda sumamos el delta invertido del ratón
                    this.dimensions.width = initialWidth + (initialX - mevent.clientX);
                    this.dimensions.height = initialHeight + (mevent.clientY - initialY);
                    
                    // Límites
                    if (this.dimensions.width < 320) this.dimensions.width = 320;
                    if (this.dimensions.width > window.innerWidth * 0.9) this.dimensions.width = window.innerWidth * 0.9;
                    if (this.dimensions.height < 400) this.dimensions.height = 400;
                    if (this.dimensions.height > window.innerHeight * 0.85) this.dimensions.height = window.innerHeight * 0.85;
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
            toggleChat(e) {
                if (this.wasDragged) {
                    if (e) e.preventDefault();
                    this.wasDragged = false;
                    return;
                }
                this.open = !this.open;
                if(this.open) {
                    this.scrollToBottom();
                    // Reset unread count after 2 seconds of being open
                    setTimeout(() => {
                        if (this.open) this.unread = 0;
                    }, 2000);
                }
            },

            initChat() {
                if (!this.teamId) {
                    this.messages = [{ id: 1, text: '⚠️ Entra en el panel de un equipo concreto para usar el chat de Telegram.', author: 'SientiaBot', from_me: false, time: 'Sistema' }];
                    this.canLoadMore = false;
                    return;
                }
                
                this.refreshMessages(true);
                setInterval(() => this.refreshMessages(), 8000); // Polling cada 8s
            },
            async refreshMessages(initial = false) {
                if (!this.teamId || this.loading) return;

                try {
                    const response = await fetch(`{{ route('telegram.chat.messages') }}?team_id=${this.teamId}`);
                    const data = await response.json();
                    
                    if (data.messages && data.messages.length > 0) {
                        const newMsgs = data.messages;
                        const latestIncomingId = newMsgs[newMsgs.length - 1].id;
                        
                        if (initial) {
                            this.messages = newMsgs;
                            this.firstMessageId = newMsgs[0].id;
                            this.lastMessageId = latestIncomingId;
                            this.scrollToBottom();
                        } else {
                            // 1. Detectar y aplicar ediciones en mensajes existentes
                            let hasEdits = false;
                            this.messages = this.messages.map(m => {
                                const match = newMsgs.find(nm => nm.id === m.id);
                                if (match && match.text !== m.text) {
                                    hasEdits = true;
                                    return { ...m, text: match.text };
                                }
                                return m;
                            });

                            // 2. Añadir mensajes nuevos si los hay
                            if (latestIncomingId > this.lastMessageId) {
                                const filtered = newMsgs.filter(m => m.id > this.lastMessageId);
                                if (filtered.length > 0) {
                                    if (!this.open) {
                                        this.unread += filtered.length;
                                    } else {
                                        this.unread = 0;
                                    }
                                    this.messages = [...this.messages, ...filtered];
                                    this.lastMessageId = latestIncomingId;
                                    if (this.open) this.scrollToBottom();
                                }
                            }
                        }
                    }
                } catch (e) {
                    console.error('Error al actualizar:', e);
                }
            },
            async loadOlderMessages() {
                if (!this.teamId || this.loading || !this.canLoadMore) return;
                
                this.loading = true;
                const container = document.getElementById('telegram-messages-container');
                const oldHeight = container.scrollHeight;

                try {
                    const response = await fetch(`{{ route('telegram.chat.messages') }}?team_id=${this.teamId}&before_id=${this.firstMessageId}`);
                    const data = await response.json();
                    
                    if (data.messages && data.messages.length > 0) {
                        this.messages = [...data.messages, ...this.messages];
                        this.firstMessageId = data.messages[0].id;
                        
                        // Mantener la posición del scroll
                        this.$nextTick(() => {
                            container.scrollTop = container.scrollHeight - oldHeight;
                        });

                        if (data.messages.length < 25) {
                            this.canLoadMore = false;
                        }
                    } else {
                        this.canLoadMore = false;
                    }
                } catch (e) {
                    console.error('Error cargando anteriores:', e);
                } finally {
                    this.loading = false;
                }
            },
            startEdit(msg) {
                this.editingId = msg.id;
                this.newMessage = msg.text;
                this.$refs.chatInput.focus();
            },
            cancelEdit() {
                this.editingId = null;
                this.newMessage = '';
            },
            setReply(msg) {
                this.replyToId = msg.id;
                this.replyToText = msg.text || (msg.file_type === 'photo' ? 'Imagen' : (msg.file_type === 'voice' ? 'Audio' : 'Mensaje'));
                this.$refs.chatInput.focus();
                this.editingId = null;
            },
            cancelReply() {
                this.replyToId = null;
                this.replyToText = null;
            },
            handlePaste(e) {
                const items = (e.clipboardData || e.originalEvent.clipboardData).items;
                for (let index in items) {
                    const item = items[index];
                    if (item.kind === 'file') {
                        const blob = item.getAsFile();
                        if (blob && blob.type.startsWith('image/')) {
                            this.pendingPhoto = new File([blob], `telegram_paste_${new Date().getTime()}.png`, { type: blob.type });
                            this.previewUrl = URL.createObjectURL(blob);
                        }
                    }
                }
            },
            async send() {
                if ((!this.newMessage.trim() && !this.pendingPhoto && !this.pendingVoice) || !this.teamId) return;
                
                if (this.editingId) {
                    await this.updateMsg();
                    return;
                }

                const formData = new FormData();
                formData.append('message', this.newMessage);
                formData.append('team_id', this.teamId);
                if (this.replyToId) {
                    formData.append('reply_to_id', this.replyToId);
                }
                if (this.pendingPhoto) {
                    formData.append('photo', this.pendingPhoto);
                }
                if (this.pendingVoice) {
                    formData.append('voice', this.pendingVoice);
                }

                this.newMessage = '';
                this.pendingPhoto = null;
                this.previewUrl = null;
                this.pendingVoice = null;
                this.voicePreviewUrl = null;
                this.replyToId = null;
                this.replyToText = null;
                
                try {
                    const response = await fetch('{{ route('telegram.chat.send') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });
                    
                    if (response.ok) {
                        this.refreshMessages();
                    }
                } catch (e) {
                    console.error('Error enviando:', e);
                }
            },
            async updateMsg() {
                const id = this.editingId;
                const text = this.newMessage;
                
                this.editingId = null;
                this.newMessage = '';

                try {
                    const url = '{{ route("telegram.chat.update", ":id") }}'.replace(':id', id);
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ message: text })
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        this.messages = this.messages.map(m => {
                            if (m.id === id) {
                                m.text = data.message;
                            }
                            return m;
                        });
                    }
                } catch (e) {
                    console.error('Error editando:', e);
                }
            },
            async deleteMsg(msgId) {
                if (!confirm('¿Borrar mensaje? Se eliminará de Telegram y del historial.')) return;
                
                try {
                    const url = '{{ route("telegram.chat.delete", ":id") }}'.replace(':id', msgId);
                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (response.ok) {
                        this.messages = this.messages.filter(m => m.id !== msgId);
                    } else {
                        const error = await response.json();
                        alert('Error: ' + (error.error || 'No se pudo borrar'));
                    }
                } catch (e) {
                    console.error('Error deleting message:', e);
                }
            },
            scrollToBottom() {
                setTimeout(() => {
                    const container = document.getElementById('telegram-messages-container');
                    if (container) container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
                }, 100);
            }
        }
    }
</script>
