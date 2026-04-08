<x-app-layout>
    @section('title', __('navigation.users'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">{{ __('navigation.users') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Manage global user roles and access.') }}</p>
            </div>
            <div>
                <a href="{{ route('settings.users.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-violet-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Create User') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-4 shadow-sm">
        <div class="max-w-7xl mx-auto">
            @include('settings.partials.tabs')

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
                <!-- Filtros -->
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20">
                    <form method="GET" action="{{ route('settings.users') }}" class="flex flex-wrap items-center gap-3">
                        <div class="relative flex-1 min-w-[200px]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" name="search" value="{{ $search }}"
                                   placeholder="{{ __('Buscar por nombre o email…') }}"
                                   class="w-full pl-9 pr-4 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent outline-none text-gray-900 dark:text-white placeholder-gray-400">
                        </div>
                        <select name="role"
                                class="text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 focus:ring-2 focus:ring-violet-500 outline-none text-gray-700 dark:text-gray-300">
                            <option value="" {{ $role === '' ? 'selected' : '' }}>{{ __('Todos los roles') }}</option>
                            <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>{{ __('Administrator') }}</option>
                            <option value="user" {{ $role === 'user' ? 'selected' : '' }}>{{ __('User') }}</option>
                        </select>

                        <div class="flex items-center gap-2">
                            <button type="submit" class="px-5 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-violet-500/20">
                                {{ __('Filtrar') }}
                            </button>
                            @if($search || $role)
                                <a href="{{ route('settings.users') }}" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-red-500 transition-colors uppercase tracking-widest">
                                    {{ __('Limpiar') }}
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
                                @foreach([
                                    'name' => __('Name'),
                                    'email' => __('Email'),
                                    'is_admin' => __('Role')
                                ] as $field => $label)
                                    <th class="px-6 py-4">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => $field, 'direction' => $sort === $field && $direction === 'asc' ? 'desc' : 'asc']) }}" 
                                           class="flex items-center gap-1.5 text-xs font-black uppercase tracking-widest leading-none {{ $sort === $field ? 'text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400' }} hover:text-violet-500 transition-colors group">
                                            {{ $label }}
                                            <div class="flex flex-col {{ $sort === $field ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }} transition-opacity">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 {{ $sort === $field && $direction === 'asc' ? 'text-violet-600' : 'text-gray-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 -mt-1 {{ $sort === $field && $direction === 'desc' ? 'text-violet-600' : 'text-gray-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </a>
                                    </th>
                                @endforeach
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($users as $user)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-[10px] font-bold text-white">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>
                                            <div class="flex flex-col">
                                                <a href="{{ route('settings.users.edit', $user) }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                                    {{ $user->name }}
                                                </a>
                                                @if($user->invitations_count > 0)
                                                    <span class="inline-flex items-center gap-1 text-[10px] text-amber-500 font-bold uppercase mt-0.5">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                        </svg>
                                                        {{ trans_choice('{1} :count Invitación|[2,*] :count Invitaciones', $user->invitations_count, ['count' => $user->invitations_count]) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($user->is_admin)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-400 border border-violet-200 dark:border-violet-800">
                                                {{ __('Administrator') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                                {{ __('User') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @if ($user->id !== auth()->id())
                                            <form action="{{ route('settings.users.toggle-admin', $user) }}" method="POST" id="toggle-admin-{{ $user->id }}">
                                                @csrf
                                                <button type="button" 
                                                    onclick="confirmToggle({{ $user->id }}, '{{ $user->is_admin ? __('Remove administrator privileges from :name?', ['name' => $user->name]) : __('Grant administrator privileges to :name?', ['name' => $user->name]) }}')"
                                                    class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-all {{ $user->is_admin ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20' : 'text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/20' }}">
                                                    {{ $user->is_admin ? __('Revoke Admin') : __('Make Admin') }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400 italic">{{ __('Current User') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($users->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function confirmToggle(userId, message) {
            Swal.fire({
                title: '{{ __('Manage Roles') }}',
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '{{ __('teams.confirm_ok') }}',
                cancelButtonText: '{{ __('teams.confirm_cancel') }}',
                background: document.documentElement.classList.contains('dark') ? '#111827' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#111827',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('toggle-admin-' + userId).submit();
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
