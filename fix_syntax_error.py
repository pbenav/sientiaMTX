import os
import re

# We will minify the CSS block and make sure it's safely injected without breaking JS strings.
MINIFIED_CSS = "@media print { pre, code, pre *, code *, .prose pre, .prose code { background-color: transparent !important; color: #000 !important; } div[class*='bg-gray-8'], div[class*='bg-gray-9'], div[class*='bg-slate-8'], div[class*='bg-slate-9'], div[style*='background'], .prose div, .markdown-body div, .bg-gray-800, .bg-gray-900, .dark\\\\:bg-gray-800, .dark\\\\:bg-gray-900 { background-color: transparent !important; } pre, .prose pre { border: 1px solid #cbd5e1 !important; border-radius: 0.25rem !important; white-space: pre-wrap !important; word-break: break-all !important; padding: 0.5rem !important; } code, .prose code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace !important; } }"

files_to_check = []
for root, dirs, files in os.walk('resources/views'):
    for file in files:
        if file.endswith('.blade.php'):
            files_to_check.append(os.path.join(root, file))

# Regex to find the injected block, which starts with `@media print {` and ends with `}` and contains `/* -- Print Mode Code Block Fix -- */`
# But it might be broken across lines
pattern = re.compile(r'@media print \{\s*/\* -- Print Mode Code Block Fix -- \*/.*?\n\}\s*\n', re.DOTALL)

for filepath in files_to_check:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if '/* -- Print Mode Code Block Fix -- */' in content:
        # replace the old block with MINIFIED_CSS
        new_content = pattern.sub(MINIFIED_CSS, content)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Fixed {filepath}")
