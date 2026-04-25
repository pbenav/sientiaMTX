<div x-data="sientiaQuickNotes" 
     class="fixed inset-0 pointer-events-none z-[8888]"
     @keydown.window.escape="closeAll()"
     @quicknote-create.window="createNote()"
     @quicknote-toggle-all.window="toggleAll()"
     @quicknote-refresh.window="refreshNotes()"
     @mousemove.window="handleMouseMove($event)"
     @touchmove.window="handleMouseMove($event)"
     @mouseup.window="stopMoving()"
     @touchend.window="stopMoving()">

    <style>
        .swal2-container { z-index: 100000 !important; }
    </style>
    
    <!-- Notes Container -->
    <template x-for="note in notes" :key="note.id">
        <div 
            x-show="!note.is_hidden"
            class="absolute pointer-events-auto transition-shadow duration-300"
            :class="{'shadow-2xl z-[8889]': activeNoteId === note.id, 'shadow-lg z-[8888]': activeNoteId !== note.id}"
            :style="`left: ${note.position_x}px; top: ${note.position_y}px; width: ${note.width}px; height: ${note.is_minimized ? '40px' : note.height + 'px'};`"
            @mousedown="focusNote(note.id)"
        >
            <!-- Note Card -->
            <div 
                class="flex flex-col h-full rounded-2xl border border-black/5 overflow-hidden backdrop-blur-sm"
                :style="`background-color: ${note.color || '#fef3c7'}; opacity: ${note.is_minimized ? '0.8' : '1'};`"
            >
                <!-- Drag Handle / Toolbar -->
                <div 
                    class="h-10 shrink-0 px-4 flex items-center justify-between cursor-move select-none border-b border-black/5 bg-black/5"
                    @mousedown="startDrag($event, note)"
                    @touchstart="startDrag($event, note)"
                >
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-black/20"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-black/40">Post-it</span>
                    </div>
                    
                    <div class="flex items-center gap-1">
                        <button @click="toggleMinimize(note)" class="p-1 hover:bg-black/10 rounded-md transition-colors text-black/60" title="Minimizar">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" :d="note.is_minimized ? 'M12 4v16m8-8H4' : 'M20 12H4'" /></svg>
                        </button>
                        <button @click="note.is_preview = !note.is_preview" class="p-1 hover:bg-black/10 rounded-md transition-colors text-black/60" :title="note.is_preview ? 'Editar nota' : 'Ver Markdown'">
                            <!-- Icono Chispas (Ver Markdown) -->
                            <svg x-show="!note.is_preview" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z" />
                            </svg>
                            <!-- Icono Lápiz (Editar) -->
                            <svg x-show="note.is_preview" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click="sendToAi(note)" class="p-1 hover:bg-indigo-500/20 hover:text-indigo-700 rounded-md transition-colors text-black/60" title="Enviar a Ax.ia">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </button>
                        <button @click="hideNote(note)" class="p-1 hover:bg-black/10 rounded-md transition-colors text-black/60" title="Ocultar">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </button>
                        <button @click="deleteNote(note)" class="p-1 hover:bg-red-500/20 hover:text-red-700 rounded-md transition-colors text-black/60" title="Eliminar">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Content Area -->
                <div x-show="!note.is_minimized" class="flex-1 flex flex-col p-4 overflow-hidden">
                    <div class="flex-1 flex flex-col overflow-hidden">
                        <textarea 
                            x-show="!note.is_preview"
                            x-model="note.content"
                            @input.debounce.1000ms="updateNote(note)"
                            @paste="handlePaste($event, note)"
                            class="flex-1 w-full bg-transparent border-none focus:ring-0 p-0 text-sm text-black/80 font-medium leading-relaxed resize-none placeholder:text-black/20"
                            placeholder="Escribe algo aquí... (Markdown soportado)"
                        ></textarea>
                        
                        <div 
                            x-show="note.is_preview"
                            class="flex-1 w-full prose prose-sm max-w-none text-black/80 font-medium leading-relaxed overflow-y-auto select-text prose-p:my-1 prose-headings:my-2 prose-li:my-0 prose-ul:my-1"
                            x-html="renderMarkdown(note.content || '*Escribe algo aquí...*')"
                        ></div>
                    </div>

                    <!-- Attachments Strip -->
                    <template x-if="note.attachments && note.attachments.length > 0">
                        <div class="mt-3 flex flex-wrap gap-2 pt-3 border-t border-black/5">
                            <template x-for="att in note.attachments" :key="att.id || att.path">
                                <div class="group relative">
                                    <template x-if="att.type.startsWith('image/')">
                                        <img :src="att.url" class="w-12 h-12 rounded-lg object-cover shadow-sm border border-white/50">
                                    </template>
                                    <template x-if="att.type && (att.type.startsWith('audio/') || att.type.includes('audio') || att.type.includes('webm') || att.type.includes('mp4') || att.name.includes('note_recording'))">
                                        <div class="flex flex-col gap-2 bg-black/5 p-3 rounded-2xl border border-white/50 w-full group/att relative">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs">🎤</span>
                                                    <audio controls class="h-8 w-40" :src="att.url"></audio>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <button @click.stop="transcribe(note, att)" 
                                                            :disabled="att.transcribing"
                                                            class="p-2 hover:bg-indigo-600 hover:text-white rounded-lg transition-all text-indigo-600/60 bg-indigo-50/50" 
                                                            title="Transcribir con Ax.ia">
                                                        <svg x-show="!att.transcribing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5h12M9 3v2m1.042 11.35a.75.75 0 001.251-.248L12 12l.707 2.102a.75.75 0 001.251.248l3-3a.75.75 0 10-1.06-1.06l-2.189 2.189-.504-1.511a.75.75 0 00-1.41 0l-.504 1.511-2.189-2.189a.75.75 0 00-1.06 1.06l3 3z" /></svg>
                                                        <svg x-show="att.transcribing" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83" stroke-width="2" stroke-linecap="round"/></svg>
                                                    </button>
                                                    <button @click="removeAttachment(note, att)" 
                                                            class="p-1.5 hover:bg-red-600 hover:text-white rounded-lg transition-all text-black/40" 
                                                            title="Eliminar audio">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div x-show="att.transcription" x-text="att.transcription" class="text-[10px] bg-white/50 p-2 rounded-lg border border-black/5 italic text-gray-700 leading-tight"></div>
                                            
                                            <!-- Transcribing indicator -->
                                            <div x-show="att.transcribing" class="flex items-center gap-2 text-[9px] font-black uppercase tracking-tighter text-indigo-600 animate-pulse bg-indigo-50/80 px-2.5 py-1 rounded-full w-max border border-indigo-100">
                                                <span class="w-1.5 h-1.5 bg-indigo-600 rounded-full animate-ping"></span>
                                                Transcribiendo...
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Bottom Actions -->
                    <div class="mt-3 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1">
                            <template x-for="c in ['#fef3c7', '#dcfce7', '#dbeafe', '#f3e8ff', '#fee2e2']">
                                <button 
                                    @click="note.color = c; updateNote(note)" 
                                    class="w-4 h-4 rounded-full border border-black/5 transition-transform hover:scale-125"
                                    :style="`background-color: ${c}`"
                                    :class="{'ring-2 ring-black/20 ring-offset-1': note.color === c}"
                                ></button>
                            </template>
                        </div>

                        <div class="flex items-center gap-2">
                            <button @click="startRecording(note)" 
                                    class="p-1.5 hover:bg-black/5 rounded-full transition-all duration-300 relative group"
                                    :class="isRecording && recordingNoteId === note.id ? 'text-red-600 bg-red-50' : 'text-black/60 hover:text-indigo-600'"
                                    :disabled="isRecording && recordingNoteId !== note.id"
                                    :title="isRecording && recordingNoteId === note.id ? 'Detener grabación' : 'Grabar audio'">
                                
                                <!-- Icono Micro (normal) -->
                                <svg x-show="!(isRecording && recordingNoteId === note.id)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-20a3 3 0 013 3v10a3 3 0 01-3 3 3 3 0 01-3-3V7a3 3 0 013-3z" />
                                </svg>
                                
                                <!-- Icono Stop (grabando) -->
                                <div x-show="isRecording && recordingNoteId === note.id" class="flex items-center justify-center">
                                    <div class="w-3 h-3 bg-red-600 rounded-sm animate-pulse"></div>
                                </div>

                                <!-- Tooltip dinámico -->
                                <span x-show="isRecording && recordingNoteId === note.id" class="absolute right-full mr-2 px-2 py-1 bg-red-600 text-white text-[8px] font-black rounded-md uppercase tracking-widest whitespace-nowrap animate-pulse flex items-center gap-1 shadow-lg">
                                    <span class="w-1.5 h-1.5 bg-white rounded-full animate-ping"></span>
                                    Grabando <span class="ml-1 opacity-80 font-bold" x-text="`${recordingTime}s`"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Resize Handle -->
                <div 
                    x-show="!note.is_minimized"
                    class="absolute bottom-0 right-0 w-4 h-4 cursor-se-resize flex items-center justify-center group"
                    @mousedown="startResize($event, note)"
                >
                    <div class="w-1.5 h-1.5 rounded-full bg-black/10 group-hover:bg-black/30 transition-colors"></div>
                </div>
            </div>
        </div>
    </template>

    <!-- Global Toggle Button (if hidden) -->
    <button 
        x-show="notes.length > 0 && !allVisible"
        @mousedown="startButtonDrag($event)"
        @touchstart="startButtonDrag($event)"
        @click="if(!wasButtonDragged) showAll()"
        class="fixed p-4 bg-amber-400 text-amber-900 rounded-full shadow-2xl pointer-events-auto hover:scale-110 transition-transform active:scale-95 flex items-center gap-2 font-black uppercase tracking-widest text-[10px] z-[8887] select-none"
        :class="isDraggingButton ? 'cursor-grabbing scale-110' : 'cursor-grab'"
        :style="`right: ${buttonPos.right}px; bottom: ${buttonPos.bottom}px; touch-action: none;`"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
        <span x-text="notes.length"></span> Notas
    </button>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('sientiaQuickNotes', () => ({
        notes: [],
        activeNoteId: null,
        isDragging: false,
        isResizing: false,
        dragTarget: null,
        resizeTarget: null,
        isRecording: false,
        recordingNoteId: null,
        mediaRecorder: null,
        audioChunks: [],
        recordingTime: 0,
        recordingInterval: null,
        maxRecordingTime: {{ \App\Models\Setting::get('quick_notes_audio_max_duration', 30) }},
        
        // Button Dragging State
        buttonPos: { right: 24, bottom: 220 },
        isDraggingButton: false,
        wasButtonDragged: false,
        buttonDragOffset: { x: 0, y: 0 },
        
        async init() {
            await this.refreshNotes();
        },

        renderMarkdown(content) {
            if (!content) return '';
            try {
                return marked.parse(content, { breaks: true, gfm: true });
            } catch (e) {
                return content;
            }
        },

        processAttachments(attachments) {
            if (!attachments) return [];
            return attachments.map(att => ({
                ...att,
                transcribing: false,
                transcription: att.transcription || null
            }));
        },

        async refreshNotes() {
            try {
                const response = await fetch('/quick-notes');
                const data = await response.json();
                this.notes = data.map(n => {
                    n.attachments = this.processAttachments(n.attachments);
                    n.is_preview = false;
                    return n;
                });
            } catch (e) {
                console.error('Error fetching notes:', e);
            }
        },

        sendToAi(note) {
            window.dispatchEvent(new CustomEvent('ai:inject-note', { 
                detail: { 
                    content: note.content
                } 
            }));
        },

        get allVisible() {
            return this.notes.some(n => !n.is_hidden);
        },

        async createNote() {
            try {
                const response = await fetch('/quick-notes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        // Calculate center of screen
                        position_x: Math.max(20, (window.innerWidth / 2) - 150 + (this.notes.length * 20 % 100)),
                        position_y: Math.max(20, (window.innerHeight / 2) - 150 + (this.notes.length * 20 % 100)),
                        width: 300,
                        height: 300,
                        color: '#fef3c7',
                        is_pinned: false,
                        is_minimized: false,
                        is_hidden: false
                    })
                });
                const newNote = await response.json();
                newNote.attachments = this.processAttachments(newNote.attachments);
                newNote.is_preview = false;
                this.notes.push(newNote);
                this.focusNote(newNote.id);
            } catch (e) {
                console.error('Error creating note:', e);
            }
        },

        async updateNote(note) {
            try {
                await fetch(`/quick-notes/${note.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        content: note.content,
                        position_x: note.position_x,
                        position_y: note.position_y,
                        width: note.width,
                        height: note.height,
                        color: note.color,
                        is_pinned: !!note.is_pinned,
                        is_minimized: !!note.is_minimized,
                        is_hidden: !!note.is_hidden
                    })
                });
            } catch (e) {
                console.error('Error updating note:', e);
            }
        },

        async deleteNote(note) {
            const isDark = document.documentElement.classList.contains('dark');
            const result = await Swal.fire({
                title: '<span class="text-xs font-black uppercase tracking-widest text-red-600">¿Eliminar Nota?</span>',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: isDark ? '#1e293b' : '#94a3b8',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f1f5f9' : '#1e293b',
                customClass: { popup: 'rounded-[2rem] border-none shadow-2xl' }
            });

            if (!result.isConfirmed) return;
            
            try {
                await fetch(`/quick-notes/${note.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                this.notes = this.notes.filter(n => n.id !== note.id);
            } catch (e) {
                console.error('Error deleting note:', e);
            }
        },

        hideNote(note) {
            note.is_hidden = true;
            this.updateNote(note);
        },

        toggleMinimize(note) {
            note.is_minimized = !note.is_minimized;
            this.updateNote(note);
        },

        showAll() {
            this.notes.forEach(n => {
                if (n.is_hidden) {
                    n.is_hidden = false;
                    this.updateNote(n);
                }
            });
        },

        toggleAll() {
            const anyVisible = this.notes.some(n => !n.is_hidden);
            this.notes.forEach(n => {
                n.is_hidden = anyVisible;
                this.updateNote(n);
            });
        },

        focusNote(id) {
            this.activeNoteId = id;
        },

        startDrag(e, note) {
            this.isDragging = true;
            this.dragTarget = note;
            this.focusNote(note.id);
            this.dragOffset = {
                x: (e.clientX || (e.touches && e.touches[0].clientX)) - note.position_x,
                y: (e.clientY || (e.touches && e.touches[0].clientY)) - note.position_y
            };
        },

        startResize(e, note) {
            e.stopPropagation();
            this.isResizing = true;
            this.resizeTarget = note;
            this.initialSize = {
                width: note.width,
                height: note.height,
                x: e.clientX,
                y: e.clientY
            };
        },

        startButtonDrag(e) {
            this.isDraggingButton = true;
            this.wasButtonDragged = false;
            const event = e.type.includes('touch') ? e.touches[0] : e;
            this.buttonDragOffset = {
                x: window.innerWidth - event.clientX - this.buttonPos.right,
                y: window.innerHeight - event.clientY - this.buttonPos.bottom
            };
        },

        handleMouseMove(e) {
            const event = (e.touches && e.touches[0]) ? e.touches[0] : e;

            // Notes Dragging
            if (this.isDragging && this.dragTarget) {
                if (e.cancelable) e.preventDefault();
                this.dragTarget.position_x = Math.round(event.clientX - this.dragOffset.x);
                this.dragTarget.position_y = Math.round(event.clientY - this.dragOffset.y);
                
                this.dragTarget.position_x = Math.max(0, Math.min(this.dragTarget.position_x, window.innerWidth - this.dragTarget.width));
                this.dragTarget.position_y = Math.max(0, Math.min(this.dragTarget.position_y, window.innerHeight - (this.dragTarget.is_minimized ? 40 : this.dragTarget.height)));
            }
            
            // Notes Resizing
            if (this.isResizing && this.resizeTarget) {
                if (e.cancelable) e.preventDefault();
                const deltaX = event.clientX - this.initialSize.x;
                const deltaY = event.clientY - this.initialSize.y;
                this.resizeTarget.width = Math.round(Math.max(200, this.initialSize.width + deltaX));
                this.resizeTarget.height = Math.round(Math.max(150, this.initialSize.height + deltaY));
            }

            // Global Button Dragging
            if (this.isDraggingButton) {
                const newRight = window.innerWidth - event.clientX - this.buttonDragOffset.x;
                const newBottom = window.innerHeight - event.clientY - this.buttonDragOffset.y;
                
                if (Math.abs(newRight - this.buttonPos.right) > 3 || Math.abs(newBottom - this.buttonPos.bottom) > 3) {
                    this.wasButtonDragged = true;
                }
                
                this.buttonPos.right = Math.max(10, Math.min(newRight, window.innerWidth - 60));
                this.buttonPos.bottom = Math.max(10, Math.min(newBottom, window.innerHeight - 60));
            }
        },

        stopMoving() {
            if (this.isDragging || this.isResizing) {
                if (this.dragTarget) this.updateNote(this.dragTarget);
                if (this.resizeTarget) this.updateNote(this.resizeTarget);
            }
            this.isDragging = false;
            this.isResizing = false;
            this.dragTarget = null;
            this.resizeTarget = null;
            
            // Button Dragging
            setTimeout(() => {
                this.isDraggingButton = false;
            }, 50);
        },

        toggleMinimize(note) {
            note.is_minimized = !note.is_minimized;
            this.updateNote(note);
        },


        closeAll() {
            this.activeNoteId = null;
        },

        async handlePaste(e, note) {
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;
            for (let index in items) {
                const item = items[index];
                if (item.kind === 'file') {
                    const blob = item.getAsFile();
                    if (!blob) continue;
                    
                    const formData = new FormData();
                    formData.append('file', blob);
                    
                    try {
                        const response = await fetch(`/quick-notes/${note.id}/attachment`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: formData
                        });
                        const updatedNote = await response.json();
                        const processed = this.processAttachments(updatedNote.attachments);
                        
                        const nIdx = this.notes.findIndex(n => n.id === note.id);
                        if (nIdx !== -1) {
                            this.notes[nIdx].attachments = processed;
                            this.notes = [...this.notes];
                        }
                    } catch (e) {
                        console.error('Error uploading paste:', e);
                    }
                }
            }
        },

        async startRecording(note) {
            if (this.isRecording) {
                this.stopRecording(note);
                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                
                // Detect supported mime type for mobile compatibility (iOS/Android)
                const mimeType = ['audio/webm', 'audio/mp4', 'audio/ogg', 'audio/wav']
                    .find(type => MediaRecorder.isTypeSupported(type)) || '';
                
                this.mediaRecorder = new MediaRecorder(stream, mimeType ? { mimeType } : {});
                this.audioChunks = [];
                this.recordingNoteId = note.id;
                this.recordingTime = this.maxRecordingTime;

                // Timer interval
                this.recordingInterval = setInterval(() => {
                    this.recordingTime--;
                    if (this.recordingTime <= 0) {
                        this.stopRecording(note);
                    }
                }, 1000);

                this.mediaRecorder.ondataavailable = (event) => {
                    this.audioChunks.push(event.data);
                };

                this.mediaRecorder.onstop = async () => {
                    console.log("QuickNotes: Grabación detenida. Chunks:", this.audioChunks.length);
                    const finalMimeType = this.mediaRecorder.mimeType || 'audio/webm';
                    console.log("QuickNotes: MIME Type detectado:", finalMimeType);
                    
                    const extension = finalMimeType.includes('mp4') ? 'm4a' : 
                                     (finalMimeType.includes('webm') ? 'webm' : 
                                     (finalMimeType.includes('ogg') ? 'ogg' : 'wav'));
                    
                    try {
                        const audioBlob = new Blob(this.audioChunks, { type: finalMimeType });
                        const audioFile = new File([audioBlob], `note_recording.${extension}`, { type: finalMimeType });
                        console.log("QuickNotes: Archivo creado:", audioFile.name, audioFile.size, "bytes");
                        
                        const formData = new FormData();
                        formData.append('file', audioFile);
                        
                        const response = await fetch(`/quick-notes/${note.id}/attachment`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: formData
                        });
                        
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        
                        const updatedNote = await response.json();
                        const processed = this.processAttachments(updatedNote.attachments);
                        
                        const index = this.notes.findIndex(n => n.id === note.id);
                        if (index !== -1) {
                            this.notes[index].attachments = processed;
                            this.notes = [...this.notes];
                        }
                        console.log("QuickNotes: Subida completada.");
                    } catch (e) {
                        console.error('QuickNotes: Error en subida de grabación:', e);
                    } finally {
                        this.recordingNoteId = null;
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                        }
                    }
                };

                this.mediaRecorder.start(1000);
                this.isRecording = true;
            } catch (err) {
                console.error('Error recording:', err);
            }
        },

        stopRecording(note) {
            if (this.mediaRecorder && this.isRecording) {
                this.mediaRecorder.stop();
                this.isRecording = false;
                if (this.recordingInterval) {
                    clearInterval(this.recordingInterval);
                    this.recordingInterval = null;
                }
            }
        },

        async transcribe(note, att) {
            const isDark = document.documentElement.classList.contains('dark');
            
            if (!att.id) {
                Swal.fire({
                    icon: 'info',
                    title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">Actualización Necesaria</span>',
                    text: 'Este audio necesita sincronizarse. Por favor, refresca la página (F5) y vuelve a intentarlo.',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f1f5f9' : '#1e293b',
                    customClass: { popup: 'rounded-[2rem]' }
                });
                return;
            }

            if (att.transcribing) return;
            att.transcribing = true;

            // Feedback inmediato
            const toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f1f5f9' : '#1e293b',
            });
            toast.fire({
                icon: 'info',
                title: 'Ax.ia está escuchando su audio...'
            });
            
            try {
                const url = window.location.origin + `/quick-notes/${note.id}/attachment/${att.id}/transcribe`;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Error del servidor');
                }

                const data = await response.json();
                if (data.transcription) {
                    att.transcription = data.transcription;
                    
                    const result = await Swal.fire({
                        title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">Transcripción Lista</span>',
                        html: `<div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl text-left italic text-sm text-gray-700 dark:text-gray-300 border border-indigo-100 dark:border-indigo-800/30">${data.transcription}</div><p class="mt-4 text-[10px] font-black text-gray-500 text-center uppercase tracking-widest">¿Qué quieres hacer?</p>`,
                        icon: 'success',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonText: 'Añadir y Mantener',
                        denyButtonText: 'Añadir y BORRAR AUDIO',
                        cancelButtonText: 'Cerrar',
                        confirmButtonColor: '#4f46e5',
                        denyButtonColor: '#ef4444',
                        background: isDark ? '#0f172a' : '#ffffff',
                        color: isDark ? '#f1f5f9' : '#1e293b',
                        customClass: { popup: 'rounded-[2.5rem]' }
                    });

                    if (result.isConfirmed || result.isDenied) {
                        note.content = (note.content || '') + '\n\n[Transcripción]: ' + data.transcription;
                        await this.updateNote(note);
                        
                        if (result.isDenied) {
                            // Borrar audio directamente si se eligió esa opción
                            this.performDeleteAttachment(note, att);
                        }
                    }
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo obtener la transcripción.', background: isDark ? '#0f172a' : '#ffffff' });
                }
            } catch (e) {
                console.error('Transcription error:', e);
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Fallo en Transcripción', 
                    text: e.message || 'Error de conexión',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f1f5f9' : '#1e293b'
                });
            } finally {
                att.transcribing = false;
            }
        },

        async removeAttachment(note, att) {
            const isDark = document.documentElement.classList.contains('dark');
            const result = await Swal.fire({
                title: '<span class="text-xs font-black uppercase tracking-widest text-red-600">¿Eliminar Audio?</span>',
                text: "¿Estás seguro de que quieres borrar este archivo de audio?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: isDark ? '#1e293b' : '#94a3b8',
                confirmButtonText: 'Sí, borrar',
                cancelButtonText: 'Cancelar',
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f1f5f9' : '#1e293b',
                customClass: { popup: 'rounded-[2rem]' }
            });

            if (!result.isConfirmed) return;
            this.performDeleteAttachment(note, att);
        },

        async performDeleteAttachment(note, att) {
            try {
                await fetch(`/quick-notes/${note.id}/attachment/${att.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                // Force reactivity
                const idx = this.notes.findIndex(n => n.id === note.id);
                if (idx !== -1) {
                    this.notes[idx].attachments = this.notes[idx].attachments.filter(a => a.id !== att.id);
                    this.notes = [...this.notes];
                }
            } catch (e) {
                console.error('Error removing attachment:', e);
            }
        }
    }));
});
</script>
