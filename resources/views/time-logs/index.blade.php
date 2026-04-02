<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.index') }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                        {{ __('tasks.worked_time') }}
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                @include('teams.partials.header-actions')
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-8 mb-4">
            @include('teams.partials.view-switcher')
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Summary Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Task Time -->
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-800">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">{{ __('tasks.active_tasks') }}</p>
                    <h3 class="text-3xl font-black text-violet-600 dark:text-violet-400">
                        {{ count($tasks) }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">{{ __('tasks.tasks_with_time_logged') }}</p>
                </div>

                <!-- Last Workday -->
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-800">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">{{ __('tasks.last_workday') }}</p>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-gray-100">
                        @if($workdayLogs->first())
                            {{ $workdayLogs->first()->start_at->format('H:i') }} 
                            @if($workdayLogs->first()->end_at)
                                - {{ $workdayLogs->first()->end_at->format('H:i') }}
                            @else
                                <span class="text-red-500 animate-pulse text-sm ml-2">{{ __('tasks.last_workday_active') }}</span>
                            @endif
                        @else
                            --:--
                        @endif
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">{{ $workdayLogs->first() ? $workdayLogs->first()->start_at->translatedFormat('d M Y') : __('tasks.no_records') }}</p>
                </div>

                <!-- Global Sync Info -->
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-800">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">{{ __('tasks.sync_status') }}</p>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span class="text-lg font-black text-gray-900 dark:text-gray-100">{{ __('tasks.real_time') }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ __('tasks.sync_hint') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tasks Accounting Table -->
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 dark:border-gray-800 group transition-all duration-300">
                    <div class="p-6 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between">
                        <h4 class="font-black text-gray-900 dark:text-gray-100">{{ __('tasks.task_accounting') }}</h4>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 dark:bg-gray-950/50">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('navigation.task_list') }}</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">{{ __('tasks.total_time') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @forelse($tasks as $task)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $task->title }}</span>
                                                <span class="text-[10px] text-gray-500 uppercase font-black">Q{{ $task->getQuadrant($task) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="inline-flex px-3 py-1 bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 font-mono font-bold rounded-full text-sm">
                                                {{ $task->totalTrackedTimeHuman() }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-6 py-8 text-center text-gray-500 italic">{{ __('tasks.no_task_logs') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Workday History Table -->
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 dark:border-gray-800 group transition-all duration-300">
                    <div class="p-6 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between">
                        <h4 class="font-black text-gray-900 dark:text-gray-100">{{ __('tasks.workday_history') }}</h4>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 dark:bg-gray-950/50">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('tasks.date') }}</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('tasks.entrance_exit') }}</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">{{ __('tasks.total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @forelse($workdayLogs as $log)
                                    @php
                                        $duration = $log->end_at ? $log->start_at->diffInSeconds($log->end_at) : $log->start_at->diffInSeconds(now());
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $log->start_at->translatedFormat('d M Y') }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-mono text-gray-500">
                                            {{ $log->start_at->format('H:i') }} - {{ $log->end_at ? $log->end_at->format('H:i') : '--:--' }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="font-mono font-black {{ $log->end_at ? 'text-gray-900 dark:text-gray-100' : 'text-red-500 animate-pulse' }}">
                                                {{ $hours }}h {{ $minutes }}m
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-500 italic">{{ __('tasks.no_workday_logs') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
