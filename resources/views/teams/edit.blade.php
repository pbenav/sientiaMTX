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
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                        {{ __('teams.edit') }}: {{ $team->name }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-4 mb-2 flex w-full">
            @include('teams.partials.view-switcher')
        </div>

        <!-- Action Buttons Row -->
        <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Edit form -->
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-transparent">
                <h2 class="font-bold text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 heading">
                    {{ __('teams.info') }}
                </h2>
            </div>

            <form method="POST" action="{{ route('teams.update', $team) }}" class="p-6 space-y-6">
                @csrf @method('PATCH')

                <div class="space-y-4">
                    <div>
                        <x-input-label for="name" :value="__('teams.name')"
                            class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                        <x-text-input id="name" name="name" type="text" class="block w-full"
                            :value="old('name', $team->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('teams.description')"
                            class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                        <textarea id="description" name="description" rows="4"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all resize-none placeholder-gray-400">{{ old('description', $team->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="p-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </span>
                            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">{{ __('teams.telegram_integration') }}</h3>
                        </div>
                        
                        <x-input-label for="telegram_chat_id" :value="__('teams.telegram_chat_id')"
                            class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                        <x-text-input id="telegram_chat_id" name="telegram_chat_id" type="text" class="block w-full font-mono text-xs"
                            :value="old('telegram_chat_id', $team->telegram_chat_id)" placeholder="-123456789" />
                        <p class="mt-2 text-[10px] text-gray-500">{{ __('teams.telegram_chat_id_description') }}</p>
                        <x-input-error :messages="$errors->get('telegram_chat_id')" class="mt-2" />
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
</x-app-layout>)
