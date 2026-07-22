    <!-- Navigation -->
    <nav x-show="layout === 'horizontal'" style="{{ $layout === 'vertical' ? 'display:none' : '' }}"
        x-data="{ mobileMenuOpen: false }"
        class="bg-white border-b border-gray-200 dark:bg-gray-950 dark:border-gray-800 sticky top-0 z-[80] w-full overflow-visible">
        <div class="max-w-none lg:{{ $maxWidth }} mx-auto px-2 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-12">

                <!-- Logo -->
                <a href="{{ auth()->check() ? (request()->route('team') ? route('teams.dashboard', request()->route('team')) : route('dashboard')) : route('home') }}"
                    class="flex items-center gap-2 group shrink-0">
                    <div
                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center shadow-lg group-hover:shadow-violet-500/30 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="3" y="3" width="8" height="8" rx="1" />
                            <rect x="13" y="3" width="8" height="8" rx="1" />
                            <rect x="3" y="13" width="8" height="8" rx="1" />
                            <rect x="13" y="13" width="8" height="8" rx="1" />
                        </svg>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white text-lg tracking-tight"
                        style="font-family:'Space Grotesk',sans-serif">sientia<span
                            class="text-violet-600 dark:text-violet-400">MTX</span></span>
                </a>

                <!-- Right side: flex container taking remaining space -->
                <div class="flex items-center gap-1 sm:gap-3 flex-1 justify-end min-w-0">

                    <!-- 1. DESKTOP: Inline Icons (Labels only on lg+) -->
                    <div class="hidden lg:flex items-center gap-1 sm:gap-3 overflow-x-auto min-w-0 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                    @auth
                        @if(auth()->user()->favoriteTeam)
                            <!-- Favorite Team Desktop -->
                            <a href="{{ route('teams.dashboard', auth()->user()->favoriteTeam) }}"
                                class="flex flex-col items-center justify-center gap-0.5 px-2.5 min-h-[3rem] text-amber-500 dark:text-amber-400 hover:text-amber-600 dark:hover:text-amber-300 transition-all rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 relative group"
                                title="Escritorio de {{ auth()->user()->favoriteTeam->name }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 fill-amber-400/20" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                </svg>
                                <span class="hidden lg:block text-[10px] leading-none mt-0.5 text-center">Escritorio</span>
                            </a>
                        @endif

                        <!-- My Teams -->
                        <a href="{{ route('teams.index') }}"
                            class="flex flex-col items-center justify-center gap-0.5 px-2.5 min-h-[3rem] text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-all rounded-lg hover:bg-violet-50 dark:hover:bg-violet-500/10 relative group"
                            title="{{ __('navigation.my_teams') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="hidden lg:block text-[10px] leading-none mt-0.5 text-center">{{ __('navigation.my_teams') ?? 'Mis Equipos' }}</span>
                            @php $teamCount = auth()->user()->teams()->count(); @endphp
                            @if($teamCount > 0)
                                <span class="absolute top-0.5 right-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-violet-600 text-[8px] font-bold text-white shadow-sm ring-1 ring-white dark:ring-gray-950">
                                    {{ $teamCount }}
                                </span>
                            @endif
                        </a>

                        <!-- Canal Ciudadano -->
                        <a href="{{ route('global-surveys.index') }}"
                            class="flex flex-col items-center justify-center gap-0.5 px-2.5 min-h-[3rem] text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-500/10 {{ request()->routeIs('global-surveys.*') ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : '' }}"
                            title="{{ __('Encuestas Globales') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                            <span class="hidden lg:block text-[10px] leading-none mt-0.5 text-center">Portal Ciudadano</span>
                        </a>

                        <!-- Disk Usage -->
                        <a href="{{ route('media.index') }}"
                            class="flex flex-col items-center justify-center gap-0.5 px-2.5 min-h-[3rem] text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all rounded-lg hover:bg-blue-50 dark:hover:bg-blue-500/10 {{ request()->routeIs('media.index') ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400' : '' }}"
                            title="{{ __('tasks.disk_quota') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                            <span class="hidden lg:block text-[10px] leading-none mt-0.5 text-center">Archivos</span>
                        </a>

                        <a href="{{ route('docs') }}"
                            class="flex flex-col items-center justify-center gap-0.5 px-2.5 min-h-[3rem] text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->is('docs*') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}"
                            title="{{ __('Documentación') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="hidden lg:block text-[10px] leading-none mt-0.5 text-center">Docs</span>
                        </a>

                        @can('admin')
                            <a href="{{ route('settings.users') }}"
                                class="flex flex-col items-center justify-center gap-0.5 px-2.5 min-h-[3rem] text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->routeIs('settings.users') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}"
                                title="{{ __('navigation.users') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span class="hidden lg:block text-[10px] leading-none mt-0.5 text-center">{{ __('navigation.users') }}</span>
                            </a>

                            <a href="{{ route('settings.mail') }}"
                                class="flex flex-col items-center justify-center gap-0.5 px-2.5 min-h-[3rem] text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->routeIs('settings.mail*') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}"
                                title="{{ __('navigation.settings') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="hidden lg:block text-[10px] leading-none mt-0.5 text-center">{{ __('navigation.settings') }}</span>
                            </a>
                        @endcan
                    </div>

                    <!-- 2. TABLET & MOBILE (sm to lg): Main Menu Dropdown -->
                    <div class="hidden sm:block lg:hidden relative shrink-0" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 px-3 h-11 text-sm font-bold uppercase tracking-tight text-gray-500 hover:text-violet-600 bg-gray-50 dark:bg-gray-800/80 rounded-xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <span>{{ __('Menú') }}</span>
                        </button>
                        <div x-show="open" x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             x-cloak style="display: none;"
                             class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl overflow-hidden z-[90]">
                            @auth
                             <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700">
                                 <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Accesos Rápidos') }}</span>
                             </div>
                             @if(auth()->user()->favoriteTeam)
                              <a href="{{ route('teams.dashboard', auth()->user()->favoriteTeam) }}" class="flex items-center gap-3 px-4 py-3 text-sm text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:text-amber-700 dark:hover:text-amber-300 transition-colors border-b border-gray-100 dark:border-gray-800">
                                  <svg class="h-5 w-5 text-amber-500 fill-amber-500/20" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                                  <span class="font-bold">Escritorio Favorito</span>
                              </a>
                             @endif
                             <a href="{{ route('teams.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-violet-50 dark:hover:bg-violet-500/10 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                 <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                 <span class="font-bold">{{ __('navigation.my_teams') }}</span>
                             </a>
                             <a href="{{ route('global-surveys.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                 <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                                 <span class="font-bold">Encuestas Globales</span>
                             </a>
                             <a href="{{ route('media.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                 <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" /></svg>
                                 <span class="font-bold">{{ __('tasks.disk_quota') }}</span>
                             </a>
                             <a href="{{ route('docs') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                 <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                 <span class="font-bold">Doc</span>
                             </a>
                             @can('admin')
                                 <div class="px-4 py-2 mt-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50 dark:bg-gray-800/80 border-y border-gray-100 dark:border-gray-700">{{ __('Administración') }}</div>
                                 <a href="{{ route('settings.users') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                     <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                     <span class="font-bold">{{ __('navigation.users') }}</span>
                                 </a>
                                 <a href="{{ route('settings.mail') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                     <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                     <span class="font-bold">{{ __('navigation.settings') }}</span>
                                 </a>
                             @endcan

                             {{-- System Preferences for Tablet/Medium screens --}}
                             <div class="px-4 py-2 mt-1 text-[10px] font-black uppercase tracking-widest text-gray-400 bg-gray-50 dark:bg-gray-800/80 border-y border-gray-100 dark:border-gray-700">{{ __('Preferencias') }}</div>
                             <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 bg-white dark:bg-gray-900">
                                 @auth @include('layouts.partials.workday-timer') @endauth
                                 @include('layouts.partials.theme-toggle')
                                 @include('layouts.partials.layout-toggle')
                                 @include('layouts.partials.clean-mode-toggle')
                                 @include('layouts.partials.language-toggle')
                                 @include('layouts.partials.zoom-controls')
                             </div>
                             @endauth
                        </div>
                    </div>
                    <!-- Right Utilities & User Profile (Fixed) -->
                    <div class="flex items-center gap-1 sm:gap-3 shrink-0">

                    @endauth

                    <!-- Utility controls: hidden on mobile, shown on md+ (tablets and desktop) -->
                    <div class="hidden md:flex items-center gap-1 pl-2 ml-1 border-l border-gray-200 dark:border-gray-800">
                        @include('layouts.partials.system-tools')
                    </div>

                    <!-- Mobile: just notifications bell + hamburger -->
                    <div class="flex items-center sm:hidden gap-2 ml-auto">
                        @auth
                        <!-- Chat Notification: Mobile -->
                        <div class="relative inline-flex items-center sm:hidden">
                             <button @click="$dispatch('open-last-chat')"
                                     class="relative p-2 text-gray-400"
                                     title="{{ __('Chat Interno') }}">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                     <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                 </svg>
                                 <template x-if="$store.chatStore.totalCount > 0">
                                      <span class="absolute top-0.5 right-0.5 h-4 w-4 rounded-full bg-emerald-500 text-[9px] font-bold text-white flex items-center justify-center"
                                            x-text="$store.chatStore.totalCount > 9 ? '9+' : $store.chatStore.totalCount">
                                      </span>
                                 </template>
                             </button>
                        </div>

                        <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-400" x-data>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <template x-if="$store.notifications.count > 0">
                                <span class="absolute top-0.5 right-0.5 h-4 w-4 rounded-full bg-red-500 text-[9px] font-bold text-white flex items-center justify-center"
                                      x-text="$store.notifications.count > 9 ? '9+' : $store.notifications.count"></span>
                            </template>
                        </a>
                        @endauth
                        <!-- Hamburger -->
                        <button @click="layout === 'vertical' ? (sidebarOpen = true) : window.dispatchEvent(new CustomEvent('mobile-menu-open'))"
                            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            aria-label="Menu">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>



                    @auth
                        <!-- Chat Notification: Desktop -->
                        <div class="hidden sm:inline-flex relative items-center">
                             <button @click="$dispatch('open-last-chat')"
                                     class="relative p-2 text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors duration-150 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-500/10"
                                     title="{{ __('Chat Interno') }}">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                     <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                 </svg>
                                 <template x-if="$store.chatStore.totalCount > 0">
                                     <span class="absolute top-1 right-1 flex h-4 w-4">
                                         <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                         <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 text-[10px] text-white font-bold items-center justify-center"
                                               x-text="$store.chatStore.totalCount > 9 ? '9+' : $store.chatStore.totalCount">
                                         </span>
                                     </span>
                                 </template>
                             </button>
                        </div>

                        <!-- Notifications Bell: hidden on mobile (in mobile block above) -->
                        <a href="{{ route('notifications.index') }}"
                           class="hidden sm:inline-flex relative p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors duration-150 rounded-xl hover:bg-violet-50 dark:hover:bg-violet-500/10"
                           title="{{ __('Notificaciones') }}"
                           x-data
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <template x-if="$store.notifications.count > 0">
                                <span class="absolute top-1 right-1 flex h-4 w-4">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] text-white font-bold items-center justify-center"
                                          x-text="$store.notifications.count > 99 ? '99+' : $store.notifications.count">
                                    </span>
                                </span>
                            </template>
                        </a>

                        <!-- User menu: hidden on mobile -->
                        <div class="hidden sm:block relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">
                                <img src="{{ auth()->user()->profile_photo_url }}"
                                    alt="{{ auth()->user()->name }}"
                                    class="w-8 h-8 rounded-full object-cover shadow-sm border border-white dark:border-gray-800 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform"
                                    :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition x-cloak style="display: none"
                                class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-visible z-[90]">
                                <div
                                    class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-transparent rounded-t-xl">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                        {{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ __('navigation.profile') }}
                                </a>
                                @if(auth()->check() && auth()->user()->is_admin)
                                <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                                <a href="{{ route('metrics.index') }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors font-bold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    {{ __('Cuadros de Mando') }}
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                                @endif
                                <a href="{{ route('credits') }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    {{ __('credits.title') }}
                                </a>
                                <a href="{{ route('media.index') }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                                    </svg>
                                    {{ __('tasks.disk_quota') }}
                                </a>

                                <!-- Embedded Utilities for Mobile/Small tablets (Hidden when visible in header) -->
                                <div class="hidden sm:flex md:hidden flex-wrap items-center justify-center gap-2 px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 sm:justify-start dark:bg-gray-800/50">
                                    @auth @include('layouts.partials.workday-timer') @endauth
                                    @include('layouts.partials.theme-toggle')
                                    @include('layouts.partials.layout-toggle')
                                    @include('layouts.partials.clean-mode-toggle')
                                    @include('layouts.partials.language-toggle')
                                </div>

                                <div class="border-t border-gray-100 dark:border-gray-700">
                                    <form method="POST" action="{{ route('profile.toggle-privacy-mode') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm {{ $isDemoMode ? 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} transition-colors text-left font-medium">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                @if($isDemoMode)
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                @endif
                                            </svg>
                                            {{ $isDemoMode ? __('Desactivar Privacidad') : __('Modo Privacidad') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left font-medium rounded-b-xl">
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
                    @else
                        <a href="{{ route('login') }}"
                            class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                            {{ __('navigation.login') }}
                        </a>
                        <a href="{{ route('register') }}"
                            class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-4 py-1.5 rounded-lg font-medium transition-all shadow-lg hover:shadow-violet-500/30">
                            {{ __('navigation.register') }}
                        </a>
                    @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- ============================================================
         MOBILE SLIDE-IN DRAWER
         Full navigation panel triggered by hamburger button
         ============================================================ --}}
    @auth
    @php
        $drawerTeamId = null;
        if (request()->route('team')) {
            $drawerTeamId = is_object(request()->route('team'))
                ? request()->route('team')->id
                : request()->route('team');
        }
    @endphp
    {{-- Drawer controlled via custom window event 'mobile-menu-open' --}}
    <div id="mobile-drawer"
         x-data="{ open: false }"
         x-init="
            window.addEventListener('mobile-menu-open', () => open = true);
            window.addEventListener('mobile-menu-close', () => open = false);
         "
         class="sm:hidden">

        {{-- Backdrop --}}
        <div x-show="open"
             x-cloak
             style="display: none"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="open = false"
             class="fixed inset-0 z-[999] bg-black/40 backdrop-blur-sm">
        </div>

        {{-- Drawer panel --}}
        <div x-show="open"
             style="display: none"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 z-[9999] w-72 bg-white dark:bg-gray-900 shadow-2xl flex flex-col overflow-y-auto transform">


            {{-- Drawer header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <span class="font-bold text-gray-900 dark:text-white text-lg" style="font-family:'Space Grotesk',sans-serif">
                    sientia<span class="text-violet-600 dark:text-violet-400">MTX</span>
                </span>
                <button @click="open = false" class="p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- User info --}}
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                <img src="{{ auth()->user()->profile_photo_url }}"
                    alt="{{ auth()->user()->name }}"
                    class="w-10 h-10 rounded-full object-cover shadow border border-white dark:border-gray-700 shrink-0">
                <div class="min-w-0">
                    <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>

            {{-- Navigation links --}}
            <nav class="flex-1 px-3 py-4 space-y-1">

                {{-- Main --}}
                <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Principal</p>
                @if(auth()->user()->favoriteTeam)
                <a href="{{ route('teams.dashboard', auth()->user()->favoriteTeam) }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors bg-amber-50 dark:bg-amber-900/10 text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/20 mb-2 border border-amber-100 dark:border-amber-900/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 fill-amber-500/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                    </svg>
                    Escritorio Favorito
                </a>
                @endif

                <a href="{{ route('teams.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ request()->routeIs('teams.index') ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ __('navigation.my_teams') }}
                </a>
                <a href="{{ route('global-surveys.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ request()->routeIs('global-surveys.*') ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                    Encuestas Globales
                </a>
                <a href="{{ route('notifications.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ request()->routeIs('notifications.*') ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Notificaciones
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="ml-auto bg-red-500 text-white text-[10px] font-bold rounded-full px-2 py-0.5">{{ auth()->user()->unreadNotifications->count() }}</span>
                    @endif
                </a>
                <a href="{{ route('media.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ request()->routeIs('media.index') ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                    </svg>
                    {{ __('tasks.disk_quota') }}
                </a>
                <a href="{{ route('docs') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Documentación
                </a>

                {{-- Team views (if inside a team) --}}
                @if($drawerTeamId)
                <div class="pt-3">
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Vistas del Equipo</p>
                    @php
                        $drawerViews = [
                            ['name' => 'Escritorio', 'route' => route('teams.time-reports', $drawerTeamId), 'active' => request()->routeIs('teams.time-reports')],
                            ['name' => __('forum.title') ?? 'Foro', 'route' => route('teams.forum.index', $drawerTeamId), 'active' => request()->routeIs('teams.forum.*')],
                            ['divider' => true],
                            ['name' => 'Expedientes', 'route' => route('teams.expedientes.index', $drawerTeamId), 'active' => request()->routeIs('teams.expedientes.*')],
                            ['name' => __('navigation.task_list'), 'route' => route('teams.activities.index', $drawerTeamId), 'active' => request()->routeIs('teams.activities.*')],
                            ['name' => __('teams.eisenhower_matrix'), 'route' => route('teams.eisenhower', $drawerTeamId), 'active' => request()->routeIs('teams.eisenhower')],
                            ['name' => __('navigation.gantt'), 'route' => route('teams.gantt', $drawerTeamId), 'active' => request()->routeIs('teams.gantt')],
                            ['name' => __('navigation.kanban'), 'route' => route('teams.kanban', $drawerTeamId), 'active' => request()->routeIs('teams.kanban')],
                            ['divider' => true],
                            ['name' => __('Encuestas del Equipo'), 'route' => route('teams.surveys.index', $drawerTeamId), 'active' => request()->routeIs('teams.surveys.*')],
                        ];
                        if (auth()->user()->hasAppointmentsEnabledForTeam($drawerTeamId)) {
                            $drawerViews[] = [
                                'name' => 'Citas Previas',
                                'route' => route('appointments.index', $drawerTeamId),
                                'active' => request()->routeIs('appointments.*')
                            ];
                        }
                        $drawerViews[] = [
                            'name' => __('teams.view_members'),
                            'route' => route('teams.members', $drawerTeamId),
                            'active' => request()->routeIs('teams.members')
                        ];

                    @endphp
                    @foreach($drawerViews as $dv)
                        @if(isset($dv['divider']))
                            <div class="border-t border-gray-100 dark:border-gray-800 my-1 mx-3"></div>
                        @else
                            <a href="{{ $dv['route'] }}" @click="open = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                                      {{ $dv['active'] ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                                {{ $dv['name'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
                @endif

                @can('admin')
                <div class="pt-3">
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Administración</p>
                    <a href="{{ route('settings.users') }}" @click="open = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        {{ __('navigation.users') }}
                    </a>
                    <a href="{{ route('settings.mail') }}" @click="open = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        {{ __('navigation.settings') }}
                    </a>
                </div>
                @endcan

                {{-- Mobile Utilities --}}
                <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-800">
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Preferencias</p>
                    <div class="flex flex-wrap items-center gap-3 px-3">
                        @auth @include('layouts.partials.workday-timer') @endauth
                        @include('layouts.partials.theme-toggle')
                        @include('layouts.partials.layout-toggle')
                        @include('layouts.partials.clean-mode-toggle')
                        @include('layouts.partials/language-toggle')
                    </div>
                </div>
            </nav>

            {{-- Footer actions --}}
            <div class="px-3 py-4 border-t border-gray-100 dark:border-gray-800 space-y-1">
                <a href="{{ route('profile.edit') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Mi Perfil
                </a>
                @if(auth()->check() && auth()->user()->is_admin)
                <a href="{{ route('metrics.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    {{ __('Cuadros de Mando') }}
                </a>
                @endif
                <form method="POST" action="{{ route('profile.toggle-privacy-mode') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium {{ $isDemoMode ? 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            @if($isDemoMode)
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            @endif
                        </svg>
                        {{ $isDemoMode ? __('Desactivar Privacidad') : __('Modo Privacidad') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        {{ __('navigation.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endauth

