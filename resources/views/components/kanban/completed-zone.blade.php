@props(['completedTasks' => [], 'hideCompleted' => false, 'team' => null])

<div class="mt-16 w-full px-4 pb-16" x-show="{{ $hideCompleted ? 'false' : 'true' }}" x-transition>
    <div class="bg-gray-50/50 dark:bg-gray-950/20 border border-gray-200 dark:border-gray-800/40 rounded-[2.5rem] overflow-hidden shadow-sm dark:shadow-none transition-colors">
            <div class="px-8 py-5 border-b border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-gray-900/10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-500/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-[12px] font-black uppercase tracking-[0.25em] text-gray-900 dark:text-gray-100">
                        {{ __('teams.completed_tasks') }}
                    </h3>
                </div>
                <span class="text-xs font-bold text-gray-400 dark:text-gray-600">{{ count($completedTasks) }}</span>
            </div>

            <div class="min-h-[140px] p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="completed-tasks-zone" data-column-type="done">
                @forelse($completedTasks as $task)
                    <div class="px-4 py-3 flex items-center gap-4 bg-white dark:bg-gray-900/20 hover:bg-gray-100 dark:hover:bg-white/10 group transition-all rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm dark:shadow-none relative overflow-hidden">
                        <div class="w-1.5 h-1.5 rounded-full shrink-0 bg-emerald-500/20 z-10 relative"></div>
                        <span class="flex-1 text-[12px] text-gray-400 dark:text-gray-600 line-through truncate group-hover:text-gray-600 dark:group-hover:text-gray-400 transition-colors cursor-pointer"
                            onclick="window.location.href='{{ route('teams.activities.show', [$team, $task]) }}'">
                            {{ $task->title }}
                        </span>
                        <button onclick="unarchiveTask({{ $task->id }})"
                                class="p-1 rounded-lg opacity-60 group-hover:opacity-100 hover:bg-violet-100 dark:hover:bg-violet-900/40 text-gray-400 hover:text-violet-600 transition-all border border-transparent hover:border-violet-200"
                                title="{{ __('tasks.restore') ?? 'Desarchivar' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                @empty
                    <div class="col-span-full py-20 text-center text-xs text-gray-500 italic">
                        {{ __('No hay tareas completadas archivadas.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
