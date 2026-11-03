{{--
    SPDX-License-Identifier: AGPL-3.0-or-later
    Copyright (c) 2022-2026 pbenav <info@sientia.com>

    Barra de Subnavegación de Circuitos
--}}

@php
    $myPendingCount = auth()->user()->pendingCircuitExecutionsCount($team->id);
    $isIndex = request()->routeIs('teams.circuits.index');
    $isPending = request()->routeIs('teams.circuits.pending');
    $isTemplates = request()->routeIs('teams.circuits.templates.*');
    $isBoard = request()->routeIs('teams.circuits.board.*');
@endphp

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
    {{-- Circuit navigation tabs --}}
    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
        <a href="{{ route('teams.circuits.index', $team) }}"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 {{ $isIndex ? 'bg-violet-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            <span>Todas las Tramitaciones</span>
        </a>

        <a href="{{ route('teams.circuits.pending', $team) }}"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 {{ $isPending ? 'bg-violet-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Mis Pendientes</span>
            @if($myPendingCount > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ $isPending ? 'bg-white text-violet-700' : 'bg-rose-500 text-white' }}">
                    {{ $myPendingCount }}
                </span>
            @endif
        </a>

        <a href="{{ route('teams.circuits.templates.index', $team) }}"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 {{ $isTemplates ? 'bg-violet-600 text-white shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Plantillas</span>
        </a>

        <a href="{{ route('teams.circuits.board.index', $team) }}"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-1.5 {{ $isBoard ? 'bg-teal-600 text-white shadow-sm' : 'text-teal-700 dark:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-950/40' }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            <span>Tablón Edictal</span>
        </a>
    </div>

    {{-- Contextual quick actions --}}
    <div class="flex items-center gap-2 shrink-0">
        @if($isTemplates)
            <a href="{{ route('teams.circuits.templates.create', $team) }}"
                class="flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-3.5 py-1.5 rounded-xl transition-all font-bold shadow-sm shadow-violet-500/20 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Nueva Plantilla</span>
            </a>
        @endif

        <a href="{{ route('public.circuits.board', $team) }}" target="_blank"
            class="inline-flex items-center gap-1 text-[11px] font-bold text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-600 transition shadow-xs">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            <span>Portal Ciudadano</span>
        </a>
    </div>
</div>

