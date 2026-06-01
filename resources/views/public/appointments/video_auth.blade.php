@extends('layouts.public_appointments')

@section('title', 'Acceso a Videoconferencia')

@section('content')
<div class="py-16 px-4 sm:px-6 lg:px-8 max-w-lg mx-auto text-center space-y-8">
    
    <!-- Icono de Candado -->
    <div class="w-20 h-20 bg-cyan-50 dark:bg-cyan-950/30 rounded-full flex items-center justify-center text-cyan-500 mx-auto border border-cyan-100 dark:border-cyan-900/50 shadow-md">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>

    <!-- Título -->
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white heading-font tracking-tight">Acceso a Videoconferencia</h1>
        <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold mt-2">Introduce el Localizador de tu cita para entrar a la sala.</p>
    </div>

    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-2xl p-4 text-xs font-bold text-center shadow-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Formulario -->
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-150 dark:border-gray-800 shadow-lg p-8">
        <form method="POST" action="{{ route('public.appointments.video.access', $appointment) }}" class="space-y-6">
            @csrf
            <div>
                <label for="localizador" class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Localizador Único</label>
                <input type="text" name="localizador" id="localizador" required autofocus autocomplete="off"
                       value="{{ old('localizador', $prefilledLocalizador ?? '') }}"
                       class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-lg font-mono font-black text-center uppercase text-gray-900 dark:text-white outline-none transition-all"
                       placeholder="EJ. MTXCITA-XXXXXXXX">
            </div>

            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-5 py-3.5 text-xs font-black uppercase tracking-widest text-white bg-cyan-600 hover:bg-cyan-500 rounded-2xl shadow-lg shadow-cyan-500/20 active:scale-98 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Acceder a la Sala
            </button>
        </form>
    </div>

</div>
@endsection
