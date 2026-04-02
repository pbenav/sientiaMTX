<div x-data="telegramChat()" 
     class="fixed bottom-6 right-6 z-[9999] flex flex-col items-end"
     style="position: fixed !important; bottom: 1.5rem !important; right: 1.5rem !important; z-index: 9999 !important;"
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
         class="mb-4 w-[350px] sm:w-[400px] h-[620px] max-h-[85vh] sm:h-[550px] bg-white dark:bg-gray-900 rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-800 flex flex-col overflow-hidden ring-1 ring-black/5"
         style="background: white; border: 1px solid #eee; overflow: hidden; display: none;"
         :style="open ? 'display: flex !important;' : 'display: none !important;'"
         x-cloak>
        
        <!-- Cabecera -->
        <div class="px-6 py-4 bg-gradient-to-r from-sky-500 to-blue-600 flex items-center justify-between shadow-lg"
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
                        <template x-if="msg.photo">
                            <div class="mb-2 -mx-2 -mt-2 overflow-hidden border-b border-black/5 bg-gray-100 dark:bg-gray-900">
                                <img :src="msg.photo" class="w-full h-auto max-h-60 object-cover cursor-pointer hover:scale-105 transition-transform duration-300" 
                                     @click="window.open(msg.photo, '_blank')">
                            </div>
                        </template>

                        <p class="text-sm leading-relaxed whitespace-pre-wrap" x-text="msg.text" x-show="msg.text"></p>
                        
                        <!-- Footer del mensaje: Hora y acciones -->
                        <div class="flex items-center justify-between gap-4 mt-1.5 border-t border-black/5 pt-1">
                            <span :class="msg.from_me ? 'text-white/60' : 'text-gray-400'" class="text-[9px] block" x-text="msg.time"></span>
                            
                            <!-- Botón de borrar -->
                            <button @click="deleteMsg(msg.id)"
                                    class="text-[9px] opacity-0 group-hover:opacity-100 transition-opacity font-bold uppercase tracking-tighter"
                                    :class="msg.from_me ? 'text-white/70 hover:text-white' : 'text-red-400 hover:text-red-500'">
                                {{ __('Eliminar') }}
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Input -->
        <form @submit.prevent="send()" class="p-4 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 relative" x-data="{ showEmojis: false }">
            <!-- Emoji Picker -->
            <div x-show="showEmojis" @click.outside="showEmojis = false"
                 class="absolute bottom-20 left-4 right-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-2xl p-4 z-50 origin-bottom flex flex-col"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 style="display: none; max-height: 250px;">
                
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-7 gap-2">
                        @foreach([
                            '😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🤭','🤫','🤥','😶','😐','😑','😬','🙄','😯','😦','😧','😮','😲','🥱','😴','🤤','😪','😵','🤐','🥴','🤢','🤮','🤧','👋','👌','👍','👎','👏','🙏','💪','❤️','🔥','✨','🚀','✅','❌','⚠️','💡'
                        ] as $emoji)
                            <button type="button" @click="newMessage += '{{ $emoji }}'; $refs.chatInput.focus();" 
                                    class="text-xl hover:bg-gray-100 dark:hover:bg-gray-700 p-2 rounded-xl transition-all hover:scale-125">{{ $emoji }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700 text-[10px] text-gray-400 font-bold uppercase tracking-widest text-center">
                    Selector de Emojis
                </div>
            </div>
            
            <div class="relative flex items-center gap-2">
                <button type="button" @click="showEmojis = !showEmojis" :disabled="!teamId"
                        class="p-2 text-gray-400 hover:text-sky-500 dark:hover:text-sky-400 transition-colors disabled:opacity-50"
                        title="Emojis">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </button>
                <input x-ref="chatInput" x-model="newMessage" 
                       type="text" 
                       placeholder="Escribe un mensaje..."
                       :disabled="!teamId"
                       class="flex-1 bg-gray-100 dark:bg-gray-800 border-none rounded-2xl px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 dark:text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <button type="submit" 
                        :disabled="!newMessage.trim() || !teamId"
                        class="p-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl shadow-lg hover:shadow-blue-500/30 transition-all disabled:opacity-50 disabled:grayscale disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </div>
            <p class="text-[9px] text-gray-400 mt-2 text-center">Conectado vía @SientiaBot</p>
        </form>
    </div>

    <!-- Botón Flotante -->
    <button @click="open = !open; if(open) scrollToBottom()" 
            class="group relative w-14 h-14 bg-gradient-to-br from-sky-400 to-blue-600 rounded-full shadow-2xl flex items-center justify-center transition-all duration-500 hover:scale-110 active:scale-95 ring-4 ring-white dark:ring-gray-950"
            style="width: 56px; height: 56px; background: linear-gradient(135deg, #38bdf8, #2563eb); border-radius: 9999px; display: flex; align-items: center; justify-center; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); border: 4px solid white;">
        <div class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-white dark:border-gray-950 shadow-sm" 
             style="position: absolute; top: -4px; right: -4px; width: 20px; height: 20px; background: #ef4444; color: white; border-radius: 9999px; display: none;" 
             x-show="unread > 0" x-text="unread" x-cloak></div>
        
        <svg x-show="!open" x-transition class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor" style="width: 28px; height: 28px;">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.35-.01-1.02-.2-1.53-.37-.6-.2-1.07-.31-1.03-.66.02-.18.27-.36.75-.55 2.94-1.28 4.9-2.13 5.88-2.54 2.8-.1.5.15.5.99c.01.26-.01.52-.06.78z"/>
        </svg>
        <svg x-show="open" x-transition class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 28px; height: 28px; display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
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
                        } else if (latestIncomingId > this.lastMessageId) {
                            // Solo añadimos los nuevos al final
                            const filtered = newMsgs.filter(m => m.id > this.lastMessageId);
                            if (filtered.length > 0) {
                                if (!this.open) this.unread += filtered.length;
                                this.messages = [...this.messages, ...filtered];
                                this.lastMessageId = latestIncomingId;
                                if (this.open) this.scrollToBottom();
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
            async send() {
                if (!this.newMessage.trim() || !this.teamId) return;
                
                const text = this.newMessage;
                this.newMessage = '';
                
                try {
                    const response = await fetch('{{ route('telegram.chat.send') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ 
                            message: text,
                            team_id: this.teamId
                        })
                    });
                    
                    if (response.ok) {
                        this.refreshMessages();
                    }
                } catch (e) {
                    console.error('Error enviando:', e);
                }
            },
            async deleteMsg(msgId) {
                if (!confirm('¿Borrar mensaje? Se eliminará de Telegram y del historial.')) return;
                
                try {
                    const response = await fetch(`/telegram-chat/messages/${msgId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (response.ok) {
                        this.messages = this.messages.filter(m => m.id !== msgId);
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
