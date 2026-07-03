{{--
    Barra de acciones superior derecha del equipo.
    Por defecto: Integraciones + Mantenimiento.
    Con withCreate: incluye también Nueva actividad (Gantt, etc.).
--}}
@php
    $withCreate = $withCreate ?? false;
@endphp

<div class="flex items-center gap-2 shrink-0 {{ $class ?? '' }}">
    @if ($withCreate)
        @include('teams.partials.header-actions')
    @else
        @include('teams.partials.header-actions', ['toolsOnly' => true])
    @endif
</div>
