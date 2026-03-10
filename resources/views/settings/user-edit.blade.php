<x-app-layout>
    @section('title', __('Edit User'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">
                    <a href="{{ route('settings.users') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors mr-2">
                        {{ __('navigation.users') }}
                    </a> / {{ __('Edit User') }}: {{ $user->name }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Update user details and manage team invitations.') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- User Information -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ __('User Information') }}
            </h2>

            <form action="{{ route('settings.users.update', $user) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" placeholder="{{ __('Leave blank to keep current password') }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('password')" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
                    </div>

                    <div>
                        <x-input-label for="locale" :value="__('navigation.language')" />
                        <select id="locale" name="locale" class="block mt-1 w-full border-gray-700 bg-gray-900 text-gray-300 focus:border-violet-500 focus:ring-violet-500 rounded-md shadow-sm">
                            <option value="es" {{ $user->locale === 'es' ? 'selected' : '' }}>{{ __('Spanish') }}</option>
                            <option value="en" {{ $user->locale === 'en' ? 'selected' : '' }}>{{ __('English') }}</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('locale')" />
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-6">
                    <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                </div>
            </form>
        </div>

        <!-- Pending Invitations -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ __('Pending Invitations') }}
            </h2>

            @if ($invitations->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                    {{ __('No pending invitations for this user.') }}
                </p>
            @else
                <div class="overflow-x-auto border border-gray-100 dark:border-gray-800 rounded-xl">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">{{ __('Team') }}</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">{{ __('Role') }}</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            @foreach ($invitations as $invitation)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/20 transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $invitation->team->name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $invitation->role->name }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <form action="{{ route('settings.users.accept-invitation', [$user, $invitation]) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:text-violet-700 transition-colors">
                                                {{ __('Accept Invitation') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Team Memberships -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                {{ __('Team Memberships') }}
            </h2>

            @if ($user->teams->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                    {{ __('This user is not a member of any team.') }}
                </p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($user->teams as $team)
                        <div class="border border-gray-100 dark:border-gray-800 rounded-xl p-4 bg-gray-50 dark:bg-gray-800/20">
                            <div class="font-bold text-gray-900 dark:text-white mb-1">{{ $team->name }}</div>
                            <div class="text-xs text-violet-500 font-semibold uppercase tracking-wider">
                                {{ $team->pivot->role_id == 1 ? __('Coordinator') : ($team->pivot->role_id == 2 ? __('Moderator') : __('Member')) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
