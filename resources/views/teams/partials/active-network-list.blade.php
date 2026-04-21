@foreach($members as $member)
    <div class="flex items-center justify-between group">
        @php
            $isWorking = $member->isWorking();
            $isOnline = $member->isOnline();
            
            $statusColorClass = 'bg-gray-200 dark:bg-gray-800';
            $gradientClass = 'from-gray-200 to-gray-400 opacity-50';
            $textClass = 'text-gray-400';
            $animateClass = '';
            
            if ($isWorking) {
                $statusColorClass = 'bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.6)]';
                $gradientClass = 'from-rose-400 to-red-600 animate-pulse-subtle';
                $textClass = 'text-rose-600';
                $animateClass = 'animate-pulse';
            } elseif ($isOnline) {
                $statusColorClass = 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.4)]';
                $gradientClass = 'from-emerald-400 to-teal-600';
                $textClass = 'text-emerald-600';
            }
        @endphp
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br {{ $gradientClass }} p-0.5 shadow-sm transition-transform group-hover:scale-105">
                <div class="w-full h-full rounded-[10px] bg-white dark:bg-gray-800 flex items-center justify-center text-[10px] font-black {{ $textClass }} uppercase">
                    {{ substr($member->name, 0, 2) }}
                </div>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-black {{ ($isWorking || $isOnline) ? 'text-gray-900 dark:text-white' : 'text-gray-400' }} uppercase truncate">{{ $member->name }}</p>
                <p class="text-[9px] {{ $isWorking ? 'text-rose-500 font-bold' : ($isOnline ? 'text-emerald-500 font-bold' : 'text-gray-400') }} truncate tracking-tight">{{ $member->working_area_name ?? 'Zona Sin Nombre' }}</p>
            </div>
        </div>
        <div class="h-1.5 w-1.5 rounded-full {{ $statusColorClass }} {{ $animateClass }} transition-all duration-500"></div>
    </div>
@endforeach
