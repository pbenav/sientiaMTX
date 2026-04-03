<x-app-layout>
    @section('title', __('teams.create'))

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('teams.index') }}"
                class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white heading">{{ __('teams.create') }}</h1>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm transition-colors">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-transparent">
                <h2 class="font-bold text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 heading">
                    {{ __('teams.info') }}
                </h2>
            </div>
            
            <form method="POST" action="{{ route('teams.store') }}" class="p-6 space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                    <!-- Left Column -->
                    <div class="md:col-span-8 space-y-6">
                        <!-- Name -->
                        <div>
                            <x-input-label for="name" :value="__('teams.name')"
                                class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                            <x-text-input id="name" name="name" type="text" class="block w-full border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 focus:bg-white dark:focus:bg-gray-800 transition-all font-medium"
                                :value="old('name')" required autofocus placeholder="{{ __('teams.name') }}..." />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <!-- Description -->
                        <div>
                            <x-input-label for="description" :value="__('teams.description')"
                                class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2" />
                            <textarea id="description" name="description" rows="5"
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all resize-none placeholder-gray-400 focus:bg-white dark:focus:bg-gray-800"
                                placeholder="{{ __('teams.description') }}...">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Right Column: Optional/Meta -->
                    <div class="md:col-span-4 space-y-6">
                        <div class="bg-violet-50/30 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-800/50 rounded-2xl p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/50 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="space-y-1">
                                    <h4 class="text-xs font-bold text-violet-900 dark:text-violet-100">{{ __('Nota') }}</h4>
                                    <p class="text-[10px] text-violet-600/70 dark:text-violet-400/70 leading-relaxed italic">
                                        {{ __('Los equipos permiten agrupar miembros y tareas bajo un mismo contexto de trabajo.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end items-center gap-4 pt-6 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('teams.index') }}"
                        class="text-xs font-bold uppercase tracking-widest text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        {{ __('teams.cancel') }}
                    </a>
                    <button type="submit"
                        class="bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold uppercase tracking-widest px-8 py-3 rounded-xl transition-all shadow-lg hover:shadow-violet-500/25">
                        {{ __('teams.save_changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
