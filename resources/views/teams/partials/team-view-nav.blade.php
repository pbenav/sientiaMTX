{{--
    Menú de vistas del equipo + fila opcional de creación de actividades.
    Variables requeridas: $team
    Variables opcionales:
    - $showCreateActions (bool): muestra "Nueva actividad" bajo el menú
    - $switcherClass (string): clases del contenedor del menú
--}}
@php
    $showCreateActions = $showCreateActions ?? false;
    $switcherClass = $switcherClass ?? 'mt-2 flex w-full';
@endphp

<div class="{{ $switcherClass }}">
    @include('teams.partials.view-switcher')
</div>

@if ($showCreateActions)
    <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
        @include('teams.partials.header-actions', ['createOnly' => true])
    </div>
@endif
