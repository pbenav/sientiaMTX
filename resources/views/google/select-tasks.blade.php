<x-app-layout>
    @section('title', __('google.sync_tasks_title'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white heading">
                    {{ __('google.select_to_import') }}
                </h1>
            </div>
            <div class="text-xs text-gray-400">
                {{ $team->name }}
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-2xl rounded-3xl overflow-hidden transition-all">
            <form action="{{ route('google.import') }}" method="POST">
                @csrf
                <input type="hidden" name="team_id" value="{{ $team->id }}">
                <input type="hidden" name="visibility" value="{{ $visibility }}">

                <div
                    class="px-8 py-6 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-gray-900/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="relative group max-w-md">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-violet-500 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" id="event-search"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-200 dark:border-gray-700 rounded-xl leading-5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 sm:text-xs transition-all"
                                placeholder="{{ __('google.search_placeholder') }}">
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <label for="filter-mode" class="text-[10px] font-bold uppercase text-gray-400">
                                {{ __('google.filter_mode') }}:
                            </label>
                            <select id="filter-mode"
                                class="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-lg text-[10px] font-bold uppercase tracking-tighter text-gray-600 dark:text-gray-400 focus:ring-violet-500 focus:border-violet-500 p-1">
                                <option value="include">{{ __('google.filter_include') }}</option>
                                <option value="exclude">{{ __('google.filter_exclude') }}</option>
                            </select>
                        </div>
                        <div class="h-4 w-px bg-gray-200 dark:bg-gray-700 mx-1"></div>
                        <button type="button" id="select-all"
                            class="text-[10px] font-bold text-violet-600 dark:text-violet-400 uppercase tracking-tighter hover:underline">
                            {{ __('google.select_all') }}
                        </button>
                    </div>
                </div>

                <div class="divide-y divide-gray-50 dark:divide-white/5 max-h-[500px] overflow-y-auto custom-scrollbar">
                    @forelse($events as $event)
                        <div class="event-item group relative px-8 py-5 hover:bg-violet-50/30 dark:hover:bg-violet-500/5 transition-all cursor-pointer"
                            data-title="{{ strtolower($event['title']) }}"
                            data-description="{{ strtolower($event['description'] ?? '') }}"
                            onclick="toggleCheckbox('checkbox-{{ $event['id'] }}')">
                            <div class="flex items-start gap-6">
                                <div class="mt-1">
                                    <input type="checkbox" name="events[]" value="{{ $event['id'] }}"
                                        id="checkbox-{{ $event['id'] }}"
                                        class="w-5 h-5 rounded-lg border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 bg-white dark:bg-gray-800 transition-all pointer-events-none"
                                        {{ $event['exists'] ? 'disabled' : '' }}>
                                    <div class="mt-2 text-center">
                                        @if ($event['type'] === 'calendar')
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 mx-auto text-blue-500" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4 mx-auto text-amber-500" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3
                                            class="text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                                            {{ $event['title'] }}
                                        </h3>
                                        <span class="shrink-0 text-[10px] font-mono font-bold text-gray-400 uppercase">
                                            {{ date('d M, H:i', strtotime($event['start'])) }}
                                        </span>
                                    </div>
                                    @if ($event['description'])
                                        <p class="text-xs text-gray-500 dark:text-gray-500 line-clamp-1 mt-0.5">
                                            {{ $event['description'] }}
                                        </p>
                                    @endif
                                    @if ($event['exists'])
                                        <span
                                            class="inline-flex items-center gap-1 mt-2 text-[9px] font-bold text-emerald-500 uppercase tracking-tighter">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            {{ __('google.already_sync') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-20 text-center">
                            <div
                                class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-300 mb-4 transition-transform hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 italic">
                                {{ __('google.no_events') }}</p>
                        </div>
                    @endforelse
                </div>

                <div
                    class="px-8 py-6 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-white/5 flex items-center justify-end gap-3">
                    <button type="button" 
                        onclick="confirmDelete('google-disconnect-form', '{{ __('google.disconnect_confirm') }}')"
                        class="px-5 py-2.5 text-xs font-bold text-red-500 hover:text-red-700 dark:hover:text-red-400 transition-all uppercase tracking-wider mr-auto">
                        {{ __('google.disconnect') }}
                    </button>
                    <a href="{{ route('teams.dashboard', $team) }}"
                        class="px-5 py-2.5 text-xs font-bold text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-all uppercase tracking-wider">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit"
                        class="px-8 py-2.5 bg-violet-600 hover:bg-violet-500 text-white rounded-2xl shadow-lg shadow-violet-500/20 transition-all font-bold text-xs uppercase tracking-wider active:scale-95 disabled:opacity-50 disabled:pointer-events-none"
                        id="import-btn">
                        {{ __('google.import_selected') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <form id="google-disconnect-form" action="{{ route('google.disconnect') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="team_id" value="{{ $team->id }}">
    </form>

    @push('scripts')
        <script>
            function toggleCheckbox(id) {
                const cb = document.getElementById(id);
                if (!cb.disabled) {
                    cb.checked = !cb.checked;
                    updateButtonState();
                }
            }

            function updateButtonState() {
                const checkboxes = document.querySelectorAll('input[name="events[]"]:checked');
                const btn = document.getElementById('import-btn');
                btn.disabled = checkboxes.length === 0;
            }

            // Search and Filter logic
            const searchInput = document.getElementById('event-search');
            const filterMode = document.getElementById('filter-mode');
            const eventItems = document.querySelectorAll('.event-item');

            function applyFilter() {
                const query = searchInput.value.toLowerCase().trim();
                const mode = filterMode.value;

                eventItems.forEach(item => {
                    const title = item.getAttribute('data-title');
                    const description = item.getAttribute('data-description');
                    const matches = title.includes(query) || description.includes(query);

                    if (query === '') {
                        item.classList.remove('hidden');
                        return;
                    }

                    if (mode === 'include') {
                        if (matches) {
                            item.classList.remove('hidden');
                        } else {
                            item.classList.add('hidden');
                        }
                    } else { // exclude
                        if (matches) {
                            item.classList.add('hidden');
                        } else {
                            item.classList.remove('hidden');
                        }
                    }
                });
            }

            searchInput.addEventListener('input', applyFilter);
            filterMode.addEventListener('change', applyFilter);

            document.getElementById('select-all').addEventListener('click', function() {
                const visibleCheckboxes = document.querySelectorAll(
                    '.event-item:not(.hidden) input[name="events[]"]:not(:disabled)');
                const allChecked = Array.from(visibleCheckboxes).every(cb => cb.checked);
                visibleCheckboxes.forEach(cb => cb.checked = !allChecked);
                updateButtonState();
            });

            // Initial state
            updateButtonState();
        </script>
        <style>
            .custom-scrollbar::-webkit-scrollbar {
                width: 4px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: rgba(139, 92, 246, 0.2);
                border-radius: 10px;
            }
        </style>
    @endpush
</x-app-layout>
