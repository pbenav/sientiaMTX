<x-app-layout>
    @section('title', __('Create User'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl shadow-sm border border-violet-200 dark:border-violet-800/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">
                        <a href="{{ route('settings.users') }}"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors mr-2">
                            {{ __('navigation.users') }}
                        </a> / {{ __('Create User') }}
                    </h1>
                    <x-demo-hint>
                        Formulario de registro manual de usuarios. Los nuevos usuarios creados aquí no pertenecerán a ningún equipo hasta que sean asignados o acepten una invitación.
                    </x-demo-hint>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-4 shadow-sm">
        <div class="max-w-7xl mx-auto">
            @include('settings.partials.tabs')

            <div class="max-w-2xl">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
                    <form action="{{ route('settings.users.store') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                :value="old('name')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                :value="old('email')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="password" :value="__('Password')" />
                                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                                    required />
                                <x-input-error class="mt-2" :messages="$errors->get('password')" />
                            </div>

                            <div>
                                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                                    class="mt-1 block w-full" required />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="locale" :value="__('navigation.language')" />
                                <select id="locale" name="locale"
                                    class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm outline-none transition-all cursor-pointer">
                                    <option value="es" {{ old('locale') === 'es' ? 'selected' : '' }}>{{ __('Spanish') }}
                                    </option>
                                    <option value="en" {{ old('locale') === 'en' ? 'selected' : '' }}>{{ __('English') }}
                                    </option>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('locale')" />
                            </div>

                            <div>
                                <x-input-label for="timezone" :value="__('Timezone')" />
                                <select id="timezone" name="timezone"
                                    class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm outline-none transition-all cursor-pointer">
                                    @foreach($timezones as $tz)
                                        <option value="{{ $tz }}" {{ old('timezone', \App\Models\Setting::get('site_timezone', 'Europe/Madrid', true)) === $tz ? 'selected' : '' }}>
                                            {{ $tz }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4 mt-6">
                            <x-primary-button>{{ __('Create User') }}</x-primary-button>
                            <a href="{{ route('settings.users') }}"
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
