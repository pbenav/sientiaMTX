<x-app-layout>
    @section('title', __('teams.members') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.dashboard', $team) }}" class="text-gray-500 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl font-bold text-white heading">{{ __('teams.members') }} — {{ $team->name }}</h1>
            </div>
        </div>
    </x-slot>

    <div x-data="{ activeTab: '{{ session('tab', request('tab', 'members')) }}' }" class="space-y-6">
        <!-- Tabs -->
        <div class="flex items-center gap-1 border-b border-gray-800">
            <button @click="activeTab = 'members'"
                :class="activeTab === 'members' ? 'text-violet-400 border-b-2 border-violet-500' :
                    'text-gray-500 hover:text-gray-300'"
                class="px-5 py-3 text-sm font-medium transition-all">
                {{ __('teams.members') }}
            </button>
            <button @click="activeTab = 'groups'"
                :class="activeTab === 'groups' ? 'text-violet-400 border-b-2 border-violet-500' :
                    'text-gray-500 hover:text-gray-300'"
                class="px-5 py-3 text-sm font-medium transition-all">
                {{ __('Groups') }}
            </button>
        </div>

        <div x-show="activeTab === 'members'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Members list -->
            <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-800">
                    <h2 class="font-semibold text-sm text-gray-300 heading">{{ __('teams.members') }}
                        ({{ $members->total() }})</h2>
                </div>
                @forelse($members as $member)
                    <div class="px-5 py-3.5 border-b border-gray-800/60 last:border-0 flex items-center gap-4">
                        <div
                            class="w-9 h-9 rounded-full bg-gradient-to-br from-violet-600 to-indigo-700 flex items-center justify-center text-xs font-bold text-white shrink-0">
                            {{ strtoupper(substr($member->name, 0, 2)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-200 truncate">{{ $member->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $member->email }}</p>
                        </div>
                        @can('manageMembers', $team)
                            <form method="POST" action="{{ route('teams.updateMemberRole', [$team, $member]) }}"
                                class="shrink-0">
                                @csrf @method('PATCH')
                                <select name="role_id" onchange="this.form.submit()"
                                    class="bg-gray-800 border border-gray-700 text-xs text-gray-300 px-2 py-1.5 rounded-lg focus:border-violet-500 outline-none cursor-pointer">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}"
                                            {{ ($member->pivot->role_id ?? null) == $role->id ? 'selected' : '' }}>
                                            {{ ucfirst($role->name) }}
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

                                    <form method="POST" action="{{ route('teams.removeMember', [$team, $member]) }}"
                                        onsubmit="return confirm('{{ __('teams.delete_confirm') }}')" class="shrink-0">
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

                                        <h2 class="text-lg font-medium text-white heading">
                                            {{ __('Edit Member Info') }}
                                        </h2>

                                        <div class="mt-6 space-y-4">
                                            <div>
                                                <x-input-label for="name_{{ $member->id }}" :value="__('Name')" />
                                                <x-text-input id="name_{{ $member->id }}" name="name" type="text"
                                                    class="mt-1 block w-full" :value="$member->name" required />
                                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                            </div>

                                            <div>
                                                <x-input-label for="email_{{ $member->id }}" :value="__('Email')" />
                                                <x-text-input id="email_{{ $member->id }}" name="email" type="email"
                                                    class="mt-1 block w-full" :value="$member->email" required />
                                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                            </div>
                                        </div>

                                        <div class="mt-6 flex justify-end gap-3">
                                            <x-secondary-button x-on:click="$dispatch('close')">
                                                {{ __('Cancel') }}
                                            </x-secondary-button>

                                            <x-primary-button>
                                                {{ __('Save Changes') }}
                                            </x-primary-button>
                                        </div>
                                    </form>
                                </x-modal>
                            @endif
                        @endcan
                    </div>
                @empty
                    <div class="py-10 text-center text-gray-500 text-sm">{{ __('teams.no_tasks') }}</div>
                @endforelse
                <div class="px-5 py-4">{{ $members->links() }}</div>
            </div>

            <!-- Add member form -->
            @can('manageMembers', $team)
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 h-fit">
                    <h3 class="font-semibold text-sm text-gray-300 heading mb-4">{{ __('teams.add_member') }}</h3>
                    <form method="POST" action="{{ route('teams.addMember', $team) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">{{ __('teams.email') }}</label>
                            <input type="email" name="email" required
                                class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-3 py-2.5 text-sm text-white outline-none transition-all"
                                placeholder="user@example.com">
                            @error('email')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">{{ __('teams.role') }}</label>
                            <select name="role_id" required
                                class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-white outline-none transition-all">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit"
                            class="w-full bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium py-2.5 rounded-xl transition-all">
                            {{ __('teams.add_member') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <div x-show="activeTab === 'groups'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Groups list -->
            <div class="lg:col-span-2 space-y-4">
                @forelse($groups as $group)
                    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-white font-semibold flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    {{ $group->name }}
                                </h3>
                                <p class="text-xs text-gray-500">{{ $group->description }}</p>
                            </div>
                            @can('manageMembers', $team)
                                <div class="flex items-center gap-2">
                                    <button @click="$dispatch('open-modal', 'edit-group-{{ $group->id }}')"
                                        class="text-gray-500 hover:text-white transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <form method="POST" action="{{ route('teams.groups.destroy', [$team, $group]) }}"
                                        onsubmit="return confirm('¿Eliminar grupo?')">
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
                        <div class="flex flex-wrap gap-2 mb-4">
                            @foreach ($group->users as $u)
                                <div
                                    class="bg-gray-800 text-gray-300 text-[10px] px-2 py-1 rounded flex items-center gap-1.5 border border-gray-700">
                                    {{ $u->name }}
                                    @can('manageMembers', $team)
                                        <form method="POST"
                                            action="{{ route('teams.groups.removeMember', [$team, $group, $u]) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-500 hover:text-red-400">×</button>
                                        </form>
                                    @endcan
                                </div>
                            @endforeach
                        </div>

                        @can('manageMembers', $team)
                            <form method="POST" action="{{ route('teams.groups.addMember', [$team, $group]) }}"
                                class="flex gap-2">
                                @csrf
                                <select name="user_id"
                                    class="flex-1 bg-gray-800 border border-gray-700 focus:border-violet-500 rounded-lg px-2 py-1.5 text-xs text-white outline-none">
                                    <option value="">{{ __('Add member to group...') }}</option>
                                    @foreach ($members as $m)
                                        @if (!$group->users->contains($m->id))
                                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <button type="submit"
                                    class="bg-gray-800 hover:bg-gray-700 text-white px-3 py-1.5 rounded-lg text-[10px] border border-gray-700 transition-all">
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
                            <h2 class="text-lg font-medium text-white heading">{{ __('Edit Group') }}</h2>
                            <div class="mt-6 space-y-4">
                                <div>
                                    <x-input-label for="group_name_{{ $group->id }}" :value="__('Name')" />
                                    <x-text-input id="group_name_{{ $group->id }}" name="name" type="text"
                                        class="mt-1 block w-full" :value="$group->name" required />
                                </div>
                                <div>
                                    <x-input-label for="group_desc_{{ $group->id }}" :value="__('Description')" />
                                    <textarea name="description"
                                        class="mt-1 block w-full bg-gray-800 border border-gray-700 rounded-xl text-white text-sm px-3 py-2 outline-none focus:border-violet-500">{{ $group->description }}</textarea>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <x-secondary-button
                                    x-on:click="$dispatch('close')">{{ __('Cancel') }}</x-secondary-button>
                                <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                            </div>
                        </form>
                    </x-modal>
                @empty
                    <div class="py-10 text-center text-gray-500 text-sm">{{ __('No groups defined yet.') }}</div>
                @endforelse
            </div>

            <!-- Create group form -->
            @can('manageMembers', $team)
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5 h-fit">
                    <h3 class="font-semibold text-sm text-gray-300 heading mb-4">{{ __('Create Group') }}</h3>
                    <form method="POST" action="{{ route('teams.groups.store', $team) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">{{ __('Name') }}</label>
                            <input type="text" name="name" required
                                class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-white outline-none"
                                placeholder="e.g. Development Team">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1.5">{{ __('Description') }}</label>
                            <textarea name="description"
                                class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-white outline-none"
                                placeholder="Short description..."></textarea>
                        </div>
                        <button type="submit"
                            class="w-full bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium py-2.5 rounded-xl transition-all">
                            {{ __('Create Group') }}
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
