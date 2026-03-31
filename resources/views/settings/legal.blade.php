<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-white leading-tight heading">
            {{ __('Legal Texts Configuration') }}
        </h2>
    </x-slot>

    <!-- Trix Editor Assets -->
    <link rel="stylesheet" href="https://unpkg.com/trix@2.0.8/dist/trix.css">
    <script src="https://unpkg.com/trix@2.0.8/dist/trix.umd.min.js"></script>
    <style>
        trix-editor {
            min-height: 400px;
            background-color: white;
            border-radius: 0.75rem;
            border-color: #e5e7eb;
        }
        .dark trix-editor {
            background-color: #111827;
            border-color: #374151;
            color: #d1d5db;
        }
        trix-toolbar .trix-button-row {
            margin-bottom: 0.5rem;
        }
    </style>

    <div class="py-12 px-4 shadow-sm">
        <div class="max-w-7xl mx-auto">
            @include('settings.partials.tabs')

            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 rounded-xl flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl sm:rounded-2xl overflow-hidden transition-all">
                <div class="p-8">
                    <form method="POST" action="{{ route('settings.legal.update') }}" class="space-y-8">
                        @csrf

                        <div x-data="{ tab: 'privacy' }" class="space-y-6">
                            <!-- Tabs Navigation -->
                            <div class="flex border-b border-gray-200 dark:border-gray-800 mb-6 overflow-x-auto">
                                <button type="button" @click="tab = 'privacy'" :class="tab === 'privacy' ? 'border-violet-500 text-violet-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                                    {{ __('Privacy Policy') }}
                                </button>
                                <button type="button" @click="tab = 'terms'" :class="tab === 'terms' ? 'border-violet-500 text-violet-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                                    {{ __('Terms of Service') }}
                                </button>
                                <button type="button" @click="tab = 'cookies'" :class="tab === 'cookies' ? 'border-violet-500 text-violet-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-all focus:outline-none">
                                    {{ __('Cookie Policy') }}
                                </button>
                            </div>

                            <!-- Tab Contents -->
                            <div x-show="tab === 'privacy'" x-cloak>
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Privacy Policy') }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Use the editor below to customize your privacy statement.') }}</p>
                                </div>
                                <input id="legal_privacy" type="hidden" name="legal_privacy" value="{{ old('legal_privacy', $privacy) }}">
                                <trix-editor input="legal_privacy" class="trix-content"></trix-editor>
                                <x-input-error :messages="$errors->get('legal_privacy')" class="mt-2" />
                            </div>

                            <div x-show="tab === 'terms'" x-cloak>
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Terms of Service') }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Define your terms and conditions for all users.') }}</p>
                                </div>
                                <input id="legal_terms" type="hidden" name="legal_terms" value="{{ old('legal_terms', $terms) }}">
                                <trix-editor input="legal_terms" class="trix-content"></trix-editor>
                                <x-input-error :messages="$errors->get('legal_terms')" class="mt-2" />
                            </div>

                            <div x-show="tab === 'cookies'" x-cloak>
                                <div class="mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Cookie Policy') }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Explain how you use cookies and tracking technologies.') }}</p>
                                </div>
                                <input id="legal_cookies" type="hidden" name="legal_cookies" value="{{ old('legal_cookies', $cookies) }}">
                                <trix-editor input="legal_cookies" class="trix-content"></trix-editor>
                                <x-input-error :messages="$errors->get('legal_cookies')" class="mt-2" />
                            </div>
                        </div>

                        <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-800/50 p-6 rounded-2xl">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" id="notify_changes" name="notify_changes" value="1"
                                    class="rounded-lg border-gray-300 dark:border-gray-700 text-violet-600 shadow-sm focus:ring-violet-500 w-5 h-5 transition-all">
                                <div>
                                    <label for="notify_changes" class="text-sm font-bold text-gray-900 dark:text-white block mb-0.5">
                                        {{ __('Notify substantial changes') }}
                                    </label>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                        {{ __('Marking this will require all users to re-accept terms upon their next login or dashboard access.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-6 border-t border-gray-100 dark:border-gray-800">
                            <x-primary-button class="px-8 py-3">
                                {{ __('Save Legal Texts') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Warning Note -->
            <div class="mt-8 p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl flex gap-4 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 dark:text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-200/90 leading-relaxed">
                    <p class="font-bold mb-1">{{ __('Note on Customization') }}</p>
                    <p>{{ __('The changes made here will be reflected instantly on the public legal pages. If a field is left empty, the system will use the default template text.') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
