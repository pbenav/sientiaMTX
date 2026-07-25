@props(['user', 'allBadges' => null])

@php
    $allBadges = $allBadges ?? \App\Models\Badge::orderBy('id')->get();
    $userBadgeIds = $user->badges()->pluck('badges.id')->toArray();
@endphp

<div class="bg-white dark:bg-gray-800 rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 dark:border-gray-700">
    <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6 uppercase tracking-wider flex items-center gap-3">
        <span class="text-emerald-500">⭐</span>
        MEDALLAS
        <span class="text-gray-400 text-sm cursor-help ml-1" title="Desbloquea medallas completando objetivos y rachas en la plataforma.">ℹ️</span>
    </h3>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        @foreach($allBadges as $badge)
            @php
                $unlocked = in_array($badge->id, $userBadgeIds);
            @endphp
            <div class="relative group flex flex-col items-center justify-center p-4 rounded-2xl transition-all duration-300 {{ $unlocked ? 'bg-gradient-to-br from-emerald-50 to-emerald-100/50 dark:from-emerald-900/20 dark:to-emerald-800/10 shadow-sm border border-emerald-100 dark:border-emerald-800/30 transform hover:-translate-y-1' : 'bg-gray-50/50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 grayscale opacity-70 hover:opacity-100 hover:grayscale-0' }}">
                
                @if($unlocked)
                    <div class="absolute top-2 right-2 text-emerald-500 text-xs">
                        ✔️
                    </div>
                @endif

                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3 {{ $unlocked ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400' : 'bg-gray-200 text-gray-400 dark:bg-gray-700 dark:text-gray-500' }}">
                    <span class="text-3xl">{!! $badge->icon !!}</span>
                </div>
                
                <h4 class="text-sm font-bold text-center {{ $unlocked ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $badge->name }}
                </h4>
                
                <!-- Tooltip on hover -->
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 p-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-10 text-center pointer-events-none">
                    <p class="font-bold mb-1">{{ $badge->name }}</p>
                    <p class="text-gray-300">{{ $badge->description }}</p>
                    @if(!$unlocked)
                        <div class="mt-2 text-[10px] text-emerald-400 uppercase tracking-widest font-black">
                            🔒 Bloqueada
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
