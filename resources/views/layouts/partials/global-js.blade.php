    <!-- 💫 Sientia Premium UX: Intelligent Scroll & State Preserver 💫 -->
    <script>
        (function() {
            // Clave única por ruta para evitar conflictos de scroll entre distintas páginas
            const scrollKey = "sientia_scroll_pos_" + window.location.pathname;

            // 1. RESTAURACIÓN INSTANTÁNEA (SOLO EN RECARGAS)
            document.addEventListener("DOMContentLoaded", function() {
                const savedScroll = sessionStorage.getItem(scrollKey);

                // Verificar si la página fue recargada (reload) o si venimos de una navegación normal
                const navEntries = performance.getEntriesByType("navigation");
                const isReload = navEntries.length > 0 && navEntries[0].type === 'reload';

                if (savedScroll !== null) {
                    if (isReload) {
                        // Un micro-retardo de 30ms garantiza que el layout de Tailwind/Alpine ya se haya estabilizado
                        setTimeout(function() {
                            window.scrollTo({
                                top: parseInt(savedScroll, 10),
                                behavior: 'instant' // Evita la animación de scroll fluido al recargar para dar sensación de inmediatez
                            });
                            // Una vez restaurado, limpiamos la sesión para no forzar el scroll en visitas posteriores no deseadas
                            sessionStorage.removeItem(scrollKey);
                        }, 30);
                    } else {
                        // Si no es una recarga (por ejemplo, nueva visita desde otra página), limpiamos el scroll guardado
                        // para evitar saltar al final del formulario al acceder a los detalles o edición de tareas.
                        sessionStorage.removeItem(scrollKey);
                    }
                }
            });

            // 2. CAPTURA AL ABANDONAR LA VISTA (Refresco, Enlaces de acción, etc.)
            window.addEventListener("beforeunload", function() {
                sessionStorage.setItem(scrollKey, window.scrollY);
            });

            // 3. BLINDAJE EXTRA PARA FORMULARIOS
            // Salvaguarda ante submits que bloquean temporalmente antes de iniciar la recarga
            document.addEventListener("submit", function() {
                sessionStorage.setItem(scrollKey, window.scrollY);
            });
        })();
    </script>

    <!-- 🔗 Sientia Global Link Security & Navigation Flow 🔗 -->
    <script>
        (function() {
            /**
             * Processes all links within markdown-rendered containers to ensure external links
             * open in a new tab, preserving the application's SPA-like navigation flow.
             */
            const processMarkdownLinks = (container) => {
                if (!container || typeof container.querySelectorAll !== 'function') return;

                const markdownContainers = container.querySelectorAll('.prose, .markdown-content');

                markdownContainers.forEach(mc => {
                    const links = mc.querySelectorAll('a');
                    links.forEach(link => {
                        // Check if it's an external link
                        const href = link.getAttribute('href');
                        if (!href) return;

                        const isExternal = (href.startsWith('http') || href.startsWith('//')) &&
                                         !href.includes(window.location.hostname) &&
                                         !link.hasAttribute('target');

                        if (isExternal) {
                            link.setAttribute('target', '_blank');
                            link.setAttribute('rel', 'noopener noreferrer');
                        }
                    });
                });
            };

            // 1. Initial process on load
            document.addEventListener("DOMContentLoaded", () => {
                processMarkdownLinks(document);

                // 2. Observer for dynamic content (AI Assistant, Quick Notes, Livewire)
                const observer = new MutationObserver(mutations => {
                    mutations.forEach(mutation => {
                        mutation.addedNodes.forEach(node => {
                            if (node.nodeType === 1) { // Element node
                                if (node.matches('.prose, .markdown-content') || node.querySelector('.prose, .markdown-content')) {
                                    processMarkdownLinks(node);
                                }
                            }
                        });
                    });
                });

                observer.observe(document.body, { childList: true, subtree: true });
            });

            // 3. Hook into specific app events that might re-render markdown
            window.addEventListener('quicknote-state-changed', () => {
                setTimeout(() => processMarkdownLinks(document), 150);
            });
        })();
    </script>
    <!-- 🚀 Sientia Floating Draggable Trait 🚀 -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('floatingDraggable', () => ({
                isDragging: false,
                hasDragged: false,
                startX: 0,
                startY: 0,

                startDrag(e) {
                    if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input')) return;

                    this.isDragging = true;
                    this.$el.style.transition = 'none';

                    const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                    const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

                    const rect = this.$el.getBoundingClientRect();

                    if (!this.hasDragged) {
                        this.$el.style.bottom = 'auto';
                        this.$el.style.right = 'auto';
                        this.$el.style.transform = 'none';
                        this.$el.style.left = rect.left + 'px';
                        this.$el.style.top = rect.top + 'px';
                        this.hasDragged = true;
                    }

                    this.startX = clientX - rect.left;
                    this.startY = clientY - rect.top;
                },

                drag(e) {
                    if (!this.isDragging) return;
                    if (e.cancelable) e.preventDefault();

                    const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                    const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

                    let newLeft = clientX - this.startX;
                    let newTop = clientY - this.startY;

                    const rect = this.$el.getBoundingClientRect();
                    const maxLeft = window.innerWidth - rect.width;
                    const maxTop = window.innerHeight - rect.height;

                    newLeft = Math.max(0, Math.min(newLeft, maxLeft));
                    newTop = Math.max(0, Math.min(newTop, maxTop));

                    this.$el.style.left = newLeft + 'px';
                    this.$el.style.top = newTop + 'px';
                },

                stopDrag() {
                    if (!this.isDragging) return;
                    this.isDragging = false;
                    this.$el.style.transition = 'all 0.35s cubic-bezier(0.4, 0, 0.2, 1)';
                }
            }));
        });
    </script>
    <!-- Letrero Flotante de Celebración (Tipo Banner de Feria/Fiesta) -->
    <div x-data="{
            showCelebration: false,
            konami: { keys: [], code: ['arrowup','arrowup','arrowdown','arrowdown','arrowleft','arrowright','arrowleft','arrowright','b','a'] }
         }"
         @v1-celebration.window="showCelebration = true; setTimeout(() => showCelebration = false, 5000)"
         @keydown.window="
            konami.keys.push($event.key.toLowerCase());
            if (konami.keys.length > konami.code.length) konami.keys.shift();
            if (konami.keys.join(',') === konami.code.join(',')) {
                konami.keys = [];
                if (typeof window.triggerV1Celebration === 'function') window.triggerV1Celebration();
            }
         "
         x-show="showCelebration"
         x-cloak
         class="fixed inset-0 z-[999999] flex items-center justify-center pointer-events-none px-4"
         x-transition:enter="transition ease-out duration-700"
         x-transition:enter-start="opacity-0 scale-75 rotate-3"
         x-transition:enter-end="opacity-100 scale-100 rotate-0"
         x-transition:leave="transition ease-in duration-1000"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-110">

         <div class="relative w-full max-w-4xl rounded-sm shadow-[0_25px_60px_rgba(0,0,0,0.5)] overflow-hidden text-center transform transition-all" style="background-color: #fdfaf3; border: 1px solid #e5e7eb;">

            <!-- Borde interior decorativo -->
            <div class="absolute inset-2 border-2 border-dashed rounded-sm pointer-events-none" style="border-color: #d1c8b4;"></div>

            <!-- Banderines SVG -->
            <div class="absolute top-0 left-0 w-full h-24 opacity-90">
                <svg width="100%" height="100%" viewBox="0 0 1000 100" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M-10,10 Q250,60 500,20" stroke="#6b7280" stroke-width="2" fill="none"/>
                  <path d="M500,20 Q750,60 1010,10" stroke="#6b7280" stroke-width="2" fill="none"/>
                  <polygon points="50,22 80,60 110,28" fill="#ef4444" />
                  <polygon points="150,33 180,71 210,38" fill="#f59e0b" />
                  <polygon points="250,40 280,78 310,43" fill="#3b82f6" />
                  <polygon points="350,38 380,76 410,38" fill="#10b981" />
                  <polygon points="450,26 480,64 510,22" fill="#8b5cf6" />
                  <polygon points="550,22 580,60 610,26" fill="#ef4444" />
                  <polygon points="650,34 680,72 710,38" fill="#f59e0b" />
                  <polygon points="750,42 780,80 810,41" fill="#3b82f6" />
                  <polygon points="850,35 880,73 910,31" fill="#10b981" />
                  <polygon points="950,20 980,58 1010,12" fill="#8b5cf6" />
                </svg>
            </div>

            <div class="relative z-10 px-8 py-16 md:py-20 mt-4">
                <h3 class="text-lg md:text-xl font-bold tracking-[0.3em] uppercase mb-3" style="font-family: 'Arial', sans-serif; color: #14b8a6;">
                    Lanzamiento Oficial
                </h3>

                <h2 class="text-5xl md:text-7xl font-bold mb-6 drop-shadow-sm" style="font-family: 'Georgia', serif; color: #453c38;">
                    SientiaMTX <span style="color: #7c3aed;">v1.1.0</span>
                </h2>

                <div class="inline-block border-y-2 py-3 mb-6 px-8" style="border-color: #d1c8b4;">
                    <p class="text-xl md:text-2xl font-medium tracking-wider uppercase" style="color: #6b5c54;">
                        Plataforma • Equipo • Éxito
                    </p>
                </div>

                <p class="text-lg italic max-w-lg mx-auto" style="color: #6b7280;">
                    Gracias por acompañarnos y ser parte fundamental de este gran hito.
                </p>

                <!-- Decoración inferior geométrica -->
                <div class="absolute bottom-0 left-0 w-full h-4 flex">
                    <div class="flex-1" style="background-color: #ef4444;"></div>
                    <div class="flex-1" style="background-color: #14b8a6;"></div>
                    <div class="flex-1" style="background-color: #f59e0b;"></div>
                    <div class="flex-1" style="background-color: #ef4444;"></div>
                    <div class="flex-1" style="background-color: #14b8a6;"></div>
                    <div class="flex-1" style="background-color: #f59e0b;"></div>
                    <div class="flex-1" style="background-color: #ef4444;"></div>
                    <div class="flex-1" style="background-color: #14b8a6;"></div>
                    <div class="flex-1" style="background-color: #f59e0b;"></div>
                    <div class="flex-1" style="background-color: #ef4444;"></div>
                </div>
            </div>
         </div>
    </div>

    <script>
        window.triggerV1Celebration = function() {
            const fireConfetti = () => {
                var duration = 4 * 1000;
                var end = Date.now() + duration;
                (function frame() {
                    confetti({ particleCount: 7, angle: 60, spread: 55, origin: { x: 0 }, zIndex: 999999, colors: ['#8b5cf6', '#c4b5fd', '#f59e0b', '#10b981'] });
                    confetti({ particleCount: 7, angle: 120, spread: 55, origin: { x: 1 }, zIndex: 999999, colors: ['#8b5cf6', '#c4b5fd', '#f59e0b', '#10b981'] });
                    if (Date.now() < end) requestAnimationFrame(frame);
                }());

                // Disparar evento Alpine para mostrar el letrero
                window.dispatchEvent(new CustomEvent('v1-celebration'));
            };

            if (typeof confetti === 'undefined') {
                let s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js';
                s.onload = fireConfetti;
                document.head.appendChild(s);
            } else {
                fireConfetti();
            }
        };

        // Interceptores y reemplazos globales para modernizar alert() y confirm() con SweetAlert2
        (function() {
            // Sobrescribir window.alert nativo
            window.alert = function(message) {
                Swal.fire({
                    title: 'Aviso',
                    text: message,
                    icon: 'info',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#0ea5e9',
                    customClass: {
                        popup: 'rounded-[2rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                        confirmButton: 'rounded-xl px-5 py-2.5 text-[11px] font-black uppercase tracking-widest text-white focus:ring-0'
                    }
                });
            };

            // Interceptar clicks con confirm inline en fase de captura
            document.addEventListener('click', function(e) {
                const target = e.target.closest('[onclick*="confirm("]');
                if (target) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    const onclickAttr = target.getAttribute('onclick');
                    const match = onclickAttr.match(/confirm\(['"](.*?)['"]\)/);
                    const message = match ? match[1] : '¿Estás seguro?';

                    const isDanger = message.toLowerCase().includes('eliminar') ||
                                     message.toLowerCase().includes('borrar') ||
                                     message.toLowerCase().includes('cancelar') ||
                                     message.toLowerCase().includes('physical') ||
                                     message.toLowerCase().includes('físicamente') ||
                                     message.includes('⚠️');

                    Swal.fire({
                        title: isDanger ? '¿Estás seguro?' : 'Confirmación',
                        text: message,
                        icon: isDanger ? 'warning' : 'question',
                        showCancelButton: true,
                        confirmButtonText: isDanger ? 'Sí, proceder' : 'Aceptar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: isDanger ? '#e11d48' : '#0ea5e9',
                        cancelButtonColor: '#6b7280',
                        customClass: {
                            popup: 'rounded-[2rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-xl px-5 py-2.5 text-[11px] font-black uppercase tracking-widest text-white focus:ring-0',
                            cancelButton: 'rounded-xl px-5 py-2.5 text-[11px] font-black uppercase tracking-widest text-white focus:ring-0'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const originalOnclick = target.getAttribute('onclick');
                            target.removeAttribute('onclick');

                            if (target.type === 'submit' && target.form) {
                                if (target.name) {
                                    const hiddenInput = document.createElement('input');
                                    hiddenInput.type = 'hidden';
                                    hiddenInput.name = target.name;
                                    hiddenInput.value = target.value || '';
                                    target.form.appendChild(hiddenInput);
                                }
                                target.form.dataset.swalConfirmed = 'true';
                                target.form.submit();
                            } else {
                                target.click();
                            }

                            setTimeout(() => target.setAttribute('onclick', originalOnclick), 50);
                        }
                    });
                }
            }, true);

            // Interceptar submits con onsubmit="confirm(...)" inline en fase de captura
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (form.dataset.swalConfirmed) {
                    delete form.dataset.swalConfirmed;
                    return;
                }

                const onsubmitAttr = form.getAttribute('onsubmit');
                if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    const match = onsubmitAttr.match(/confirm\(['"](.*?)['"]\)/);
                    const message = match ? match[1] : '¿Estás seguro de que deseas continuar?';

                    const isDanger = message.toLowerCase().includes('eliminar') ||
                                     message.toLowerCase().includes('borrar') ||
                                     message.toLowerCase().includes('cancelar') ||
                                     message.toLowerCase().includes('physical') ||
                                     message.toLowerCase().includes('físicamente') ||
                                     message.includes('⚠️');

                    Swal.fire({
                        title: isDanger ? '¿Estás seguro?' : 'Confirmación',
                        text: message,
                        icon: isDanger ? 'warning' : 'question',
                        showCancelButton: true,
                        confirmButtonText: isDanger ? 'Sí, proceder' : 'Aceptar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: isDanger ? '#e11d48' : '#0ea5e9',
                        cancelButtonColor: '#6b7280',
                        customClass: {
                            popup: 'rounded-[2rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-xl px-5 py-2.5 text-[11px] font-black uppercase tracking-widest text-white focus:ring-0',
                            cancelButton: 'rounded-xl px-5 py-2.5 text-[11px] font-black uppercase tracking-widest text-white focus:ring-0'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.dataset.swalConfirmed = 'true';
                            form.submit();
                        }
                    });
                }
            }, true);
        })();
    </script>
