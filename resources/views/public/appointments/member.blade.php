@extends('layouts.public_appointments')

@section('title', $settings->display_name ?: $member->name)

@section('content')
<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto space-y-8">
    
    <!-- Botón Volver al mapa -->
    <a href="{{ route('public.appointments.map') }}" class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        {{ __('Volver al Mapa') }}
    </a>

    <!-- Encabezado / Perfil del miembro -->
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-150 dark:border-gray-800 p-8 shadow-sm relative overflow-hidden group">
        <div class="absolute -right-12 -top-12 w-32 h-32 bg-cyan-500/5 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
        
        <div class="flex flex-col sm:flex-row sm:items-center gap-6">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-cyan-400 to-blue-500 flex items-center justify-center text-white text-2xl font-black shrink-0 shadow-md">
                {{ substr($settings->display_name ?: $member->name, 0, 2) }}
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading-font tracking-tight">
                    {{ $settings->display_name ?: $member->name }}
                </h1>
                @if(!empty($member->working_area_name))
                    <p class="text-xs font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-widest mt-1.5">{{ $member->working_area_name }}</p>
                @endif
                <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold mt-1">{{ __('Miembro verificado de Sientia MTX') }}</p>
            </div>
            <div class="shrink-0 sm:ml-auto flex flex-col items-center">
                @php
                    $qrUrl = route('public.appointments.member', $member->slug);
                    $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->margin(1)->color(8, 145, 178)->generate($qrUrl);
                    $qrCodeSmall = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(48)->margin(0)->color(8, 145, 178)->generate($qrUrl);
                @endphp
                <a href="data:image/svg+xml;base64,{{ base64_encode($qrCodeSvg) }}" download="qr-cita-{{ $member->slug }}.svg" 
                   class="block p-1 bg-white border border-gray-200 dark:border-gray-700 hover:border-cyan-500 rounded-xl shadow-sm transition-all hover:scale-105 group/qr"
                   title="{{ __('Descargar código QR de Cita Previa') }}">
                    {!! $qrCodeSmall !!}
                </a>
                <span class="text-[9px] font-black uppercase text-gray-400 dark:text-gray-500 mt-1">{{ __('Escanear') }}</span>
            </div>
        </div>

        @if($settings->welcome_text)
            <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-850 text-sm text-gray-600 dark:text-gray-400 leading-relaxed font-medium markdown-body">
                {!! Str::markdown($settings->welcome_text) !!}
            </div>
        @endif
    </div>

    <!-- Lista de Servicios Disponibles -->
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-black tracking-tight text-gray-900 dark:text-white heading-font">{{ __('Servicios Disponibles') }}</h2>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">{{ __('Elige el servicio del que deseas solicitar cita previa.') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4">
            @forelse($services as $service)
                <div class="group bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-cyan-300 dark:hover:border-cyan-500/50 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="absolute -right-6 -top-6 w-20 h-20 bg-cyan-500/5 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-base font-black text-gray-900 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ $service->name }}</h3>
                            @if($service->team)
                                <span class="text-[10px] font-black text-violet-750 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/30 px-2 py-0.5 rounded-md border border-violet-100 dark:border-violet-900/50">
                                    {{ $service->team->name }}
                                </span>
                            @endif
                            
                            <span class="text-[10px] font-black text-cyan-700 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/30 px-2 py-0.5 rounded-md">
                                ⏱ {{ $service->duration_minutes }} min
                            </span>

                            @if($service->price !== null && $service->price_visible)
                                <span class="text-[10px] font-black text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-md">
                                    {{ $service->price > 0 ? '€' . number_format($service->price, 2) : __('Gratuito') }}
                                </span>
                            @endif
                        </div>

                        @if($service->description)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                                {!! Str::markdown($service->description) !!}
                            </div>
                        @endif
                    </div>

                    <div class="shrink-0">
                        <a href="{{ route('public.appointments.book', $service) }}" 
                           class="flex items-center justify-center gap-2 px-5 py-3 text-xs font-black uppercase tracking-widest bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl shadow-lg shadow-cyan-500/20 active:scale-98 transition-all w-full md:w-auto">
                            {{ __('Elegir fecha y hora') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-12 text-center">
                    <p class="text-3xl mb-2">📭</p>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400">{{ __('Sin servicios disponibles') }}</p>
                    <p class="text-xs text-gray-450 dark:text-gray-550 mt-1">{{ __('Este miembro aún no ha activado ningún servicio.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
