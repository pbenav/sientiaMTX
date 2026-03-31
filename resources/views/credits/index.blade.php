<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-amber-100 dark:bg-amber-500/20 rounded-lg text-amber-600 dark:text-amber-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('credits.title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('credits.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    {{-- Hero --}}
    <div class="rounded-3xl overflow-hidden mb-10 relative" style="background:linear-gradient(135deg,#4f46e5,#7c3aed,#a855f7)">
        <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 20% 80%, white 1px, transparent 1px),radial-gradient(circle at 80% 20%, white 1px, transparent 1px),radial-gradient(circle at 50% 50%, white 1px, transparent 1px);background-size:30px 30px"></div>
        <div class="relative px-8 py-14 text-center text-white">
            <div class="text-5xl mb-4">🙏</div>
            <h2 class="text-3xl font-bold mb-3" style="font-family:'Space Grotesk',sans-serif">{{ __('credits.hero_title') }}</h2>
            <p class="text-white/80 max-w-2xl mx-auto text-base leading-relaxed">{{ __('credits.hero_desc') }}</p>
            <div class="mt-6 inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-full px-5 py-2 text-sm font-medium">
                <span>🛡️</span>
                <span>{{ __('credits.license_badge') }}</span>
            </div>
        </div>
    </div>

    {{-- Credits Grid --}}
    <div class="space-y-10">
        @foreach($credits as $group)
            <div>
                {{-- Category Header --}}
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shadow-sm" style="background:linear-gradient(135deg,{{collect(explode(' ', $group['color']))->filter(fn($c)=>str_contains($c,'from-'))->map(fn($c)=>str_replace(['from-','text-','-500','-600'],'',$c))->first() ?? 'violet'}})">
                        {{ $group['icon'] }}
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white" style="font-family:'Space Grotesk',sans-serif">
                        {{ $group['category'] }}
                    </h3>
                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                </div>

                {{-- Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($group['items'] as $lib)
                        <a href="{{ $lib['url'] }}" target="_blank" rel="noopener noreferrer"
                           class="group flex flex-col gap-3 p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm hover:shadow-md hover:border-violet-300 dark:hover:border-violet-700 transition-all duration-300 hover:-translate-y-0.5">

                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-gray-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">{{ $lib['name'] }}</span>
                                        @if($lib['version'])
                                            <span class="text-[10px] font-mono font-bold px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 rounded-full">v{{ $lib['version'] }}</span>
                                        @endif
                                    </div>
                                    <span class="inline-block mt-1 text-[10px] font-bold px-2 py-0.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full border border-emerald-200 dark:border-emerald-800">
                                        {{ $lib['license'] }}
                                    </span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-300 dark:text-gray-600 group-hover:text-violet-400 transition-colors shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </div>

                            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ $lib['description'] }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Footer Attribution --}}
    <div class="mt-12 text-center py-10 border-t border-gray-200 dark:border-gray-800">
        <p class="text-3xl mb-3">❤️</p>
        <p class="text-gray-500 dark:text-gray-400 text-sm max-w-lg mx-auto leading-relaxed">
            {{ __('credits.footer_text') }}
        </p>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-600">
            SientiaMTX · Open Source · <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">AGPL-3.0</a>
        </p>
    </div>
</x-app-layout>
