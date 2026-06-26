<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('profile.edit_profile') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('profile.update_profile_intro') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <!-- Profile Photo Section -->
    <div class="mt-6 p-4 bg-violet-50/30 dark:bg-violet-900/5 rounded-2xl border border-violet-100 dark:border-violet-800/50" x-data="{ photoName: null, photoPreview: null }">
        <form method="post" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data">
            @csrf
            @method('patch')

            <div class="flex flex-col sm:flex-row items-center gap-6">
                <!-- Photo Preview -->
                <div class="relative group" x-data="{
                    openEditor() {
                        if ($refs.photo.files.length === 0) return;
                        
                        let file = $refs.photo.files[0];
                        
                        // Check if it's an image
                        if (!file.type.startsWith('image/')) {
                            // If not an image, just preview directly or error
                            this.previewLocalFile(file);
                            return;
                        }

                        // Call global image editor
                        if (typeof window.openGlobalImageEditor === 'function') {
                            window.openGlobalImageEditor(file, (editedFile, base64) => {
                                // Create a new FileList containing the edited file
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(editedFile);
                                $refs.photo.files = dataTransfer.files;
                                
                                // Show preview
                                photoName = editedFile.name;
                                photoPreview = base64;
                            });
                        } else {
                            // Fallback if editor is not available
                            this.previewLocalFile(file);
                        }
                    },
                    previewLocalFile(file) {
                        photoName = file.name;
                        let reader = new FileReader();
                        reader.onload = (e) => {
                            photoPreview = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                }">
                    <input type="file" class="hidden" x-ref="photo"
                        x-on:change="openEditor()"
                        name="photo" accept="image/*">
                    
                    <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-white dark:border-gray-800 shadow-xl ring-2 ring-violet-500/20 transition-transform group-hover:scale-105 duration-300">
                        <!-- Current Photo -->
                        <img x-show="!photoPreview" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                        <!-- New Photo Preview -->
                        <img x-show="photoPreview" :src="photoPreview" class="w-full h-full object-cover" style="display: none;">
                    </div>

                    <button type="button" @click="$refs.photo.click()" class="absolute -bottom-1 -right-1 bg-violet-600 hover:bg-violet-700 text-white p-2 rounded-full shadow-lg transition-all transform hover:scale-110 active:scale-95 group-hover:rotate-12">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                </div>

                <div class="flex-1 text-center sm:text-left space-y-2">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ __('Foto de Perfil') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Personaliza tu cuenta con una foto. Formatos: JPG, PNG. Máx: 1MB.') }}
                    </p>
                    
                    <div class="flex flex-wrap justify-center sm:justify-start gap-3">
                        <button type="submit" x-show="photoPreview" class="bg-emerald-500 hover:bg-emerald-600 text-white text-[10px] uppercase tracking-wider font-bold py-2 px-4 rounded-xl transition-all shadow-lg shadow-emerald-500/20">
                            {{ __('Guardar Foto') }}
                        </button>
                        
                        @if($user->profile_photo_path)
                            <button type="button" onclick="event.preventDefault(); document.getElementById('delete-photo-form').submit();" class="bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 text-[10px] uppercase tracking-wider font-bold py-2 px-4 rounded-xl border border-rose-100 dark:border-rose-900/30 hover:bg-rose-100 transition-all">
                                {{ __('Eliminar') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        <form id="delete-photo-form" method="POST" action="{{ route('profile.photo.update') }}" class="hidden">
            @csrf
            @method('patch')
            <input type="hidden" name="delete_photo" value="1">
        </form>
        
        <x-input-error class="mt-2" :messages="$errors->get('photo')" />
        @if (session('status') === 'photo-updated')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                class="mt-2 text-xs font-bold text-emerald-600">{{ __('¡Foto actualizada!') }}</p>
        @endif
    </div>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')
        <input type="hidden" name="tab" value="general">

        <div>
            <x-input-label for="name" :value="__('profile.name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)"
                required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('profile.email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)"
                required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <!-- Locale Selection -->
        <div>
            <x-input-label for="locale" :value="__('profile.language')" />
            <select id="locale" name="locale"
                class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                <option value="en" {{ old('locale', $user->locale) === 'en' ? 'selected' : '' }}>English</option>
                <option value="es" {{ old('locale', $user->locale) === 'es' ? 'selected' : '' }}>Español</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('locale')" />
        </div>

        <!-- Timezone Selection -->
        <div>
            <x-input-label for="timezone" :value="__('profile.timezone')" />
            <select id="timezone" name="timezone"
                class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                @foreach (\DateTimeZone::listIdentifiers() as $tz)
                    <option value="{{ $tz }}"
                        {{ old('timezone', $user->timezone) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
        </div>

        <!-- Horario de Trabajo Selection -->
        <div class="p-6 bg-gray-50/50 dark:bg-gray-800/30 rounded-2xl border border-gray-100 dark:border-gray-800/50 space-y-5">
            <div class="flex items-center gap-2">
                <span class="p-1.5 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-widest text-violet-600 dark:text-violet-400">Horario de Trabajo Diario</h3>
                    <span class="text-[9px] text-gray-400 font-medium block uppercase tracking-wider mt-0.5">Soporta jornada continua y partida (dos turnos)</span>
                </div>
            </div>
            
            <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">
                Define tus turnos diarios habituales de trabajo. Si excedes los horarios límite y tu contador de jornada sigue en marcha, se mostrará un mensaje interactivo para evitar olvidos al apagar tus contadores.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4 border-t border-gray-100 dark:border-gray-800/50" x-data="{ 
                days: {mon: 'L', tue: 'M', wed: 'X', thu: 'J', fri: 'V', sat: 'S', sun: 'D'},
                shift1Days: {{ json_encode($user->work_days_1 ?? []) }},
                shift2Days: {{ json_encode($user->work_days_2 ?? []) }},
                shift1Enabled: {{ ($user->work_days_1 && count($user->work_days_1) > 0) ? 'true' : 'false' }},
                shift2Enabled: {{ ($user->work_days_2 && count($user->work_days_2) > 0) ? 'true' : 'false' }},
                toggleDay(shift, day) {
                    if (shift === 1) {
                        if (this.shift1Days.includes(day)) {
                            this.shift1Days = this.shift1Days.filter(d => d !== day);
                        } else {
                            this.shift1Days.push(day);
                        }
                    } else {
                        if (this.shift2Days.includes(day)) {
                            this.shift2Days = this.shift2Days.filter(d => d !== day);
                        } else {
                            this.shift2Days.push(day);
                        }
                    }
                }
            }">
                <!-- Turno 1 (Mañana) -->
                <div class="space-y-4 p-4 rounded-2xl transition-all duration-300" :class="shift1Enabled ? 'bg-white dark:bg-gray-900/40 shadow-sm border border-gray-100 dark:border-gray-800' : 'opacity-60 bg-gray-100/50 dark:bg-gray-800/20 border border-transparent'">
                    <div class="flex items-center justify-between">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full" :class="shift1Enabled ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-700'"></span>
                            Primer Turno / Mañana
                        </h4>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="shift1Enabled" class="sr-only peer">
                            <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-amber-500"></div>
                        </label>
                    </div>

                    <div x-show="shift1Enabled" x-collapse>
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div>
                                <x-input-label for="work_start_time_1" value="Entrada" class="text-[9px] font-bold uppercase text-gray-400" />
                                <x-text-input id="work_start_time_1" name="work_start_time_1" type="time" class="mt-1 block w-full bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-sm" :value="old('work_start_time_1', $user->work_start_time_1 ?? '08:00')" />
                                <x-input-error class="mt-2" :messages="$errors->get('work_start_time_1')" />
                            </div>
                            <div>
                                <x-input-label for="work_end_time_1" value="Salida" class="text-[9px] font-bold uppercase text-gray-400" />
                                <x-text-input id="work_end_time_1" name="work_end_time_1" type="time" class="mt-1 block w-full bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-sm" :value="old('work_end_time_1', $user->work_end_time_1 ?? '14:00')" />
                                <x-input-error class="mt-2" :messages="$errors->get('work_end_time_1')" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <span class="text-[9px] font-bold uppercase text-gray-400 block">Días de actividad</span>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="(label, key) in days" :key="key">
                                    <button type="button" @click="toggleDay(1, key)" 
                                        class="w-8 h-8 rounded-xl text-[10px] font-bold transition-all duration-200 flex items-center justify-center border"
                                        :class="shift1Days.includes(key) 
                                            ? 'bg-amber-500 border-amber-600 text-white shadow-md shadow-amber-500/20 scale-105' 
                                            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500 hover:border-amber-300'">
                                        <span x-text="label"></span>
                                    </button>
                                </template>
                            </div>
                            <template x-if="shift1Enabled">
                                <template x-for="day in shift1Days" :key="'s1-'+day">
                                    <input type="hidden" name="work_days_1[]" :value="day">
                                </template>
                            </template>
                            <x-input-error class="mt-2" :messages="$errors->get('work_days_1')" />

                            <!-- Input para asegurar que si no hay días o está desactivado, se envíe un array vacío si fuera necesario, 
                                 pero con nullable en el Request, si no se envía nada quedará como null, lo cual es correcto. -->
                        </div>
                    </div>
                </div>

                <!-- Turno 2 (Tarde) -->
                <div class="space-y-4 p-4 rounded-2xl transition-all duration-300" :class="shift2Enabled ? 'bg-white dark:bg-gray-900/40 shadow-sm border border-gray-100 dark:border-gray-800' : 'opacity-60 bg-gray-100/50 dark:bg-gray-800/20 border border-transparent'">
                    <div class="flex items-center justify-between">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full" :class="shift2Enabled ? 'bg-indigo-500' : 'bg-gray-300 dark:bg-gray-700'"></span>
                            Segundo Turno / Tarde
                        </h4>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="shift2Enabled" class="sr-only peer">
                            <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-500"></div>
                        </label>
                    </div>

                    <div x-show="shift2Enabled" x-collapse>
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div>
                                <x-input-label for="work_start_time_2" value="Entrada" class="text-[9px] font-bold uppercase text-gray-400" />
                                <x-text-input id="work_start_time_2" name="work_start_time_2" type="time" class="mt-1 block w-full bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-sm" :value="old('work_start_time_2', $user->work_start_time_2 ?? '15:00')" />
                                <x-input-error class="mt-2" :messages="$errors->get('work_start_time_2')" />
                            </div>
                            <div>
                                <x-input-label for="work_end_time_2" value="Salida" class="text-[9px] font-bold uppercase text-gray-400" />
                                <x-text-input id="work_end_time_2" name="work_end_time_2" type="time" class="mt-1 block w-full bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-sm" :value="old('work_end_time_2', $user->work_end_time_2 ?? '18:00')" />
                                <x-input-error class="mt-2" :messages="$errors->get('work_end_time_2')" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <span class="text-[9px] font-bold uppercase text-gray-400 block">Días de actividad</span>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="(label, key) in days" :key="key">
                                    <button type="button" @click="toggleDay(2, key)" 
                                        class="w-8 h-8 rounded-xl text-[10px] font-bold transition-all duration-200 flex items-center justify-center border"
                                        :class="shift2Days.includes(key) 
                                            ? 'bg-indigo-500 border-indigo-600 text-white shadow-md shadow-indigo-500/20 scale-105' 
                                            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500 hover:border-indigo-300'">
                                        <span x-text="label"></span>
                                    </button>
                                </template>
                            </div>
                            <template x-if="shift2Enabled">
                                <template x-for="day in shift2Days" :key="'s2-'+day">
                                    <input type="hidden" name="work_days_2[]" :value="day">
                                </template>
                            </template>
                            <x-input-error class="mt-2" :messages="$errors->get('work_days_2')" />

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTH Integration -->
        <div class="p-6 bg-cyan-50/50 dark:bg-cyan-900/10 rounded-2xl border border-cyan-100 dark:border-cyan-800/50 space-y-5" x-data="{ syncEnabled: {{ old('sync_with_cth', $user->sync_with_cth) ? 'true' : 'false' }} }">
            <div class="flex items-center gap-2">
                <span class="p-1.5 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-xs font-black uppercase tracking-widest text-cyan-600 dark:text-cyan-400">Integración con Sientia CTH</h3>
                    <span class="text-[9px] text-gray-400 font-medium block uppercase tracking-wider mt-0.5">Sincroniza tus fichajes y jornada automáticamente</span>
                </div>
            </div>
            
            <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">
                Introduce las credenciales de tu portal de Sientia CTH para que cuando inicies o cierres jornada desde aquí, el fichaje se registre también automáticamente en tu cuenta de CTH.
            </p>

            <div class="space-y-4 pt-2">
                <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300 block">Activar Sincronización</span>
                        <span class="text-[10px] text-gray-500">Si está activo, tus inicios y cierres de jornada se replicarán.</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="sync_with_cth" value="0">
                        <input type="checkbox" name="sync_with_cth" value="1" x-model="syncEnabled" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                    </label>
                </div>

                <div x-show="syncEnabled" x-collapse class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-cyan-100 dark:border-cyan-800/50">
                    <div class="md:col-span-2">
                        <x-input-label for="cth_api_url" value="Servidor CTH (URL API)" class="text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400" />
                        <x-text-input id="cth_api_url" name="cth_api_url" type="url" class="mt-1 block w-full bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-sm" :value="old('cth_api_url', $user->cth_api_url)" placeholder="https://cth.tuservidor.com/api" />
                        <span class="text-[10px] text-gray-400 block mt-1">Especifica la URL base del API de tu instancia de Sientia CTH.</span>
                        <x-input-error class="mt-2" :messages="$errors->get('cth_api_url')" />
                    </div>

                    <div>
                        <x-input-label for="cth_user_code" value="Código Numpad / PIN de Usuario" class="text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400" />
                        <x-text-input id="cth_user_code" name="cth_user_code" type="text" class="mt-1 block w-full bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-sm" :value="old('cth_user_code', $user->cth_user_code)" placeholder="Ej. 4829" />
                        <span class="text-[10px] text-gray-400 block mt-1">El PIN numérico que utilizas para fichar en el Numpad de CTH.</span>
                        <x-input-error class="mt-2" :messages="$errors->get('cth_user_code')" />
                    </div>

                    <div>
                        <x-input-label for="cth_work_center_code" value="Código Centro de Trabajo (Opcional)" class="text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400" />
                        <x-text-input id="cth_work_center_code" name="cth_work_center_code" type="text" class="mt-1 block w-full bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-sm" :value="old('cth_work_center_code', $user->cth_work_center_code)" placeholder="Ej. CEN-01" />
                        <span class="text-[10px] text-gray-400 block mt-1">Código del centro si estás asignado a una delegación o Punto Vuela específico.</span>
                        <x-input-error class="mt-2" :messages="$errors->get('cth_work_center_code')" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Welcome Messages Toggle -->
        <div class="flex items-center gap-3 bg-violet-50/50 dark:bg-violet-900/10 p-4 rounded-2xl border border-violet-100 dark:border-violet-800 group hover:shadow-md transition-all duration-300">
            <div class="flex-1">
                <label for="show_welcome_messages" class="block text-sm font-bold text-violet-700 dark:text-violet-400 cursor-pointer">
                    {{ __('Mensajes de Bienvenida') }}
                </label>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Recibe un saludo alegre e inspirador cada vez que inicies sesión.') }}
                </p>
            </div>
            <div class="relative inline-flex items-center cursor-pointer">
                <input type="hidden" name="show_welcome_messages" value="0">
                <input type="checkbox" id="show_welcome_messages" name="show_welcome_messages" value="1" 
                    {{ old('show_welcome_messages', $user->show_welcome_messages) ? 'checked' : '' }}
                    class="w-6 h-6 rounded-lg border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 shadow-sm transition-all cursor-pointer">
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Guardar Cambios') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
