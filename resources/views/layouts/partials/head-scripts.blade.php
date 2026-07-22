    <!-- Sientia Direct Chat & Video Call Engine -->
    @auth
    <script>
        document.addEventListener('alpine:init', () => {


            Alpine.store('chatStore', {
                unreadConversations: [],
                setUnread(list) { this.unreadConversations = list; },
                markAsRead(senderId) { this.unreadConversations = this.unreadConversations.filter(c => parseInt(c.id) !== parseInt(senderId)); },
                hasUnread(senderId) { return this.unreadConversations.some(c => parseInt(c.id) === parseInt(senderId)); },
                get totalCount() { return this.unreadConversations.length; }
            });

            Alpine.data('sientiaChat', () => ({
                open: false,
                messages: [],
                message: '',
                member: { id: null, name: '', photo: '', status: '', email: '', telegram: '' },
                isTyping: false,
                chatSoundsEnabled: {{ (auth()->check() && (auth()->user()->notification_settings['chat_sounds'] ?? true)) ? 'true' : 'false' }},
                activeCallRoom: null,
                incomingCall: null,
                pollInterval: null,
                presenceInterval: null,
                lastUserActivity: Date.now(),
                presenceIdleThresholdMs: 5 * 60 * 1000,
                titleInterval: null,
                originalTitle: '',
                lastNotifiedMsgId: null,
                callRingInterval: null,
                showEmojis: false,
                pendingFile: null,
                pendingDriveFile: null,
                previewUrl: null,
                isUploading: false,
                replyingTo: null,
                addingMember: false,
                allUsers: [],
                searchUserQuery: '',
                recentGroups: [],
                showingRecentGroups: false,

                getFilteredUsersForAdd() {
                    const all = this.allUsers || [];
                    if (!this.searchUserQuery) return all;
                    const q = String(this.searchUserQuery).toLowerCase();
                    return all.filter(u => u.name && String(u.name).toLowerCase().includes(q));
                },
                fetchUsersForChat() {
                    if (this.allUsers.length > 0) return;
                    fetch('/chat/users')
                        .then(r => r.json())
                        .then(d => this.allUsers = d.users || [])
                        .catch(e => console.error('Error fetching users:', e));
                },
                fetchRecentGroups() {
                    fetch('/chat/groups/recent')
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) {
                                this.recentGroups = d.groups || [];
                            }
                        })
                        .catch(e => console.error('Error fetching recent groups:', e));
                },
                addMemberToGroup(userId) {
                    const isGroup = String(this.member.id).startsWith('group_');
                    if (isGroup) {
                        const groupId = String(this.member.id).replace('group_', '');
                        fetch(`/chat/group/${groupId}/members`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                            body: JSON.stringify({ user_id: userId })
                        })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) {
                                Swal.fire({ icon: 'success', title: 'Añadido', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                                this.member.status = d.status;
                                this.addingMember = false;
                                this.searchUserQuery = '';
                                this.fetchMessages();
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: d.message, toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                            }
                        })
                        .catch(e => Swal.fire({ icon: 'error', title: 'Error de red', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false }));
                    } else {
                        fetch('/chat/group', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                            body: JSON.stringify({ receiver_ids: [this.member.id, userId] })
                        })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) {
                                Swal.fire({ icon: 'success', title: 'Grupo creado', text: 'Chat convertido a grupal', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                                this.addingMember = false;
                                this.searchUserQuery = '';
                                this.openChat(d.group);
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: d.message, toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                            }
                        })
                        .catch(e => Swal.fire({ icon: 'error', title: 'Error de red', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false }));
                    }
                },
                renameActiveGroup(newName) {
                    if (!newName.trim()) return;
                    const groupId = String(this.member.id).replace('group_', '');
                    fetch(`/chat/group/${groupId}/rename`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ name: newName })
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            this.member.name = d.name;
                            try { localStorage.setItem('sientia_last_chat', JSON.stringify(this.member)); } catch(e) {}
                            Swal.fire({ icon: 'success', title: 'Grupo renombrado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: d.message, toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                        }
                    })
                    .catch(e => console.error('Error renaming group:', e));
                },
                deleteGroupChat(groupId) {
                    const cleanGroupId = String(groupId).replace('group_', '');
                    Swal.fire({
                        title: '¿Eliminar Grupo?',
                        text: 'Esta acción borrará el grupo, todos sus mensajes y archivos adjuntos permanentemente. ¿Estás seguro?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        customClass: {
                            popup: 'rounded-[2rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-xl px-6 py-2.5 text-[11px] font-black uppercase tracking-widest',
                            cancelButton: 'rounded-xl px-6 py-2.5 text-[11px] font-black uppercase tracking-widest'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/chat/group/${cleanGroupId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json'
                                }
                            })
                            .then(r => r.json())
                            .then(d => {
                                if (d.success) {
                                    Swal.fire({ icon: 'success', title: 'Grupo eliminado', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                                    if (this.member && String(this.member.id) === `group_${cleanGroupId}`) {
                                        this.chatOpen = false;
                                        this.member = null;
                                        try { localStorage.removeItem('sientia_last_chat'); } catch(e) {}
                                    }
                                    this.fetchRecentGroups();
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Error', text: d.message, toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                                }
                            })
                            .catch(e => console.error('Error deleting group:', e));
                        }
                    });
                },

                init() {
                    this.originalTitle = document.title;

                    this.pollInterval = setInterval(() => this.checkNewMessages(), 4000);

                    // --- Real Presence System ---
                    // Track genuine user activity (mouse, keyboard, touch, scroll)
                    const activityEvents = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click', 'focus'];
                    const recordActivity = () => { this.lastUserActivity = Date.now(); };
                    activityEvents.forEach(evt => window.addEventListener(evt, recordActivity, { passive: true }));

                    // Send presence ping every 60s, but ONLY if:
                    // 1. The tab is visible (document not hidden)
                    // 2. User has been active in the last 5 minutes
                    const sendPresencePing = () => {
                        if (document.hidden) return;
                        const idleMs = Date.now() - this.lastUserActivity;
                        if (idleMs > this.presenceIdleThresholdMs) return;
                        fetch('/comms/presence', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                        }).catch(() => {});
                    };

                    // Fire immediately (user just loaded the page = real activity)
                    sendPresencePing();
                    this.presenceInterval = setInterval(sendPresencePing, 60000);

                    // Unlock AudioContext on first user interaction (Brave/Firefox fix)
                    const unlockAudio = () => {
                        if (window.sientiaAudioCtx && window.sientiaAudioCtx.state === 'suspended') {
                            window.sientiaAudioCtx.resume();
                        }
                        document.removeEventListener('click', unlockAudio);
                        document.removeEventListener('keydown', unlockAudio);
                    };
                    document.addEventListener('click', unlockAudio);
                    document.addEventListener('keydown', unlockAudio);
                },

                openChat(detail) {
                    this.member = detail;
                    try { localStorage.setItem('sientia_last_chat', JSON.stringify(detail)); } catch(e) {}
                    this.open = true;
                    this.activeCallRoom = null;
                    this.incomingCall = null;
                    this.replyingTo = null;
                    this.clearPendingAttachments();
                    Alpine.store('chatStore').markAsRead(detail.id);
                    this.fetchMessages();
                    this.$nextTick(() => {
                        const input = this.$refs.chatInput;
                        if (input) input.focus();
                    });
                },

                openLastChat() {
                    if (Alpine.store('chatStore').unreadConversations.length > 0) {
                        this.openChat(Alpine.store('chatStore').unreadConversations[0]);
                        return;
                    }
                    if (this.member && this.member.id) {
                        this.open = true;
                        this.fetchMessages();
                        this.$nextTick(() => {
                            const input = this.$refs.chatInput;
                            if (input) input.focus();
                        });
                    } else {
                        const lastChatJson = localStorage.getItem('sientia_last_chat');
                        if (lastChatJson) {
                            try {
                                const lastChat = JSON.parse(lastChatJson);
                                if (lastChat && lastChat.id) {
                                    this.openChat(lastChat);
                                    return;
                                }
                            } catch (e) {}
                        }
                        Swal.fire({ icon: 'info', title: 'Chat Interno', text: 'No tienes ninguna sala de chat activa. Selecciona un usuario o grupo en la Red Activa o abre un mensaje pendiente.', toast: true, position: 'top-end', timer: 4000, showConfirmButton: false });
                    }
                },

                close() { this.open = false; this.activeCallRoom = null; this.stopFlashAndSound(); },

                fetchMessages() {
                    if (!this.member.id) return;
                    fetch('/chat/' + this.member.id + '?_=' + Date.now(), {
                        headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache', 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.member) this.member = { ...this.member, ...data.member };
                        this.messages = data.messages || [];
                        this.$nextTick(() => this.scrollToBottom());
                    })
                    .catch(err => console.error('Error fetching chat messages:', err));
                },

                sendMessage() {
                    if (this.isUploading) return;
                    const text = this.message.trim();
                    if (!text && !this.pendingFile && !this.pendingDriveFile) return;
                    if (!this.member.id) return;

                    this.isUploading = true;
                    this.message = '';
                    this.showEmojis = false;

                    const optimisticMsg = {
                        id: Date.now(),
                        sender: 'me',
                        text: text,
                        file_name: this.pendingDriveFile ? this.pendingDriveFile.name : (this.pendingFile ? this.pendingFile.name : null),
                        file_type: this.pendingDriveFile ? 'file' : (this.pendingFile ? (this.pendingFile.type.startsWith('image/') ? 'image' : 'file') : null),
                        file_url: this.previewUrl,
                        storage_provider: this.pendingDriveFile ? 'google' : 'local',
                        web_view_link: this.pendingDriveFile ? this.pendingDriveFile.webViewLink : null,
                        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                        parent_id: this.replyingTo ? this.replyingTo.id : null,
                        parent_text: this.replyingTo ? (this.replyingTo.text || (this.replyingTo.file_name ? '📎 ' + this.replyingTo.file_name : '...')) : null,
                        parent_sender_name: this.replyingTo ? (this.replyingTo.sender === 'me' ? 'Tú' : this.member.name) : null
                    };

                    this.messages.push(optimisticMsg);
                    const replyToId = this.replyingTo ? this.replyingTo.id : null;
                    this.replyingTo = null;
                    const fileToUpload = this.pendingFile;
                    const driveFileToUpload = this.pendingDriveFile;
                    this.clearPendingAttachments();
                    this.$nextTick(() => this.scrollToBottom());

                    const formData = new FormData();
                    formData.append('receiver_id', this.member.id);
                    if (text) formData.append('message', text);
                    if (fileToUpload) formData.append('file', fileToUpload);
                    if (driveFileToUpload) formData.append('drive_file', JSON.stringify(driveFileToUpload));
                    if (replyToId) formData.append('parent_id', replyToId);

                    fetch('/chat', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                        body: formData
                    })
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(data => {
                        // Actualizar el mensaje optimista con los datos reales del servidor (especialmente el ID real)
                        const idx = this.messages.findIndex(m => m.id === optimisticMsg.id);
                        if (idx !== -1) {
                            this.messages[idx] = { ...this.messages[idx], ...data.message, sender: 'me' };
                        } else {
                            this.fetchMessages(); // Fallback por si acaso
                        }
                    })
                    .catch(err => {
                        console.error('Error sending message:', err);
                        // Quitar el mensaje optimista si falló el envío
                        this.messages = this.messages.filter(m => m.id !== optimisticMsg.id);
                        Swal.fire({ icon: 'error', title: 'Error al enviar', text: 'No se pudo enviar el mensaje.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                    })
                    .finally(() => this.isUploading = false);
                },

                deleteMessage(msgId) {
                    Swal.fire({
                        title: '¿Eliminar mensaje?',
                        text: 'Esta acción no se puede deshacer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Eliminar',
                        cancelButtonText: 'Cancelar',
                        customClass: {
                            popup: 'rounded-[2rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-xl px-6 py-2.5 text-[11px] font-black uppercase tracking-widest',
                            cancelButton: 'rounded-xl px-6 py-2.5 text-[11px] font-black uppercase tracking-widest'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(`/chat/message/${msgId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json'
                                }
                            })
                            .then(r => r.json())
                            .then(d => {
                                if (d.success) {
                                    this.messages = this.messages.filter(m => m.id !== msgId);
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Error', text: d.message, toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                                }
                            })
                            .catch(e => console.error('Error deleting message:', e));
                        }
                    });
                },

                clearChat() {
                    if (!this.member || !this.member.id) return;

                    Swal.fire({
                        title: '🧹 ¿LIMPIAR CHAT?',
                        text: 'Se eliminarán todos los mensajes de esta conversación de forma permanente.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, limpiar 🧹',
                        cancelButtonText: 'Cancelar ❌',
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#4b5563',
                        customClass: {
                            popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-950 dark:text-white',
                            confirmButton: 'rounded-2xl px-6 py-3.5 uppercase tracking-widest font-black text-[10px] focus:ring-0',
                            cancelButton: 'rounded-2xl px-6 py-3.5 uppercase tracking-widest font-black text-[10px] focus:ring-0'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('/chat/clear/' + this.member.id, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json'
                                }
                            }).then(r => r.json())
                            .then(d => {
                                if(d.success) {
                                    this.messages = [];
                                    Swal.fire({
                                        title: '¡Limpiado! 🧹',
                                        text: 'El historial de chat se ha borrado.',
                                        icon: 'success',
                                        timer: 1500,
                                        showConfirmButton: false,
                                        customClass: {
                                            popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-950 dark:text-white'
                                        }
                                    });
                                }
                            });
                        }
                    });
                },

                checkNewMessages() {
                    fetch('/comms/heartbeat?_=' + Date.now(), {
                        headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache', 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.unread.length > 0) {
                            const uniqueMap = {};
                            data.unread.forEach(m => {
                                if (!uniqueMap[m.sender_id]) {
                                    uniqueMap[m.sender_id] = { id: m.sender_id, name: m.sender_name, photo: m.sender_photo, team: m.sender_team, text: m.text || (m.file_name ? '📎 Adjunto' : '...') };
                                }
                            });
                            Alpine.store('chatStore').setUnread(Object.values(uniqueMap));

                            const callMsg = data.unread.find(m => m.call_room);
                            const lastMsg = data.unread[0];

                            if (callMsg && !this.activeCallRoom && (!this.incomingCall || this.incomingCall.room !== callMsg.call_room)) {
                                this.rejectedCalls = this.rejectedCalls || [];
                                if (!this.rejectedCalls.includes(callMsg.call_room)) {
                                    this.incomingCall = { room: callMsg.call_room, sender_id: callMsg.sender_id, sender_name: callMsg.sender_name, sender_photo: callMsg.sender_photo };
                                    this.startFlashAndSound();
                                    const isGoogleMeet = callMsg.call_room.startsWith('http');
                                    Swal.fire({
                                        title: isGoogleMeet ? '🌐 GOOGLE MEET' : '🎥 LLAMADA ENTRANTE',
                                        html: `<div class="flex flex-col items-center gap-4 py-2"><div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 p-0.5 shadow-xl relative"><img src="${callMsg.sender_photo}" class="w-full h-full rounded-[14px] object-cover"></div><div><p class="text-xs font-black uppercase">${callMsg.sender_name}</p><p class="text-xs font-bold mt-2">${isGoogleMeet ? '¡te invita a una reunión!' : '¡te está llamando!'}</p></div></div>`,
                                        showCancelButton: true,
                                        confirmButtonText: isGoogleMeet ? 'Unirse 🚀' : 'Contestar 👍',
                                        confirmButtonColor: isGoogleMeet ? '#0ea5e9' : '#059669',
                                        cancelButtonText: 'Ahora no 👎',
                                        customClass: { popup: 'rounded-[2.5rem] dark:bg-gray-950 dark:text-white' }
                                    }).then((result) => {
                                        if (result.isConfirmed) this.acceptCall();
                                        else this.rejectCall(callMsg.call_room);
                                    });
                                }
                            } else if (!callMsg && (!this.lastNotifiedMsgId || this.lastNotifiedMsgId !== lastMsg.id)) {
                                this.lastNotifiedMsgId = lastMsg.id;
                                if (this.chatSoundsEnabled) this.playMessageChime();
                                this.startMessageFlash();
                                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000 });
                                Toast.fire({ icon: 'info', html: `<div class="text-left py-1 pr-2"><p class="text-xs font-black uppercase">${lastMsg.sender_name}</p><p class="text-xs text-gray-600 dark:text-gray-300 truncate">${lastMsg.text}</p></div>`, didOpen: (t) => { t.style.cursor = 'pointer'; t.onclick = () => this.openChat({ id: lastMsg.sender_id, name: lastMsg.sender_name, photo: lastMsg.sender_photo, team: lastMsg.sender_team, is_group: String(lastMsg.sender_id).startsWith('group_') }); } });
                            }
                            if (this.open && this.member.id === lastMsg.sender_id) this.fetchMessages();
                        } else {
                            Alpine.store('chatStore').setUnread([]);
                        }
                    })
                    .catch(e => console.warn('Chat poll skip:', e));
                },

                acceptCall() {
                    const url = this.incomingCall.room.startsWith('http') ? this.incomingCall.room : 'https://meet.jit.si/' + this.incomingCall.room;
                    window.open(url, '_blank');
                    this.openChat({ id: this.incomingCall.sender_id, name: this.incomingCall.sender_name, photo: this.incomingCall.sender_photo });
                    this.stopFlashAndSound();
                    this.incomingCall = null;
                },

                rejectCall(room) {
                    if (room) {
                        this.rejectedCalls = this.rejectedCalls || [];
                        if (!this.rejectedCalls.includes(room)) this.rejectedCalls.push(room);
                    }
                    this.stopFlashAndSound();
                    this.incomingCall = null;
                },

                startFlashAndSound() {
                    if (this.titleInterval) clearInterval(this.titleInterval);
                    this.titleInterval = setInterval(() => { document.title = document.title === this.originalTitle ? '📞 LLAMADA...' : this.originalTitle; }, 1000);
                    this.stopCallSound();
                    const ring = () => {
                        try {
                            if (!window.sientiaAudioCtx) {
                                window.sientiaAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                            }
                            const ctx = window.sientiaAudioCtx;
                            if (ctx.state === 'suspended') ctx.resume();

                            const notes = [523.25, 659.25, 783.99, 1046.50];
                            notes.forEach((f, i) => {
                                setTimeout(() => {
                                    if (!this.callRingInterval) return;
                                    const osc = ctx.createOscillator();
                                    const gain = ctx.createGain();
                                    osc.connect(gain);
                                    gain.connect(ctx.destination);
                                    osc.type = 'sine';
                                    osc.frequency.setValueAtTime(f, ctx.currentTime);
                                    gain.gain.setValueAtTime(0.3, ctx.currentTime);
                                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
                                    osc.start();
                                    osc.stop(ctx.currentTime + 0.6);
                                }, i * 150);
                            });
                        } catch (e) { console.warn('Call ring failed:', e); }
                    };
                    ring();
                    this.callRingInterval = setInterval(ring, 2000);
                },

                stopCallSound() { if (this.callRingInterval) { clearInterval(this.callRingInterval); this.callRingInterval = null; } },
                stopFlashAndSound() { this.stopCallSound(); if (this.titleInterval) { clearInterval(this.titleInterval); this.titleInterval = null; } document.title = this.originalTitle; },
                startMessageFlash() { if (this.titleInterval) clearInterval(this.titleInterval); this.titleInterval = setInterval(() => { document.title = document.title === this.originalTitle ? '💬 MENSAJE...' : this.originalTitle; }, 1200); const stop = () => { this.stopFlashAndSound(); window.removeEventListener('focus', stop); }; window.addEventListener('focus', stop, {once: true}); },
                playMessageChime() {
                    try {
                        if (!window.sientiaAudioCtx) {
                            window.sientiaAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        }
                        const ctx = window.sientiaAudioCtx;
                        if (ctx.state === 'suspended') ctx.resume();

                        const notes = [660, 880];
                        notes.forEach((f, i) => {
                            setTimeout(() => {
                                const osc = ctx.createOscillator();
                                const gain = ctx.createGain();
                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                osc.type = 'sine';
                                osc.frequency.setValueAtTime(f, ctx.currentTime);
                                gain.gain.setValueAtTime(0.4, ctx.currentTime);
                                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
                                osc.start();
                                osc.stop(ctx.currentTime + 0.5);
                            }, i * 120);
                        });
                    } catch (e) { console.warn('Audio chime failed:', e); }
                },
                scrollToBottom() { const c = this.$refs.chatContainer; if (c) c.scrollTop = c.scrollHeight; },
                clearPendingAttachments() { if (this.previewUrl && this.previewUrl.startsWith('blob:')) URL.revokeObjectURL(this.previewUrl); this.pendingFile = null; this.previewUrl = null; this.pendingDriveFile = null; },
                handleFileSelect(e) { const f = e.target.files[0]; if (f) this.processFile(f); e.target.value = ''; },
                processFile(f) { if (f.size > 10 * 1024 * 1024) { alert('⚠️ Límite 10MB'); return; } this.pendingFile = f; this.previewUrl = URL.createObjectURL(f); this.$nextTick(() => this.$refs.chatInput.focus()); },
                handlePaste(e) { const items = (e.clipboardData || e.originalEvent.clipboardData).items; for (let i in items) { if (items[i].kind === 'file') { const b = items[i].getAsFile(); const f = new File([b], `img_${Date.now()}.png`, { type: b.type }); this.processFile(f); } } },
                insertEmoji(e) { const i = this.$refs.chatInput; const s = i.selectionStart; const en = i.selectionEnd; this.message = this.message.substring(0, s) + e + this.message.substring(en); this.$nextTick(() => { i.focus(); const n = s + e.length; i.setSelectionRange(n, n); }); },
                startSientiaCall() {
                    if (this.member.id === {{ auth()->id() }}) return;
                    fetch('/chat/call', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ receiver_id: this.member.id })
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (!d.success || !d.room) {
                            Swal.fire({ icon: 'error', title: 'Error', text: d.message || 'No se pudo iniciar la llamada.', toast: true, position: 'top-end', timer: 4000, showConfirmButton: false });
                            return;
                        }
                        window.open('https://meet.jit.si/' + d.room, '_blank');
                        this.fetchMessages();
                    })
                    .catch(() => Swal.fire({ icon: 'error', title: 'Error de red', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false }));
                },
                startGoogleMeet() {
                    if (this.member.id === {{ auth()->id() }}) return;

                    // Show spinner while creating the Meet space
                    Swal.fire({
                        title: '🌐 Creando sala Meet...',
                        text: 'Conectando con Google Meet',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => Swal.showLoading(),
                    });

                    fetch('/chat/meet', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ receiver_id: this.member.id }),
                    })
                    .then(r => r.json())
                    .then(d => {
                        Swal.close();
                        if (!d.success) {
                            const linkToProfile = d.needs_auth
                                ? '<br><a href="/profile?tab=integrations" class="underline text-sky-400 text-xs" target="_blank">Conectar cuenta Google →</a>'
                                : '';
                            Swal.fire({
                                icon: 'error',
                                title: 'No se pudo crear la sala',
                                html: (d.message || 'Error desconocido') + linkToProfile,
                                customClass: { popup: 'rounded-[2rem] dark:bg-gray-950 dark:text-white' },
                            });
                            return;
                        }
                        // Open the Meet URL for the caller immediately
                        window.open(d.meet_url, '_blank');
                        // Refresh chat so the invitation bubble appears
                        this.fetchMessages();
                    })
                    .catch(() => {
                        Swal.close();
                        Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                    });
                }
            }));
        });
    </script>
    @endauth


    <!-- Marked.js (Markdown Rendering) -->
    @php
        $resolvedTeam = $team ?? ($__data['team'] ?? null);
        if (!$resolvedTeam) {
            $teamRouteParam = request()->route('team');
            if ($teamRouteParam) {
                if (is_object($teamRouteParam)) {
                    $resolvedTeam = $teamRouteParam;
                } else {
                    $resolvedTeam = \App\Models\Team::where('id', $teamRouteParam)
                        ->orWhere('slug', $teamRouteParam)
                        ->first();
                }
            }
        }
        if (!$resolvedTeam) {
            $threadObj = $thread ?? ($__data['thread'] ?? null);
            if ($threadObj) {
                $resolvedTeam = is_object($threadObj) ? ($threadObj->team ?? null) : null;
            }
        }
    @endphp
    <x-markdown-styles :team="$resolvedTeam" />

    <!-- Global Alpine Store for Timer (Performance optimization N -> 1) -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('timer', {
                activeTaskId: {{ auth()->check() ? (auth()->user()->activeTaskLog()?->task_id ?? 'null') : 'null' }},
                elapsed: {{ auth()->check() && auth()->user()->activeTaskLog() ? max(0, auth()->user()->activeTaskLog()->start_at->diffInSeconds(now(), false)) : 0 }},
                taskStartTime: {{ auth()->check() && auth()->user()->activeTaskLog() ? auth()->user()->activeTaskLog()->start_at->timestamp * 1000 : 'null' }},
                timer: null,

                async fetch() {
                    try {
                        const res = await fetch('{{ route('time-logs.status') }}');
                        const data = await res.json();
                        this.activeTaskId = data.active_task_id;
                        this.elapsed = Math.floor(data.task_elapsed);
                        if (this.activeTaskId) {
                            // Recalculate start time from server elapsed to keep accurate tracking
                            this.taskStartTime = Date.now() - (this.elapsed * 1000);
                            this.tick();
                        } else {
                            this.stop();
                        }
                    } catch(e) { console.error('Timer sync failed', e); }
                },
                tick() {
                    if (this.timer) clearInterval(this.timer);
                    this.timer = setInterval(() => {
                        if (this.taskStartTime) {
                            this.elapsed = Math.floor((Date.now() - this.taskStartTime) / 1000);
                        }
                    }, 1000);
                },
                stop() {
                    if (this.timer) clearInterval(this.timer);
                    this.timer = null;
                    this.activeTaskId = null;
                    this.taskStartTime = null;
                    this.elapsed = 0;
                },
                init() {
                    if (this.activeTaskId) this.tick();

                    // Listeners Centralized
                    window.addEventListener('task-started', (e) => {
                        this.activeTaskId = e.detail.taskId;
                        this.elapsed = 0;
                        this.taskStartTime = Date.now();
                        this.tick();
                    });

                    window.addEventListener('workday-toggled', (e) => {
                        if (!e.detail.working) this.stop();
                    });

                    // Sync with server when tab becomes visible (handles background throttling)
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible' && this.activeTaskId) {
                            this.fetch();
                        }
                    });
                }
            });

            Alpine.store('notifications', {
                count: {{ auth()->check() ? Auth::user()->unreadNotifications->count() : 0 }},
                lastChecked: Date.now(),
                firstCheck: true,
                lastSummaryShown: localStorage.getItem('last_notification_summary_shown') || 0,

                async check() {
                    try {
                        const res = await fetch('{{ route('notifications.unread-count') }}');
                        const data = await res.json();

                        // Only show pending summary if it hasn't been shown in the last 2 hours
                        const now = Date.now();
                        const twoHours = 2 * 60 * 60 * 1000;

                        if (this.firstCheck && data.count > 0 && (now - this.lastSummaryShown) > twoHours) {
                            if (data.count === 1) {
                                this.showToast(data.unread[0]);
                            } else {
                                Swal.fire({
                                    title: '{{ __("Pendientes") }}',
                                    text: '{{ __("Tienes :count notificaciones pendientes", ["count" => ""]) }}'.replace('""', data.count) + data.count,
                                    icon: 'info',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 7000,
                                    timerProgressBar: true,
                                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                                    color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#1f2937',
                                    didOpen: (toast) => {
                                        toast.style.zIndex = '9999'; // Force super high z-index
                                        toast.addEventListener('click', () => { window.location.href = '{{ route("notifications.index") }}'; })
                                    }
                                });
                            }
                            this.firstCheck = false;
                            this.lastSummaryShown = now;
                            localStorage.setItem('last_notification_summary_shown', now);
                        }
                        // If count increased during session, show the new one
                        else if (data.count > this.count && data.unread.length > 0) {
                            this.showToast(data.unread[0]);
                        }

                        if (data.count !== this.count) {
                            window.dispatchEvent(new CustomEvent('notifications-updated', { detail: { count: data.count } }));
                        }

                        this.count = data.count;
                    } catch(e) { console.error('Notification check failed', e); }
                },

                showToast(notification) {
                    Swal.fire({
                        title: '{{ __("Nueva notificación") }}',
                        text: notification.data.message || '{{ __("Tienes una nueva actualización") }}',
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 6000,
                        timerProgressBar: true,
                        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#1f2937',
                        didOpen: (toast) => {
                            toast.style.zIndex = '9999'; // Ensure it's above everything
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                            toast.addEventListener('click', () => {
                                window.location.href = '{{ route("notifications.index") }}';
                            })
                        }
                    });
                },

                init() {
                    @auth
                    // Initial check
                    setTimeout(() => this.check(), 5000);

                    // Poll less frequently (1 minute)
                    setInterval(() => this.check(), 60000);

                    // Re-check when coming back to the tab
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            this.check();
                        }
                    });
                    @endauth
                }
            });

            // Initial sync (background)
            Alpine.store('timer').fetch();
        });
    </script>

    <style>
        :root {
            --color-q1: #ef4444;
            /* Red   – Do First  */
            --color-q2: #3b82f6;
            /* Blue  – Schedule  */
            --color-q3: #f59e0b;
            /* Amber – Delegate  */
            --color-q4: #6b7280;
            /* Gray  – Eliminate */
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        /* Essential for sticky elements to work correctly in some browsers */
        html {
            scroll-behavior: smooth;
        }

        h1,
        h2,
        h3,
        h4,
        .heading {
            font-family: 'Space Grotesk', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Prevent layout clipping on mobile for wide content like Kanban */
        @media (max-width: 1024px) {
            #mainContent[data-wide-content="true"] {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }
            #mainContent {
                max-width: none !important;
                width: 100% !important;
            }
        }

        /* Critical Layout Stability Styles - Prevents FOUC/Flicker */
        @if($layout === 'vertical')
            #sidebar:not(.translate-x-0) {
                transform: translateX(-100%) !important;
            }
            @media (min-width: 1024px) {
                body:not(.sidebar-closed) #sidebar { transform: translateX(0) !important; }
                body:not(.sidebar-closed) #mainContent.lg-layout-v-fix,
                body:not(.sidebar-closed) footer.lg-layout-v-fix,
                body:not(.sidebar-closed) .header-v-fix {
                    padding-left: 18rem !important;
                }
            }
        @endif

        /* Critical Responsive Visibility - Prevents Mobile Overlays on Desktop FOUC */
        @media (min-width: 640px) { .sm\:hidden { display: none !important; } }
        @media (min-width: 768px) { .md\:hidden { display: none !important; } }
        @media (min-width: 1024px) { .lg\:hidden { display: none !important; } }
        @media (min-width: 1280px) { .xl\:hidden { display: none !important; } }

        /* GLOBAL TOMSELECT FIX: Prevenir que el wrapper herede estilos de Tailwind del select original */
        .ts-wrapper {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        /* Sientia Global Print Overrides */
        @media print {
            body.print-clean-mode nav,
            body.print-clean-mode header,
            body.print-clean-mode footer,
            body.print-clean-mode aside,
            body.print-clean-mode #sidebar,
            body.print-clean-mode .app-header,
            body.print-clean-mode button,
            body.print-clean-mode .no-print,
            body.print-clean-mode #docs-sidebar,
            body.print-clean-mode #docs-mobile-toggle {
                display: none !important;
            }
            body.print-clean-mode main,
            body.print-clean-mode .flex-1 {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                flex: 1 1 100% !important;
            }
        }
        
        /* FIX: Prevent SweetAlert2 Toasts from blocking app interaction */
        body.swal2-toast-shown .swal2-container {
            pointer-events: none !important;
        }
        body.swal2-toast-shown .swal2-container .swal2-popup {
            pointer-events: auto !important;
        }
    </style>
