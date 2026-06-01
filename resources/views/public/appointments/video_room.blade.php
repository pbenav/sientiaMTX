@extends('layouts.public_appointments')

@section('title', 'Sala de Videoconferencia')

@section('content')
<div class="h-[80vh] flex flex-col bg-gray-900 rounded-3xl overflow-hidden border border-gray-800 shadow-2xl mt-4 max-w-6xl mx-auto">
    
    <!-- Cabecera de la sala -->
    <div class="bg-gray-950 px-6 py-4 flex items-center justify-between border-b border-gray-800 shrink-0">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-cyan-900/50 rounded-full flex items-center justify-center text-cyan-400 border border-cyan-500/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <h1 class="text-white font-bold text-sm">Videoconferencia: {{ $appointment->service->name }}</h1>
                <p class="text-xs text-gray-500 font-mono">Cita: {{ $appointment->localizador }}</p>
            </div>
        </div>

        <a href="{{ route('public.appointments.confirm', $appointment->localizador) }}" 
           class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-xl text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            Salir de la sala
        </a>
    </div>

    <!-- Iframe de Jitsi o Link a Meet -->
    <div class="flex-1 bg-black relative">
        @if($appointment->service->modality === 'jitsi')
            <iframe allow="camera; microphone; display-capture; fullscreen; clipboard-read; clipboard-write; autoplay"
                    src="https://meet.jit.si/SientiaMTX-{{ $appointment->localizador }}#userInfo.displayName=%22{{ urlencode($appointment->visitor->full_name) }}%22&config.prejoinPageEnabled=false"
                    class="w-full h-full border-0"
                    id="jitsi-iframe">
            </iframe>
        @elseif($appointment->service->modality === 'meet')
            <div class="flex items-center justify-center h-full flex-col text-center space-y-6">
                <svg class="w-24 h-24 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                <div>
                    <h2 class="text-2xl font-bold text-white mb-2">Sala de Google Meet</h2>
                    <p class="text-gray-400">El profesional iniciará la reunión a través de Google Meet.</p>
                </div>
                <p class="text-sm text-yellow-500 bg-yellow-500/10 px-4 py-2 rounded-xl">Nota: En Google Meet, se requiere un enlace proporcionado por el organizador.</p>
            </div>
        @endif
    </div>
</div>
@endsection
