<x-app-layout>
    @section('title', __('teams.members') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.index') }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ __('teams.members_of', ['name' => $team->name]) }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-8 mb-4 flex w-full">
            @include('teams.partials.view-switcher')
        </div>

        <!-- Action Buttons Row -->
        <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    <div x-data="{ activeTab: '{{ session('tab', request('tab', 'members')) }}' }" class="space-y-6">
        <!-- Tabs -->
        <div class="flex items-center gap-1 border-b border-gray-200 dark:border-gray-800">
            <button @click="activeTab = 'members'"
                :class="activeTab === 'members' ?
                    'text-violet-600 dark:text-violet-400 border-b-2 border-violet-500 bg-violet-50/50 dark:bg-transparent' :
                    'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-6 py-4 text-sm font-bold uppercase tracking-widest transition-all">
                {{ __('teams.members') }}
            </button>
            <button @click="activeTab = 'groups'"
                :class="activeTab === 'groups' ?
                    'text-violet-600 dark:text-violet-400 border-b-2 border-violet-500 bg-violet-50/50 dark:bg-transparent' :
                    'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-6 py-4 text-sm font-bold uppercase tracking-widest transition-all">
                {{ __('tasks.groups') }}
            </button>
        </div>

        <div x-show="activeTab === 'members'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Members list -->
            <div
                class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-transparent flex items-center border-b-0">
                    <h2 class="font-bold text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 heading">
                        {{ __('teams.members') }}
                        ({{ $members->total() }})</h2>
                </div>

                <!-- Filters -->
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20">
                    <form action="{{ route('teams.members', $team) }}" method="GET" class="flex flex-wrap items-center gap-3">
                        <input type="hidden" name="tab" value="members">
                        
                        <!-- Search Input -->
                        <div class="relative flex-1 min-w-[250px] group">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 group-focus-within:text-violet-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}" 
                                placeholder="{{ __('Buscar por nombre o email...') }}"
                                enterkeyhint="search"
                                class="w-full pl-9 pr-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition-all shadow-sm">
                        </div>

                        <!-- Role Filter -->
                        <div class="w-full sm:w-48">
                            <select name="role_id" 
                                class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 px-4 py-2.5 rounded-xl focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 outline-none cursor-pointer transition-all shadow-sm">
                                <option value="">{{ __('Todos los roles') }}</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ __('teams.' . $role->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Action Button -->
                        <div class="flex items-center gap-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-violet-500/20 active:scale-95 group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18m-18 5h18M3 14.5h18M3 19.5h18" />
                                </svg>
                                <span>{{ __('Filtrar') }}</span>
                            </button>

                            @if(request()->anyFilled(['search', 'role_id']))
                                <a href="{{ route('teams.members', $team) }}?tab=members" class="p-2.5 text-gray-400 hover:text-red-500 transition-colors" title="{{ __('Limpiar filtros') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                @forelse($members as $member)
                    <div
                        class="px-5 py-4 border-b border-gray-100 dark:border-gray-800/60 last:border-0 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                        <img src="{{ $member->profile_photo_url }}" alt="{{ $member->name }}" 
                            class="w-10 h-10 rounded-full object-cover shrink-0 shadow-sm border border-gray-100 dark:border-gray-800">
                        <div class="flex-1 min-w-0">
                            <button type="button" @click="$dispatch('open-modal', 'member-activity-{{ $member->id }}')" class="group/name text-left block">
                                <p class="text-sm font-bold text-gray-700 dark:text-gray-200 group-hover/name:text-violet-600 dark:group-hover/name:text-violet-400 transition-colors flex items-center gap-1.5">
                                    {{ $member->name }}
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-0 group-hover/name:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </p>
                            </button>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                <p class="text-xs text-gray-500 dark:text-gray-500 truncate">{{ $member->email }}</p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ __('Miembro desde') }}: 
                                    <span class="font-bold">{{ $member->pivot->joined_at ? \Carbon\Carbon::parse($member->pivot->joined_at)->format('d/m/Y') : $member->created_at->format('d/m/Y') }}</span>
                                </p>
                            </div>

                            @if(auth()->user()->isCoordinator($team) || auth()->user()->is_admin)
                                <!-- Connection Data Section -->
                                <div class="mt-2.5 pt-2.5 border-t border-gray-50 dark:border-gray-800/50 flex flex-wrap items-center gap-x-4 gap-y-2">
                                    @php
                                        $activeSessions = $member->sessions->filter(function($s) {
                                            return $s->last_activity > now()->subMinutes(15)->getTimestamp();
                                        });
                                        $hasActive = $activeSessions->isNotEmpty();
                                    @endphp
                                    
                                    <div class="flex items-center gap-1.5">
                                        <div class="flex h-2 w-2 relative">
                                            @if($hasActive)
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                            @else
                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-gray-300 dark:bg-gray-700"></span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider {{ $hasActive ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">
                                            {{ $hasActive ? __('Online') : __('Offline') }}
                                        </span>
                                    </div>

                                    @if($member->last_ip)
                                        <div class="flex items-center gap-1.5 text-[10px] text-gray-400 dark:text-gray-500" title="Última IP detectada">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            <span class="font-mono bg-gray-50 dark:bg-gray-800/50 px-1.5 py-0.5 rounded border border-gray-100 dark:border-gray-700/50">{{ $member->last_ip }}</span>
                                        </div>
                                    @endif

                                    @if($member->last_activity_at)
                                        <div class="flex items-center gap-1.5 text-[10px] text-gray-400 dark:text-gray-500" title="Última actividad">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>{{ $member->last_activity_at->diffForHumans() }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <x-modal name="member-activity-{{ $member->id }}" focusable>
                                <div class="p-6">
                                    <div class="flex items-start gap-4 mb-8">
                                        <img src="{{ $member->profile_photo_url }}" class="w-16 h-16 rounded-2xl object-cover shadow-lg border-2 border-white dark:border-gray-800">
                                        <div>
                                            <h2 class="text-xl font-black text-gray-900 dark:text-white heading leading-none mb-2">{{ $member->name }}</h2>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ $member->email }}</p>
                                            <div class="flex items-center gap-2 mt-3">
                                                @php
                                                    $membership = $member->teams()->where('team_id', $team->id)->first();
                                                    $roleName = $membership && $membership->pivot->role_id ? \DB::table('team_roles')->where('id', $membership->pivot->role_id)->value('name') : 'user';
                                                @endphp
                                                <span class="px-2 py-0.5 bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 text-[10px] font-bold uppercase tracking-widest rounded-lg border border-violet-100 dark:border-violet-800/50">
                                                    {{ __('teams.' . $roleName) }}
                                                </span>
                                                @if($member->isOnline())
                                                    <span class="px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold uppercase tracking-widest rounded-lg border border-emerald-100 dark:border-emerald-900/50">Online</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                                        <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50 text-center">
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tareas</p>
                                            <p class="text-2xl font-black text-gray-900 dark:text-white heading">{{ $member->created_tasks_count }}</p>
                                            <p class="text-[9px] text-gray-500 mt-1 italic">{{ $member->assigned_tasks_count }} completadas</p>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50 text-center">
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Foro</p>
                                            <p class="text-2xl font-black text-gray-900 dark:text-white heading">{{ $member->forum_threads_count + $member->forum_messages_count }}</p>
                                            <p class="text-[9px] text-gray-500 mt-1 italic">{{ $member->forum_threads_count }} hilos / {{ $member->forum_messages_count }} msgs</p>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50 text-center">
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Aportes</p>
                                            <p class="text-2xl font-black text-gray-900 dark:text-white heading">{{ $member->attachments_count }}</p>
                                            <p class="text-[9px] text-gray-500 mt-1 italic">Archivos subidos</p>
                                        </div>
                                        <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50 text-center">
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Kudos</p>
                                            <p class="text-2xl font-black text-violet-600 dark:text-violet-400 heading">{{ $member->received_kudos_count }}</p>
                                            <p class="text-[9px] text-gray-500 mt-1 italic">Recibidos</p>
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                         <div class="flex items-center justify-between p-4 bg-violet-600 rounded-2xl text-white shadow-lg shadow-violet-500/25">
                                            <div>
                                                <p class="text-[10px] font-black uppercase tracking-widest opacity-80">Experiencia Total</p>
                                                <p class="text-2xl font-black heading">{{ number_format($member->experience_points) }} XP</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[10px] font-black uppercase tracking-widest opacity-80">Resiliencia</p>
                                                <p class="text-2xl font-black heading">{{ number_format($member->resilience_points) }} RP</p>
                                            </div>
                                         </div>

                                         @if($member->skills->isNotEmpty())
                                         <div>
                                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                Habilidades en este Equipo
                                            </h3>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($member->skills as $skill)
                                                    <div class="px-3 py-1.5 bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl flex items-center gap-2">
                                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $skill->name }}</span>
                                                        <span class="text-[10px] font-black text-violet-500">Lv.{{ $skill->pivot->level ?? 1 }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                         </div>
                                         @endif
                                    </div>

                                    <div class="mt-8 flex justify-end">
                                        <x-secondary-button x-on:click="$dispatch('close')">Cerrar Perfil</x-secondary-button>
                                    </div>
                                </div>
                            </x-modal>
                        </div>
                        <div class="flex items-center gap-4 justify-end min-w-[200px]">
                            @can('manageMembers', $team)
                                <form method="POST" action="{{ route('teams.updateMemberRole', [$team, $member]) }}"
                                    class="shrink-0">
                                    @csrf @method('PATCH')
                                    <select name="role_id" onchange="this.form.submit()"
                                        @if($member->id === $team->created_by_id) disabled @endif
                                        class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-[10px] font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 px-3 py-2 rounded-lg focus:border-violet-500 outline-none @if($member->id !== $team->created_by_id) cursor-pointer hover:bg-white dark:hover:bg-gray-700 @else opacity-75 cursor-not-allowed @endif transition-all min-w-[110px]">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}"
                                                {{ ($member->pivot->role_id ?? null) == $role->id ? 'selected' : '' }}>
                                                {{ __('teams.' . $role->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>

                                <div class="flex items-center gap-1 w-[48px] justify-center">
                                    @if ($member->id !== $team->created_by_id)
                                        @if(auth()->user()->is_admin)
                                        <button type="button" x-data=""
                                            x-on:click="$dispatch('open-modal', 'edit-member-{{ $member->id }}')"
                                            class="text-gray-400 hover:text-violet-500 transition-colors p-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        @endif

                                        <form id="remove-member-{{ $member->id }}" method="POST"
                                            action="{{ route('teams.removeMember', [$team, $member]) }}"
                                            onsubmit="event.preventDefault(); confirmDelete('remove-member-{{ $member->id }}', '{{ __('teams.delete_confirm') }}')"
                                            class="shrink-0">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="text-gray-400 hover:text-red-400 transition-colors p-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endcan

                            <x-modal name="edit-member-{{ $member->id }}" focusable>
                                <form method="post" action="{{ route('teams.updateMemberInfo', [$team, $member]) }}"
                                    class="p-6">
                                    @csrf
                                    @method('patch')

                                    <h2 class="text-lg font-medium text-gray-900 dark:text-white heading">
                                        {{ __('teams.edit_member') }}
                                    </h2>

                                    <div class="mt-6 space-y-4">
                                        <div>
                                            <x-input-label for="name_{{ $member->id }}" :value="__('teams.name')" />
                                            <x-text-input id="name_{{ $member->id }}" name="name" type="text"
                                                class="mt-1 block w-full" :value="$member->name" required />
                                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                        </div>

                                        <div>
                                            <x-input-label for="email_{{ $member->id }}" :value="__('teams.email')" />
                                            <x-text-input id="email_{{ $member->id }}" name="email" type="email"
                                                class="mt-1 block w-full" :value="$member->email" required />
                                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                        </div>
                                    </div>

                                    <div class="mt-6 flex justify-end gap-3">
                                        <x-secondary-button x-on:click="$dispatch('close')">
                                            {{ __('teams.cancel') }}
                                        </x-secondary-button>

                                        <x-primary-button>
                                            {{ __('teams.save_changes') }}
                                        </x-primary-button>
                                    </div>
                                </form>
                            </x-modal>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-gray-400 dark:text-gray-500 text-sm italic font-medium">
                        {{ __('teams.no_members') }}</div>
                @endforelse
                <div class="px-5 py-4">{{ $members->links() }}</div>

                @if ($invitations->isNotEmpty())
                    <div
                        class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20">
                        <h2
                            class="font-bold text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-4">
                            {{ __('teams.pending_invitations') }}</h2>
                        <div class="space-y-3">
                            @foreach ($invitations as $invitation)
                                <div
                                    class="flex items-center justify-between gap-4 p-3 bg-white dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002-2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-gray-700 dark:text-gray-200 truncate">
                                                {{ $invitation->email }}</p>
                                            <p class="text-[10px] font-bold uppercase tracking-widest text-violet-500">
                                                {{ __('teams.' . $invitation->role->name) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-[10px] font-bold uppercase tracking-widest px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg">
                                            {{ __('teams.invited') }}
                                        </span>
                                        @can('manageMembers', $team)
                                            <form id="cancel-invitation-{{ $invitation->id }}" method="POST"
                                                action="{{ route('teams.invitations.destroy', [$team, $invitation]) }}"
                                                onsubmit="event.preventDefault(); confirmDelete('cancel-invitation-{{ $invitation->id }}', '{{ __('teams.invitation_cancel_confirm') }}')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="text-gray-400 hover:text-red-500 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar Actions Column -->
            <div class="space-y-6">
                <!-- Add member form -->
                @can('manageMembers', $team)
                    <div
                        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none h-fit transition-colors">
                        <h3 class="font-bold text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 heading mb-6">
                            {{ __('teams.add_member') }}</h3>
                        <form method="POST" action="{{ route('teams.addMember', $team) }}" class="space-y-5">
                            @csrf
                            <div>
                                <label
                                    class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">{{ __('teams.email') }}</label>
                                <input type="email" name="email" required
                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all placeholder-gray-400"
                                    placeholder="user@example.com">
                                @error('email')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">{{ __('teams.role') }}</label>
                                <select name="role_id" required
                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}"
                                            {{ old('role_id', $roles->where('name', 'user')->first()?->id) == $role->id ? 'selected' : '' }}>
                                            {{ __('teams.' . $role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit"
                                class="w-full bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold uppercase tracking-widest py-3 rounded-xl transition-all shadow-lg hover:shadow-violet-500/25">
                                {{ __('teams.add_member') }}
                            </button>
                        </form>
                    </div>

                    <!-- Bulk Invitation Card (Admins Only) -->
                    @if(auth()->user()->is_admin)
                    <div
                        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none h-fit transition-colors">
                        <div class="flex items-center gap-2 mb-6">
                            <div class="p-1.5 bg-violet-500/10 rounded-lg text-violet-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="font-bold text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 heading">
                                Invitación Masiva</h3>
                        </div>
                        
                        <form method="POST" action="{{ route('teams.addMembersBulk', $team) }}" class="space-y-5">
                            @csrf
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">Lista de Emails</label>
                                <textarea name="emails_block" required rows="6"
                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-3 text-xs text-gray-900 dark:text-white outline-none transition-all placeholder-gray-400 font-mono"
                                    placeholder="Pega aquí los emails separados por comas, espacios o saltos de línea..."></textarea>
                                <p class="mt-2 text-[9px] text-gray-400 italic">El sistema extraerá automáticamente los correos válidos del texto.</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">Rol para todos</label>
                                <select name="role_id" required
                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}"
                                            {{ old('role_id', $roles->where('name', 'user')->first()?->id) == $role->id ? 'selected' : '' }}>
                                            {{ __('teams.' . $role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit"
                                class="w-full bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-violet-600 dark:hover:bg-violet-500 hover:text-white text-sm font-bold uppercase tracking-widest py-3 rounded-xl transition-all shadow-lg hover:shadow-violet-500/25">
                                Procesar Lista
                            </button>
                        </form>
                    </div>
                    @endif
                @endcan
            </div>
        </div>

        <div x-show="activeTab === 'groups'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Groups list -->
            <div class="lg:col-span-2 space-y-6">
                @forelse($groups as $group)
                    <div
                        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden p-6 shadow-sm dark:shadow-none transition-colors">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-gray-900 dark:text-white font-bold flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    {{ $group->name }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1 font-medium">
                                    {{ $group->description }}</p>
                            </div>
                            @can('manageMembers', $team)
                                <div class="flex items-center gap-2">
                                    <button @click="$dispatch('open-modal', 'edit-group-{{ $group->id }}')"
                                        class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <form id="delete-group-{{ $group->id }}" method="POST"
                                        action="{{ route('teams.groups.destroy', [$team, $group]) }}"
                                        onsubmit="event.preventDefault(); confirmDelete('delete-group-{{ $group->id }}', '{{ __('teams.delete_group_confirm') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-gray-500 hover:text-red-400 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endcan
                        </div>

                        <!-- Members in group -->
                        <div class="flex flex-wrap gap-2 mb-6">
                            @foreach ($group->users as $u)
                                <div
                                    class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-lg flex items-center gap-2 border border-gray-200 dark:border-gray-700 shadow-sm transition-colors">
                                    <div class="w-1 h-1 rounded-full bg-violet-400"></div>
                                    {{ $u->name }}
                                    @can('manageMembers', $team)
                                        <form method="POST"
                                            action="{{ route('teams.groups.removeMember', [$team, $group, $u]) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="text-gray-400 hover:text-red-500 transition-colors ml-1 font-bold text-sm leading-none">×</button>
                                        </form>
                                    @endcan
                                </div>
                            @endforeach
                        </div>

                        @can('manageMembers', $team)
                            <form method="POST" action="{{ route('teams.groups.addMember', [$team, $group]) }}"
                                class="flex gap-2 p-1 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
                                @csrf
                                <select name="user_id"
                                    class="flex-1 bg-transparent border-0 focus:ring-0 text-xs text-gray-600 dark:text-white outline-none cursor-pointer">
                                    <option value="" class="dark:bg-gray-900">
                                        {{ __('tasks.add_member_to_group') }}...</option>
                                    @foreach ($allMembers as $m)
                                        @if (!$group->users->contains($m->id))
                                            <option value="{{ $m->id }}" class="dark:bg-gray-900">
                                                {{ $m->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <button type="submit"
                                    class="bg-white dark:bg-gray-700 hover:bg-violet-600 hover:text-white dark:hover:bg-violet-600 text-gray-600 dark:text-white px-4 py-2 rounded-lg text-sm font-bold border border-gray-200 dark:border-gray-600 transition-all shadow-sm">
                                    +
                                </button>
                            </form>
                        @endcan
                    </div>

                    <!-- Edit Group Modal -->
                    <x-modal name="edit-group-{{ $group->id }}" focusable>
                        <form method="post" action="{{ route('teams.groups.update', [$team, $group]) }}"
                            class="p-6">
                            @csrf @method('patch')
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white heading">
                                {{ __('teams.edit_group') }}</h2>
                            <div class="mt-6 space-y-4">
                                <div>
                                    <x-input-label for="group_name_{{ $group->id }}" :value="__('teams.name')" />
                                    <x-text-input id="group_name_{{ $group->id }}" name="name" type="text"
                                        class="mt-1 block w-full" :value="$group->name" required />
                                </div>
                                <div>
                                    <x-input-label for="group_desc_{{ $group->id }}" :value="__('teams.description')" />
                                    <textarea name="description"
                                        class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white text-sm px-3 py-2 outline-none focus:border-violet-500 transition-all">{{ $group->description }}</textarea>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <x-secondary-button
                                    x-on:click="$dispatch('close')">{{ __('teams.cancel') }}</x-secondary-button>
                                <x-primary-button>{{ __('teams.save_changes') }}</x-primary-button>
                            </div>
                        </form>
                    </x-modal>
                @empty
                    <div class="py-12 text-center text-gray-400 dark:text-gray-500 text-sm italic font-medium">
                        {{ __('teams.no_groups') }}</div>
                @endforelse
            </div>

            <!-- Create group form -->
            @can('manageMembers', $team)
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none h-fit transition-colors">
                    <h3 class="font-bold text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 heading mb-6">
                        {{ __('tasks.create_group') }}</h3>
                    <form method="POST" action="{{ route('teams.groups.store', $team) }}" class="space-y-5">
                        @csrf
                        <div>
                            <label
                                class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">{{ __('tasks.name') }}</label>
                            <input type="text" name="name" required
                                class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none placeholder-gray-400 transition-all"
                                placeholder="{{ __('tasks.name') }}...">
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">{{ __('tasks.description') }}</label>
                            <textarea name="description"
                                class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none placeholder-gray-400 resize-none transition-all"
                                placeholder="{{ __('tasks.description') }}..."></textarea>
                        </div>
                        <button type="submit"
                            class="w-full bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold uppercase tracking-widest py-3 rounded-xl transition-all shadow-lg hover:shadow-violet-500/25">
                            {{ __('tasks.create_group') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
