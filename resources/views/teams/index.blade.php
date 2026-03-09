<x-app-layout>
    @section('title', __('teams.my_teams'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white heading">{{ __('teams.my_teams') }}</h1>
                <p class="text-sm text-gray-400 mt-0.5">{{ __('teams.title') }}</p>
            </div>
            <a href="{{ route('teams.create') }}"
                class="flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium px-4 py-2 rounded-xl transition-all shadow-lg hover:shadow-violet-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('teams.create') }}
            </a>
        </div>
    </x-slot>

    @if ($teams->isEmpty())
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="w-20 h-20 rounded-2xl bg-gray-800 flex items-center justify-center mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-600" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-300 heading mb-2">{{ __('teams.no_teams') }}</h2>
            <p class="text-gray-500 text-sm max-w-sm mb-6">{{ __('teams.create_first') }}</p>
            <a href="{{ route('teams.create') }}"
                class="bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium px-6 py-2.5 rounded-xl transition-all">
                {{ __('teams.create') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($teams as $team)
                @php
                    $total = $team->tasks()->count();
                    $done = $team->tasks()->where('status', 'completed')->count();
                    $progress = $total > 0 ? round(($done / $total) * 100) : 0;
                @endphp
                <div
                    class="group bg-gray-900 border border-gray-800 hover:border-violet-800 rounded-2xl p-5 flex flex-col gap-4 transition-all hover:shadow-xl hover:shadow-violet-500/10">
                    <div class="flex items-start justify-between">
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-indigo-700 flex items-center justify-center text-white font-bold text-sm">
                            {{ strtoupper(substr($team->name, 0, 2)) }}
                        </div>
                        <span class="text-xs text-gray-500 bg-gray-800 px-2 py-1 rounded-full">
                            {{ __('teams.members_count', ['count' => $team->members->count()]) }}
                        </span>
                    </div>
                    <div>
                        <h3
                            class="text-base font-semibold text-white heading group-hover:text-violet-300 transition-colors">
                            {{ $team->name }}</h3>
                        @if ($team->description)
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $team->description }}</p>
                        @endif
                    </div>
                    <!-- Progress bar -->
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                            <span>{{ __('teams.tasks_count', ['count' => $total]) }}</span>
                            <span>{{ $progress }}%</span>
                        </div>
                        <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-violet-500 to-indigo-500 rounded-full transition-all"
                                style="width: {{ $progress }}%"></div>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <a href="{{ route('teams.dashboard', $team) }}"
                            class="flex-1 text-center text-xs font-medium bg-violet-600/20 hover:bg-violet-600/40 text-violet-300 py-2 rounded-lg transition-all border border-violet-700/30">
                            {{ __('teams.view_dashboard') }}
                        </a>
                        <a href="{{ route('teams.show', $team) }}"
                            class="flex-1 text-center text-xs font-medium bg-gray-800 hover:bg-gray-700 text-gray-300 py-2 rounded-lg transition-all border border-gray-700">
                            {{ __('teams.tasks') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-8">{{ $teams->links() }}</div>
    @endif
</x-app-layout>
