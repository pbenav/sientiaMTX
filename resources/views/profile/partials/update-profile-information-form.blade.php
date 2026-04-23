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
                <div class="relative group">
                    <input type="file" class="hidden" x-ref="photo"
                        x-on:change="
                            if ($refs.photo.files.length > 0) {
                                photoName = $refs.photo.files[0].name;
                                let reader = new FileReader();
                                reader.onload = (e) => {
                                    photoPreview = e.target.result;
                                };
                                reader.readAsDataURL($refs.photo.files[0]);
                            }
                        "
                        name="photo">
                    
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
            <x-primary-button>{{ __('profile.save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
