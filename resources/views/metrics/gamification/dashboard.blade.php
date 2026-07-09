@extends('metrics.layouts.app')

@section('title', __('Gamification Dashboard'))
@section('breadcrumb', __('Gamification'))

@section('content')
<div class="space-y-6" x-data>
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('Gamification Dashboard') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Track your progress, badges, and team rankings.') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Period: 30 days') }}</span>
        </div>
    </div>

    {{-- Row 1: Your Position + Progress to Next Level --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Your Position Card --}}
        <div class="lg:col-span-1 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.08 3.277a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.08 3.277c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.08-3.277a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.08-3.277z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Your Position') }}</h2>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Tu posición actual en el ranking global del equipo basada en puntos.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>

            <div class="flex flex-col items-center py-4">
                <div class="relative mb-4">
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg shadow-orange-200 dark:shadow-orange-900/30">
                        <span class="text-3xl font-bold text-white">{{ number_format($userPosition['position'] ?? 0) }}</span>
                    </div>
                    @if(($userPosition['position'] ?? 1) <= 3)
                    <div class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-white dark:bg-gray-800 flex items-center justify-center shadow-md text-lg">
                        {{ ['🥇', '🥈', '🥉'][($userPosition['position'] ?? 1) - 1] }}
                    </div>
                    @endif
                </div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $user->name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('of :count teammates', ['count' => $userPosition['total'] ?? 0]) }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3 mt-4">
                <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3 text-center">
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($userPosition['points'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Points') }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3 text-center">
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $userProgress['badges_unlocked'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Badges') }}</p>
                </div>
            </div>
        </div>

        {{-- Progress to Next Level --}}
        <div class="lg:col-span-2 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-violet-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Level Progress') }}</h2>
                    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Progreso de experiencia necesario para alcanzar el siguiente nivel en la plataforma.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="text-xs font-bold px-3 py-1 rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300">
                    {{ __('Level :level', ['level' => $userProgress['level'] ?? 1]) }}
                </span>
            </div>

            <div class="mt-2">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Current') }}: {{ number_format($userProgress['current_points'] ?? 0) }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Next') }}: {{ number_format($userProgress['points_needed'] ?? 100) }}</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-purple-600 transition-all duration-700"
                         style="width: {{ $userProgress['progress'] ?? 0 }}%"
                         role="progressbar"
                         aria-valuenow="{{ $userProgress['progress'] ?? 0 }}"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>
                <p class="text-right text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($userProgress['progress'] ?? 0, 1) }}%</p>
            </div>

            {{-- Quick Stats Row --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6">
                <div class="text-center p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
                    <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $userProgress['level'] ?? 1 }}</p>
                    <p class="text-xs text-emerald-600/70 dark:text-emerald-400/70">{{ __('Level') }}</p>
                </div>
                <div class="text-center p-3 rounded-xl bg-sky-50 dark:bg-sky-900/20">
                    <p class="text-xl font-bold text-sky-600 dark:text-sky-400">{{ $userProgress['current_points'] ?? 0 }}</p>
                    <p class="text-xs text-sky-600/70 dark:text-sky-400/70">{{ __('Points') }}</p>
                </div>
                <div class="text-center p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20">
                    <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ $userProgress['points_needed'] ?? 0 }}</p>
                    <p class="text-xs text-amber-600/70 dark:text-amber-400/70">{{ __('To Next') }}</p>
                </div>
                <div class="text-center p-3 rounded-xl bg-rose-50 dark:bg-rose-900/20">
                    <p class="text-xl font-bold text-rose-600 dark:text-rose-400">{{ $userProgress['badges_unlocked'] ?? 0 }}</p>
                    <p class="text-xs text-rose-600/70 dark:text-rose-400/70">{{ __('Badges') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 2: Team Leaderboard --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <h2 class="text-lg font-bold">{{ __('Team Leaderboard') }}</h2>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Clasificación global de los miembros del equipo en función de los puntos y nivel de experiencia.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <select class="text-xs border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                <option value="">{{ __('All Teams') }}</option>
                <option value="{{ $user->team_id ?? '' }}" selected>{{ __('Current Team') }}</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <th class="px-6 py-3 text-left font-semibold">{{ __('Rank') }}</th>
                        <th class="px-6 py-3 text-left font-semibold">{{ __('User') }}</th>
                        <th class="px-6 py-3 text-right font-semibold">{{ __('Points') }}</th>
                        <th class="px-6 py-3 text-right font-semibold">{{ __('Badges') }}</th>
                        <th class="px-6 py-3 text-right font-semibold">{{ __('Level') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse(($teamLeaderboard ?? []) as $index => $member)
                    <tr class="{{ ($userPosition['position'] ?? 0) === ($index + 1) ? 'bg-violet-50 dark:bg-violet-900/10' : 'hover:bg-gray-50 dark:hover:bg-gray-800/30' }} transition">
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold {{ $index === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : ($index === 1 ? 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' : ($index === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400')) }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center text-xs font-bold text-white">
                                    {{ strtoupper(substr($member['name'] ?? $member['email'] ?? '?', 0, 1)) }}
                                </div>
                                <span class="font-medium {{ ($userPosition['position'] ?? 0) === ($index + 1) ? 'text-violet-600 dark:text-violet-400' : 'text-gray-700 dark:text-gray-200' }}">
                                    {{ $member['name'] ?? $member['email'] ?? __('Unknown') }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">
                            {{ number_format($member['total_points'] ?? 0) }}
                        </td>
                        <td class="px-6 py-3 text-right text-gray-500 dark:text-gray-400">
                            {{ $member['badges_count'] ?? 0 }}
                        </td>
                        <td class="px-6 py-3 text-right">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300">
                                L{{ ($member['level'] ?? 1) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No leaderboard data available yet.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Row 3: Badges Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Unlocked Badges --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.08 3.277a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.08 3.277c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.08-3.277a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.08-3.277z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Badges') }}</h2>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Insignias y logros que puedes desbloquear al alcanzar hitos en la plataforma.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>

            <div class="grid grid-cols-4 gap-3">
                @php
                    $allBadges = [
                        ['name' => 'First Login', 'icon' => '🔑', 'desc' => 'Log in for the first time'],
                        ['name' => 'Streak Master', 'icon' => '🔥', 'desc' => 'Maintain a 7-day streak'],
                        ['name' => 'Team Player', 'icon' => '🤝', 'desc' => 'Collaborate with 5 teammates'],
                        ['name' => 'Top Performer', 'icon' => '🏆', 'desc' => 'Reach #1 on the leaderboard'],
                        ['name' => 'Kudos Queen', 'icon' => '💌', 'desc' => 'Send 10 kudos'],
                        ['name' => 'Badge Collector', 'icon' => '🎖️', 'desc' => 'Collect 5 different badges'],
                        ['name' => 'Night Owl', 'icon' => '🦉', 'desc' => 'Active after midnight'],
                        ['name' => 'Early Bird', 'icon' => '🐦', 'desc' => 'Active before 7am'],
                        ['name' => 'Milestone', 'icon' => '🎯', 'desc' => 'Reach 1000 points'],
                        ['name' => 'Consistent', 'icon' => '📅', 'desc' => '30-day consecutive activity'],
                        ['name' => 'Helper', 'icon' => '🆘', 'desc' => 'Help 10 teammates'],
                        ['name' => 'Legend', 'icon' => '⭐', 'desc' => 'Reach level 10'],
                    ];
                    $unlockedNames = collect($teamLeaderboard ?? [])->flatten()->toArray();
                @endphp

                @foreach($allBadges as $badge)
                <div class="group relative flex flex-col items-center p-3 rounded-xl {{ in_array($badge['name'], $unlockedNames) ? 'bg-gray-50 dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-800/50 opacity-40 grayscale' }} transition hover:scale-105 cursor-pointer">
                    <span class="text-2xl mb-1">{{ $badge['icon'] }}</span>
                    <span class="text-[10px] text-center font-medium text-gray-600 dark:text-gray-300 leading-tight">{{ $badge['name'] }}</span>
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap shadow-xl z-10">
                        {{ $badge['desc'] }}
                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Most Popular Badges Bar Chart --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Most Popular Badges') }}</h2>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Insignias obtenidas con mayor frecuencia por los miembros del equipo.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div id="popularBadgesChart" class="w-full" style="min-height: 280px;"></div>
        </div>
    </div>

    {{-- Row 4: Kudos Leaderboards --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top 10 Sent --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Top 10 Kudos Sent') }}</h2>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Usuarios que más reconocimientos (Kudos) han enviado a sus compañeros.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="space-y-2">
                @forelse(($kudosSent ?? []) as $index => $kudo)
                <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                    <span class="text-xs font-bold w-6 text-center {{ $index < 3 ? 'text-amber-500' : 'text-gray-400' }}">{{ $index + 1 }}</span>
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                        {{ strtoupper(substr($kudo['name'] ?? $kudo['email'] ?? '?', 0, 1)) }}
                    </div>
                    <span class="text-sm font-medium flex-1 truncate text-gray-700 dark:text-gray-200">{{ $kudo['name'] ?? $kudo['email'] ?? __('Unknown') }}</span>
                    <span class="text-sm font-bold text-teal-600 dark:text-teal-400">{{ $kudo['kudos_sent'] ?? $kudo['count'] ?? 0 }}</span>
                </div>
                @empty
                <p class="text-xs text-gray-400 text-center py-8">{{ __('No kudos data yet') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Top 10 Received --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Top 10 Kudos Received') }}</h2>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Usuarios que más reconocimientos (Kudos) han recibido por parte del equipo.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="space-y-2">
                @forelse(($kudosReceived ?? []) as $index => $kudo)
                <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                    <span class="text-xs font-bold w-6 text-center {{ $index < 3 ? 'text-amber-500' : 'text-gray-400' }}">{{ $index + 1 }}</span>
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-rose-400 to-pink-500 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                        {{ strtoupper(substr($kudo['name'] ?? $kudo['email'] ?? '?', 0, 1)) }}
                    </div>
                    <span class="text-sm font-medium flex-1 truncate text-gray-700 dark:text-gray-200">{{ $kudo['name'] ?? $kudo['email'] ?? __('Unknown') }}</span>
                    <span class="text-sm font-bold text-rose-600 dark:text-rose-400">{{ $kudo['kudos_received'] ?? $kudo['count'] ?? 0 }}</span>
                </div>
                @empty
                <p class="text-xs text-gray-400 text-center py-8">{{ __('No kudos data yet') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Row 5: Streak Leaderboard --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Streak Leaderboard') }}</h2>
            <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Ranking de usuarios con más días consecutivos de actividad en la plataforma.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @forelse(($streakLeaderboard ?? []) as $index => $streak)
            @if($index < 8)
            <div class="flex items-center gap-3 p-3 rounded-xl {{ $index === 0 ? 'bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800' : 'bg-gray-50 dark:bg-gray-800' }}">
                <span class="text-sm font-bold w-6 text-center {{ $index < 3 ? 'text-amber-500' : 'text-gray-400' }}">{{ $index + 1 }}</span>
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                    {{ strtoupper(substr($streak['name'] ?? '?', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold truncate text-gray-700 dark:text-gray-200">{{ $streak['name'] ?? __('Unknown') }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ __('Streak') }}</p>
                </div>
                <span class="text-sm font-bold text-orange-600 dark:text-orange-400">{{ $streak['streak_days'] ?? 0 }}🔥</span>
            </div>
            @endif
            @empty
            <p class="col-span-full text-xs text-gray-400 text-center py-8">{{ __('No streak data yet') }}</p>
            @endforelse
        </div>
    </div>

    {{-- Row 6: Engagement Trend Chart --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Engagement Trend') }}</h2>
            <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Evolución temporal del nivel de participación y actividad de los usuarios.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="ml-auto text-xs text-gray-400">{{ __('Last 30 days') }}</span>
        </div>
        <div id="engagementTrendChart" class="w-full" style="min-height: 300px;"></div>
    </div>

    {{-- Row 7: Points Distribution + Engagement vs Productivity --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Points Distribution Histogram --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Points Distribution') }}</h2>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Distribución estadística de los puntos obtenidos agrupados por categoría o acción.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div id="pointsDistributionChart" class="w-full" style="min-height: 300px;"></div>
        </div>

        {{-- Engagement vs Productivity Scatter Plot --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8s1-3.5 4-3.5 4 7 4 7-1 3.5-4 3.5-4-7-4-7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8s1-3.5 4-3.5 4 7 4 7-1 3.5-4 3.5-4-7-4-7z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Engagement vs Productivity') }}</h2>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Comparativa entre la implicación en la plataforma y el rendimiento productivo.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div id="scatterPlotChart" class="w-full" style="min-height: 300px;"></div>
        </div>
    </div>

    {{-- Row 8: Recent Achievements Ticker --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.08 3.277a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.08 3.277c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.08-3.277a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.08-3.277z"/></svg>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Recent Achievements') }}</h2>
            <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Últimos logros e insignias desbloqueados recientemente por el equipo.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>

        <div class="overflow-hidden rounded-xl bg-gradient-to-r from-violet-500/5 via-purple-500/5 to-pink-500/5 dark:from-violet-900/10 dark:via-purple-900/10 dark:to-pink-900/10 border border-violet-100 dark:border-violet-800/30">
            <div class="flex items-center gap-6 py-3 px-5 animate-marquee whitespace-nowrap" style="animation: marquee 30s linear infinite;">
                @forelse($recentAchievements ?? [] as $achievement)
                <div class="inline-flex items-center gap-2 text-sm">
                    <span class="text-base">🏅</span>
                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $user->name ?? __('You') }}</span>
                    <span class="text-gray-400">{{ __('earned') }}</span>
                    <span class="font-bold text-violet-600 dark:text-violet-400">{{ is_array($achievement) ? ($achievement['description'] ?? __('achievement')) : ($achievement->description ?? __('achievement')) }}</span>
                    <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse(is_array($achievement) ? ($achievement['created_at'] ?? now()) : ($achievement->created_at ?? now()))->diffForHumans() }}</span>
                </div>
                @if(!$loop->last)<span class="text-gray-300 dark:text-gray-700">•</span>@endif
                @empty
                <span class="text-sm text-gray-400">{{ __('No achievements yet. Keep going!') }}</span>
                @endforelse
            </div>
        </div>

        <style>
            @keyframes marquee {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
        </style>
    </div>

    {{-- Row 9: Achievement History Timeline --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-6">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Your Achievement History') }}</h2>
            <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Historial cronológico de todos los logros y puntos que has obtenido.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>

        <div class="relative">
            <div class="absolute left-3.5 top-2 bottom-2 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
            <div class="space-y-4">
                @php
                    $userAchievements = collect($recentAchievements ?? []);
                @endphp

                @forelse($userAchievements->take(10) as $achievement)
                <div class="relative flex items-start gap-4 pl-10">
                    <div class="absolute left-1.5 top-1.5 w-4 h-4 rounded-full bg-violet-500 border-2 border-white dark:border-gray-900 z-10"></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ is_array($achievement) ? ($achievement['description'] ?? __('Achievement')) : ($achievement->description ?? __('Achievement')) }}</p>
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ \Carbon\Carbon::parse(is_array($achievement) ? $achievement['created_at'] : $achievement->created_at)->format('M d, Y H:i') }}</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ __('+') }}{{ is_array($achievement) ? ($achievement['points_earned'] ?? 0) : ($achievement->points_earned ?? 0) }} {{ __('points') }}
                            — {{ is_array($achievement) ? ($achievement['type'] ?? '') : ($achievement->type ?? '') }}
                        </p>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-8">{{ __('No achievements recorded yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? '#374151' : '#e5e7eb';
    const textColor = isDark ? '#9ca3af' : '#6b7280';

    // Popular Badges Horizontal Bar Chart
    const popularBadgesData = {!! json_encode($popularBadgesData ?? []) !!};
    if (typeof ApexCharts !== 'undefined') {
        const pbChart = new ApexCharts(document.getElementById("popularBadgesChart"), {
            chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [{ name: '{{ __('Count') }}', data: popularBadgesData.map(d => d.count ?? d.total_points ?? 0) }],
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
            colors: ['#8b5cf6'],
            xaxis: { categories: popularBadgesData.map(d => d.badge_name || d.source || '—') },
            grid: { borderColor: gridColor, yaxis: { lines: { show: true } } },
            tooltip: { theme: isDark ? 'dark' : 'light' },
            dataLabels: { enabled: true, formatter: (val) => val },
            states: { hover: { filter: { type: 'lighten', value: 0.15 } } }
        });
        pbChart.render();

        // Engagement Trend Area Chart
        const engagementData = {!! json_encode($engagementData ?? []) !!};
        const etChart = new ApexCharts(document.getElementById("engagementTrendChart"), {
            chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [{ name: '{{ __('Activities') }}', data: engagementData.map(d => d.activities ?? d.count ?? 0) }],
            xaxis: { categories: engagementData.map(d => d.label || d.date) },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.3, stops: [0, 90, 100] } },
            colors: ['#6366f1'],
            grid: { borderColor: gridColor, xaxis: { lines: { show: true } } },
            tooltip: { theme: isDark ? 'dark' : 'light' },
            dataLabels: { enabled: false }
        });
        etChart.render();

        // Points Distribution Histogram
        const pointsData = {!! json_encode($pointsData ?? []) !!};
        const pdChart = new ApexCharts(document.getElementById("pointsDistributionChart"), {
            chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [{ name: '{{ __('Total Points') }}', data: pointsData.map(d => d.total_points ?? d.count ?? 0) }],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
            colors: ['#06b6d4'],
            xaxis: { categories: pointsData.map(d => d.source || d.badge_name || '—') },
            grid: { borderColor: gridColor, yaxis: { lines: { show: true } } },
            tooltip: { theme: isDark ? 'dark' : 'light' },
            dataLabels: { enabled: false }
        });
        pdChart.render();

        // Engagement vs Productivity Scatter Plot
        const scatterData = [];
        const teamLeaderboard = {!! json_encode($teamLeaderboard ?? []) !!};
        teamLeaderboard.forEach((member, i) => {
            scatterData.push({ x: i + 1, y: member.total_points ?? Math.floor(Math.random() * 500) });
        });
        const spChart = new ApexCharts(document.getElementById("scatterPlotChart"), {
            chart: { type: 'scatter', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [{ name: '{{ __('Team Members') }}', data: scatterData.length ? scatterData : [{x:1,y:0}] }],
            xaxis: { title: { text: '{{ __('Rank') }}' }, labels: { rotate: -45 } },
            yaxis: { title: { text: '{{ __('Points') }}' } },
            colors: ['#a855f7'],
            grid: { borderColor: gridColor },
            tooltip: { theme: isDark ? 'dark' : 'light' },
            dataLabels: { enabled: false }
        });
        spChart.render();
    }
});
</script>
@endpush
@endsection
