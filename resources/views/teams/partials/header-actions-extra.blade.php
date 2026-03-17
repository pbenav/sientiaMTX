@php
    $layout = $layout ?? 'horizontal';
@endphp

@if ($layout === 'vertical')
    <div class="flex items-center gap-4">
        <!-- Minimal selectors for vertical layout header -->
        <div class="flex items-center gap-2">
            @include('layouts.partials.theme-toggle')
            @include('layouts.partials.layout-toggle')
            @include('layouts.partials.language-toggle')
        </div>
    </div>
@endif
