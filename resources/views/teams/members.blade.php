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
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                        {{ __('teams.members_of', ['name' => $team->name]) }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-4 mb-2 flex w-full">
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
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-transparent">
                    <h2 class="font-bold text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 heading">
                        {{ __('teams.members') }}
                        ({{ $members->total() }})</h2>
                </div>
                @forelse($members as $member)
                    <div
                        class="px-5 py-4 border-b border-gray-100 dark:border-gray-800/60 last:border-0 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                        <div
                            class="w-10 h-10 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white shrink-0 shadow-sm">
                            {{ strtoupper(substr($member->name, 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-700 dark:text-gray-200 truncate">{{ $member->name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500 truncate">{{ $member->email }}</p>
                        </div>
                        @can('manageMembers', $team)
                            <form method="POST" action="{{ route('teams.updateMemberRole', [$team, $member]) }}"
                                class="shrink-0">
                                @csrf @method('PATCH')
                                <select name="role_id" onchange="this.form.submit()"
                                    class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-[10px] font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 px-3 py-2 rounded-lg focus:border-violet-500 outline-none cursor-pointer hover:bg-white dark:hover:bg-gray-700 transition-all">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}"
                                            {{ ($member->pivot->role_id ?? null) == $role->id ? 'selected' : '' }}>
                                            {{ __('teams.' . $role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                            @if ($member->id !== $team->created_by_id)
                                <div class="flex items-center gap-1">
                                    <button type="button" x-data=""
                                        x-on:click="$dispatch('open-modal', 'edit-member-{{ $member->id }}')"
                                        class="text-gray-600 hover:text-violet-400 transition-colors p-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>

                                    <form id="remove-member-{{ $member->id }}" method="POST"
                                        action="{{ route('teams.removeMember', [$team, $member]) }}"
                                        onsubmit="event.preventDefault(); confirmDelete('remove-member-{{ $member->id }}', '{{ __('teams.delete_confirm') }}')"
                                        class="shrink-0">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="text-gray-600 hover:text-red-400 transition-colors p-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>

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
                            @endif
                        @endcan
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
            @endcan
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
                                    @foreach ($members as $m)
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
