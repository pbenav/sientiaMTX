<section x-data="passkeyManager()" x-init="init()">
    <header>
        <div class="flex items-center gap-3">
            <div class="p-2.5 bg-emerald-50 dark:bg-emerald-500/10 rounded-2xl text-emerald-600 dark:text-emerald-400 shadow-sm border border-emerald-100 dark:border-emerald-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-black text-gray-900 dark:text-white tracking-tight uppercase">
                    {{ __('Llaves de Acceso (Passkeys)') }}
                </h2>
                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400 font-medium">
                    {{ __('Utiliza tu huella, reconocimiento facial o PIN de dispositivo para iniciar sesión de forma segura.') }}
                </p>
            </div>
        </div>
    </header>

    <div class="mt-6 space-y-6">
        
        <!-- Feature Info Box -->
        <div class="bg-gradient-to-br from-gray-50 to-white dark:from-gray-800/30 dark:to-gray-800/10 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-sm">
            <div class="flex gap-3">
                <div class="flex-shrink-0 text-amber-500 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ __('Seguridad Absoluta') }}</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                        Las Passkeys son resistentes al Phishing y protegen tu cuenta de accesos no autorizados de forma nativa mediante criptografía avanzada.
                    </p>
                </div>
            </div>
        </div>

        <!-- Not Supported Notice (Hidden by default) -->
        <div x-show="!isSupported" x-cloak class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/50 rounded-xl text-red-700 dark:text-red-400 text-sm font-medium">
            ⚠️ Tu navegador o sistema operativo actual no es compatible con Passkeys en este momento.
        </div>

        <!-- Registered Passkeys List -->
        <div x-show="isSupported" class="space-y-3">
            <h3 class="text-[10px] uppercase tracking-widest font-black text-gray-400 dark:text-gray-500 flex items-center gap-2">
                Mis Dispositivos Vinculados
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-800"></div>
            </h3>

            @forelse(auth()->user()->passkeys as $pk)
                <div class="flex items-center justify-between p-3.5 bg-white dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 shadow-sm rounded-xl transition-all hover:border-emerald-300 dark:hover:border-emerald-500/30 group">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-50 dark:bg-gray-700 rounded-xl flex items-center justify-center text-gray-500 dark:text-gray-400 group-hover:bg-emerald-50 dark:group-hover:bg-emerald-500/10 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $pk->name ?: 'Dispositivo Desconocido' }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-500 font-medium flex items-center gap-1.5 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" /></svg>
                                Vinculada {{ $pk->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    
                    <form method="POST" action="{{ route('passkey.destroy', $pk) }}" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta llave? Perderás la capacidad de acceder con este dispositivo.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 dark:text-gray-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all" title="Eliminar Llave">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </form>
                </div>
            @empty
                <div class="text-center py-6 px-4 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-600 rounded-2xl mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400">No tienes llaves de acceso registradas.</p>
                    <p class="text-xs text-gray-400 mt-1">Vincular una llave te permite entrar al instante sin escribir tu contraseña.</p>
                </div>
            @endforelse

            <!-- Register Action -->
            <div class="pt-4">
                <button type="button" 
                        @click="registerNew()" 
                        :disabled="loading"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 text-white font-black text-xs tracking-widest uppercase rounded-xl shadow-[0_4px_14px_0_rgba(16,185,129,0.39)] hover:shadow-[0_6px_20px_rgba(16,185,129,0.23)] transition-all duration-300 disabled:opacity-50 transform active:scale-[0.98]">
                    <svg x-show="!loading" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <svg x-show="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="loading ? 'Procesando...' : 'Vincular Nuevo Dispositivo'"></span>
                </button>
            </div>
        </div>

    </div>

    <script>
        function passkeyManager() {
            return {
                isSupported: false,
                loading: false,
                
                init() {
                    // The Passkeys object is available globally via window.Passkeys injected in app.js
                    this.isSupported = window.Passkeys && window.Passkeys.isSupported();
                },
                
                async registerNew() {
                    if (!this.isSupported || this.loading) return;
                    
                    const { value: keyName } = await Swal.fire({
                        title: '🛡️ Nueva Llave de Acceso',
                        text: 'Asigna un nombre para recordar este dispositivo:',
                        input: 'text',
                        inputValue: this.guessDeviceName(),
                        inputPlaceholder: 'Ej: Mi MacBook, iPhone de Trabajo',
                        showCancelButton: true,
                        confirmButtonText: 'Siguiente ➡️',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#059669',
                        customClass: {
                            popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-2xl px-6 py-3.5 uppercase tracking-widest font-black text-[10px] focus:ring-0',
                            cancelButton: 'rounded-2xl px-6 py-3.5 uppercase tracking-widest font-black text-[10px] focus:ring-0'
                        }
                    });

                    if (!keyName) return;

                    this.loading = true;
                    try {
                        await window.Passkeys.register({ 
                            name: keyName 
                        });
                        
                        await Swal.fire({
                            icon: 'success',
                            title: '✅ ¡Éxito!',
                            text: 'Dispositivo vinculado correctamente con Passkey.',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-[2.5rem]' }
                        });
                        
                        // Reload page to see new key in user's list
                        window.location.reload();
                    } catch (e) {
                        console.error('Passkey registration error:', e);
                        
                        const errName = e.name || '';
                        const errMsg = e.message || '';
                        
                        // 1. Check for cancellation (silently ignore)
                        if (errName === 'UserCancelledError' || errName === 'NotAllowedError' || errMsg.includes('cancelled')) {
                            // User cancelled, do nothing.
                            return;
                        } 
                        
                        // 2. Check if already exists (User-friendly warning)
                        if (errName === 'PasskeyExistsError' || errMsg.includes('already registered')) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Dispositivo ya registrado',
                                text: 'Esta llave de acceso ya está vinculada a tu cuenta.',
                                customClass: { popup: 'rounded-[2.5rem]' }
                            });
                            return;
                        }

                        // 3. Generic fallback
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'No pudimos registrar la llave. Inténtalo de nuevo.',
                            footer: '<code class="text-[10px] text-gray-400">' + errName + ': ' + errMsg + '</code>',
                            customClass: { popup: 'rounded-[2.5rem]' }
                        });
                    } finally {
                        this.loading = false;
                    }
                },
                
                guessDeviceName() {
                    const userAgent = navigator.userAgent.toLowerCase();
                    let device = 'Mi Dispositivo';
                    if (userAgent.includes('iphone')) device = 'iPhone';
                    else if (userAgent.includes('android')) device = 'Android';
                    else if (userAgent.includes('macintosh')) device = 'Mac';
                    else if (userAgent.includes('windows')) device = 'Windows PC';
                    
                    const browser = userAgent.includes('chrome') && !userAgent.includes('edg') ? 'Chrome' :
                                   userAgent.includes('safari') && !userAgent.includes('chrome') ? 'Safari' :
                                   userAgent.includes('firefox') ? 'Firefox' :
                                   userAgent.includes('edg') ? 'Edge' : 'Navegador';
                                   
                    return `${device} (${browser})`;
                }
            }
        }
    </script>
</section>
