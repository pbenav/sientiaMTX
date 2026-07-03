        <!-- Sidebar -->
        <div class="space-y-4 lg:col-span-1">

            <!-- Quality Rating Widget -->
            @php
                $canRate = $activity->assignedTo()->where('users.id', auth()->id())->exists() || $activity->assigned_user_id === auth()->id() || $team->isManager(auth()->user());
                $ratings = $activity->ratings()->with('user')->get();
                $userRating = $ratings->where('user_id', auth()->id())->first();
                $currentVal = $userRating ? $userRating->score : 0;
                $ratingsCount = $ratings->count();
            @endphp

            @if($canRate || $activity->avg_quality_score > 0)
            <div x-data="{ showRatingsModal: false }" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm transition-all hover:shadow-md relative overflow-hidden group/rating">
                <div class="absolute top-0 right-0 w-24 h-24 bg-amber-500/5 dark:bg-amber-400/5 rounded-full -mr-10 -mt-10 blur-2xl transition-all group-hover/rating:scale-150 duration-700 pointer-events-none"></div>
                
                <div class="flex items-center justify-between mb-4 relative cursor-pointer" @click="showRatingsModal = true">
                    <div>
                        <h3 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-amber-500" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                            {{ __('Calidad de Gestión') }}
                        </h3>
                        <p class="text-xs text-gray-500 font-medium">{{ __('¿Es relevante y clara?') }}</p>
                    </div>
                    <div class="text-right">
                        <div class="flex items-baseline justify-end gap-1">
                            <span class="text-lg font-black text-gray-900 dark:text-white leading-none" id="avg-rating-display">{{ $activity->avg_quality_score > 0 ? number_format($activity->avg_quality_score, 1) : '0.0' }}</span>
                            <span class="text-[10px] text-gray-400 font-bold">/ 5</span>
                        </div>
                        <span class="text-[9px] text-amber-500 dark:text-amber-400 font-bold hover:underline">
                            {{ trans_choice('{0} Sin votos|{1} 1 voto|[2,*] :count votos', $ratingsCount, ['count' => $ratingsCount]) }}
                        </span>
                    </div>
                </div>

                @if($canRate)
                <div x-data="{ 
                    rating: {{ $currentVal }}, 
                    hover: 0,
                    submitting: false,
                    async submitRating(val) {
                        if(this.submitting) return;
                        this.rating = val;
                        this.submitting = true;
                        try {
                            const res = await fetch('{{ route('teams.activities.rate', [$team, $activity]) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ score: val })
                            });
                            const data = await res.json();
                            if(data.success) {
                                const el = document.getElementById('avg-rating-display');
                                if(el) el.innerText = parseFloat(data.avg_score).toFixed(1);
                                if(window.toastr) window.toastr.success(data.message);
                                setTimeout(() => window.location.reload(), 800);
                            } else {
                                if(window.toastr) window.toastr.warning(data.message);
                            }
                        } catch(e) {
                            if(window.toastr) window.toastr.error('Error de red al guardar valoración');
                        } finally {
                            this.submitting = false;
                        }
                    }
                }" class="flex items-center justify-center gap-2 py-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50 relative z-10">
                    <template x-for="i in [1,2,3,4,5]">
                        <button type="button" 
                            @mouseenter="hover = i" 
                            @mouseleave="hover = 0"
                            @click="submitRating(i)"
                            class="focus:outline-none transition-all transform hover:scale-125 active:scale-95"
                            :class="submitting ? 'opacity-50 cursor-wait' : 'cursor-pointer'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 transition-colors duration-150" 
                                :class="(hover || rating) >= i ? 'text-amber-400 fill-current' : 'text-gray-300 dark:text-gray-600 fill-none stroke-current stroke-2'"
                                viewBox="0 0 24 24">
                                <path stroke-linejoin="round" stroke-linecap="round" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                        </button>
                    </template>
                </div>
                @else
                <div class="flex items-center gap-1 justify-center py-2 opacity-70">
                    @for($i = 1; $i <= 5; $i++)
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $i <= round($activity->avg_quality_score) ? 'text-amber-400 fill-current' : 'text-gray-300 dark:text-gray-600 fill-current' }}" viewBox="0 0 24 24">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                    @endfor
                </div>
                @endif

                <!-- Modal de Desglose de Votos -->
                <div x-show="showRatingsModal" 
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    x-cloak
                    @click.self="showRatingsModal = false">
                    
                    <div class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transform transition-all text-left"
                        x-transition:enter="transition ease-out duration-300 transform"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-200 transform"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95">
                        
                        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500 fill-current" viewBox="0 0 24 24">
                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    </svg>
                                    {{ __('Desglose de Calidad') }}
                                </h3>
                                <p class="text-[11px] text-gray-500 font-medium">{{ __('Votos de los miembros de este equipo') }}</p>
                            </div>
                            <button @click="showRatingsModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 max-h-[350px] overflow-y-auto space-y-4">
                            @forelse($ratings as $rating)
                                <div class="flex items-center justify-between p-3.5 bg-gray-50 dark:bg-gray-800/40 rounded-2xl border border-gray-100/50 dark:border-gray-800/50">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $rating->user->profile_photo_url }}" 
                                            alt="{{ $rating->user->name }}" 
                                            class="w-9 h-9 rounded-full object-cover ring-2 ring-amber-500/10 shrink-0">
                                        <div>
                                            <div class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                                {{ $rating->user->name }}
                                            </div>
                                            <div class="text-[10px] text-gray-400 font-semibold">
                                                {{ $rating->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-0.5 shrink-0 bg-white dark:bg-gray-900 px-3 py-1.5 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $i <= $rating->score ? 'text-amber-400 fill-current' : 'text-gray-200 dark:text-gray-700 fill-current' }}" viewBox="0 0 24 24">
                                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                            </svg>
                                        @endfor
                                        <span class="text-[11px] font-black text-gray-700 dark:text-gray-300 ml-1.5 leading-none">{{ number_format($rating->score, 1) }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8">
                                    <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/20 rounded-full flex items-center justify-center mx-auto mb-3 text-amber-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.961 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.373-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </div>
                                    <p class="text-xs text-gray-400 font-medium">{{ __('Aún no hay valoraciones para esta tarea.') }}</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs text-gray-500 font-medium">
                            <span>{{ __('Puntuación media:') }}</span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-black text-gray-900 dark:text-white">{{ $activity->avg_quality_score > 0 ? number_format($activity->avg_quality_score, 1) : '0.0' }}</span>
                                <span class="text-gray-400">/ 5</span>
                                <div class="flex items-center gap-0.5 ml-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $i <= round($activity->avg_quality_score) ? 'text-amber-400 fill-current' : 'text-gray-200 dark:text-gray-700 fill-current' }}" viewBox="0 0 24 24">
                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                        </svg>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif



            <!-- 1. Plan Maestro Related (Only if template/child) -->
            @if ($activity->is_template)
                <div class="bg-violet-50/30 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-900/30 rounded-2xl p-4 shadow-sm space-y-4">
                    <p class="text-[10px] font-bold text-violet-600 dark:text-violet-400 uppercase tracking-widest">{{ __('ACCIONES DEL PLAN MAESTRO') }}</p>
                    
                    <div class="space-y-2">
                        @if ($activity->status_value !== 'completed')
                            <button onclick="updateTaskStatus('completed')"
                                class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-3 rounded-xl transition-all shadow-md shadow-emerald-600/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('Cerrar Plan Maestro') }}
                            </button>
                        @else
                            <button onclick="updateTaskStatus('in_progress')"
                                class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-400 text-white text-xs font-bold py-3 rounded-xl transition-all shadow-md shadow-amber-500/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ __('Reabrir Plan Maestro') }}
                            </button>
                        @endif

                        @if ($activity->status_value !== 'blocked')
                            <button onclick="updateTaskStatus('blocked')"
                                class="w-full flex items-center justify-center gap-2 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs font-bold py-3 rounded-xl transition-all border border-red-100 dark:border-red-900/30">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ __('Informar bloqueo') }}
                            </button>
                        @else
                            <button onclick="updateTaskStatus('in_progress')"
                                class="w-full flex items-center justify-center gap-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold py-3 rounded-xl transition-all border border-emerald-100 dark:border-emerald-900/30 shadow-sm active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                </svg>
                                {{ __('Quitar bloqueo') }}
                            </button>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-violet-100 dark:border-violet-900/20">
                        <div class="flex items-center justify-between text-[9px] font-black uppercase tracking-widest text-violet-400 mb-1">
                            <span>{{ __('activities.roadmap_progress') }}</span>
                            <span class="js-global-progress-val">{{ $activity->progress }}%</span>
                        </div>
                        <div class="w-full h-1 bg-violet-100 dark:bg-violet-900/30 rounded-full overflow-hidden">
                            <div class="h-full bg-violet-500 transition-all duration-1000 js-global-progress-bar" style="width: {{ $activity->progress }}%"></div>
                        </div>
                    </div>
                </div>
            @elseif ($activity->isInstance())
                <div class="bg-violet-50/50 dark:bg-violet-500/5 border border-violet-100 dark:border-violet-500/10 rounded-2xl p-4 space-y-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0 shadow-sm border border-violet-50 dark:border-violet-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black text-violet-700 dark:text-violet-400 uppercase tracking-widest">{{ __('Plan Maestro Relacionado') }}</p>
                            @if ($team->isCoordinator(auth()->user()))
                                <div class="mt-1">
                                    <select onchange="reassignTask({{ $activity->id }}, this.value)" class="w-full text-[10px] bg-white dark:bg-violet-900 border border-violet-100 dark:border-violet-800 rounded-lg px-2 py-1 shadow-sm font-bold text-violet-700 dark:text-violet-300 cursor-pointer">
                                        <option value="" disabled {{ !$activity->assigned_user_id ? 'selected' : '' }}>{{ __('Reasignar a...') }}</option>
                                        <option value="unassign">-- {{ __('Pendiente') }} --</option>
                                        @foreach($team->members()->orderBy('name')->get() as $member)
                                            <option value="{{ $member->id }}" {{ $activity->assigned_user_id === $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <p class="text-[11px] font-bold text-violet-900 dark:text-violet-200 truncate">{{ $activity->assignedUser?->name ?? __('Sin asignar') }}</p>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('teams.activities.show', [$team, $activity->parent_id]) }}" class="block w-full text-center text-[10px] font-black uppercase tracking-widest text-violet-600 dark:text-violet-300 hover:bg-violet-600 hover:text-white dark:hover:bg-violet-500 py-2 bg-white dark:bg-violet-500/10 rounded-xl border border-violet-100 dark:border-violet-500/20 transition-all">
                        {{ __('VER PLAN MAESTRO') }}
                    </a>
                </div>
            @endif

            <!-- 2. TU EJECUCIÓN Card -->
            @if ($personalInstance)
                <div class="bg-violet-50/40 dark:bg-violet-900/10 border border-violet-100/50 dark:border-violet-800/50 rounded-2xl p-5 space-y-5 shadow-sm transition-colors relative overflow-hidden">
                    <p class="text-[10px] text-violet-600 dark:text-violet-400 uppercase tracking-widest font-black flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ __('TU EJECUCIÓN') }}
                    </p>

                    <div class="space-y-2.5">
                        @if ($personalInstance->status_value !== 'completed')
                            <button onclick="updateTaskStatus('completed', {{ $personalInstance->id }})"
                                class="w-full flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold py-3.5 rounded-xl transition-all shadow-md shadow-violet-600/20 active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('Marcar como completada') }}
                            </button>
                        @else
                            <button onclick="updateTaskStatus('pending', {{ $personalInstance->id }})"
                                class="w-full flex items-center justify-center gap-2 bg-white dark:bg-gray-800 text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-900/50 text-xs font-bold py-3.5 rounded-xl transition-all border border-violet-200 dark:border-violet-700 shadow-sm active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ __('Reabrir tarea') }}
                            </button>
                        @endif

                        @if ($personalInstance->status_value !== 'blocked')
                            <button onclick="updateTaskStatus('blocked', {{ $personalInstance->id }})"
                                class="w-full flex items-center justify-center gap-2 bg-red-50/80 hover:bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs font-bold py-3.5 rounded-xl transition-all border border-red-100/50 dark:border-red-900/30 active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ __('Informar un bloqueo') }}
                            </button>
                        @else
                            <button onclick="updateTaskStatus('in_progress', {{ $personalInstance->id }})"
                                class="w-full flex items-center justify-center gap-2 bg-emerald-50/80 hover:bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold py-3.5 rounded-xl transition-all border border-emerald-100/50 dark:border-emerald-900/30 active:scale-[0.98] shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                </svg>
                                {{ __('Quitar bloqueo') }}
                            </button>
                        @endif
                    </div>

                    <div class="relative pt-4 border-t border-violet-100/30 dark:border-violet-800/30">
                        <label class="flex items-center justify-between text-[9px] text-violet-400/80 dark:text-violet-500/50 uppercase tracking-widest font-black mb-3">
                            <span>{{ __('TU PROGRESO') }}</span>
                            <div class="flex items-center gap-1 min-w-[3rem] justify-end font-bold">
                                <span id="personal-progress-val" class="text-violet-600 dark:text-violet-400 tabular-nums text-sm">{{ $personalInstance->progress_percentage }}</span>
                                <span class="text-violet-400 text-[10px]">%</span>
                            </div>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" value="{{ $personalInstance->progress_percentage }}"
                                class="flex-1 h-1 bg-violet-100 dark:bg-violet-900/50 rounded-full appearance-none cursor-pointer accent-violet-600 js-member-progress-slider"
                                oninput="document.getElementById('personal-progress-val').innerText = this.value"
                                onchange="updateTaskProgress(this.value, {{ $personalInstance->id }}, '{{ $personalInstance->status_value }}')">
                        </div>
                    </div>
                </div>
            @endif

            <!-- 3. TIEMPO DEDICADO Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm transition-colors">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-black mb-4">{{ __('TIEMPO DEDICADO') }}</p>
                <div class="flex items-center justify-between">
                    <div x-data="{ 
                        active: {{ auth()->user()->isTrackingTask($personalInstance->id ?? $activity->id) ? 'true' : 'false' }},
                        seconds: {{ auth()->user()->getTaskTrackingSeconds($personalInstance->id ?? $activity->id) }},
                        totalToday: '{{ $activity->totalTrackedTimeTodayHuman() }}',
                        
                        get formatted() {
                            const h = Math.floor(this.seconds / 3600);
                            const m = Math.floor((this.seconds % 3600) / 60);
                            const s = this.seconds % 60;
                            return [h,m,s].map(v => v.toString().padStart(2, '0')).join(':');
                        },
                        init() {
                            if (this.active) {
                                setInterval(() => { this.seconds++ }, 1000);
                            }
                        }
                    }" class="flex-1">
                        <div class="text-3xl font-black text-gray-900 dark:text-white tabular-nums tracking-tight mb-0.5" x-text="formatted">00:00:00</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">
                            Total hoy: <span class="text-gray-600 dark:text-gray-300" x-text="totalToday">0m</span>
                        </div>
                    </div>
                    
                    <div class="shrink-0">
                        @include('teams.activities.partials.activity-timer-button', ['activity' => $personalInstance ?? $activity])
                    </div>
                </div>
            </div>

            <!-- 5. Propietario Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm dark:shadow-none">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3">
                    {{ __('activities.owner') }}
                </p>
                <div class="flex items-center gap-3">
                    <img src="{{ $activity->creator ? $activity->creator->profile_photo_url : 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF' }}" 
                        alt="{{ $activity->creator?->name ?? '?' }}"
                        class="w-10 h-10 rounded-xl object-cover shadow-sm border border-gray-100 dark:border-gray-800 shrink-0">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300 truncate">{{ $activity->creator?->name ?? '—' }}</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 uppercase font-black tracking-tighter">{{ $activity->created_at->format('d M Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- 6. Estado Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-bold">{{ __('activities.status') }}</span>
                    <span class="text-[11px] font-bold px-3 py-1 rounded-full border {{ $statusColor }} uppercase tracking-wider">
                        {{ __('activities.statuses.' . $activity->status_value) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-bold">{{ __('activities.quadrant') }}</span>
                    <span class="text-[11px] font-bold {{ $qCfg['color'] }} uppercase tracking-wider">
                        Q{{ $q }}: {{ __('activities.quadrants.' . $q . '.label') }}
                    </span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('activities.visibility') }}</span>
                    <div class="flex items-center gap-1.5">
                        @if($activity->privacy_level === 'private')
                            <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ __('activities.private') }}
                            </span>
                        @elseif($activity->privacy_level === 'semi-private')
                            <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ __('Semiprivada') }}
                            </span>
                        @else
                            <div class="w-2 h-2 rounded-full bg-violet-500"></div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ __('activities.public') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 7. Prioridad Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-3 shadow-sm">
                @foreach ([['activities.priority', $activity->priority, 'activities.priorities'], ['activities.urgency', $activity->urgency, 'activities.urgencies']] as [$lbl, $val, $map])
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __($lbl) }}</span>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 {{ $map === 'activities.priorities' ? 'js-priority-label' : '' }}">{{ __($map . '.' . $val) }}</span>
                    </div>
                @endforeach

                <div class="pt-2 border-t border-gray-50 dark:border-gray-800/50 mt-2">
                    <button id="btn-auto-priority" onclick="toggleAutoPriority()" 
                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all duration-300 {{ $activity->auto_priority ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 border border-violet-100 dark:border-violet-800' : 'bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 border border-transparent hover:border-gray-200 dark:hover:border-gray-700' }}">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $activity->auto_priority ? 'animate-pulse' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span class="text-[10px] font-bold uppercase tracking-wider">{{ __('Prioridad Automática') }}</span>
                        </div>
                        <div class="relative inline-flex h-4 w-7 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $activity->auto_priority ? 'bg-violet-500' : 'bg-gray-200 dark:bg-gray-700' }}">
                            <span class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $activity->auto_priority ? 'translate-x-3' : 'translate-x-0' }}"></span>
                        </div>
                    </button>
                    @if($activity->due_date)
                        <p class="text-[9px] text-gray-400 mt-1.5 px-1 italic">
                            {{ __('La prioridad escalará según el tiempo restante hasta la entrega.') }}
                        </p>
                    @endif
                </div>
            </div>

            <!-- Expediente Card -->
            @if ($activity->expediente)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-100 dark:border-violet-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('Expediente') }}</h4>
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $activity->expediente->code }}</p>
                        </div>
                    </div>
                    <a href="{{ route('teams.expedientes.show', [$team, $activity->expediente]) }}" class="w-full flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-900/30 py-2 border border-violet-100 dark:border-violet-800/50 rounded-xl transition-all shadow-sm">
                        {{ __('Ver Expediente') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            @elseif (auth()->user()->can('update', $activity))
                <div class="bg-gray-50 dark:bg-gray-900/50 border border-dashed border-gray-200 dark:border-gray-800 rounded-2xl p-4 text-center shadow-sm">
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-2">{{ __('Sin Expediente') }}</p>
                    <a href="{{ route('teams.activities.edit', [$team, $activity]) }}" class="text-[9px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 hover:underline">
                        {{ __('Vincular uno ahora') }}
                    </a>
                </div>
            @endif

            <!-- Cita Previa Card -->
            @if ($activity->appointment)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shrink-0 border border-cyan-100 dark:border-cyan-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('Cita Previa') }}</h4>
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">Loc: {{ $activity->appointment->localizador }}</p>
                        </div>
                    </div>
                    @if(in_array($activity->appointment->modality, ['jitsi', 'meet']))
                        <a href="{{ route('public.appointments.video.auth', $activity->appointment) }}?localizador={{ $activity->appointment->localizador }}" target="_blank" class="w-full flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest text-cyan-700 dark:text-cyan-300 hover:bg-cyan-50 dark:hover:bg-cyan-900/30 py-2 border border-cyan-100 dark:border-cyan-800/50 rounded-xl transition-all shadow-sm">
                            💻 {{ __('Iniciar Videoconferencia') }}
                        </a>
                    @else
                        <p class="w-full text-center text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 py-2 border border-gray-100 dark:border-gray-800/50 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                            🏢 {{ __('Modalidad Presencial') }}
                        </p>
                    @endif
                </div>
            @endif

            <!-- 8. Fechas Card -->
            @if ($activity->due_date || $activity->scheduled_date)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-3 shadow-sm">
                    @if ($activity->scheduled_date)
                        <div class="flex items-center justify-between pb-3 border-b border-gray-50 dark:border-gray-800/50">
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ $activity->is_autoprogrammable ? 'Inicio del Ciclo' : (__('activities.scheduled_date') ?? 'Fecha de Inicio') }}</span>
                            <span class="text-[11px] text-gray-700 dark:text-gray-300 font-bold tabular-nums">{{ $activity->scheduled_date->format('d M Y, H:i') }}</span>
                        </div>
                    @endif
                    @if ($activity->due_date)
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('activities.due_date') }}</span>
                            <span class="text-[11px] text-gray-700 dark:text-gray-300 font-bold tabular-nums">{{ $activity->due_date->format('d M Y, H:i') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- 9. Autoprogramación Card -->
            @if ($activity->is_autoprogrammable)
                <div class="bg-white dark:bg-gray-900 border border-violet-100 dark:border-violet-900/30 rounded-2xl p-4 space-y-3 shadow-sm border-l-4 border-l-violet-500">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] text-violet-600 dark:text-violet-400 uppercase tracking-widest font-bold">{{ __('activities.autoprogram_active') ?? 'Autoprogramación JIT' }}</p>
                        <div class="w-2 h-2 rounded-full bg-violet-500 animate-pulse"></div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-[11px]">
                            <span class="text-gray-400 font-medium">{{ __('activities.frequency') }}:</span>
                            <span class="font-bold text-gray-700 dark:text-gray-300">{{ __('activities.' . ($activity->autoprogram_settings['frequency'] ?? 'daily')) }} (x{{ $activity->autoprogram_settings['interval'] ?? 1 }})</span>
                        </div>
                        @if(isset($activity->autoprogram_settings['next_occurrence_at']))
                            <div class="flex justify-between text-[11px] pt-1 border-t border-gray-50 dark:border-gray-800">
                                <span class="text-gray-400 font-medium">Próxima ocurrencia:</span>
                                <span class="text-violet-600 dark:text-violet-400 font-black">{{ \Carbon\Carbon::parse($activity->autoprogram_settings['next_occurrence_at'])->format('d M Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- 10. Quota de disco Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('activities.disk_quota') }}</h3>
                    <span class="text-[10px] text-gray-400 font-black tabular-nums">{{ number_format(auth()->user()->disk_used / 1024 / 1024, 1) }}MB / {{ number_format(auth()->user()->disk_quota / 1024 / 1024, 0) }}MB</span>
                </div>
                @php
                    $perc = auth()->user()->disk_quota > 0 ? (auth()->user()->disk_used / auth()->user()->disk_quota) * 100 : 0;
                    $barColor = $perc > 90 ? 'bg-red-500' : ($perc > 70 ? 'bg-amber-500' : 'bg-blue-500');
                @endphp
                <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                    <div class="h-full {{ $barColor }} transition-all duration-1000 shadow-sm" style="width: {{ $perc }}%"></div>
                </div>
            </div>


            <!-- 11. Etiquetas (Capacidades) -->
            @php $taskSkills = $activity->skills; @endphp
            @if($taskSkills->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($taskSkills as $skill)
                        <div class="group inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-900 border border-amber-100 dark:border-amber-900/40 rounded-xl shadow-sm hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all duration-300 cursor-default">
                            <div class="w-1.5 h-1.5 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 shadow-sm shadow-amber-500/20 group-hover:scale-125 transition-transform"></div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-[9px] font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest truncate leading-tight">{{ $skill->name }}</span>
                                <span class="text-[7px] text-amber-600/40 dark:text-amber-500/20 font-bold uppercase tracking-tighter truncate leading-none">{{ $skill->category }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Historial de cambios como Timeline -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="bg-gray-50/50 dark:bg-gray-800/50 px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('activities.activity_history') ?? 'Historial de Actividad' }}
                    </h3>
                </div>
                <div class="p-6 custom-scrollbar" style="max-height: 280px; overflow-y: auto;">
                    <div class="relative ml-4 border-l-2 border-gray-100 dark:border-gray-800 pl-8 space-y-8">
                        @forelse (($activity->histories?->sortByDesc('created_at') ?? collect())->take(10) as $log)
                            <div onclick="showHistoryDiff({{ $log->id }})" class="relative group cursor-pointer">
                                <!-- Dot -->
                                <div class="absolute -left-[41px] top-1 w-5 h-5 rounded-full border-4 border-white dark:border-gray-900 bg-violet-500 shadow-sm ring-4 ring-violet-50 dark:ring-violet-900/20 group-hover:scale-125 transition-transform"></div>
                                
                                <div class="bg-gray-50/50 dark:bg-gray-800/30 rounded-2xl p-4 border border-transparent group-hover:border-violet-100 dark:group-hover:border-violet-900/30 transition-all">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ $log->user?->name ?? 'Sistema' }}</span>
                                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-lg bg-violet-100 dark:bg-violet-900/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/50 shadow-sm">
                                                {{ $log->action_label ?? 'ACTUALIZACIÓN' }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-gray-400 font-bold tabular-nums">{{ $log->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <img src="{{ $log->user ? $log->user->profile_photo_url : 'https://ui-avatars.com/api/?name=S&color=7c3aed&background=f5f3ff' }}" 
                                                alt="{{ $log->user?->name ?? 'System' }}"
                                                class="w-6 h-6 rounded-full object-cover border border-white dark:border-gray-800 shadow-sm">
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Realizó cambios en los detalles de la tarea</p>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-300 dark:text-gray-600 group-hover:text-violet-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-10 text-center">
                                <div class="w-12 h-12 bg-gray-50 dark:bg-gray-800/50 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-sm text-gray-400 italic">{{ __('activities.no_history') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Foro / Discusión -->
            <div class="mt-0">
                @include('teams.forum.partials.thread-widget', ['task' => $activity])
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            function nudgeUser(taskIds, userId = null) {
                const isBulk = Array.isArray(taskIds) || (taskIds && typeof taskIds === 'object' && taskIds.length !== undefined);
                const ids = isBulk ? Array.from(taskIds) : [taskIds];
                
                Swal.fire({
                    title: isBulk ? '¿Enviar recordatorio masivo?' : '¿Enviar recordatorio?',
                    html: `
                        <p class="text-sm text-gray-500 mb-4">${isBulk ? 'Se enviará un recordatorio a todos los miembros seleccionados.' : 'Se enviará un recordatorio al miembro responsable.'}</p>
                        <textarea id="nudge-message" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-violet-500 min-h-[100px] p-3 shadow-inner" placeholder="Escribe un mensaje personalizado del coordinador (opcional)..."></textarea>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#7c3aed',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Enviar',
                    cancelButtonText: 'Cancelar',
                    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827',
                    preConfirm: () => {
                        return document.getElementById('nudge-message').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const customMessage = result.value;
                        const url = isBulk ? `{{ route('teams.activities.bulk-nudge', $team) }}` : `{{ route('teams.activities.nudge', [$team, 'TASK_ID']) }}`.replace('TASK_ID', taskIds);
                        const cleanTaskIds = ids.map(target => target.toString().split(':')[0]);
                        const payload = isBulk ? { targets: ids, task_ids: cleanTaskIds, custom_message: customMessage } : { custom_message: customMessage };
                        if (userId) payload.user_id = userId;

                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(async response => {
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                const text = await response.text();
                                console.error('Non-JSON response:', text);
                                throw new Error('El servidor no devolvió una respuesta JSON válida. Verifica la conexión o la URL del dominio.');
                            }
                            const data = await response.json();
                            if (!response.ok) {
                                throw new Error(data.message || data.error || 'Error en la petición al servidor.');
                            }
                            return data;
                        })
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: '¡Listo!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                                }).then(() => {
                                    if (isBulk) {
                                        window.location.reload();
                                    }
                                });
                            } else {
                                Swal.fire('Error', data.message || 'No se pudo enviar el recordatorio.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Nudge Error:', error);
                            Swal.fire('Error', error.message || 'Ocurrió un error en la conexión.', 'error');
                        });
                    }
                });
            }

            function reassignTask(taskId, userId) {
                if (!userId) return;
                
                const payloadValue = userId === 'unassign' ? null : userId;
                
                fetch(`/teams/{{ $team->id }}/activities/${taskId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        assigned_user_id: payloadValue
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Asignación actualizada'
                        }).then(() => location.reload());
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'No se ha podido cambiar la asignación.',
                        icon: 'error'
                    });
                });
            }

            function updateTaskStatus(status, taskId = {{ $activity->id }}) {
                const messages = {
                    'completed': '¿Marcar como completada?',
                    'blocked': '¿Informar un bloqueo en esta tarea?',
                    'pending': '¿Reabrir esta tarea?',
                    'in_progress': '¿Quitar el bloqueo de esta tarea?'
                };

                Swal.fire({
                    title: messages[status] || '¿Cambiar estado?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: status === 'blocked' ? '#ef4444' : '#6366f1',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar',
                    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/teams/{{ $team->id }}/activities/${taskId}/move`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content')
                                },
                                body: JSON.stringify({
                                    status: status
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Actualizado!',
                                        text: 'El estado se ha actualizado correctamente.',
                                        icon: 'success',
                                        timer: 1500,
                                        showConfirmButton: false,
                                        background: document.documentElement.classList.contains('dark') ?
                                            '#111827' : '#fff',
                                        color: document.documentElement.classList.contains('dark') ?
                                            '#fff' : '#111827'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    title: 'Error',
                                    text: 'No se pudo actualizar el estado',
                                    icon: 'error',
                                    background: document.documentElement.classList.contains('dark') ?
                                        '#111827' : '#fff',
                                    color: document.documentElement.classList.contains('dark') ? '#fff' :
                                        '#111827'
                                });
                            });
                    }
                });
            }

            function updateTaskProgress(progress, taskId = {{ $activity->id }}, currentStatus = '{{ $activity->status_value }}') {

                fetch(`/teams/{{ $team->id }}/activities/${taskId}/move`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            progress_percentage: progress
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // If status has changed (e.g. from completed back to in_progress), reload
                            if (data.task_status !== currentStatus || progress == 100) {
                                window.location.reload();
                            } else {
                                // Subtle label update without animations that feel like glitches
                                // Actualización masiva de todos los elementos de progreso
                                const finalProgress = data.parent_progress !== null ? data.parent_progress : data.task_progress;
                                const finalProgressRounded = Math.round(finalProgress);

                                document.querySelectorAll('.js-global-progress-val').forEach(el => el.innerText = finalProgressRounded + '%');
                                document.querySelectorAll('.js-global-progress-bar').forEach(el => el.style.width = finalProgress + '%');

                                // Sincronizar todos los miembros (tareas colaborativas/maestras)
                                document.querySelectorAll('.js-member-progress-bar').forEach(bar => {
                                    bar.style.width = finalProgress + '%';
                                });
                                document.querySelectorAll('.js-member-progress-val').forEach(val => {
                                    val.innerText = finalProgressRounded + '%';
                                });
                                document.querySelectorAll('.js-member-progress-slider').forEach(slider => {
                                    slider.value = finalProgressRounded;
                                });

                                // Elemento específico del sidebar si existe
                                const valSpan = document.getElementById('progress-val');
                                if (valSpan) valSpan.innerText = finalProgressRounded;

                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            function toggleAutoPriority() {
                const btn = document.getElementById('btn-auto-priority');
                if (!btn) return;

                fetch(`/teams/{{ $team->id }}/activities/{{ $activity->id }}/toggle-auto-priority`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                console.error('Server Error:', text);
                                throw new Error('Error del servidor: ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Update UI state
                            const isOn = data.auto_priority;
                            
                            // Update button styles
                            btn.classList.toggle('bg-violet-50', isOn);
                            btn.classList.toggle('dark:bg-violet-900/20', isOn);
                            btn.classList.toggle('text-violet-600', isOn);
                            btn.classList.toggle('dark:text-violet-400', isOn);
                            btn.classList.toggle('border-violet-100', isOn);
                            btn.classList.toggle('dark:border-violet-800', isOn);
                            
                            btn.classList.toggle('bg-gray-50', !isOn);
                            btn.classList.toggle('dark:bg-gray-800/50', !isOn);
                            btn.classList.toggle('text-gray-500', !isOn);
                            btn.classList.toggle('dark:text-gray-400', !isOn);
                            btn.classList.toggle('border-transparent', !isOn);
                            
                            const svg = btn.querySelector('svg');
                            if (svg) svg.classList.toggle('animate-pulse', isOn);
                            
                            const dot = btn.querySelector('span.pointer-events-none');
                            const bg = btn.querySelector('div.relative.inline-flex');
                            if (dot) {
                                dot.style.transform = isOn ? 'translateX(0.75rem)' : 'translateX(0)';
                                bg.classList.toggle('bg-violet-500', isOn);
                                bg.classList.toggle('bg-gray-200', !isOn);
                                bg.classList.toggle('dark:bg-gray-700', !isOn);
                            }

                            // Update priority label if it changed
                            document.querySelectorAll('.js-priority-label').forEach(el => {
                                el.innerText = data.priority_label;
                            });

                            if (typeof Toast !== 'undefined') {
                                Toast.fire({
                                    icon: 'success',
                                    title: isOn ? 'Prioridad automática activada' : 'Prioridad automática desactivada'
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('AutoPriority Error:', error);
                        let errorMsg = 'No se pudo actualizar la prioridad automática';
                        
                        // Si tenemos un objeto de error con mensaje específico
                        if (error.message && error.message.includes('Error del servidor')) {
                            // Intentamos no hacer nada especial, pero el throw de arriba ya tiene el status
                        }

                        if (typeof Toast !== 'undefined') {
                            Toast.fire({
                                icon: 'error',
                                title: errorMsg
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: errorMsg,
                                icon: 'error',
                                background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                            });
                        }
                    });
            }

            function editAttachmentImage(id, url) {
                if (typeof window.openGlobalImageEditor === 'function') {
                    window.openGlobalImageEditor(url, (editedFile) => {
                        const formData = new FormData();
                        formData.append('file', editedFile);
                        
                        Swal.fire({
                            title: 'Guardando...',
                            text: 'Actualizando la imagen en el servidor',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch(`/teams/{{ $team->id }}/attachments/${id}/replace`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Actualizada!',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                throw new Error(data.message || 'Error al guardar la imagen');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', error.message, 'error');
                        });
                    });
                }
            }

            function renameAttachment(id, currentName) {
                Swal.fire({
                    title: "{{ __('activities.rename_attachment') }}",
                    input: 'text',
                    inputLabel: "{{ __('activities.new_name') }}",
                    inputValue: currentName,
                    showCancelButton: true,
                    confirmButtonText: "{{ __('Save Changes') }}",
                    cancelButtonText: "{{ __('Cancel') }}",
                    inputValidator: (value) => {
                        if (!value) {
                            return '¡El nombre no puede estar vacío!'
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/teams/{{ $team->id }}/attachments/${id}`;
                        
                        // Add CSRF token safely
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = '{{ csrf_token() }}';
                        form.appendChild(csrfInput);

                        // Add Method override safely
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'PATCH';
                        form.appendChild(methodInput);

                        // Add File Name safely
                        const fileInput = document.createElement('input');
                        fileInput.type = 'hidden';
                        fileInput.name = 'file_name';
                        fileInput.value = result.value;
                        form.appendChild(fileInput);

                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }

            function confirmAttachmentDelete(id, provider = 'local') {
                if (provider === 'google') {
                    Swal.fire({
                        title: '¿Qué deseas hacer?',
                        text: "Este archivo está en Google Drive. ¿Quieres eliminarlo de la nube o solo desvincularlo de esta tarea?",
                        icon: 'question',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Eliminar de Drive y MTX',
                        denyButtonText: 'Solo desvincular de MTX',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#ef4444',
                        denyButtonColor: '#6b7280',
                        customClass: {
                            popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]',
                            denyButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]',
                            cancelButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Delete from both
                            const form = document.getElementById(`delete-attachment-${id}`);
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'delete_from_drive';
                            input.value = '1';
                            form.appendChild(input);
                            form.submit();
                        } else if (result.isDenied) {
                            // Only unlink
                            document.getElementById(`delete-attachment-${id}`).submit();
                        }
                    });
                } else {
                    Swal.fire({
                        title: "{{ __('activities.delete_attachment_confirm') }}",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: '{{ __('Sí, eliminar') }}',
                        cancelButtonText: '{{ __('Cancelar') }}',
                        customClass: {
                            popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]',
                            cancelButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById(`delete-attachment-${id}`).submit();
                        }
                    });
                }
            }

            async function handleAttachmentUpload(input) {
                const files = input.files;
                if (!files.length) return;

                const limit = "{{ ini_get('upload_max_filesize') }}";
                const limitBytes = parsePHPSize(limit);

                let totalSize = 0;

                // 1. Check PHP upload limit
                for (let i = 0; i < files.length; i++) {
                    totalSize += files[i].size;
                    if (files[i].size > limitBytes) {
                        Swal.fire({
                            title: '{{ __('Archivo demasiado grande') }}',
                            text: `El archivo ${files[i].name} excede el límite de ${limit} configurado en el servidor.`,
                            icon: 'error',
                            background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                            color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                        });
                        input.value = '';
                        return;
                    }
                }

                // 2. Check team quota BEFORE uploading
                try {
                    const res = await fetch('{{ route("teams.quota-status", $team) }}', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (res.ok) {
                        const quota = await res.json();
                        if (totalSize > quota.available_bytes) {
                            const usedMB = (quota.disk_used / 1024 / 1024).toFixed(1);
                            const totalMB = (quota.disk_quota / 1024 / 1024).toFixed(1);
                            Swal.fire({
                                title: '⚠️ Almacenamiento lleno',
                                html: `El equipo ha alcanzado su límite de almacenamiento o los archivos seleccionados exceden el espacio disponible.<br><small style="opacity:.7">${usedMB} MB / ${totalMB} MB usados</small><br><br>Un coordinador debe liberar espacio antes de poder subir más archivos.`,
                                icon: 'warning',
                                background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827',
                                confirmButtonColor: '#7c3aed'
                            });
                            input.value = '';
                            return;
                        }
                    }
                } catch (e) {
                    console.warn('Quota pre-check failed, proceeding with upload.', e);
                }

                sessionStorage.setItem('task_show_scrollpos', window.scrollY);
                document.getElementById('attachment-form').submit();
            }

            function parsePHPSize(size) {
                const unit = size.slice(-1).toUpperCase();
                const value = parseFloat(size);
                switch (unit) {
                    case 'G': return value * 1024 * 1024 * 1024;
                    case 'M': return value * 1024 * 1024;
                    case 'K': return value * 1024;
                    default: return value;
                }
            }

            // Restore scroll position after attachment upload
            const scrollpos = sessionStorage.getItem('task_show_scrollpos');
            if (scrollpos) {
                setTimeout(() => {
                    window.scrollTo({ top: parseInt(scrollpos), behavior: 'instant' });
                }, 50);
                sessionStorage.removeItem('task_show_scrollpos');
            }

            // Inteligencia Premium: Recarga automática al volver de editar un documento en OnlyOffice
            window.addEventListener('focus', function() {
                if (sessionStorage.getItem('needs_office_reload')) {
                    sessionStorage.removeItem('needs_office_reload');
                    window.location.reload();
                }
            });
        </script>


    @endpush

    @push('modals')
        <x-google-drive-picker :team="$team" />
    <div id="activity-history-diff-modal" class="hidden fixed inset-0 z-[110] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-950/80 backdrop-blur-sm transition-opacity" onclick="closeHistoryDiff()"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-200 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-gray-900/50">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white heading uppercase tracking-tight" id="history-diff-action">Cambios Realizados</h3>
                        <p id="history-diff-date" class="text-xs text-gray-500 dark:text-gray-400 font-medium"></p>
                    </div>
                    <button onclick="closeHistoryDiff()" class="text-gray-400 hover:text-gray-500 p-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <div class="px-6 py-6 max-h-[70vh] overflow-y-auto custom-scrollbar" id="history-diff-content">
                    <!-- Diff will be injected here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Attachment History Modal -->
    <div id="attachment-history-modal" class="hidden fixed inset-0 z-[110] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-950/80 backdrop-blur-sm transition-opacity" onclick="closeAttachmentHistory()"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-200 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-gray-900/50">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white heading uppercase tracking-tight">Historial del Archivo</h3>
                        <p id="history-filename" class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate max-w-sm"></p>
                    </div>
                    <button onclick="closeAttachmentHistory()" class="text-gray-400 hover:text-gray-500 p-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <div class="px-6 py-6 max-h-[60vh] overflow-y-auto" id="history-content">
                    <!-- Logs will be injected here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function showHistoryDiff(id) {
            const histories = @json($activity->histories->sortByDesc('created_at')->take(15)->values());
            const log = histories.find(h => h.id == id);
            
            if (!log || !log.old_values || !log.new_values) {
                // If it's a simple action without values (like 'cloned' or 'blocked'), we might just show notes
                if (log && log.notes) {
                    Swal.fire({
                        title: log.action.toUpperCase(),
                        text: log.notes,
                        icon: 'info',
                        background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                    });
                }
                return;
            }

            document.getElementById('history-diff-action').innerText = log.action.toUpperCase();
            document.getElementById('history-diff-date').innerText = new Date(log.created_at).toLocaleString();
            const content = document.getElementById('history-diff-content');
            content.innerHTML = '';

            const fieldLabels = {
                'title': 'Título',
                'description': 'Descripción',
                'status': 'Estado',
                'priority': 'Prioridad',
                'urgency': 'Urgencia',
                'progress_percentage': 'Progreso',
                'due_date': 'Fecha de entrega',
                'scheduled_date': 'Fecha de inicio',
                'visibility': 'Visibilidad',
                'observations': 'Observaciones',
                'cognitive_load': 'Carga cognitiva',
                'is_backstage': 'Backstage',
                'skill_id': 'Capacidad principal',
                'service_id': 'Servicio asociado'
            };

            const valueFormatters = {
                'status': (v) => {
                    const map = { 'pending': 'Pendiente', 'in_progress': 'En Progreso', 'completed': 'Completada', 'cancelled': 'Cancelada', 'blocked': 'Bloqueada' };
                    return map[v] || v;
                },
                'priority': (v) => {
                    const map = { 'low': 'Baja', 'medium': 'Media', 'high': 'Alta', 'critical': 'Crítica' };
                    return map[v] || v;
                },
                'urgency': (v) => {
                    const map = { 'low': 'Baja', 'medium': 'Media', 'high': 'Alta', 'critical': 'Crítica' };
                    return map[v] || v;
                },
                'progress_percentage': (v) => v + '%',
                'visibility': (v) => v === 'public' ? 'Público' : 'Privado',
                'is_backstage': (v) => v ? 'Sí' : 'No',
                'due_date': (v) => v ? new Date(v).toLocaleString() : '—',
                'scheduled_date': (v) => v ? new Date(v).toLocaleString() : '—',
                'autoprogram_settings': (v) => {
                    if (!v) return '—';
                    try {
                        const obj = typeof v === 'string' ? JSON.parse(v) : v;
                        return '<pre class="whitespace-pre-wrap font-mono text-[9px]">' + JSON.stringify(obj, null, 2) + '</pre>';
                    } catch (e) { return v; }
                }
            };

            const ignoredFields = ['updated_at', 'created_at', 'id', 'uuid', 'google_synced_at', 'matrix_order', 'kanban_order', 'kanban_column_id'];
            
            let hasChanges = false;
            let html = '<div class="space-y-4">';

            for (const key in log.new_values) {
                if (ignoredFields.includes(key)) continue;
                
                const oldVal = log.old_values[key];
                const newVal = log.new_values[key];

                if (JSON.stringify(oldVal) !== JSON.stringify(newVal)) {
                    hasChanges = true;
                    const label = fieldLabels[key] || key;
                    const formatter = valueFormatters[key] || ((v) => {
                        if (v === null || v === undefined) return '—';
                        if (typeof v === 'boolean') return v ? 'Sí' : 'No';
                        return v;
                    });
                    
                    html += `
                        <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-4 border border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">${label}</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-stretch">
                                <div class="bg-red-50 dark:bg-red-900/10 text-red-700 dark:text-red-400 p-2 rounded-lg text-xs border border-red-100 dark:border-red-900/20 line-through opacity-60 break-all overflow-hidden">
                                    ${formatter(oldVal)}
                                </div>
                                <div class="bg-emerald-50 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-400 p-2 rounded-lg text-xs border border-emerald-100 dark:border-emerald-900/20 font-bold break-all overflow-hidden">
                                    ${formatter(newVal)}
                                </div>
                            </div>
                        </div>
                    `;
                }
            }

            if (!hasChanges) {
                html += '<p class="text-center text-gray-500 italic py-4">No hay cambios detallados registrados para esta acción.</p>';
            }

            if (log.notes) {
                html += `
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/20 rounded-xl">
                        <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">NOTAS</p>
                        <p class="text-xs text-blue-700 dark:text-blue-300 font-medium">${log.notes}</p>
                    </div>
                `;
            }

            html += '</div>';
            content.innerHTML = html;

            document.getElementById('activity-history-diff-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeHistoryDiff() {
            document.getElementById('activity-history-diff-modal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeHistoryDiff();
        });

        function showAttachmentHistory(id) {
            const attachments = @json($allAttachments);
            const attachment = attachments.find(a => a.id == id);
            
            if (!attachment) return;

            document.getElementById('history-filename').innerText = attachment.file_name;
            const content = document.getElementById('history-content');
            content.innerHTML = '';

            if (attachment.logs && attachment.logs.length > 0) {
                const logs = attachment.logs.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                
                let html = '<div class="space-y-6 relative ml-4 border-l-2 border-gray-100 dark:border-gray-800 pl-8">';
                
                logs.forEach(log => {
                    const date = new Date(log.created_at).toLocaleString();
                    const actionColors = {
                        'upload': 'bg-emerald-500',
                        'download': 'bg-blue-500',
                        'view': 'bg-violet-500',
                        'rename': 'bg-amber-500',
                        'move_to_drive': 'bg-violet-500',
                        'delete': 'bg-red-500'
                    };
                    
                    const actionIcons = {
                        'upload': '<path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v8" />',
                        'download': '<path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />',
                        'view': '<path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />',
                        'rename': '<path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />',
                        'move_to_drive': '<path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />'
                    };

                    const actionLabel = {
                        'upload': 'Subida de archivo',
                        'download': 'Descarga realizada',
                        'view': 'Visualización online',
                        'rename': 'Cambio de nombre',
                        'move_to_drive': 'Traspaso a Google Drive',
                        'delete': 'Eliminación'
                    };

                    let metaHtml = '';
                    if (log.metadata) {
                        if (log.metadata.original_name) metaHtml = `<p class="mt-1 text-gray-400">Origen: <span class="font-bold text-gray-600 dark:text-gray-300 italic">${log.metadata.original_name}</span></p>`;
                        if (log.metadata.old_name) metaHtml = `<p class="mt-1 text-gray-400">De <span class="line-through">${log.metadata.old_name}</span> a <span class="font-bold text-gray-600 dark:text-gray-300">${log.metadata.new_name}</span></p>`;
                    }

                    html += `
                        <div class="relative">
                            <div class="absolute -left-[45px] top-1 w-8 h-8 rounded-full border-4 border-white dark:border-gray-900 ${actionColors[log.action] || 'bg-gray-400'} flex items-center justify-center text-white shadow-sm ring-4 ring-gray-100 dark:ring-gray-800/30">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    ${actionIcons[log.action] || '<circle cx="12" cy="12" r="10" />'}
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight">${actionLabel[log.action]}</span>
                                    <span class="text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-2 py-0.5 rounded-full font-bold tabular-nums">${date}</span>
                                </div>
                                <div class="flex items-center gap-2 group">
                                    <img src="${log.user ? (log.user.profile_photo_path ? '/storage/' + log.user.profile_photo_path : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(log.user.name) + '&color=7F9CF5&background=EBF4FF') : 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF'}" 
                                        class="w-5 h-5 rounded-full object-cover shadow-sm" alt="${log.user?.name || '?'}">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-tighter">${log.user?.name || 'Sistema'}</span>
                                    ${log.ip_address ? `<span class="text-[9px] text-gray-400 font-mono bg-gray-50 dark:bg-gray-800/50 px-1.5 py-0.5 rounded">IP: ${log.ip_address}</span>` : ''}
                                </div>
                                ${metaHtml}
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="text-center py-10"><p class="text-gray-500 italic">No hay movimientos registrados para este archivo todavía.</p></div>';
            }

            document.getElementById('attachment-history-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeAttachmentHistory() {
            document.getElementById('attachment-history-modal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAttachmentHistory();
        });

        function copyTaskJson() {
            const btn = event.currentTarget;
            
            btn.disabled = true;
            btn.style.opacity = '0.5';

            fetch("{{ route('teams.activities.export-json', [$team, $activity]) }}", {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                const jsonStr = JSON.stringify(data, null, 4);
                navigator.clipboard.writeText(jsonStr).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Copiado!',
                        text: 'El JSON de la tarea está en tu portapapeles.',
                        timer: 2000,
                        showConfirmButton: false,
                        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                    });
                });
            })
            .catch(e => {
                console.error(e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo obtener el JSON de la tarea.'
                });
            })
            .finally(() => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }
    </script>
