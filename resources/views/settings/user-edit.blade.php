<x-app-layout>
    @section('title', __('Edit User'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl shadow-sm border border-violet-200 dark:border-violet-800/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">
                        <a href="{{ route('settings.users') }}"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors mr-2">
                            {{ __('navigation.users') }}
                        </a> / {{ __('Edit User') }}: {{ $user->name }}
                    </h1>
                    <x-demo-hint>
                        Panel avanzado de perfil. Permite a los administradores modificar datos de acceso, forzar cuotas, habilitar integraciones Premium (como WhatsApp Personal) o definir los turnos horarios del usuario.
                    </x-demo-hint>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-4 shadow-sm">
        <div class="max-w-7xl mx-auto">
            @include('settings.partials.tabs')

            <div class="space-y-6">
                <!-- User Information -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 11-7-7z" />
                        </svg>
                        {{ __('User Information') }}
                    </h2>

                    <form action="{{ route('settings.users.update', $user) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="name" :value="__('Name')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                    :value="old('name', $user->name)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="email" :value="__('Email')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                    :value="old('email', $user->email)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            </div>

                            <div>
                                <x-input-label for="password" :value="__('Password')" />
                                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                                    placeholder="{{ __('Leave blank to keep current password') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('password')" />
                            </div>

                            <div>
                                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                                    class="mt-1 block w-full" />
                            </div>

                            <div>
                                <x-input-label for="locale" :value="__('navigation.language')" />
                                <select id="locale" name="locale"
                                    class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm outline-none transition-all cursor-pointer">
                                    <option value="es" {{ old('locale', $user->locale) === 'es' ? 'selected' : '' }}>
                                        {{ __('Spanish') }}
                                    </option>
                                    <option value="en" {{ old('locale', $user->locale) === 'en' ? 'selected' : '' }}>
                                        {{ __('English') }}
                                    </option>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('locale')" />
                            </div>

                            <div>
                                <x-input-label for="timezone" :value="__('Timezone')" />
                                <select id="timezone" name="timezone"
                                    class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm outline-none transition-all cursor-pointer">
                                    @foreach($timezones as $tz)
                                        <option value="{{ $tz }}" {{ old('timezone', $user->timezone ?? \App\Models\Setting::get('site_timezone', 'Europe/Madrid', true)) === $tz ? 'selected' : '' }}>
                                            {{ $tz }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('timezone')" />
                            </div>

                            <div>
                                <x-input-label for="disk_quota" :value="__('Disk Quota') . ' (MB)'" />
                                <x-text-input id="disk_quota" name="disk_quota" type="number" class="mt-1 block w-full"
                                    :value="old('disk_quota', $user->disk_quota / 1024 / 1024)" required min="1" />
                                <x-input-error class="mt-2" :messages="$errors->get('disk_quota')" />
                            </div>

                            <div>
                                <x-input-label for="invitations_left" :value="__('Invitaciones Disponibles')" />
                                <x-text-input id="invitations_left" name="invitations_left" type="number" class="mt-1 block w-full"
                                    :value="old('invitations_left', $user->invitations_left ?? 0)" required min="0" />
                                <x-input-error class="mt-2" :messages="$errors->get('invitations_left')" />
                            </div>

                            <div class="flex items-center gap-3 pt-6 md:col-span-2 lg:col-span-3">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="whatsapp_personal_allowed" value="1" 
                                           class="rounded-lg border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500/20 w-5 h-5 cursor-pointer"
                                           {{ old('whatsapp_personal_allowed', ($user->notification_settings['whatsapp_personal_allowed'] ?? false)) ? 'checked' : '' }}>
                                    <div class="ml-3">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                            <span>Permitir WhatsApp Personal (Premium)</span>
                                            <span class="px-2 py-0.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-[8px] font-black uppercase rounded-full">Premium</span>
                                        </span>
                                        <span class="block text-[10px] text-gray-400 font-medium">Habilita a este usuario la capacidad de vincular su propio número de WhatsApp móvil privado de forma aislada en su perfil</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Horario de Trabajo Selection -->
                            <div class="md:col-span-2 lg:col-span-3 p-6 bg-gray-50/50 dark:bg-gray-800/30 rounded-2xl border border-gray-100 dark:border-gray-800/50 space-y-5">
                                <input type="hidden" name="schedule_provided" value="1">
                                <div class="flex items-center gap-2">
                                    <span class="p-1.5 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h3 class="text-xs font-black uppercase tracking-widest text-violet-600 dark:text-violet-400">Horario de Trabajo Diario</h3>
                                        <span class="text-[9px] text-gray-400 font-medium block uppercase tracking-wider mt-0.5">Define los turnos de trabajo del usuario</span>
                                    </div>
                                </div>

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
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-primary-button>
                                {{ __('Save Changes') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>

                <!-- Pending Invitations -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
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
                                        <th
                                            class="px-4 py-3 text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                            {{ __('Team') }}</th>
                                        <th
                                            class="px-4 py-3 text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                            {{ __('Role') }}</th>
                                        <th
                                            class="px-4 py-3 text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400 text-right">
                                            {{ __('Actions') }}</th>
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
                                                <form
                                                    action="{{ route('settings.users.accept-invitation', [$user, $invitation]) }}"
                                                    method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                        class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:text-violet-700 transition-colors">
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
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
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
                                <div
                                    class="border border-gray-100 dark:border-gray-800 rounded-xl p-4 bg-gray-50 dark:bg-gray-800/20">
                                    <div class="font-bold text-gray-900 dark:text-white mb-1">{{ $team->name }}</div>
                                    <div class="text-xs text-violet-500 font-semibold uppercase tracking-wider">
                                        {{ __('teams.' . ($team->members()->find($user->id)->pivot->role->name ?? 'user')) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
                    <div class="mt-4 border-t border-red-50/50 dark:border-red-900/10 pt-6">
                        <h3 class="text-lg font-bold text-red-600 dark:text-red-400 mb-2">{{ __('Danger Zone') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            {{ __('Once you delete a user, there is no going back. Please be certain.') }}</p>

                        <form id="delete-user-form" action="{{ route('settings.users.destroy', $user) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                onclick="window.confirmDelete('delete-user-form', '{{ __('Are you sure you want to delete this user?') }}')"
                                class="px-6 py-3 bg-white dark:bg-gray-900 border-2 border-red-500 text-red-600 hover:bg-red-500 hover:text-white font-bold rounded-2xl transition-all shadow-lg hover:shadow-red-500/20 active:scale-95">
                                {{ __('Delete User') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
