@php
    // Define status priority for sorting
    $statusPriority = [
        'working' => 1,
        'online' => 2,
        'sleeping' => 3,
        'offline' => 4
    ];

    $sortedMembers = $members->map(function($m) use ($statusPriority) {
        $statusInfo = $m->getStatusInfo();
        $m->status_info = $statusInfo;
        $m->status_priority = $statusPriority[$statusInfo['status']] ?? 99;
        return $m;
    })->sort(function($a, $b) {
        if ($a->status_priority === $b->status_priority) {
            return strcasecmp($a->name, $b->name);
        }
        return $a->status_priority <=> $b->status_priority;
    });
@endphp
@foreach($sortedMembers as $member)
    @php
            $status = $member->status_info;
            $hasLocation = !empty($member->location_lat);
            
            $statusColorClass = $status['dot_class'];
            $textClass = 'text-' . $status['color'];
            $animateClass = $status['animate'];
            $statusLabel = $status['label'];

            // Additional activity details
            $timezone = auth()->user()->timezone ?? 'Europe/Madrid';
            $loginTime = $member->last_login_at ? $member->last_login_at->timezone($timezone)->format('H:i') : 'Desconocido';
            $lastActivity = $member->last_login_at ? $member->last_activity_at : null;
            $activityDiff = $lastActivity ? $lastActivity->diffForHumans(null, true) : 'N/A';
            
            if ($status['status'] !== 'offline') {
                $statusLabel .= ' hace ' . $activityDiff;
            }
            $statusLabel .= ' • Logado: ' . $loginTime;

            if (!$hasLocation && ($status['status'] === 'working' || $status['status'] === 'online')) {
                $statusLabel = 'Sin GPS • ' . $statusLabel;
            }

            // Custom gradient for avatar border
            $gradientClass = match($status['status']) {
                'working' => 'from-rose-400 to-red-600 animate-pulse-subtle',
                'online' => 'from-emerald-400 to-teal-600',
                'sleeping' => 'from-amber-400 to-yellow-500 opacity-80',
                default => 'from-gray-200 to-gray-400 opacity-40',
            };
        @endphp
        @php
            $isSelf = $member->id === auth()->id();
            $clickAction = $isSelf ? '' : '@click="groupMode ? (selectedUsers.includes('.$member->id.') ? selectedUsers = selectedUsers.filter(id => id !== '.$member->id.') : selectedUsers.push('.$member->id.')) : $dispatch(\'open-chat\', { id: ' . $member->id . ', name: \'' . addslashes($member->name) . '\', photo: \'' . $member->profile_photo_url . '\', status: \'' . addslashes($statusLabel) . '\', email: \'' . $member->email . '\', telegram: \'' . ($member->telegram_chat_id ?? '') . '\' })"';
            $itemClasses = $isSelf 
                ? 'flex items-center justify-between group p-2 rounded-2xl opacity-80 transition-all duration-300 cursor-default w-full'
                : 'flex items-center justify-between group p-2 hover:bg-gray-50 dark:hover:bg-gray-800/20 rounded-2xl transition-all duration-300 cursor-pointer w-full relative';
        @endphp

        <div {!! $clickAction !!}
             :class="selectedUsers.includes({{ $member->id }}) ? '{{ $itemClasses }} bg-emerald-50 dark:bg-emerald-900/20 ring-1 ring-emerald-500' : '{{ $itemClasses }} !hover:translate-x-1'">
            <div class="flex items-center gap-3 min-w-0">
                <!-- Checkbox for group mode -->
                @if(!$isSelf)
                <div x-show="groupMode" class="shrink-0 transition-all" x-cloak>
                    <div class="w-4 h-4 rounded border flex items-center justify-center transition-colors"
                         :class="selectedUsers.includes({{ $member->id }}) ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'">
                        <svg x-show="selectedUsers.includes({{ $member->id }})" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                </div>
                @endif
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
                <p class="text-[11px] font-black {{ ($status['status'] !== 'offline') ? 'text-gray-900 dark:text-white' : 'text-gray-400' }} uppercase tracking-tight">
                    {{ $member->name }} @if($isSelf) <span class="text-indigo-500 dark:text-indigo-400 lowercase italic">(tú)</span> @endif
                </p>
                <p class="text-[9px] {{ $textClass }} tracking-tight">{{ $statusLabel }}</p>
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

<div class="hidden" x-init="$nextTick(() => { if (typeof activeMemberIds !== 'undefined') { activeMemberIds = [{{ $sortedMembers->where('status_priority', '<', 4)->pluck('id')->join(',') }}]; } })"></div>
