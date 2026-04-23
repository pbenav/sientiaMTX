@auth
    @include('layouts.partials.workday-timer')
    <div class="h-6 w-px bg-gray-200 dark:bg-gray-800 mx-1 hidden lg:block"></div>
@endauth

<div class="flex items-center gap-1">
    @include('layouts.partials.theme-toggle')
    @include('layouts.partials.layout-toggle')
    @include('layouts.partials.zoom-controls')
    @include('layouts.partials.language-toggle')
</div>
