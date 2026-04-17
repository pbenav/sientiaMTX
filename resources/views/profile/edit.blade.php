<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-white leading-tight heading">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ activeTab: '{{ request('tab', 'general') }}' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Tabs Nav -->
            <div class="flex gap-4 border-b border-gray-200 dark:border-gray-800 pb-2 overflow-x-auto">
                <button @click="activeTab = 'general'" :class="activeTab === 'general' ? 'border-violet-500 text-violet-600 dark:text-violet-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="whitespace-nowrap px-4 py-2 border-b-2 font-bold text-sm tracking-tight transition-colors">General</button>
                <button @click="activeTab = 'integrations'" :class="activeTab === 'integrations' ? 'border-violet-500 text-violet-600 dark:text-violet-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="whitespace-nowrap px-4 py-2 border-b-2 font-bold text-sm tracking-tight transition-colors">Integraciones e IA</button>
                <button @click="activeTab = 'notifications'" :class="activeTab === 'notifications' ? 'border-violet-500 text-violet-600 dark:text-violet-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="whitespace-nowrap px-4 py-2 border-b-2 font-bold text-sm tracking-tight transition-colors">Notificaciones</button>
                <button @click="activeTab = 'security'" :class="activeTab === 'security' ? 'border-violet-500 text-violet-600 dark:text-violet-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'" class="whitespace-nowrap px-4 py-2 border-b-2 font-bold text-sm tracking-tight transition-colors">Seguridad y Privacidad</button>
            </div>

            <!-- TAB: General -->
            <div x-show="activeTab === 'general'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm dark:shadow-none sm:rounded-2xl transition-colors">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            <!-- TAB: Integrations -->
            <div x-show="activeTab === 'integrations'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm dark:shadow-none sm:rounded-2xl transition-colors">
                    <div class="max-w-xl">
                        @include('profile.partials.integrations-form')
                    </div>
                </div>
            </div>

            <!-- TAB: Notifications -->
            <div x-show="activeTab === 'notifications'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm dark:shadow-none sm:rounded-2xl transition-all">
                    <div class="max-w-xl">
                        @include('profile.partials.notification-settings-form')
                    </div>
                </div>
            </div>

            <!-- TAB: Security -->
            <div x-show="activeTab === 'security'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" class="space-y-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm dark:shadow-none sm:rounded-2xl transition-colors">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm dark:shadow-none sm:rounded-2xl transition-colors">
                    <div class="max-w-xl">
                        @include('profile.partials.gdpr-data-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm dark:shadow-none sm:rounded-2xl transition-colors">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
