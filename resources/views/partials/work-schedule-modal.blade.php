@auth
@if(auth()->user()->isWorking())
@php
    // Obtenemos la fecha más antigua de inicio de los contadores activos
    $oldestActiveLog = auth()->user()->timeLogs()->whereNull('end_at')->orderBy('start_at', 'asc')->first();
    $startedAtDate = $oldestActiveLog && $oldestActiveLog->start_at ? $oldestActiveLog->start_at->toDateString() : now()->toDateString();
@endphp
<div x-data="{
    startedAtDate: '{{ $startedAtDate }}',
    open: false,
    countdown: 60,
    timer: null,
    endTime1: '{{ auth()->user()->work_end_time_1 }}',
    workDays1: {{ json_encode(auth()->user()->work_days_1 ?? []) }},
    startTime2: '{{ auth()->user()->work_start_time_2 }}',
    endTime2: '{{ auth()->user()->work_end_time_2 }}',
    workDays2: {{ json_encode(auth()->user()->work_days_2 ?? []) }},
    limitShown: '',
    checkInterval: null,
    
    init() {
        // Run check every 30 seconds
        this.checkSchedule();
        this.checkInterval = setInterval(() => this.checkSchedule(), 30000);
    },
    
    checkSchedule() {
        if (this.open) return;
        
        const nowObj = new Date();
        const Y = nowObj.getFullYear();
        const M = String(nowObj.getMonth() + 1).padStart(2, '0');
        const D = String(nowObj.getDate()).padStart(2, '0');
        const todayStr = `${Y}-${M}-${D}`;
        
        const dayNames = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        const currentDayName = dayNames[nowObj.getDay()];

        if (localStorage.getItem('schedule_check_ack_' + todayStr) === 'true') {
            return;
        }

        // CONTROL ABSOLUTO 1: ¿El registro de tiempo empezó ayer o antes? (Cambio de Día)
        if (this.startedAtDate && this.startedAtDate < todayStr) {
             this.limitShown = 'Cambio de Día';
             this.open = true;
             this.startCountdown();
             return;
        }
        
        const parseTime = (timeStr) => {
            if (!timeStr || timeStr.trim() === '') return null;
            const parts = timeStr.split(':');
            return Number(parts[0]) * 60 + Number(parts[1]);
        };

        const currentMinutes = nowObj.getHours() * 60 + nowObj.getMinutes();

        const end1 = parseTime(this.endTime1);
        const start2 = parseTime(this.startTime2);
        const end2 = parseTime(this.endTime2);

        let pastEndTime = false;
        let limitLabel = '';

        // 1. EVALUAR HORARIO DEFINIDO
        const shift1Active = this.workDays1.includes(currentDayName);
        const shift2Active = this.workDays2.includes(currentDayName);

        if (shift1Active || shift2Active) {
            // Caso Turno 1
            if (shift1Active && end1 && currentMinutes >= end1) {
                // Si el turno 2 también está activo hoy y empieza después, esperamos.
                if (shift2Active && start2) {
                    if (currentMinutes < start2) {
                        pastEndTime = true;
                        limitLabel = this.endTime1;
                    } else if (end2 && currentMinutes >= end2) {
                        pastEndTime = true;
                        limitLabel = this.endTime2;
                    }
                } else {
                    pastEndTime = true;
                    limitLabel = this.endTime1;
                }
            } 
            // Caso solo Turno 2 activo hoy o turno 1 no activado pero el 2 sí
            else if (shift2Active && end2 && currentMinutes >= end2) {
                pastEndTime = true;
                limitLabel = this.endTime2;
            }
        }
        
        // 2. CONTROL ABSOLUTO 2: Si no hay horario (o incluso si lo hay), el límite HARD diario es 23:55.
        if (!pastEndTime && currentMinutes >= 1435) { 
            pastEndTime = true;
            limitLabel = 'Medianoche';
        }
        
        if (pastEndTime) {
            this.limitShown = limitLabel;
            this.open = true;
            this.startCountdown();
        }
    },
    
    startCountdown() {
        this.countdown = 60;
        this.timer = setInterval(() => {
            this.countdown--;
            if (this.countdown <= 0) {
                this.autoLogout();
            }
        }, 1000);
    },
    
    keepWorking() {
        clearInterval(this.timer);
        this.open = false;
        const today = new Date().toISOString().slice(0, 10);
        localStorage.setItem('schedule_check_ack_' + today, 'true');
        
        // Touch activity endpoint to keep session alive
        fetch('{{ route('time-logs.status') }}')
            .catch(err => console.error('Error keeping session alive:', err));
    },
    
    autoLogout() {
        clearInterval(this.timer);
        clearInterval(this.checkInterval);
        document.getElementById('schedule-logout-form').submit();
    }
}"
x-show="open"
x-cloak
style="display: none;"
class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-gray-950/40 backdrop-blur-md">
    
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="w-full max-w-md bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl shadow-2xl p-6 relative overflow-hidden">
        
        <!-- Glowing Ambient Background -->
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-violet-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col items-center text-center space-y-4">
            <!-- Alert Icon with Pulsing Ping -->
            <div class="relative">
                <span class="animate-ping absolute inline-flex h-12 w-12 rounded-full bg-violet-400 opacity-20"></span>
                <div class="relative w-12 h-12 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-2xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Title & Description -->
            <div class="space-y-2">
                <h3 class="text-lg font-black tracking-tight text-gray-900 dark:text-white uppercase">¿Sigues en tus labores?</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    Hemos detectado que tu turno habitual finalizó a las <span class="font-bold text-violet-600 dark:text-violet-400" x-text="limitShown">14:00</span>, pero tus contadores de tiempo siguen activos.
                </p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 leading-relaxed">
                    Si no respondes, el sistema detendrá automáticamente tu jornada y cerrará tu sesión para evitar registros erróneos.
                </p>
            </div>

            <!-- Countdown Timer Ring -->
            <div class="flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-xl">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                </span>
                <span class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">
                    Cierre automático en: <span x-text="countdown" class="font-mono font-black">60</span>s
                </span>
            </div>

            <!-- Interactive Action Buttons -->
            <div class="w-full grid grid-cols-2 gap-3 pt-2">
                <button @click="autoLogout()" 
                        class="px-4 py-3 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800/50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-bold uppercase tracking-wider rounded-2xl border border-gray-100 dark:border-gray-700/50 transition-all">
                    Finalizar y Salir
                </button>
                <button @click="keepWorking()" 
                        class="px-4 py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white text-xs font-bold uppercase tracking-wider rounded-2xl shadow-lg shadow-violet-600/20 hover:shadow-violet-600/35 transition-all">
                    Sigo Activo
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden standard logout form -->
    <form id="schedule-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
        @csrf
    </form>
</div>
@endif
@endauth
