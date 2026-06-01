@extends('layouts.public_appointments')

@section('title', 'Cita Confirmada — ' . $appointment->localizador)

@section('content')
<div class="py-16 px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto text-center space-y-8">
    
    <!-- Icono de éxito animado -->
    <div class="w-20 h-20 bg-emerald-50 dark:bg-emerald-950/30 rounded-full flex items-center justify-center text-emerald-500 mx-auto border border-emerald-100 dark:border-emerald-900/50 shadow-md">
        <svg class="w-10 h-10 animate-bounce" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>

    <!-- Título -->
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white heading-font tracking-tight">¡Cita Reservada con Éxito!</h1>
        <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold mt-2">Tu reserva ha sido procesada y confirmada en nuestro sistema.</p>
    </div>

    <!-- Tarjeta del Localizador (Premium) -->
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-150 dark:border-gray-800 shadow-lg p-8 relative overflow-hidden group max-w-md mx-auto">
        <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl pointer-events-none"></div>
        
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Localizador Único</p>
        <p class="text-2xl font-black text-cyan-600 dark:text-cyan-400 font-mono tracking-wider select-all py-2.5 px-4 bg-gray-50 dark:bg-gray-850 rounded-2xl inline-block shadow-inner border border-gray-150/50 dark:border-gray-800">
            {{ $appointment->localizador }}
        </p>
        
        <p class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold mt-4">Conserva este código para cualquier modificación o consulta sobre tu cita.</p>
    </div>

    <!-- Detalles de la reserva -->
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-150 dark:border-gray-800 shadow-sm p-6 max-w-md mx-auto text-left divide-y divide-gray-100 dark:divide-gray-850">
        <div class="pb-3.5 flex items-center justify-between">
            <span class="text-xs font-black uppercase tracking-wider text-gray-400">Servicio</span>
            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->service->name }}</span>
        </div>
        <div class="py-3.5 flex items-center justify-between">
            <span class="text-xs font-black uppercase tracking-wider text-gray-400">Fecha</span>
            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->appointment_date->format('d/m/Y') }}</span>
        </div>
        <div class="py-3.5 flex items-center justify-between">
            <span class="text-xs font-black uppercase tracking-wider text-gray-400">Hora</span>
            <span class="text-sm font-bold text-cyan-600 dark:text-cyan-400">{{ substr($appointment->appointment_time, 0, 5) }}</span>
        </div>
        <div class="py-3.5 flex items-center justify-between">
            <span class="text-xs font-black uppercase tracking-wider text-gray-400">Ciudadano</span>
            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->visitor->full_name }}</span>
        </div>
        @if($appointment->visitor->email)
            <div class="pt-3.5 text-center text-xs font-semibold text-gray-400 dark:text-gray-500">
                📬 Se ha enviado un correo electrónico de confirmación a <span class="text-gray-700 dark:text-gray-300 font-black">{{ $appointment->visitor->email }}</span>
            </div>
        @endif
    </div>

    <!-- Botones de Acción -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 max-w-md mx-auto">
        @php
            $gcalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
                . '&text=' . urlencode('[CITA] ' . $appointment->service->name)
                . '&dates=' . $appointment->appointment_datetime->format('Ymd\THis')
                . '/' . $appointment->end_datetime->format('Ymd\THis')
                . '&details=' . urlencode('Localizador: ' . $appointment->localizador . "\nAtendido por: " . ($appointment->member->name));
        @endphp
        
        <a href="{{ $gcalUrl }}" target="_blank"
           class="flex items-center justify-center gap-2 px-5 py-3 text-xs font-black uppercase tracking-widest text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-950/20 border border-cyan-150 dark:border-cyan-900/50 hover:bg-cyan-100 dark:hover:bg-cyan-950/40 rounded-xl transition-all w-full sm:w-auto">
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M19.5 3h-2V1.5h-1.5V3h-9V1.5H5.5V3h-2C2.67 3 2 3.67 2 4.5v15C2 20.33 2.67 21 3.5 21h16c.83 0 1.5-.67 1.5-1.5v-15c0-.83-.67-1.5-1.5-1.5zm0 16.5h-16V9h16v10.5zM3.5 7.5h16V4.5h-16V7.5z"/></svg>
            Añadir a Google Calendar
        </a>

        <a href="{{ route('public.appointments.map') }}" 
           class="px-5 py-3 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all w-full sm:w-auto">
            Volver al Inicio
        </a>
    </div>

</div>
@endsection
