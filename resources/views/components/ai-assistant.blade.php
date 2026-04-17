@props(['user' => auth()->user(), 'teamId' => null, 'taskId' => null])

<div x-data="sientiaAiAssistant()" 
     class="fixed z-[50] flex flex-col items-start font-sans"
     :style="`position: fixed; bottom: 6rem; left: 1.5rem; z-index: 50; transform: translate3d(${pos.x}px, ${pos.y}px, 0);`"
     @mousemove.window="drag($event)"
     @touchmove.window="drag($event)"
     @mouseup.window="stopDrag()"
     @touchend.window="stopDrag()">
    
    <!-- Floating Button -->
    <button 
        @mousedown="startDrag($event)" 
        @touchstart="startDrag($event)" 
        @click="toggle($event)"
        class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-full p-4 shadow-xl transition-all flex items-center justify-center focus:outline-none ring-4 ring-white dark:ring-gray-950 active:scale-95"
        :class="isDragging ? 'cursor-grabbing scale-110' : 'cursor-grab hover:scale-110'"
        style="touch-action: none;"
    >
        <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        <svg x-show="open" style="display:none;" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>

    <!-- Chat Window -->
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-90 translate-y-10"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-90 translate-y-10"
        style="display:none; resize: both; overflow: hidden; min-width: 320px; min-height: 400px;"
        class="absolute bottom-20 left-0 w-[420px] h-[580px] max-h-[85vh] bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-gray-100 dark:border-gray-800 flex flex-col overflow-hidden ring-1 ring-black/5"
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
                <button @click="clearHistory()" class="p-2 text-indigo-200 hover:text-white hover:bg-white/10 rounded-full transition-colors" title="Borrar historial">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
                <button @click="showHelp = !showHelp" class="p-2 hover:bg-white/10 rounded-full transition-colors" title="Ayuda">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </button>
                <button @click="open = false" class="p-2 hover:bg-white/10 rounded-full transition-colors">
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
            <template x-for="(msg, index) in messages" :key="index">
                <div :class="msg.role === 'user' ? 'self-end bg-indigo-600 text-white rounded-3xl rounded-tr-none shadow-indigo-500/20' : 'self-start bg-white dark:bg-gray-800 dark:text-gray-100 text-gray-800 rounded-3xl rounded-tl-none shadow-black/5 border border-gray-100 dark:border-gray-700/50'" class="px-5 py-3.5 max-w-[90%] text-sm relative group shadow-xl transition-all">
                    <div x-html="renderMarkdown(msg.content)" 
                         :class="msg.role === 'user' ? 'prose-invert text-white' : 'dark:prose-invert text-gray-800 dark:text-gray-100'" 
                         class="leading-relaxed prose prose-sm max-w-none"></div>
                    
                    <!-- Quick Actions (Only for AI messages) -->
                    <template x-if="msg.role === 'ai'">
                        <div class="absolute -bottom-10 right-0 opacity-0 group-hover:opacity-100 transition-all duration-300 flex space-x-1 translate-y-2 group-hover:translate-y-0 text-sans">
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

            <div x-show="loading" class="self-start bg-white dark:bg-gray-800 text-gray-800 rounded-3xl rounded-tl-none shadow-xl border border-gray-100 dark:border-gray-700/50 px-5 py-3.5">
                <div class="flex space-x-1.5 items-center h-5">
                    <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
            <form @submit.prevent="sendMessage" class="flex items-center space-x-3">
                <input 
                    x-model="input" 
                    type="text" 
                    class="flex-1 border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:border-indigo-500 focus:ring-0 rounded-2xl text-sm py-3 px-5 shadow-inner"
                    placeholder="Pregúntame algo..." 
                    :disabled="loading"
                >
                <button 
                    type="submit" 
                    class="bg-indigo-600 text-white rounded-2xl p-3 hover:bg-indigo-700 disabled:opacity-50 transition-all shadow-lg hover:shadow-indigo-500/30 active:scale-95 flex items-center justify-center cursor-pointer"
                    :disabled="loading || input.trim() === ''"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sientiaAiAssistant', () => ({
            open: false,
            loading: false,
            input: '',
            messages: [
                { role: 'ai', content: '¡Hola! Soy **Ax.ia**, tu asistente inteligente en Sientia. ¿En qué puedo ayudarte con tus tareas hoy?' }
            ],
            
            teamId: {{ $teamId ?: 'null' }},
            taskId: {{ $taskId ?: 'null' }},
            showHelp: false,

            init() {
                this.getHistory();
            },

            async getHistory() {
                try {
                    const response = await fetch('{{ route('ai.history') }}?team_id=' + this.teamId);
                    const data = await response.json();
                    if (data.messages && data.messages.length > 0) {
                        this.messages = data.messages.map(m => ({
                            role: m.role,
                            content: m.content
                        }));
                        this.scrollToBottom();
                    }
                } catch (error) {
                    console.error('Error fetching history:', error);
                }
            },

            async clearHistory() {
                if (!confirm('¿Seguro que quieres borrar todo el historial del chat?')) return;
                
                try {
                    await fetch('{{ route('ai.clear-history') }}', {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    this.messages = [
                        { role: 'ai', content: 'Historial borrado. ¡Empecemos de cero! ¿En qué puedo ayudarte?' }
                    ];
                } catch (error) {
                    console.error('Error clearing history:', error);
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
                if(this.open) this.scrollToBottom();
            },

            async sendMessage() {
                if (this.input.trim() === '') return;
                
                const userText = this.input.trim();
                this.messages.push({ role: 'user', content: userText });
                this.input = '';
                this.loading = true;
                
                this.scrollToBottom();

                try {
                    const response = await fetch('{{ route('ai.ask') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            prompt: userText,
                            team_id: this.teamId,
                            task_id: this.taskId
                        })
                    });
                    
                    if (!response.ok) throw new Error('Network error');
                    
                    const data = await response.json();
                    this.messages.push({ role: 'ai', content: data.message });
                } catch (error) {
                    this.messages.push({ role: 'ai', content: 'Lo siento, ha habido un problema de conexión.' });
                } finally {
                    this.loading = false;
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
                // Format [PAYLOAD] blocks as distinct cards
                let formatted = text.replace(/\[PAYLOAD\]([\s\S]*?)\[\/PAYLOAD\]/g, (match, content) => {
                    return `<div class="bg-indigo-50/50 dark:bg-indigo-500/5 border-2 border-dashed border-indigo-200/50 dark:border-indigo-500/20 rounded-2xl p-4 my-4 font-mono text-[11px] leading-relaxed text-gray-700 dark:text-gray-300 relative group/payload shadow-inner">
                        <span class="absolute -top-3 left-4 px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 text-[9px] font-black uppercase tracking-widest rounded-full border border-indigo-200 dark:border-indigo-800 shadow-sm">Contenido Inyectable</span>
                        ${content.trim().replace(/\n/g, '<br>')}
                    </div>`;
                });

                // Simple bold and italic formatter
                return formatted
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/\n/g, '<br>');
            },

            copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    const btn = event.currentTarget;
                    const oldHtml = btn.innerHTML;
                    btn.innerHTML = '<svg class="w-3 h-3 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
                    setTimeout(() => btn.innerHTML = oldHtml, 2000);
                });
            },
            
            async saveToDrive(content) {
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
                        body: JSON.stringify({ content: content })
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

            async transferToTask(content) {
                const { value: target } = await Swal.fire({
                    title: '<div class="flex items-center justify-center space-x-2"><div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg text-indigo-600 dark:text-indigo-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg></div><span class="text-sm font-black uppercase tracking-[0.2em] text-gray-800 dark:text-white">Acción de Ax.ia</span></div>',
                    html: '<p class="text-[11px] text-gray-400 dark:text-gray-500 font-medium mt-1 mb-4 leading-relaxed">Analizando payload... ¿Dónde inyectamos el grano?</p>',
                    input: 'radio',
                    inputOptions: {
                        'description': '🎯 Descripción (Reemplazar)',
                        'observations': '📝 Observaciones (Reemplazar)',
                        'comment': '💬 Comentario de Foro'
                    },
                    inputValidator: (value) => {
                        if (!value) return '¡Selecciona un destino!'
                    },
                    padding: '2rem',
                    confirmButtonText: 'Aplicar Inyección',
                    confirmButtonColor: '#4f46e5',
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                    customClass: {
                        popup: 'rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-[0_20px_60px_rgba(0,0,0,0.2)]',
                        confirmButton: 'rounded-xl px-6 py-3 text-[10px] font-bold uppercase tracking-widest transition-all hover:scale-105 active:scale-95',
                        cancelButton: 'rounded-xl px-6 py-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 transition-all hover:text-red-500',
                        input: 'text-sm font-medium text-gray-600 dark:text-gray-300 overflow-hidden'
                    }
                });

                if (target) {
                    // CASE 1: Persisted Task (Sync via Server)
                    if (this.taskId) {
                        try {
                            const response = await fetch('{{ route('ai.transfer', ['team' => $teamId ?? 0, 'task' => 'TASK_ID']) }}'.replace('TASK_ID', this.taskId), {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    content: content,
                                    target: target
                                })
                            });

                            const data = await response.json();
                            if (data.success) {
                                Swal.fire({
                                    title: '¡Hecho!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            }
                        } catch (error) {
                            console.error('Transfer error:', error);
                            Swal.fire('Error', 'No se pudo completar la transferencia', 'error');
                        }
                    } 
                    // CASE 2: New Task / Draft (Local Inject via DOM)
                    else {
                        let cleanContent = content;
                        const match = content.match(/\[PAYLOAD\]([\s\S]*?)\[\/PAYLOAD\]/);
                        if (match) {
                            cleanContent = match[1].trim();
                        } else {
                            cleanContent = content.replace(/\[PAYLOAD\]|\[\/PAYLOAD\]/g, '').trim();
                        }

                        const element = document.getElementById(target);
                        if (element) {
                            element.value = cleanContent;
                            // Trigger input event for frameworks like Alpine/Vue/Livewire
                            element.dispatchEvent(new Event('input', { bubbles: true }));
                            
                            Swal.fire({
                                title: '¡Inyectado!',
                                text: 'El texto se ha pegado en el formulario.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Atención', 'No se encontró el campo ' + target + ' en esta página.', 'warning');
                        }
                    }
                }
            }
        }));
    });
</script>
