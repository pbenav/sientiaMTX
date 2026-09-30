import os

CSS_SNIPPET = """
@media print {
    /* -- Print Mode Code Block Fix -- */
    pre, code, pre *, code *, .prose pre, .prose code {
        background-color: transparent !important;
        color: #000 !important;
    }
    pre, .prose pre {
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.25rem !important;
        white-space: pre-wrap !important;
        word-break: break-all !important;
        padding: 0.5rem !important;
    }
    code, .prose code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
    }
}
"""

files_to_check = []
for root, dirs, files in os.walk('resources/views'):
    for file in files:
        if file.endswith('.blade.php'):
            files_to_check.append(os.path.join(root, file))

for filepath in files_to_check:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if 'printWin.document.write' in content or 'window.open' in content:
        if '<style>' in content:
            if '/* -- Print Mode Code Block Fix -- */' not in content:
                new_content = content.replace('<style>', '<style>' + CSS_SNIPPET)
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                print(f"Patched {filepath}")
