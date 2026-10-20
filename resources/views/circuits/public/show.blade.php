<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $publication->document_code }} — {{ $publication->title }}</title>
    <meta name="description" content="{{ $publication->summary ?? $publication->title }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; font-size: 11pt; }
            .print-border { border: 1px solid #ddd !important; }
            .shadow-xs, .shadow-sm, .shadow-md, .shadow-lg { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased min-h-screen flex flex-col"
      x-data="{
          activeTab: 'pdf',
          viewerHeight: parseInt(localStorage.getItem('circuit_viewer_height') || '850'),
          isExpandedWidth: false,
          isFullscreen: false,
          toggleWidth() {
              this.isExpandedWidth = !this.isExpandedWidth;
          },
          setHeight(h) {
              this.viewerHeight = h;
              localStorage.setItem('circuit_viewer_height', h);
              const container = document.getElementById('pdf-viewer-wrapper');
              const iframe = document.getElementById('pdf-viewer-iframe');
              if (container) container.style.height = h + 'px';
              if (iframe) iframe.style.height = (h - 2) + 'px';
          },
          stepHeight(delta) {
              const newH = Math.max(400, Math.min(2000, this.viewerHeight + delta));
              this.setHeight(newH);
          },
          toggleFullscreen() {
              const el = document.getElementById('pdf-viewer-card');
              if (!document.fullscreenElement) {
                  if (el.requestFullscreen) {
                      el.requestFullscreen();
                      this.isFullscreen = true;
                  }
              } else {
                  if (document.exitFullscreen) {
                      document.exitFullscreen();
                      this.isFullscreen = false;
                  }
              }
          }
      }">

    @php
        $csvCode = $csv ?? ($publication->metadata['csv'] ?? $publication->public_token);
        $pdfStreamUrl = route('public.circuits.pdf', $publication->public_slug);
        $pdfDownloadUrl = route('public.circuits.download', $publication->public_slug);
        $verifyUrl = route('public.circuits.verify', ['csv' => $csvCode]);
        $attachmentsList = $attachments ?? collect();
    @endphp

    <!-- Official Navbar -->
    <header class="bg-white border-b border-gray-200 shadow-xs no-print sticky top-0 z-30">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-3 flex-wrap"
             :class="isExpandedWidth ? 'max-w-none' : 'max-w-6xl mx-auto'">
            <a href="{{ route('public.circuits.board', $publication->team) }}"
                class="inline-flex items-center gap-2 text-xs font-bold text-gray-600 hover:text-teal-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                <span>Volver al Tablón Edictal</span>
            </a>

            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ $verifyUrl }}" target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 transition-colors"
                    title="Cotejar validez oficial mediante Código Seguro de Verificación">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>Cotejar CSV</span>
                </a>

                <a href="{{ $pdfDownloadUrl }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white shadow-sm shadow-teal-600/20 transition-all hover:scale-105 active:scale-95"
                    title="Descargar el documento original consolidado en PDF con firmas reconocidas">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Descargar PDF Firmado</span>
                </a>

                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span class="hidden sm:inline">Imprimir</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main :class="isExpandedWidth ? 'w-full px-2 sm:px-6' : 'max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8'"
          class="flex-1 py-8 space-y-6 transition-all duration-300">

        <!-- Document Sheet Card -->
        <div class="bg-white rounded-3xl p-6 sm:p-10 border border-gray-200 shadow-sm print-border space-y-6">

            <!-- Document Header -->
            <div class="border-b-2 border-gray-900 pb-6 flex flex-col sm:flex-row justify-between items-start gap-4">
                <div class="space-y-1">
                    <span class="text-xs font-black tracking-widest uppercase text-teal-700 block">
                        {{ $publication->team->name }}
                    </span>
                    <span class="text-[11px] font-bold text-gray-400 block">
                        Tablón Edictal Oficial · Sede Electrónica
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight pt-1">
                        {{ $publication->title }}
                    </h1>
                </div>

                <div class="text-left sm:text-right shrink-0 bg-gray-50 p-4 rounded-2xl border border-gray-200 min-w-[200px]">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Código Oficial</div>
                    <div class="text-base font-black font-mono text-teal-700">{{ $publication->document_code }}</div>
                    <div class="text-[11px] text-gray-500 mt-1">Fecha: <strong>{{ $publication->publication_date->format('d/m/Y') }}</strong></div>
                    @if($publication->expiration_date)
                        <div class="text-[10px] text-gray-400 mt-0.5">Vence: {{ $publication->expiration_date->format('d/m/Y') }}</div>
                    @endif
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="border-b border-gray-200 no-print flex items-center justify-between gap-2 overflow-x-auto">
                <div class="flex items-center gap-2">
                    <button type="button" @click="activeTab = 'pdf'"
                        class="flex items-center gap-2 py-3 px-4 text-xs font-black uppercase tracking-wider border-b-2 transition-all shrink-0"
                        :class="activeTab === 'pdf' ? 'border-teal-600 text-teal-700 bg-teal-50/50 rounded-t-xl' : 'border-transparent text-gray-500 hover:text-gray-900'">
                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span>Visor PDF Oficial Firmado</span>
                    </button>

                    <button type="button" @click="activeTab = 'content'"
                        class="flex items-center gap-2 py-3 px-4 text-xs font-black uppercase tracking-wider border-b-2 transition-all shrink-0"
                        :class="activeTab === 'content' ? 'border-teal-600 text-teal-700 bg-teal-50/50 rounded-t-xl' : 'border-transparent text-gray-500 hover:text-gray-900'">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Transcripción del Documento</span>
                    </button>

                    @if($attachmentsList->isNotEmpty())
                        <button type="button" @click="activeTab = 'attachments'"
                            class="flex items-center gap-2 py-3 px-4 text-xs font-black uppercase tracking-wider border-b-2 transition-all shrink-0"
                            :class="activeTab === 'attachments' ? 'border-teal-600 text-teal-700 bg-teal-50/50 rounded-t-xl' : 'border-transparent text-gray-500 hover:text-gray-900'">
                            <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <span>Anexos Adjuntos ({{ $attachmentsList->count() }})</span>
                        </button>
                    @endif
                </div>

                <!-- Width expand helper in tab row -->
                <div x-show="activeTab === 'pdf'" class="hidden sm:flex items-center gap-1.5 pb-2">
                    <button type="button" @click="toggleWidth()"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold rounded-lg border border-gray-200 hover:bg-gray-100 text-gray-600 transition-colors"
                        title="Expandir a lo ancho de la pantalla">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span x-text="isExpandedWidth ? 'Reducir Ancho' : 'Ancho Completo'"></span>
                    </button>
                </div>
            </div>

            <!-- TAB 1: VISOR PDF OFICIAL FIRMADO (REDIMENSIONABLE) -->
            <div x-show="activeTab === 'pdf'" id="pdf-viewer-card" class="space-y-3">
                <!-- PDF Toolbar con Controles de Redimensión -->
                <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-200 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3 text-xs">
                    <!-- Title & CSV -->
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <div>
                            <p class="font-bold text-gray-900">Documento Oficial Consolidado con Diligencia de Firmas</p>
                            <p class="text-[11px] text-gray-500 font-mono">CSV: {{ $csvCode }}</p>
                        </div>
                    </div>

                    <!-- Dimension & Zoom Controls -->
                    <div class="flex items-center gap-2 flex-wrap w-full lg:w-auto justify-start lg:justify-end">
                        <!-- Botones de incremento / decremento fino -->
                        <div class="inline-flex items-center bg-white border border-gray-200 rounded-xl p-0.5 shadow-2xs">
                            <button type="button" @click="stepHeight(-150)"
                                class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                                title="Reducir altura (-150px)">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                            </button>
                            <span class="px-2 text-[11px] font-mono font-bold text-gray-600 select-none" x-text="viewerHeight + 'px'"></span>
                            <button type="button" @click="stepHeight(150)"
                                class="p-1.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                                title="Aumentar altura (+150px)">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>

                        <!-- Presets de Altura -->
                        <div class="hidden sm:inline-flex items-center bg-white border border-gray-200 rounded-xl p-0.5 shadow-2xs">
                            <button type="button" @click="setHeight(650)"
                                :class="viewerHeight === 650 ? 'bg-teal-600 text-white font-bold' : 'text-gray-600 hover:text-gray-900'"
                                class="px-2.5 py-1 text-[11px] rounded-lg transition-colors" title="Altura compacta (650px)">
                                650
                            </button>
                            <button type="button" @click="setHeight(850)"
                                :class="viewerHeight === 850 ? 'bg-teal-600 text-white font-bold' : 'text-gray-600 hover:text-gray-900'"
                                class="px-2.5 py-1 text-[11px] rounded-lg transition-colors" title="Altura estándar (850px)">
                                850
                            </button>
                            <button type="button" @click="setHeight(1200)"
                                :class="viewerHeight === 1200 ? 'bg-teal-600 text-white font-bold' : 'text-gray-600 hover:text-gray-900'"
                                class="px-2.5 py-1 text-[11px] rounded-lg transition-colors" title="Altura extendida (1200px)">
                                1200
                            </button>
                        </div>

                        <!-- Botón de Ancho Completo -->
                        <button type="button" @click="toggleWidth()"
                            :class="isExpandedWidth ? 'bg-teal-100 text-teal-800 border-teal-300 font-bold' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs border transition-all"
                            title="Alternar entre ancho estándar y ancho completo de pantalla">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            <span x-text="isExpandedWidth ? 'Ancho Estándar' : 'Ancho Completo'"></span>
                        </button>

                        <!-- Botón Pantalla Completa Nativos -->
                        <button type="button" @click="toggleFullscreen()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors"
                            title="Modo pantalla completa">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                            <span>Pantalla Completa</span>
                        </button>

                        <!-- Abrir pestaña / Descargar -->
                        <a href="{{ $pdfStreamUrl }}" target="_blank"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors"
                            title="Abrir PDF en pestaña independiente">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>

                        <a href="{{ $pdfDownloadUrl }}"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white transition-all shadow-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>Descargar</span>
                        </a>
                    </div>
                </div>

                <!-- Contenedor del Visor PDF Redimensionable con Tirador Inferior -->
                <div class="relative bg-gray-100 rounded-2xl border border-gray-200 shadow-inner overflow-hidden flex flex-col">
                    <div id="pdf-viewer-wrapper"
                         class="w-full relative transition-[height] duration-75"
                         :style="'height: ' + viewerHeight + 'px;'">
                        <iframe id="pdf-viewer-iframe"
                                src="{{ $pdfStreamUrl }}#toolbar=1&navpanes=0"
                                class="w-full h-full border-0 block"
                                title="Visor PDF Documento Oficial Firmado">
                            <div class="p-8 text-center space-y-4">
                                <p class="text-sm text-gray-600">Su navegador no soporta la visualización incrustada de PDFs.</p>
                                <a href="{{ $pdfDownloadUrl }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-black bg-teal-600 text-white">
                                    Descargar documento PDF
                                </a>
                            </div>
                        </iframe>
                    </div>

                    <!-- Tirador de Arrastre Vertical (Draggable Resizer Handle) -->
                    <div id="pdf-resizer-handle"
                         class="h-6 bg-gray-100 hover:bg-teal-600 text-gray-400 hover:text-white flex items-center justify-center cursor-row-resize select-none border-t border-gray-200 transition-colors group"
                         title="Arrastra verticalmente para ajustar la altura del visor a tu medida">
                        <div class="flex items-center gap-1.5">
                            <span class="w-6 h-0.5 bg-gray-300 group-hover:bg-white rounded-full"></span>
                            <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-white transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                            </svg>
                            <span class="text-[10px] font-bold tracking-wider uppercase opacity-75 group-hover:opacity-100">Arrastrar para Redimensionar</span>
                            <span class="w-6 h-0.5 bg-gray-300 group-hover:bg-white rounded-full"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: TRANSCRIPCIÓN MARKDOWN / CONTENIDO -->
            <div x-show="activeTab === 'content'" class="space-y-4">
                <div class="prose max-w-none text-gray-800 leading-relaxed text-sm sm:text-base border border-gray-100 bg-gray-50/40 p-6 sm:p-8 rounded-2xl">
                    @if(!empty($publication->content_html))
                        {!! str($publication->content_html)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    @else
                        <p class="italic text-gray-400">El cuerpo íntegro del documento se encuentra autenticado en el expediente y documento PDF oficial.</p>
                    @endif
                </div>
            </div>

            <!-- TAB 3: ANEXOS Y ARCHIVOS ADJUNTOS -->
            @if($attachmentsList->isNotEmpty())
                <div x-show="activeTab === 'attachments'" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($attachmentsList as $att)
                            @php
                                $isPdf = str_contains($att->mime_type ?? '', 'pdf') || str_ends_with(strtolower($att->file_name ?? ''), '.pdf');
                            @endphp
                            <div class="p-3.5 rounded-2xl border border-gray-200 bg-gray-50 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $isPdf ? 'bg-rose-100 text-rose-600' : 'bg-violet-100 text-violet-600' }}">
                                        @if($isPdf)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-900 truncate" title="{{ $att->file_name }}">{{ $att->file_name }}</p>
                                        <p class="text-[10px] text-gray-400 mt-0.5">{{ round(($att->file_size ?? 0) / 1024, 1) }} KB</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Diligencia Oficial de Aprobación y Trazabilidad de Firmas -->
            <div class="mt-8 p-6 rounded-2xl bg-gray-50 border border-gray-200 print-border">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                        Diligencia Oficial de Validación y Aprobación
                    </h3>
                </div>
                <p class="text-xs text-gray-500 mb-4">
                    Este documento ha superado el circuito preceptivo de tramitación interna conforme a la siguiente trazabilidad colegiada:
                </p>

                <!-- Signatures / Audit Trail Table -->
                @php
                    $signatures = $publication->metadata['signatures_audit'] ?? [];
                @endphp

                @if(!empty($signatures))
                    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-[10px] tracking-wider border-b border-gray-200">
                                <tr>
                                    <th class="py-2.5 px-3">Etapa</th>
                                    <th class="py-2.5 px-3">Acción</th>
                                    <th class="py-2.5 px-3">Interviniente / Cargo</th>
                                    <th class="py-2.5 px-3">Fecha y Hora</th>
                                    <th class="py-2.5 px-3">Resultado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($signatures as $sig)
                                    <tr>
                                        <td class="py-2.5 px-3 font-bold">{{ $sig['step_name'] ?? 'Etapa ' . ($sig['step_order'] ?? '') }}</td>
                                        <td class="py-2.5 px-3 font-mono capitalize">{{ $sig['action_type'] ?? 'Validación' }}</td>
                                        <td class="py-2.5 px-3 font-semibold">{{ $sig['signer_name'] ?? 'Responsable' }}</td>
                                        <td class="py-2.5 px-3 text-gray-500">
                                            {{ !empty($sig['completed_at']) ? \Carbon\Carbon::parse($sig['completed_at'])->format('d/m/Y H:i:s') : '—' }}
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                {{ ucfirst($sig['decision'] ?? 'Aprobado') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Electronic Verification Code (CSV) -->
                <div class="mt-4 pt-4 border-t border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs text-gray-600 font-mono">
                    <div class="space-y-0.5">
                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Código Seguro de Verificación (CSV):</span>
                        <strong class="text-gray-900 select-all text-xs bg-gray-100 px-2 py-0.5 rounded border border-gray-200">{{ $csvCode }}</strong>
                    </div>
                    <div>
                        <a href="{{ $verifyUrl }}" target="_blank"
                            class="inline-flex items-center gap-1 text-teal-700 hover:text-teal-800 font-bold underline">
                            <span>Sede Electrónica de Cotejo Oficial</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 text-center text-xs text-gray-400 no-print">
        <div class="max-w-5xl mx-auto px-4">
            <p>{{ $publication->team->name }} · Tablón Edictal Oficial y Transparencia</p>
        </div>
    </footer>

    <!-- Script de Arrastre para Redimensionar Visor PDF (Touch + Mouse) -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const handle = document.getElementById('pdf-resizer-handle');
            const wrapper = document.getElementById('pdf-viewer-wrapper');
            const iframe = document.getElementById('pdf-viewer-iframe');
            if (!handle || !wrapper || !iframe) return;

            let isResizing = false;
            let startY = 0;
            let startHeight = 0;

            function onStart(e) {
                isResizing = true;
                startY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0);
                startHeight = wrapper.offsetHeight;

                // Deshabilitar temporalmente eventos en iframe para evitar que capture el cursor del ratón
                iframe.style.pointerEvents = 'none';
                document.body.style.cursor = 'row-resize';
                document.body.style.userSelect = 'none';

                window.addEventListener('mousemove', onMove);
                window.addEventListener('mouseup', onEnd);
                window.addEventListener('touchmove', onMove, { passive: false });
                window.addEventListener('touchend', onEnd);
            }

            function onMove(e) {
                if (!isResizing) return;
                const clientY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0);
                const delta = clientY - startY;
                const newHeight = Math.max(380, Math.min(2400, Math.round(startHeight + delta)));

                wrapper.style.height = newHeight + 'px';
                localStorage.setItem('circuit_viewer_height', newHeight);

                // Notificar a Alpine para mantener sincronizado el badge de altura
                if (window.Alpine) {
                    const bodyEl = document.querySelector('body');
                    if (bodyEl && bodyEl._x_dataStack && bodyEl._x_dataStack[0]) {
                        bodyEl._x_dataStack[0].viewerHeight = newHeight;
                    }
                }
            }

            function onEnd() {
                if (!isResizing) return;
                isResizing = false;
                iframe.style.pointerEvents = 'auto';
                document.body.style.cursor = '';
                document.body.style.userSelect = '';

                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onEnd);
                window.removeEventListener('touchmove', onMove);
                window.removeEventListener('touchend', onEnd);
            }

            handle.addEventListener('mousedown', onStart);
            handle.addEventListener('touchstart', onStart, { passive: true });
        });
    </script>
</body>
</html>