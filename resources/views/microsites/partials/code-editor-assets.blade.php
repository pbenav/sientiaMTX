{{-- CodeMirror 5: Editor de código profesional, ligero y ampliado para HTML y CSS --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/theme/dracula.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/dialog/dialog.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/foldgutter.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/display/fullscreen.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/show-hint.min.css">

<style>
    .CodeMirror {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
        font-size: 13px !important;
        line-height: 1.6 !important;
        border-radius: 0.75rem !important;
        box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.25) !important;
        border: 1px solid #374151 !important;
        resize: vertical;
    }
    .cm-css-wrapper .CodeMirror {
        height: 240px !important;
        min-height: 160px;
    }
    .cm-html-wrapper .CodeMirror {
        height: 580px !important;
        min-height: 350px;
    }
    .cm-s-dracula.CodeMirror {
        background-color: #18181b !important;
        color: #f3f4f6 !important;
    }
    .cm-s-dracula .CodeMirror-gutters {
        background-color: #121215 !important;
        border-right: 1px solid #27272a !important;
        padding-right: 4px !important;
    }
    .cm-s-dracula .CodeMirror-linenumber {
        color: #71717a !important;
        font-size: 11px !important;
    }
    .cm-s-dracula .CodeMirror-activeline-background {
        background: rgba(236, 72, 153, 0.08) !important;
    }
    .CodeMirror-cursor {
        border-left: 2px solid #ec4899 !important;
    }
    .CodeMirror-focused .CodeMirror-selected {
        background: rgba(236, 72, 153, 0.25) !important;
    }
    .CodeMirror-matchingbracket {
        text-decoration: underline !important;
        color: #ec4899 !important;
        font-weight: bold !important;
    }
    .cm-matchhighlight {
        background-color: rgba(236, 72, 153, 0.22) !important;
        border-bottom: 1px dashed #ec4899 !important;
    }
    .CodeMirror-dialog {
        background-color: #27272a !important;
        color: #f3f4f6 !important;
        border-bottom: 1px solid #3f3f46 !important;
        padding: 6px 12px !important;
        font-size: 12px !important;
        border-top-left-radius: 0.75rem !important;
        border-top-right-radius: 0.75rem !important;
    }
    .CodeMirror-dialog input {
        background-color: #18181b !important;
        color: #f3f4f6 !important;
        border: 1px solid #52525b !important;
        border-radius: 6px !important;
        padding: 3px 8px !important;
        outline: none !important;
    }
    .CodeMirror-foldmarker {
        color: #ec4899 !important;
        text-shadow: none !important;
        cursor: pointer;
    }
    .CodeMirror-foldgutter {
        width: 14px;
    }
    .CodeMirror-foldgutter-open,
    .CodeMirror-foldgutter-folded {
        cursor: pointer;
        color: #71717a;
        line-height: 1;
    }
    .CodeMirror-foldgutter-open:after {
        content: "▾";
    }
    .CodeMirror-foldgutter-folded:after {
        content: "▸";
        color: #ec4899;
    }
    .CodeMirror-hints {
        position: absolute;
        z-index: 999999 !important;
        overflow: hidden;
        list-style: none;
        margin: 0;
        padding: 4px;
        border-radius: 0.75rem !important;
        border: 1px solid #3f3f46 !important;
        background: #18181b !important;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        font-size: 12px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.6), 0 8px 10px -6px rgba(0, 0, 0, 0.6) !important;
        max-height: 20em;
        overflow-y: auto;
    }
    .CodeMirror-hint {
        margin: 0;
        padding: 4px 8px;
        border-radius: 0.375rem;
        white-space: pre;
        color: #d1d5db;
        cursor: pointer;
    }
    li.CodeMirror-hint-active {
        background: #ec4899 !important;
        color: #ffffff !important;
    }
    .CodeMirror-fullscreen {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        height: 100vh !important;
        z-index: 99999 !important;
        border-radius: 0 !important;
        border: none !important;
    }
</style>

{{-- Core & Modes --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/htmlmixed/htmlmixed.min.js"></script>

{{-- Addons: Edición, etiquetas y corchetes --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/edit/closetag.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/edit/closebrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/selection/active-line.min.js"></script>

{{-- Addons: Búsqueda, Reemplazo y Resaltado de coincidencias --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/dialog/dialog.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/search/searchcursor.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/search/search.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/search/match-highlighter.min.js"></script>

{{-- Addons: Comentar (Ctrl+/) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/comment/comment.min.js"></script>

{{-- Addons: Plegado de código (Folding) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/foldcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/foldgutter.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/brace-fold.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/xml-fold.min.js"></script>

{{-- Addons: Pantalla Completa (F11 / botón) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/display/fullscreen.min.js"></script>

{{-- Addons: Autocompletado (Ctrl+Espacio) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/show-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/xml-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/html-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/css-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/anyword-hint.min.js"></script>

<script>
    (function () {
        function setupFullscreenWatcher(editor) {
            editor.on('fullscreenChange', function(cm, fullScreen) {
                let exitBtn = document.getElementById('cm-exit-fullscreen-btn');
                if (fullScreen) {
                    if (!exitBtn) {
                        exitBtn = document.createElement('button');
                        exitBtn.id = 'cm-exit-fullscreen-btn';
                        exitBtn.type = 'button';
                        exitBtn.innerHTML = `
                            <svg class="w-4 h-4 shrink-0 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>{{ __('Salir de pantalla completa') }} <kbd class="ml-1 px-1.5 py-0.5 rounded bg-gray-800 text-[10px] text-gray-300 font-mono">Esc</kbd></span>
                        `;
                        exitBtn.className = 'fixed top-4 right-6 z-[100000] flex items-center gap-2 px-3.5 py-2 bg-gray-900/95 hover:bg-black text-white text-xs font-bold rounded-xl shadow-2xl border border-gray-700/80 backdrop-blur-md transition-all cursor-pointer';
                        document.body.appendChild(exitBtn);
                    }
                    exitBtn.onclick = function() {
                        cm.setOption('fullScreen', false);
                    };
                    exitBtn.style.display = 'flex';
                } else if (exitBtn) {
                    exitBtn.style.display = 'none';
                }
            });
        }

        function initCodeMirrorEditors() {
            if (typeof CodeMirror === 'undefined') return;

            const baseConfig = {
                theme: 'dracula',
                lineNumbers: true,
                lineWrapping: true,
                matchBrackets: true,
                autoCloseBrackets: true,
                styleActiveLine: true,
                highlightSelectionMatches: { showToken: /\w/, annotateScrollbar: false },
                foldGutter: true,
                gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
                tabSize: 2,
                indentUnit: 2,
                extraKeys: {
                    'Ctrl-/': 'toggleComment',
                    'Cmd-/': 'toggleComment',
                    'Ctrl-Space': function(cm) { cm.showHint({ completeSingle: false }); },
                    'Cmd-Space': function(cm) { cm.showHint({ completeSingle: false }); },
                    'F11': function(cm) { cm.setOption('fullScreen', !cm.getOption('fullScreen')); },
                    'Esc': function(cm) { if (cm.getOption('fullScreen')) cm.setOption('fullScreen', false); }
                }
            };

            // Editor de CSS
            const cssEl = document.getElementById('css_content');
            if (cssEl && !window.cssCodeMirrorEditor) {
                const parentDiv = cssEl.closest('div');
                if (parentDiv) parentDiv.classList.add('cm-css-wrapper');

                window.cssCodeMirrorEditor = CodeMirror.fromTextArea(cssEl, Object.assign({}, baseConfig, {
                    mode: 'css'
                }));
                setupFullscreenWatcher(window.cssCodeMirrorEditor);
            }

            // Editor de HTML
            const htmlEl = document.getElementById('html_content');
            if (htmlEl && !window.htmlCodeMirrorEditor) {
                const parentDiv = htmlEl.closest('div');
                if (parentDiv) parentDiv.classList.add('cm-html-wrapper');

                window.htmlCodeMirrorEditor = CodeMirror.fromTextArea(htmlEl, Object.assign({}, baseConfig, {
                    mode: 'htmlmixed',
                    autoCloseTags: true
                }));
                setupFullscreenWatcher(window.htmlCodeMirrorEditor);
            }

            // Sincronización automática antes del envío del formulario
            const form = document.getElementById('microsite-form');
            if (form) {
                form.addEventListener('submit', function () {
                    if (window.cssCodeMirrorEditor) window.cssCodeMirrorEditor.save();
                    if (window.htmlCodeMirrorEditor) window.htmlCodeMirrorEditor.save();
                });
            }
        }

        // Alternar pantalla completa
        window.toggleCodeMirrorFullscreen = function(type) {
            const editor = type === 'css' ? window.cssCodeMirrorEditor : window.htmlCodeMirrorEditor;
            if (editor) {
                editor.setOption('fullScreen', !editor.getOption('fullScreen'));
                editor.focus();
            }
        };

        // Auto-indentar todo el documento
        window.formatCodeMirrorEditor = function(type) {
            const editor = type === 'css' ? window.cssCodeMirrorEditor : window.htmlCodeMirrorEditor;
            if (editor) {
                const totalLines = editor.lineCount();
                for (let i = 0; i < totalLines; i++) {
                    editor.indentLine(i);
                }
                editor.focus();
            }
        };

        // Función global invocada por los botones de guardar (barra flotante y formulario)
        window.submitMicrositeForm = function () {
            if (window.cssCodeMirrorEditor) window.cssCodeMirrorEditor.save();
            if (window.htmlCodeMirrorEditor) window.htmlCodeMirrorEditor.save();
            const form = document.getElementById('microsite-form');
            if (form) form.submit();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCodeMirrorEditors);
        } else {
            initCodeMirrorEditors();
        }
    })();
</script>
