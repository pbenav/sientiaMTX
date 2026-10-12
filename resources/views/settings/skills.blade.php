<x-app-layout>
    @section('title', isset($team) ? __('Skills of :name', ['name' => $team->name]) : __('Skills Management'))

    <x-slot name="header">
        @if(isset($team))
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                <div class="flex items-start gap-4 min-w-0 flex-1">
                    <a href="{{ route('teams.index') }}"
                        class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                        title="{{ __('navigation.back') ?? __('Back') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <div class="min-w-0 flex-1">
                        @include('teams.partials.breadcrumb')
                        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                            {{ __('teams.edit') }}: {{ $team->name }}
                        </h1>
                    </div>
                </div>
                @include('teams.partials.header-toolbar', ['class' => 'self-start'])
            </div>

            <div class="mt-2">
                <x-demo-hint>
                    {{ __('The team Skills Catalog allows managing technical and operational competencies assignable to members. These skills influence smart assignment and professional growth metrics.') }}
                </x-demo-hint>
            </div>

            @include('teams.partials.team-view-nav', ['switcherClass' => 'mt-4 mb-2 flex w-full'])
        @else
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl shadow-sm border border-violet-200 dark:border-violet-800/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">
                            {{ __('Skills Catalog') }}
                        </h1>
                        <x-demo-hint>
                            {{ __('The Global Skills Catalog structures competencies or skills needed in the organization. These skills are assigned to tasks to calculate cognitive workload and visualize each team\'s strengths.') }}
                        </x-demo-hint>
                    </div>
                </div>
            </div>
        @endif
    </x-slot>

    <div class="py-12 px-4">
        <div class="w-full max-w-[1920px] mx-auto">
            @if(!isset($team))
                @include('settings.partials.tabs')
            @else
                @include('teams.partials.settings-tabs')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                @include('settings.partials.skill-management')
            </div>
        </div>
    </div>
</x-app-layout>
