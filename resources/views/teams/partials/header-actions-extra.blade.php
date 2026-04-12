@php
    $layout = $layout ?? 'horizontal';
@endphp

@if ($layout === 'vertical')
    <div class="flex items-center gap-1.5 sm:gap-2">
        <!-- Compact selectors for vertical layout header -->
        <div class="flex items-center gap-1">
            @include('layouts.partials.theme-toggle')
            @include('layouts.partials.layout-toggle')
            @include('layouts.partials.zoom-controls')
            @include('layouts.partials.language-toggle')

            <div class="h-6 w-px bg-gray-200 dark:bg-gray-800 mx-1 shrink-0"></div>
            <div class="h-6 w-px bg-gray-200 dark:bg-gray-800 mx-1"></div>
            
            <!-- Notifications Bell -->
            <a href="{{ route('notifications.index') }}" class="relative p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors duration-150 rounded-lg hover:bg-violet-50 dark:hover:bg-violet-500/10" title="{{ __('Notificaciones') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                @if(Auth::user()->unreadNotifications->count() > 0)
                    <span class="absolute top-0.5 right-0.5 flex h-3.5 w-3.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500 text-[8px] text-white font-bold items-center justify-center">
                            {{ Auth::user()->unreadNotifications->count() > 9 ? '9+' : Auth::user()->unreadNotifications->count() }}
                        </span>
                    </span>
                @endif
            </a>

            <!-- User menu for vertical header -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.outside="open = false"
                        class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">
                        <div
                            class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white shadow-lg">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    </button>
                    <div x-show="open" x-transition
                        class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden z-50">
                        <div
                            class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-transparent">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ __('navigation.profile') }}
                        </a>
                        <div class="border-t border-gray-100 dark:border-gray-700">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    {{ __('navigation.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endauth
        </div>
    </div>
@endif
