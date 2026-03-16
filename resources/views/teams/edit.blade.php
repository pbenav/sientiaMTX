<x-app-layout>
    @section('title', __('teams.edit'))

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white heading">{{ __('teams.edit') }}:
                    {{ $team->name }}</h1>
            </div>

            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
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
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-transparent">
                    <h2 class="font-bold text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 heading">
                        {{ __('teams.transfer_ownership') }}
                    </h2>
                </div>

                <div class="p-6">
                    <p class="text-xs text-gray-500 mb-4">{{ __('teams.transfer_ownership_description') }}</p>

                    <form id="transfer-ownership-form" method="POST"
                        action="{{ route('teams.transfer-ownership', $team) }}"
                        onsubmit="event.preventDefault(); if(confirm('{{ __('teams.transfer_ownership_confirm') }}')) this.submit();">
                        @csrf
                        <div class="flex flex-col sm:flex-row items-end gap-3">
                            <div class="flex-1 w-full">
                                <x-input-label for="user_id" :value="__('teams.new_owner')"
                                    class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                                <select id="user_id" name="user_id" required
                                    class="block w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2 text-sm text-gray-900 dark:text-white outline-none transition-all">
                                    <option value="">{{ __('teams.select_member') ?? 'Seleccionar miembro' }}
                                    </option>
                                    @foreach ($team->members->where('id', '!=', auth()->id()) as $member)
<option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
@endforeach
                                </select>
                            </div>
                            <button type="submit"
                                class="w-full sm:w-auto bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-xs font-bold uppercase tracking-widest px-6 py-2.5 rounded-xl transition-all shadow-lg hover:shadow-gray-500/25">
                                {{ __('teams.transfer_btn') }}
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
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
