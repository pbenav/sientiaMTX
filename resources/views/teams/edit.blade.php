<!-- DEBUG_VERSION_3A788FC -->
<x-app-layout>
    @section('title', __('teams.edit'))

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.index') }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ __('teams.edit') }}: {{ $team->name }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-8 mb-4 flex w-full">
            @include('teams.partials.view-switcher')
        </div>

        <!-- Action Buttons Row -->
        <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6" x-data="{ tab: '{{ request('tab', 'general') }}' }">
        <div class="flex items-center gap-2 mb-8 bg-gray-100/50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 w-fit">
            <button @click="tab = 'general'" 
                :class="tab === 'general' ? 'bg-white dark:bg-gray-900 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-800' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                Información General
            </button>
            <button @click="tab = 'skills'" 
                :class="tab === 'skills' ? 'bg-white dark:bg-gray-900 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-800' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                Habilidades / Especialidades
            </button>
            <button @click="tab = 'appearance'" 
                :class="tab === 'appearance' ? 'bg-white dark:bg-gray-900 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-800' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                Apariencia del Equipo
            </button>
            <button @click="tab = 'whatsapp'" 
                :class="tab === 'whatsapp' ? 'bg-white dark:bg-gray-900 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-800' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
                WhatsApp del Equipo
            </button>
        </div>

        <!-- General Info Tab -->
        <div x-show="tab === 'general'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
            <!-- Edit form -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-transparent">
                    <h2 class="font-bold text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 heading">
                        {{ __('teams.info') }}
                    </h2>
                </div>

                <form method="POST" action="{{ route('teams.update', $team) }}" class="p-6 space-y-6">
                    @csrf @method('PATCH')

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                        <!-- Left Column: Primary Info -->
                        <div class="md:col-span-8 space-y-6">
                            <div>
                                <x-input-label for="name" :value="__('teams.name')"
                                    class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                                <x-text-input id="name" name="name" type="text" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 focus:bg-white dark:focus:bg-gray-800 transition-all"
                                    :value="old('name', $team->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="description" :value="__('teams.description')"
                                    class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                                <textarea id="description" name="description" rows="5"
                                    class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all resize-none placeholder-gray-400 focus:bg-white dark:focus:bg-gray-800">{{ old('description', $team->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Right Column: Integrations & Meta -->
                        <div class="md:col-span-4 space-y-6">
                            <div class="bg-gray-50/50 dark:bg-gray-800/30 border border-gray-100 dark:border-gray-800/50 rounded-2xl p-5">
                                <div class="flex items-center gap-2 mb-5">
                                    <span class="p-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                    </span>
                                    <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">{{ __('teams.telegram_integration') }}</h3>
                                </div>
                                
                                <div>
                                    <x-input-label for="telegram_chat_id" :value="__('teams.telegram_chat_id')"
                                        class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                                    <x-text-input id="telegram_chat_id" name="telegram_chat_id" type="text" class="block w-full font-mono text-xs bg-white dark:bg-gray-800"
                                        :value="old('telegram_chat_id', $team->telegram_chat_id)" placeholder="-123456789" />
                                    <p class="mt-3 text-[10px] leading-relaxed text-gray-400">{{ __('teams.telegram_chat_id_description') }}</p>
                                    <x-input-error :messages="$errors->get('telegram_chat_id')" class="mt-2" />
                                </div>
                            </div>

                            <div class="bg-gray-50/50 dark:bg-gray-800/30 border border-gray-100 dark:border-gray-800/50 rounded-2xl p-5">
                                <div class="flex items-center gap-2 mb-5">
                                    <span class="p-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </span>
                                    <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">Integración WhatsApp</h3>
                                </div>
                                
                                <div>
                                    <x-input-label for="whatsapp_chat_id" value="ID DE CHAT/NÚMERO DE WHATSAPP"
                                        class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                                    <x-text-input id="whatsapp_chat_id" name="whatsapp_chat_id" type="text" class="block w-full font-mono text-xs bg-white dark:bg-gray-800"
                                        :value="old('whatsapp_chat_id', $team->whatsapp_chat_id)" placeholder="34600123456" />
                                    <p class="mt-3 text-[10px] leading-relaxed text-gray-400">Para vincular el grupo o número, pon el ID (ej: 1234567890@c.us). Los mensajes se recibirán en el widget de WhatsApp.</p>
                                    <x-input-error :messages="$errors->get('whatsapp_chat_id')" class="mt-2" />
                                </div>
                            </div>

                            @if(auth()->user()->is_admin)
                            <div class="bg-gray-50/50 dark:bg-gray-800/30 border border-gray-100 dark:border-gray-800/50 rounded-2xl p-5">
                                <div class="flex items-center gap-2 mb-5">
                                    <span class="p-1.5 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4a2 2 0 012-2m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414a1 1 0 00-.707-.293H4" />
                                        </svg>
                                    </span>
                                    <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">{{ __('Mantenimiento (Admin)') }}</h3>
                                </div>
                                
                                <div>
                                    <x-input-label for="disk_quota_gb" :value="__('Cuota de Almacenamiento (GB)')"
                                        class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                                    <x-text-input id="disk_quota_gb" name="disk_quota_gb" type="number" step="0.1" min="0.1" class="block w-full font-bold bg-white dark:bg-gray-800"
                                        :value="old('disk_quota_gb', $team->disk_quota / 1024 / 1024 / 1024)" />
                                    <p class="mt-3 text-[10px] leading-relaxed text-gray-400">Define el límite máximo de archivos que este equipo puede alojar.</p>
                                    <x-input-error :messages="$errors->get('disk_quota_gb')" class="mt-2" />
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-end items-center gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <a href="{{ route('teams.dashboard', $team) }}"
                            class="text-xs font-bold uppercase tracking-widest text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            {{ __('teams.cancel') }}
                        </a>
                        <button type="submit"
                            class="bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold uppercase tracking-widest px-6 py-3 rounded-xl transition-all shadow-lg hover:shadow-violet-500/25">
                            {{ __('teams.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Transfer Ownership -->
            @can('transferOwnership', $team)
                <div
                    class="bg-white dark:bg-gray-900 border border-amber-100 dark:border-amber-900/30 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
                    <div
                        class="px-6 py-4 border-b border-amber-50 dark:border-amber-900/30 bg-amber-50/30 dark:bg-amber-900/10">
                        <h2 class="font-bold text-xs uppercase tracking-widest text-amber-600 dark:text-amber-400 heading">
                            {{ __('teams.transfer_ownership') }}
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="flex items-start gap-4 mb-6">
                            <div
                                class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                {{ __('teams.transfer_ownership_description') }}
                            </p>
                        </div>

                        <form id="transfer-ownership-form" method="POST"
                            action="{{ route('teams.transfer-ownership', $team) }}"
                            onsubmit="event.preventDefault(); if(confirm('{{ __('teams.transfer_ownership_confirm') }}')) this.submit();">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="user_id" :value="__('teams.new_owner')"
                                        class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                                    <select id="user_id" name="user_id" required
                                        class="block w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-amber-500 focus:ring focus:ring-amber-500/20 rounded-xl px-4 py-3 text-sm font-medium text-gray-900 dark:text-white outline-none transition-all shadow-sm">
                                        <option value="">{{ __('teams.select_member') ?? 'Seleccionar miembro' }}
                                        </option>
                                        @foreach ($team->members->where('id', '!=', auth()->id()) as $member)
                                            <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                                </div>

                                <div class="flex justify-end pt-2">
                                    <button type="submit"
                                        class="text-[11px] font-bold uppercase tracking-widest text-amber-600 dark:text-amber-400 hover:text-white hover:bg-amber-500 border border-amber-200 dark:border-amber-900/50 px-6 py-2.5 rounded-xl transition-all shadow-sm hover:shadow-amber-500/20">
                                        {{ __('teams.transfer_btn') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

            <!-- Danger zone -->
            @can('delete', $team)
                <div
                    class="bg-white dark:bg-gray-900 border border-red-100 dark:border-red-900/30 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
                    <div class="px-6 py-4 border-b border-red-50 dark:border-red-900/30 bg-red-50/50 dark:bg-red-900/10">
                        <h3 class="text-[10px] font-bold uppercase tracking-widest text-red-500 heading">
                            {{ __('teams.danger_zone') }}</h3>
                    </div>
                    <div class="p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-gray-700 dark:text-gray-200">{{ __('teams.delete_team') }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ __('teams.delete_confirm_description') }}</p>
                        </div>

                        <form id="delete-team-form" method="POST" action="{{ route('teams.destroy', $team) }}"
                            onsubmit="event.preventDefault(); confirmDelete('delete-team-form', '{{ __('teams.delete_confirm') }}')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="text-xs font-bold uppercase tracking-widest text-red-500 hover:text-white hover:bg-red-500 border border-red-200 dark:border-red-900/50 px-4 py-2.5 rounded-xl transition-all">
                                {{ __('teams.delete_team') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>

        <!-- Skills Tab -->
        <div x-show="tab === 'skills'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                @include('settings.partials.skill-management')
            </div>
        </div>

        <!-- Appearance Tab -->
        <div x-show="tab === 'appearance'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <form action="{{ route('teams.update', $team) }}" method="POST" class="space-y-6">
                        @csrf @method('PATCH')
                        <input type="hidden" name="name" value="{{ $team->name }}">
                        
                        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm">
                            <div class="p-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-black uppercase tracking-widest text-violet-600 dark:text-violet-400">Personalidad del Equipo: {{ $team->name }}</h3>
                                    <p class="text-xs text-gray-500 mt-1">Configura estilos exclusivos para este equipo. Sobrescriben los ajustes globales.</p>
                                </div>
                            </div>
                            
                            @php
                                $s = $team->settings ?? [];
                                $defaultGlobal = [
                                    'markdown_h1_size' => \App\Models\Setting::get('markdown_h1_size', '1.875rem'),
                                    'markdown_h1_weight' => \App\Models\Setting::get('markdown_h1_weight', '800'),
                                    'markdown_h2_size' => \App\Models\Setting::get('markdown_h2_size', '1.5rem'),
                                    'markdown_h2_weight' => \App\Models\Setting::get('markdown_h2_weight', '700'),
                                    'markdown_h3_size' => \App\Models\Setting::get('markdown_h3_size', '1.25rem'),
                                    'markdown_h3_weight' => \App\Models\Setting::get('markdown_h3_weight', '600'),
                                    'markdown_text_size' => \App\Models\Setting::get('markdown_text_size', '1rem'),
                                    'markdown_accent_color' => \App\Models\Setting::get('markdown_accent_color', '#4f46e5'),
                                    'markdown_bullet_color' => \App\Models\Setting::get('markdown_bullet_color', '#4f46e5'),
                                    'markdown_bq_color' => \App\Models\Setting::get('markdown_bq_color', '#4f46e5'),
                                    'markdown_bq_width' => \App\Models\Setting::get('markdown_bq_width', '4px'),
                                ];
@endphp
                            
                            <div class="p-8 space-y-10" x-data="{
                                settings: {
                                    h1_size: '{{ $s['markdown_h1_size'] ?? $defaultGlobal['markdown_h1_size'] }}',
                                    h1_weight: '{{ $s['markdown_h1_weight'] ?? $defaultGlobal['markdown_h1_weight'] }}',
                                    h2_size: '{{ $s['markdown_h2_size'] ?? $defaultGlobal['markdown_h2_size'] }}',
                                    h2_weight: '{{ $s['markdown_h2_weight'] ?? $defaultGlobal['markdown_h2_weight'] }}',
                                    h3_size: '{{ $s['markdown_h3_size'] ?? $defaultGlobal['markdown_h3_size'] }}',
                                    h3_weight: '{{ $s['markdown_h3_weight'] ?? $defaultGlobal['markdown_h3_weight'] }}',
                                    accent: '{{ $s['markdown_accent_color'] ?? $defaultGlobal['markdown_accent_color'] }}',
                                    bullet: '{{ $s['markdown_bullet_color'] ?? $defaultGlobal['markdown_bullet_color'] }}',
                                    bq: '{{ $s['markdown_bq_color'] ?? $defaultGlobal['markdown_bq_color'] }}',
                                    bq_width: '{{ $s['markdown_bq_width'] ?? $defaultGlobal['markdown_bq_width'] }}',
                                    text: '{{ $s['markdown_text_size'] ?? $defaultGlobal['markdown_text_size'] }}'
                                }
                            }">
                                <!-- Grid de Headings -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    @foreach(['h1' => '# Principal', 'h2' => '## Secundario', 'h3' => '### Terciario'] as $h => $label)
                                        <div class="space-y-4 p-5 bg-gray-50 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800">
                                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">{{ $label }}</label>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="block text-[9px] font-bold text-gray-500 mb-1 uppercase">Tamaño</label>
                                                    <input type="text" name="settings[markdown_{{ $h }}_size]" x-model="settings.{{ $h }}_size" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-sm focus:ring-violet-500/20 focus:border-violet-500">
                                                </div>
                                                <div>
                                                    <label class="block text-[9px] font-bold text-gray-500 mb-1 uppercase">Peso</label>
                                                    <input type="text" name="settings[markdown_{{ $h }}_weight]" x-model="settings.{{ $h }}_weight" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-700 rounded-xl text-sm focus:ring-violet-500/20 focus:border-violet-500">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4">
                                    <div class="space-y-6 p-5 bg-gray-50 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800">
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4">Colores de Acento</label>
                                        <div class="space-y-4">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Color Primario (Enlaces)</span>
                                                <input type="color" name="settings[markdown_accent_color]" x-model="settings.accent" class="h-8 w-8 rounded-lg overflow-hidden border-none cursor-pointer">
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Color Viñetas</span>
                                                <input type="color" name="settings[markdown_bullet_color]" x-model="settings.bullet" class="h-8 w-8 rounded-lg overflow-hidden border-none cursor-pointer">
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Borde Citas (Quotes)</span>
                                                <input type="color" name="settings[markdown_bq_color]" x-model="settings.bq" class="h-8 w-8 rounded-lg overflow-hidden border-none cursor-pointer">
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Ancho Borde Citas</span>
                                                <input type="text" name="settings[markdown_bq_width]" x-model="settings.bq_width" class="w-16 bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-xs text-center focus:ring-violet-500/20 focus:border-violet-500">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-5 bg-gray-50 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800">
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4">Cuerpo de Texto</label>
                                        <div>
                                            <label class="block text-[9px] font-bold text-gray-500 mb-1 uppercase">Tamaño Base</label>
                                            <input type="text" name="settings[markdown_text_size]" x-model="settings.text" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-sm focus:ring-violet-500/20 focus:border-violet-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- Previsualización del Equipo -->
                                <div class="mt-4 p-8 bg-gray-100 dark:bg-gray-950/20 rounded-3xl border border-dashed border-gray-200 dark:border-gray-800 overflow-hidden">
                                     <h1 :style="'font-size: ' + settings.h1_size + '; font-weight: ' + settings.h1_weight + '; color: ' + settings.accent + '; margin-top:0;'" class="mb-4">Título del Equipo</h1>
                                     <h2 :style="'font-size: ' + settings.h2_size + '; font-weight: ' + settings.h2_weight + ';'" class="mb-3">Subtítulo Secundario</h2>
                                     <h3 :style="'font-size: ' + settings.h3_size + '; font-weight: ' + settings.h3_weight + ';'" class="mb-2">Sección Detallada</h3>
                                     <p :style="'font-size: ' + settings.text + ';'" class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                                         Este es un ejemplo de cómo se verá la información en el foro y las tareas de <strong>{{ $team->name }}</strong>. Incluye <a href="#" :style="'color: ' + settings.accent + '; text-decoration: underline;'">vínculos personalizados</a>.
                                     </p>
                                     <ul class="mb-4 space-y-1">
                                        <li class="flex items-center gap-2">
                                            <span :style="'color: ' + settings.bullet">•</span>
                                            <span class="text-xs text-gray-600 dark:text-gray-400">Lista personalizada</span>
                                        </li>
                                     </ul>
                                     <div :style="'border-left: ' + settings.bq_width + ' solid ' + settings.bq" class="pl-4 py-1 bg-gray-50 dark:bg-gray-800/40 rounded-r-lg italic text-[11px] text-gray-500">
                                         "Cita representativa del espíritu de este equipo."
                                     </div>
                                </div>
                            </div>

                            <div class="p-6 bg-gray-50/50 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                                <button type="submit" class="px-8 py-3 bg-violet-600 hover:bg-violet-500 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-violet-500/20">
                                    Guardar Identidad del Equipo
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="lg:col-span-1 space-y-6">
                    <div class="p-6 bg-violet-50 dark:bg-violet-500/10 rounded-3xl border border-violet-100 dark:border-violet-800/50">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="p-2 bg-violet-600 text-white rounded-xl shadow-lg shadow-violet-600/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.828 2.828a2 2 0 010 2.828l-8.486 8.485"></path></svg>
                            </span>
                            <h3 class="text-xs font-black uppercase tracking-widest text-violet-700 dark:text-violet-400">¿Por qué per-equipo?</h3>
                        </div>
                        <p class="text-xs text-violet-600/80 dark:text-violet-400/80 leading-relaxed font-medium">
                            Diferenciar visualmente los equipos ayuda a los usuarios a ubicarse rápidamente al cambiar de contexto. 
                            <br><br>
                            Un equipo creativo puede usar fuentes grandes y colores vibrantes, mientras que uno técnico puede preferir algo más condensado y sobrio.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- WhatsApp Tab -->
        <div x-show="tab === 'whatsapp'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
            <div x-data="{
                    ready: false,
                    qr: null,
                    loading: false,
                    initSession: false,
                    pollingInterval: null,
                    async checkStatus() {
                        try {
                            const url = '{{ route('whatsapp.team-status') }}?team_id={{ $team->id }}' + (this.initSession ? '&init=true' : '');
                            const response = await fetch(url);
                            const data = await response.json();
                            this.ready = data.ready;
                            this.qr = data.qr;
                            if (this.ready) {
                                this.initSession = false;
                            }
                        } catch (e) {
                            console.error('Error consultando estado de WhatsApp del Equipo:', e);
                        }
                    },
                    startPolling() {
                        this.loading = true;
                        this.checkStatus();
                        this.pollingInterval = setInterval(() => {
                            this.checkStatus();
                        }, 3000);
                    },
                    stopPolling() {
                        if (this.pollingInterval) {
                            clearInterval(this.pollingInterval);
                            this.pollingInterval = null;
                        }
                        this.loading = false;
                    },
                    async startConnection() {
                        this.initSession = true;
                        await this.checkStatus();
                    },
                    async restartSession() {
                        if (!confirm('¿Deseas desvincular o reiniciar la cuenta de WhatsApp corporativa de este Equipo?')) return;
                        try {
                            await fetch('{{ route('whatsapp.team-restart') }}', {
                                method: 'POST',
                                headers: { 
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ team_id: {{ $team->id }} })
                            });
                            this.ready = false;
                            this.qr = null;
                            this.initSession = true;
                            this.startPolling();
                        } catch (e) {
                            console.error('Error al reiniciar sesión de equipo:', e);
                        }
                    }
                 }"
                 x-init="checkStatus(); startPolling()"
                 x-on:destroy="stopPolling()"
                 class="p-6 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl space-y-4">
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl text-emerald-500">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span>WhatsApp Corporativo de Equipo</span>
                                <span class="px-2 py-0.5 bg-gradient-to-r from-violet-500 to-indigo-500 text-white text-[8px] font-black uppercase rounded-full">Canal de Equipo</span>
                            </h4>
                            <p class="text-[10px] text-gray-400 font-medium">Vincula un número de teléfono móvil corporativo exclusivo para el equipo <strong>{{ $team->name }}</strong></p>
                        </div>
                    </div>
                    <div>
                        <template x-if="ready">
                            <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 text-[10px] font-black uppercase rounded-lg border border-emerald-100 dark:border-emerald-800">Conectado</span>
                        </template>
                        <template x-if="!ready && qr">
                            <span class="px-3 py-1 bg-amber-50 dark:bg-amber-900/20 text-amber-600 text-[10px] font-black uppercase rounded-lg border border-amber-100 dark:border-amber-800 animate-pulse">Esperando Escaneo</span>
                        </template>
                        <template x-if="!ready && !qr">
                            <span class="px-3 py-1 bg-gray-50 dark:bg-gray-800 text-gray-400 text-[10px] font-black uppercase rounded-lg border border-gray-200 dark:border-gray-700">Desconectado</span>
                        </template>
                    </div>
                </div>

                <!-- Interfaz de Conexión en Caja de Coherencia Gris idéntica a Telegram -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center bg-gray-50/50 dark:bg-gray-800/30 p-6 rounded-2xl border border-gray-100 dark:border-gray-800">
                    <div class="space-y-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                            Al vincular un WhatsApp exclusivo de equipo, los mensajes del foro, notificaciones de tareas del equipo y alertas se enviarán directamente con este número corporativo. Toda la comunicación está aislada de forma segura para este equipo.
                        </p>
                        
                        <div class="flex gap-3 pt-2">
                            <!-- Botón de Conexión Pasiva -->
                            <template x-if="!ready && !qr && !initSession">
                                <button @click="startConnection()" type="button" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white text-[10px] font-black uppercase rounded-xl transition-all shadow-md shadow-emerald-500/10">
                                    Vincular WhatsApp de Equipo
                                </button>
                            </template>

                            <!-- Botón de Desvinculación -->
                            <template x-if="ready || qr || initSession">
                                <button @click="restartSession()" type="button" class="px-4 py-2 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-900/30 text-red-600 text-[10px] font-black uppercase rounded-xl transition-all">
                                    Desvincular Cuenta
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Panel de QR / Cargando / Listo -->
                    <div class="flex justify-center md:border-l border-gray-100 dark:border-gray-800 md:pl-8 py-2 min-h-[14rem] items-center">
                        <!-- Caso 1: Listo y conectado -->
                        <div x-show="ready" class="flex flex-col items-center text-center space-y-2">
                            <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-950/30 flex items-center justify-center text-emerald-500 shadow-sm border border-emerald-100 dark:border-emerald-800/50">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <span class="text-xs font-bold text-gray-900 dark:text-white">¡Sesión de Equipo Activa!</span>
                            <span class="text-[10px] text-gray-400">El número está listo para enviar y recibir notificaciones corporativas de este equipo.</span>
                        </div>

                        <!-- Caso 2: QR disponible para escaneo -->
                        <div x-show="!ready && qr" class="flex flex-col items-center space-y-4">
                            <div class="p-3 bg-white rounded-2xl shadow-xl border border-gray-100 inline-block">
                                <img :src="qr" alt="WhatsApp QR Code" class="w-44 h-44 object-contain">
                            </div>
                            <div class="text-center">
                                <span class="text-[10px] font-black text-amber-500 uppercase tracking-widest block">Escanea el Código</span>
                                <span class="text-[8px] text-gray-400 mt-1 block">Abre WhatsApp > Dispositivos Vinculados > Vincular un Dispositivo</span>
                            </div>
                        </div>

                        <!-- Caso 3: Iniciando o buscando estado -->
                        <div x-show="!ready && !qr && initSession" class="flex flex-col items-center text-center space-y-3">
                            <svg class="w-8 h-8 text-emerald-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Generando sesión...</span>
                            <span class="text-[8px] text-gray-400">Iniciando Puppeteer en el servidor para el equipo de forma aislada</span>
                        </div>

                        <!-- Caso 4: Desconectado pasivo -->
                        <div x-show="!ready && !qr && !initSession" class="flex flex-col items-center text-center space-y-2 text-gray-400">
                            <svg class="w-12 h-12 stroke-current" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                            <span class="text-[10px] font-bold uppercase tracking-widest">Sin Vinculación Activa</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function confirmDelete(formId, message) {
                if (confirm(message)) {
                    document.getElementById(formId).submit();
                }
            }
        </script>
    @endpush
</x-app-layout>
