<x-app-layout>
    @section('title', __('navigation.task_list') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.show', $team) }}"
                    class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white heading">
                        {{ __('navigation.task_list') }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $team->name }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:border-violet-500 hover:text-violet-600 dark:hover:text-violet-400 px-3 py-2 rounded-xl transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    {{ __('tasks.eisenhower_matrix') }}
                </a>
                <a href="{{ route('teams.tasks.create', $team) }}"
                    class="flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-3 py-2 rounded-xl transition-all font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('tasks.create') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl rounded-2xl overflow-hidden transition-all">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('tasks.name') }}
                        </th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('tasks.status') }}
                        </th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('tasks.priority') }} / {{ __('tasks.urgency') }}
                        </th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('tasks.assigned_to') }}
                        </th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('tasks.due_date') }}
                        </th>
                        <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 text-right">
                            {{ __('tasks.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($tasks as $task)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full {{ $task->status === 'completed' ? 'bg-emerald-500' : ($task->status === 'blocked' ? 'bg-red-500' : 'bg-violet-500') }}"></div>
                                    <div>
                                        <a href="{{ route('teams.tasks.show', [$team, $task]) }}" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                            {{ $task->title }}
                                        </a>
                                        @if($task->is_template)
                                            <span class="ml-2 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-tighter bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 rounded">{{ __('tasks.template') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg border 
                                    @if($task->status === 'completed') bg-emerald-50 border-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400
                                    @elseif($task->status === 'in_progress') bg-blue-50 border-blue-100 text-blue-700 dark:bg-blue-500/10 dark:border-blue-500/20 dark:text-blue-400
                                    @elseif($task->status === 'blocked') bg-red-50 border-red-100 text-red-700 dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-400
                                    @else bg-gray-50 border-gray-100 text-gray-600 dark:bg-gray-500/10 dark:border-gray-500/20 dark:text-gray-400
                                    @endif uppercase">
                                    {{ __("tasks.statuses.{$task->status}") }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ __('tasks.priority') }}: <span class="text-gray-700 dark:text-gray-300">{{ __("tasks.priorities.{$task->priority}") }}</span></span>
                                    <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">{{ __('tasks.urgency') }}: <span class="text-gray-700 dark:text-gray-300">{{ __("tasks.urgencies.{$task->urgency}") }}</span></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($task->assigned_user_id)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-[10px] font-bold text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-800">
                                            {{ strtoupper(substr($task->assignedUser->name, 0, 2)) }}
                                        </div>
                                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ $task->assignedUser->name }}</span>
                                    </div>
                                @else
                                    <span class="text-xs italic text-gray-400">{{ __('tasks.unassigned') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($task->due_date)
                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ $task->due_date->format('d/m/Y') }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-0.5">
                                    <a href="{{ route('teams.tasks.edit', [$team, $task]) }}" 
                                        class="p-1.5 text-gray-500 hover:text-violet-600 dark:text-gray-400 dark:hover:text-violet-400 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
                                        title="{{ __('tasks.edit') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('teams.tasks.destroy', [$team, $task]) }}" method="POST" id="delete-task-{{ $task->id }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" @click="confirmDelete('delete-task-{{ $task->id }}', '{{ __('tasks.confirm_delete') }}')" 
                                            class="p-1.5 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20"
                                            title="{{ __('tasks.delete') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-12 h-12 rounded-2xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center text-gray-400 border border-gray-100 dark:border-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('tasks.no_tasks') }}</p>
                                    <a href="{{ route('teams.tasks.create', $team) }}" class="mt-2 text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                        {{ __('tasks.create') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($tasks->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-transparent">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
