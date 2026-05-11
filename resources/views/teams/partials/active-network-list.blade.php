@php
    $activeMembers = collect();
    $inactiveMembers = collect();

    foreach ($members as $m) {
        $isWorking = $m->last_login_at ? $m->isWorking() : false;
        $lastActivity = $m->last_login_at ? $m->last_activity_at : null;
        $isOnline = $lastActivity && $lastActivity->greaterThanOrEqualTo(now()->subMinutes(15));
        
        if (($isWorking || $isOnline) && $m->last_login_at) {
            $activeMembers->push($m);
        } else {
            $inactiveMembers->push($m);
        }
    }

    $sortedMembers = $activeMembers->sortBy('name')->concat($inactiveMembers->sortBy('name'));
@endphp
@foreach($sortedMembers as $member)
    @php
            $isWorking = $member->last_login_at ? $member->isWorking() : false;
            $lastActivity = $member->last_login_at ? $member->last_activity_at : null;
            
            // Check online state (active in last 15 minutes) - REQUIRES lastActivity
            $isOnline = $lastActivity && $lastActivity->greaterThanOrEqualTo(now()->subMinutes(15));
            
            // Check sleeping state (active between 15 and 60 minutes ago) - REQUIRES lastActivity
            $isSleeping = !$isOnline && $lastActivity && $lastActivity->greaterThanOrEqualTo(now()->subMinutes(60));
            
            $hasLocation = !empty($member->location_lat);
            
            $statusColorClass = 'bg-gray-300 dark:bg-gray-700';
            $gradientClass = 'from-gray-200 to-gray-400 opacity-40';
            $textClass = 'text-gray-400';
            $animateClass = '';
            
            // Format times nicely
            $timezone = auth()->user()->timezone ?? 'Europe/Madrid';
            $loginTime = $member->last_login_at ? $member->last_login_at->timezone($timezone)->format('H:i') : 'Desconocido';
            $activityDiff = $lastActivity ? $lastActivity->diffForHumans(null, true) : 'N/A';

            if ($isWorking && $member->last_login_at) {
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
        <div @click="$dispatch('open-chat', { id: {{ $member->id }}, name: '{{ addslashes($member->name) }}', photo: '{{ $member->profile_photo_url }}', status: '{{ addslashes($statusLabel) }}', email: '{{ $member->email }}', telegram: '{{ $member->telegram_chat_id ?? '' }}' })"
             x-data
             class="flex items-center justify-between group p-2 hover:bg-gray-50 dark:hover:bg-gray-800/20 rounded-2xl transition-all duration-300 cursor-pointer hover:translate-x-1 duration-200 w-full">
            <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br {{ $gradientClass }} p-0.5 shadow-sm transition-transform group-hover:scale-105 shrink-0 relative">
                <img src="{{ $member->profile_photo_url }}" 
                    alt="{{ $member->name }}"
                    class="w-full h-full rounded-[14px] object-cover border border-white dark:border-gray-800 shadow-inner">
                
                <!-- Unread Message Badge -->
                <template x-if="$store.chatStore.hasUnread({{ $member->id }})">
                    <span class="absolute -top-1 -right-1 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-rose-500 text-[8px] font-black text-white ring-2 ring-white dark:ring-gray-900 shadow-lg animate-bounce">
                        ✉️
                    </span>
                </template>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-black {{ ($isWorking || $isOnline || $isSleeping) ? 'text-gray-900 dark:text-white' : 'text-gray-400' }} uppercase truncate tracking-tight">{{ $member->name }}</p>
                <p class="text-[9px] {{ $isWorking ? 'text-rose-500 font-bold' : ($isOnline ? 'text-emerald-500 font-bold' : ($isSleeping ? 'text-amber-500 font-bold' : 'text-gray-400')) }} truncate tracking-tight">{{ $statusLabel }}</p>
                @if(auth()->user()->is_admin && $member->last_ip)
                    <div class="flex items-center gap-1 mt-0.5">
                        <svg class="w-2.5 h-2.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 21h6l-.75-4M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <p class="text-[8px] font-mono text-gray-400 dark:text-gray-500 font-semibold select-all">{{ $member->last_ip }}</p>
                    </div>
                @endif
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
