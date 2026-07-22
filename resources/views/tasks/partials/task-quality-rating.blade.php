            <!-- Quality Rating Widget -->
            @php
                $canRate = $task->assignedTo()->where('users.id', auth()->id())->exists() || $task->assigned_user_id === auth()->id() || $team->isManager(auth()->user());
                $ratings = $task->ratings()->with('user')->get();
                $userRating = $ratings->where('user_id', auth()->id())->first();
                $currentVal = $userRating ? $userRating->score : 0;
                $ratingsCount = $ratings->count();
            @endphp

            @if($canRate || $task->avg_quality_score > 0)
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
                            <span class="text-lg font-black text-gray-900 dark:text-white leading-none" id="avg-rating-display">{{ $task->avg_quality_score > 0 ? number_format($task->avg_quality_score, 1) : '0.0' }}</span>
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
                            const res = await fetch('{{ route('teams.activities.rate', [$team, $task]) }}', {
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
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $i <= round($task->avg_quality_score) ? 'text-amber-400 fill-current' : 'text-gray-300 dark:text-gray-600 fill-current' }}" viewBox="0 0 24 24">
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
                                <span class="text-sm font-black text-gray-900 dark:text-white">{{ $task->avg_quality_score > 0 ? number_format($task->avg_quality_score, 1) : '0.0' }}</span>
                                <span class="text-gray-400">/ 5</span>
                                <div class="flex items-center gap-0.5 ml-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $i <= round($task->avg_quality_score) ? 'text-amber-400 fill-current' : 'text-gray-200 dark:text-gray-700 fill-current' }}" viewBox="0 0 24 24">
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



