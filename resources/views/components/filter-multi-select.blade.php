@props([
    'name' => 'type',
    'options' => [],
    'selected' => [],
    'placeholder' => 'Tipo de actividad',
    'width' => 'w-48',
])

@php
    $selectedArray = is_array($selected) ? array_values(array_filter($selected)) : (empty($selected) ? [] : explode(',', (string) $selected));
    $selectedCount = count($selectedArray);
    $allKeys = array_keys($options);
@endphp

<div x-data="{
        open: false,
        selected: {{ json_encode($selectedArray) }},
        options: {{ json_encode($options) }},
        toggle(val) {
            const idx = this.selected.indexOf(val);
            if (idx > -1) {
                this.selected.splice(idx, 1);
            } else {
                this.selected.push(val);
            }
        },
        selectAll() {
            this.selected = {{ json_encode($allKeys) }};
        },
        clearAll() {
            this.selected = [];
        },
        apply() {
            this.open = false;
            this.$nextTick(() => {
                if (this.$refs.form) {
                    this.$refs.form.submit();
                } else {
                    const form = this.$el.closest('form');
                    if (form) form.submit();
                }
            });
        },
        get labelText() {
            if (this.selected.length === 0) {
                return '{{ $placeholder }}';
            }
            if (this.selected.length === 1) {
                const opt = this.options[this.selected[0]];
                return opt ? (opt.label || opt) : this.selected[0];
            }
            return this.selected.length + ' {{ __('seleccionados') }}';
        }
    }"
    @click.outside="if (open) { apply(); }"
    @keydown.escape.window="if (open) { open = false; }"
    class="relative inline-block {{ $width }}">

    <!-- Hidden inputs for form submission -->
    <template x-for="val in selected" :key="val">
        <input type="hidden" name="{{ $name }}[]" :value="val">
    </template>

    <!-- Trigger Button -->
    <button type="button"
            @click="open = !open"
            class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-xs font-bold uppercase transition-all shadow-sm border cursor-pointer select-none text-left"
            :class="selected.length > 0
                ? 'bg-violet-50/50 dark:bg-violet-900/10 border-violet-300 dark:border-violet-800 ring-2 ring-violet-500/20 text-violet-700 dark:text-violet-300'
                : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'">
        
        <span class="truncate" x-text="labelText">{{ $placeholder }}</span>
        
        <div class="flex items-center gap-1 shrink-0">
            <template x-if="selected.length > 0">
                <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-violet-200 dark:bg-violet-800 text-violet-800 dark:text-violet-200 font-extrabold" x-text="selected.length"></span>
            </template>
            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </button>

    <!-- Dropdown Panel -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-1 scale-95"
         class="absolute left-0 top-full mt-2 z-50 w-64 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-xl overflow-hidden p-2 backdrop-blur-xl"
         style="display: none;">

        <!-- Header Actions: Seleccionar todos / Limpiar -->
        <div class="flex items-center justify-between px-2 py-1.5 mb-1 border-b border-gray-100 dark:border-gray-800 text-[11px] font-semibold">
            <button type="button" @click="selectAll()" class="text-violet-600 dark:text-violet-400 hover:underline">
                {{ __('Todos') }}
            </button>
            <button type="button" @click="clearAll()" class="text-gray-400 hover:text-red-500 hover:underline">
                {{ __('Limpiar') }}
            </button>
        </div>

        <!-- Options list -->
        <div class="max-h-60 overflow-y-auto space-y-0.5 custom-scrollbar py-1">
            @foreach($options as $val => $opt)
                @php
                    $optLabel = is_array($opt) ? ($opt['label'] ?? $val) : $opt;
                @endphp
                <div @click="toggle('{{ $val }}')"
                     class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl hover:bg-violet-50/60 dark:hover:bg-violet-900/20 cursor-pointer select-none transition-colors group">
                    <input type="checkbox"
                           value="{{ $val }}"
                           :checked="selected.includes('{{ $val }}')"
                           @click.stop="toggle('{{ $val }}')"
                           class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500 border-gray-300 dark:border-gray-600 dark:bg-gray-800 cursor-pointer transition-all">
                    <span class="text-xs text-gray-700 dark:text-gray-300 group-hover:text-violet-700 dark:group-hover:text-violet-300 flex-1 truncate">
                        {{ $optLabel }}
                    </span>
                </div>
            @endforeach
        </div>

        <!-- Footer Apply Button -->
        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-800">
            <button type="button"
                    @click="apply()"
                    class="w-full py-1.5 px-3 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-sm">
                {{ __('Aplicar') }}
            </button>
        </div>
    </div>
</div>
