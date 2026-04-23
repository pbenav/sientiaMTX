<div x-data="drivePicker()" 
     @open-drive-picker.window="openModal($event.detail)" 
     x-show="isOpen" 
     class="fixed inset-0 z-[100] overflow-y-auto" 
     x-cloak>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="isOpen" 
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
             class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-200 dark:border-gray-700">
            
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600" viewBox="0 0 48 48">
                            <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                            <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                            <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Google Drive</h3>
                </div>
                <button @click="isOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6">
                <!-- Breadcrumbs -->
                <div class="flex items-center gap-2 mb-4 text-xs font-medium text-gray-500 overflow-x-auto whitespace-nowrap pb-2">
                    <button @click="loadFolder(null)" class="hover:text-blue-600 transition-colors">Mi Unidad</button>
                    <template x-for="crumb in breadcrumbs">
                        <div class="flex items-center gap-2">
                            <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <button @click="loadFolder(crumb.id)" class="hover:text-blue-600 transition-colors" x-text="crumb.name"></button>
                        </div>
                    </template>
                </div>

                <div class="relative min-h-[300px] max-h-[400px] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700">
                    <!-- Loading State -->
                    <div x-show="loading" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 flex items-center justify-center z-10">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>

                    <!-- Files List -->
                    <div class="grid grid-cols-1 gap-1">
                        <template x-for="file in files" :key="file.id">
                            <div @click="handleAction(file)" 
                                 class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 group cursor-pointer border border-transparent hover:border-blue-100 dark:hover:border-blue-900/50 transition-all">
                                <div class="flex items-center gap-3 truncate">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                                        <template x-if="file.mimeType === 'application/vnd.google-apps.folder'">
                                            <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                                        </template>
                                        <template x-if="file.mimeType !== 'application/vnd.google-apps.folder'">
                                            <img :src="file.iconLink" class="w-4 h-4 opacity-70">
                                        </template>
                                    </div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium truncate" x-text="file.name"></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-[10px] text-gray-400" x-text="file.mimeType === 'application/vnd.google-apps.folder' ? '' : formatSize(file.size)"></span>
                                    <div class="p-1.5 rounded-lg bg-gray-50 dark:bg-gray-700 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                        <svg x-show="file.mimeType === 'application/vnd.google-apps.folder'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        <svg x-show="file.mimeType !== 'application/vnd.google-apps.folder'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <div x-show="!loading && files.length === 0" class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <svg class="w-12 h-12 mb-2 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            <p class="text-xs">Carpeta vacía</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function drivePicker() {
        return {
            isOpen: false,
            loading: false,
            files: [],
            breadcrumbs: [],
            currentFolderId: null,
            targetId: null,
            targetType: null,
            mode: 'attach',

            openModal(detail) {
                this.targetId = detail.id || null;
                this.targetType = detail.type || null;
                this.mode = detail.mode || (this.targetId ? 'attach' : 'collect');
                this.isOpen = true;
                this.loadFolder(null);
            },

            async loadFolder(folderId) {
                this.loading = true;
                try {
                    const response = await fetch(`{{ route('google.drive.list') }}?folderId=${folderId || ''}&team_id={{ $team->id }}`);
                    const data = await response.json();
                    this.files = data.files || [];
                    if (folderId === null) this.breadcrumbs = [];
                } catch (error) {
                    console.error('Error loading Drive folder:', error);
                } finally {
                    this.loading = false;
                }
            },

            handleAction(file) {
                if (file.mimeType === 'application/vnd.google-apps.folder') {
                    this.loadFolder(file.id);
                    this.breadcrumbs.push({ id: file.id, name: file.name });
                } else {
                    if (this.mode === 'collect') {
                        window.dispatchEvent(new CustomEvent('drive-file-selected', { detail: file }));
                        this.isOpen = false;
                    } else {
                        this.attachFile(file);
                    }
                }
            },

            async attachFile(file) {
                this.loading = true;
                try {
                    const response = await fetch('{{ route('teams.attachments.from-drive', [$team]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            attachable_id: this.targetId,
                            attachable_type: this.targetType,
                            file_id: file.id,
                            file_name: file.name,
                            web_view_link: file.webViewLink,
                            file_size: file.size || 0,
                            mime_type: file.mimeType
                        })
                    });

                    const data = await response.json();
                    if (data.success) window.location.reload();
                    else alert('Error: ' + data.message);
                } catch (error) {
                    console.error('Error attaching from Drive:', error);
                } finally {
                    this.loading = false;
                }
            },

            formatSize(bytes) {
                if (!bytes) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            }
        }
    }
</script>
