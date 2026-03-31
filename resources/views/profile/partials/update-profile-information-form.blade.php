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

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

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
