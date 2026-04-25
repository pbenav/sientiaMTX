<div x-data="sientiaQuickNotes()" 
     class="fixed inset-0 pointer-events-none z-[8888]"
     @keydown.window.escape="closeAll()"
     @quick-note:create.window="createNote()">
    
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
                        <button @click="toggleMinimize(note)" class="p-1 hover:bg-black/10 rounded-md transition-colors text-black/60">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" :d="note.is_minimized ? 'M12 4v16m8-8H4' : 'M20 12H4'" /></svg>
                        </button>
                        <button @click="deleteNote(note)" class="p-1 hover:bg-red-500/20 hover:text-red-700 rounded-md transition-colors text-black/60">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>

                <!-- Content Area -->
                <div x-show="!note.is_minimized" class="flex-1 flex flex-col p-4 overflow-hidden">
                    <textarea 
                        x-model="note.content"
                        @input.debounce.1000ms="updateNote(note)"
                        @paste="handlePaste($event, note)"
                        class="flex-1 w-full bg-transparent border-none focus:ring-0 p-0 text-sm text-black/80 font-medium leading-relaxed resize-none placeholder:text-black/20"
                        placeholder="Escribe algo aquí... pega imágenes o graba audio"
                    ></textarea>

                    <!-- Attachments Strip -->
                    <template x-if="note.attachments && note.attachments.length > 0">
                        <div class="mt-3 flex flex-wrap gap-2 pt-3 border-t border-black/5">
                            <template x-for="att in note.attachments" :key="att.path">
                                <div class="group relative">
                                    <template x-if="att.type.startsWith('image/')">
                                        <img :src="att.url" class="w-12 h-12 rounded-lg object-cover shadow-sm border border-white/50">
                                    </template>
                                    <template x-if="att.type.startsWith('audio/')">
                                        <div class="w-12 h-12 rounded-lg bg-black/5 flex items-center justify-center text-xl shadow-sm border border-white/50">🎤</div>
                                    </template>
                                    
                                    <!-- Full view on hover/click could be added here -->
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
                            <button @click="startRecording(note)" :disabled="isRecording" class="p-1.5 hover:bg-black/5 rounded-full transition-colors text-black/60" :class="{'text-red-600 animate-pulse': isRecording && recordingNoteId === note.id}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-20a3 3 0 013 3v10a3 3 0 01-3 3 3 3 0 01-3-3V7a3 3 0 013-3z" /></svg>
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
        @click="showAll()"
        class="fixed bottom-32 right-6 p-4 bg-amber-400 text-amber-900 rounded-full shadow-2xl pointer-events-auto hover:scale-110 transition-transform active:scale-95 flex items-center gap-2 font-black uppercase tracking-widest text-[10px] z-[8887]"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
        <span x-text="notes.length"></span> Notas
    </button>
</div>

<script>
function sientiaQuickNotes() {
    return {
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
        
        async init() {
            const response = await fetch('/quick-notes');
            this.notes = await response.json();
            
            // Listen for window resize to keep notes within bounds
            window.addEventListener('mousemove', (e) => this.handleMouseMove(e));
            window.addEventListener('mouseup', () => this.stopMoving());
            window.addEventListener('touchmove', (e) => this.handleMouseMove(e.touches[0]));
            window.addEventListener('touchend', () => this.stopMoving());
        },

        get allVisible() {
            return this.notes.some(n => !n.is_hidden);
        },

        async createNote() {
            const response = await fetch('/quick-notes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    position_x: 100 + (this.notes.length * 20),
                    position_y: 100 + (this.notes.length * 20),
                    color: '#fef3c7'
                })
            });
            const newNote = await response.json();
            this.notes.push(newNote);
            this.focusNote(newNote.id);
        },

        async updateNote(note) {
            await fetch(`/quick-notes/${note.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    content: note.content,
                    position_x: note.position_x,
                    position_y: note.position_y,
                    width: note.width,
                    height: note.height,
                    color: note.color,
                    is_pinned: note.is_pinned,
                    is_minimized: note.is_minimized
                })
            });
        },

        async deleteNote(note) {
            if (!confirm('¿Seguro que quieres eliminar esta nota?')) return;
            
            await fetch(`/quick-notes/${note.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            this.notes = this.notes.filter(n => n.id !== note.id);
        },

        focusNote(id) {
            this.activeNoteId = id;
        },

        startDrag(e, note) {
            this.isDragging = true;
            this.dragTarget = note;
            this.focusNote(note.id);
            this.dragOffset = {
                x: (e.clientX || e.touches[0].clientX) - note.position_x,
                y: (e.clientY || e.touches[0].clientY) - note.position_y
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

        handleMouseMove(e) {
            if (this.isDragging && this.dragTarget) {
                this.dragTarget.position_x = e.clientX - this.dragOffset.x;
                this.dragTarget.position_y = e.clientY - this.dragOffset.y;
                
                // Constraints
                this.dragTarget.position_x = Math.max(0, Math.min(this.dragTarget.position_x, window.innerWidth - this.dragTarget.width));
                this.dragTarget.position_y = Math.max(0, Math.min(this.dragTarget.position_y, window.innerHeight - (this.dragTarget.is_minimized ? 40 : this.dragTarget.height)));
            }
            
            if (this.isResizing && this.resizeTarget) {
                const deltaX = e.clientX - this.initialSize.x;
                const deltaY = e.clientY - this.initialSize.y;
                this.resizeTarget.width = Math.max(200, this.initialSize.width + deltaX);
                this.resizeTarget.height = Math.max(150, this.initialSize.height + deltaY);
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
        },

        toggleMinimize(note) {
            note.is_minimized = !note.is_minimized;
            this.updateNote(note);
        },

        showAll() {
            this.notes.forEach(n => n.is_hidden = false);
        },

        closeAll() {
            // Optional: just un-focus or actually hide
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
                    
                    const response = await fetch(`/quick-notes/${note.id}/attachment`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formData
                    });
                    const updatedNote = await response.json();
                    note.attachments = updatedNote.attachments;
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
                this.mediaRecorder = new MediaRecorder(stream);
                this.audioChunks = [];
                this.recordingNoteId = note.id;

                this.mediaRecorder.ondataavailable = (event) => {
                    this.audioChunks.push(event.data);
                };

                this.mediaRecorder.onstop = async () => {
                    const audioBlob = new Blob(this.audioChunks, { type: 'audio/webm' });
                    const formData = new FormData();
                    formData.append('file', audioBlob, 'note_recording.webm');
                    
                    const response = await fetch(`/quick-notes/${note.id}/attachment`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formData
                    });
                    const updatedNote = await response.json();
                    note.attachments = updatedNote.attachments;
                    this.recordingNoteId = null;
                    
                    stream.getTracks().forEach(track => track.stop());
                };

                this.mediaRecorder.start();
                this.isRecording = true;
            } catch (err) {
                console.error('Error recording:', err);
            }
        },

        stopRecording(note) {
            if (this.mediaRecorder && this.isRecording) {
                this.mediaRecorder.stop();
                this.isRecording = false;
            }
        }
    }
}
</script>
