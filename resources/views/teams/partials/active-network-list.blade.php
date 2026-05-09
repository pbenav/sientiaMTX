@php
    $sortedMembers = $members->sortBy('name')->sortBy(function ($m) {
        $isActiveOrWorking = $m->last_login_at && ($m->isWorking() || ($m->last_activity_at && $m->last_activity_at->greaterThanOrEqualTo(now()->subMinutes(15))));
        return $isActiveOrWorking ? 0 : 1;
    });
@endphp
@foreach($sortedMembers as $member)
    <div class="flex items-center justify-between group p-2 hover:bg-gray-50 dark:hover:bg-gray-800/20 rounded-2xl transition-all duration-300">
        @php
            $isWorking = $member->last_login_at ? $member->isWorking() : false;
            $lastActivity = $member->last_login_at ? $member->last_activity_at : null;
            
            // Check online state (active in last 15 minutes)
            $isOnline = $lastActivity && $lastActivity->greaterThanOrEqualTo(now()->subMinutes(15));
            
            // Check sleeping state (active between 15 and 60 minutes ago)
            $isSleeping = !$isOnline && $lastActivity && $lastActivity->greaterThanOrEqualTo(now()->subMinutes(60));
            
            $hasLocation = !empty($member->location_lat);
            
            $statusColorClass = 'bg-gray-300 dark:bg-gray-700';
            $gradientClass = 'from-gray-200 to-gray-400 opacity-40';
            $textClass = 'text-gray-400';
            $animateClass = '';
            
            // Format times nicely
            $timezone = auth()->user()->timezone ?? 'Europe/Madrid';
            $loginTime = $member->last_login_at ? $member->last_login_at->timezone($timezone)->format('H:i') : 'Desconocido';
            $activityDiff = $lastActivity ? $lastActivity->diffForHumans(null, true) : 'ahora';

            if ($isWorking) {
                $statusColorClass = 'bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.6)]';
                $gradientClass = 'from-rose-400 to-red-600 animate-pulse-subtle';
                $textClass = 'text-rose-600 font-bold';
                $animateClass = 'animate-pulse';
                $statusLabel = 'En labor • Logado: ' . $loginTime;
            } elseif ($isOnline) {
                $statusColorClass = 'bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.4)]';
                $gradientClass = 'from-emerald-400 to-teal-600';
                $textClass = 'text-emerald-600 font-bold';
                $animateClass = 'animate-ping';
                $statusLabel = 'Activo hace ' . $activityDiff . ' • Logado: ' . $loginTime;
            } elseif ($isSleeping) {
                $statusColorClass = 'bg-amber-500 shadow-[0_0_10px_rgba(245,158,11,0.5)]';
                $gradientClass = 'from-amber-400 to-yellow-500 opacity-80';
                $textClass = 'text-amber-600 font-bold';
                $animateClass = 'animate-pulse';
                $statusLabel = '😴 Dormido hace ' . $activityDiff . ' • Logado: ' . $loginTime;
            } else {
                $statusLabel = 'Desconectado • Logado: ' . $loginTime;
            }

            if (!$hasLocation && ($isWorking || $isOnline)) {
                $statusLabel = 'Sin GPS • ' . $statusLabel;
            }
        @endphp
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br {{ $gradientClass }} p-0.5 shadow-sm transition-transform group-hover:scale-105 shrink-0">
                <img src="{{ $member->profile_photo_url }}" 
                    alt="{{ $member->name }}"
                    class="w-full h-full rounded-[14px] object-cover border border-white dark:border-gray-800 shadow-inner">
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-black {{ ($isWorking || $isOnline || $isSleeping) ? 'text-gray-900 dark:text-white' : 'text-gray-400' }} uppercase truncate tracking-tight">{{ $member->name }}</p>
                <p class="text-[9px] {{ $isWorking ? 'text-rose-500 font-bold' : ($isOnline ? 'text-emerald-500 font-bold' : ($isSleeping ? 'text-amber-500 font-bold' : 'text-gray-400')) }} truncate tracking-tight">{{ $statusLabel }}</p>
            </div>
        </div>
        <div class="flex items-center justify-center shrink-0 w-3 h-3 relative">
            @if($animateClass === 'animate-ping')
                <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full {{ $statusColorClass }} opacity-75"></span>
            @endif
            <div class="h-2 w-2 rounded-full {{ $statusColorClass }} {{ $animateClass === 'animate-ping' ? 'relative' : $animateClass }} transition-all duration-500"></div>
        </div>
    </div>
@endforeach
